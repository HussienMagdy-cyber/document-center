<?php

if (! defined('ABSPATH')) {
    exit;
}

function doccenter_add_notice($message, $type = 'success')
{
    $user_id = get_current_user_id();
    if ($user_id) {
        // Use user meta instead of transient — more reliable
        $notices = get_user_meta($user_id, 'doccenter_notices', true);
        if (!is_array($notices)) {
            $notices = [];
        }
        $notices[] = ['message' => $message, 'type' => $type];
        update_user_meta($user_id, 'doccenter_notices', $notices);
    } else {
        // Anonymous users: use transient with fingerprint
        $fingerprint = md5($_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']);
        $key = 'doccenter_notice_' . $fingerprint;
        set_transient($key, ['message' => $message, 'type' => $type], 60);
    }
}

function doccenter_render_notices()
{
    $user_id = get_current_user_id();
    if ($user_id) {
        $notices = get_user_meta($user_id, 'doccenter_notices', true);
        if (is_array($notices) && !empty($notices)) {
            foreach ($notices as $notice) {
                $type = $notice['type'] ?? 'success';
                $class = ($type === 'error') ? 'doccenter-alert-error' : 'doccenter-alert-success';
                echo '<div class="doccenter-alert ' . esc_attr($class) . '">' . esc_html($notice['message']) . '</div>';
            }
            delete_user_meta($user_id, 'doccenter_notices');
        }
    } else {
        $fingerprint = md5($_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']);
        $key = 'doccenter_notice_' . $fingerprint;
        $notice = get_transient($key);
        if ($notice) {
            $type = $notice['type'] ?? 'success';
            $class = ($type === 'error') ? 'doccenter-alert-error' : 'doccenter-alert-success';
            echo '<div class="doccenter-alert ' . esc_attr($class) . '">' . esc_html($notice['message']) . '</div>';
            delete_transient($key);
        }
    }
}

