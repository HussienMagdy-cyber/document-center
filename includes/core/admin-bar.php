<?php

if (! defined('ABSPATH')) {
    exit;
}

add_filter('show_admin_bar', function () {
    return false;
});

