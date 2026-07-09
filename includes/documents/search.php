<?php

if (! defined('ABSPATH')) {
    exit;
}

function doccenter_search_documents($user_id, $search_term)
{
    $is_admin = doccenter_is_admin_user();
    $search_term = sanitize_text_field($search_term);

    if (empty($search_term)) {
        return [];
    }

    $results = [];

    // For admins: search all documents with visibility meta
    if ($is_admin) {
        // Search by title
        $title_args = [
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => 100,
            'orderby'        => 'date',
            'order'          => 'DESC',
            's'              => $search_term,
            'meta_query'     => [
                [
                    'key'     => '_document_visibility',
                    'compare' => 'EXISTS',
                ],
            ],
        ];

        $title_query = new WP_Query($title_args);
        while ($title_query->have_posts()) {
            $title_query->the_post();
            $results[] = get_the_ID();
        }
        wp_reset_postdata();

        // Search by unique tag
        $tag_args = [
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => 100,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'meta_query'     => [
                'relation' => 'AND',
                [
                    'key'     => '_document_visibility',
                    'compare' => 'EXISTS',
                ],
                [
                    'key'     => '_document_unique_tag',
                    'value'   => $search_term,
                    'compare' => 'LIKE',
                ],
            ],
        ];

        $tag_query = new WP_Query($tag_args);
        while ($tag_query->have_posts()) {
            $tag_query->the_post();
            $id = get_the_ID();
            if (!in_array($id, $results)) {
                $results[] = $id;
            }
        }
        wp_reset_postdata();

    } else {
        // Viewers: search only visible documents
        $user_groups = doccenter_get_user_groups($user_id);

        $visibility_query = [
            'relation' => 'OR',
            [
                'key'   => '_document_visibility',
                'value' => 'everyone',
            ],
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

        if (!empty($user_groups)) {
            foreach ($user_groups as $group) {
                $visibility_query[] = [
                    'key'     => '_document_groups',
                    'value'   => '"' . $group . '"',
                    'compare' => 'LIKE',
                ];
            }
        }

        // Search by title
        $title_args = [
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => 100,
            'orderby'        => 'date',
            'order'          => 'DESC',
            's'              => $search_term,
            'meta_query'     => $visibility_query,
        ];

        $title_query = new WP_Query($title_args);
        while ($title_query->have_posts()) {
            $title_query->the_post();
            $results[] = get_the_ID();
        }
        wp_reset_postdata();

        // Search by unique tag
        $tag_args = [
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => 100,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'meta_query'     => [
                'relation' => 'AND',
                $visibility_query,
                [
                    'key'     => '_document_unique_tag',
                    'value'   => $search_term,
                    'compare' => 'LIKE',
                ],
            ],
        ];

        $tag_query = new WP_Query($tag_args);
        while ($tag_query->have_posts()) {
            $tag_query->the_post();
            $id = get_the_ID();
            if (!in_array($id, $results)) {
                $results[] = $id;
            }
        }
        wp_reset_postdata();
    }

    return $results;
}

