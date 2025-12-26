/**
 * FILE REQUESTS PAGE MODULE - jQuery Optimized
 * Handles: Create Request, Share, Manage Request, Table actions, Folder Picker
 */
(function ($) {
    'use strict';

    // Prevent duplicate initialization
    if (window.FileRequestsInitialized) {
        console.warn('FileRequests: already initialized, skipping');
        return;
    }
    window.FileRequestsInitialized = true;

    // ============================================================
    // CONFIGURATION
    // ============================================================

    var csrfToken = $('meta[name="csrf-token"]').attr('content') || '';
    var defaultFolderSharedId = window.DEFAULT_FILE_REQUEST_FOLDER || null;
    var defaultFolderName = window.DEFAULT_FILE_REQUEST_FOLDER_NAME || null;
    var defaultFolderLabel = window.DEFAULT_FILE_REQUEST_FOLDER_LABEL || null;

    // ============================================================
    // CACHED DOM ELEMENTS
    // ============================================================

    var $createModal = $('#fileRequestCreateModal');
    var $shareModal = $('#fileRequestShareModal');
    var $manageModal = $('#fileRequestManageModal');
    var $folderPickerModal = $('#folderPickerModal');
    var $createForm = $('#fileRequestCreateForm');
    var $manageForm = $('#fileRequestManageForm');

    // ============================================================
    // MODAL INSTANCES (lazy init)
    // ============================================================

    var modals = { create: null, share: null, manage: null, folder: null };

    function getModal(type) {
        if (modals[type]) return modals[type];

        var $el = null;
        switch (type) {
            case 'create': $el = $createModal; break;
            case 'share': $el = $shareModal; break;
            case 'manage': $el = $manageModal; break;
            case 'folder': $el = $folderPickerModal; break;
        }

        if ($el && $el.length) {
            modals[type] = new bootstrap.Modal($el[0], { backdrop: 'static' });
        }
        return modals[type];
    }

    // ============================================================
    // STATE
    // ============================================================

    var allFolders = [];
    var activeFolderTarget = 'create';

    // ============================================================
    // HELPER FUNCTIONS
    // ============================================================

    function cleanupModals() {
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open').css({ overflow: '', paddingRight: '' });
    }

    function setBtnLoading($btn, isLoading, text) {
        if (!$btn || !$btn.length) return;

        if (!$btn.data('original-html')) {
            $btn.data('original-html', $btn.html());
        }

        if (isLoading) {
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>' + (text || 'Loading...'));
        } else {
            $btn.prop('disabled', false).html($btn.data('original-html'));
        }
    }

    function api(url, options) {
        var defaults = {
            url: url,
            type: 'GET',
            dataType: 'json',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            }
        };
        return $.ajax($.extend(true, defaults, options || {}));
    }

    function escapeHtml(text) {
        return $('<div>').text(text || '').html();
    }

    function copyToClipboard(text, successMsg, errorMsg) {
        if (!text) {
            toastr.error(errorMsg || 'No link available');
            return;
        }

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text)
                .then(function () { toastr.success(successMsg || 'Copied!'); })
                .catch(function () { fallbackCopy(text, successMsg, errorMsg); });
        } else {
            fallbackCopy(text, successMsg, errorMsg);
        }
    }

    function fallbackCopy(text, successMsg, errorMsg) {
        var $temp = $('<textarea>').val(text).css({ position: 'fixed', left: '-9999px' }).appendTo('body');
        $temp[0].select();
        try {
            document.execCommand('copy');
            toastr.success(successMsg || 'Copied!');
        } catch (e) {
            toastr.error(errorMsg || 'Failed to copy');
        }
        $temp.remove();
    }

    // ============================================================
    // MODAL CLEANUP ON HIDE
    // ============================================================

    $createModal.add($manageModal).add($folderPickerModal).on('hidden.bs.modal', function () {
        setTimeout(cleanupModals, 100);
    });

    // Share modal - reload page after close (native event for Bootstrap compatibility)
    var shareModalElement = document.getElementById('fileRequestShareModal');
    if (shareModalElement) {
        shareModalElement.addEventListener('hidden.bs.modal', function () {
            cleanupModals();
            setTimeout(function () {
                window.location.reload();
            }, 100);
        });
    }

    // ============================================================
    // CREATE REQUEST
    // ============================================================

    $('#btnCreateFileRequest').on('click', function () {
        var modal = getModal('create');
        if (!modal) return;

        var $folderText = $('#frSelectedFolderText');
        var $folderId = $('#frSelectedFolderId');
        var defaultLabel = $folderText.data('default-label') || 'Root';

        if ($createForm.length) $createForm[0].reset();

        if (defaultFolderSharedId && $folderId.length) {
            $folderId.val(defaultFolderSharedId);
            $folderText.val(defaultFolderLabel || defaultFolderName || defaultLabel);
        } else {
            $folderId.val('');
            $folderText.val(defaultLabel);
        }

        cleanupModals();
        modal.show();
    });

    // Auto-populate folder name from title input
    $(document).on('input', '#fileRequestCreateForm input[name="folder_name"]', function () {
        var $folderText = $('#frSelectedFolderText');
        var defaultLabel = $folderText.data('default-label') || 'Root';
        var val = $(this).val().trim();
        $folderText.val(val || defaultLabel);
    });

    // Submit create form
    $('#btnCreateRequestSubmit').on('click', function () {
        var $btn = $(this);
        if (!$createForm.length) return;

        setBtnLoading($btn, true, 'Creating...');

        api('/user/file-requests', {
            type: 'POST',
            data: new FormData($createForm[0]),
            processData: false,
            contentType: false
        })
            .done(function (data) {
                setBtnLoading($btn, false);

                if (!data.success) {
                    toastr.error(data.message || 'Failed to create request');
                    return;
                }

                toastr.success('Request created successfully');
                getModal('create')?.hide();

                setTimeout(function () {
                    cleanupModals();

                    var shareModal = getModal('share');
                    if (!shareModal) {
                        location.reload();
                        return;
                    }

                    $('#shareFileRequestId').val(data.id);
                    $('#shareLinkInput').val(data.url || '');
                    $('#shareEmails').val('');
                    $('#shareMessage').val('');

                    shareModal.show();
                }, 300);
            })
            .fail(function (xhr) {
                setBtnLoading($btn, false);
                var msg = xhr.responseJSON?.message || 'Failed to create request';
                toastr.error(msg);
            });
    });

    // ============================================================
    // SHARE REQUEST
    // ============================================================

    $('#btnCopyShareLink').on('click', function () {
        var link = $('#shareLinkInput').val();
        copyToClipboard(link, 'Link copied to clipboard', 'No link available');
    });

    $('#btnSendShareRequest').on('click', function () {
        var $btn = $(this);
        var id = $('#shareFileRequestId').val();
        if (!id) return;

        var emails = $('#shareEmails').val() || '';
        if (!emails.trim()) {
            toastr.warning('Please enter at least one email address');
            return;
        }

        setBtnLoading($btn, true, 'Sending...');

        api('/user/file-requests/' + id + '/share', {
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                emails: emails,
                message: $('#shareMessage').val() || ''
            })
        })
            .done(function (data) {
                setBtnLoading($btn, false);

                if (!data.success) {
                    toastr.error(data.message || 'Failed to send invitations');
                    return;
                }

                toastr.success('Invitation sent successfully');
                getModal('share')?.hide();
            })
            .fail(function (xhr) {
                setBtnLoading($btn, false);
                var msg = xhr.responseJSON?.message || 'Failed to send invitations';
                toastr.error(msg);
            });
    });

    // ============================================================
    // MANAGE REQUEST - INSTANT MODAL OPEN
    // ============================================================

    function resetManageFormToLoading() {
        $('#manageFileRequestId').val('');
        $('#manageTitle').val('').attr('placeholder', 'Loading...');
        $('#manageDescription').val('').attr('placeholder', 'Loading...');
        $('#manageFolderText').val('Loading...');
        $('#manageFolderId').val('');
        $('#manageViewsCount').text('-');
        $('#manageUploadsCount').text('-');
        $('#managePassword').val('').attr('placeholder', 'Loading...');
        $('#manageExpirationDate').val('');
        $('#manageExpirationTime').val('');
        $('#manageStorageLimitValue').val('');
        $('#manageStorageLimitUnit').val('GB');
        $('#passwordStatusBadge').addClass('d-none');
        $('#passwordRemovalBadge').addClass('d-none');
        $('#manageRemovePassword').val('0');
    }

    function populateManageForm(data) {
        $('#manageFileRequestId').val(data.id);
        $('#manageTitle').val(data.title || '').attr('placeholder', '');
        $('#manageDescription').val(data.description || '').attr('placeholder', '');
        $('#manageFolderText').val(data.folder_path || 'Root');
        $('#manageFolderId').val(data.folder_shared_id || '');
        $('#manageViewsCount').text(data.views_count ?? '0');
        $('#manageUploadsCount').text(data.uploads_count ?? '0');

        // Password handling
        var $passInput = $('#managePassword');
        var $passBadge = $('#passwordStatusBadge');
        var $removePassBadge = $('#passwordRemovalBadge');
        var $passHelp = $('#managePasswordHelp');
        var $btnToggle = $('#btnTogglePassword');

        $('#togglePasswordIcon').removeClass('fa-eye-slash').addClass('fa-eye');

        if (data.password_protected) {
            $passBadge.removeClass('d-none');
            $removePassBadge.addClass('d-none');
            $passHelp.text('Leave blank to keep current password');
            $passInput.val('').attr('type', 'password').attr('placeholder', 'Enter new password to change');
            $btnToggle.addClass('d-none');
        } else {
            $passBadge.addClass('d-none');
            $removePassBadge.addClass('d-none');
            $passHelp.text('Optional: Add a password to protect this link');
            $passInput.val('').attr('type', 'password').attr('placeholder', 'Enter password');
            $btnToggle.addClass('d-none');
        }

        $('#manageRemovePassword').val('0');

        // Expiration
        if (data.expires_at) {
            try {
                var dt = new Date(data.expires_at.replace(' ', 'T'));
                $('#manageExpirationDate').val(dt.toISOString().slice(0, 10));
                $('#manageExpirationTime').val(dt.toTimeString().slice(0, 5));
            } catch (e) {
                $('#manageExpirationDate').val('');
                $('#manageExpirationTime').val('');
            }
        } else {
            $('#manageExpirationDate').val('');
            $('#manageExpirationTime').val('');
        }

        // Storage limit
        if (data.storage_limit_value) {
            $('#manageStorageLimitValue').val(data.storage_limit_value);
            $('#manageStorageLimitUnit').val(data.storage_limit_unit || 'GB');
        } else {
            $('#manageStorageLimitValue').val('');
            $('#manageStorageLimitUnit').val('GB');
        }
    }

    $(document).on('click', '.js-manage-request', function () {
        var id = $(this).data('id');
        if (!id) return;

        // STEP 1: Open modal IMMEDIATELY with loading state
        cleanupModals();
        resetManageFormToLoading();
        getModal('manage')?.show();

        // STEP 2: Fetch data in background and populate
        api('/user/file-requests/' + id)
            .done(function (data) {
                if (!data) {
                    toastr.error('No data received');
                    return;
                }
                populateManageForm(data);
            })
            .fail(function () {
                toastr.error('Failed to load request details');
                getModal('manage')?.hide();
            });
    });

    // ============================================================
    // PASSWORD TOGGLES
    // ============================================================

    $('#btnToggleCreatePassword').on('click', function () {
        var $input = $('#createPassword');
        var $icon = $('#toggleCreatePasswordIcon');
        var isPassword = $input.attr('type') === 'password';

        $input.attr('type', isPassword ? 'text' : 'password');
        $icon.toggleClass('fa-eye', !isPassword).toggleClass('fa-eye-slash', isPassword);
    });

    $('#createPassword').on('input', function () {
        var hasValue = $(this).val().trim().length > 0;
        $('#btnToggleCreatePassword').toggleClass('d-none', !hasValue);

        if (!hasValue) {
            $(this).attr('type', 'password');
            $('#toggleCreatePasswordIcon').removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    $('#btnRemovePassword').on('click', function () {
        $('#manageRemovePassword').val('1');
        $('#managePassword').val('');
        $('#passwordStatusBadge').addClass('d-none');
        $('#passwordRemovalBadge').removeClass('d-none');
        $('#btnTogglePassword').addClass('d-none');

        toastr.info('Password will be removed when you save changes');
    });

    // ============================================================
    // SAVE MANAGE
    // ============================================================

    $('#btnSaveFileRequest').on('click', function () {
        var $btn = $(this);
        var id = $('#manageFileRequestId').val();

        if (!$manageForm.length || !id) return;

        setBtnLoading($btn, true, 'Saving...');

        api('/user/file-requests/' + id, {
            type: 'POST',
            data: new FormData($manageForm[0]),
            processData: false,
            contentType: false
        })
            .done(function (data) {
                setBtnLoading($btn, false);

                if (!data.success) {
                    toastr.error(data.message || 'Failed to save changes');
                    return;
                }

                toastr.success('Changes saved successfully');
                getModal('manage')?.hide();

                setTimeout(function () {
                    cleanupModals();
                    location.reload();
                }, 400);
            })
            .fail(function (xhr) {
                setBtnLoading($btn, false);
                var msg = xhr.responseJSON?.message || 'Failed to save changes';
                toastr.error(msg);
            });
    });

    // ============================================================
    // CLOSE REQUEST
    // ============================================================

    $('#btnCloseFileRequest').on('click', function () {
        var $btn = $(this);
        var id = $('#manageFileRequestId').val();
        if (!id) return;

        if (!confirm('Are you sure you want to close this request?')) return;

        setBtnLoading($btn, true, 'Closing...');

        api('/user/file-requests/' + id + '/close', { type: 'POST' })
            .done(function (data) {
                setBtnLoading($btn, false);

                if (!data.success) {
                    toastr.error(data.message || 'Failed to close request');
                    return;
                }

                toastr.success('Request closed successfully');
                getModal('manage')?.hide();

                setTimeout(function () {
                    cleanupModals();
                    location.reload();
                }, 400);
            })
            .fail(function (xhr) {
                setBtnLoading($btn, false);
                var msg = xhr.responseJSON?.message || 'Failed to close request';
                toastr.error(msg);
            });
    });

    // ============================================================
    // COPY LINK FROM TABLE
    // ============================================================

    $(document).on('click', '.js-copy-link', function (e) {
        e.preventDefault();
        var url = $(this).data('url');
        copyToClipboard(url, 'Link copied to clipboard', 'No link available');
    });

    // ============================================================
    // FOLDER PICKER
    // ============================================================

    function openFolderPicker(target) {
        var modal = getModal('folder');
        if (!modal) return;

        activeFolderTarget = target;

        var $list = $('#folderPickerList');
        $list.html('<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>');

        modal.show();

        api('/user/file-requests/folders')
            .done(function (data) {
                if (data.success && data.folders) {
                    allFolders = data.folders;
                    renderFolderList(data.folders);
                } else {
                    $list.html('<div class="alert alert-danger mb-0">Failed to load folders</div>');
                }
            })
            .fail(function () {
                $list.html('<div class="alert alert-danger mb-0">Error loading folders</div>');
            });
    }

    function renderFolderList(folders) {
        var $list = $('#folderPickerList');
        if (!$list.length) return;

        if (!folders || folders.length === 0) {
            $list.html('<div class="alert alert-info mb-0"><i class="fas fa-info-circle me-2"></i>No folders found.</div>');
            return;
        }

        var rootFolders = folders.filter(function (f) { return !f.parent_id; });
        var childMap = {};
        folders.forEach(function (f) {
            if (f.parent_id) {
                if (!childMap[f.parent_id]) childMap[f.parent_id] = [];
                childMap[f.parent_id].push(f);
            }
        });

        var html = '<button type="button" class="list-group-item list-group-item-action folder-item" data-folder-id="" data-folder-name="Root"><i class="fas fa-folder me-2 text-primary"></i><strong>Root</strong></button>';

        function renderFolder(folder, level) {
            var indent = '';
            for (var i = 0; i < (level || 0); i++) indent += '&nbsp;&nbsp;&nbsp;&nbsp;';
            var safeName = escapeHtml(folder.name);
            html += '<button type="button" class="list-group-item list-group-item-action folder-item" data-folder-id="' + folder.shared_id + '" data-folder-name="' + safeName + '">' + indent + '<i class="fas fa-folder me-2 text-warning"></i>' + safeName + '</button>';
            var children = childMap[folder.id] || [];
            children.forEach(function (child) { renderFolder(child, (level || 0) + 1); });
        }

        rootFolders.forEach(function (f) { renderFolder(f, 0); });
        $list.html(html);
    }

    $('#btnChangeFolder').on('click', function () { openFolderPicker('create'); });
    $('#manageChangeFolder').on('click', function () { openFolderPicker('manage'); });

    $(document).on('click', '#folderPickerList .folder-item', function () {
        var folderId = $(this).data('folder-id') || '';
        var folderName = $(this).data('folder-name') || 'Root';

        if (activeFolderTarget === 'create') {
            $('#frSelectedFolderId').val(folderId);
            $('#frSelectedFolderText').val(folderName);
        } else {
            $('#manageFolderId').val(folderId);
            $('#manageFolderText').val(folderName);
        }

        getModal('folder')?.hide();
        toastr.success('Selected: ' + folderName);
    });

    console.log('FileRequests: initialized');

})(jQuery);
