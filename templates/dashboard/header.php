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

