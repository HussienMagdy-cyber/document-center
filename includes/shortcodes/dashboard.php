<?php

if (! defined('ABSPATH')) {
    exit;
}

function doccenter_dashboard_shortcode()
{
    if (! is_user_logged_in()) {
        return '<p>You must be logged in to view this page. <a href="' . esc_url(site_url('/sign-in')) . '">Login here</a>.</p>';
    }

$user       = wp_get_current_user();
$user_id    = $user->ID;
$is_admin   = doccenter_is_admin_user();
$groups     = doccenter_get_groups();
$userGroups = doccenter_get_user_groups($user_id);
$active_folder = isset($_GET['folder']) ? sanitize_text_field($_GET['folder']) : null;

    $edit_doc_id   = 0;
    $edit_doc      = null;
    $edit_notes    = '';
    $edit_vis      = 'admins';
    $edit_gids     = [];
    $edit_uids     = [];

    if ($is_admin && isset($_GET['edit_doc'])) {
        $edit_doc_id = intval($_GET['edit_doc']);
        if ($edit_doc_id) {
            $post = get_post($edit_doc_id);
            if ($post && $post->post_type === 'attachment') {
                $edit_doc      = $post;
                $edit_notes    = get_post_meta($edit_doc_id, '_document_notes', true);
                $edit_vis      = get_post_meta($edit_doc_id, '_document_visibility', true);
                $edit_gids     = get_post_meta($edit_doc_id, '_document_groups', true);
                $edit_uids     = get_post_meta($edit_doc_id, '_document_users', true);
                if (! is_array($edit_gids)) {
                    $edit_gids = [];
                }
                if (! is_array($edit_uids)) {
                    $edit_uids = [];
                }
            } else {
                $edit_doc_id = 0;
            }
        }
    }

    $visible_docs = doccenter_get_visible_documents_for_user($user_id, 20);

    $all_viewers = get_users(
        [
            'role'    => 'document_viewer',
            'orderby' => 'display_name',
            'order'   => 'ASC',
        ]
    );

    // Admin requests: only open
    $requests = [];
    if ($is_admin) {
        $requests = get_posts(
            [
                'post_type'      => 'doc_request',
                'post_status'    => 'publish',
                'posts_per_page' => 20,
                'orderby'        => 'date',
                'order'          => 'DESC',
                'meta_query'     => [
                    [
                        'key'   => '_request_status',
                        'value' => 'open',
                    ],
                ],
            ]
        );
    }

    // Viewer requests (for status list)
    $viewer_requests = [];
    if (! $is_admin) {
        $viewer_requests = get_posts(
            [
                'post_type'      => 'doc_request',
                'post_status'    => 'publish',
                'posts_per_page' => 20,
                'orderby'        => 'date',
                'order'          => 'DESC',
                'meta_query'     => [
                    [
                        'key'   => '_request_user',
                        'value' => $user_id,
                    ],
                ],
            ]
        );
    }

    // Handle group add/delete from Groups tab (already processed in doccenter_handle_user_management, but this keeps $groups fresh after POST)
    if ($is_admin && ! empty($_POST['doccenter_action']) && in_array($_POST['doccenter_action'], ['add_group', 'delete_group', 'assign_group_members', 'create_new_user'], true)) {
        $groups = doccenter_get_groups();
    }

    ob_start();
?>
    <style>
        .doccenter-user-promote-btn {
            background: #19b916ff !important;
            color: #fff !important;
            padding: 4px 12px !important;
            border-radius: 16px !important;
            border: none !important;
            font-size: 11px !important;
            cursor: pointer !important;
            font-weight: 500 !important;
            transition: background 0.2s ease, box-shadow 0.2s ease !important;
        }

        .doccenter-user-demote-super-btn {
            background: #ef4444ff !important;
            color: #fff !important;
            padding: 4px 12px !important;
            border-radius: 16px !important;
            border: none !important;
            font-size: 11px !important;
            cursor: pointer !important;
            font-weight: 500 !important;
            transition: background 0.2s ease, box-shadow 0.2s ease !important;
        }

        .doccenter-demote-admin-btn {
            background: #ef4444;
            color: #fff;
            padding: 6px 14px;
            border-radius: 16px !important;
            border: none;
            font-size: 13px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.2s ease, box-shadow 0.2s ease;
        }

        .doccenter-demote-admin-btn:hover {
            background: #dc2626;
            box-shadow: 0 2px 6px rgba(220, 38, 38, 0.3);
        }

        .doccenter-root {
            width: 100%;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .doccenter-shell {
            display: flex;
            align-items: stretch;
            width: 100%;
        }

        /* Sidebar: fixed width, full height, no outer padding */
        .doccenter-sidebar {
            width: 260px;
            padding: 50px 0px;
            background: #ffffff;
            border-right: 1px solid rgba(148, 163, 184, 0.5);
            box-shadow: none;
            border-radius: 0;
            position: sticky;
            top: 0;
            align-self: flex-start;
            height: 100vh;
            box-sizing: border-box;

        }

        /* Main content: full width area, no "boxed" card */
        .doccenter-main {
            flex: 1;
            margin-left: 0;
            background: #f9fafb;
            border-radius: 0;
            padding: 24px 24px 32px;
            border: none;
            box-shadow: none;
            box-sizing: border-box;
        }

        @media (max-width: 900px) {
            .doccenter-shell {
                flex-direction: column;
                min-height: auto !important;
            }

            .doccenter-sidebar {
                display: none !important;
                position: relative !important;
                left: 0 !important;
                width: 100% !important;
                max-height: none !important;
                height: auto !important;
                border-right: none !important;
                border-bottom: 1px solid rgba(148, 163, 184, 0.4) !important;
                padding: 12px !important;
                margin-bottom: 0 !important;
                box-shadow: 0 4px 14px rgba(15, 23, 42, 0.08) !important;
            }

            .doccenter-main {
                padding: 16px 12px 24px;
            }
        }

        .doccenter-logo {
            padding: 8px 0 4px 0;
            text-align: center !important;
        }

        .doccenter-logo img {
            max-width: 165px;
            height: auto;
            display: block;
        }

        .doccenter-brand {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .doccenter-brand-title {
            font-size: 18px;
            font-weight: 600;
            color: #111827;
        }

        .doccenter-nav {
            margin-top: 6px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .doccenter-nav-item {
            padding: 9px 10px;
            border-radius: 10px;
            font-size: 13px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            color: #4b5563;
            border: 1px solid transparent;
        }

        .doccenter-nav-item span {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .doccenter-nav-item-active {
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.1), rgba(129, 140, 248, 0.1));
            border-color: rgba(139, 92, 246, 0.3);
            color: #4c1d95;
        }

        .doccenter-nav-pill {
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 999px;
            background: rgba(139, 92, 246, 0.12);
            color: #4c1d95;
        }

        .doccenter-main-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 14px;
        }

        .doccenter-main-title {
            font-size: 18px;
            font-weight: 600;
            color: #111827;
        }

        .doccenter-main-subtitle {
            font-size: 13px;
            color: #6b7280;
        }

        .doccenter-user-chip {
            font-size: 12px;
            padding: 5px 9px;
            border-radius: 999px;
            background: #fff;
            border: 1px solid #e5e7eb;
            color: #374151;
        }

        .doccenter-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 16px 16px 18px;
            border: 1px solid rgba(209, 213, 219, 0.8);
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05);
            margin-bottom: 14px;
        }

        .doccenter-card h3 {
            margin-top: 0;
            margin-bottom: 4px;
            font-size: 15px;
            font-weight: 600;
            color: #111827;
        }

        .doccenter-card-sub {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 10px;
        }

        .doccenter-fields-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px 14px;
        }

        @media (max-width: 700px) {
            .doccenter-fields-grid {
                grid-template-columns: 1fr;
            }
        }

        .doccenter-field {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .doccenter-field label {
            font-size: 12px;
            font-weight: 500;
            color: #374151;
        }

        .doccenter-field input[type="text"],
        .doccenter-field input[type="email"],
        .doccenter-field input[type="password"],
        .doccenter-field select,
        .doccenter-field textarea {
            border-radius: 10px;
            border: 1px solid #d1d5db;
            padding: 8px 9px;
            font-size: 13px;
            width: 100%;
            box-sizing: border-box;
        }

        .doccenter-field textarea {
            min-height: 80px;
            resize: vertical;
        }

        .doccenter-field input:focus,
        .doccenter-field select:focus,
        .doccenter-field textarea:focus {
            outline: none;
            border-color: #8b5cf6;
            box-shadow: 0 0 0 1px rgba(139, 92, 246, 0.35);
            background: #f9fafb;
        }

        .doccenter-checkbox-group {
            display: flex;
            flex-direction: column;
            gap: 4px;
            max-height: 160px;
            overflow-y: auto;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            padding: 6px 8px;
            background: #f9fafb;
        }

        .doccenter-checkbox-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: #374151;
        }

        .doccenter-checkbox-item input[type="checkbox"] {
            margin: 0;
        }

        .doccenter-btn-primary {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 8px 16px !important;
            font-size: 13px !important;
            font-weight: 500 !important;
            border-radius: 999px !important;
            border: none !important;
            cursor: pointer !important;
            background: linear-gradient(135deg, #8b5cf6, #6366f1) !important;
            color: #fff !important;
            box-shadow: 0 10px 24px rgba(99, 102, 241, 0.35) !important;
        }

        .doccenter-btn-secondary {
            font-size: 12px !important;
            padding: 6px 12px !important;
            border-radius: 999px !important;
            border: 1px solid #d1d5db !important;
            background: #fff !important;
            cursor: pointer !important;
            color: #374151 !important;
        }

        .doccenter-btn-danger {
            font-size: 12px !important;
            padding: 6px 12px !important;
            border-radius: 999px !important;
            border: 1px solid #fecaca !important;
            background: #e33333ff !important;
            cursor: pointer;
            color: #ffffffff !important;
        }

        .doccenter-doc-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .doccenter-doc-item {
            padding: 8px 10px;
            border-radius: 12px;
            background: #f9fafb;
            border: 1px solid transparent;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
        }

        .doccenter-doc-item:hover {
            background: #eef2ff;
            border-color: #c7d2fe;
        }

        .doccenter-doc-title {
            font-size: 13px;
            font-weight: 500;
        }

        .doccenter-doc-meta {
            color: #6b7280;
            margin-top: 2px;
        }

        .doccenter-doc-actions {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 4px;
        }

        .doccenter-doc-actions a,
        .doccenter-doc-actions button {
            font-size: 11px;
            padding: 4px 8px;
            border-radius: 999px;
            border: 1px solid #e5e7eb;
            background: #fff;
            text-decoration: none;
            color: #374151;
            cursor: pointer;
        }

        /* Hover effect */
        .doccenter-doc-actions a:hover,
        .doccenter-doc-actions button:hover {
            background: #3371d4ff;
            /* Hover background */
            color: #ffffff;
            /* White text on hover */
            border-color: #e2e2e2ff;
            /* Match border on hover */
            box-shadow: 0 2px 6px rgba(55, 65, 81, 0.25);
        }

        .doccenter-alert.doccenter-alert-success {
    background: #ecfdf3 !important;
    border: 1px solid #bbf7d0 !important;
    color: #166534 !important;
}

.doccenter-alert.doccenter-alert-error {
    background: #fef2f2 !important;
    border: 1px solid #fecaca !important;
    color: #b91c1c !important;
}

        .doccenter-alert {
            margin-bottom: 10px;
            padding: 8px 10px;
            border-radius: 10px;
            font-size: 12px;
        }

        .doccenter-alert-success {
            background: #ecfdf3;
            border: 1px solid #bbf7d0;
            color: #166534;
        }

        .doccenter-alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
        }

        .doccenter-section {
            display: none;
        }

        .doccenter-section-active {
            display: block;
        }

        /* View toggles for list / grid */
        .doccenter-docview-toggle {
            display: inline-flex;
            border-radius: 999px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
        }

        .doccenter-docview-toggle button {
            border: none;
            background: transparent;
            font-size: 11px;
            padding: 4px 10px;
            cursor: pointer;
            color: #4b5563;
        }

        .doccenter-docview-toggle button.doccenter-docview-active {
            background: #eef2ff;
            color: #4f46e5;
        }

        /* Grid mode */
        .doccenter-doc-list-grid {
            display: grid !important;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 16px;
        }

        .doccenter-doc-list-grid .doccenter-doc-item {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 14px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
            min-height: 150px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.05);
            transition: 0.15s ease-in-out;
        }

        .doccenter-doc-list-grid .doccenter-doc-item:hover {
            background: #f9fafb;
            transform: translateY(-2px);
        }

        .doccenter-doc-list-grid .doccenter-doc-actions {
            margin-top: auto;
        }

        /* SLIDE-OVER EDIT PANEL */
        .doccenter-edit-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.40);
            display: none;
            align-items: stretch;
            justify-content: flex-end;
            z-index: 1200;
        }

        .doccenter-edit-overlay-active {
            display: flex;
        }

        .doccenter-edit-panel {
            width: 420px;
            max-width: 100%;
            height: 100%;
            background: #ffffff;
            box-shadow: -10px 0 35px rgba(15, 23, 42, 0.4);
            padding: 18px 18px 20px;
            box-sizing: border-box;
            border-left: 1px solid rgba(209, 213, 219, 0.8);
            position: relative;
        }

        .doccenter-edit-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .doccenter-edit-title {
            font-size: 15px;
            font-weight: 600;
            color: #111827;
        }

        .doccenter-edit-close {
            border: none;
            background: transparent;
            font-size: 18px;
            cursor: pointer;
            color: #6b7280;
        }

        .doccenter-edit-body {
            height: calc(100% - 70px);
            overflow: auto;
            padding-right: 4px;
        }

        /* VIEW MODAL (DETAILS) */
        .doccenter-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.45);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1300;
        }

        .doccenter-modal-active {
            display: flex;
        }

        .doccenter-modal {
            background: #ffffff;
            max-width: 480px;
            width: 95%;
            border-radius: 16px;
            padding: 16px 18px 18px;
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.5);
            box-sizing: border-box;
        }

        .doccenter-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .doccenter-modal-title {
            font-size: 15px;
            font-weight: 600;
            color: #111827;
        }

        .doccenter-modal-close {
            border: none;
            background: transparent;
            font-size: 18px;
            cursor: pointer;
            color: #6b7280;
        }

        .doccenter-modal-meta {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 8px;
        }

        .doccenter-modal-notes {
            font-size: 13px;
            color: #374151;
            margin-bottom: 10px;
        }

        /* USER EDIT MODAL */
        .doccenter-user-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.40);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1250;
        }

        .doccenter-user-overlay-active {
            display: flex;
        }

        .doccenter-user-modal {
            background: #ffffff;
            max-width: 420px;
            width: 95%;
            border-radius: 16px;
            padding: 16px 18px 18px;
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.5);
            box-sizing: border-box;
        }

        .doccenter-user-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .doccenter-user-modal-title {
            font-size: 15px;
            font-weight: 600;
            color: #111827;
        }

        .doccenter-user-modal-close {
            border: none;
            background: transparent;
            font-size: 18px;
            cursor: pointer;
            color: #6b7280;
        }
    </style>

    <!-- LAYOUT OVERRIDES: sticky sidebar + fullwidth main -->
    <style>
        .doccenter-folder-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
            gap: 16px;
            margin-top: 10px;
        }

        .doccenter-folder {
            display: block;
            padding: 14px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            background: #f9fafb;
            text-decoration: none;
            color: #374151;
            transition: 0.25s;
        }

        .doccenter-folder:hover {
            background: #eef2ff;
            border-color: #c7d2fe;
        }

        .doccenter-folder-icon {
            font-size: 32px;
            margin-bottom: 6px;
        }

        .doccenter-back-btn {
            display: inline-block;
            margin-bottom: 10px;
            font-size: 13px;
            color: #2563eb;
            text-decoration: none;
            text-align: center;
        }

        .doccenter-breadcrumb {
            margin-bottom: 10px;
            font-size: 13px;
            align-items: center;
        }

        .doccenter-breadcrumb a {
            color: #0000e2;
            text-decoration: none;
            align-self: center;
        }

        .doccenter-breadcrumb a:hover {
            text-decoration: underline;
        }

        .doccenter-breadcrumb-separator {
            color: #9ca3af;
            margin: 0 4px;
        }

        .doccenter-subfolder-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 12px;
            margin-bottom: 20px;
            padding: 16px;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .doccenter-subfolder {
            padding: 12px !important;
        }

        .doccenter-subfolder .doccenter-folder-icon {
            font-size: 28px;
        }

        .doccenter-subfolder .doccenter-folder-name {
            font-size: 13px;
        }


        .doccenter-notice {
            margin: 0 0 18px 0 !important;
            padding: 12px 16px !important;
            border-radius: 8px !important;
            font-size: 14px !important;
            font-weight: 500 !important;
            border-width: 1px !important;
            border-style: solid !important;
            border-color: transparent !important;
            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.06) !important;
        }

        /* Success */
        .doccenter-notice-success {
            background-color: #ecfdf5 !important;
            border-color: #a7f3d0 !important;
            color: #065f46 !important;
        }

        /* Error */
        .doccenter-notice-error {
            background-color: #fef2f2 !important;
            border-color: #fecaca !important;
            color: #991b1b !important;
        }

        .doccenter-root {
            width: 100%;
            padding: 0;
            margin: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            position: relative;
            background: #f9fafb;
        }

        @media (max-width: 900px) {
            .doccenter-root {
                padding: 0;
            }
        }

        .doccenter-shell {
            display: flex;
            align-items: stretch;
            min-height: 100vh;
        }

        @media (max-width: 900px) {
            .doccenter-shell {
                flex-direction: column;
                min-height: auto;
            }
        }

        .doccenter-sidebar {
            position: sticky;
            top: 0;
            left: 0;
            width: 260px;
            padding: 0 16px 16px 0;
            background: #ffffff;
            border-right: 1px solid rgba(148, 163, 184, 0.5);
            box-shadow: none;
            border-radius: 0;
            box-sizing: border-box;
            max-height: 100vh;
            overflow-y: auto;
        }

        @media (max-width: 900px) {
            .doccenter-sidebar {
                position: relative;
                width: 100%;
                max-height: none;
                border-right: none;
                border-radius: 0;
                padding: 12px;
                margin-bottom: 0;
                box-shadow: 0 4px 14px rgba(15, 23, 42, 0.08);
            }
        }

        .doccenter-main {
            flex: 1;
            margin: 0;
            padding: 24px 24px 32px;
            background: #f9fafb;
            border-radius: 0;
            border: none;
            box-shadow: none;
            box-sizing: border-box;
        }

        @media (max-width: 900px) {
            .doccenter-main {
                padding: 16px 12px 24px;
            }
        }

        .doccenter-logo {
            padding: 12px 0 6px 0;
        }

        /* Mobile toggle button */
        .doccenter-mobile-toggle {
            display: none;
            position: fixed;
            top: 14px;
            left: 14px;
            z-index: 3001;
            border: none;
            background: white;
            padding: 8px 10px;
            border-radius: 6px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
            cursor: pointer;
        }

        /* Overlay */
        .doccenter-sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.45);
            z-index: 3000;
        }

        /* Sidebar default (desktop) */
        .doccenter-sidebar {
            width: 260px;
            min-height: 100vh;
            background: #fff;
            border-right: 1px solid #e5e7eb;
            padding: 20px;
        }

        /* Mobile */
        @media (max-width: 900px) {
            .doccenter-mobile-toggle {
                display: block;
            }

            .doccenter-sidebar {
                position: fixed;
                top: 0;
                left: -280px;
                width: 260px;
                height: 100%;
                background: #fff;
                z-index: 3001;
                box-shadow: 2px 0 10px rgba(0, 0, 0, 0.15);
                transition: left 0.3s ease;
            }

            .doccenter-sidebar.active {
                left: 0;
            }

            .doccenter-sidebar-overlay.active {
                display: block;
            }
        }
    </style>

    <div class="doccenter-root" id="doccenter-root">
        <?php
        $custom_logo_id = get_theme_mod('custom_logo');
        $site_logo_url  = wp_get_attachment_image_url($custom_logo_id, 'full');
        ?>

        <div class="doccenter-shell">

            <aside class="doccenter-sidebar" id="dc-sidebar">
                <div class="doccenter-logo">
                    <img src="<?php echo esc_url($site_logo_url); ?>" alt="Document Center Logo">
                </div>
                <div class="doccenter-brand">
                    <div>
                        <div class="doccenter-brand-title">Document Center</div>
                        <div style="font-size:11px;color:#6b7280;">
                            <?php echo $is_admin ? 'Admin Dashboard' : 'Viewer Dashboard'; ?>
                        </div>
                    </div>
                </div>

                <nav class="doccenter-nav" id="doccenter-nav">
                    <div class="doccenter-nav-item doccenter-nav-item-active" data-tab="overview">
                        <span>Overview</span>
                    </div>

                    <?php if ($is_admin) : ?>
                        <div class="doccenter-nav-item" data-tab="upload">
                            <span>Upload documents</span>
                        </div>
                        <div class="doccenter-nav-item" data-tab="documents">
                            <span>All documents</span>
                        </div>
                        <div class="doccenter-nav-item" data-tab="groups">
                            <span>Viewer groups</span>
                        </div>
                        <div class="doccenter-nav-item" data-tab="users">
                            <span>Manage users</span>
                        </div>
                        <div class="doccenter-nav-item" data-tab="requests">
                            <span>Requests</span>
                            <?php if (! empty($requests)) : ?>
                                <span class="doccenter-nav-pill"><?php echo esc_html(count($requests)); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php else : ?>
                        <div class="doccenter-nav-item" data-tab="documents">
                            <span>Shared with me</span>
                        </div>
                        <div class="doccenter-nav-item" data-tab="requests">
                            <span>Requests</span>
                        </div>
                    <?php endif; ?>

                    <div class="doccenter-nav-item" data-tab="profile">
                        <span>Profile</span>
                    </div>
                    <div class="doccenter-nav-item">
                        <span>
                            <a href="<?php echo esc_url(wp_logout_url(site_url('/sign-in'))); ?>" style="text-decoration:none;color:inherit;">Logout</a>
                        </span>
                    </div>
                </nav>
            </aside>

            <main class="doccenter-main">
                <div class="doccenter-main-header">
                    <div style="flex:1;">
                        <div class="doccenter-main-title">Welcome back, <?php echo esc_html($user->display_name); ?></div>
                        <div class="doccenter-main-subtitle">Quick access to documents and sharing controls.</div>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <!-- SEARCH BAR -->
                        <form method="get" action="" style="display:flex;gap:8px;align-items:center;">
                            <input type="hidden" name="tab" value="documents">
                            <?php if ($active_folder) : ?>
                                <input type="hidden" name="folder" value="<?php echo esc_attr($active_folder); ?>">
                            <?php endif; ?>
                            <div style="position:relative;">
                                <input 
                                    type="text" 
                                    name="doc_search" 
                                    id="doccenter_search_input"
                                    placeholder="Search files or tags..." 
                                    value="<?php echo esc_attr(isset($_GET['doc_search']) ? sanitize_text_field($_GET['doc_search']) : ''); ?>"
                                    style="padding:8px 12px 8px 32px;border-radius:10px;border:1px solid #d1d5db;font-size:13px;width:220px;box-sizing:border-box;outline:none;"
                                    onfocus="this.style.borderColor='#8b5cf6';this.style.boxShadow='0 0 0 3px rgba(139,92,246,0.15)';"
                                    onblur="this.style.borderColor='#d1d5db';this.style.boxShadow='none';"
                                >
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);pointer-events:none;">
                                    <circle cx="11" cy="11" r="8"/>
                                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                                </svg>
                            </div>
                            <button type="submit" class="doccenter-btn-primary" style="padding:8px 14px;font-size:12px;">Search</button>
                            <?php if (!empty($_GET['doc_search'])) : ?>
                                <a href="<?php echo esc_url(remove_query_arg('doc_search')); ?>" class="doccenter-btn-secondary" style="font-size:12px;text-decoration:none;">Clear</a>
                            <?php endif; ?>
                        </form>
                        <div class="doccenter-user-chip">
                            <?php
                            if (doccenter_is_super_admin_user()) {
                                echo 'Super admin';
                            } elseif ($is_admin) {
                                echo 'Admin';
                            } else {
                                echo 'Viewer';
                            }
                            ?>
                        </div>
                    </div>
                </div>

                <?php doccenter_render_notices(); ?>

                <!-- OVERVIEW -->
                <section class="doccenter-section doccenter-section-active" data-tab="overview">
                    <div class="doccenter-card">
                        <h3>Recent documents</h3>
                        <div class="doccenter-card-sub">Documents you have access to.</div>
                        <?php if ($visible_docs->have_posts()) : ?>
                            <div class="doccenter-doc-list">
                                <?php
                                while ($visible_docs->have_posts()) :
                                    $visible_docs->the_post();
                                    $id            = get_the_ID();
                                    $url           = wp_get_attachment_url($id);
                                    $title         = get_the_title();
                                    $modified_date = doccenter_get_document_modified_date($id);
                                    $expiry_date   = doccenter_get_document_expiry_date($id);
                                    $category      = get_post_meta($id, '_document_category', true);
                                    $vis           = get_post_meta($id, '_document_visibility', true);
                                    $notes         = get_post_meta($id, '_document_notes', true);
                                    $tags_str      = get_post_meta($id, '_document_tags', true);
                                    $uploaded_by_id = get_post_meta($id, '_document_uploaded_by', true);
                                    if (empty($uploaded_by_id)) {
                                        $uploaded_by_id = get_post_field('post_author', $id);
                                    }
                                    $uploaded_by_username = 'Unknown';
                                    if (! empty($uploaded_by_id)) {
                                        $user_data = get_userdata($uploaded_by_id);
                                        if ($user_data) $uploaded_by_username = $user_data->user_login;
                                    }
                                ?>
                                    <?php
                                        $doc_ext = strtolower(pathinfo($url, PATHINFO_EXTENSION));
                                        $doc_type = doccenter_get_file_type($doc_ext);
                                        $doc_icon = doccenter_get_file_icon_svg($doc_ext);
                                        $is_previewable = doccenter_is_media_previewable($doc_ext);
                                    ?>
                                    <div class="doccenter-doc-item">
                                        <div style="display:flex;align-items:center;gap:10px;">
                                            <div style="flex-shrink:0;width:40px;height:40px;display:flex;align-items:center;justify-content:center;background:#f3f4f6;border-radius:10px;">
                                                <?php echo $doc_icon; ?>
                                            </div>
                                            <div>
                                                <div class="doccenter-doc-title"><?php echo esc_html($title); ?></div>
                                                <div class="doccenter-doc-meta">
                                                    <?php echo esc_html('Modified ' . $modified_date . ' · Expires ' . $expiry_date); ?>
                                                    <?php echo $category ? ' · ' . esc_html($category) : ''; ?>
                                                    <?php echo $vis ? ' ' . esc_html(ucfirst($vis)) : ''; ?>
                                                    <?php echo ' · Uploaded by ' . esc_html($uploaded_by_username); ?>
                                                    <?php $unique_tag = get_post_meta($id, '_document_unique_tag', true); ?>
                                                    <?php echo $unique_tag ? ' · ' . esc_html($unique_tag) : ''; ?>
                                                    <?php echo ' · ' . esc_html(strtoupper($doc_ext)); ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="doccenter-doc-actions">
                                            <?php if ($is_previewable) : ?>
                                                <button type="button"
                                                    class="doccenter-doc-preview"
                                                    data-title="<?php echo esc_attr($title); ?>"
                                                    data-url="<?php echo esc_url($url); ?>"
                                                    data-type="<?php echo esc_attr($doc_type); ?>"
                                                    data-ext="<?php echo esc_attr($doc_ext); ?>">
                                                    Preview
                                                </button>
                                            <?php endif; ?>
                                            <button type="button"
                                                class="doccenter-doc-details"
                                                data-title="<?php echo esc_attr($title); ?>"
                                                data-date="<?php echo esc_attr($modified_date); ?>"
                                                data-expiry="<?php echo esc_attr($expiry_date); ?>"
                                                data-category="<?php echo esc_attr($category); ?>"
                                                data-tags="<?php echo esc_attr($tags_str); ?>"
                                                data-uploaded-by="<?php echo esc_attr($uploaded_by_username); ?>"
                                                data-notes="<?php echo esc_attr(wp_trim_words(wp_strip_all_tags($notes), 80)); ?>"
                                                data-url="<?php echo esc_url($url); ?>">
                                                Details
                                            </button>
                                            <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener">View</a>
                                            <a href="<?php echo esc_url($url); ?>" download>Download</a>
                                            <?php if ($is_admin) : ?>
                                                <button type="button"
                                                    class="doccenter-doc-edit"
                                                    data-id="<?php echo esc_attr($id); ?>">
                                                    Edit
                                                </button>
                                                                                                 <form method="post" style="display:inline;" onsubmit="return confirm('Delete this document? This cannot be undone.');">
                                                    <?php wp_nonce_field('doccenter_delete_document', 'doccenter_delete_doc_nonce'); ?>
                                                    <input type="hidden" name="doc_id" value="<?php echo esc_attr($id); ?>">
                                                    <input type="hidden" name="doccenter_action" value="delete_document">
                                                    <input type="hidden" name="current_tab" value="overview">
                                                    <?php if (!empty($active_folder)) : ?>
                                                        <input type="hidden" name="folder" value="<?php echo esc_attr($active_folder); ?>">
                                                    <?php endif; ?>
                                                    <button type="submit" class="doccenter-btn-danger">Delete</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php
                                endwhile;
                                wp_reset_postdata();
                                ?>
                            </div>
                        <?php else : ?>
                            <div style="font-size:13px;color:#9ca3af;">No documents available yet.</div>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- UPLOAD (ADMIN ONLY) -->
                <?php if ($is_admin) : ?>
                    <section class="doccenter-section" data-tab="upload">

                        <div class="doccenter-card">
                            <h3>Upload document</h3>
                            <div class="doccenter-card-sub">Add a file, set metadata, and choose who can see it.</div>

                            <form method="post" enctype="multipart/form-data">
                                <?php if (! empty($active_folder) && $active_folder !== 'all' && $active_folder !== 'direct') : ?>
                                    <input type="hidden" name="folder" value="<?php echo esc_attr($active_folder); ?>">
                                <?php endif; ?>
                                <div class="doccenter-field" style="margin-bottom:10px;">
    <label>Files</label>
    <div id="doccenter-drop-zone" style="
        border: 2px dashed #c4b5fd;
        border-radius: 14px;
        padding: 32px 20px;
        text-align: center;
        background: #faf5ff;
        cursor: pointer;
        transition: background 0.2s ease, border-color 0.2s ease;
        position: relative;
    ">
        <input type="file" id="doccenter_file" name="doccenter_files[]" multiple required
            style="position:absolute;inset:0;opacity:0;width:100%;height:100%;cursor:pointer;">
        <div style="pointer-events:none;">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#8b5cf6" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin:0 auto 10px;display:block;">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                <polyline points="17 8 12 3 7 8"/>
                <line x1="12" y1="3" x2="12" y2="15"/>
            </svg>
            <div id="doccenter-drop-label" style="font-size:14px;font-weight:500;color:#6d28d9;">Drag &amp; drop files here</div>
            <div style="font-size:12px;color:#9ca3af;margin-top:4px;">or click to browse &nbsp;·&nbsp; PDF, DOCX, XLSX, PPTX, TXT, Images, Video, Audio &nbsp;·&nbsp; Max 250MB each</div>
        </div>
    </div>
