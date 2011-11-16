jQuery(document).ready(function($) {


	$('#oa_social_login_test_api_settings').click(function(){
		var subdomain = jQuery('#oa_social_login_settings_api_subdomain').val();
		var key = jQuery('#oa_social_login_settings_api_key').val();
		var secret = jQuery('#oa_social_login_settings_api_secret').val();
				
		var data = {
			_ajax_nonce: oa_social_login_ajax_nonce.value,
			action: 'check_api_settings',
			api_subdomain: subdomain,
			api_key: key,
			api_secret: secret
		};
		
		jQuery.post(ajaxurl,data, function(response) {	
			var message;		
			var success;

			
			if (response == 'error_subdomain_wrong'){
				success = false;
				message = 'The subdomain does not exist. Have you filled it out correctly?'
			}
			else if (response == 'error_authentication_credentials_wrong'){
				success = false;
				message = 'The API credentials are wrong';
			}
			else {
				success = true;
				message = 'The API Settings are correct!';
			}
		
			jQuery('#oa_social_login_api_test_result').html(message);
		
			if (success){
				jQuery('#oa_social_login_api_test_result').removeClass('error_message');
				jQuery('#oa_social_login_api_test_result').addClass('success_message');
			} else {
				jQuery('#oa_social_login_api_test_result').removeClass('success_message');
				jQuery('#oa_social_login_api_test_result').addClass('error_message');
			}		
			
		});
		return false;
	});
});