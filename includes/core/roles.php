<?php

if (! defined('ABSPATH')) {
    exit;
}

function doccenter_register_roles()
{
    // Super admin: full control over document center (can promote admins, manage everything)
    add_role(
        'document_super_admin',
        'Document Super Admin',
        [
            'read'         => true,
            'upload_files' => true,
            'edit_posts'   => true,
            'delete_posts' => true,
            'list_users'   => true,
            'promote_users' => true,
        ]
    );

    // Standard document admin
    add_role(
        'document_admin',
        'Document Admin',
        [
            'read'         => true,
            'upload_files' => true,
            'edit_posts'   => true,
            'delete_posts' => true,
            'list_users'   => true,
        ]
    );

    // Viewer role – can only read documents shared with them
    add_role(
        'document_viewer',
        'Document Viewer',
        [
            'read' => true,
        ]
    );
}
add_action('init', 'doccenter_register_roles');

/**
 * Check if user is document admin (or WP admin)
 */
function doccenter_is_super_admin_user()
{
    if (! is_user_logged_in()) {
        return false;
    }
    $user  = wp_get_current_user();
    $roles = (array) $user->roles;

    // Treat WordPress site admins as super admins as well
    return in_array('administrator', $roles, true) || in_array('document_super_admin', $roles, true);
}

/**
 * Check if user is document admin (or WP admin / document super admin)
 */
function doccenter_is_admin_user()
{
    if (! is_user_logged_in()) {
        return false;
    }
    $user  = wp_get_current_user();
    $roles = (array) $user->roles;

    if (doccenter_is_super_admin_user()) {
        return true;
    }

    return in_array('document_admin', $roles, true);
}

