<?php

if (! defined('ABSPATH')) {
    exit;
}

function doccenter_handle_group_management()
{

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    $action = isset($_POST['doccenter_action']) ? sanitize_text_field($_POST['doccenter_action']) : '';

    if ($action !== 'assign_group_members' || ! isset($_POST['group_id'])) {
        return;
    }

    $group_id = sanitize_text_field($_POST['group_id']);
    $selected_viewers = isset($_POST['group_viewers']) ? (array) $_POST['group_viewers'] : [];
    $selected_viewers = array_map('intval', $selected_viewers);

    // Get all viewer IDs
    $all_viewers = get_users([
        'role'   => 'document_viewer',
        'fields' => 'ID',
    ]);

    if (! empty($all_viewers)) {
        foreach ($all_viewers as $vid) {
            $current_groups = doccenter_get_user_groups($vid);
            if (! is_array($current_groups)) {
                $current_groups = [];
            }

            if (in_array($vid, $selected_viewers, true)) {
                if (! in_array($group_id, $current_groups, true)) {
                    $current_groups[] = $group_id;
                }
            } else {
                $current_groups = array_values(array_diff($current_groups, [$group_id]));
            }

            doccenter_set_user_groups($vid, $current_groups);
        }
    }

    doccenter_add_notice('Group viewers updated.', 'success');
    wp_redirect(site_url('/dashboard?tab=groups'));
    exit;
}

add_action('init', 'doccenter_handle_group_management');

