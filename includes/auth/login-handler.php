<?php

if (! defined('ABSPATH')) {
    exit;
}

function doccenter_handle_login()
{
    if (isset($_POST['doccenter_action']) && $_POST['doccenter_action'] === 'login') {

        if (! isset($_POST['doccenter_login_nonce']) || ! wp_verify_nonce($_POST['doccenter_login_nonce'], 'doccenter_login')) {
            doccenter_add_notice('Security check failed.', 'error');
            return;
        }

        $username = isset($_POST['doc_login_username']) ? sanitize_user($_POST['doc_login_username']) : '';
        $password = isset($_POST['doc_login_password']) ? $_POST['doc_login_password'] : '';
        $remember = isset($_POST['doc_login_remember']);

        if ($username === '' || $password === '') {
            doccenter_add_notice('Username and password are required.', 'error');
            return;
        }

        $user = wp_signon(
            [
                'user_login'    => $username,
                'user_password' => $password,
                'remember'      => $remember,
            ],
            false
        );

        if (is_wp_error($user)) {
            doccenter_add_notice($user->get_error_message(), 'error');
            return;
        }

        wp_safe_redirect(site_url('/dashboard'));
        exit;
    }
}
add_action('init', 'doccenter_handle_login');

