<?php

if (! defined('ABSPATH')) {
    exit;
}

function doccenter_get_groups()
{
    $groups = get_option('doccenter_groups', []);
    if (! is_array($groups)) {
        $groups = [];
    }
    return $groups;
}

function doccenter_save_groups($groups)
{
    update_option('doccenter_groups', $groups);
}

function doccenter_add_group($name, $parent_id = '')
{
    $name = trim($name);
    if ($name === '') {
        return;
    }
    $groups   = doccenter_get_groups();
    $id       = uniqid('grp_', true);
    $groups[] = [
        'id'     => $id,
        'name'   => $name,
        'parent' => $parent_id,
    ];
    doccenter_save_groups($groups);
    return $id;
}

function doccenter_delete_group($id)
{
    $groups   = doccenter_get_groups();
    $changed  = false;
    $filtered = [];

    foreach ($groups as $g) {
        if ($g['id'] === $id) {
            $changed = true;
            continue;
        }
        $filtered[] = $g;
    }

    if ($changed) {
        doccenter_save_groups($filtered);
    }
}

function doccenter_get_child_groups($parent_id)
{
    $groups = doccenter_get_groups();
    $children = [];
    
    foreach ($groups as $group) {
        if (isset($group['parent']) && $group['parent'] === $parent_id) {
            $children[] = $group;
        }
    }
    
    return $children;
}

function doccenter_get_group_hierarchy($parent_id = '', $level = 0)
{
    $groups = doccenter_get_groups();
    $hierarchy = [];
    
    foreach ($groups as $group) {
        $group_parent = isset($group['parent']) ? $group['parent'] : '';
        if ($group_parent === $parent_id) {
            $group['level'] = $level;
            $hierarchy[] = $group;
            $children = doccenter_get_group_hierarchy($group['id'], $level + 1);
            $hierarchy = array_merge($hierarchy, $children);
        }
    }
    
    return $hierarchy;
}

function doccenter_get_group_path($group_id)
{
    $groups = doccenter_get_groups();
    $path = [];
    $current_id = $group_id;
    
    while ($current_id) {
        $found = false;
        foreach ($groups as $group) {
            if ($group['id'] === $current_id) {
                array_unshift($path, $group);
                $current_id = isset($group['parent']) ? $group['parent'] : '';
                $found = true;
                break;
            }
        }
        if (!$found) break;
    }
    
    return $path;
}

/**
 * User groups meta
 */
function doccenter_get_user_groups($user_id)
{
    $groups = get_user_meta($user_id, 'doccenter_groups', true);
    if (! is_array($groups)) {
        $groups = [];
    }
    return $groups;
}

function doccenter_set_user_groups($user_id, $group_ids)
{
    $group_ids = array_filter(array_map('sanitize_text_field', (array) $group_ids));
    update_user_meta($user_id, 'doccenter_groups', $group_ids);
}

