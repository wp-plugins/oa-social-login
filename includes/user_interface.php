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
		oa_social_login_render_login_form ('shortcode');
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
		oa_social_login_render_login_form ('comments');
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
		oa_social_login_render_login_form ('registration');
	}
}
add_action ('register_form', 'oa_social_login_render_login_form_registration');



/**
 * Display the provider grid for login
 */
function oa_social_login_render_login_form_login ()
{
	if (!is_user_logged_in ())
	{
		oa_social_login_render_login_form ('login');
	}
}
add_action ('login_form', 'oa_social_login_render_login_form_login');


/**
 * Display a custom grid for login
 */
function oa_social_login_render_custom_form_login ()
{
	if (!is_user_logged_in ())
	{
		oa_social_login_render_login_form ('custom');
	}
}
add_action ('oa_social_login', 'oa_social_login_render_custom_form_login');


/**
 * Display the provider grid
 */
function oa_social_login_render_login_form ($source)
{
	//Import providers
	GLOBAL $oa_social_login_providers;

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

		//Grid Size
		$grid_size_x = 99;
		$grid_size_y = 99;

		//Widget
		if ($source == 'widget')
		{
			$css_theme_uri = 'http://oneallcdn.com/css/api/socialize/themes/wp_widget.css';
			$show_title = false;
		}
		//Inline
		else
		{
			//For all page, except the Widget
			$css_theme_uri = 'http://oneallcdn.com/css/api/socialize/themes/wp_inline.css';
			$show_title = (empty($plugin_caption ) ? false : true);

			//Comments
			if ($source == 'comments')
			{
				$source .= '#comments';
			}
			elseif (in_array($source, array ('login', 'registration')))
			{
				$grid_size_x = 5;
				$grid_size_y = 1;
			}
		}

		//Providers selected?
		if (count ($providers) > 0)
		{
			//Random integer
			$rand = mt_rand(99999, 9999999);
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
					<div class="oneall_social_login_providers" id="oneall_social_login_providers_<?php echo $rand; ?>" style="margin-top:5px;margin-bottom:5px"></div>
					<script type="text/javascript">
					 oneall.api.plugins.social_login.build("oneall_social_login_providers_<?php echo $rand; ?>", {
					  'providers':  ['<?php echo implode ("','", $providers); ?>'],
					  'callback_uri': (window.location.href + ((window.location.href.split('?')[1] ? '&':'?') + 'oa_social_login_source=<?php echo $source; ?>')),
					  'css_theme_uri': '<?php echo $css_theme_uri; ?>',
					  'grid_size_x': '<?php echo $grid_size_x; ?>',
					 	'grid_size_y': '<?php echo $grid_size_y; ?>'
					 });
					</script>
				</div>
			<?php
		}
	}
}
