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
