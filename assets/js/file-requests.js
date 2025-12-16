/**
 * FILE REQUESTS PAGE MODULE
 * Handles:
 * - Create Request
 * - Share
 * - Manage Request
 * - Table actions
 */

document.addEventListener("DOMContentLoaded", () => {
    const csrfToken           = document.querySelector('meta[name="csrf-token"]')?.content || "";
    const defaultFolderSharedId   = window.DEFAULT_FILE_REQUEST_FOLDER       || null;
    const defaultFolderName       = window.DEFAULT_FILE_REQUEST_FOLDER_NAME  || null;
    const defaultFolderLabel      = window.DEFAULT_FILE_REQUEST_FOLDER_LABEL || null;
    const $  = (id) => document.getElementById(id);
    const qs = (sel) => document.querySelector(sel);

    /* ======================================================
     * Simple helpers
     * ====================================================== */
    const noop = () => {};

    function jsonFetch(url, options = {}) {
        return fetch(url, options).then(async (r) => {
            const data = await r.json().catch(() => ({}));
            return { ok: r.ok, data };
        });
    }

    function setBtnLoading(btn, isLoading, loadingText) {
        if (!btn) return;
        if (!btn.dataset.originalHtml) {
            btn.dataset.originalHtml = btn.innerHTML;
        }
        if (isLoading) {
            btn.disabled = true;
            btn.innerHTML = `<i class="fas fa-spinner fa-spin me-2"></i>${loadingText}`;
        } else {
            btn.disabled = false;
            btn.innerHTML = btn.dataset.originalHtml;
        }
    }

    /* ======================================================
     * Modal Setup
     * ====================================================== */
    const createModalEl = $("fileRequestCreateModal");
    const shareModalEl  = $("fileRequestShareModal");
    const manageModalEl = $("fileRequestManageModal");

    const createModal = createModalEl ? new bootstrap.Modal(createModalEl, { backdrop: "static" }) : null;
    const shareModal  = shareModalEl  ? new bootstrap.Modal(shareModalEl,  { backdrop: "static" }) : null;
    const manageModal = manageModalEl ? new bootstrap.Modal(manageModalEl, { backdrop: "static" }) : null;

    const createForm = $("fileRequestCreateForm");
    const btnCreateSubmit = $("btnCreateRequestSubmit");

    const shareFormId  = $("shareFileRequestId");
    const shareEmails  = $("shareEmails");
    const shareMsg     = $("shareMessage");
    const shareLink    = $("shareLinkInput");
    const btnCopyShare = $("btnCopyShareLink");
    const btnSendShare = $("btnSendShareRequest");

    const manageForm   = $("fileRequestManageForm");
    const manageId     = $("manageFileRequestId");
    const manageTitle  = $("manageTitle");
    const manageDesc   = $("manageDescription");
    const manageFolderText = $("manageFolderText");
    const manageFolderId   = $("manageFolderId");
    const manageDate   = $("manageExpirationDate");
    const manageTime   = $("manageExpirationTime");
    const managePass   = $("managePassword");
    const manageRemovePass = $("manageRemovePassword");
    const manageViews  = $("manageViewsCount");
    const manageUploads = $("manageUploadsCount");
    const passwordStatusBadge  = $("passwordStatusBadge");
    const passwordRemovalBadge = $("passwordRemovalBadge");
    const passwordHelp         = $("managePasswordHelp");

    const btnSaveManage = $("btnSaveFileRequest");
    const btnCloseReq   = $("btnCloseFileRequest");

    /* ======================================================
     * Modal Cleanup - Fix backdrop issue
     * ====================================================== */
    function cleanupModals() {
        document.querySelectorAll(".modal-backdrop").forEach((backdrop) => backdrop.remove());
        document.body.classList.remove("modal-open");
        document.body.style.overflow = "";
        document.body.style.paddingRight = "";
    }

    [createModalEl, shareModalEl, manageModalEl].forEach((modalEl) => {
        if (!modalEl) return;
        modalEl.addEventListener("hidden.bs.modal", () => {
            setTimeout(cleanupModals, 100);
        });
    });

    /* ======================================================
     * Create Request
     * ====================================================== */
    const btnCreateFileRequest = $("btnCreateFileRequest");

    btnCreateFileRequest?.addEventListener("click", () => {
        if (!createForm || !createModal) return;

        createForm.reset();

        const folderTextField    = $("frSelectedFolderText");
        const frSelectedFolderId = $("frSelectedFolderId");

        const defaultLabelLocal = folderTextField?.dataset.defaultLabel || "Root";

        if (defaultFolderSharedId && frSelectedFolderId) {
            // We came from /user/file-requests?folder=SHARED_ID
            frSelectedFolderId.value = defaultFolderSharedId;

            if (folderTextField) {
                folderTextField.value =
                    defaultFolderLabel ||
                    defaultFolderName  ||
                    defaultLabelLocal;
            }
        } else {
            if (frSelectedFolderId) frSelectedFolderId.value = "";
            if (folderTextField) folderTextField.value = defaultLabelLocal;
        }

        cleanupModals();
        createModal.show();
    });

    // Auto-populate "Folder for uploaded files" from Folder name input
    const folderNameInput  = createForm?.querySelector('input[name="folder_name"]');
    const frSelectedFolderText = $("frSelectedFolderText");

    if (folderNameInput && frSelectedFolderText) {
        const defaultLabelLocal = frSelectedFolderText.dataset.defaultLabel || "Root";

        folderNameInput.addEventListener("input", () => {
            const nameValue = folderNameInput.value.trim();
            frSelectedFolderText.value = nameValue || defaultLabelLocal;
        });
    }

    if (btnCreateSubmit && !btnCreateSubmit.dataset.listenerAttached) {
        btnCreateSubmit.dataset.listenerAttached = "true";

        btnCreateSubmit.addEventListener("click", () => {
            if (!createForm) return;

            const formData = new FormData(createForm);
            setBtnLoading(btnCreateSubmit, true, "Creating...");

            jsonFetch(`/user/file-requests`, {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": csrfToken,
                    "X-Requested-With": "XMLHttpRequest",
                },
                body: formData,
            })
                .then(({ ok, data }) => {
                    setBtnLoading(btnCreateSubmit, false);

                    if (!ok || !data.success) {
                        return toastr.error(data.message || "Request failed");
                    }

                    createModal?.hide();

                    // Wait for create modal to close before opening share modal
                    setTimeout(() => {
                        cleanupModals();

                        if (!shareModal) return;

                        if (shareFormId) shareFormId.value = data.id;
                        if (shareLink) shareLink.value = data.url || "";
                        if (shareEmails) shareEmails.value = "";
                        if (shareMsg) shareMsg.value = "";

                        shareModal.show();
                    }, 300);
                })
                .catch(() => {
                    setBtnLoading(btnCreateSubmit, false);
                    toastr.error("Request failed");
                });
        });
    }

    /* ======================================================
     * Share – copy link
     * ====================================================== */
    btnCopyShare?.addEventListener("click", () => {
        if (!shareLink) return;
        const value = shareLink.value;
        if (!navigator.clipboard || !value) {
            toastr.error("Failed to copy");
            return;
        }
        navigator.clipboard
            .writeText(value)
            .then(() => toastr.success("Link copied to clipboard"))
            .catch(() => toastr.error("Failed to copy"));
    });

    /* ======================================================
     * Share – send email
     * ====================================================== */
    btnSendShare?.addEventListener("click", () => {
        if (!shareFormId) return;

        jsonFetch(`/user/file-requests/${shareFormId.value}/share`, {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": csrfToken,
                "Content-Type": "application/json",
            },
            body: JSON.stringify({
                emails: shareEmails?.value || "",
                message: shareMsg?.value || "",
            }),
        })
            .then(({ data }) => {
                if (!data.success) return toastr.error(data.message || "Failed to send invitations");
                toastr.success("Invitation sent successfully");
                shareModal?.hide();
                setTimeout(() => {
                    cleanupModals();
                    location.reload();
                }, 400);
            })
            .catch(() => toastr.error("Failed to send invitations"));
    });

    // Handle Share modal skip/close - reload page to show created request
    if (shareModalEl && !shareModalEl.dataset.reloadListenerAttached) {
        shareModalEl.dataset.reloadListenerAttached = "true";

        shareModalEl.addEventListener("hidden.bs.modal", () => {
            setTimeout(() => {
                cleanupModals();
                location.reload();
            }, 200);
        });
    }

    /* ======================================================
     * Manage Request – open modal
     * (delegated to document to avoid many listeners)
     * ====================================================== */
    document.addEventListener("click", (e) => {
        const manageBtn = e.target.closest?.(".js-manage-request");
        if (!manageBtn) return;

        const id = manageBtn.dataset.id;
        if (!id) return;

        jsonFetch(`/user/file-requests/${id}`, {
            headers: { Accept: "application/json" },
        })
            .then(({ data }) => {
                if (!data) return;

                if (manageId) manageId.value = data.id;
                if (manageTitle) manageTitle.value = data.title || "";
                if (manageDesc) manageDesc.value = data.description || "";
                if (manageFolderText) manageFolderText.value = data.folder_path || "";
                if (manageFolderId) manageFolderId.value = data.folder_shared_id || "";
                if (manageViews) manageViews.textContent = data.views_count ?? "0";
                if (manageUploads) manageUploads.textContent = data.uploads_count ?? "0";

                // Reset password toggle icon state
                const togglePasswordIcon = $("togglePasswordIcon");
                const btnTogglePassword = $("btnTogglePassword");
                if (togglePasswordIcon) {
                    togglePasswordIcon.classList.remove("fa-eye-slash");
                    togglePasswordIcon.classList.add("fa-eye");
                }

                // Handle password status indicator
                if (passwordStatusBadge && passwordRemovalBadge && passwordHelp) {
                    if (data.password_protected) {
                        // With password
                        passwordStatusBadge.classList.remove("d-none");
                        passwordRemovalBadge.classList.add("d-none");
                        passwordHelp.textContent = "Leave blank to keep current password";

                        if (managePass) {
                            managePass.value = "";
                            managePass.type = "password";
                            managePass.placeholder = "Enter new password to change";
                        }

                        if (btnTogglePassword) {
                            btnTogglePassword.classList.add("d-none");
                        }
                    } else {
                        // Without password
                        passwordStatusBadge.classList.add("d-none");
                        passwordRemovalBadge.classList.add("d-none");
                        passwordHelp.textContent = "Optional: Add a password to protect this link";

                        if (managePass) {
                            managePass.value = "";
                            managePass.type = "password";
                            managePass.placeholder = "Enter password";
                        }

                        if (btnTogglePassword) {
                            btnTogglePassword.classList.add("d-none");
                        }
                    }
                }

                // Reset remove password flag
                if (manageRemovePass) manageRemovePass.value = "0";

                // Expiration
                if (data.expires_at && manageDate && manageTime) {
                    const dt = new Date(data.expires_at.replace(" ", "T"));
                    manageDate.value = dt.toISOString().slice(0, 10);
                    manageTime.value = dt.toTimeString().slice(0, 5);
                }

                // Storage limit
                const manageLimitValue = $("manageStorageLimitValue");
                const manageLimitUnit  = $("manageStorageLimitUnit");
                if (manageLimitValue && manageLimitUnit) {
                    if (data.storage_limit_value) {
                        manageLimitValue.value = data.storage_limit_value;
                        manageLimitUnit.value  = data.storage_limit_unit || "GB";
                    } else {
                        manageLimitValue.value = "";
                        manageLimitUnit.value  = "GB";
                    }
                }

                cleanupModals();
                manageModal?.show();
            })
            .catch(noop);
    });

    /* ======================================================
     * Create Modal Password Toggle (Show/Hide)
     * ====================================================== */
    const createPass               = $("createPassword");
    const btnToggleCreatePassword  = $("btnToggleCreatePassword");
    const toggleCreatePasswordIcon = $("toggleCreatePasswordIcon");

    if (btnToggleCreatePassword && createPass && toggleCreatePasswordIcon) {
        btnToggleCreatePassword.addEventListener("click", () => {
            const isPassword = createPass.type === "password";
            createPass.type = isPassword ? "text" : "password";
            toggleCreatePasswordIcon.classList.toggle("fa-eye", !isPassword);
            toggleCreatePasswordIcon.classList.toggle("fa-eye-slash", isPassword);
        });

        createPass.addEventListener("input", () => {
            const hasValue = createPass.value.trim().length > 0;
            btnToggleCreatePassword.classList.toggle("d-none", !hasValue);

            if (!hasValue) {
                createPass.type = "password";
                toggleCreatePasswordIcon.classList.remove("fa-eye-slash");
                toggleCreatePasswordIcon.classList.add("fa-eye");
            }
        });
    }

    /* ======================================================
     * Remove Password Button
     * ====================================================== */
    const btnRemovePassword = $("btnRemovePassword");

    if (btnRemovePassword && managePass && manageRemovePass) {
        btnRemovePassword.addEventListener("click", () => {
            manageRemovePass.value = "1";
            managePass.value = "";

            if (passwordStatusBadge && passwordRemovalBadge) {
                passwordStatusBadge.classList.add("d-none");
                passwordRemovalBadge.classList.remove("d-none");
            }

            const btnToggle = $("btnTogglePassword");
            if (btnToggle) btnToggle.classList.add("d-none");

            toastr.info("Password will be removed when you save changes");
        });
    }

    /* ======================================================
     * Save Manage
     * ====================================================== */
    btnSaveManage?.addEventListener("click", () => {
        if (!manageForm || !manageId) return;
        const id = manageId.value;
        const form = new FormData(manageForm);

        jsonFetch(`/user/file-requests/${id}`, {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": csrfToken,
                "X-Requested-With": "XMLHttpRequest",
            },
            body: form,
        })
            .then(({ data }) => {
                if (!data.success) return toastr.error(data.message || "Failed to save changes");
                toastr.success("Changes saved successfully");
                manageModal?.hide();
                setTimeout(() => {
                    cleanupModals();
                    location.reload();
                }, 400);
            })
            .catch(() => toastr.error("Failed to save changes"));
    });

    /* ======================================================
     * Close Request
     * ====================================================== */
    btnCloseReq?.addEventListener("click", () => {
        if (!manageId) return;

        jsonFetch(`/user/file-requests/${manageId.value}/close`, {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": csrfToken,
            },
        })
            .then(({ data }) => {
                if (!data.success) return toastr.error(data.message || "Failed to close request");
                toastr.success("Request closed successfully");
                manageModal?.hide();
                setTimeout(() => {
                    cleanupModals();
                    location.reload();
                }, 400);
            })
            .catch(() => toastr.error("Failed to close request"));
    });

    /* ======================================================
     * Copy Link from Table (event delegation)
     * ====================================================== */
    document.addEventListener("click", (e) => {
        const btn = e.target.closest?.(".js-copy-link");
        if (!btn) return;

        e.preventDefault();
        const url = btn.dataset.url;
        if (navigator.clipboard && url) {
            navigator.clipboard
                .writeText(url)
                .then(() => toastr.success("Link copied to clipboard"))
                .catch(() => toastr.error("Failed to copy link"));
        } else {
            toastr.error("Failed to copy link");
        }
    });

    /* ======================================================
     * FOLDER PICKER FUNCTIONALITY
     * ====================================================== */
    const folderPickerModalEl = $("folderPickerModal");
    const folderPickerModal   = folderPickerModalEl ? new bootstrap.Modal(folderPickerModalEl) : null;
    const btnChangeFolder     = $("btnChangeFolder");
    const manageChangeFolder  = $("manageChangeFolder");
    const folderPickerList    = $("folderPickerList");

    const frSelectedFolderId = $("frSelectedFolderId");

    let allFolders = [];
    let activeFolderTarget = "create"; // 'create' or 'manage'

    async function openFolderPicker(target) {
        if (!folderPickerModal || !folderPickerList) return;

        activeFolderTarget = target;

        folderPickerList.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `;

        folderPickerModal.show();

        try {
            const { data } = await jsonFetch("/user/file-requests/folders", {
                headers: {
                    "X-CSRF-TOKEN": csrfToken,
                    "X-Requested-With": "XMLHttpRequest",
                    Accept: "application/json",
                },
            });

            if (data.success && data.folders) {
                allFolders = data.folders;
                renderFolderList(allFolders);
            } else {
                folderPickerList.innerHTML = `
                    <div class="alert alert-danger">
                        Failed to load folders
                    </div>
                `;
            }
        } catch (error) {
            console.error("Error loading folders:", error);
            folderPickerList.innerHTML = `
                <div class="alert alert-danger">
                    Error loading folders. Please try again.
                </div>
            `;
        }
    }

    btnChangeFolder?.addEventListener("click", () => openFolderPicker("create"));
    manageChangeFolder?.addEventListener("click", () => openFolderPicker("manage"));

    function renderFolderList(folders) {
        if (!folderPickerList) return;

        if (!folders || folders.length === 0) {
            folderPickerList.innerHTML = `
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    No folders found. Create folders in the Files section first.
                </div>
            `;
            return;
        }

        const rootFolders = folders.filter((f) => !f.parent_id);
        const childMap = {};
        folders.forEach((f) => {
            if (f.parent_id) {
                if (!childMap[f.parent_id]) childMap[f.parent_id] = [];
                childMap[f.parent_id].push(f);
            }
        });

        let html = `
            <button type="button" class="list-group-item list-group-item-action folder-item"
                    data-folder-id="" data-folder-name="Root">
                <i class="fas fa-folder me-2 text-primary"></i>
                <strong>Root (All Files)</strong>
            </button>
        `;

        function escapeHtml(text) {
            const div = document.createElement("div");
            div.textContent = text;
            return div.innerHTML;
        }

        function renderFolder(folder, level = 0) {
            const indent = "&nbsp;&nbsp;&nbsp;&nbsp;".repeat(level);
            const safeName = escapeHtml(folder.name);

            html += `
                <button type="button" class="list-group-item list-group-item-action folder-item"
                        data-folder-id="${folder.shared_id}" data-folder-name="${safeName}">
                    ${indent}<i class="fas fa-folder me-2 text-warning"></i>
                    ${safeName}
                </button>
            `;

            const children = childMap[folder.id] || [];
            children.forEach((child) => renderFolder(child, level + 1));
        }

        rootFolders.forEach((folder) => renderFolder(folder));

        folderPickerList.innerHTML = html;
    }

    // Folder picker click – event delegation on list container
    folderPickerList?.addEventListener("click", (e) => {
        const item = e.target.closest?.(".folder-item");
        if (!item) return;

        const folderId   = item.dataset.folderId || "";
        const folderName = item.dataset.folderName || "Root";

        if (activeFolderTarget === "create") {
            if (frSelectedFolderId) frSelectedFolderId.value = folderId;
            if (frSelectedFolderText) frSelectedFolderText.value = folderName;
        } else if (activeFolderTarget === "manage") {
            if (manageFolderId) manageFolderId.value = folderId;
            if (manageFolderText) manageFolderText.value = folderName;
        }

        folderPickerModal?.hide();
        toastr.success(`Selected folder: ${folderName}`);
    });
});
