<?php

if (! defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_doccenter_demote_admin', 'doccenter_demote_admin');
function doccenter_demote_admin()
{

    // Must be super admin to demote admins
    $current_user = wp_get_current_user();
    $current_roles = (array) $current_user->roles;

    $is_super = in_array('document_super_admin', $current_roles, true) || in_array('administrator', $current_roles, true);

    if (! $is_super) {
        wp_send_json_error('You do not have permission.');
    }

    if (!isset($_POST['user_id'])) {
        wp_send_json_error('User ID missing.');
    }

    $uid = intval($_POST['user_id']);
    $user = get_user_by('ID', $uid);

    if (!$user) {
        wp_send_json_error('User not found.');
    }

    // Remove admin role
    $user->remove_role('document_admin');

    // Add viewer role
    $user->add_role('document_viewer');

    wp_send_json_success('Admin demoted to viewer successfully.');
}

