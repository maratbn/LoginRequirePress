=== LoginRequirePress ===
Contributors: maratbn
Tags: require login, password protect, security, limit access, control access, members, visitors
Requires at least: 3.8.1
Tested up to: 4.1
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

Technical summary:

  Plugin works by hooking-in special logic into the action 'send_headers' to
  redirect unauthenticated client browsers to the site's login page from any
  non-feed page upon detecting any login-requiring post, and by hooking-in
  another special logic into the filter 'posts_results' to filter out all
  login-requiring posts from all feed page listings.

== Installation ==

1. Save `loginrequirepress.zip` in the `/wp-content/plugins/` directory.
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
