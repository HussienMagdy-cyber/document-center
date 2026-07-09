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
