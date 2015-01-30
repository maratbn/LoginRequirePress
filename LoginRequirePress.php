<?php
/*
  Plugin Name: LoginRequirePress
  Plugin URI: http://www.maratbn.com/
  Description: WordPress plugin for password-protecting regular posts and pages on a WordPress site, making them accessible to regular site subscribers upon login.
  Author: Marat Nepomnyashy
  Author URI: http://www.maratbn.com
  License: GPL3
  Version: 0.0.1-development_unreleased
*/

/*
  LoginRequirePress -- WordPress plugin for password-protecting regular posts
                       and pages on a WordPress site, making them accessible
                       to regular site subscribers upon login.

  Copyright (C) 2015  Marat Nepomnyashy  http://maratbn.com  maratbn@gmail

  Version:        0.0.1-development_unreleased

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
    add_action('send_headers', '\\plugin_LoginRequirePress\\action_send_headers');


    function action_admin_menu() {
        add_options_page( 'LoginRequirePress Settings',
                          'LoginRequirePress',
                          'manage_options',
                          'plugin_LoginRequirePress_settings',
                          '\\plugin_LoginRequirePress\\render_settings');
    }

    function action_send_headers() {

        if (is_user_logged_in()) return;

        global $wp;
        $w_p_query = new \WP_Query($wp->query_vars);

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

    function isLoginRequiredForPost(&$post) {
        return (strcasecmp(YES, \get_post_meta($post->ID,
                                               LOGIN_REQUIRE_PRESS,
                                               true)) == 0);
    }

    function render_settings() {
        //  Based on http://codex.wordpress.org/Administration_Menus
        if (!current_user_can('manage_options' ))  {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
    ?><div class="wrap"><?php

        $w_p_query = new \WP_Query(['order'           => 'ASC',
                                    'orderby'         => 'name',
                                    'post_status'     => 'any',
                                    'post_type'       => \get_post_types(['public' => true]),
                                    'posts_per_page'  => -1]);

        global $post;
        if ($w_p_query->have_posts()) {
        ?><table><?php
            while($w_p_query->have_posts()) {
                $w_p_query->the_post();
                $isLoginRequired = isLoginRequiredForPost($post);
            ?><tr>
                <td><a href='<?=get_edit_post_link($post->ID)?>'><?=$post->post_name?></a></td>
                <?php
                    if ($isLoginRequired) {
                    ?><td>login required</td><?php
                    }
                ?>
              </tr><?php
            }
            wp_reset_postdata();
        ?></table><?php
        }

    ?></div><?php
    }

?>