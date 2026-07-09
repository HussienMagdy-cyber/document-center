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
