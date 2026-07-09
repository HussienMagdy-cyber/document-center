<?php

if (! defined('ABSPATH')) {
    exit;
}

function doccenter_demote_user()
{
    // Only process our specific action
    if (empty($_POST['doccenter_action']) || $_POST['doccenter_action'] !== 'demote_user') {
        return;
    }

    if (!is_user_logged_in() || !current_user_can('document_super_admin')) {
        doccenter_add_notice('You do not have permission.', 'error');
        return;
    }

    if (empty($_POST['user_id'])) {
        doccenter_add_notice('Invalid user.', 'error');
        return;
    }

    $uid = (int) $_POST['user_id'];

    $user = get_user_by('ID', $uid);
    if (!$user) {
        doccenter_add_notice('User not found.', 'error');
        return;
    }

    // Remove admin role
    if ($user->has_role('document_admin')) {
        $user->remove_role('document_admin');
    }

    // Add viewer role
    $user->add_role('document_viewer');

    doccenter_add_notice('User demoted to viewer.', 'success');

    wp_redirect(site_url('/dashboard?tab=users'));
    exit;
}
add_action('init', 'doccenter_demote_user');

