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
