=== LoginRequirePress ===
Contributors: maratbn
Tags: require login, password protect, security, limit access, control access, members, visitors, subscribers
Requires at least: 3.8.1
Tested up to: 4.2.2
Stable tag: 0.1.2
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Allows site administrators to specifically designate arbitrary posts with any public post type as viewable only after user login.

== Description ==

Overview:

  At the time of this writing, the latest version of WordPress, version 4.1,
  has 3 post visibility options, which are 'public', 'password protected', and
  'private'.

  The 'password protected' option allows the site administrator to
  individually lock certain posts, even from the logged in users, with an
  additional password.  However, there is currently no built-in way to just
  deny access only to the unauthenticated users.

  LoginRequirePress is a WordPress plugin that allows site administrators to
  specifically designate arbitrary posts with any public post type as viewable
  only after user login.

  Unauthenticated site visitors attempting to view any page that includes any
  such specifically designated post will then be automatically redirected to
  the site's default login page, and then back to the original page after they
  login, thereby limiting access only to logged-in users with subscriber roles
  and above.

  Plugin will still allow unauthenticated downloading of site's feeds, but
  will filter out all login-requiring posts from the feed listings.

  Plugin will protect the titles and contents of login-requiring posts in
  search result page listings when the user is not logged in.  The titles /
  contents will be replaced by text "[Post title / content protected by
  LoginRequirePress.  Login to see the title / content.]"

Technical summary:

  Plugin works by hooking-in special logic into the action 'send_headers' to
  redirect unauthenticated client browsers to the site's login page from any
  non-feed and non-search-results page upon detecting any login-requiring post,
  and by hooking-in another special logic into the filter 'posts_results' to
  filter out all login-requiring posts from all feed page listings, and to
  protect the titles and contents of login-requiring posts in search result
  page listings.

Official project URLs:

  https://github.com/maratbn/LoginRequirePress
  https://wordpress.org/plugins/loginrequirepress
  http://www.maratbn.com/projects/login-require-press

== Installation ==

1. Unzip contents of `loginrequirepress.zip` into the directory `/wp-content/plugins/loginrequirepress/`.
2. Activate the plugin through the 'Plugins' menu in WordPress.

== Frequently Asked Questions ==

= Where can I ask a question about LoginRequirePress? =

Ask your questions at: https://wordpress.org/support/plugin/loginrequirepress

= Where can I post an issue / bug / feature request? =

Post issues / bugs / feature requests at: https://github.com/maratbn/LoginRequirePress/issues

== Screenshots ==

1. LoginRequirePress configuration screen.

== Changelog ==

= 0.1.0 =
* Initial release.

= 0.1.1 =
* Various documentation improvement.

= 0.1.2 =
* Minor improvement to plugin WordPress description meta field.
* Fixed issue https://github.com/maratbn/LoginRequirePress/issues/2:  Added file 'REQUIREMENTS'.
* Fixed issue https://github.com/maratbn/LoginRequirePress/issues/3:  Protecting the titles and
  contents of login-requiring posts in search result page listings when the user is not logged in.
