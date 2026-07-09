<?php

if (! defined('ABSPATH')) {
    exit;
}

function doccenter_handle_upload()
{
    if (! is_user_logged_in() || ! doccenter_is_admin_user()) {
        return;
    }

    if (empty($_POST['doccenter_action']) || $_POST['doccenter_action'] !== 'upload_document') {
        return;
    }

    if (! isset($_POST['doccenter_upload_nonce']) || ! wp_verify_nonce($_POST['doccenter_upload_nonce'], 'doccenter_upload')) {
        doccenter_add_notice('Security check failed.', 'error');
        return;
    }

    $max_size = 250 * 1024 * 1024; // 50MB per file
    $files    = [];

    if (isset($_FILES['doccenter_files'])) {
        $files = doccenter_restructure_files_array($_FILES['doccenter_files']);
    } elseif (isset($_FILES['doccenter_file'])) {
        $files = doccenter_restructure_files_array($_FILES['doccenter_file']);
    }

    if (empty($files)) {
        doccenter_add_notice('Please choose one or more files to upload.', 'error');
        return;
    }

        $titles       = isset($_POST['doccenter_title']) ? array_map('sanitize_text_field', (array) $_POST['doccenter_title']) : [];
    $unique_tags  = isset($_POST['doccenter_unique_tag']) ? array_map('sanitize_text_field', (array) $_POST['doccenter_unique_tag']) : [];
    $notes       = isset($_POST['doccenter_notes']) ? wp_kses_post($_POST['doccenter_notes']) : '';
    $visibility  = isset($_POST['doccenter_visibility']) ? sanitize_text_field($_POST['doccenter_visibility']) : 'admins';
    $group_ids   = isset($_POST['doccenter_groups']) ? (array) $_POST['doccenter_groups'] : [];
    $user_ids    = isset($_POST['doccenter_users']) ? (array) $_POST['doccenter_users'] : [];
    $folder_id   = isset($_POST['folder']) ? sanitize_text_field($_POST['folder']) : '';

    if ($folder_id && $folder_id !== 'all' && $folder_id !== 'direct') {
        $valid_group_ids = array_column(doccenter_get_groups(), 'id');
        if (in_array($folder_id, $valid_group_ids, true)) {
            $group_ids[] = $folder_id;
        }
    }

    $group_ids = array_map('sanitize_text_field', $group_ids);
    $user_ids  = array_map('intval', $user_ids);

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';

    $allowed_exts = ['pdf', 'docx', 'xlsx', 'pptx', 'txt', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'mp4', 'mov', 'avi', 'webm', 'mkv', 'mp3', 'wav', 'ogg'];
    $uploaded     = 0;
    $errors       = [];

    foreach ($files as $index => $file) {
        if (empty($file['name']) || $file['error'] !== UPLOAD_ERR_OK) {
            if (! empty($file['name'])) {
                $errors[] = 'Failed to upload: ' . sanitize_text_field($file['name']);
            }
            continue;
        }

        if ($file['size'] > $max_size) {
            $errors[] = 'File is too large: ' . sanitize_text_field($file['name']);
            continue;
        }

        $file_name = $file['name'];
        $ext       = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        if (! in_array($ext, $allowed_exts, true)) {
            $errors[] = 'Invalid file type: ' . sanitize_text_field($file_name);
            continue;
        }

        $movefile = wp_handle_upload($file, ['test_form' => false]);
        if (isset($movefile['error'])) {
            $errors[] = 'Upload error for ' . sanitize_text_field($file_name) . ': ' . $movefile['error'];
            continue;
        }

        $default_title = pathinfo($file_name, PATHINFO_FILENAME);
        $title = isset($titles[$index]) && $titles[$index] !== '' ? $titles[$index] : $default_title;
        $tag   = isset($unique_tags[$index]) && $unique_tags[$index] !== '' ? $unique_tags[$index] : '';
        $tag   = doccenter_ensure_unique_document_tag($tag);

        $attachment = [
            'guid'           => $movefile['url'],
            'post_mime_type' => $movefile['type'],
            'post_title'     => sanitize_text_field($title),
            'post_content'   => '',
            'post_status'    => 'inherit',
        ];

        $attachment_id = wp_insert_attachment($attachment, $movefile['file']);
        if (is_wp_error($attachment_id)) {
            $errors[] = 'Could not save document: ' . sanitize_text_field($file_name);
            continue;
        }

        $attach_data = wp_generate_attachment_metadata($attachment_id, $movefile['file']);
        wp_update_attachment_metadata($attachment_id, $attach_data);

        update_post_meta($attachment_id, '_document_notes', $notes);
        update_post_meta($attachment_id, '_document_visibility', $visibility);
        update_post_meta($attachment_id, '_document_groups', $group_ids);
        update_post_meta($attachment_id, '_document_users', $user_ids);
        update_post_meta($attachment_id, '_document_uploaded_by', get_current_user_id());
        update_post_meta($attachment_id, '_document_unique_tag', $tag);

        $uploaded++;
    }

    if ($uploaded > 0) {
        $message = 'Uploaded ' . intval($uploaded) . ' document' . ($uploaded === 1 ? '' : 's') . ' successfully.';
        if (! empty($errors)) {
            $message .= ' Some files were skipped: ' . implode('; ', $errors);
        }
        doccenter_add_notice($message, 'success');
    } else {
        $message = 'No documents were uploaded. ' . implode('; ', $errors);
        doccenter_add_notice($message, 'error');
    }

    wp_redirect(site_url('/dashboard?tab=upload'));
    exit;
}

function doccenter_restructure_files_array($file_post)
{
    $files = [];
    if (! is_array($file_post['name'])) {
        return [$file_post];
    }

    $file_count = count($file_post['name']);
    for ($i = 0; $i < $file_count; $i++) {
        $files[] = [
            'name'     => $file_post['name'][$i],
            'type'     => $file_post['type'][$i],
            'tmp_name' => $file_post['tmp_name'][$i],
            'error'    => $file_post['error'][$i],
            'size'     => $file_post['size'][$i],
        ];
    }

    return $files;
}

function doccenter_generate_document_tag($length = 8)
{
    $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
    $tag = '';
    for ($i = 0; $i < $length; $i++) {
        $tag .= $characters[random_int(0, strlen($characters) - 1)];
    }
    return $tag;
}

function doccenter_document_tag_exists($tag)
{
    if ($tag === '') {
        return false;
    }

    $existing = get_posts([
        'post_type'      => 'attachment',
        'post_status'    => 'inherit',
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'meta_query'     => [
            [
                'key'     => '_document_unique_tag',
                'value'   => $tag,
                'compare' => '=',
            ],
        ],
    ]);

    return ! empty($existing);
}

function doccenter_ensure_unique_document_tag($tag)
{
    $tag = preg_replace('/[^A-Za-z0-9]/', '', trim($tag));
    if ($tag === '') {
        $tag = doccenter_generate_document_tag();
    }

    $candidate = $tag;
    $counter   = 1;
    while (doccenter_document_tag_exists($candidate)) {
        $candidate = $tag . $counter;
        $counter++;
        if ($counter > 1000) {
            $candidate = doccenter_generate_document_tag(12);
            break;
        }
    }

    return $candidate;
}

add_action('init', 'doccenter_handle_upload');

