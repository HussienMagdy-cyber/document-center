<?php

if (! defined('ABSPATH')) {
    exit;
}

function doccenter_handle_register()
{
    if (isset($_POST['doccenter_action']) && $_POST['doccenter_action'] === 'register') {

        if (! isset($_POST['doccenter_register_nonce']) || ! wp_verify_nonce($_POST['doccenter_register_nonce'], 'doccenter_register')) {
            doccenter_add_notice('Security check failed.', 'error');
            return;
        }

        $username = isset($_POST['doc_username']) ? sanitize_user($_POST['doc_username']) : '';
        $email    = isset($_POST['doc_email']) ? sanitize_email($_POST['doc_email']) : '';
        $password = isset($_POST['doc_password']) ? $_POST['doc_password'] : '';

        if ($username === '' || $email === '' || $password === '') {
            doccenter_add_notice('All fields are required.', 'error');
            return;
        }

        if (username_exists($username)) {
            doccenter_add_notice('Username already exists.', 'error');
            return;
        }

        if (email_exists($email)) {
            doccenter_add_notice('Email already exists.', 'error');
            return;
        }

        $user_id = wp_create_user($username, $password, $email);
        if (is_wp_error($user_id)) {
            doccenter_add_notice($user_id->get_error_message(), 'error');
            return;
        }

        $user = new WP_User($user_id);
        $user->set_role('document_viewer');

        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);
        wp_safe_redirect(site_url('/dashboard'));
        exit;
    }
}
add_action('init', 'doccenter_handle_register');

