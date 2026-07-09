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
