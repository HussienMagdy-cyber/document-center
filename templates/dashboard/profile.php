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
