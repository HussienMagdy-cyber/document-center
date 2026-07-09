<?php

/**
 * Plugin Name: Document Center
 * Description: Frontend document center with viewer groups, sharing, and custom dashboard.
 * Version: 2.6
 * Author: Your Name
 * Text Domain: document-center
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! defined('DOCCENTER_PLUGIN_VERSION')) {
    define('DOCCENTER_PLUGIN_VERSION', '2.6');
}

if (! defined('DOCCENTER_PLUGIN_DIR')) {
    define('DOCCENTER_PLUGIN_DIR', plugin_dir_path(__FILE__));
}

if (! defined('DOCCENTER_PLUGIN_URL')) {
    define('DOCCENTER_PLUGIN_URL', plugin_dir_url(__FILE__));
}

require_once DOCCENTER_PLUGIN_DIR . 'includes/class-doccenter-assets.php';
require_once DOCCENTER_PLUGIN_DIR . 'includes/class-doccenter.php';

DocCenter::instance();
