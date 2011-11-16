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
				if ( ! empty ($identity->profileUrl))
				{
					$user_website = $identity->profileUrl;
				}
				elseif ( ! empty ($identity->urls [0]->value))
				{
					$user_website = $identity->urls [0]->value;
				}
				else
				{
					$user_website = '';
				}

				//Preferred Username
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
				else
				{
					$user_login = 'user_' . rand (99999, 999999);
				}

				// Get user by token
				$user_id = oa_social_login_get_user_by_token ($user_token);

				//User found
				if ($user_id)
				{
					$user_data = get_userdata ($user_id);
					$user_login = $user_data->user_login;
				}
				else
				{
					// Create new user and associate token
					$user_login_raw = $user_login;
					$i = 1;
					while (username_exists ($user_login))
					{
						$user_login = $user_login_raw . '-'.($i++);
					}

					$userdata = array (
						'user_login' => $user_login,
						'user_email' => $user_email,
						'first_name' => $user_first_name,
						'last_name' => $user_last_name,
						'user_url' => $user_website,
						'user_pass' => wp_generate_password ()
					);

					// Create a new user
					$user_id = wp_insert_user ($userdata);
					if ( ! empty ($user_id))
					{
						delete_metadata('user', null, 'oa_social_login_user_token', $user_token, true);
						update_user_meta ($user_id, 'oa_social_login_user_token', $user_token);
						update_user_meta ($user_id, 'oa_social_login_identity_id', $user_identity_id);
						update_user_meta ($user_id, 'oa_social_login_identity_provider', $user_identity_provider);
					}
				}

				wp_set_auth_cookie ($user_id);
				wp_set_current_user($user_id);
			}
		}
	}
}

