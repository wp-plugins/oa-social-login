<?php

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
 * Add Site CSS
 **/
function oa_social_login_add_site_css ()
{
	if (!wp_style_is ('oa_social_login_site_css', 'registered'))
	{
		wp_register_style ('oa_social_login_site_css', OA_SOCIAL_LOGIN_PLUGIN_URL . "/assets/css/site.css");
	}

	if (did_action ('wp_print_styles'))
	{
		wp_print_styles ('oa_social_login_site_css');
	}
	else
	{
		wp_enqueue_style ('oa_social_login_site_css');
	}
}


/**
 * Check if the current connection is being made over https
 */
function oa_social_login_https_on()
{
	if ( ! empty ($_SERVER ['SERVER_PORT']))
	{
		if (trim($_SERVER ['SERVER_PORT']) == '443')
		{
			return true;
		}
	}

	if ( ! empty ($_SERVER ['HTTP_X_FORWARDED_PROTO']))
	{
		if (strtolower(trim($_SERVER ['HTTP_X_FORWARDED_PROTO'])) == 'https')
		{
			return true;
		}
	}

	if ( ! empty ($_SERVER ['HTTPS']))
	{
		if (strtolower(trim($_SERVER ['HTTPS'])) == 'on' OR trim($_SERVER ['HTTPS']) == '1')
		{
			return true;
		}
	}

	return false;
}


/**
 * Escape an attribute
 */
function oa_social_login_esc_attr ($string)
{
	//Available since Wordpress 2.8
	if (function_exists('esc_attr'))
	{
		return esc_attr ($string);
	}
	//Deprecated as of Wordpress 2.8
	elseif (function_exists('attribute_escape'))
	{
		return attribute_escape($string);
	}
	return htmlspecialchars ($string);
}


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