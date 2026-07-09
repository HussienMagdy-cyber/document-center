<?php

if (! defined('ABSPATH')) {
    exit;
}

function doccenter_handle_request()
{
    if (! is_user_logged_in()) {
        return;
    }
    if (empty($_POST['doccenter_action']) || $_POST['doccenter_action'] !== 'request_document') {
        return;
    }

    if (! isset($_POST['doccenter_request_nonce']) || ! wp_verify_nonce($_POST['doccenter_request_nonce'], 'doccenter_request')) {
        doccenter_add_notice('Security check failed.', 'error');
        return;
    }

    $user_id = get_current_user_id();
    $title   = isset($_POST['doc_req_title']) ? sanitize_text_field($_POST['doc_req_title']) : '';
    $message = isset($_POST['doc_req_message']) ? wp_kses_post($_POST['doc_req_message']) : '';

    if ($title === '') {
        doccenter_add_notice('Request title is required.', 'error');
        return;
    }

    $post_id = wp_insert_post(
        [
            'post_type'    => 'doc_request',
            'post_status'  => 'publish',
            'post_title'   => $title,
            'post_content' => $message,
        ]
    );

    if (is_wp_error($post_id)) {
        doccenter_add_notice('Could not save request.', 'error');
        return;
    }

    update_post_meta($post_id, '_request_user', $user_id);
    update_post_meta($post_id, '_request_status', 'open');

    // Email admins & document_admins
    $admins = get_users(
        [
            'role__in' => ['administrator', 'document_admin'],
        ]
    );

    $subject = 'New document request: ' . $title;
    $body    = "User ID: {$user_id}\nUsername: " . wp_get_current_user()->user_login . "\n\nMessage:\n" . wp_strip_all_tags($message);
    $headers = ['Content-Type: text/plain; charset=UTF-8'];

    foreach ($admins as $admin) {
        wp_mail($admin->user_email, $subject, $body, $headers);
    }

    doccenter_add_notice('Request submitted successfully.', 'success');
    wp_redirect(site_url('/dashboard?tab=requests'));
    exit;
}
add_action('init', 'doccenter_handle_request');

/**
 * Admin reply handler for requests
 */
function doccenter_handle_request_reply()
{
    if (! is_user_logged_in() || ! doccenter_is_admin_user()) {
        return;
    }

    if (empty($_POST['doccenter_action']) || $_POST['doccenter_action'] !== 'reply_request') {
        return;
    }

    if (! isset($_POST['doccenter_reply_nonce']) || ! wp_verify_nonce($_POST['doccenter_reply_nonce'], 'doccenter_reply')) {
        doccenter_add_notice('Security check failed.', 'error');
        return;
    }

    $req_id = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;
    $reply  = isset($_POST['reply_text']) ? wp_kses_post($_POST['reply_text']) : '';

    if (! $req_id || get_post_type($req_id) !== 'doc_request') {
        doccenter_add_notice('Invalid request.', 'error');
        return;
    }

    update_post_meta($req_id, '_request_reply', $reply);
    update_post_meta($req_id, '_request_status', 'closed');

    doccenter_add_notice('Request updated successfully.', 'success');
    wp_redirect(site_url('/dashboard?tab=requests'));
    exit;
}
add_action('init', 'doccenter_handle_request_reply');

/**
 * Viewer delete closed request handler
 */
function doccenter_handle_request_delete()
{
    if (! is_user_logged_in()) {
        return;
    }

    if (empty($_POST['doccenter_action']) || $_POST['doccenter_action'] !== 'delete_request') {
        return;
    }

    if (! isset($_POST['doccenter_request_delete_nonce']) || ! wp_verify_nonce($_POST['doccenter_request_delete_nonce'], 'doccenter_request_delete')) {
        doccenter_add_notice('Security check failed.', 'error');
        return;
    }

    $req_id = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;
    if (! $req_id || get_post_type($req_id) !== 'doc_request') {
        doccenter_add_notice('Invalid request.', 'error');
        return;
    }

    $current_user = get_current_user_id();
    $owner_id     = (int) get_post_meta($req_id, '_request_user', true);

    if ($owner_id !== $current_user && ! doccenter_is_admin_user()) {
        doccenter_add_notice('You are not allowed to delete this request.', 'error');
        return;
    }

    $status = get_post_meta($req_id, '_request_status', true);
    if ($status !== 'closed') {
        doccenter_add_notice('Only closed requests can be removed.', 'error');
        return;
    }

    wp_delete_post($req_id, true);

    doccenter_add_notice('Request removed.', 'success');
    wp_redirect(site_url('/dashboard') . '?tab=requests');
    exit;
}
add_action('init', 'doccenter_handle_request_delete');

