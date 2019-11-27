<?php
/*
  Plugin Name: Login Require Press
  Plugin URI: https://wordpress.org/plugins/loginrequirepress
  Plugin URI: https://github.com/maratbn/LoginRequirePress
  Plugin URI: http://www.maratbn.com/projects/login-require-press
  Description: Allows site administrators to specifically designate arbitrary posts with any public post type as viewable only after user login.
  Author: Marat Nepomnyashy
  Author URI: http://www.maratbn.com
  License: GPL3
  Version: 1.4.0
  Text Domain: domain-plugin-LoginRequirePress
*/

/*
  Login Require Press -- WordPress plugin that allows site administrators to
                         specifically designate arbitrary posts with any
                         public post type as viewable only after user login.

                         It is an easy way to require user login to view
                         specific pages / posts.

                         Unauthenticated site visitors attempting to view any
                         page that includes any such specifically designated
                         post will then be automatically redirected to the
                         site's default login page, and then back to the
                         original page after they login, thereby limiting
                         access only to logged-in users with subscriber roles
                         and above.

                         Plugin will still allow unauthenticated downloading
                         of site's feeds, but will filter out any
                         login-requiring posts from the feed listings.

                         Plugin will protect the titles and contents of login-
                         requiring posts in search result page listings when
                         the user is not logged in.  The titles / contents
                         will be replaced by text "[Post title / content
                         protected by Login Require Press.  Login to see the
                         title / content.]"

  https://wordpress.org/plugins/loginrequirepress
  https://github.com/maratbn/LoginRequirePress
  http://www.maratbn.com/projects/login-require-press

  Copyright (C) 2015-2019  Marat Nepomnyashy  http://maratbn.com  maratbn@gmail

  Version:        1.4.0

  Module:         LoginRequirePress.php

  Description:    Main PHP file for the WordPress plugin 'Login Require Press'.

  This file is part of Login Require Press.

  Licensed under the GNU General Public License Version 3.

  Login Require Press is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  Login Require Press is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with Login Require Press.  If not, see <http://www.gnu.org/licenses/>.
*/

    namespace plugin_LoginRequirePress;

    const DOMAIN_PLUGIN                 = 'domain-plugin-LoginRequirePress';
    const PHP_VERSION_MIN_SUPPORTED     = '5.4';

    const LOCK_                         = 'lock_';
    const LOGIN_REQUIRE_PRESS           = 'login_require_press';
    const LOGIN_REQUIRE_PRESS__LOCK     = 'login_require_press__lock';
    const LOGIN_REQUIRE_PRESS__META     = 'login_require_press__meta';
    const ON                            = 'on';
    const PRESENT                       = 'present';
    const YES                           = 'yes';


    \add_action('send_headers', '\\plugin_LoginRequirePress\\action_send_headers');

    \add_filter('posts_results', '\\plugin_LoginRequirePress\\filter_posts_results');


    if (\is_admin()) {
        \register_activation_hook(__FILE__, '\\plugin_LoginRequirePress\\plugin_activation_hook');

        \add_action('add_meta_boxes', '\\plugin_LoginRequirePress\\action_add_meta_boxes');
        \add_action('admin_menu', '\\plugin_LoginRequirePress\\action_admin_menu');
        \add_action('admin_post_plugin_LoginRequirePress_settings',
                    '\\plugin_LoginRequirePress\\action_admin_post_plugin_LoginRequirePress_settings');
        \add_action('save_post', '\\plugin_LoginRequirePress\\action_save_post');

        \add_filter('plugin_action_links_' . \plugin_basename(__FILE__),
                    '\\plugin_LoginRequirePress\\filter_plugin_action_links');
    }


    function action_add_meta_boxes() {
        add_meta_box('plugin_LoginRequirePress_require_login',
                     __('Login Require Press', DOMAIN_PLUGIN),
                     '\\plugin_LoginRequirePress\\callbackMetaBox',
                     null,
                     'advanced',
                     'default',
                     ['__block_editor_compatible_meta_box' => false]);
    }

    function action_admin_menu() {
        \add_options_page(\__('Login Require Press Settings', DOMAIN_PLUGIN),
                          \__('Login Require Press', DOMAIN_PLUGIN),
                          'manage_options',
                          'plugin_LoginRequirePress_settings',
                          '\\plugin_LoginRequirePress\\render_settings');
    }

    function action_admin_post_plugin_LoginRequirePress_settings() {
        //  Based on: http://jaskokoyn.com/2013/03/26/wordpress-admin-forms/
        if (!\current_user_can('manage_options')) {
            \wp_die(\__('Insufficient user permissions to modify options.',
                        DOMAIN_PLUGIN));
        }

        // Check that nonce field
        \check_admin_referer('plugin_LoginRequirePress_settings_nonce');

        foreach ($_POST as $strFieldName => $strFieldValue) {
            \preg_match('/^post_(\d+)$/', $strFieldName, $arrMatch);
            if ($arrMatch && \count($arrMatch) == 2) {
                $idPost = $arrMatch[1];
                updateLoginRequired($idPost, isset($_POST[LOCK_ . $idPost]));
            }
        }

        \wp_redirect(getUrlSettings());
        exit();
    }

    function action_save_post($idPost) {
        if (!\current_user_can('manage_options')) {
            $postToSave = \get_post($idPost);
            if ($postToSave == null ||
                $postToSave->post_author != \get_current_user_id()) {
                return;
            }
        }

        if (isset($_POST[LOGIN_REQUIRE_PRESS__META]) &&
                  $_POST[LOGIN_REQUIRE_PRESS__META] == PRESENT) {
            updateLoginRequired($idPost, isset($_POST[LOGIN_REQUIRE_PRESS__LOCK]) &&
                                               $_POST[LOGIN_REQUIRE_PRESS__LOCK] == ON);
        }
    }

    function action_send_headers() {

        //  No need to redirect to the login page if the user is already logged in.
        if (\is_user_logged_in()) return;

        global $wp;
        $w_p_query = new \WP_Query($wp->query_vars);

        //  Feed pages will obviously contain any login-requiring posts; however, as it would be
        //  undesirable to completely deny access to all the feed pages, the login-requiring
        //  posts will be filtered out from inside each feed by the filter hook 'posts_results'.
        if ($w_p_query->is_feed) return;

        //  Search result pages may contain login-requiring posts; however, as it would be
        //  undesirable to completely deny access to the rest of the search results, the titles
        //  and contents of login-requiring posts will be protected in search result page listings
        //  by the filter hook 'posts_results'.
        if ($w_p_query->is_search) return;

        //  Need to obtain the site root directory:
        $strSelf = $_SERVER['PHP_SELF'];
        if ($strSelf == null) return;
        $posIndexPHP = \strpos($strSelf, 'index.php');
        if ($posIndexPHP === false) return;                         //  Not on regular site page.
        $strSiteRoot = \substr($strSelf, 0, $posIndexPHP);

        //  Need to obtain the internal site path portion after the root directory:
        $strRequestPath = $_SERVER['REQUEST_URI'];
        if ($strRequestPath == null) return;
        if (\strpos($strRequestPath, $strSiteRoot) !== 0) return;
        $strInternalPath = \substr($strRequestPath, \strlen($strSiteRoot));

        global $post;
        if ($w_p_query->have_posts()) {
            while($w_p_query->have_posts()) {
                $w_p_query->the_post();
                if (isLoginRequiredForPost($post)) {
                    \header('Location: ' . \wp_login_url(\home_url($strInternalPath)));
                    exit(0);
                }
            }
            \wp_reset_postdata();
        }
    }

    function callbackMetaBox($post) {
        $flagLR = isLoginRequiredForPost($post);

    ?><p><?=\__('Login protection:',
                DOMAIN_PLUGIN)?><?php
      ?> <strong><?=$flagLR ? \__('Enabled', DOMAIN_PLUGIN)
                            : \__('Disabled',DOMAIN_PLUGIN)?></strong></p><?php

    ?><input type='hidden' name='<?=LOGIN_REQUIRE_PRESS__META?>' value='present'><?php
    ?><label><?php
      ?><input type='checkbox' name='<?=LOGIN_REQUIRE_PRESS__LOCK?>'<?php
              ?><?=$flagLR ? 'checked' : "" ?>><?php
          ?><?=\__('Require login', DOMAIN_PLUGIN)?><?php
    ?></label><?php
    ?><p><i><?php
      ?><?=\__('Any changes will not persist until this post is updated via the \'<b>Publish</b>\' box.',
               DOMAIN_PLUGIN)?><?php
    ?></i></p><?php
    }

    function filter_plugin_action_links($arrLinks) {
        \array_push($arrLinks,
                    '<a href=\'' . getUrlSettings() . '\'>'
                                   . \__('Settings', DOMAIN_PLUGIN) . '</a>');
        return $arrLinks;
    }

    function filter_posts_results($arrPosts) {
        //  This logic is intended to filter out the login-protected posts from the site feeds,
        //  and to protect the contents and titles of login-requiring posts in search result
        //  page listings when the user is not logged in.

        //  Busting out if the user is already logged in:
        if (\is_user_logged_in()) {
            return $arrPosts;
        }

        $flagIsFeed = \is_feed();
        $arrPostsFiltered = [];

        foreach ($arrPosts as $post) {
            if (isLoginRequiredForPost($post)) {
                if ($flagIsFeed) {
                    //  Completely filter login-protected posts from feeds.
                    continue;
                }

                $post->post_content = \__('[Post content protected by Login Require Press.  Login to see the content.]',
                                          DOMAIN_PLUGIN);
                $post->post_excerpt = \__('[Post excerpt protected by Login Require Press.  Login to see the excerpt.]',
                                          DOMAIN_PLUGIN);
                $post->post_title = \__('[Post title protected by Login Require Press.  Login to see the title.]',
                                        DOMAIN_PLUGIN);
            }

            \array_push($arrPostsFiltered, $post);
        }

        return $arrPostsFiltered;
    }

    function getUrlSettings() {
        return \admin_url('options-general.php?page=plugin_LoginRequirePress_settings');
    }

    function isLoginRequiredForPost(&$post) {
        return (\strcasecmp(YES, \get_post_meta($post->ID,
                                                LOGIN_REQUIRE_PRESS,
                                                true)) == 0);
    }

    function plugin_activation_hook() {
         if (\version_compare(\strtolower(\PHP_VERSION), PHP_VERSION_MIN_SUPPORTED, '<')) {
            \wp_die(
                \sprintf(\__('Login Require Press plugin cannot be activated because the currently active PHP version on this server is %s < %s and not supported.  PHP version >= %s is required.',
                             DOMAIN_PLUGIN),
                         \PHP_VERSION,
                         PHP_VERSION_MIN_SUPPORTED,
                         PHP_VERSION_MIN_SUPPORTED));
        }
    }

    function render_settings() {
        //  Based on http://codex.wordpress.org/Administration_Menus
        if (!\current_user_can('manage_options' ))  {
            \wp_die(\__('You do not have sufficient permissions to access this page.',
                        DOMAIN_PLUGIN));
        }


        $renderListOfPosts = function($strName, $strDesc, $arrPosts) {
                ?><hr><?php
                ?><h3><?php
                  ?><?=\__($strName, DOMAIN_PLUGIN)?><?php
                ?></h3><?php
                ?><i><?php
                  ?><?=\__($strDesc, DOMAIN_PLUGIN)?><?php
                ?></i><?php
                if (\count($arrPosts) == 0) {
                    ?><p><strong>[none]</strong></p><?php
                } else {
                    ?><ul><?php
                    foreach ($arrPosts as $objPost) {
                        ?><li><?php
                          ?><a href='<?=\get_edit_post_link($objPost->ID)?>'><?php
                            ?><?=$objPost->post_name ? $objPost->post_name
                                                     : \sprintf(
                                                        \__('[no name %d]',
                                                            DOMAIN_PLUGIN),
                                                        $objPost->ID)
                               ?><?php
                          ?></a><?php
                        ?></li><?php
                    }
                    ?></ul><?php
                }
            };

        $renderRefreshButton = function() {
                ?><p><button class='button-secondary'<?php
                          ?> onclick='window.location.reload()'>Refresh</button></p><?php
            };

    ?><div class="wrap"><?php

        $renderRefreshButton();

        $w_p_query = new \WP_Query(['order'           => 'ASC',
                                    'orderby'         => 'name',
                                    'post_status'     => 'any',
                                    'post_type'       => \get_post_types(['public' => true]),
                                    'posts_per_page'  => -1]);

        if ($w_p_query->have_posts()) {
            ?><p><?=\sprintf(
              \__('Check the checkbox(es) corresponding to the post(s) for which you want to ' .
                  'require user login, then submit the form by clicking \'%1$s\' at the top or ' .
                  'bottom.',
                  DOMAIN_PLUGIN),
              \__('Update LR Settings',
                  DOMAIN_PLUGIN));
                   ?></p><?php
            ?><p><?=\sprintf(
              \__('After submitting the form, make sure that any post(s) you want ' .
                  'login-protected are listed in the \'%1$s\' section below.',
                  DOMAIN_PLUGIN),
              \__('Non-private login-protected post(s)',
                  DOMAIN_PLUGIN))
                   ?></p><?php
            ?><form method='post' action='admin-post.php'><?php
              ?><input type='hidden' name='action'
                                    value='plugin_LoginRequirePress_settings' /><?php
                  \wp_nonce_field('plugin_LoginRequirePress_settings_nonce');

                  $arrNonPrivateLoginProtected = [];
                  $arrNonPrivateLoginPasscodeProtected = [];
                  $arrPrivate = [];
                  $arrPasscodeProtected = [];
              ?><input type='submit' value='<?=\__('Update LR Settings',
                                                   DOMAIN_PLUGIN)
                                              ?>' class='button-primary'/><hr><?php
              ?><table style='border-collapse:collapse'><?php
                ?><tr><?php
                  ?><th style='padding-right:15px;text-align:left'><?=
                    \__('LR', DOMAIN_PLUGIN)
                  ?></th><?php
                  ?><th style='padding-right:15px;text-align:left'><?=
                    \__('Current LR', DOMAIN_PLUGIN)
                  ?></th><?php
                  ?><th style='padding-right:15px;text-align:left'><?=
                    \__('ID', DOMAIN_PLUGIN)
                  ?></th><?php
                  ?><th style='padding-right:15px;text-align:left'><?=
                    \__('Post Name', DOMAIN_PLUGIN)
                  ?></th><?php
                  ?><th style='padding-right:15px;text-align:left'><?=
                    \__('Post Type', DOMAIN_PLUGIN)
                  ?></th><?php
                  ?><th style='padding-right:15px;text-align:left'><?=
                    \__('Page Template', DOMAIN_PLUGIN)
                  ?></th><?php
                  ?><th style='padding-right:15px;text-align:left'><?=
                    \__('Post Status', DOMAIN_PLUGIN)
                  ?></th><?php
                  ?><th style='padding-right:15px;text-align:left'><?=
                    \__('Default Visibility', DOMAIN_PLUGIN)
                  ?></th><?php
                ?></tr><?php
                    $indexRow = 0;
                    while($w_p_query->have_posts()) {
                        $w_p_query->the_post();

                        global $post;
                        $idPost = $post->ID;
                        $isLoginRequired = isLoginRequiredForPost($post);
                        $strPostName = $post->post_name;
                        $urlPostEdit = \get_edit_post_link($idPost);
                        $strPostStatus = \get_post_status($idPost);
                        $isPrivate = ($strPostStatus != 'publish');
                        $strVisibility = $isPrivate ? \__('Private',
                                                          DOMAIN_PLUGIN)
                                                    : \__('Public',
                                                          DOMAIN_PLUGIN);
                        $isPasscodeProtected = ($post->post_password != null);
                        if ($isPasscodeProtected) {
                            $strVisibility = \__('Passcode (AKA password) protected');
                        }

                        if ($isPrivate) {
                            \array_push($arrPrivate, $post);
                        } else if ($isLoginRequired) {
                            \array_push($arrNonPrivateLoginProtected, $post);
                            if ($isPasscodeProtected) {
                                \array_push($arrNonPrivateLoginPasscodeProtected, $post);
                            }
                        }
                        if ($isPasscodeProtected) {
                            \array_push($arrPasscodeProtected, $post);
                        }
                    ?><input type='hidden' name='post_<?=$idPost?>'><?php
                    ?><tr <?=$indexRow % 2 == 0
                             ? 'style=\'background-color:#dde\''
                             : ""?>>
                        <td><input type='checkbox' name='<?=(LOCK_ . $idPost)
                                                          ?>' <?=$isLoginRequired ? 'checked'
                                                                                  : ""?>></td>
                        <td>
                        <?php
                            if ($isLoginRequired) {
                                ?><font color='red'><?php
                                  ?><?=\__(YES, DOMAIN_PLUGIN)?><?php
                                ?></font><?php
                            }
                        ?>
                        </td>
                        <td><a href='<?=$urlPostEdit?>'><?=$idPost?></a></td>
                        <td><a href='<?=$urlPostEdit?>'><?=$strPostName?></a></td>
                        <td><?=$post->post_type?></td>
                        <td><?=\get_page_template_slug($idPost)?></td>
                        <td style='<?=$isPrivate ? 'color:red' : "" ?>'>
                          <?=$strPostStatus?>
                        </td>
                        <td style='<?=$isPrivate || $isPasscodeProtected ? 'color:red' : "" ?>'>
                          <?=$strVisibility?>
                        </td>
                      </tr><?php
                        $indexRow++;
                    }
                    \wp_reset_postdata();
              ?></table><?php
              ?><hr><input type='submit' value='<?=\__('Update LR Settings',
                                                       DOMAIN_PLUGIN)
                                                  ?>' class='button-primary'<?php
                                                   ?> style='margin-bottom:3em'/><?php

            ?></form><?php

            $renderRefreshButton();

            if (\count($arrPrivate) > 0) {
                $renderListOfPosts(
                    'Private / pending post(s):',
                    'These posts are invisible to the public, as well as to the logged-in ' .
                    'Subscribers, Contributors, and other Authors.  Post visibility can be ' .
                    'edited on each post\'s edit page.',
                    $arrPrivate);
            }

            if (true) {
                $renderListOfPosts(
                    'Non-private login-protected post(s):',
                    'These posts will require user login to read, but since logged-in users ' .
                    'will be able to read them, they\'re not "private".  The login protection ' .
                    'can be modified on the table above by checking or unchecking the LR (Login ' .
                    'Required) checkbox corresponding to each post.',
                    $arrNonPrivateLoginProtected);
            }

            if (\count($arrPasscodeProtected) > 0) {
                $renderListOfPosts(
                    'Passcode-protected post(s):',
                    'Also known as the WordPress "Password Protected" posts, but different from ' .
                    'login-protected.  The content of any of these posts will be invisible to ' .
                    'the public, as well as to any logged-in users, until they enter a special ' .
                    'post-only passcode / "password", previously chosen in the Post Visibility ' .
                    'section of each of these posts\' edit pages.',
                    $arrPasscodeProtected);
            }
            if (\count($arrNonPrivateLoginPasscodeProtected) > 0) {
                $renderListOfPosts(
                    'Non-private login-and-passcode-protected post(s):',
                    'These posts are both login-protected and passcode-protected.  Website '.
                    'visitors will have to first login, and then enter an additional ' .
                    'post-specific passcode to read any of these post(s).',
                    $arrNonPrivateLoginPasscodeProtected);
            }

            ?><hr><?php

            $renderRefreshButton();
        } else {
        ?><?=\__('No posts', DOMAIN_PLUGIN)?><?php
        }
    ?></div><?php
    }

    function updateLoginRequired($idPost, $flagRequired) {
        if ($flagRequired) {
            \update_post_meta($idPost, LOGIN_REQUIRE_PRESS, YES);
        } else {
            \delete_post_meta($idPost, LOGIN_REQUIRE_PRESS);
        }
    }
?>
