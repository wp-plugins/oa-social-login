<?php
require_once(dirname (dirname (dirname (dirname (dirname (__FILE__))))) . '/wp-load.php');


/**
 * Get the user details for a specific token
 */
function oa_social_login_get_user_by_token ($user_token)
{
	global $wpdb;
	$sql = "SELECT u.ID FROM $wpdb->usermeta AS um	INNER JOIN  $wpdb->users AS u ON (um.user_id=u.ID)	WHERE um.meta_key = 'oa_social_login_user_token' AND um.meta_value = '%s'";
	return $wpdb->get_var ($wpdb->prepare ($sql, $user_token));
}

/**
 * Create a random email
 */
function oa_social_login_create_rand_email ()
{
	do
	{
		$email = md5 (uniqid (wp_rand (10000, 99000))) . "@example.com";
	}
	while (email_exists ($email));

	return $email;
}

/**
 * Initialise
 */
function oa_social_login_init ()
{
	//Localization
	if (function_exists ('load_plugin_textdomain'))
	{
		load_plugin_textdomain ('oa_social_login', false, OA_SOCIAL_LOGIN_BASE_PATH . '/languages/');
	}

	//Callback Handler
	oa_social_login_callback ();
}

/**
 * Handle the callback
 */
function oa_social_login_callback ()
{
	//Callback Handler
	if (isset ($_POST) AND !empty ($_POST ['oa_action']) AND $_POST ['oa_action'] == 'social_login' AND !empty ($_POST ['connection_token']))
	{
		//Read settings
		$settings = get_option ('oa_social_login_settings');

		//API Settings
		$api_subdomain = (!empty ($settings ['api_subdomain']) ? $settings ['api_subdomain'] : '');
		$api_key = (!empty ($settings ['api_key']) ? $settings ['api_key'] : '');
		$api_secret = (!empty ($settings ['api_secret']) ? $settings ['api_secret'] : '');

		//Get user profile
		$curl = curl_init ();
		curl_setopt ($curl, CURLOPT_URL, 'https://' . $api_subdomain . '.api.oneall.com/connections/' . $_POST ['connection_token'] . '.json');
		curl_setopt ($curl, CURLOPT_HEADER, 0);
		curl_setopt ($curl, CURLOPT_USERPWD, $api_key . ":" . $api_secret);
		curl_setopt ($curl, CURLOPT_TIMEOUT, 15);
		curl_setopt ($curl, CURLOPT_VERBOSE, 0);
		curl_setopt ($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt ($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt ($curl, CURLOPT_FAILONERROR, 0);

		//Process
		if (($json = curl_exec ($curl)) !== false)
		{
			//Close connection
			curl_close ($curl);

			//Decode
			$social_data = json_decode ($json);

			//User Data
			if (is_object ($social_data) AND $social_data->response->result->status->code == 200)
			{
				$identity = $social_data->response->result->data->user->identity;
				$user_token = $social_data->response->result->data->user->user_token;

				//Identity
				$user_identity_id = $identity->id;
				$user_identity_provider = $identity->source->name;

				//Firstname
				$user_first_name = $identity->name->givenName;

				//Lastname
				$user_last_name = $identity->name->familyName;

				//Construct a full name from first and last names
				$user_constructed_name = trim ($user_first_name . ' ' . $user_last_name);

				//Fullname
				if (!empty ($identity->name->formatted))
				{
					$user_full_name = $identity->name->formatted;
				}
				elseif (!empty ($identity->name->displayName))
				{
					$user_full_name = $identity->name->displayName;
				}
				else
				{
					$user_full_name = $user_constructed_name;
				}

				//Email
				$user_email = '';
				if (property_exists ($identity, 'emails') AND is_array ($identity->emails))
				{
					foreach ($identity->emails AS $email)
					{
						$user_email = $email->value;
						$user_email_is_verified = ($email->is_verified == '1');
					}
				}

				//Thumbnail
				if (!empty ($identity->thumbnailUrl))
				{
					$user_thumbnail = trim ($identity->thumbnailUrl);
				}
				else
				{
					$user_thumbnail = '';
				}

				//User Website
				if (!empty ($identity->profileUrl))
				{
					$user_website = $identity->profileUrl;
				}
				elseif (!empty ($identity->urls [0]->value))
				{
					$user_website = $identity->urls [0]->value;
				}
				else
				{
					$user_website = '';
				}

				//Preferred Username
				if (!empty ($identity->preferredUsername))
				{
					$user_login = $identity->preferredUsername;
				}
				elseif (!empty ($identity->displayName))
				{
					$user_login = $identity->displayName;
				}
				else
				{
					$user_login = $user_full_name;
				}

				//Sanitize Login
				$user_login = sanitize_user ($user_login, true);

				// Get user by token
				$user_id = oa_social_login_get_user_by_token ($user_token);

				//Try to link to existing account
				if (!is_numeric ($user_id))
				{
					//Linked enabled?
					if (!isset ($settings ['plugin_link_verified_accounts']) OR $settings ['plugin_link_verified_accounts'] == '1')
					{
						//Only if email is verified
						if (!empty ($user_email) AND $user_email_is_verified === true)
						{
							//Read existing user
							if (($user_id_tmp = email_exists ($user_email)) !== false)
							{
								if (is_numeric ($user_id_tmp))
								{
									$user_id = $user_id_tmp;
									delete_metadata ('user', null, 'oa_social_login_user_token', $user_token, true);
									update_user_meta ($user_id, 'oa_social_login_user_token', $user_token);
									update_user_meta ($user_id, 'oa_social_login_identity_id', $user_identity_id);
									update_user_meta ($user_id, 'oa_social_login_identity_provider', $user_identity_provider);

									if (!empty ($user_thumbnail))
									{
										update_user_meta ($user_id, 'oa_social_login_user_thumbnail', $user_thumbnail);
									}
								}
							}
						}
					}
				}


				//New User
				if (!is_numeric ($user_id))
				{
					//Username is mandatory
					if (!isset ($user_login) OR strlen (trim ($user_login)) == 0)
					{
						$user_login = $user_identity_provider . 'User';
					}

					//Username must be unique
					if (username_exists ($user_login))
					{
						$i = 1;
						$user_login_tmp = $user_login;
						do
						{
							$user_login_tmp = $user_login . ($i++);
						}
						while (username_exists ($user_login_tmp));
						$user_login = $user_login_tmp;
					}

					//Email must be unique
					if (!isset ($user_email) OR !is_email ($user_email) OR email_exists ($user_email))
					{
						$user_email = oa_social_login_create_rand_email ();
					}

					//Build user data
					$user_data = array (
						'user_login' => $user_login,
						'display_name' => (!empty ($user_full_name) ? $user_full_name : $user_login),
						'user_email' => $user_email,
						'first_name' => $user_first_name,
						'last_name' => $user_last_name,
						'user_url' => $user_website,
						'user_pass' => wp_generate_password ()
					);

					// Create a new user
					$user_id = wp_insert_user ($user_data);
					if (is_numeric ($user_id))
					{
						delete_metadata ('user', null, 'oa_social_login_user_token', $user_token, true);
						update_user_meta ($user_id, 'oa_social_login_user_token', $user_token);
						update_user_meta ($user_id, 'oa_social_login_identity_id', $user_identity_id);
						update_user_meta ($user_id, 'oa_social_login_identity_provider', $user_identity_provider);

						if (!empty ($user_thumbnail))
						{
							update_user_meta ($user_id, 'oa_social_login_user_thumbnail', $user_thumbnail);
						}
					}
				}

				//Sucess
				if (is_object (get_userdata ($user_id)))
				{
					//Setup Cookie
					wp_set_auth_cookie ($user_id, true);

					//Where did the user come from?
					$oa_social_login_source = (!empty ($_REQUEST ['oa_social_login_source']) ? strtolower (trim ($_REQUEST ['oa_social_login_source'])) : '');

					//Use safe redirection?
					$redirect_to_safe = false;

					//Build the url to redirect the user to
					switch ($oa_social_login_source)
					{
						//*************** Registration ***************
						case 'registration':
						//Default redirection
							$redirect_to = admin_url ();

							//Redirection customized
							if (isset ($settings ['plugin_registration_form_redirect']))
							{
								switch (strtolower ($settings ['plugin_registration_form_redirect']))
								{
									//Homepage
									case 'homepage':
										$redirect_to = site_url ();
										break;

									//Custom
									case 'custom':
										if (isset ($settings ['plugin_registration_form_redirect_custom_url']) AND strlen (trim ($settings ['plugin_registration_form_redirect_custom_url'])) > 0)
										{
											$redirect_to = trim ($settings ['plugin_registration_form_redirect_custom_url']);
										}
										break;

									//Default/Dashboard
									default:
									case 'dashboard':
										$redirect_to = admin_url ();
										break;
								}
							}
							break;


						//*************** Login ***************
						case 'login':
						//Default redirection
							$redirect_to = site_url ();

							//Redirection in URL
							if (!empty ($_GET ['redirect_to']))
							{
								$redirect_to = $_GET ['redirect_to'];
								$redirect_to_safe = true;
							}
							else
							{
								//Redirection customized
								if (isset ($settings ['plugin_login_form_redirect']))
								{
									switch (strtolower ($settings ['plugin_login_form_redirect']))
									{
										//Dashboard
										case 'dashboard':
											$redirect_to = admin_url ();
											break;

										//Custom
										case 'custom':
											if (isset ($settings ['plugin_login_form_redirect_custom_url']) AND strlen (trim ($settings ['plugin_login_form_redirect_custom_url'])) > 0)
											{
												$redirect_to = trim ($settings ['plugin_login_form_redirect_custom_url']);
											}
											break;

										//Default/Homepage
										default:
										case 'homepage':
											$redirect_to = site_url ();
											break;
									}
								}
							}
							break;

						// *************** Other ***************
						default:
						//Get request URI - Should work on Apache + IIS
							$request_uri = ((!isset ($_SERVER ['REQUEST_URI'])) ? $_SERVER ['PHP_SELF'] : $_SERVER ['REQUEST_URI']);
							$request_port = ((!empty ($_SERVER ['SERVER_PORT']) AND $_SERVER ['SERVER_PORT'] <> '80') ? (":" . $_SERVER ['SERVER_PORT']) : '');
							$request_protocol = (is_ssl () ? 'https' : 'http') . "://";
							$redirect_to = $request_protocol . $_SERVER ['SERVER_NAME'] . $request_port . $request_uri;

							//Remove the oa_social_login_source argument
							if (strpos ($redirect_to, 'oa_social_login_source') !== false)
							{
								//Break up url
								list($url_part, $query_part) = array_pad (explode ('?', $redirect_to), 2, '');
								parse_str ($query_part, $query_vars);

								//Remove oa_social_login_source argument
								if (is_array ($query_vars) AND isset ($query_vars ['oa_social_login_source']))
								{
									unset ($query_vars ['oa_social_login_source']);
								}

								//Build new url
								$redirect_to = $url_part . ((is_array ($query_vars) AND count ($query_vars) > 0) ? ('?' . http_build_query ($query_vars)) : '');
							}

							//Anchor to #comments
							if ($oa_social_login_source == 'comments')
							{
								$redirect_to .= '#comments';
							}
							break;
					}

					//Check if url set
					if (!isset ($redirect_to_safe) OR strlen (trim ($redirect_to_safe)) == 0)
					{
						$redirect_to_safe = site_url ();
					}

					//Use safe redirection
					if ($redirect_to_safe === true)
					{
						wp_safe_redirect ($redirect_to);
					}
					else
					{
						wp_redirect ($redirect_to);
					}
					exit ();
				}
			}
		}
	}
}
