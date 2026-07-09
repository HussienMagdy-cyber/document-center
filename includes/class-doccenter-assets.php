<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Enqueue frontend CSS and JS for the Document Center dashboard.
 */
class DocCenter_Assets
{
    public static function init()
    {
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue']);
    }

    public static function enqueue()
    {
        if (! is_user_logged_in() || ! is_page('dashboard')) {
            return;
        }

        wp_enqueue_style(
            'doccenter-dashboard',
            DOCCENTER_PLUGIN_URL . 'assets/css/style.css',
            [],
            DOCCENTER_PLUGIN_VERSION
        );

        wp_enqueue_script(
            'doccenter-dashboard',
            DOCCENTER_PLUGIN_URL . 'assets/js/dashboard.js',
            ['jquery'],
            DOCCENTER_PLUGIN_VERSION,
            true
        );

        wp_localize_script(
            'doccenter-dashboard',
            'doccenter_ajax',
            [
                'url' => admin_url('admin-ajax.php'),
            ]
        );
    }
}
