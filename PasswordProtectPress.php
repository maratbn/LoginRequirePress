<?php
/*
  Plugin Name: PasswordProtectPress
  Plugin URI: http://www.maratbn.com/
  Description: WordPress plugin for password-protecting regular posts and pages on a WordPress site, making them accessible to regular site subscribers upon login.
  Author: Marat Nepomnyashy
  Author URI: http://www.maratbn.com
  License: GPL3
  Version: 0.0.1-development_unreleased
*/

/*
  PasswordProtectPress -- WordPress plugin for password-protecting regular
                          posts and pages on a WordPress site, making them
                          accessible to regular site subscribers upon login.

  Copyright (C) 2015  Marat Nepomnyashy  http://maratbn.com  maratbn@gmail

  Version:        0.0.1-development_unreleased

  Module:         PasswordProtectPress.php

  Description:    Main PHP file for the WordPress plugin 'PasswordProtectPress'.

  This file is part of PasswordProtectPress.

  Licensed under the GNU General Public License Version 3.

  PasswordProtectPress is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  PasswordProtectPress is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with PasswordProtectPress.  If not, see <http://www.gnu.org/licenses/>.
*/

namespace plugin_PasswordProtectPress;


const PASSWORD_PROTECT_PRESS = 'password_protect_press';

add_action('admin_menu', '\\plugin_PasswordProtectPress\\action_admin_menu');
add_action('send_headers', '\\plugin_PasswordProtectPress\\action_send_headers');


function action_admin_menu() {
    add_options_page( 'PasswordProtectPress Settings',
                      'PasswordProtectPress',
                      'manage_options',
                      'plugin_PasswordProtectPress_settings',
                      '\\plugin_PasswordProtectPress\\render_settings');
}

function action_send_headers() {

    if (is_user_logged_in()) return;

    global $wp;
    $w_p_query = new \WP_Query($wp->query_vars);

    global $post;
    if ($w_p_query->have_posts()) {
        while($w_p_query->have_posts()) {
            $w_p_query->the_post();
            $strPostMetaPasswordProtectPress = \get_post_meta($post->ID,
                                                              PASSWORD_PROTECT_PRESS,
                                                              true);
            if (strcasecmp($strPostMetaPasswordProtectPress, 'yes') == 0) {
                \header('Location: ' . wp_login_url(home_url($_SERVER['REQUEST_URI'])));
                exit(0);
            }
        }
        wp_reset_postdata();
    }
}

function render_settings() {
    //  Based on http://codex.wordpress.org/Administration_Menus
    if (!current_user_can('manage_options' ))  {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
?>
<div class="wrap">
<p>Here is where the form would go if I actually had options.</p>
</div>
<?php
}

?>