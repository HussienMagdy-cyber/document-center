<?php

if (! defined('ABSPATH')) {
    exit;
}

add_filter('upload_mimes', function ($mimes) {
    $mimes['txt']  = 'text/plain';
    // Images
    $mimes['jpg|jpeg'] = 'image/jpeg';
    $mimes['png']      = 'image/png';
    $mimes['gif']      = 'image/gif';
    $mimes['webp']     = 'image/webp';
    $mimes['svg']      = 'image/svg+xml';
    // Video
    $mimes['mp4']  = 'video/mp4';
    $mimes['mov']  = 'video/quicktime';
    $mimes['avi']  = 'video/x-msvideo';
    $mimes['webm'] = 'video/webm';
    $mimes['mkv']  = 'video/x-matroska';
    // Audio
    $mimes['mp3'] = 'audio/mpeg';
    $mimes['wav'] = 'audio/wav';
    $mimes['ogg'] = 'audio/ogg';
    return $mimes;
});

