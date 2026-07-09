        (function() {
            const root = document.getElementById('doccenter-root');
            const navItems = document.querySelectorAll('.doccenter-nav-item[data-tab]');
            const sections = document.querySelectorAll('.doccenter-section');
            const editButtons = document.querySelectorAll('.doccenter-doc-edit');
            const editOverlay = document.getElementById('doccenter-edit-overlay');
            const editClose = document.getElementById('doccenter-edit-close');
            const modalOverlay = document.getElementById('doccenter-modal-overlay');
            const modalClose = document.getElementById('doccenter-modal-close');
            const modalTitle = document.getElementById('doccenter-modal-title');
            const modalMeta = document.getElementById('doccenter-modal-meta');
            const modalNotes = document.getElementById('doccenter-modal-notes');
            const modalView = document.getElementById('doccenter-modal-view');
            const modalDownload = document.getElementById('doccenter-modal-download');
            const detailButtons = document.querySelectorAll('.doccenter-doc-details');
            const docListMain = document.getElementById('doccenter-doc-list-main');
            const viewToggleBtns = document.querySelectorAll('[data-docview]');

            // User modal elements
            const userOverlay = document.getElementById('doccenter-user-overlay');
            const userModalClose = document.getElementById('doccenter-user-modal-close');
            const userDisplay = document.getElementById('doccenter-user-display');
            const userIdInput = document.getElementById('doccenter-user-id');
            const userActionInput = document.getElementById('doccenter-user-action');
            const userDeleteConfirm = document.getElementById('doccenter-user-delete-confirm');
            const userForm = document.getElementById('doccenter-user-form');
            const userEditBtns = document.querySelectorAll('.doccenter-user-edit-btn');
            const userDeleteBtns = document.querySelectorAll('.doccenter-user-delete-btn');
            const userGroupsBox = document.getElementById('doccenter-user-groups-box');

            // Group & role controls
            const groupOverlay = document.getElementById('doccenter-group-overlay');
            const groupModalClose = document.getElementById('doccenter-group-modal-close');
            const groupCancelBtn = document.getElementById('doccenter-group-cancel');
            const groupNameInput = document.getElementById('doccenter-group-name');
            const groupIdInput = document.getElementById('doccenter-group-id');
            const groupUsersBox = document.getElementById('doccenter-group-users-box');
            const groupEditBtns = document.querySelectorAll('.doccenter-group-edit-btn');

            const userPromoteBtns = document.querySelectorAll('.doccenter-user-promote-btn');
            const userPromoteSuperBtns = document.querySelectorAll('.doccenter-user-promote-super-btn');
            const userDemoteSuperBtns = document.querySelectorAll('.doccenter-user-demote-super-btn');
            // ***************
            // UPLOAD VISIBILITY LOGIC
            // ***************
            const visibilitySelect = document.getElementById('doccenter_visibility');
            const groupsBox = document.getElementById('doccenter_groups_box');
            const usersBox = document.getElementById('doccenter_users_box');






            function updateUploadVisibility() {
                const val = visibilitySelect.value;

                // disable both by default
                groupsBox.style.opacity = "0.4";
                groupsBox.style.pointerEvents = "none";

                usersBox.style.opacity = "0.4";
                usersBox.style.pointerEvents = "none";

                // enable both only if custom
                if (val === "custom") {
                    groupsBox.style.opacity = "1";
                    groupsBox.style.pointerEvents = "auto";

                    usersBox.style.opacity = "1";
                    usersBox.style.pointerEvents = "auto";
                }
            }

            // attach event
            if (visibilitySelect) {
                visibilitySelect.addEventListener("change", updateUploadVisibility);
                updateUploadVisibility(); // run on page load
            }


            // Auto-hide notices after 3s
            setTimeout(function() {
                document.querySelectorAll('.doccenter-alert').forEach(function(el) {
                    el.remove();
                });
            }, 3000);

            function activateTab(tab) {
                navItems.forEach(function(item) {
                    item.classList.toggle('doccenter-nav-item-active', item.getAttribute('data-tab') === tab);
                });
                sections.forEach(function(sec) {
                    sec.classList.toggle('doccenter-section-active', sec.getAttribute('data-tab') === tab);
                });
            }


            // Startup tab from URL (?tab=...) or fall back to "overview"
            var params = new URLSearchParams(window.location.search);
            var startupTab = params.get('tab') || 'overview';
            activateTab(startupTab);

            // When clicking tabs, update ?tab= in URL without reloading
            navItems.forEach(function(item) {
                item.addEventListener('click', function() {
                    var tab = item.getAttribute('data-tab');
                    if (!tab) return;

                    var p = new URLSearchParams(window.location.search);
                    p.set('tab', tab);

                    history.replaceState(null, '', window.location.pathname + '?' + p.toString());
                    activateTab(tab);
                });
            });

            // Edit buttons -> add ?edit_doc=ID
            editButtons.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var id = btn.getAttribute('data-id');
                    if (!id) return;
                    var url = new URL(window.location.href);
                    url.searchParams.set('edit_doc', id);
                    window.location.href = url.toString();
                });
            });

            // Close edit overlay -> remove ?edit_doc
            if (editClose && editOverlay) {
                editClose.addEventListener('click', function() {
                    var url = new URL(window.location.href);
                    url.searchParams.delete('edit_doc');
                    window.location.href = url.toString();
                });
            }

            // Details modal
            function openModal(data) {
                if (!modalOverlay) return;
                modalTitle.textContent = data.title || 'Document details';
                var metaParts = [];
                if (data.date) metaParts.push('Modified: ' + data.date);
                if (data.expiry) metaParts.push('Expires: ' + data.expiry);
                if (data.uploadedBy) metaParts.push('Uploaded by: ' + data.uploadedBy);
                if (data.category) metaParts.push(data.category);
                if (data.tags) metaParts.push('Tags: ' + data.tags);
                modalMeta.textContent = metaParts.join(' · ');
                modalNotes.textContent = data.notes || '';
                if (data.url) {
                    modalView.href = data.url;
                    modalDownload.href = data.url;
                }
                modalOverlay.classList.add('doccenter-modal-active');
            }

            function closeModal() {
                if (!modalOverlay) return;
                modalOverlay.classList.remove('doccenter-modal-active');
            }

            detailButtons.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var data = {
                        title: btn.getAttribute('data-title') || '',
                        date: btn.getAttribute('data-date') || '',
                        expiry: btn.getAttribute('data-expiry') || '',
                        uploadedBy: btn.getAttribute('data-uploaded-by') || '',
                        category: btn.getAttribute('data-category') || '',
                        tags: btn.getAttribute('data-tags') || '',
                        notes: btn.getAttribute('data-notes') || '',
                        url: btn.getAttribute('data-url') || ''
                    };
                    openModal(data);
                });
            });

            if (modalClose && modalOverlay) {
                modalClose.addEventListener('click', closeModal);
                modalOverlay.addEventListener('click', function(e) {
                    if (e.target === modalOverlay) closeModal();
                });
            }

            // List / Grid toggle
            if (docListMain && viewToggleBtns.length) {
                viewToggleBtns.forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        var mode = btn.getAttribute('data-docview'); // 'list' or 'grid'
                        viewToggleBtns.forEach(function(b) {
                            b.classList.toggle('doccenter-docview-active', b === btn);
                        });
                        if (mode === 'grid') {
                            docListMain.classList.add('doccenter-doc-list-grid');
                        } else {
                            docListMain.classList.remove('doccenter-doc-list-grid');
                        }
                    });
                });
            }

            // USER MODAL LOGIC (ADMIN)
            function openUserModal(userId, userName, groupsCsv, deleteMode) {
                if (!userOverlay || !userDisplay || !userIdInput || !userActionInput) return;

                userDisplay.value = userName || '';
                userIdInput.value = userId || '';
                userActionInput.value = deleteMode ? 'delete_user' : 'edit_user';

                // Reset checkboxes
                if (userGroupsBox) {
                    const checkboxes = userGroupsBox.querySelectorAll('input[type="checkbox"]');
                    checkboxes.forEach(function(cb) {
                        cb.checked = false;
                    });

                    if (groupsCsv && !deleteMode) {
                        const parts = groupsCsv.split(',').map(function(x) {
                            return x.trim();
                        }).filter(Boolean);
                        checkboxes.forEach(function(cb) {
                            if (parts.indexOf(cb.value) !== -1) {
                                cb.checked = true;
                            }
                        });
                    }
                }

                userOverlay.classList.add('doccenter-user-overlay-active');
            }

            function closeUserModal() {
                if (!userOverlay) return;
                userOverlay.classList.remove('doccenter-user-overlay-active');
            }

            if (userEditBtns) {
                userEditBtns.forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        const id = btn.getAttribute('data-user-id');
                        const name = btn.getAttribute('data-user-name') || '';
                        const groups = btn.getAttribute('data-user-groups') || '';
                        openUserModal(id, name, groups, false);
                    });
                });
            }

            if (userDeleteBtns && userDeleteConfirm && userForm && userActionInput) {
                userDeleteBtns.forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        const id = btn.getAttribute('data-user-id');
                        const name = btn.getAttribute('data-user-name') || '';
                        if (!confirm('Delete user "' + name + '"? This cannot be undone.')) {
                            return;
                        }
                        // Open modal in delete mode so user sees confirmation + can cancel
                        openUserModal(id, name, '', true);
                    });
                });

                userDeleteConfirm.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (!confirm('Are you sure you want to permanently delete this user?')) {
                        return;
                    }
                    userActionInput.value = 'delete_user';
                    userForm.submit();
                });
            }


            // GROUP MODAL LOGIC (ADMIN)
            function openGroupModal(groupId, groupName, viewersCsv) {
                if (!groupOverlay || !groupNameInput || !groupIdInput || !groupUsersBox) return;

                groupNameInput.value = groupName || '';
                groupIdInput.value = groupId || '';

                // Reset all checkboxes
                const checkboxes = groupUsersBox.querySelectorAll('input[type="checkbox"]');
                checkboxes.forEach(function(cb) {
                    cb.checked = false;
                });

                if (viewersCsv) {
                    const ids = viewersCsv.split(',').map(function(v) {
                        return v.trim();
                    }).filter(Boolean);

                    checkboxes.forEach(function(cb) {
                        if (ids.indexOf(cb.value) !== -1) {
                            cb.checked = true;
                        }
                    });
                }

                groupOverlay.classList.add('doccenter-user-overlay-active');
            }

            function closeGroupModal() {
                if (!groupOverlay) return;
                groupOverlay.classList.remove('doccenter-user-overlay-active');
            }

            if (groupEditBtns && groupEditBtns.length && groupOverlay && groupUsersBox) {
                groupEditBtns.forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        const gid = btn.getAttribute('data-group-id');
                        const gname = btn.getAttribute('data-group-name') || '';
                        const viewers = btn.getAttribute('data-group-viewers') || '';
                        openGroupModal(gid, gname, viewers);
                    });
                });
            }

            const subfolderBtns = document.querySelectorAll('.doccenter-subfolder-btn');
            const newGroupParent = document.getElementById('new_group_parent');
            const newGroupName = document.getElementById('new_group_name');
            const newGroupParentHelp = document.getElementById('new_group_parent_help');

            if (subfolderBtns && subfolderBtns.length && newGroupParent && newGroupName) {
                subfolderBtns.forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        const parentId = btn.getAttribute('data-parent-id');
                        const parentName = btn.getAttribute('data-parent-name') || 'selected folder';
                        newGroupParent.value = parentId;
                        newGroupName.focus();
                        if (newGroupParentHelp) {
                            newGroupParentHelp.textContent = 'New folder will be created inside: ' + parentName;
                        }
                    });
                });
            }

            if (groupModalClose && groupOverlay) {
                groupModalClose.addEventListener('click', closeGroupModal);
            }
            if (groupCancelBtn && groupOverlay) {
                groupCancelBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    closeGroupModal();
                });
            }
            if (groupOverlay) {
                groupOverlay.addEventListener('click', function(e) {
                    if (e.target === groupOverlay) closeGroupModal();
                });
            }

            // PROMOTE VIEWER -> ADMIN (SUPER ADMIN ONLY)
            if (userPromoteBtns && userPromoteBtns.length && userForm && userIdInput && userActionInput) {
                userPromoteBtns.forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        const id = btn.getAttribute('data-user-id');
                        const name = btn.getAttribute('data-user-name') || '';
                        if (!confirm('Promote "' + name + '" to admin?')) {
                            return;
                        }
                        userIdInput.value = id;
                        userActionInput.value = 'promote_to_admin';
                        userForm.submit();
                    });
                });
            }

            // SUPER ADMIN: make/remove super admin
            if (userPromoteSuperBtns && userPromoteSuperBtns.length && userForm && userIdInput && userActionInput) {
                userPromoteSuperBtns.forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        const id = btn.getAttribute('data-user-id');
                        const name = btn.getAttribute('data-user-name') || '';
                        if (!confirm('Make "' + name + '" a super admin?')) {
                            return;
                        }
                        userIdInput.value = id;
                        userActionInput.value = 'make_super_admin';
                        userForm.submit();
                    });
                });
            }

            if (userDemoteSuperBtns && userDemoteSuperBtns.length && userForm && userIdInput && userActionInput) {
                userDemoteSuperBtns.forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        const id = btn.getAttribute('data-user-id');
                        const name = btn.getAttribute('data-user-name') || '';
                        if (!confirm('Remove super admin role from "' + name + '"?')) {
                            return;
                        }
                        userIdInput.value = id;
                        userActionInput.value = 'remove_super_admin';
                        userForm.submit();
                    });
                });
            }

            if (userModalClose && userOverlay) {
                userModalClose.addEventListener('click', closeUserModal);
                userOverlay.addEventListener('click', function(e) {
                    if (e.target === userOverlay) closeUserModal();
                });
            }

            // MEDIA PREVIEW MODAL
            const previewOverlay = document.getElementById('doccenter-preview-overlay');
            const previewClose = document.getElementById('doccenter-preview-close');
            const previewContent = document.getElementById('doccenter-preview-content');
            const previewTitle = document.getElementById('doccenter-preview-title');
            const previewMeta = document.getElementById('doccenter-preview-meta');
            const previewDownload = document.getElementById('doccenter-preview-download');
            const previewButtons = document.querySelectorAll('.doccenter-doc-preview');

            function openPreviewModal(data) {
                if (!previewOverlay || !previewContent) return;

                previewTitle.textContent = data.title || 'Preview';
                previewMeta.textContent = data.type ? data.type.toUpperCase() + ' · ' + data.ext.toUpperCase() : '';
                if (previewDownload) previewDownload.href = data.url || '#';

                previewContent.innerHTML = '';

                if (data.type === 'image') {
                    const img = document.createElement('img');
                    img.src = data.url;
                    img.style.cssText = 'max-width:100%;max-height:75vh;display:block;margin:0 auto;';
                    img.alt = data.title || 'Preview';
                    previewContent.appendChild(img);
                } else if (data.type === 'video') {
                    const video = document.createElement('video');
                    video.src = data.url;
                    video.controls = true;
                    video.style.cssText = 'max-width:100%;max-height:75vh;display:block;';
                    video.setAttribute('playsinline', '');
                    previewContent.appendChild(video);
                } else if (data.type === 'audio') {
                    const audio = document.createElement('audio');
                    audio.src = data.url;
                    audio.controls = true;
                    audio.style.cssText = 'width:100%;max-width:500px;';
                    previewContent.appendChild(audio);
                }

                previewOverlay.classList.add('doccenter-modal-active');
            }

            function closePreviewModal() {
                if (!previewOverlay) return;
                previewOverlay.classList.remove('doccenter-modal-active');
                // Stop any playing media
                if (previewContent) {
                    const media = previewContent.querySelector('video, audio');
                    if (media) {
                        media.pause();
                        media.src = '';
                    }
                    previewContent.innerHTML = '';
                }
            }

            previewButtons.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    const data = {
                        title: btn.getAttribute('data-title') || '',
                        url: btn.getAttribute('data-url') || '',
                        type: btn.getAttribute('data-type') || '',
                        ext: btn.getAttribute('data-ext') || ''
                    };
                    openPreviewModal(data);
                });
            });

            if (previewClose && previewOverlay) {
                previewClose.addEventListener('click', closePreviewModal);
                previewOverlay.addEventListener('click', function(e) {
                    if (e.target === previewOverlay) closePreviewModal();
                });
            }

            // Escape key closes all modals
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeModal();
                    closePreviewModal();
                    closeUserModal();
                    closeGroupModal();
                    if (editOverlay) editOverlay.classList.remove('doccenter-edit-overlay-active');
                }
            });
        })();
        document.addEventListener("click", function(e) {
            if (e.target.classList.contains("doccenter-demote-admin-btn")) {

                let userId = e.target.getAttribute("data-user-id");

                if (!confirm("Are you sure you want to demote this admin to viewer?")) return;

                let form = new FormData();
                form.append("action", "doccenter_demote_admin");
                form.append("user_id", userId);

                fetch(doccenter_ajax.url, {
                        method: "POST",
                        credentials: "same-origin",
                        body: form
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            alert("User demoted successfully.");
                            location.reload();
                        } else {
                            alert(data.data || "Error.");
                        }
                    });
            }
        });
        document.addEventListener("click", function(e) {
            if (e.target.id === "dc_generate_password") {

                function generatePassword(len = 12) {
                    const chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()";
                    let pwd = "";
                    for (let i = 0; i < len; i++) {
                        pwd += chars.charAt(Math.floor(Math.random() * chars.length));
                    }
                    return pwd;
                }

                const newPass = generatePassword();
                document.getElementById("dc_new_password").value = newPass;

                // Copy to clipboard for convenience
                navigator.clipboard.writeText(newPass).catch(() => {});
                alert("Password generated & copied to clipboard:\n\n" + newPass);
            }

        });
        document.addEventListener("click", function(e) {
            const card = e.target.closest(".doccenter-folder-card");
            if (!card) return;

            const content = card.nextElementSibling;
            if (!content) return;

            content.style.display = content.style.display === "block" ? "none" : "block";
        });

        (function () {
    const dropZone = document.getElementById('doccenter-drop-zone');
    const fileInput = document.getElementById('doccenter_file');
    const dropLabel = document.getElementById('doccenter-drop-label');
    const metaContainer = document.getElementById('doccenter_file_metadata');

    if (!dropZone || !fileInput || !metaContainer) return;

    // Drag visual feedback
    ['dragenter', 'dragover'].forEach(function (evt) {
        dropZone.addEventListener(evt, function (e) {
            e.preventDefault();
            dropZone.style.background = '#ede9fe';
            dropZone.style.borderColor = '#7c3aed';
        });
    });
    ['dragleave', 'drop'].forEach(function (evt) {
        dropZone.addEventListener(evt, function (e) {
            e.preventDefault();
            dropZone.style.background = '#faf5ff';
            dropZone.style.borderColor = '#c4b5fd';
        });
    });

    // Drop handler
    dropZone.addEventListener('drop', function (e) {
        e.preventDefault();
        const dt = e.dataTransfer;
        if (dt && dt.files && dt.files.length) {
            fileInput.files = dt.files;
            renderFileRows(dt.files);
        }
    });

    // Click-to-browse handler
    fileInput.addEventListener('change', function () {
        renderFileRows(fileInput.files);
    });

    function renderFileRows(files) {
        metaContainer.innerHTML = '';
        if (!files || !files.length) return;

        dropLabel.textContent = files.length + ' file' + (files.length > 1 ? 's' : '') + ' selected';

        Array.from(files).forEach(function (file, i) {
            const row = document.createElement('div');
            row.style.cssText = 'display:flex;align-items:center;gap:10px;padding:8px 10px;border:1px solid #e5e7eb;border-radius:10px;background:#f9fafb;margin-bottom:6px;';

            // File name label
            const nameEl = document.createElement('div');
            nameEl.style.cssText = 'flex:1;font-size:12px;color:#374151;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;';
            nameEl.textContent = file.name;

            // Title input
            const titleInput = document.createElement('input');
            titleInput.type = 'text';
            titleInput.name = 'doccenter_title[]';
            titleInput.placeholder = 'Title (optional)';
            titleInput.style.cssText = 'width:160px;border-radius:8px;border:1px solid #d1d5db;padding:5px 8px;font-size:12px;';

            // Unique tag input (manual)
            const tagInput = document.createElement('input');
            tagInput.type = 'text';
            tagInput.name = 'doccenter_unique_tag[]';
            tagInput.placeholder = 'Unique tag (required)';
            tagInput.required = true;
            tagInput.pattern = '[A-Za-z0-9]+';
            tagInput.title = 'Letters and numbers only';
            tagInput.style.cssText = 'width:140px;border-radius:8px;border:1px solid #d1d5db;padding:5px 8px;font-size:12px;';

            row.appendChild(nameEl);
            row.appendChild(titleInput);
            row.appendChild(tagInput);
            metaContainer.appendChild(row);
        });

        // Add column headers above first row
        const header = document.createElement('div');
        header.style.cssText = 'display:flex;gap:10px;font-size:11px;color:#9ca3af;padding:0 10px;margin-bottom:2px;';
        header.innerHTML = '<div style="flex:1">File</div><div style="width:160px">Title</div><div style="width:140px">Unique tag</div>';
        metaContainer.insertBefore(header, metaContainer.firstChild);
    }

                // INLINE UPLOAD TOGGLE
            const inlineUploadToggle = document.getElementById('doccenter-inline-upload-toggle');
            const inlineUploadForm = document.getElementById('doccenter-inline-upload-form');
            const inlineUploadCancel = document.getElementById('doccenter-inline-upload-cancel');

            if (inlineUploadToggle && inlineUploadForm) {
                inlineUploadToggle.addEventListener('click', function() {
                    inlineUploadForm.style.display = inlineUploadForm.style.display === 'none' ? 'block' : 'none';
                });
            }
            if (inlineUploadCancel && inlineUploadForm) {
                inlineUploadCancel.addEventListener('click', function() {
                    inlineUploadForm.style.display = 'none';
                });
            }

            // INLINE DROP ZONE
            (function() {
                const dropZone = document.getElementById('doccenter-inline-drop-zone');
                const fileInput = document.getElementById('doccenter_inline_file');
                const dropLabel = document.getElementById('doccenter-inline-drop-label');
                const metaContainer = document.getElementById('doccenter_inline_file_metadata');

                if (!dropZone || !fileInput || !metaContainer) return;

                ['dragenter', 'dragover'].forEach(function(evt) {
                    dropZone.addEventListener(evt, function(e) {
                        e.preventDefault();
                        dropZone.style.background = '#ede9fe';
                        dropZone.style.borderColor = '#7c3aed';
                    });
                });
                ['dragleave', 'drop'].forEach(function(evt) {
                    dropZone.addEventListener(evt, function(e) {
                        e.preventDefault();
                        dropZone.style.background = '#faf5ff';
                        dropZone.style.borderColor = '#c4b5fd';
                    });
                });

                dropZone.addEventListener('drop', function(e) {
                    e.preventDefault();
                    const dt = e.dataTransfer;
                    if (dt && dt.files && dt.files.length) {
                        fileInput.files = dt.files;
                        renderInlineFileRows(dt.files);
                    }
                });

                fileInput.addEventListener('change', function() {
                    renderInlineFileRows(fileInput.files);
                });

                function renderInlineFileRows(files) {
                    metaContainer.innerHTML = '';
                    if (!files || !files.length) return;

                    dropLabel.textContent = files.length + ' file' + (files.length > 1 ? 's' : '') + ' selected';

                    Array.from(files).forEach(function(file, i) {
                        const row = document.createElement('div');
                        row.style.cssText = 'display:flex;align-items:center;gap:10px;padding:8px 10px;border:1px solid #e5e7eb;border-radius:10px;background:#f9fafb;margin-bottom:6px;';

                        const nameEl = document.createElement('div');
                        nameEl.style.cssText = 'flex:1;font-size:12px;color:#374151;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;';
                        nameEl.textContent = file.name;

                        const titleInput = document.createElement('input');
                        titleInput.type = 'text';
                        titleInput.name = 'doccenter_title[]';
                        titleInput.placeholder = 'Title (optional)';
                        titleInput.style.cssText = 'width:160px;border-radius:8px;border:1px solid #d1d5db;padding:5px 8px;font-size:12px;';

                        const tagInput = document.createElement('input');
                        tagInput.type = 'text';
                        tagInput.name = 'doccenter_unique_tag[]';
                        tagInput.placeholder = 'Unique tag (required)';
                        tagInput.required = true;
                        tagInput.pattern = '[A-Za-z0-9]+';
                        tagInput.title = 'Letters and numbers only';
                        tagInput.style.cssText = 'width:140px;border-radius:8px;border:1px solid #d1d5db;padding:5px 8px;font-size:12px;';

                        row.appendChild(nameEl);
                        row.appendChild(titleInput);
                        row.appendChild(tagInput);
                        metaContainer.appendChild(row);
                    });

                    const header = document.createElement('div');
                    header.style.cssText = 'display:flex;gap:10px;font-size:11px;color:#9ca3af;padding:0 10px;margin-bottom:2px;';
                    header.innerHTML = '<div style="flex:1">File</div><div style="width:160px">Title</div><div style="width:140px">Unique tag</div>';
                    metaContainer.insertBefore(header, metaContainer.firstChild);
                }
            })();
})();
