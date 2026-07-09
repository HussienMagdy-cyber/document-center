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
