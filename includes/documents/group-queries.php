<?php

if (! defined('ABSPATH')) {
    exit;
}

function doccenter_get_group_documents($group_id, $user_id)
{
    $docs = doccenter_get_visible_documents_for_user($user_id, 300);
    $result = [];
    
    // Get all descendant group IDs (including the current group)
    $descendant_ids = doccenter_get_descendant_group_ids($group_id);
    $descendant_ids[] = $group_id; // Include current group

    while ($docs->have_posts()) {
        $docs->the_post();
        $id = get_the_ID();
        $allowed_groups = get_post_meta($id, '_document_groups', true) ?: [];

        if (is_array($allowed_groups) && !empty(array_intersect($descendant_ids, $allowed_groups))) {
            $result[] = $id;
        }
    }
    wp_reset_postdata();
    return $result;
}

function doccenter_get_descendant_group_ids($group_id)
{
    $descendants = [];
    $child_groups = doccenter_get_child_groups($group_id);
    
    foreach ($child_groups as $child) {
        $descendants[] = $child['id'];
        $grandchildren = doccenter_get_descendant_group_ids($child['id']);
        $descendants = array_merge($descendants, $grandchildren);
    }
    
    return $descendants;
}

function doccenter_get_group_documents_recursive($group_id, $user_id)
{
    // Get documents directly in this group
    $result = doccenter_get_group_documents($group_id, $user_id);
    
    // Get documents from child groups
    $child_groups = doccenter_get_child_groups($group_id);
    foreach ($child_groups as $child_group) {
        $child_docs = doccenter_get_group_documents_recursive($child_group['id'], $user_id);
        $result = array_merge($result, $child_docs);
    }
    
    return $result;
}

function doccenter_get_direct_documents($user_id)
{
    $docs = doccenter_get_visible_documents_for_user($user_id, 300);
    $result = [];

    while ($docs->have_posts()) {
        $docs->the_post();
        $id = get_the_ID();

        $vis = get_post_meta($id, '_document_visibility', true);
        if ($vis === 'custom') {
            $users = get_post_meta($id, '_document_users', true) ?: [];
            if (in_array($user_id, $users)) {
                $result[] = $id;
            }
        }
    }
    wp_reset_postdata();
    return $result;
}

function doccenter_count_group_documents($group_id, $user_id)
{
    return count(doccenter_get_group_documents($group_id, $user_id));
}

function doccenter_count_direct_documents($user_id)
{
    return count(doccenter_get_direct_documents($user_id));
}

