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
