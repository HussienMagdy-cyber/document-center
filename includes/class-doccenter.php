<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Main plugin bootstrap — loads all modules in dependency order.
 */
final class DocCenter
{
    private static $instance = null;

    public static function instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        $this->load_modules();
        DocCenter_Assets::init();
    }

    private function load_modules()
    {
        $dir = DOCCENTER_PLUGIN_DIR . 'includes/';

        $modules = [
            // Core
            'core/notices.php',
            'core/admin-bar.php',
            'core/roles.php',

            // Auth & redirects
            'auth/redirects.php',
            'auth/auth-styles.php',
            'auth/login-handler.php',
            'auth/register-handler.php',

            // Groups
            'groups/functions.php',
            'groups/management-handler.php',

            // Documents
            'documents/helpers.php',
            'documents/visibility.php',
            'documents/search.php',
            'documents/group-queries.php',
            'documents/upload-handler.php',
            'documents/edit-handler.php',
            'documents/delete-handler.php',
            'documents/mimes.php',

            // Requests
            'requests/cpt.php',
            'requests/handlers.php',

            // Users
            'users/management-handler.php',
            'users/demote-user.php',

            // AJAX
            'ajax/demote-admin.php',

            // Shortcodes
            'shortcodes/login.php',
            'shortcodes/register.php',
            'shortcodes/dashboard.php',
        ];

        foreach ($modules as $module) {
            require_once $dir . $module;
        }
    }
}
