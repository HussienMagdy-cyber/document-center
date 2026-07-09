<?php

if (! defined('ABSPATH')) {
    exit;
}

function doccenter_get_document_modified_date($doc_id, $format = 'M j, Y')
{
    $modified = get_post_modified_time($format, false, $doc_id);
    return $modified ? $modified : get_the_date($format, $doc_id);
}


/**
 * --------------------------------------------------
 * FILE TYPE HELPERS (Media support)
 * --------------------------------------------------
 */
function doccenter_get_file_type($ext)
{
    $ext = strtolower($ext);
    $image_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'ico'];
    $video_exts = ['mp4', 'mov', 'avi', 'webm', 'mkv', 'flv', 'wmv'];
    $audio_exts = ['mp3', 'wav', 'ogg', 'aac', 'flac', 'm4a', 'wma'];
    $doc_exts   = ['pdf', 'docx', 'xlsx', 'pptx', 'txt', 'doc', 'xls', 'ppt', 'csv', 'rtf'];

    if (in_array($ext, $image_exts, true)) return 'image';
    if (in_array($ext, $video_exts, true)) return 'video';
    if (in_array($ext, $audio_exts, true)) return 'audio';
    if (in_array($ext, $doc_exts, true))   return 'document';
    return 'file';
}

function doccenter_get_file_icon($ext)
{
    $type = doccenter_get_file_type($ext);
    $icons = [
        'image'    => '🖼️',
        'video'    => '🎬',
        'audio'    => '🎵',
        'document' => '📄',
        'file'     => '📎',
    ];
    return $icons[$type] ?? '📎';
}

function doccenter_get_file_icon_svg($ext)
{
    $type = doccenter_get_file_type($ext);

    // Image icon
    if ($type === 'image') {
        return '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#8b5cf6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>';
    }
    // Video icon
    if ($type === 'video') {
        return '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="2.18" ry="2.18"/><line x1="7" y1="2" x2="7" y2="22"/><line x1="17" y1="2" x2="17" y2="22"/><line x1="2" y1="12" x2="22" y2="12"/><line x1="2" y1="7" x2="7" y2="7"/><line x1="2" y1="17" x2="7" y2="17"/><line x1="17" y1="17" x2="22" y2="17"/><line x1="17" y1="7" x2="22" y2="7"/></svg>';
    }
    // Audio icon
    if ($type === 'audio') {
        return '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>';
    }
    // Document icon (default)
    return '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>';
}

function doccenter_is_media_previewable($ext)
{
    $type = doccenter_get_file_type($ext);
    return in_array($type, ['image', 'video', 'audio'], true);
}

function doccenter_get_document_expiry_date($doc_id, $format = 'M j, Y')
{
    $modified_timestamp = get_post_modified_time('U', false, $doc_id);
    if (! $modified_timestamp || $modified_timestamp === '0') {
        $modified_timestamp = get_post_time('U', false, $doc_id);
    }

    $expiry_timestamp = strtotime('+2 years', (int) $modified_timestamp);
    if ($expiry_timestamp === false) {
        return '';
    }

    return date_i18n($format, $expiry_timestamp);
}

