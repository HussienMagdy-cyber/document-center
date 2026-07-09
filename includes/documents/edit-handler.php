<?php

if (! defined('ABSPATH')) {
    exit;
}

function doccenter_handle_edit_document()
{
    if (! is_user_logged_in() || ! doccenter_is_admin_user()) {
        return;
    }

    if (empty($_POST['doccenter_action']) || $_POST['doccenter_action'] !== 'edit_document') {
        return;
    }

    if (! isset($_POST['doccenter_edit_nonce']) || ! wp_verify_nonce($_POST['doccenter_edit_nonce'], 'doccenter_edit')) {
        doccenter_add_notice('Security check failed.', 'error');
        return;
    }

    $attachment_id = isset($_POST['doccenter_edit_id']) ? intval($_POST['doccenter_edit_id']) : 0;
    if (! $attachment_id || get_post_type($attachment_id) !== 'attachment') {
        doccenter_add_notice('Invalid document.', 'error');
        return;
    }

    $title      = isset($_POST['doccenter_edit_title']) ? sanitize_text_field($_POST['doccenter_edit_title']) : '';
    $notes      = isset($_POST['doccenter_edit_notes']) ? wp_kses_post($_POST['doccenter_edit_notes']) : '';
    $visibility = isset($_POST['doccenter_edit_visibility']) ? sanitize_text_field($_POST['doccenter_edit_visibility']) : 'admins';
    $group_ids  = isset($_POST['doccenter_edit_groups']) ? (array) $_POST['doccenter_edit_groups'] : [];
    $user_ids   = isset($_POST['doccenter_edit_users']) ? (array) $_POST['doccenter_edit_users'] : [];

    $group_ids = array_map('sanitize_text_field', $group_ids);
    $user_ids  = array_map('intval', $user_ids);

    if ($title) {
        wp_update_post(
            [
                'ID'         => $attachment_id,
                'post_title' => $title,
            ]
        );
    }

    update_post_meta($attachment_id, '_document_notes', $notes);
    update_post_meta($attachment_id, '_document_visibility', $visibility);
    update_post_meta($attachment_id, '_document_groups', $group_ids);
    update_post_meta($attachment_id, '_document_users', $user_ids);

    doccenter_add_notice('Document updated successfully.', 'success');
    wp_redirect(site_url('/dashboard?tab=documents'));
    exit;
}
add_action('init', 'doccenter_handle_edit_document');

