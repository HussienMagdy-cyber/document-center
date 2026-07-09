<?php

if (! defined('ABSPATH')) {
    exit;
}

function doccenter_handle_delete_document()
{
    if (! is_user_logged_in() || ! doccenter_is_admin_user()) {
        return;
    }

    if (empty($_POST['doccenter_action']) || $_POST['doccenter_action'] !== 'delete_document') {
        return;
    }

    if (! isset($_POST['doccenter_delete_doc_nonce']) || ! wp_verify_nonce($_POST['doccenter_delete_doc_nonce'], 'doccenter_delete_document')) {
        doccenter_add_notice('Security check failed.', 'error');
        return;
    }

    $doc_id = isset($_POST['doc_id']) ? intval($_POST['doc_id']) : 0;
    if (! $doc_id || get_post_type($doc_id) !== 'attachment') {
        doccenter_add_notice('Invalid document.', 'error');
        return;
    }

    wp_delete_post($doc_id, true);

        doccenter_add_notice('Document deleted successfully.', 'success');
    
       $folder = isset($_POST['folder']) ? sanitize_text_field($_POST['folder']) : '';
    $current_tab = isset($_POST['current_tab']) ? sanitize_text_field($_POST['current_tab']) : 'documents';
    
    if (!empty($folder) && $folder !== 'all' && $folder !== 'direct') {
        wp_redirect(site_url('/dashboard') . '?tab=documents&folder=' . urlencode($folder));
    } elseif ($current_tab === 'overview') {
        wp_redirect(site_url('/dashboard') . '?tab=overview');
    } else {
        wp_redirect(site_url('/dashboard') . '?tab=documents');
    }
    exit;
}
add_action('init', 'doccenter_handle_delete_document');

