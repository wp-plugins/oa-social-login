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
	
			if (response == 'error_not_all_fields_filled_out'){
				success = false;
				message = objectL10n.oa_admin_js_1;
			}
			else if (response == 'error_subdomain_wrong'){
				success = false;
				message = objectL10n.oa_admin_js_2;
			}
			else if (response == 'error_subdomain_wrong_syntax'){
				success = false;
				message = objectL10n.oa_admin_js_3;	
			}
			else if (response == 'error_communication'){
				success = false;
				message = objectL10n.oa_admin_js_4;					
			}
			else if (response == 'error_authentication_credentials_wrong'){
				success = false;
				message = objectL10n.oa_admin_js_5;		
			}
			else {
				success = true;
				message = objectL10n.oa_admin_js_6;		
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