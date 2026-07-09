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