</div>
<div id="doccenter_file_metadata" style="margin-bottom:12px;"></div>

                                <div class="doccenter-fields-grid">
                                    <div class="doccenter-field">
                                        <label for="doccenter_visibility">Visibility</label>
                                        <select id="doccenter_visibility" name="doccenter_visibility" style="margin-top:4px; width:100%; ">
                                            <option value="admins">Admins only</option>
                                            <option value="everyone">Everyone</option>
                                            <option value="custom">Specific groups / users</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="doccenter-fields-grid" style="margin-top:18px;">
                                    <div class="doccenter-field">
                                        <label>Share with groups</label>
                                        <div class="doccenter-checkbox-group" id="doccenter_groups_box">
                                            <?php
                                            $group_hierarchy = doccenter_get_group_hierarchy();
                                            if (! empty($group_hierarchy)) : 
                                            ?>
                                                <?php foreach ($group_hierarchy as $g) : 
                                                    $level = isset($g['level']) ? $g['level'] : 0;
                                                    $indent_style = $level > 0 ? 'margin-left: ' . ($level * 15) . 'px;' : '';
                                                ?>
                                                    <label class="doccenter-checkbox-item" style="<?php echo esc_attr($indent_style); ?>">
                                                        <input type="checkbox" name="doccenter_groups[]" value="<?php echo esc_attr($g['id']); ?>">
                                                        <span><?php echo esc_html($g['name']); ?></span>
                                                    </label>
                                                <?php endforeach; ?>
                                            <?php else : ?>
                                                <div style="font-size:11px;color:#9ca3af;">No groups yet.</div>
                                            <?php endif; ?>
                                        </div>
                                        <div style="font-size:11px;color:#9ca3af;margin-top:4px;">You can leave this empty and only use specific users.</div>
                                    </div>
                                    <div class="doccenter-field">
                                        <label>Share with specific users</label>
                                        <div class="doccenter-checkbox-group" id="doccenter_users_box">
                                            <?php if (! empty($all_viewers)) : ?>
                                                <?php foreach ($all_viewers as $vu) : ?>
                                                    <label class="doccenter-checkbox-item">
                                                        <input type="checkbox" name="doccenter_users[]" value="<?php echo esc_attr($vu->ID); ?>">
                                                        <span><?php echo esc_html($vu->display_name . ' (' . $vu->user_login . ')'); ?></span>
                                                    </label>
                                                <?php endforeach; ?>
                                            <?php else : ?>
                                                <div style="font-size:11px;color:#9ca3af;">No viewer accounts yet.</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="doccenter-field" style="margin-top:10px;">
                                    <label for="doccenter_notes">Description / Notes</label>
                                    <textarea id="doccenter_notes" name="doccenter_notes"></textarea>
                                </div>

                                <?php wp_nonce_field('doccenter_upload', 'doccenter_upload_nonce'); ?>
                                <input type="hidden" name="doccenter_action" value="upload_document">

                                <div style="margin-top:12px;text-align:right;">
                                    <button type="submit" class="doccenter-btn-primary">Upload</button>
                                </div>
                            </form>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- DOCUMENTS LIST -->
                <!-- DOCUMENTS LIST (FOLDER MODE) -->
                <!-- DOCUMENTS LIST -->
                <section class="doccenter-section" data-tab="documents">

                    <div class="doccenter-card">

                        <?php
                        $viewer_groups = doccenter_get_user_groups($user_id);
                        $active_folder = isset($_GET['folder']) ? sanitize_text_field($_GET['folder']) : null;
                        $search_query = isset($_GET['doc_search']) ? sanitize_text_field($_GET['doc_search']) : '';
                        $search_query_lower = strtolower($search_query);

                        if ($is_admin) {
                            // Admins can see all groups
                            $admin_groups = $groups;
                        }
                        ?>

                        <?php if (!$active_folder) : ?>

                            <!-- ===================== -->
                            <!--   FOLDER LIST VIEW    -->
                            <!-- ===================== -->

                            <h3>Documents</h3>
                            <div class="doccenter-card-sub">
                                <?php 
                                if (!empty($search_query)) {
                                    echo 'Search results for: <strong>' . esc_html($search_query) . '</strong>';
                                } else {
                                    echo $is_admin ? 'Browse documents by group or view everything.' : 'Select a folder to view its shared documents.';
                                }
                                ?>
                            </div>

                            <?php 
                            // Search results: show matched documents at root level
                            if (!empty($search_query)) {
                                $matched_doc_ids = doccenter_search_documents($user_id, $search_query);
                                if (!empty($matched_doc_ids)) : ?>
                                    <div style="margin-bottom: 20px;">
                                        <h4 style="font-size: 14px; color: #6b7280; margin-bottom: 10px;">📄 Matched Documents</h4>
                                        <div class="doccenter-doc-list">
                                            <?php foreach ($matched_doc_ids as $doc_id) :
                                                $url           = wp_get_attachment_url($doc_id);
                                                $title         = get_the_title($doc_id);
                                                $modified_date = doccenter_get_document_modified_date($doc_id);
                                                $expiry_date   = doccenter_get_document_expiry_date($doc_id);
                                                $unique_tag    = get_post_meta($doc_id, '_document_unique_tag', true);
                                                $uploaded_by_id = get_post_meta($doc_id, '_document_uploaded_by', true);
                                                if (empty($uploaded_by_id)) {
                                                    $uploaded_by_id = get_post_field('post_author', $doc_id);
                                                }
                                                $uploaded_by_username = 'Unknown';
                                                if (!empty($uploaded_by_id)) {
                                                    $user_data = get_userdata($uploaded_by_id);
                                                    if ($user_data) $uploaded_by_username = $user_data->user_login;
                                                }
                                            ?>
                                                <div class="doccenter-doc-item">
                                                    <div>
                                                        <div class="doccenter-doc-title"><?php echo esc_html($title); ?></div>
                                                        <div class="doccenter-doc-meta">
                                                            <?php echo esc_html('Modified ' . $modified_date . ' · Expires ' . $expiry_date); ?> • Uploaded by <?php echo esc_html($uploaded_by_username); ?>
                                                            <?php echo $unique_tag ? ' • Tag: ' . esc_html($unique_tag) : ''; ?>
                                                        </div>
                                                    </div>
                                                    <div class="doccenter-doc-actions">
                                                        <a href="<?php echo esc_url($url); ?>" target="_blank">View</a>
                                                        <a href="<?php echo esc_url($url); ?>" download>Download</a>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php else : ?>
                                    <div style="font-size: 13px; color: #9ca3af; margin-bottom: 16px;">No documents found matching "<?php echo esc_html($search_query); ?>".</div>
                                <?php endif;
                            }
                            ?>

                            <div class="doccenter-folder-list">

                                <!-- Admin: All Documents Folder -->
                                <?php if ($is_admin) : ?>
                                    <?php 
                                    $show_all = empty($search_query) || stripos('all documents', $search_query_lower) !== false;
                                    if ($show_all) : 
                                    ?>
                                        <a class="doccenter-folder" href="?tab=documents&folder=all">
                                            <div class="doccenter-folder-icon">📁</div>
                                            <div class="doccenter-folder-name">All Documents</div>
                                            <div class="doccenter-folder-meta">
                                                <?php echo wp_count_attachments()->inherit ?? 0; ?> items
                                            </div>
                                        </a>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <!-- Group folders -->
                                <?php
                                $folders_source = $is_admin ? $admin_groups : $groups;
                                $folder_hierarchy = doccenter_get_group_hierarchy();

                                if (!empty($folder_hierarchy)) :
                                    foreach ($folder_hierarchy as $g) :
                                        $group_id = $g['id'];
                                        $level = isset($g['level']) ? $g['level'] : 0;

                                        // Only show top-level folders in the root list
                                        if ($level > 0) {
                                            continue;
                                        }

                                        if (!$is_admin && !in_array($group_id, $viewer_groups, true)) {
                                            continue; // viewers: only show assigned groups
                                        }

                                        // SEARCH: Filter folders by name
                                        if (!empty($search_query) && stripos($g['name'], $search_query_lower) === false) {
                                            continue;
                                        }

                                        $count_docs = doccenter_count_group_documents($group_id, $user_id);
                                        $indent_style = '';
                                ?>
                                        <a class="doccenter-folder" href="?tab=documents&folder=<?php echo esc_attr($group_id); ?>" style="<?php echo esc_attr($indent_style); ?>">
                                            <div class="doccenter-folder-icon">
                                                <svg width="36" height="36" viewBox="0 0 24 24" fill="#6C63FF">
                                                    <path d="M10 4H4C2.9 4 2 4.9 2 6V18C2 19.1 2.9 20 4 20H20C21.1 20 22 19.1 22 18V8C22 6.9 21.1 6 20 6H12L10 4Z" />
                                                </svg>
                                            </div>

                                            <div class="doccenter-folder-name"><?php echo esc_html($g['name']); ?></div>
                                            <div class="doccenter-folder-meta"><?php echo $count_docs; ?> items</div>
                                        </a>
                                <?php
                                    endforeach;
                                endif;
                                ?>

                                <!-- Direct shares folder -->
                                <a class="doccenter-folder" href="?tab=documents&folder=direct">
                                    <div class="doccenter-folder-icon">
                                        <svg width="36" height="36" viewBox="0 0 24 24" fill="#6C63FF">
                                            <path d="M10 4H4C2.9 4 2 4.9 2 6V18C2 19.1 2.9 20 4 20H20C21.1 20 22 19.1 22 18V8C22 6.9 21.1 6 20 6H12L10 4Z" />
                                        </svg>
                                    </div>

                                    <div class="doccenter-folder-name">Shared With You</div>
                                    <div class="doccenter-folder-meta">
                                        <?php echo doccenter_count_direct_documents($user_id); ?> items
                                    </div>
                                </a>

                            </div>

                                                   <?php else : ?>
 
                            <!-- ======================= -->
                            <!--  INSIDE A FOLDER VIEW   -->
                            <!-- ======================= -->

                            <?php
                            $breadcrumb = [];
                            $folder_name = ''; // <-- FIX: initialize to prevent undefined variable

                            if ($active_folder === 'all' && $is_admin) {
                                $folder_name = 'All Documents';
                            } elseif ($active_folder === 'direct') {
                                $folder_name = 'Direct Shares';
                            } else {
                                $path = doccenter_get_group_path($active_folder);
                                foreach ($path as $path_item) {
                                    $breadcrumb[] = '<a href="?tab=documents&folder=' . esc_attr($path_item['id']) . '">' . esc_html($path_item['name']) . '</a>';
                                }
                                foreach ($groups as $g) {
                                    if ($g['id'] == $active_folder) {
                                        $folder_name = $is_admin ? $g['name'] . " (Group)" : $g['name'];
                                        break;
                                    }
                                }
                            }
                            ?>

                            <div class="doccenter-breadcrumb">
                                <a href="?tab=documents" class="doccenter-back-btn">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="#374151" style="vertical-align:middle;margin-right:6px;">
                                        <path d="M15 18l-6-6 6-6" stroke="#374151" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    Folders
                                </a>
                                <?php if (!empty($breadcrumb)) : ?>
                                    <span class="doccenter-breadcrumb-separator">/</span>
                                    <?php echo implode(' <span class="doccenter-breadcrumb-separator">/</span> ', $breadcrumb); ?>
                                <?php endif; ?>
                            </div>

                            <h3 style="margin:0 0 12px 0; padding:0;"><?php echo esc_html($folder_name); ?></h3>

                            <?php if ($is_admin && $active_folder !== 'all' && $active_folder !== 'direct') : ?>
                                                                <div style="width:100%; display:flex; flex-wrap:wrap; gap:10px; margin-bottom:18px; justify-content:space-between; align-items:center;">
                                    <div style=" width:100%; display:flex; gap:10px; justify-content:space-between; align-items:center;">
                                        <button type="button" id="doccenter-inline-upload-toggle" class="doccenter-btn-primary" style="display:inline-flex;align-items:center;gap:6px;">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                            <polyline points="17 8 12 3 7 8"/>
                                            <line x1="12" y1="3" x2="12" y2="15"/>
                                        </svg>
                                        Upload Documents
                                    </button>
                                    <form method="post" style="margin:0; display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end;">
                                        <div style="flex:1 1 180px; max-width:280px;">
                                            <input type="text" name="new_group_name" placeholder="New subfolder name" style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;" required>
                                        </div>
                                        <input type="hidden" name="new_group_parent" value="<?php echo esc_attr($active_folder); ?>">
                                        <input type="hidden" name="return_to" value="<?php echo esc_url(add_query_arg(['tab' => 'documents', 'folder' => $active_folder], site_url('/dashboard'))); ?>">
                                        <?php wp_nonce_field('doccenter_dashboard', 'doccenter_dashboard_nonce'); ?>
                                        <input type="hidden" name="doccenter_action" value="add_group">
                                        <button type="submit" class="doccenter-btn-primary" style="height:36px; white-space:nowrap;">Create Subfolder</button>
                                    </form>
                                    </div>
                                </div>

                                <!-- INLINE UPLOAD FORM -->
                                <div id="doccenter-inline-upload-form" class="doccenter-card" style="display:none;margin-bottom:20px;">
                                    <h3 style="margin-top:0;">Upload to <?php echo esc_html($folder_name); ?></h3>
                                    <div class="doccenter-card-sub">Files will be automatically shared with this folder's group. You can add more groups or specific users below.</div>

                                    <form method="post" enctype="multipart/form-data">
                                        <input type="hidden" name="folder" value="<?php echo esc_attr($active_folder); ?>">

                                        <div class="doccenter-field" style="margin-bottom:10px;">
                                            <label>Files</label>
                                            <div id="doccenter-inline-drop-zone" style="
                                                border: 2px dashed #c4b5fd;
                                                border-radius: 14px;
                                                padding: 32px 20px;
                                                text-align: center;
                                                background: #faf5ff;
                                                cursor: pointer;
                                                transition: background 0.2s ease, border-color 0.2s ease;
                                                position: relative;
                                            ">
                                                <input type="file" id="doccenter_inline_file" name="doccenter_files[]" multiple required
                                                    style="position:absolute;inset:0;opacity:0;width:100%;height:100%;cursor:pointer;">
                                                <div style="pointer-events:none;">
                                                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#8b5cf6" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin:0 auto 10px;display:block;">
                                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                                        <polyline points="17 8 12 3 7 8"/>
                                                        <line x1="12" y1="3" x2="12" y2="15"/>
                                                    </svg>
                                                    <div id="doccenter-inline-drop-label" style="font-size:14px;font-weight:500;color:#6d28d9;">Drag &amp; drop files here</div>
                                                    <div style="font-size:12px;color:#9ca3af;margin-top:4px;">or click to browse &middot; PDF, DOCX, XLSX, PPTX, TXT, Images, Video, Audio &middot; Max 250MB each</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="doccenter_inline_file_metadata" style="margin-bottom:12px;"></div>

                                        <div class="doccenter-fields-grid">
                                            <div class="doccenter-field">
                                                <label for="doccenter_inline_visibility">Visibility</label>
                                                <select id="doccenter_inline_visibility" name="doccenter_visibility">
                                                    <option value="admins">Admins only</option>
                                                    <option value="everyone">Everyone</option>
                                                    <option value="custom" selected>Specific groups / users (folder group auto-selected)</option>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Hidden: folder group is always included -->
                                        <input type="hidden" name="doccenter_groups[]" value="<?php echo esc_attr($active_folder); ?>">

                                        <div class="doccenter-fields-grid" style="margin-top:10px;">
                                            <div class="doccenter-field">
                                                <label>Also share with additional groups</label>
                                                <div class="doccenter-checkbox-group" id="doccenter_inline_groups_box" style="max-height:120px;">
                                                    <?php
                                                    $other_groups = doccenter_get_group_hierarchy();
                                                    $has_other = false;
                                                    foreach ($other_groups as $g) :
                                                        if ($g['id'] === $active_folder) continue;
                                                        $has_other = true;
                                                        $level = isset($g['level']) ? $g['level'] : 0;
                                                        $indent_style = $level > 0 ? 'margin-left: ' . ($level * 15) . 'px;' : '';
                                                    ?>
                                                        <label class="doccenter-checkbox-item" style="<?php echo esc_attr($indent_style); ?>">
                                                            <input type="checkbox" name="doccenter_groups[]" value="<?php echo esc_attr($g['id']); ?>">
                                                            <span><?php echo esc_html($g['name']); ?></span>
                                                        </label>
                                                    <?php endforeach; ?>
                                                    <?php if (!$has_other) : ?>
                                                        <div style="font-size:11px;color:#9ca3af;">No other groups available.</div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="doccenter-field">
                                                <label>Share with specific users</label>
                                                <div class="doccenter-checkbox-group" id="doccenter_inline_users_box" style="max-height:120px;">
                                                    <?php if (! empty($all_viewers)) : ?>
                                                        <?php foreach ($all_viewers as $vu) : ?>
                                                            <label class="doccenter-checkbox-item">
                                                                <input type="checkbox" name="doccenter_users[]" value="<?php echo esc_attr($vu->ID); ?>">
                                                                <span><?php echo esc_html($vu->display_name . ' (' . $vu->user_login . ')'); ?></span>
                                                            </label>
                                                        <?php endforeach; ?>
                                                    <?php else : ?>
                                                        <div style="font-size:11px;color:#9ca3af;">No viewer accounts yet.</div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="doccenter-field" style="margin-top:10px;">
                                            <label for="doccenter_inline_notes">Description / Notes</label>
                                            <textarea id="doccenter_inline_notes" name="doccenter_notes" style="min-height:60px;"></textarea>
                                        </div>

                                        <?php wp_nonce_field('doccenter_upload', 'doccenter_upload_nonce'); ?>
                                        <input type="hidden" name="doccenter_action" value="upload_document">

                                        <div style="margin-top:12px;display:flex;gap:8px;justify-content:flex-end;">
                                            <button type="button" id="doccenter-inline-upload-cancel" class="doccenter-btn-secondary">Cancel</button>
                                            <button type="submit" class="doccenter-btn-primary">Upload to Folder</button>
                                        </div>
                                    </form>
                                </div>
                            <?php endif; ?>

                            <?php
                            // Get documents for this folder view
                            if ($active_folder === 'all' && $is_admin) {
                                $docs_for_folder = new WP_Query([
                                    'post_type'      => 'attachment',
                                    'post_status'    => 'inherit',
                                    'posts_per_page' => 300,
                                    'orderby'        => 'date',
                                    'order'          => 'DESC',
                                    'meta_query'     => [
                                        ['key' => '_document_visibility', 'compare' => 'EXISTS'],
                                    ],
                                ]);

                                $folder_docs = [];
                                while ($docs_for_folder->have_posts()) {
                                    $docs_for_folder->the_post();
                                    $folder_docs[] = get_the_ID();
                                }
                                wp_reset_postdata();
                            } elseif ($active_folder === 'direct') {
                                $folder_docs = doccenter_get_direct_documents($user_id);
                            } else {
                                // Normal group folders
                                $folder_docs = doccenter_get_group_documents($active_folder, $user_id);

                                // Show child folders
                                $child_folders = doccenter_get_child_groups($active_folder);
                                if (!empty($child_folders)) {
                                    echo '<div class="doccenter-subfolder-list">';
                                    foreach ($child_folders as $child_folder) {
                                        $child_count = doccenter_count_group_documents($child_folder['id'], $user_id);
                                        ?>
                                        <a class="doccenter-folder doccenter-subfolder" href="?tab=documents&folder=<?php echo esc_attr($child_folder['id']); ?>">
                                            <div class="doccenter-folder-icon">
                                                <svg width="32" height="32" viewBox="0 0 24 24" fill="#6C63FF">
                                                    <path d="M10 4H4C2.9 4 2 4.9 2 6V18C2 19.1 2.9 20 4 20H20C21.1 20 22 19.1 22 18V8C22 6.9 21.1 6 20 6H12L10 4Z" />
                                                </svg>
                                            </div>
                                            <div class="doccenter-folder-name"><?php echo esc_html($child_folder['name']); ?></div>
                                            <div class="doccenter-folder-meta"><?php echo $child_count; ?> items</div>
                                        </a>
                                        <?php
                                    }
                                    echo '</div>';
                                }
                            }
                            ?>

                            <?php 
                            // Apply search filter to folder documents
                            if (!empty($search_query) && !empty($folder_docs)) {
                                $filtered_docs = [];
                                foreach ($folder_docs as $doc_id) {
                                    $title = strtolower(get_the_title($doc_id));
                                    $unique_tag = strtolower(get_post_meta($doc_id, '_document_unique_tag', true));
                                    if (stripos($title, $search_query_lower) !== false || stripos($unique_tag, $search_query_lower) !== false) {
                                        $filtered_docs[] = $doc_id;
                                    }
                                }
                                $folder_docs = $filtered_docs;
                            }
                            ?>

                            <?php if (!empty($folder_docs)) : ?>
                                <?php if (!empty($search_query)) : ?>
                                    <div style="font-size: 13px; color: #6b7280; margin-bottom: 10px;">
                                        Found <?php echo count($folder_docs); ?> result(s) for "<?php echo esc_html($search_query); ?>"
                                    </div>
                                <?php endif; ?>
                                <div class="doccenter-doc-list">
                                    <?php foreach ($folder_docs as $doc_id) :
                                        $url           = wp_get_attachment_url($doc_id);
                                        $title         = get_the_title($doc_id);
                                        $modified_date = doccenter_get_document_modified_date($doc_id);
                                        $expiry_date   = doccenter_get_document_expiry_date($doc_id);
                                        $uploaded_by_id = get_post_meta($doc_id, '_document_uploaded_by', true);

                                        // If no custom meta, fall back to post_author
                                        if (empty($uploaded_by_id)) {
                                            $uploaded_by_id = get_post_field('post_author', $doc_id);
                                        }

                                        $uploaded_by_username = 'Unknown';
                                        if (!empty($uploaded_by_id)) {
                                            $user_data = get_userdata($uploaded_by_id);
                                            if ($user_data) {
                                                $uploaded_by_username = $user_data->user_login;
                                            }
                                        }
                                    ?>
                                        <?php
                                            $doc_ext = strtolower(pathinfo($url, PATHINFO_EXTENSION));
                                            $doc_type = doccenter_get_file_type($doc_ext);
                                            $doc_icon = doccenter_get_file_icon_svg($doc_ext);
                                            $is_previewable = doccenter_is_media_previewable($doc_ext);
                                        ?>
                                        <div class="doccenter-doc-item">
                                            <div style="display:flex;align-items:center;gap:10px;">
                                                <div style="flex-shrink:0;width:36px;height:36px;display:flex;align-items:center;justify-content:center;background:#f3f4f6;border-radius:8px;">
                                                    <?php echo $doc_icon; ?>
                                                </div>
                                                <div>
                                                    <div class="doccenter-doc-title"><?php echo esc_html($title); ?></div>
                                                    <?php $unique_tag = get_post_meta($doc_id, '_document_unique_tag', true); ?>
                                                    <div class="doccenter-doc-meta"><?php echo esc_html('Modified ' . $modified_date . ' &middot; Expires ' . $expiry_date); ?> &bull; Uploaded by <?php echo esc_html($uploaded_by_username); ?><?php echo $unique_tag ? ' &bull; ' . esc_html($unique_tag) : ''; ?> &bull; <?php echo esc_html(strtoupper($doc_ext)); ?></div>
                                                </div>
                                            </div>

                                            <div class="doccenter-doc-actions">
                                                <?php if ($is_previewable) : ?>
                                                    <button type="button"
                                                        class="doccenter-doc-preview"
                                                        data-title="<?php echo esc_attr($title); ?>"
                                                        data-url="<?php echo esc_url($url); ?>"
                                                        data-type="<?php echo esc_attr($doc_type); ?>"
                                                        data-ext="<?php echo esc_attr($doc_ext); ?>">
                                                        Preview
                                                    </button>
                                                <?php endif; ?>
                                                <a href="<?php echo esc_url($url); ?>" target="_blank">View</a>
                                                <a href="<?php echo esc_url($url); ?>" download>Download</a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                            <?php else : ?>
                                <div style="font-size:13px;color:#9ca3af;">
                                    <?php 
                                    if (!empty($search_query)) {
                                        echo 'No documents found matching "' . esc_html($search_query) . '" in this folder.';
                                    } else {
                                        echo 'No documents found in this folder.';
                                    }
                                    ?>
                                </div>
                            <?php endif; ?>

                        <?php endif; ?>
                    </div>
                </section>




                <!-- GROUPS (ADMIN) -->
                <?php if ($is_admin) : ?>
                    <section class="doccenter-section" data-tab="groups">
                        <div class="doccenter-card">
                            <h3>Viewer groups</h3>
                            <div class="doccenter-card-sub">Create categories of viewers and share documents with groups.</div>

                            <form method="post" style="margin-bottom:12px;">
                                <div class="doccenter-fields-grid">
                                    <div class="doccenter-field">
                                        <label for="new_group_name">New group name</label>
                                        <input type="text" id="new_group_name" name="new_group_name">
                                    </div>
                                    <div class="doccenter-field">
                                        <label for="new_group_parent">Parent folder (optional)</label>
                                        <select id="new_group_parent" name="new_group_parent">
                                            <option value="">Root level</option>
                                            <?php
                                            $parent_options = doccenter_get_group_hierarchy();
                                            foreach ($parent_options as $grp) {
                                                $indent = isset($grp['level']) && $grp['level'] > 0 ? str_repeat('— ', $grp['level']) : '';
                                                echo '<option value="' . esc_attr($grp['id']) . '">' . esc_html($indent . $grp['name']) . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="doccenter-field" style="font-size:11px;color:#6b7280;" id="new_group_parent_help">Create a root-level folder or choose a parent folder.</div>
                                </div>
                                <?php wp_nonce_field('doccenter_dashboard', 'doccenter_dashboard_nonce'); ?>
                                <input type="hidden" name="doccenter_action" value="add_group">
                                <div style="margin-top:10px;">
                                    <button type="submit" class="doccenter-btn-primary">Add group</button>
                                </div>
                            </form>

                            <?php if (! empty($groups)) : ?>
                                <div style="margin-top:8px;">
                                    <?php
                                    $group_hierarchy = doccenter_get_group_hierarchy();
                                    foreach ($group_hierarchy as $g) :
                                        $group_id      = $g['id'];
                                        $level         = isset($g['level']) ? $g['level'] : 0;
                                        $indent_style  = $level > 0 ? 'margin-left:' . ($level * 16) . 'px;' : '';
                                        $group_viewers = [];

                                        if (! empty($all_viewers)) {
                                            foreach ($all_viewers as $vu) {
                                                $vu_groups = doccenter_get_user_groups($vu->ID);
                                                if (is_array($vu_groups) && in_array($group_id, $vu_groups, true)) {
                                                    $group_viewers[] = $vu->ID;
                                                }
                                            }
                                        }

                                        $group_viewers_csv = implode(',', $group_viewers);
                                        ?>
                                        <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;padding:8px;border-radius:10px;border:1px solid #e5e7eb;background:#f9fafb;margin-bottom:6px;<?php echo esc_attr($indent_style); ?>">
                                            <div style="font-size:13px;"><?php echo esc_html($g['name']); ?></div>
                                            <div style="display:flex;align-items:center;gap:6px;">
                                                <button type="button"
                                                    class="doccenter-btn-secondary doccenter-group-edit-btn"
                                                    data-group-id="<?php echo esc_attr($group_id); ?>"
                                                    data-group-name="<?php echo esc_attr($g['name']); ?>"
                                                    data-group-viewers="<?php echo esc_attr($group_viewers_csv); ?>">
                                                    Edit viewers
                                                </button>

                                                <button type="button"
                                                    class="doccenter-btn-secondary doccenter-subfolder-btn"
                                                    data-parent-id="<?php echo esc_attr($group_id); ?>"
                                                    data-parent-name="<?php echo esc_attr($g['name']); ?>">
                                                    New subfolder
                                                </button>

                                                <form method="post" style="margin:0;">
                                                    <?php wp_nonce_field('doccenter_dashboard', 'doccenter_dashboard_nonce'); ?>
                                                    <input type="hidden" name="group_id" value="<?php echo esc_attr($g['id']); ?>">
                                                    <input type="hidden" name="doccenter_action" value="delete_group">
                                                    <button type="submit" class="doccenter-btn-secondary">Delete</button>
                                                </form>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else : ?>
                                <div style="font-size:13px;color:#9ca3af;">No groups created yet.</div>
                            <?php endif; ?>

                        </div>
                    </section>

                    <!-- USERS (ADMIN) -->
                    <section class="doccenter-section" data-tab="users">

                        <div class="doccenter-card">
                            <h3>Manage viewers</h3>
                            <div class="doccenter-card-sub">Edit viewer groups or delete accounts.</div>

                            <?php if (! empty($all_viewers)) : ?>
                                <div class="doccenter-doc-list">
                                    <?php foreach ($all_viewers as $vu) : ?>
                                        <?php $vu_groups = doccenter_get_user_groups($vu->ID); ?>
                                        <div class="doccenter-doc-item" data-user-row="<?php echo esc_attr($vu->ID); ?>">
                                            <div>
                                                <div class="doccenter-doc-title">
                                                    <?php echo esc_html($vu->display_name); ?>
                                                    <span style="font-weight:400;color:#6b7280;">(<?php echo esc_html($vu->user_login); ?>)</span>
                                                </div>
                                                <div class="doccenter-doc-meta">
                                                    <?php
                                                    if (! empty($vu_groups) && ! empty($groups)) {
                                                        $names = [];
                                                        $map   = [];
                                                        foreach ($groups as $g) {
                                                            $map[$g['id']] = $g['name'];
                                                        }
                                                        foreach ($vu_groups as $gid) {
                                                            if (isset($map[$gid])) {
                                                                $names[] = $map[$gid];
                                                            }
                                                        }
                                                        if (! empty($names)) {
                                                            echo 'Groups: ' . esc_html(implode(', ', $names));
                                                        } else {
                                                            echo 'Groups: none';
                                                        }
                                                    } else {
                                                        echo 'Groups: none';
                                                    }
                                                    ?>
                                                </div>
                                            </div>
                                            <div class="doccenter-doc-actions">
                                                <button type="button"
                                                    class="doccenter-user-edit-btn"
                                                    data-user-id="<?php echo esc_attr($vu->ID); ?>"
                                                    data-user-name="<?php echo esc_attr($vu->display_name . ' (' . $vu->user_login . ')'); ?>"
                                                    data-user-groups="<?php echo esc_attr(implode(',', $vu_groups)); ?>">
                                                    Edit
                                                </button>
                                                <button type="button"
                                                    class="doccenter-user-delete-btn"
                                                    data-user-id="<?php echo esc_attr($vu->ID); ?>"
                                                    data-user-name="<?php echo esc_attr($vu->display_name . ' (' . $vu->user_login . ')'); ?>">
                                                    Delete
                                                </button>
                                                <?php if (doccenter_is_super_admin_user()) : ?>
                                                    <button type="button"
                                                        class="doccenter-user-promote-btn"
                                                        data-user-id="<?php echo esc_attr($vu->ID); ?>"
                                                        data-user-name="<?php echo esc_attr($vu->display_name . ' (' . $vu->user_login . ')'); ?>">
                                                        Promote to admin
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else : ?>
                                <div style="font-size:13px;color:#9ca3af;">No viewer accounts found.</div>
                            <?php endif; ?>
                        </div>

                        <?php if (doccenter_is_super_admin_user()) : ?>
                            <div class="doccenter-card" style="margin-top:16px;">
                                <h3>Admins &amp; roles</h3>
                                <div class="doccenter-card-sub">Promote admins to Super admin or revert back.</div>
                                <?php
                                $all_admins = get_users(
                                    [
                                        'role__in' => ['document_admin', 'document_super_admin'],
                                        'orderby'  => 'display_name',
                                        'order'    => 'ASC',
                                    ]
                                );
                                ?>
                                <?php if (! empty($all_admins)) : ?>
                                    <div class="doccenter-doc-list">
                                        <?php foreach ($all_admins as $au) : ?>
                                            <?php
                                            $roles    = (array) $au->roles;
                                            $is_super = in_array('document_super_admin', $roles, true) || in_array('administrator', $roles, true);

                                            // Check if CURRENT USER is super admin
                                            $current_user   = wp_get_current_user();
                                            $current_roles  = (array) $current_user->roles;
                                            $current_is_super = in_array('document_super_admin', $current_roles, true) || in_array('administrator', $current_roles, true);
                                            ?>

                                            <div class="doccenter-doc-item">
                                                <div>
                                                    <div class="doccenter-doc-title">
                                                        <?php echo esc_html($au->display_name); ?>
                                                        <span style="font-size:12px;color:#6b7280;">(<?php echo esc_html($au->user_login); ?>)</span>
                                                    </div>
                                                    <div class="doccenter-doc-meta">
                                                        Role: <?php echo $is_super ? 'Super admin' : 'Admin'; ?>
                                                    </div>
                                                </div>

                                                <div class="doccenter-doc-actions">

                                                    <?php if (! $is_super) : ?>
                                                        <!-- Promote admin → super admin -->
                                                        <button type="button"
                                                            class="doccenter-user-promote-super-btn"
                                                            data-user-id="<?php echo esc_attr($au->ID); ?>"
                                                            data-user-name="<?php echo esc_attr($au->display_name . ' (' . $au->user_login . ')'); ?>">
                                                            Make super admin
                                                        </button>

                                                        <?php if ($current_is_super) : ?>
                                                            <!-- NEW: Demote admin → viewer -->
                                                            <button type="button"
                                                                class="doccenter-demote-admin-btn"
                                                                data-user-id="<?php echo esc_attr($au->ID); ?>"
                                                                data-user-name="<?php echo esc_attr($au->display_name . ' (' . $au->user_login . ')'); ?>"
                                                                style="background:#ef4444;color:white;padding:6px 10px;border-radius:6px;border:none;cursor:pointer;">
                                                                Demote to viewer
                                                            </button>
                                                        <?php endif; ?>

                                                    <?php elseif (! in_array('administrator', $roles, true)) : ?>
                                                        <!-- Demote super admin → admin -->
                                                        <button type="button"
                                                            class="doccenter-user-demote-super-btn"
                                                            data-user-id="<?php echo esc_attr($au->ID); ?>"
                                                            data-user-name="<?php echo esc_attr($au->display_name . ' (' . $au->user_login . ')'); ?>">
                                                            Remove super admin
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                <?php else : ?>
                                    <div style="font-size:13px;color:#9ca3af;">No admin accounts found yet.</div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <!-- CREATE NEW USER (ADMIN / SUPER ADMIN) -->
                        <?php if (doccenter_is_admin_user()) : ?>
                            <div class="doccenter-card" style="margin-bottom:20px;">
                                <h3>Create new user</h3>
                                <div class="doccenter-card-sub">
                                    Super Admin can create admins or viewers. Admins can create viewers only.
                                </div>

                                <form method="post" style="margin-top:10px;">
                                    <div class="doccenter-fields-grid">
                                        <div class="doccenter-field">
                                            <label for="dc_new_username">Username</label>
                                            <input type="text" id="dc_new_username" name="dc_new_username" required>
                                        </div>

                                        <div class="doccenter-field">
                                            <label for="dc_new_email">Email</label>
                                            <input type="email" id="dc_new_email" name="dc_new_email" required>
                                        </div>

                                        <div class="doccenter-field">
                                            <label for="dc_new_password">Password</label>
                                            <div style="display:flex;gap:10px;align-items:center;">
                                                <input type="text" id="dc_new_password" name="dc_new_password" required>
                                                <button type="button" class="doccenter-btn-secondary" id="dc_generate_password">
                                                    Generate
                                                </button>
                                            </div>
                                        </div>

                                        <div class="doccenter-field">
                                            <label for="dc_new_role">Role</label>
                                            <select name="dc_new_role" required>
                                                <?php if (doccenter_is_super_admin_user()) : ?>
                                                    <option value="document_super_admin">Super Admin</option>
                                                    <option value="document_admin">Admin</option>
                                                    <option value="document_viewer">Viewer</option>
                                                <?php else : ?>
                                                    <option value="document_viewer">Viewer</option>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="doccenter-field" id="doccenter_groups_box" style="margin-top:10px;">
                                        <label>Assign to groups</label>
                                        <div class="doccenter-checkbox-group">
                                            <?php
                                            $group_hierarchy = doccenter_get_group_hierarchy();
                                            foreach ($group_hierarchy as $g) : 
                                                $level = isset($g['level']) ? $g['level'] : 0;
                                                $indent_style = $level > 0 ? 'margin-left: ' . ($level * 15) . 'px;' : '';
                                            ?>
                                                <label class="doccenter-checkbox-item" style="<?php echo esc_attr($indent_style); ?>">
                                                    <input type="checkbox" name="dc_new_groups[]" value="<?php echo esc_attr($g['id']); ?>">
                                                    <span><?php echo esc_html($g['name']); ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <?php wp_nonce_field('doccenter_dashboard', 'doccenter_dashboard_nonce'); ?>
                                    <input type="hidden" name="doccenter_action" value="create_new_user">

                                    <div style="margin-top:12px;text-align:right;">
                                        <button type="submit" class="doccenter-btn-primary">Create User</button>
                                    </div>
                                </form>
                            </div>
                        <?php endif; ?>

                    </section>
                <?php endif; ?>

                <!-- REQUESTS TAB -->
                <section class="doccenter-section" data-tab="requests">
                    <?php if ($is_admin) : ?>
                        <div class="doccenter-card">
                            <h3>Incoming requests</h3>
                            <div class="doccenter-card-sub">View and reply to document requests from viewers.</div>
                            <?php if (! empty($requests)) : ?>
                                <div style="display:flex;flex-direction:column;gap:8px;">
                                    <?php foreach ($requests as $req) : ?>
                                        <?php
                                        $req_user_id = (int) get_post_meta($req->ID, '_request_user', true);
                                        $req_user    = $req_user_id ? get_user_by('id', $req_user_id) : null;
                                        $status      = get_post_meta($req->ID, '_request_status', true);
                                        $reply_txt   = get_post_meta($req->ID, '_request_reply', true);
                                        ?>
                                        <div class="doccenter-doc-item" style="align-items:flex-start;flex-direction:column;">
                                            <div style="width:100%;">
                                                <div style="font-size:13px;font-weight:500;"><?php echo esc_html($req->post_title); ?></div>
                                                <div class="doccenter-doc-meta">
                                                    From: <?php echo $req_user ? esc_html($req_user->display_name) : 'Unknown'; ?>
                                                    · <?php echo esc_html(get_the_date('M j, Y', $req)); ?>
                                                    · Status: <?php echo esc_html(ucfirst($status)); ?>
                                                </div>
                                                <?php if ($req->post_content) : ?>
                                                    <div style="font-size:12px;color:#4b5563;margin-top:4px;"><?php echo esc_html(wp_trim_words($req->post_content, 40)); ?></div>
                                                <?php endif; ?>
                                            </div>
                                            <div style="width:100%;margin-top:8px;">
                                                <form method="post">
                                                    <div class="doccenter-field">
                                                        <label for="reply_<?php echo esc_attr($req->ID); ?>">Reply (optional)</label>
                                                        <textarea id="reply_<?php echo esc_attr($req->ID); ?>" name="reply_text"><?php echo esc_textarea($reply_txt); ?></textarea>
                                                    </div>
                                                    <?php wp_nonce_field('doccenter_reply', 'doccenter_reply_nonce'); ?>
                                                    <input type="hidden" name="doccenter_action" value="reply_request">
                                                    <input type="hidden" name="request_id" value="<?php echo esc_attr($req->ID); ?>">
                                                    <div style="margin-top:8px;text-align:right;">
                                                        <button type="submit" class="doccenter-btn-primary">Send reply &amp; close</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else : ?>
                                <div style="font-size:13px;color:#9ca3af;">No open document requests.</div>
                            <?php endif; ?>
                        </div>
                    <?php else : ?>
                        <div class="doccenter-card">
                            <h3>Request a document</h3>
                            <div class="doccenter-card-sub">Send a request to administrators if you cannot find what you need.</div>

                            <form method="post">
                                <div class="doccenter-field">
                                    <label for="doc_req_title">Request title</label>
                                    <input type="text" id="doc_req_title" name="doc_req_title" required>
                                </div>
                                <div class="doccenter-field" style="margin-top:8px;">
                                    <label for="doc_req_message">Details</label>
                                    <textarea id="doc_req_message" name="doc_req_message"></textarea>
                                </div>
                                <?php wp_nonce_field('doccenter_request', 'doccenter_request_nonce'); ?>
                                <input type="hidden" name="doccenter_action" value="request_document">
                                <div style="margin-top:10px;text-align:right;">
                                    <button type="submit" class="doccenter-btn-primary">Send request</button>
                                </div>
                            </form>
                        </div>

                        <div class="doccenter-card">
                            <h3>Your requests</h3>
                            <div class="doccenter-card-sub">Check status and read admin replies.</div>
                            <?php if (! empty($viewer_requests)) : ?>
                                <div style="display:flex;flex-direction:column;gap:8px;">
                                    <?php foreach ($viewer_requests as $req) : ?>
                                        <?php
                                        $status    = get_post_meta($req->ID, '_request_status', true);
                                        $reply_txt = get_post_meta($req->ID, '_request_reply', true);
                                        ?>
                                        <div class="doccenter-doc-item" style="align-items:flex-start;flex-direction:column;">
                                            <div style="font-size:13px;font-weight:500;"><?php echo esc_html($req->post_title); ?></div>
                                            <div class="doccenter-doc-meta">
                                                <?php echo esc_html(get_the_date('M j, Y', $req)); ?>
                                                · Status: <?php echo esc_html(ucfirst($status ? $status : 'open')); ?>
                                            </div>
                                            <?php if ($req->post_content) : ?>
                                                <div style="font-size:12px;color:#4b5563;margin-top:4px;"><?php echo esc_html(wp_trim_words($req->post_content, 40)); ?></div>
                                            <?php endif; ?>
                                            <?php if ($reply_txt) : ?>
                                                <div style="margin-top:6px;padding:6px 8px;border-radius:8px;background:#f3f4ff;font-size:12px;color:#111827;">
                                                    <strong>Admin reply:</strong><br><?php echo esc_html($reply_txt); ?>
                                                </div>
                                            <?php endif; ?>

                                            <?php if ($status === 'closed') : ?>
                                                <form method="post" style="margin-top:6px;text-align:right;width:100%;">
                                                    <?php wp_nonce_field('doccenter_request_delete', 'doccenter_request_delete_nonce'); ?>
                                                    <input type="hidden" name="doccenter_action" value="delete_request">
                                                    <input type="hidden" name="request_id" value="<?php echo esc_attr($req->ID); ?>">
                                                    <button type="submit" class="doccenter-btn-secondary" onclick="return confirm('Remove this closed request?');">Remove</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else : ?>
                                <div style="font-size:13px;color:#9ca3af;">You have not sent any requests yet.</div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <!-- PROFILE -->
                <section class="doccenter-section" data-tab="profile">
                    <div class="doccenter-card">
                        <h3>Profile &amp; access</h3>
                        <div class="doccenter-card-sub">Basic info and your viewer groups.</div>

                        <div class="doccenter-fields-grid">
                            <div class="doccenter-field">
                                <label>Username</label>
                                <input type="text" value="<?php echo esc_attr($user->user_login); ?>" disabled>
                            </div>
                            <div class="doccenter-field">
                                <label>Email</label>
                                <input type="email" value="<?php echo esc_attr($user->user_email); ?>" disabled>
                            </div>
                        </div>

                        <?php if (! $is_admin) : ?>
                            <div style="margin-top:12px;">
                                <div style="font-size:12px;font-weight:500;color:#374151;margin-bottom:4px;">Your groups</div>
                                <?php
                                if (! empty($userGroups) && ! empty($groups)) {
                                    $map = [];
                                    foreach ($groups as $g) {
                                        $map[$g['id']] = $g['name'];
                                    }
                                    foreach ($userGroups as $gid) {
                                        if (isset($map[$gid])) {
                                            echo '<span class="doccenter-btn-secondary" style="margin-right:6px;margin-bottom:4px;font-size:11px;display:inline-block;">' . esc_html($map[$gid]) . '</span>';
                                        }
                                    }
                                } else {
                                    echo '<div style="font-size:12px;color:#9ca3af;">No groups assigned yet.</div>';
                                }
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
            </main>
            <div style="clear:both;"></div>
        </div>

        <!-- SLIDE-OVER EDIT PANEL (ADMIN) -->
        <?php if ($is_admin && $edit_doc_id && $edit_doc) : ?>
            <?php
            $edit_vis   = $edit_vis ? $edit_vis : 'admins';
            $edit_title = $edit_doc->post_title;
            $edit_url   = wp_get_attachment_url($edit_doc_id);
            $edit_date  = get_the_date('M j, Y', $edit_doc);
            ?>
            <div class="doccenter-edit-overlay doccenter-edit-overlay-active" id="doccenter-edit-overlay">
                <div class="doccenter-edit-panel">
                    <div class="doccenter-edit-header">
                        <div class="doccenter-edit-title">Edit document</div>
                        <button type="button" class="doccenter-edit-close" id="doccenter-edit-close">&times;</button>
                    </div>
                    <div class="doccenter-edit-body">
                        <div style="font-size:12px;color:#6b7280;margin-bottom:8px;">
                            <?php echo esc_html($edit_title); ?> · Uploaded <?php echo esc_html($edit_date); ?>
                        </div>
                        <form method="post">
                            <div class="doccenter-field">
                                <label for="doccenter_edit_title">Title</label>
                                <input type="text" id="doccenter_edit_title" name="doccenter_edit_title" value="<?php echo esc_attr($edit_title); ?>" required>
                            </div>
                            <div class="doccenter-field" style="margin-top:8px;">
                                <label for="doccenter_edit_visibility">Visibility</label>
                                <select id="doccenter_edit_visibility" name="doccenter_edit_visibility">
                                    <option value="admins" <?php selected($edit_vis === 'admins'); ?>>Admins only</option>
                                    <option value="everyone" <?php selected($edit_vis === 'everyone'); ?>>Everyone</option>
                                    <option value="custom" <?php selected($edit_vis === 'custom'); ?>>Specific groups / users</option>
                                </select>
                            </div>

                            <div class="doccenter-field" style="margin-top:8px;">
                                <label>Share with groups</label>
                                <div class="doccenter-checkbox-group">
                                    <?php if (! empty($groups)) : ?>
                                        <?php foreach ($groups as $g) : ?>
                                            <label class="doccenter-checkbox-item">
                                                <input type="checkbox" name="doccenter_edit_groups[]" value="<?php echo esc_attr($g['id']); ?>" <?php checked(in_array($g['id'], $edit_gids, true)); ?>>
                                                <span><?php echo esc_html($g['name']); ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <div style="font-size:11px;color:#9ca3af;">No groups yet.</div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="doccenter-field" style="margin-top:8px;">
                                <label>Share with specific users</label>
                                <div class="doccenter-checkbox-group">
                                    <?php if (! empty($all_viewers)) : ?>
                                        <?php foreach ($all_viewers as $vu) : ?>
                                            <label class="doccenter-checkbox-item">
                                                <input type="checkbox" name="doccenter_edit_users[]" value="<?php echo esc_attr($vu->ID); ?>" <?php checked(in_array($vu->ID, $edit_uids, true)); ?>>
                                                <span><?php echo esc_html($vu->display_name . ' (' . $vu->user_login . ')'); ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <div style="font-size:11px;color:#9ca3af;">No viewer accounts yet.</div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="doccenter-field" style="margin-top:8px;">
                                <label for="doccenter_edit_notes">Description / Notes</label>
                                <textarea id="doccenter_edit_notes" name="doccenter_edit_notes"><?php echo esc_textarea($edit_notes); ?></textarea>
                            </div>

                            <?php wp_nonce_field('doccenter_edit', 'doccenter_edit_nonce'); ?>
                            <input type="hidden" name="doccenter_action" value="edit_document">
                            <input type="hidden" name="doccenter_edit_id" value="<?php echo esc_attr($edit_doc_id); ?>">

                            <div style="margin-top:12px;text-align:right;">
                                <button type="submit" class="doccenter-btn-primary">Save changes</button>
                            </div>
                        </form>

                        <div style="margin-top:12px;font-size:12px;color:#6b7280;">
                            File: <a href="<?php echo esc_url($edit_url); ?>" target="_blank" rel="noopener">Open current file</a><br>
                            Note: file itself cannot be replaced from this editor. Upload a new document if needed.
                        </div>
                    </div>
                </div>
            </div>
        <?php else : ?>
            <div class="doccenter-edit-overlay" id="doccenter-edit-overlay" style="display:none;"></div>
        <?php endif; ?>

        <!-- VIEWER / ADMIN DETAIL MODAL -->
        <div class="doccenter-modal-overlay" id="doccenter-modal-overlay">
            <div class="doccenter-modal">
                <div class="doccenter-modal-header">
                    <div class="doccenter-modal-title" id="doccenter-modal-title">Document details</div>
                    <button type="button" class="doccenter-modal-close" id="doccenter-modal-close">&times;</button>
                </div>
                <div class="doccenter-modal-meta" id="doccenter-modal-meta"></div>
                <div class="doccenter-modal-notes" id="doccenter-modal-notes"></div>
                <div style="text-align:right;margin-top:6px;">
                    <a href="#" target="_blank" rel="noopener" id="doccenter-modal-view" class="doccenter-btn-secondary">View</a>
                    <a href="#" id="doccenter-modal-download" class="doccenter-btn-primary" download>Download</a>
                </div>
            </div>
        </div>

        <!-- MEDIA PREVIEW MODAL -->
        <div class="doccenter-modal-overlay" id="doccenter-preview-overlay">
            <div class="doccenter-modal" style="max-width:900px;width:95%;padding:0;overflow:hidden;">
                <div class="doccenter-modal-header" style="padding:12px 18px;border-bottom:1px solid #e5e7eb;background:#fff;">
                    <div class="doccenter-modal-title" id="doccenter-preview-title">Preview</div>
                    <button type="button" class="doccenter-modal-close" id="doccenter-preview-close">&times;</button>
                </div>
                <div id="doccenter-preview-content" style="padding:0;background:#0f0f0f;display:flex;align-items:center;justify-content:center;min-height:300px;max-height:80vh;overflow:auto;">
                    <!-- Content injected by JS -->
                </div>
                <div style="padding:12px 18px;background:#fff;border-top:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center;">
                    <span id="doccenter-preview-meta" style="font-size:12px;color:#6b7280;"></span>
                    <div style="display:flex;gap:8px;">
                        <a href="#" id="doccenter-preview-download" class="doccenter-btn-primary" download>Download</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- GROUP EDIT MODAL -->
        <!-- GROUP EDIT MODAL -->
        <?php if ($is_admin && ! empty($groups) && ! empty($all_viewers)) : ?>
            <div class="doccenter-user-overlay" id="doccenter-group-overlay">
                <div class="doccenter-user-modal">
                    <div class="doccenter-user-modal-header">
                        <div class="doccenter-user-modal-title" id="doccenter-group-modal-title">Edit group</div>
                        <button type="button" class="doccenter-user-modal-close" id="doccenter-group-modal-close">&times;</button>
                    </div>
                    <form method="post" id="doccenter-group-form">
                        <div class="doccenter-field">
                            <label for="doccenter-group-name">Group</label>
                            <input type="text" id="doccenter-group-name" value="" readonly style="background:#f9fafb;border-color:#e5e7eb;">
                        </div>

                        <div class="doccenter-field" style="margin-top:8px;">
                            <label>Assigned viewers</label>
                            <div class="doccenter-checkbox-group" id="doccenter-group-users-box">
                                <?php foreach ($all_viewers as $vu) : ?>
                                    <label class="doccenter-checkbox-item">
                                        <input type="checkbox" name="group_viewers[]" value="<?php echo esc_attr($vu->ID); ?>">
                                        <span><?php echo esc_html($vu->display_name); ?> (<?php echo esc_html($vu->user_login); ?>)</span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <?php wp_nonce_field('doccenter_dashboard', 'doccenter_dashboard_nonce'); ?>
                        <input type="hidden" name="group_id" id="doccenter-group-id" value="">
                        <input type="hidden" name="doccenter_action" value="assign_group_members">


                        <div style="margin-top:12px;display:flex;justify-content:space-between;gap:8px;">
                            <button type="button" class="doccenter-btn-secondary" id="doccenter-group-cancel">Cancel</button>
                            <button type="submit" class="doccenter-btn-primary">Save viewers</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- USER EDIT MODAL -->
        <?php if ($is_admin) : ?>
            <div class="doccenter-user-overlay" id="doccenter-user-overlay">
                <div class="doccenter-user-modal">
                    <div class="doccenter-user-modal-header">
                        <div class="doccenter-user-modal-title" id="doccenter-user-modal-title">Edit user</div>
                        <button type="button" class="doccenter-user-modal-close" id="doccenter-user-modal-close">&times;</button>
                    </div>
                    <form method="post" id="doccenter-user-form">
                        <div class="doccenter-field">
                            <label>Viewer</label>
                            <input type="text" id="doccenter-user-display" value="" disabled>
                        </div>
                        <div class="doccenter-field" style="margin-top:8px;">
                            <label>Groups</label>
                            <div class="doccenter-checkbox-group" id="doccenter-user-groups-box">
                                <?php if (! empty($groups)) : ?>
                                    <?php foreach ($groups as $g) : ?>
                                        <label class="doccenter-checkbox-item">
                                            <input type="checkbox" name="user_groups[]" value="<?php echo esc_attr($g['id']); ?>">
                                            <span><?php echo esc_html($g['name']); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <div style="font-size:11px;color:#9ca3af;">No groups yet.</div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php wp_nonce_field('doccenter_user_manage', 'doccenter_user_nonce'); ?>
                        <input type="hidden" name="user_id" id="doccenter-user-id" value="">
                        <input type="hidden" name="doccenter_action" id="doccenter-user-action" value="edit_user">

                        <div style="margin-top:12px;display:flex;justify-content:space-between;gap:8px;">
                            <button type="button" class="doccenter-btn-danger" id="doccenter-user-delete-confirm">Delete user</button>
                            <button type="submit" class="doccenter-btn-primary">Save changes</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        (function() {
            const root = document.getElementById('doccenter-root');
            const navItems = document.querySelectorAll('.doccenter-nav-item[data-tab]');
            const sections = document.querySelectorAll('.doccenter-section');
            const editButtons = document.querySelectorAll('.doccenter-doc-edit');
            const editOverlay = document.getElementById('doccenter-edit-overlay');
            const editClose = document.getElementById('doccenter-edit-close');
            const modalOverlay = document.getElementById('doccenter-modal-overlay');
            const modalClose = document.getElementById('doccenter-modal-close');
            const modalTitle = document.getElementById('doccenter-modal-title');
            const modalMeta = document.getElementById('doccenter-modal-meta');
            const modalNotes = document.getElementById('doccenter-modal-notes');
            const modalView = document.getElementById('doccenter-modal-view');
            const modalDownload = document.getElementById('doccenter-modal-download');
            const detailButtons = document.querySelectorAll('.doccenter-doc-details');
            const docListMain = document.getElementById('doccenter-doc-list-main');
            const viewToggleBtns = document.querySelectorAll('[data-docview]');

            // User modal elements
            const userOverlay = document.getElementById('doccenter-user-overlay');
            const userModalClose = document.getElementById('doccenter-user-modal-close');
            const userDisplay = document.getElementById('doccenter-user-display');
            const userIdInput = document.getElementById('doccenter-user-id');
            const userActionInput = document.getElementById('doccenter-user-action');
            const userDeleteConfirm = document.getElementById('doccenter-user-delete-confirm');
            const userForm = document.getElementById('doccenter-user-form');
            const userEditBtns = document.querySelectorAll('.doccenter-user-edit-btn');
            const userDeleteBtns = document.querySelectorAll('.doccenter-user-delete-btn');
            const userGroupsBox = document.getElementById('doccenter-user-groups-box');

            // Group & role controls
            const groupOverlay = document.getElementById('doccenter-group-overlay');
            const groupModalClose = document.getElementById('doccenter-group-modal-close');
            const groupCancelBtn = document.getElementById('doccenter-group-cancel');
            const groupNameInput = document.getElementById('doccenter-group-name');
            const groupIdInput = document.getElementById('doccenter-group-id');
            const groupUsersBox = document.getElementById('doccenter-group-users-box');
            const groupEditBtns = document.querySelectorAll('.doccenter-group-edit-btn');

            const userPromoteBtns = document.querySelectorAll('.doccenter-user-promote-btn');
            const userPromoteSuperBtns = document.querySelectorAll('.doccenter-user-promote-super-btn');
            const userDemoteSuperBtns = document.querySelectorAll('.doccenter-user-demote-super-btn');
            // ***************
            // UPLOAD VISIBILITY LOGIC
            // ***************
            const visibilitySelect = document.getElementById('doccenter_visibility');
            const groupsBox = document.getElementById('doccenter_groups_box');
            const usersBox = document.getElementById('doccenter_users_box');






            function updateUploadVisibility() {
                const val = visibilitySelect.value;

                // disable both by default
                groupsBox.style.opacity = "0.4";
                groupsBox.style.pointerEvents = "none";

                usersBox.style.opacity = "0.4";
                usersBox.style.pointerEvents = "none";

                // enable both only if custom
                if (val === "custom") {
                    groupsBox.style.opacity = "1";
                    groupsBox.style.pointerEvents = "auto";

                    usersBox.style.opacity = "1";
                    usersBox.style.pointerEvents = "auto";
                }
            }

            // attach event
            if (visibilitySelect) {
                visibilitySelect.addEventListener("change", updateUploadVisibility);
                updateUploadVisibility(); // run on page load
            }


            // Auto-hide notices after 3s
            setTimeout(function() {
                document.querySelectorAll('.doccenter-alert').forEach(function(el) {
                    el.remove();
                });
            }, 3000);

            function activateTab(tab) {
                navItems.forEach(function(item) {
                    item.classList.toggle('doccenter-nav-item-active', item.getAttribute('data-tab') === tab);
                });
                sections.forEach(function(sec) {
                    sec.classList.toggle('doccenter-section-active', sec.getAttribute('data-tab') === tab);
                });
            }


            // Startup tab from URL (?tab=...) or fall back to "overview"
            var params = new URLSearchParams(window.location.search);
            var startupTab = params.get('tab') || 'overview';
            activateTab(startupTab);

            // When clicking tabs, update ?tab= in URL without reloading
            navItems.forEach(function(item) {
                item.addEventListener('click', function() {
                    var tab = item.getAttribute('data-tab');
                    if (!tab) return;

                    var p = new URLSearchParams(window.location.search);
                    p.set('tab', tab);

                    history.replaceState(null, '', window.location.pathname + '?' + p.toString());
                    activateTab(tab);
                });
            });

            // Edit buttons -> add ?edit_doc=ID
            editButtons.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var id = btn.getAttribute('data-id');
                    if (!id) return;
                    var url = new URL(window.location.href);
                    url.searchParams.set('edit_doc', id);
                    window.location.href = url.toString();
                });
            });

            // Close edit overlay -> remove ?edit_doc
            if (editClose && editOverlay) {
                editClose.addEventListener('click', function() {
                    var url = new URL(window.location.href);
                    url.searchParams.delete('edit_doc');
                    window.location.href = url.toString();
                });
            }

            // Details modal
            function openModal(data) {
                if (!modalOverlay) return;
                modalTitle.textContent = data.title || 'Document details';
                var metaParts = [];
                if (data.date) metaParts.push('Modified: ' + data.date);
                if (data.expiry) metaParts.push('Expires: ' + data.expiry);
                if (data.uploadedBy) metaParts.push('Uploaded by: ' + data.uploadedBy);
                if (data.category) metaParts.push(data.category);
                if (data.tags) metaParts.push('Tags: ' + data.tags);
                modalMeta.textContent = metaParts.join(' · ');
                modalNotes.textContent = data.notes || '';
                if (data.url) {
                    modalView.href = data.url;
                    modalDownload.href = data.url;
                }
                modalOverlay.classList.add('doccenter-modal-active');
            }

            function closeModal() {
                if (!modalOverlay) return;
                modalOverlay.classList.remove('doccenter-modal-active');
            }

            detailButtons.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var data = {
                        title: btn.getAttribute('data-title') || '',
                        date: btn.getAttribute('data-date') || '',
                        expiry: btn.getAttribute('data-expiry') || '',
                        uploadedBy: btn.getAttribute('data-uploaded-by') || '',
                        category: btn.getAttribute('data-category') || '',
                        tags: btn.getAttribute('data-tags') || '',
                        notes: btn.getAttribute('data-notes') || '',
                        url: btn.getAttribute('data-url') || ''
                    };
                    openModal(data);
                });
            });

            if (modalClose && modalOverlay) {
                modalClose.addEventListener('click', closeModal);
                modalOverlay.addEventListener('click', function(e) {
                    if (e.target === modalOverlay) closeModal();
                });
            }

            // List / Grid toggle
            if (docListMain && viewToggleBtns.length) {
                viewToggleBtns.forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        var mode = btn.getAttribute('data-docview'); // 'list' or 'grid'
                        viewToggleBtns.forEach(function(b) {
                            b.classList.toggle('doccenter-docview-active', b === btn);
                        });
                        if (mode === 'grid') {
                            docListMain.classList.add('doccenter-doc-list-grid');
                        } else {
                            docListMain.classList.remove('doccenter-doc-list-grid');
                        }
                    });
                });
            }

            // USER MODAL LOGIC (ADMIN)
            function openUserModal(userId, userName, groupsCsv, deleteMode) {
                if (!userOverlay || !userDisplay || !userIdInput || !userActionInput) return;

                userDisplay.value = userName || '';
                userIdInput.value = userId || '';
                userActionInput.value = deleteMode ? 'delete_user' : 'edit_user';

                // Reset checkboxes
                if (userGroupsBox) {
                    const checkboxes = userGroupsBox.querySelectorAll('input[type="checkbox"]');
                    checkboxes.forEach(function(cb) {
                        cb.checked = false;
                    });

                    if (groupsCsv && !deleteMode) {
                        const parts = groupsCsv.split(',').map(function(x) {
                            return x.trim();
                        }).filter(Boolean);
                        checkboxes.forEach(function(cb) {
                            if (parts.indexOf(cb.value) !== -1) {
                                cb.checked = true;
                            }
                        });
                    }
                }

                userOverlay.classList.add('doccenter-user-overlay-active');
            }

            function closeUserModal() {
                if (!userOverlay) return;
                userOverlay.classList.remove('doccenter-user-overlay-active');
            }

            if (userEditBtns) {
                userEditBtns.forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        const id = btn.getAttribute('data-user-id');
                        const name = btn.getAttribute('data-user-name') || '';
                        const groups = btn.getAttribute('data-user-groups') || '';
                        openUserModal(id, name, groups, false);
                    });
                });
            }

            if (userDeleteBtns && userDeleteConfirm && userForm && userActionInput) {
                userDeleteBtns.forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        const id = btn.getAttribute('data-user-id');
                        const name = btn.getAttribute('data-user-name') || '';
                        if (!confirm('Delete user "' + name + '"? This cannot be undone.')) {
                            return;
                        }
                        // Open modal in delete mode so user sees confirmation + can cancel
                        openUserModal(id, name, '', true);
                    });
                });

                userDeleteConfirm.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (!confirm('Are you sure you want to permanently delete this user?')) {
                        return;
                    }
                    userActionInput.value = 'delete_user';
                    userForm.submit();
                });
            }


            // GROUP MODAL LOGIC (ADMIN)
            function openGroupModal(groupId, groupName, viewersCsv) {
                if (!groupOverlay || !groupNameInput || !groupIdInput || !groupUsersBox) return;

                groupNameInput.value = groupName || '';
                groupIdInput.value = groupId || '';

                // Reset all checkboxes
                const checkboxes = groupUsersBox.querySelectorAll('input[type="checkbox"]');
                checkboxes.forEach(function(cb) {
                    cb.checked = false;
                });

                if (viewersCsv) {
                    const ids = viewersCsv.split(',').map(function(v) {
                        return v.trim();
                    }).filter(Boolean);

                    checkboxes.forEach(function(cb) {
                        if (ids.indexOf(cb.value) !== -1) {
                            cb.checked = true;
                        }
                    });
                }

                groupOverlay.classList.add('doccenter-user-overlay-active');
            }

            function closeGroupModal() {
                if (!groupOverlay) return;
                groupOverlay.classList.remove('doccenter-user-overlay-active');
            }

            if (groupEditBtns && groupEditBtns.length && groupOverlay && groupUsersBox) {
                groupEditBtns.forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        const gid = btn.getAttribute('data-group-id');
                        const gname = btn.getAttribute('data-group-name') || '';
                        const viewers = btn.getAttribute('data-group-viewers') || '';
                        openGroupModal(gid, gname, viewers);
                    });
                });
            }

            const subfolderBtns = document.querySelectorAll('.doccenter-subfolder-btn');
            const newGroupParent = document.getElementById('new_group_parent');
            const newGroupName = document.getElementById('new_group_name');
            const newGroupParentHelp = document.getElementById('new_group_parent_help');

            if (subfolderBtns && subfolderBtns.length && newGroupParent && newGroupName) {
                subfolderBtns.forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        const parentId = btn.getAttribute('data-parent-id');
                        const parentName = btn.getAttribute('data-parent-name') || 'selected folder';
                        newGroupParent.value = parentId;
                        newGroupName.focus();
                        if (newGroupParentHelp) {
                            newGroupParentHelp.textContent = 'New folder will be created inside: ' + parentName;
                        }
                    });
                });
            }

            if (groupModalClose && groupOverlay) {
                groupModalClose.addEventListener('click', closeGroupModal);
            }
            if (groupCancelBtn && groupOverlay) {
                groupCancelBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    closeGroupModal();
                });
            }
            if (groupOverlay) {
                groupOverlay.addEventListener('click', function(e) {
                    if (e.target === groupOverlay) closeGroupModal();
                });
            }

            // PROMOTE VIEWER -> ADMIN (SUPER ADMIN ONLY)
            if (userPromoteBtns && userPromoteBtns.length && userForm && userIdInput && userActionInput) {
                userPromoteBtns.forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        const id = btn.getAttribute('data-user-id');
                        const name = btn.getAttribute('data-user-name') || '';
                        if (!confirm('Promote "' + name + '" to admin?')) {
                            return;
                        }
                        userIdInput.value = id;
                        userActionInput.value = 'promote_to_admin';
                        userForm.submit();
                    });
                });
            }

            // SUPER ADMIN: make/remove super admin
            if (userPromoteSuperBtns && userPromoteSuperBtns.length && userForm && userIdInput && userActionInput) {
                userPromoteSuperBtns.forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        const id = btn.getAttribute('data-user-id');
                        const name = btn.getAttribute('data-user-name') || '';
                        if (!confirm('Make "' + name + '" a super admin?')) {
                            return;
                        }
                        userIdInput.value = id;
                        userActionInput.value = 'make_super_admin';
                        userForm.submit();
                    });
                });
            }

            if (userDemoteSuperBtns && userDemoteSuperBtns.length && userForm && userIdInput && userActionInput) {
                userDemoteSuperBtns.forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        const id = btn.getAttribute('data-user-id');
                        const name = btn.getAttribute('data-user-name') || '';
                        if (!confirm('Remove super admin role from "' + name + '"?')) {
                            return;
                        }
                        userIdInput.value = id;
                        userActionInput.value = 'remove_super_admin';
                        userForm.submit();
                    });
                });
            }

            if (userModalClose && userOverlay) {
                userModalClose.addEventListener('click', closeUserModal);
                userOverlay.addEventListener('click', function(e) {
                    if (e.target === userOverlay) closeUserModal();
                });
            }

            // MEDIA PREVIEW MODAL
            const previewOverlay = document.getElementById('doccenter-preview-overlay');
            const previewClose = document.getElementById('doccenter-preview-close');
            const previewContent = document.getElementById('doccenter-preview-content');
            const previewTitle = document.getElementById('doccenter-preview-title');
            const previewMeta = document.getElementById('doccenter-preview-meta');
            const previewDownload = document.getElementById('doccenter-preview-download');
            const previewButtons = document.querySelectorAll('.doccenter-doc-preview');

            function openPreviewModal(data) {
                if (!previewOverlay || !previewContent) return;

                previewTitle.textContent = data.title || 'Preview';
                previewMeta.textContent = data.type ? data.type.toUpperCase() + ' · ' + data.ext.toUpperCase() : '';
                if (previewDownload) previewDownload.href = data.url || '#';

                previewContent.innerHTML = '';

                if (data.type === 'image') {
                    const img = document.createElement('img');
                    img.src = data.url;
                    img.style.cssText = 'max-width:100%;max-height:75vh;display:block;margin:0 auto;';
                    img.alt = data.title || 'Preview';
                    previewContent.appendChild(img);
                } else if (data.type === 'video') {
                    const video = document.createElement('video');
                    video.src = data.url;
                    video.controls = true;
                    video.style.cssText = 'max-width:100%;max-height:75vh;display:block;';
                    video.setAttribute('playsinline', '');
                    previewContent.appendChild(video);
                } else if (data.type === 'audio') {
                    const audio = document.createElement('audio');
                    audio.src = data.url;
                    audio.controls = true;
                    audio.style.cssText = 'width:100%;max-width:500px;';
                    previewContent.appendChild(audio);
                }

                previewOverlay.classList.add('doccenter-modal-active');
            }

            function closePreviewModal() {
                if (!previewOverlay) return;
                previewOverlay.classList.remove('doccenter-modal-active');
                // Stop any playing media
                if (previewContent) {
                    const media = previewContent.querySelector('video, audio');
                    if (media) {
                        media.pause();
                        media.src = '';
                    }
                    previewContent.innerHTML = '';
                }
            }

            previewButtons.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    const data = {
                        title: btn.getAttribute('data-title') || '',
                        url: btn.getAttribute('data-url') || '',
                        type: btn.getAttribute('data-type') || '',
                        ext: btn.getAttribute('data-ext') || ''
                    };
                    openPreviewModal(data);
                });
            });

            if (previewClose && previewOverlay) {
                previewClose.addEventListener('click', closePreviewModal);
                previewOverlay.addEventListener('click', function(e) {
                    if (e.target === previewOverlay) closePreviewModal();
                });
            }

            // Escape key closes all modals
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeModal();
                    closePreviewModal();
                    closeUserModal();
                    closeGroupModal();
                    if (editOverlay) editOverlay.classList.remove('doccenter-edit-overlay-active');
                }
            });
        })();
        document.addEventListener("click", function(e) {
            if (e.target.classList.contains("doccenter-demote-admin-btn")) {

                let userId = e.target.getAttribute("data-user-id");

                if (!confirm("Are you sure you want to demote this admin to viewer?")) return;

                let form = new FormData();
                form.append("action", "doccenter_demote_admin");
                form.append("user_id", userId);

                fetch(doccenter_ajax.url, {
                        method: "POST",
                        credentials: "same-origin",
                        body: form
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            alert("User demoted successfully.");
                            location.reload();
                        } else {
                            alert(data.data || "Error.");
                        }
                    });
            }
        });
        document.addEventListener("click", function(e) {
            if (e.target.id === "dc_generate_password") {

                function generatePassword(len = 12) {
                    const chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()";
                    let pwd = "";
                    for (let i = 0; i < len; i++) {
                        pwd += chars.charAt(Math.floor(Math.random() * chars.length));
                    }
                    return pwd;
                }

                const newPass = generatePassword();
                document.getElementById("dc_new_password").value = newPass;

                // Copy to clipboard for convenience
                navigator.clipboard.writeText(newPass).catch(() => {});
                alert("Password generated & copied to clipboard:\n\n" + newPass);
            }

        });
        document.addEventListener("click", function(e) {
            const card = e.target.closest(".doccenter-folder-card");
            if (!card) return;

            const content = card.nextElementSibling;
            if (!content) return;

            content.style.display = content.style.display === "block" ? "none" : "block";
        });

        (function () {
    const dropZone = document.getElementById('doccenter-drop-zone');
    const fileInput = document.getElementById('doccenter_file');
    const dropLabel = document.getElementById('doccenter-drop-label');
    const metaContainer = document.getElementById('doccenter_file_metadata');

    if (!dropZone || !fileInput || !metaContainer) return;

    // Drag visual feedback
    ['dragenter', 'dragover'].forEach(function (evt) {
        dropZone.addEventListener(evt, function (e) {
            e.preventDefault();
            dropZone.style.background = '#ede9fe';
            dropZone.style.borderColor = '#7c3aed';
        });
    });
    ['dragleave', 'drop'].forEach(function (evt) {
        dropZone.addEventListener(evt, function (e) {
            e.preventDefault();
            dropZone.style.background = '#faf5ff';
            dropZone.style.borderColor = '#c4b5fd';
        });
    });

    // Drop handler
    dropZone.addEventListener('drop', function (e) {
        e.preventDefault();
        const dt = e.dataTransfer;
        if (dt && dt.files && dt.files.length) {
            fileInput.files = dt.files;
            renderFileRows(dt.files);
        }
    });

    // Click-to-browse handler
    fileInput.addEventListener('change', function () {
        renderFileRows(fileInput.files);
    });

    function renderFileRows(files) {
        metaContainer.innerHTML = '';
        if (!files || !files.length) return;

        dropLabel.textContent = files.length + ' file' + (files.length > 1 ? 's' : '') + ' selected';

        Array.from(files).forEach(function (file, i) {
            const row = document.createElement('div');
            row.style.cssText = 'display:flex;align-items:center;gap:10px;padding:8px 10px;border:1px solid #e5e7eb;border-radius:10px;background:#f9fafb;margin-bottom:6px;';

            // File name label
            const nameEl = document.createElement('div');
            nameEl.style.cssText = 'flex:1;font-size:12px;color:#374151;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;';
            nameEl.textContent = file.name;

            // Title input
            const titleInput = document.createElement('input');
            titleInput.type = 'text';
            titleInput.name = 'doccenter_title[]';
            titleInput.placeholder = 'Title (optional)';
            titleInput.style.cssText = 'width:160px;border-radius:8px;border:1px solid #d1d5db;padding:5px 8px;font-size:12px;';

            // Unique tag input (manual)
            const tagInput = document.createElement('input');
            tagInput.type = 'text';
            tagInput.name = 'doccenter_unique_tag[]';
            tagInput.placeholder = 'Unique tag (required)';
            tagInput.required = true;
            tagInput.pattern = '[A-Za-z0-9]+';
            tagInput.title = 'Letters and numbers only';
            tagInput.style.cssText = 'width:140px;border-radius:8px;border:1px solid #d1d5db;padding:5px 8px;font-size:12px;';

            row.appendChild(nameEl);
            row.appendChild(titleInput);
            row.appendChild(tagInput);
            metaContainer.appendChild(row);
        });

        // Add column headers above first row
        const header = document.createElement('div');
        header.style.cssText = 'display:flex;gap:10px;font-size:11px;color:#9ca3af;padding:0 10px;margin-bottom:2px;';
        header.innerHTML = '<div style="flex:1">File</div><div style="width:160px">Title</div><div style="width:140px">Unique tag</div>';
        metaContainer.insertBefore(header, metaContainer.firstChild);
    }

                // INLINE UPLOAD TOGGLE
            const inlineUploadToggle = document.getElementById('doccenter-inline-upload-toggle');
            const inlineUploadForm = document.getElementById('doccenter-inline-upload-form');
            const inlineUploadCancel = document.getElementById('doccenter-inline-upload-cancel');

            if (inlineUploadToggle && inlineUploadForm) {
                inlineUploadToggle.addEventListener('click', function() {
                    inlineUploadForm.style.display = inlineUploadForm.style.display === 'none' ? 'block' : 'none';
                });
            }
            if (inlineUploadCancel && inlineUploadForm) {
                inlineUploadCancel.addEventListener('click', function() {
                    inlineUploadForm.style.display = 'none';
                });
            }

            // INLINE DROP ZONE
            (function() {
                const dropZone = document.getElementById('doccenter-inline-drop-zone');
                const fileInput = document.getElementById('doccenter_inline_file');
                const dropLabel = document.getElementById('doccenter-inline-drop-label');
                const metaContainer = document.getElementById('doccenter_inline_file_metadata');

                if (!dropZone || !fileInput || !metaContainer) return;

                ['dragenter', 'dragover'].forEach(function(evt) {
                    dropZone.addEventListener(evt, function(e) {
                        e.preventDefault();
                        dropZone.style.background = '#ede9fe';
                        dropZone.style.borderColor = '#7c3aed';
                    });
                });
                ['dragleave', 'drop'].forEach(function(evt) {
                    dropZone.addEventListener(evt, function(e) {
                        e.preventDefault();
                        dropZone.style.background = '#faf5ff';
                        dropZone.style.borderColor = '#c4b5fd';
                    });
                });

                dropZone.addEventListener('drop', function(e) {
                    e.preventDefault();
                    const dt = e.dataTransfer;
                    if (dt && dt.files && dt.files.length) {
                        fileInput.files = dt.files;
                        renderInlineFileRows(dt.files);
                    }
                });

                fileInput.addEventListener('change', function() {
                    renderInlineFileRows(fileInput.files);
                });

                function renderInlineFileRows(files) {
                    metaContainer.innerHTML = '';
                    if (!files || !files.length) return;

                    dropLabel.textContent = files.length + ' file' + (files.length > 1 ? 's' : '') + ' selected';

                    Array.from(files).forEach(function(file, i) {
                        const row = document.createElement('div');
                        row.style.cssText = 'display:flex;align-items:center;gap:10px;padding:8px 10px;border:1px solid #e5e7eb;border-radius:10px;background:#f9fafb;margin-bottom:6px;';

                        const nameEl = document.createElement('div');
                        nameEl.style.cssText = 'flex:1;font-size:12px;color:#374151;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;';
                        nameEl.textContent = file.name;

                        const titleInput = document.createElement('input');
                        titleInput.type = 'text';
                        titleInput.name = 'doccenter_title[]';
                        titleInput.placeholder = 'Title (optional)';
                        titleInput.style.cssText = 'width:160px;border-radius:8px;border:1px solid #d1d5db;padding:5px 8px;font-size:12px;';

                        const tagInput = document.createElement('input');
                        tagInput.type = 'text';
                        tagInput.name = 'doccenter_unique_tag[]';
                        tagInput.placeholder = 'Unique tag (required)';
                        tagInput.required = true;
                        tagInput.pattern = '[A-Za-z0-9]+';
                        tagInput.title = 'Letters and numbers only';
                        tagInput.style.cssText = 'width:140px;border-radius:8px;border:1px solid #d1d5db;padding:5px 8px;font-size:12px;';

                        row.appendChild(nameEl);
                        row.appendChild(titleInput);
                        row.appendChild(tagInput);
                        metaContainer.appendChild(row);
                    });

                    const header = document.createElement('div');
                    header.style.cssText = 'display:flex;gap:10px;font-size:11px;color:#9ca3af;padding:0 10px;margin-bottom:2px;';
                    header.innerHTML = '<div style="flex:1">File</div><div style="width:160px">Title</div><div style="width:140px">Unique tag</div>';
                    metaContainer.insertBefore(header, metaContainer.firstChild);
                }
            })();
})();
    </script>
<?php
    return ob_get_clean();
}
add_shortcode('doccenter_dashboard', 'doccenter_dashboard_shortcode');

