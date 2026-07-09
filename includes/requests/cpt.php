<?php

if (! defined('ABSPATH')) {
    exit;
}

function doccenter_register_request_cpt()
{
    register_post_type(
        'doc_request',
        [
            'labels'   => [
                'name' => 'Document Requests',
            ],
            'public'   => false,
            'show_ui'  => false,
            'supports' => ['title', 'editor'],
        ]
    );
}
add_action('init', 'doccenter_register_request_cpt');

