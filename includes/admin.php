<?php

/**
 * Add Settings Tab
 **/
function oa_social_login_admin_menu ()
{
	$page = add_options_page ('Social Login', 'Social Login', 'manage_options', 'oa_social_login', 'oa_display_social_login_settings');
	add_action ('admin_print_styles-' . $page, 'oa_social_login_admin_css');
	add_action ('admin_enqueue_scripts', 'oa_social_login_admin_js');
	add_action ('admin_init', 'oa_register_social_login_settings');
	add_action ('admin_notices', 'oa_social_login_admin_message');
}
add_action ('admin_menu', 'oa_social_login_admin_menu');


/**
 * Add an activation message to be displayed once
 */
function oa_social_login_admin_message ()
{
	if (get_option('oa_social_login_activation_message') !== '1')
	{
		echo '<div class="updated"><p><strong>Thank you for using the Social Login Plugin!</strong> Please go to the <strong>Settings\Social Login</strong> page to setup the plugin.</p></div>';
		update_option ('oa_social_login_activation_message', '1');
	}
}

/**
 * Check API Settings trough an Ajax Call
 */
function oa_social_login_check_api_settings()
{
	check_ajax_referer('oa_social_login_ajax_nonce');

	$api_domain = strtolower($_POST['api_subdomain']).'.api.oneall.com';
	$api_key = $_POST['api_key'];
	$api_secret = $_POST['api_secret'];

	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, 'https://'.$api_domain.'/tools/ping.json');
	curl_setopt($curl, CURLOPT_HEADER, 0);
	curl_setopt($curl, CURLOPT_USERPWD, $api_key . ":" . $api_secret);
	curl_setopt($curl, CURLOPT_TIMEOUT, 5);
	curl_setopt($curl, CURLOPT_VERBOSE, 0);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($curl, CURLOPT_FAILONERROR, 0);
	if ( ($json = curl_exec($curl)) === false)
	{
				echo 'Curl error: ' . curl_error($curl);
	}
	//Success
	else
	{
		$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

		//Authentication Error
		if ($http_code == 401)
		{
			echo 'error_authentication_credentials_wrong';
			delete_option ('oa_social_login_api_settings_verified');
		}
		elseif ($http_code == 404)
		{
			echo 'error_subdomain_wrong';
			delete_option ('oa_social_login_api_settings_verified');
		}
		elseif ($http_code == 200)
		{
			echo 'success';
			update_option ('oa_social_login_api_settings_verified', '1');
		}
	}

	die();
}
add_action('wp_ajax_check_api_settings', 'oa_social_login_check_api_settings');


/**
 * Add Settings JS
 **/
function oa_social_login_admin_js ($hook)
{
	if ($hook == 'settings_page_oa_social_login')
	{
		if (!wp_script_is ('oa_social_login_admin_js', 'registered'))
		{
			wp_register_script('oa_social_login_admin_js', OA_SOCIAL_LOGIN_PLUGIN_URL . "/assets/js/admin.js");
		}

		wp_enqueue_script ('oa_social_login_admin_js');
		wp_enqueue_script ('jquery');

		$oa_social_login_ajax_nonce = wp_create_nonce  ('oa_social_login_ajax_nonce');
		wp_localize_script('oa_social_login_admin_js', 'oa_social_login_ajax_nonce', array ('value' => $oa_social_login_ajax_nonce));
	}
}

/**
 * Add Settings CSS
 **/
function oa_social_login_admin_css ($hook)
{
	if (!wp_style_is ('oa_social_login_admin_css', 'registered'))
	{
		wp_register_style ('oa_social_login_admin_css', OA_SOCIAL_LOGIN_PLUGIN_URL . "/assets/css/admin.css");
	}

	if (did_action ('wp_print_styles'))
	{
		wp_print_styles ('oa_social_login_admin_css');
	}
	else
	{
		wp_enqueue_style ('oa_social_login_admin_css');
	}
}


/**
 * Register plugin settings and their sanitization callback
 */
function oa_register_social_login_settings ()
{
	register_setting ('oa_social_login_settings_group', 'oa_social_login_settings', 'oa_social_login_settings_validate');
}


/**
 *  Plugin settings sanitization callback
 */
function oa_social_login_settings_validate ($settings)
{
	//Import providers
	GLOBAL $oa_social_login_providers;

	//Sanitzed Settings
	$sanitzed_settings = array ();

	//Base Settings
	foreach (array (
		'api_subdomain',
		'api_key',
		'api_secret',
		'plugin_caption'
	) AS $key)
	{
		if (isset ($settings [$key]))
		{
			$sanitzed_settings [$key] = trim ($settings [$key]);
		}
	}

	//Subdomain is always lowercase
	if (isset ($sanitzed_settings['api_subdomain']))
	{
		$sanitzed_settings['api_subdomain'] = strtolower ($sanitzed_settings['api_subdomain']);
	}


	//Enabled providers
	if (isset ($settings ['providers']) AND is_array ($settings ['providers']))
	{
		foreach ($oa_social_login_providers AS $key => $name)
		{
			if (isset ($settings ['providers'] [$key]) AND $settings ['providers'] [$key] == '1')
			{
				$sanitzed_settings ['providers'] [$key] = 1;
			}
		}
	}

	//Done
	return $sanitzed_settings;
}


/**
 * Display Settings Page
 **/
