<?php

/**
 * Include the Social Library
 **/
function oa_social_login_add_javascripts ()
{
	if ( ! wp_script_is ('oa_social_library', 'registered'))
	{
		//Read settings
		$settings = get_option ('oa_social_login_settings');

		if (!empty ($settings ['api_subdomain']))
		{
			wp_register_script ("oa_social_library",  (is_ssl() ? 'https' : 'http').'://'.$settings ['api_subdomain'].'.api.oneall.com/socialize/library.js');
		}
	}
	wp_print_scripts ("oa_social_library");
}
add_action ('login_head', 'oa_social_login_add_javascripts');
add_action ('wp_head', 'oa_social_login_add_javascripts');



/**
 * Setup Shortcode handler
 **/
function oa_social_login_shortcode_handler ($args)
{
	if (!is_user_logged_in ())
	{
		oa_social_login_render_login_form ();
	}
}
add_shortcode ('oa_social_login', 'oa_social_login_shortcode_handler');



/**
 * Display the provider grid for comments
 */
function oa_social_login_render_login_form_comments ()
{
	if (comments_open () && !is_user_logged_in ())
	{
		oa_social_login_render_login_form ();
	}
}
add_action ('comment_form_top', 'oa_social_login_render_login_form_comments');



/**
 * Display the provider grid for registration
 */
function oa_social_login_render_login_form_registration ()
{
	if (get_option('users_can_register') === '1')
	{
		oa_social_login_render_login_form ();
	}
}
add_action ('register_form', 'oa_social_login_render_login_form_registration');



/**
 * Display the provider grid for login
 */
function oa_social_login_render_login_form_login ()
{
	oa_social_login_render_login_form ();
}
add_action ('login_form', 'oa_social_login_render_login_form_login');


/**
 * Display the provider grid
 */
function oa_social_login_render_login_form ($args = null)
{
	//Import providers
	GLOBAL $oa_social_login_providers;

	//Arguments
	$target = 'inline';
	if (is_array ($args))
	{
		if (isset ($args['target']))
		{
			if ($args['target'] == 'widget')
			{
				$target = 'widget';
			}
		}
	}

	//Read settings
	$settings = get_option ('oa_social_login_settings');

	//API Subdomain
	$api_subdomain = (!empty ($settings ['api_subdomain']) ? $settings ['api_subdomain'] : '');

	//API Subdomain Required
	if ( ! empty ($api_subdomain))
	{
		//Caption
		$plugin_caption = (!empty ($settings ['plugin_caption']) ? $settings ['plugin_caption'] : '');

		//Build providers
		$providers = array ();
		if (is_array ($settings ['providers']))
		{
			foreach ($settings ['providers'] AS $settings_provider_key => $settings_provider_name)
			{
				if (isset ($oa_social_login_providers [$settings_provider_key]))
				{
					$providers [] = $settings_provider_key;
				}
			}
		}

		//Widget
		if ($target == 'widget')
		{
			$css_theme_uri = 'http://oneallcdn.com/css/api/socialize/themes/wp_widget.css';
			$show_title = false;
		}
		//Inline
		else
		{
			$css_theme_uri = 'http://oneallcdn.com/css/api/socialize/themes/wp_inline.css';
			$show_title = (empty($plugin_caption ) ? false : true);
		}

		//Build Redirection URL
		if (in_the_loop())
		{
			$redirect_url = "'".get_permalink()."'";
		}
		else
		{
			$redirect_url = "window.location.href";
		}

		if (count ($providers) > 0)
		{
			?>
				<div class="oneall_social_login">
					<?php
						if ($show_title)
						{
							?>
								<div style="margin-bottom: 3px;"><label><?php _e ($plugin_caption, 'oa_social_login'); ?></label></div>
							<?php
						}
					?>
					<div class="oneall_social_login_providers"></div>
					<script type="text/javascript">
					 oneall.api.plugins.social_login.build("oneall_social_login_providers", {
					  'providers' :  ['<?php echo implode ("','", $providers); ?>'],
					  'callback_uri': <?php echo $redirect_url; ?>,
					  'css_theme_uri' : '<?php echo $css_theme_uri; ?>'
					 });
					</script>
				</div>
			<?php
		}
	}
}
