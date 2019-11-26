=== Login Require Press ===
Contributors: maratbn
Tags: require login, password protect, security, limit access, control access, members, visitors, subscribers, require-login, password-protect, login-protect, limit-access
Requires at least: 3.8.1
Tested up to: 4.9.8
Stable tag: 1.3.0
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Easy way to require user login to view specific pages / posts.

== Description ==

Overview:

  At the time of this writing, the latest version of WordPress, version 5.3, has 3 post visibility options, which are 'public', 'password protected', and 'private'.

  The 'password protected' option allows the site administrator to individually lock certain posts, even from the logged in users, with an additional password / passcode.  However, there is currently no built-in way to just deny access only to the unauthenticated users.

  Login Require Press is a WordPress plugin that allows site administrators to specifically designate arbitrary posts with any public post type as viewable only after user login.  Post authors can also enable or disable login protection for their own posts.

  It is an easy way to require login to view specific pages / posts.

  Unauthenticated site visitors attempting to view any page that includes any such specifically designated post will then be automatically redirected to the site's default login page, and then back to the original page after they login, thereby limiting access only to logged-in users with subscriber roles and above.

  Plugin will still allow unauthenticated downloading of site's feeds, but will filter out all login-requiring posts from the feed listings.

  Plugin will protect the titles, contents, and excerpts of login-requiring posts in search result page listings when the user is not logged in.  The titles / contents / excerpts will be replaced by text "[Post title / content / excerpts protected by Login Require Press.  Login to see the title / content / excerpt.]"

Technical summary:

  Plugin works by hooking-in special logic into the action 'send_headers' to redirect unauthenticated client browsers to the site's login page from any non-feed and non-search-results page upon detecting any login-requiring post, and by hooking-in another special logic into the filter 'posts_results' to filter out all login-requiring posts from all feed page listings, and to protect the titles, contents, and excerpts of login-requiring posts in search result page listings.

  Login-requiring posts are marked with a custom field 'login_require_press' set to 'yes'.

Official project URLs:

  https://github.com/maratbn/LoginRequirePress
  https://wordpress.org/plugins/loginrequirepress
  http://www.maratbn.com/projects/login-require-press

== Installation ==

1. Unzip contents of `loginrequirepress.zip` into the directory `/wp-content/plugins/loginrequirepress/`.
2. Activate the plugin through the 'Plugins' menu in WordPress.

== Frequently Asked Questions ==

= What do I do if I get this error "Plugin could not be activated because it triggered a fatal error." when trying to activate? =

Upgrade to PHP >= 5.4  See
https://wordpress.org/support/topic/crashes-on-activate

Make sure to check your PHP version with
https://wordpress.org/plugins/display-php-version/

= Where can I ask a question about Login Require Press? =

Ask your questions at: https://wordpress.org/support/plugin/loginrequirepress

= Where can I post an issue / bug / feature request? =

Post issues / bugs / feature requests at: https://github.com/maratbn/LoginRequirePress/issues

= Plugin is missing feature X that I really want, what do I do? =

Post a bug / feature request, or implement the feature at your leisure, and submit a pull request.

== Screenshots ==

1. Login Require Press configuration screen with the table used to specify which posts are to be login-protected.

2. Login Require Press configuration screen with the lists of private, non-private login-protected, and passcode-protected posts.

3. Login Require Press edit post meta box to enable or disable login protection.

== Changelog ==

= 1.4.0 =
* Fixed bug that prevented authors without administrative privileges from enabling / disabling login protection on their own posts.
* Added logic to indicate that the previously-implemented Login Require Press post meta box is not compatible with the new WordPress block editor.

= 1.3.0 =
* Tested up to WordPress 4.9.8
* Added notice that changing settings in the meta box will not persist until the post is updated.
* Improved post filtering logic.
* Now also masking post excerpts of login-protected posts from search results.

= 1.2.0 =
* Tested up to WordPress 4.6
* Changed the plugin name from 'LoginRequirePress' to 'Login Require Press'.
* Indicating posts without names by token [no name #] where # is the post ID number.
* Shortened the plugin description appearing on the admin dashboard plugins list to just one sentence.
* Added support for WordPress instances nested inside subdirectories.

= 1.1.0 =
* Tested up to WordPress 4.5.2
* Improved the listing-out of posts in the post categorizations sections on the plugin's Settings page.
* Added 'Refresh' buttons to the Settings page.
* Added LoginRequirePress meta box to the post edit screen.

= 1.0.0 =
* Version incremented to 1.0.0 to signify public release.
* Tested up to WordPress 4.5
* Login Required status indicators now in red.
* Added new 'Default Visibility' column, that indicates posts' present visibility according to the default WordPress logic without this plugin.
* Explicitly listing-out the Private, Login-Protected, and Passcode/Password-Protected posts.
* Added plugin activation check for PHP version >= 5.4
* Revised screenshot 1 to display the latest UI, and added screenshot 2.

= 0.1.2 =
* Minor improvement to plugin WordPress description meta field.
* Fixed issue https://github.com/maratbn/LoginRequirePress/issues/2:  Added file 'REQUIREMENTS'.
* Fixed issue https://github.com/maratbn/LoginRequirePress/issues/3:  Protecting the titles and contents of login-requiring posts in search result page listings when the user is not logged in.

= 0.1.1 =
* Various documentation improvement.

= 0.1.0 =
* Initial release.
