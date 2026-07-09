<?php

if (! defined('ABSPATH')) {
    exit;
}

function doccenter_handle_user_management()
{
    if (! is_user_logged_in() || ! doccenter_is_admin_user()) {
        return;
    }

    if (empty($_POST['doccenter_action'])) {
        return;
    }

    $action = sanitize_text_field($_POST['doccenter_action']);

    if (! in_array($action, [
        'edit_user',
        'delete_user',
        'add_group',
        'delete_group',
        'save_user_groups',
        'assign_group_members',
        'promote_to_admin',
        'make_super_admin',
        'remove_super_admin',
        'create_new_user'
    ], true)) {
        return;
    }

    // For group actions (still allowed from Groups tab)
    if (in_array($action, ['add_group', 'delete_group', 'save_user_groups', 'assign_group_members', 'create_new_user'], true)) {
        if (! isset($_POST['doccenter_dashboard_nonce']) || ! wp_verify_nonce($_POST['doccenter_dashboard_nonce'], 'doccenter_dashboard')) {
            doccenter_add_notice('Security check failed.', 'error');
            return;
        }

        $groups = doccenter_get_groups();

        if ($action === 'add_group') {
            $name = isset($_POST['new_group_name']) ? sanitize_text_field($_POST['new_group_name']) : '';
            $parent = isset($_POST['new_group_parent']) ? sanitize_text_field($_POST['new_group_parent']) : '';
            $return_to = isset($_POST['return_to']) ? esc_url_raw($_POST['return_to']) : '';
            if ($name) {
                doccenter_add_group($name, $parent);
                doccenter_add_notice('Group added.', 'success');
                if ($return_to) {
                    wp_redirect($return_to);
                } else {
                    wp_redirect(site_url('/dashboard?tab=groups'));
                }
                exit;
            }
        } elseif ($action === 'delete_group' && isset($_POST['group_id'])) {
            $gid = sanitize_text_field($_POST['group_id']);
            doccenter_delete_group($gid);
            doccenter_add_notice('Group deleted.', 'success');
            wp_redirect(site_url('/dashboard?tab=groups'));
            exit;
        } elseif ($action === 'save_user_groups' && isset($_POST['user_id'])) {
            $target_user_id = intval($_POST['user_id']);
            $user_group_ids = isset($_POST['user_groups']) ? (array) $_POST['user_groups'] : [];
            doccenter_set_user_groups($target_user_id, $user_group_ids);
            doccenter_add_notice('User groups updated.', 'success');
            wp_safe_redirect(site_url('/dashboard/?tab=users'));
            exit;

            wp_redirect(site_url('/dashboard?tab=users'));
            exit;
        } elseif ($action === 'assign_group_members' && isset($_POST['group_id'])) {

            $group_id        = sanitize_text_field($_POST['group_id']);
            $selected_viewers = isset($_POST['group_viewers']) ? (array) $_POST['group_viewers'] : [];

            $selected_viewers = array_map('intval', $selected_viewers);

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

                    if (in_array((int)$vid, $selected_viewers, true)) {
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
        } // CREATE NEW USER (ADMIN / SUPER ADMIN)
        // CREATE NEW USER (Admin / Super Admin)
        elseif ($action === 'create_new_user') {

            if (
                ! isset($_POST['doccenter_dashboard_nonce']) ||
                ! wp_verify_nonce($_POST['doccenter_dashboard_nonce'], 'doccenter_dashboard')
            ) {
                doccenter_add_notice('Security check failed.', 'error');
                wp_safe_redirect(site_url('/dashboard/?tab=users'));
                exit;
            }

            $is_super = doccenter_is_super_admin_user();
            $is_admin = doccenter_is_admin_user();

            if (! $is_admin) {
                doccenter_add_notice('You do not have permission to create users.', 'error');
                wp_safe_redirect(site_url('/dashboard/?tab=users'));
                exit;
            }

            // --- Gather input ---
            $username = sanitize_user($_POST['dc_new_username'] ?? '');
            $email    = sanitize_email($_POST['dc_new_email'] ?? '');
            $password = sanitize_text_field($_POST['dc_new_password'] ?? '');
            $role     = sanitize_text_field($_POST['dc_new_role'] ?? 'document_viewer');

            // --- Role enforcement ---
            // Admins can only create VIEWERS
            if (! $is_super && $role !== 'document_viewer') {
                $role = 'document_viewer';
            }

            // Super Admin can create ANY role:
            // document_viewer, document_admin, document_super_admin
            if ($is_super) {
                if (! in_array($role, ['document_viewer', 'document_admin', 'document_super_admin'], true)) {
                    $role = 'document_viewer';
                }
            }

            // --- Assign selected groups ---
            $groups = isset($_POST['dc_new_groups']) ? (array) $_POST['dc_new_groups'] : [];
            $groups = array_map('sanitize_text_field', $groups);

            // --- Validate input ---
            if (empty($username) || empty($email) || empty($password)) {
                doccenter_add_notice('Please complete all required fields.', 'error');
                wp_safe_redirect(site_url('/dashboard/?tab=users'));
                exit;
            }

            if (username_exists($username) || email_exists($email)) {
                doccenter_add_notice('Username or email already exists.', 'error');
                wp_safe_redirect(site_url('/dashboard/?tab=users'));
                exit;
            }

            // --- Create the user ---
            $user_id = wp_insert_user([
                'user_login' => $username,
                'user_pass'  => $password,
                'user_email' => $email,
                'role'       => $role, // Now includes document_super_admin
            ]);

            if (is_wp_error($user_id)) {
                doccenter_add_notice('Failed to create user.', 'error');
                wp_safe_redirect(site_url('/dashboard/?tab=users'));
                exit;
            }

            // --- Save groups ---
            doccenter_set_user_groups($user_id, $groups);

            // --- Email credentials ---
            $login_url = wp_login_url();
            $subject   = 'Your Document Center Account';
            $message   = "Hello,\n\nYour account has been created.\n\nUsername: {$username}\nPassword: {$password}\nLogin: {$login_url}\n\nPlease change your password once logged in.\n";

            wp_mail($email, $subject, $message);

            doccenter_add_notice('User created successfully.', 'success');
            wp_safe_redirect(site_url('/dashboard/?tab=users'));
            exit;
        }




        return;
    }

    // For edit/delete user from modal
    if (! isset($_POST['doccenter_user_nonce']) || ! wp_verify_nonce($_POST['doccenter_user_nonce'], 'doccenter_user_manage')) {
        doccenter_add_notice('Security check failed.', 'error');
        return;
    }

    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    if (! $user_id) {
        doccenter_add_notice('Invalid user.', 'error');
        return;
    }

    if ($action === 'edit_user') {
        $user_group_ids = isset($_POST['user_groups']) ? (array) $_POST['user_groups'] : [];
        doccenter_set_user_groups($user_id, $user_group_ids);
        doccenter_add_notice('User groups updated.', 'success');
        wp_redirect(site_url('/dashboard?tab=users'));
        exit;
    } elseif ($action === 'delete_user') {
        require_once ABSPATH . 'wp-admin/includes/user.php';
        $deleted = wp_delete_user($user_id);
        if ($deleted) {
            doccenter_add_notice('User deleted.', 'success');
            wp_redirect(site_url('/dashboard?tab=users'));
            exit;
        } else {
            doccenter_add_notice('Could not delete user.', 'error');
        }
    } elseif ($action === 'promote_to_admin') {
        if (! doccenter_is_super_admin_user()) {
            doccenter_add_notice('Only super admins can promote users.', 'error');
            return;
        }

        $target = get_user_by('ID', $user_id);
        if (! $target) {
            doccenter_add_notice('Invalid user.', 'error');
            return;
        }

        $roles = (array) $target->roles;

        if (! in_array('document_admin', $roles, true)) {
            $target->add_role('document_admin');
        }

        // Optionally remove viewer role to keep things clean
        if (in_array('document_viewer', $roles, true)) {
            $target->remove_role('document_viewer');
        }

        doccenter_add_notice('User promoted to admin.', 'success');
        wp_redirect(site_url('/dashboard?tab=users'));
        exit;
    } elseif ($action === 'make_super_admin') {
        if (! doccenter_is_super_admin_user()) {
            doccenter_add_notice('Only super admins can promote other admins.', 'error');
            return;
        }

        $target = get_user_by('ID', $user_id);
        if (! $target) {
            doccenter_add_notice('Invalid user.', 'error');
            return;
        }

        $roles = (array) $target->roles;

        if (! in_array('document_super_admin', $roles, true)) {
            $target->add_role('document_super_admin');
        }

        doccenter_add_notice('User promoted to super admin.', 'success');
        wp_redirect(site_url('/dashboard?tab=users'));
        exit;
    } elseif ($action === 'remove_super_admin') {
        if (! doccenter_is_super_admin_user()) {
            doccenter_add_notice('Only super admins can change this role.', 'error');
            return;
        }

        $target = get_user_by('ID', $user_id);
        if (! $target) {
            doccenter_add_notice('Invalid user.', 'error');
            return;
        }

        $roles = (array) $target->roles;

        // Do not touch core WP admins here
        if (in_array('document_super_admin', $roles, true) && ! in_array('administrator', $roles, true)) {
            $target->remove_role('document_super_admin');
        }

        doccenter_add_notice('Super admin role updated.', 'success');
        wp_redirect(site_url('/dashboard?tab=users'));
        exit;
    }
}
add_action('init', 'doccenter_handle_user_management');

