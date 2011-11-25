=== Social Login ===
Contributors: ClaudeSchlesser
Tags: social login, social connect, facebook, linkedin, google, yahoo, twitter, openid, wordpress.com, vkontakte, hyves, widget, plugin, social network login
Requires at least: 3.0
Tested up to: 3.2.1
Stable tag: 1.4

Allow your visitors to comment and login with social networks like Twitter, Facebook, LinkedIn, Hyves, OpenID, Вконтакте, Google, Yahoo

== Description ==

The Social Login Plugin is a professional though free plugin that allows your visitors to comment, 
login and register with social networks like Twitter, Facebook, LinkedIn, Hyves, Вконтакте, Google or 
Yahoo. <br />

<strong>Choose where to add the social login feature:</strong>
<ul>
 <li>On the comment formular</li>
 <li>On the login page</li>
 <li>On the registration page</li>
</ul>

<strong>Optionally add the Social Login widget:</strong>
<ul>
 	<li>A login widget that you can easily attach to your sidebar is provided</li>
 </ul>

<strong>Select the Social Networks/Providers:</strong>
<ul>
 <li>Facebook</li>
 <li>Twitter</li>
 <li>Google</li>
 <li>LinkedIn</li>
 <li>Yahoo</li>
 <li>OpenID</li>
 <li>Wordpress.com</li>
 <li>Hyves</li>
 <li>VKontakte (Вконтакте)</li>
 </ul>

<strong>Increase your user engagement in a few simple steps with this plugin.</strong><br />

The Social Login Plugin is maintained by <a href="http://www.oneall.com">OneAll</a>, a technology company offering a set of web-delivered
tools and services for establishing and optimizing a site's connection with social providers such as Facebook, Twitter, Google, Yahoo!,
LinkedIn amongst others.


== Installation ==

1. Upload the plugin folder to the "/wp-content/plugins/" directory of your WordPress site,
2. Activate the plugin through the 'Plugins' menu in WordPress,
3. Visit the "Settings\Social Login" administration page to setup the plugin. 

== Frequently Asked Questions ==

= Do I have to add template tags to my theme? =

You should not have to change your templates. 
The Social Login seamlessly integrates into your blog by using predefined hooks.

= I have a custom template and the plugin is not displayed correctly =

The plugin uses predefined hooks. If your theme does not support these hooks,
you can add the Social Login form manually to your theme by inserting the following code 
in your template (at the location where it should be displayed, i.e. above the comments).

`<?php do_action('oa_social_login'); ?>`

= Do I have to change my Rewrite Settings? =

The plugins does not rely on mod_rewrite and does not need any additional rules.
It should work out of the box.

= Where can I report bugs & get support? =

Our team answers your request at:
http://www.oneall.com/company/contact-us/

The plugin documentation is available at:
http://docs.oneall.com/plugins/guide/social-login-wordpress/

== Screenshots ==

1. **Comment** - Comment formular (Social Network Buttons are includes)
2. **Login** - Login formular (Social Network Buttons are includes)
3. **Plugin Settings** - Plugin Settings in the Wordpress Administration Area
4. **Widget Settings** - Widget Settings in the Wordpress Administration Area

== Changelog ==

= 1.0 =
* Initial release

= 1.0.1 =
* Hook oa_social_login fixed
* Plugin description changed

= 1.0.2 = 
* Version numbers fixed

= 1.3.2 =
* Stable Version

= 1.3.4 =
* Multisite issues with Widget fixed

= 1.3.5 =
* Administration area redirection fixed
* Automatic email creation added
* Email verification added

= 1.4 =
* Social Network Avatars can be displayed in comments
* Social Login can be disabled below the login form
* Social Login can be disabled below the registration form
* Select redirection target after login
* Select redirection target after registration
* Enable account linking