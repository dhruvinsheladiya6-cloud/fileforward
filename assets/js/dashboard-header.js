/**
 * DASHBOARD HEADER - FILE MANAGEMENT
 * Handles upload link creation from the files section header
 */

document.addEventListener('DOMContentLoaded', function () {
    // Only run if we're on a page that has the upload link button
    const createUploadLinkBtn = document.querySelector('.create-upload-link-option');
    if (!createUploadLinkBtn) {
        return; // Exit early if button doesn't exist on this page
    }

    // Get modal elements
    const createFileRequestModalEl = document.getElementById('createFileRequestModal');
    const createFileRequestForm = document.getElementById('createFileRequestForm');
    const submitFileRequestBtn = document.getElementById('submitFileRequestBtn');

    // Create modal instance
    let createFileRequestModal = null;
    if (createFileRequestModalEl && typeof bootstrap !== 'undefined') {
        createFileRequestModal = new bootstrap.Modal(createFileRequestModalEl, {
            backdrop: true, // Show dark backdrop
            keyboard: false // Still prevent ESC key close
        });
    }

    // Get current folder from URL
    const urlParams = new URLSearchParams(window.location.search);
    const currentFolderId = urlParams.get('folder');

    // Get CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    // Get file request URL
    const fileRequestUrl = window.APP_ROUTES?.fileRequestCreate || '/user/file-requests';

    /**
     * Show toast notification
     */
    function showToast(type, message) {
        if (typeof toastr !== 'undefined') {
            toastr[type](message);
        } else if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: type === 'success' ? 'success' : 'error',
                title: type === 'success' ? 'Success' : 'Error',
                text: message,
                timer: 3000,
                showConfirmButton: false
            });
        } else {
            alert(message);
        }
    }

    /**
     * Clean modal backdrops
     */
    function cleanupModals() {
        document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
            backdrop.remove();
        });
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    }

    // ----------------------
    // CREATE UPLOAD LINK EVENT HANDLERS
    // ----------------------

    // Prevent duplicate event listeners
    if (!createUploadLinkBtn.dataset.listenerAttached) {
        createUploadLinkBtn.dataset.listenerAttached = 'true';

        createUploadLinkBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation(); // Prevent Bootstrap's default modal trigger

            if (!createFileRequestModal || !createFileRequestForm) {
                showToast('error', 'Upload link dialog is not available.');
                return;
            }

            // Reset form fields
            createFileRequestForm.reset();

            // Ensure "min" on date (today)
            const dateInput = createFileRequestForm.querySelector('input[name="expiration_date"]');
            if (dateInput) {
                const today = new Date().toISOString().split('T')[0];
                dateInput.setAttribute('min', today);
            }

            // Clean up any existing backdrops
            cleanupModals();

            // Manually show modal (since we prevented default)
            createFileRequestModal.show();
        });
    }

    // Submit button handler
    if (submitFileRequestBtn && !submitFileRequestBtn.dataset.listenerAttached) {
        submitFileRequestBtn.dataset.listenerAttached = 'true';

        submitFileRequestBtn.addEventListener('click', function () {
            if (!csrfToken) {
                showToast('error', 'CSRF token missing, please refresh the page.');
                return;
            }

            const formData = new FormData(createFileRequestForm);

            // Attach current folder shared_id if present
            if (currentFolderId) {
                formData.append('folder_shared_id', currentFolderId);
            }

            // Button loading state
            submitFileRequestBtn.disabled = true;
            const originalHtml = submitFileRequestBtn.innerHTML;
            submitFileRequestBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating...';

            fetch(fileRequestUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            })
                .then(response => response.json().then(json => ({ ok: response.ok, json })))
                .then(({ ok, json }) => {
                    submitFileRequestBtn.disabled = false;
                    submitFileRequestBtn.innerHTML = originalHtml;

                    if (!ok || !json.success) {
                        const msg = json.message || json.msg || 'Failed to create upload link';
                        showToast('error', msg);
                        return;
                    }

                    const link = json.url;

                    // Copy to clipboard
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(link).then(() => {
                            showToast('success', 'Upload link created and copied to clipboard');
                        }).catch(() => {
                            showToast('success', 'Upload link created. Copy it manually:\n' + link);
                        });
                    } else {
                        showToast('success', 'Upload link created. Copy it manually:\n' + link);
                    }

                    // Clean up and close modal
                    cleanupModals();
                    if (createFileRequestModal) {
                        createFileRequestModal.hide();
                    }

                    console.log('Upload link created:', link);
                })
                .catch(err => {
                    console.error(err);
                    submitFileRequestBtn.disabled = false;
                    submitFileRequestBtn.innerHTML = originalHtml;
                    showToast('error', 'Failed to create upload link');
                });
        });
    }

    // Cleanup on modal hide
    if (createFileRequestModalEl) {
        createFileRequestModalEl.addEventListener('hidden.bs.modal', function () {
            setTimeout(cleanupModals, 100);
        });
    }

    console.log('Dashboard header upload link initialized');
});
