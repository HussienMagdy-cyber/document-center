<?php

if (! defined('ABSPATH')) {
    exit;
}

add_action('template_redirect', function () {
    if (is_admin()) return;

    // Already on sign-in or register pages
    if (is_page('sign-in') || is_page('sign-up')) return;

    // Logged in → OK
    if (is_user_logged_in()) return;

    // Redirect visitors to sign-in
    wp_redirect(site_url('/sign-in'));
    exit;
});

function doccenter_redirect_home_to_dashboard()
{
    if (! is_user_logged_in()) {
        return;
    }

    if (! function_exists('is_front_page') || ! is_front_page()) {
        return;
    }

    $user  = wp_get_current_user();
    $roles = (array) $user->roles;

    if (
        in_array('administrator', $roles, true) ||
        in_array('document_admin', $roles, true) ||
        in_array('document_viewer', $roles, true)
    ) {
        wp_safe_redirect(site_url('/dashboard'));
        exit;
    }
}
add_action('template_redirect', 'doccenter_redirect_home_to_dashboard');

