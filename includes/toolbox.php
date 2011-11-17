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
function oa_social_login_create_rand_email()
{
	do
	{
		$email = md5(uniqid(wp_rand(10000,99000)))."@example.com";
	}	while(email_exists($email));

	return $email;
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

				//Fullname
				$user_full_name = '';
				if ( ! empty ($identity->name->formatted))
				{
					$user_full_name = $identity->name->formatted;
				}
				elseif ( ! empty ($identity->name->displayName))
				{
					$user_full_name = $identity->name->displayName;
				}
				else
				{
					$user_full_name = trim ($user_first_name.' '.$user_last_name);
				}

				//Email
				$user_email = '';
				if (property_exists ($identity, 'emails') AND is_array ($identity->emails))
				{
					foreach ($identity->emails AS $email)
					{
						$user_email = $email->value;
					}
				}

				//User Website
				$user_website = '';
				if ( ! empty ($identity->profileUrl))
				{
					$user_website = $identity->profileUrl;
				}
				elseif ( ! empty ($identity->urls [0]->value))
				{
					$user_website = $identity->urls [0]->value;
				}

				//Preferred Username
				$user_login = '';
				if ( ! empty ($identity->preferredUsername))
				{
					$user_login = $identity->preferredUsername;
				}
				elseif (! empty ($identity->displayName))
				{
					$user_login = $identity->displayName;
				}
				elseif (! empty ($identity->name->formatted))
				{
					$user_login = $identity->name->formatted;
				}

				// Get user by token
				$user_id = oa_social_login_get_user_by_token ($user_token);

				//New User
				if ( ! is_numeric ($user_id))
				{
					//Username is mandatory
					if ( ! isset ($user_login) OR strlen(trim($user_login)) == 0)
					{
						$user_login = $user_identity_provider.'User';
					}

					//Username must be unique
					if (username_exists ($user_login))
					{
						$i = 1;
						$user_login_tmp = $user_login;
						do
						{
							$user_login_tmp = $user_login.($i++);
						} 	while (username_exists ($user_login_tmp));
						$user_login = $user_login_tmp;
					}

					//Email must be unique
					if ( ! isset ($user_email) OR ! is_email($user_email) OR email_exists ($user_email))
					{
						$user_email = oa_social_login_create_rand_email();
					}

					$user_data = array (
						'user_login' => $user_login,
						'user_email' => $user_email,
						'first_name' => $user_first_name,
						'last_name' => $user_last_name,
						'user_url' => $user_website,
						'user_pass' => wp_generate_password ()
					);

					// Create a new user
					$user_id = wp_insert_user ($user_data);
					if (is_numeric($user_id))
					{
						delete_metadata('user', null, 'oa_social_login_user_token', $user_token, true);
						update_user_meta ($user_id, 'oa_social_login_user_token', $user_token);
						update_user_meta ($user_id, 'oa_social_login_identity_id', $user_identity_id);
						update_user_meta ($user_id, 'oa_social_login_identity_provider', $user_identity_provider);
					}
				}

				//Sucess
				if (is_object(get_userdata($user_id)))
				{
					//Setup Cookie
					wp_set_auth_cookie($user_id);

					//Redirect to administration area
					if (! empty ($_REQUEST['oa_social_login_source']) AND in_array ($_REQUEST['oa_social_login_source'], array ('login', 'registration')))
					{
						wp_safe_redirect(admin_url());
						exit();
					}
					//Set current user
					else
					{
						wp_set_current_user($user_id);
					}
				}
			}
		}
	}
}

