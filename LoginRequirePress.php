<?php
/*
  Plugin Name: LoginRequirePress
  Plugin URI: https://github.com/maratbn/LoginRequirePress
  Plugin URI: http://www.maratbn.com/projects/login-require-press/
  Description: WordPress plugin that allows site administrators to specifically designate arbitrary posts with any public post type as viewable only after user login.  Unauthenticated site visitors attempting to view any page that includes any such specifically designated post will then be automatically redirected to the site's default login page, and then back to the original page after they login, thereby limiting access only to logged-in users with subscriber roles and above.  Plugin will still allow unauthenticated downloading of site's feeds, but will filter out any login-requiring posts from the feed listings.
  Author: Marat Nepomnyashy
  Author URI: http://www.maratbn.com
  License: GPL3
  Version: 0.1.1
  Text Domain: domain-plugin-LoginRequirePress
*/

/*
  LoginRequirePress -- WordPress plugin that allows site administrators to
                       specifically designate arbitrary posts with any public
                       post type as viewable only after user login.

                       Unauthenticated site visitors attempting to view any
                       page that includes any such specifically designated
                       post will then be automatically redirected to the
                       site's default login page, and then back to the
                       original page after they login, thereby limiting access
                       only to logged-in users with subscriber roles and
                       above.

                       Plugin will still allow unauthenticated downloading of
                       site's feeds, but will filter out any login-requiring
                       posts from the feed listings.

  https://github.com/maratbn/LoginRequirePress
  http://www.maratbn.com/projects/login-require-press/

  Copyright (C) 2015  Marat Nepomnyashy  http://maratbn.com  maratbn@gmail

  Version:        0.1.1

  Module:         LoginRequirePress.php

  Description:    Main PHP file for the WordPress plugin 'LoginRequirePress'.

  This file is part of LoginRequirePress.

  Licensed under the GNU General Public License Version 3.

  LoginRequirePress is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  LoginRequirePress is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with LoginRequirePress.  If not, see <http://www.gnu.org/licenses/>.
*/

    namespace plugin_LoginRequirePress;


    const LOGIN_REQUIRE_PRESS     = 'login_require_press';
    const YES                     = 'yes';

    add_action('admin_menu', '\\plugin_LoginRequirePress\\action_admin_menu');
    add_action('admin_post_plugin_LoginRequirePress_settings',
               '\\plugin_LoginRequirePress\\action_admin_post_plugin_LoginRequirePress_settings');
    add_action('send_headers', '\\plugin_LoginRequirePress\\action_send_headers');

    add_filter('plugin_action_links_' . plugin_basename(__FILE__),
                                        '\\plugin_LoginRequirePress\\filter_plugin_action_links');
    add_filter('posts_results', '\\plugin_LoginRequirePress\\filter_posts_results');


    function action_admin_menu() {
        add_options_page( __('LoginRequirePress Settings', 'domain-plugin-LoginRequirePress'),
                          __('LoginRequirePress', 'domain-plugin-LoginRequirePress'),
                          'manage_options',
                          'plugin_LoginRequirePress_settings',
                          '\\plugin_LoginRequirePress\\render_settings');
    }

    function action_admin_post_plugin_LoginRequirePress_settings() {
        //  Based on: http://jaskokoyn.com/2013/03/26/wordpress-admin-forms/
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient user permissions to modify options.',
                      'domain-plugin-LoginRequirePress'));
        }

        // Check that nonce field
        check_admin_referer('plugin_LoginRequirePress_settings_nonce');

        foreach ($_POST as $strFieldName => $strFieldValue) {
            preg_match('/^post_(\d+)$/', $strFieldName, $arrMatch);
            if ($arrMatch && count($arrMatch) == 2) {
                $idPost = $arrMatch[1];
                $flagIsLocked = isset($_POST['lock_' . $idPost]);
                if ($flagIsLocked) {
                    \update_post_meta($idPost, LOGIN_REQUIRE_PRESS, YES);
                } else {
                    \delete_post_meta($idPost, LOGIN_REQUIRE_PRESS);
                }
            }
        }

        wp_redirect(getUrlSettings());
        exit();
    }

    function action_send_headers() {

        //  No need to redirect to the login page if the user is already logged in.
        if (is_user_logged_in()) return;

        global $wp;
        $w_p_query = new \WP_Query($wp->query_vars);

        //  Feed pages will obviously contain any login-requiring posts; however, as it would be
        //  undesirable to completely deny access to all the feed pages, the login-requiring
        //  posts will be filtered out from inside each feed by the filter hook 'posts_results'.
        if ($w_p_query->is_feed) return;

        global $post;
        if ($w_p_query->have_posts()) {
            while($w_p_query->have_posts()) {
                $w_p_query->the_post();
                if (isLoginRequiredForPost($post)) {
                    \header('Location: ' . wp_login_url(home_url($_SERVER['REQUEST_URI'])));
                    exit(0);
                }
            }
            wp_reset_postdata();
        }
    }

    function filter_plugin_action_links($arrLinks) {
        array_push($arrLinks,
                   '<a href=\'' . getUrlSettings() . '\'>'
                                    . __('Settings', 'domain-plugin-LoginRequirePress') . '</a>');
        return $arrLinks;
    }

    function filter_posts_results($arrPosts) {
        //  This logic is intended to filter out the login-protected posts from the site feeds.

        //  Busting out if the current query is not for a feed:
        if (!\is_feed()) return $arrPosts;

        $arrPostsFiltered = [];

        foreach ($arrPosts as $post) {
            if (isLoginRequiredForPost($post)) continue;

            array_push($arrPostsFiltered, $post);
        }

        return $arrPostsFiltered;
    }

    function getUrlSettings() {
        return admin_url('options-general.php?page=plugin_LoginRequirePress_settings');
    }

    function isLoginRequiredForPost(&$post) {
        return (strcasecmp(YES, \get_post_meta($post->ID,
                                               LOGIN_REQUIRE_PRESS,
                                               true)) == 0);
    }

    function render_settings() {
        //  Based on http://codex.wordpress.org/Administration_Menus
        if (!current_user_can('manage_options' ))  {
            wp_die(__('You do not have sufficient permissions to access this page.',
                      'domain-plugin-LoginRequirePress'));
        }
    ?><div class="wrap"><?php
      ?><p><?=sprintf(
        __('Check the checkbox(es) corresponding to the post(s) for which you want to require ' .
           'user login, then submit the form by clicking \'%1$s\' at the top or bottom.',
           'domain-plugin-LoginRequirePress'),
        __('Update LR Settings',
           'domain-plugin-LoginRequirePress'));
             ?></p><?php
      ?><form method='post' action='admin-post.php'><?php
        ?><input type='hidden' name='action' value='plugin_LoginRequirePress_settings' /><?php
          wp_nonce_field('plugin_LoginRequirePress_settings_nonce');

          $w_p_query = new \WP_Query(['order'           => 'ASC',
                                      'orderby'         => 'name',
                                      'post_status'     => 'any',
                                      'post_type'       => \get_post_types(['public' => true]),
                                      'posts_per_page'  => -1]);

          global $post;
          if ($w_p_query->have_posts()) {
          ?><input type='submit' value='<?=__('Update LR Settings',
                                              'domain-plugin-LoginRequirePress')
                                          ?>' class='button-primary'/><hr><?php
          ?><table style='border-collapse:collapse'><?php
            ?><tr><?php
              ?><th style='padding-right:15px;text-align:left'><?=
                __('LR', 'domain-plugin-LoginRequirePress')
              ?></th><?php
              ?><th style='padding-right:15px;text-align:left'><?=
                __('Current LR', 'domain-plugin-LoginRequirePress')
              ?></th><?php
              ?><th style='padding-right:15px;text-align:left'><?=
                __('ID', 'domain-plugin-LoginRequirePress')
              ?></th><?php
              ?><th style='padding-right:15px;text-align:left'><?=
                __('Post Name', 'domain-plugin-LoginRequirePress')
              ?></th><?php
              ?><th style='padding-right:15px;text-align:left'><?=
                __('Post Type', 'domain-plugin-LoginRequirePress')
              ?></th><?php
              ?><th style='padding-right:15px;text-align:left'><?=
                __('Page Template', 'domain-plugin-LoginRequirePress')
              ?></th><?php
              ?><th style='padding-right:15px;text-align:left'><?=
                __('Post Status', 'domain-plugin-LoginRequirePress')
              ?></th><?php
            ?></tr><?php
                $indexRow = 0;
                while($w_p_query->have_posts()) {
                    $w_p_query->the_post();
                    $idPost = $post->ID;
                    $isLoginRequired = isLoginRequiredForPost($post);
                    $strPostName = $post->post_name;
                ?><input type='hidden' name='post_<?=$idPost?>'><?php
                ?><tr <?=$indexRow % 2 == 0
                         ? 'style=\'background-color:#dde\''
                         : ""?>>
                    <td><input type='checkbox' name='lock_<?=$idPost?>' <?=$isLoginRequired
                                                                           ? 'checked'
                                                                           : ""?>></td>
                    <td>
                    <?php
                        if ($isLoginRequired) {
                            echo YES;
                        }
                    ?>
                    </td>
                    <td><a href='<?=get_edit_post_link($idPost)?>'><?=$idPost?></a></td>
                    <td><a href='<?=get_edit_post_link($idPost)?>'><?=$strPostName?></a></td>
                    <td><?=$post->post_type?></td>
                    <td><?=get_page_template_slug($idPost)?></td>
                    <td><?=get_post_status($idPost)?></td>
                  </tr><?php
                    $indexRow++;
                }
                wp_reset_postdata();
          ?></table><?php
          ?><hr><input type='submit' value='<?=__('Update LR Settings',
                                                  'domain-plugin-LoginRequirePress')
                                              ?>' class='button-primary'/><?php
          } else {
          ?><?=__('No posts', 'domain-plugin-LoginRequirePress')?><?php
          }
      ?></form></div><?php
    }

?>