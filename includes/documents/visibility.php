<?php

if (! defined('ABSPATH')) {
    exit;
}

function doccenter_get_visible_documents_for_user($user_id, $limit = 20)
{
    $is_admin    = doccenter_is_admin_user();
    $user_groups = doccenter_get_user_groups($user_id);

    // Admin sees everything configured
    if ($is_admin) {
        return new WP_Query(
            [
                'post_type'      => 'attachment',
                'post_status'    => 'inherit',
                'posts_per_page' => $limit,
                'orderby'        => 'date',
                'order'          => 'DESC',
                'meta_query'     => [
                    [
                        'key'     => '_document_visibility',
                        'compare' => 'EXISTS',
                    ],
                ],
            ]
        );
    }

    // Viewer – build OR meta_query
    $meta_query = [
        'relation' => 'OR',

        // Public docs
        [
            'key'   => '_document_visibility',
            'value' => 'everyone',
        ],

        // Direct user share
        [
            'relation' => 'OR',
            [
                'key'     => '_document_users',
                'value'   => '"' . $user_id . '"',
                'compare' => 'LIKE',
            ],
            [
                'key'     => '_document_users',
                'value'   => $user_id,
                'compare' => 'LIKE',
            ]
        ]

    ];

    // Group-based share
    if (! empty($user_groups)) {
        foreach ($user_groups as $group) {
            $meta_query[] = [
                'key'     => '_document_groups',
                'value'   => '"' . $group . '"',
                'compare' => 'LIKE',
            ];
        }
    }

    return new WP_Query(
        [
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => $limit,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'meta_query'     => $meta_query,
        ]
    );
}