function oa_display_social_login_settings ()
{
	//Import providers
	GLOBAL $oa_social_login_providers;
	?>
	<div class="wrap">
		<h2><?php _e ('Social Login Settings', 'oa_social_login'); ?></h2>
		<?php
			if (get_option('oa_social_login_api_settings_verified') !== '1')
			{
				?>
					<div class="oa_container oa_container_welcome">
						<h3>
							Make your blog social!
						</h3>
						<div class="oa_container_body">
							<p>
								Allow your visitors to comment, login and register with social networks like Twitter, Facebook, LinkedIn, Hyves, Вконтакте, Google or Yahoo.
								<strong>Draw a larger audience and increase user engagement in a  few simple steps.</strong>
							</p>
							<p>
								To be able to use this plugin you first of all need to create a free account at  <a href="https://app.oneall.com/signup/" target="_blank">http://www.oneall.com</a>
								and create a new Site. After having created your account and setup your Site, please enter the Site Settings in the form below.
							</p>
							<h3>The basic account creation is free and the setup is easy!</h3>
							<p>
								<a class="button-secondary" href="https://app.oneall.com/signup/" target="_blank"><strong>Setup my account now</strong></a>
							</p>
						</div>
					</div>
				<?php
			}
			else
			{
				?>
					<div class="oa_container oa_container_welcome">
						<h3>
							Your API Account is setup correctly
						</h3>
						<div class="oa_container_body">
							<p>
								Login to your account to manage your providers and access your <a href="http://www.oneall.com/services/social-insights/"  target="_blank">Social Insights</a>.
								Determine which social networks are popular amongst your users and tailor your registration experience increase user engagement
							</p>
							<p>
								<a class="button-secondary" href="https://app.oneall.com/signin/" target="_blank"><strong>Signin to my account</strong></a>
							</p>
						</div>
					</div>
				<?php
			}
		?>
		<form method="post" action="options.php">
			<?php
				settings_fields ('oa_social_login_settings_group');
				$settings = get_option ('oa_social_login_settings');
				?>
			  <table class="form-table oa_form_table">
			  	<tr>
			  		<th class="head" colspan="2">
			  			<?php _e('API Settings', 'oa_social_login'); ?>
			  		</th>
			  	</tr>
					<tr>
		    	  <th scope="row">
		      		<label for="oneall_api_subdomain"><?php _e('API Subdomain', 'oa_social_login'); ?>:</label>
		      	</th>
		      	<td>
		      		<input type="text" id="oa_social_login_settings_api_subdomain" name="oa_social_login_settings[api_subdomain]" size="60" value="<?php echo (isset ($settings ['api_subdomain']) ? htmlspecialchars ($settings ['api_subdomain']) : ''); ?>" />
		      	</td>
		      </tr>
					<tr>
						<th scope="row">
							<label for="oneall_api_public_key"><?php _e('API Public Key', 'oa_social_login'); ?>:</label>
		      	</th>
						<td>
		      		<input type="text" id="oa_social_login_settings_api_key" name="oa_social_login_settings[api_key]" size="60" value="<?php echo (isset ($settings ['api_key']) ? htmlspecialchars ($settings ['api_key']) : ''); ?>" />
		      	</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="oneall_api_private_key"><?php _e('API Private Key', 'oa_social_login'); ?>:</label>
						</th>
						<td>
							<input type="text" id="oa_social_login_settings_api_secret"  name="oa_social_login_settings[api_secret]" size="60" value="<?php echo (isset ($settings ['api_secret']) ? htmlspecialchars ($settings ['api_secret']) : ''); ?>" />
						</td>
					</tr>
					<tr class="foot">
						<td>
							<a class="button-secondary" id="oa_social_login_test_api_settings" href="#"><?php _e('Verify API Settings', 'oa_social_login'); ?></a>
						</td>
						<td>
							<div id="oa_social_login_api_test_result"></div>
						</td>
					</tr>
				</table>

				<table class="form-table oa_form_table">
			  	<tr>
			  		<th class="head" colspan="2">
			  			Enable the providers of your choice
			  		</th>
			  	</tr>
			  	<?php
					  $i = 0;
					  foreach ($oa_social_login_providers AS $key => $name)
					  {
				 		 ?>
				  			<tr class="<?php echo ((($i++) % 2) == 0) ? 'row_even' : 'row_odd)' ?> row_provider">
					    	  <th scope="row">
					    	  	<label for="oneall_social_login_provider_<?php echo $key; ?>"><span class="oa_provider oa_provider_<?php echo $key; ?>" title="<?php echo htmlspecialchars ($name); ?>"><?php echo htmlspecialchars ($name); ?></span></label>
					    	  	<input type="checkbox" id="oneall_social_login_provider_<?php echo $key; ?>" name="oa_social_login_settings[providers][<?php echo $key; ?>]" value="1"  <?php checked ('1', $settings ['providers'] [$key]); ?> />
					      		<label for="oneall_social_login_provider_<?php echo $key; ?>"><?php echo htmlspecialchars ($name); ?></label>
					      	</th>
					      </tr>
			  		<?php
					 }
					?>
				</table>

				<table class="form-table oa_form_table">
			  	<tr>
			  		<th class="head" colspan="2">
			  			<?php _e('Settings', 'oa_social_login'); ?>
			  		</th>
			  	</tr>
					<tr>
						<th scope="row">
							<label for="oneall_api_private_key"><?php _e('Social Login Caption', 'oa_social_login'); ?>:</label>
						</th>
						<td>
							<input type="text" id="oa_social_login_settings_plugin_caption"  name="oa_social_login_settings[plugin_caption]" size="60" value="<?php echo (isset ($settings ['plugin_caption']) ? htmlspecialchars ($settings ['plugin_caption']) : _e('Connect with:')); ?>" />
						</td>
					</tr>
				</table>
				<p class="submit">
					<input type="submit" class="button-primary" value="<?php _e ('Save Changes') ?>" />
				</p>
			</form>
		</div>
	<?php
}
