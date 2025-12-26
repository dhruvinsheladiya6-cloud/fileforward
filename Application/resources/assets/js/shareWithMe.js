/**
 * ShareWithMe - Consolidated share modal module
 * Extracted from inline JavaScript across multiple views for code reuse
 * 
 * Usage:
 *   ShareWithMe.init({
 *     modalId: 'sharedWithMeModal',
 *     baseUrl: '/user/files',
 *     csrfToken: document.querySelector('meta[name="csrf-token"]').content,
 *     translations: { ... }
 *   });
 */
(function (global) {
    'use strict';

    const ShareWithMe = {
        // Configuration
        config: {
            modalId: 'sharedWithMeModal',
            baseUrl: '/user/files',
            csrfToken: '',
            recipientsUrl: '/user/shares/recipients',
            translations: {
                share: 'Share',
                sharing: 'Sharing...',
                statusUpdated: 'Status updated',
                sharedSuccessfully: 'Shared successfully',
                linkCopied: 'Link copied to clipboard!',
                noLinkAvailable: 'No link available to copy.',
                failedToCopy: 'Failed to copy link.',
                fileIdMissing: 'File ID is missing. Please close and try again.',
                invalidEmail: 'Please enter a valid email address.',
                sharingFailed: 'Sharing failed. Please try again.',
                failedToUpdateStatus: 'Failed to update access status.',
                accessRemoved: 'Access removed successfully',
                permissionUpdated: 'Permission updated successfully',
                failedToRemove: 'Failed to remove access',
                failedToUpdatePermission: 'Failed to update permission',
                confirmRemove: 'Are you sure you want to remove access for this person?'
            }
        },

        // State
        state: {
            currentSharedId: null,
            currentType: null,
            currentDownloadLink: '',
            installed: false,
            suggest: { items: [], open: false, activeIndex: -1, lastQuery: '' }
        },

        // Constants
        MIN_CHARS_TO_SUGGEST: 1,

        /**
         * Initialize the module
         * @param {Object} options - Configuration options
         */
        init: function (options = {}) {
            // Prevent double initialization
            if (this.state.installed) return;
            this.state.installed = true;

            // Merge config
            Object.assign(this.config, options);
            if (options.translations) {
                Object.assign(this.config.translations, options.translations);
            }

            // Get CSRF token
            if (!this.config.csrfToken) {
                const meta = document.querySelector('meta[name="csrf-token"]');
                this.config.csrfToken = meta ? meta.content : '';
            }

            // Setup modal
            this._setupModal();
        },

        /**
         * DOM helper - querySelector shorthand
         */
        $: function (selector, context = document) {
            return context.querySelector(selector);
        },

        /**
         * Make API request
         */
        api: async function (url, { method = 'GET', data = null } = {}) {
            const opt = {
                method,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': this.config.csrfToken,
                },
            };

            if (data && method !== 'GET') {
                if (data instanceof FormData) {
                    opt.body = data;
                } else {
                    opt.headers['Content-Type'] = 'application/json';
                    opt.body = JSON.stringify(data);
                }
            }

            const res = await fetch(url, opt);
            const json = await res.json().catch(() => ({}));
            if (!res.ok) throw (json.message || 'Request failed');
            return json;
        },

        /**
         * Show alert message in modal
         */
        showAlert: function (msg, type = 'success') {
            const box = this.$('#swmAlert');
            if (!box) return;
            box.className = 'alert py-2 px-3 alert-' + (type === 'success' ? 'success' : 'danger');
            box.textContent = msg;
            box.classList.remove('d-none');
            setTimeout(() => box.classList.add('d-none'), 3000);
        },

        /**
         * Set modal title with filename
         */
        setTitle: function (fileName) {
            const span = this.$('#swmFileName');
            if (!span) return;

            fileName = (fileName || '').trim();
            if (fileName) {
                span.textContent = '"' + fileName + '"';
                span.title = fileName;
                span.style.display = '';
            } else {
                span.textContent = '';
                span.removeAttribute('title');
                span.style.display = 'none';
            }
        },

        /**
         * Escape HTML entities
         */
        escapeHtml: function (str) {
            return (str || '').replace(/[&<>"']/g, (s) => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
            }[s]));
        },

        /**
         * Debounce utility
         */
        debounce: function (fn, ms = 200) {
            let t;
            return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
        },

        /**
         * Copy text to clipboard
         */
        copyToClipboard: async function (text) {
            if (!text || !text.trim()) {
                this.showAlert(this.config.translations.noLinkAvailable, 'danger');
                return;
            }

            if (navigator.clipboard && window.isSecureContext) {
                try {
                    await navigator.clipboard.writeText(text);
                    this.showAlert(this.config.translations.linkCopied, 'success');
                    return;
                } catch (e) { }
            }

            // Fallback for non-secure contexts
            const temp = document.createElement('textarea');
            temp.value = text;
            temp.style.position = 'fixed';
            temp.style.left = '-9999px';
            document.body.appendChild(temp);
            temp.select();
            try {
                document.execCommand('copy');
                this.showAlert(this.config.translations.linkCopied, 'success');
            } catch (e) {
                this.showAlert(this.config.translations.failedToCopy, 'danger');
            }
            document.body.removeChild(temp);
        },

        /**
         * Update copy link button visibility
         */
        updateCopyLinkButton: function () {
            const accessStatus = this.$('#swmAccessStatus')?.value;
            const copyGroup = this.$('#swmCopyLinkGroup');
            const copyBtn = this.$('#swmCopyLink');
            if (!copyGroup || !copyBtn) return;

            if (accessStatus === '1') {
                copyGroup.style.display = 'block';
                copyBtn.disabled = false;
            } else {
                copyGroup.style.display = 'none';
                copyBtn.disabled = true;
            }
        },

        /**
         * Render people list with access
         */
        renderPeopleList: function (people, ownerInfo, uploaderInfo = null) {
            const container = this.$('#swmPeopleList');
            const section = this.$('#swmPeopleSection');
            if (!container || !section) return;

            if ((!people || !Array.isArray(people) || people.length === 0) && !ownerInfo && !uploaderInfo) {
                section.style.display = 'none';
                container.innerHTML = '';
                return;
            }

            section.style.display = 'block';
            let peopleHtml = '';

            const ownerId = ownerInfo?.id ?? null;
            const ownerEmail = ownerInfo?.email || '';
            const ownerName = ownerInfo?.name || ownerEmail;

            const uploaderId = uploaderInfo?.id ?? null;
            const uploaderEmail = uploaderInfo?.email || '';
            const uploaderName = uploaderInfo?.name || uploaderEmail;

            const hasDifferentUploader =
                uploaderInfo && ownerInfo && uploaderId && ownerId && uploaderId !== ownerId;

            if (hasDifferentUploader) {
                peopleHtml += this._renderOwnerItem(uploaderName, uploaderEmail, 'Owner', 'success');
                peopleHtml += this._renderOwnerItem(ownerName, ownerEmail, 'Editor', 'primary');
            } else if (ownerInfo) {
                peopleHtml += this._renderOwnerItem(ownerName, ownerEmail, 'Owner', 'success');
            }

            if (people && Array.isArray(people)) {
                people.forEach(person => {
                    peopleHtml += this._renderPersonItem(person);
                });
            }

            container.innerHTML = peopleHtml;
            this._attachPeopleListeners(container);
        },

        /**
         * Render owner/uploader item
         */
        _renderOwnerItem: function (name, email, badge, badgeClass) {
            return `
                <div class="person-item d-flex align-items-center justify-content-between py-2 border-bottom">
                    <div class="d-flex align-items-center flex-grow-1">
                        <div class="avatar me-2">
                            <div class="bg-${badgeClass} rounded-circle d-flex align-items-center justify-content-center text-white"
                                style="width:32px;height:32px;font-size:14px;">
                                ${this.escapeHtml(name).charAt(0).toUpperCase()}
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-medium">${this.escapeHtml(name)}</div>
                            <div class="text-muted small">${this.escapeHtml(email)}</div>
                        </div>
                    </div>
                    <span class="badge bg-${badgeClass}">${badge}</span>
                </div>
            `;
        },

        /**
         * Render a shared person item
         */
        _renderPersonItem: function (person) {
            const email = person.recipient_email || person.email || '';
            const name = person.recipient_name || person.name || email;
            const permission = person.permission || 'view';
            const status = person.status || 'active';
            const isActive = status === 'active' && !person.revoked_at && !person.expired;

            let badgeText = permission === 'edit' ? 'Editor' : 'Viewer';
            let badgeClass = 'bg-primary text-white';

            let statusText = '';
            let statusClass = '';
            if (!isActive) {
                statusText = person.revoked_at ? 'Revoked' : 'Expired';
                statusClass = 'text-danger';
            }

            return `
                <div class="person-item d-flex align-items-center justify-content-between py-2 border-bottom">
                    <div class="d-flex align-items-center flex-grow-1">
                        <div class="avatar me-2">
                            <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center text-white"
                                style="width:32px;height:32px;font-size:14px;">
                                ${this.escapeHtml(name).charAt(0).toUpperCase()}
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-medium">${this.escapeHtml(name)}</div>
                            <div class="text-muted small">${this.escapeHtml(email)}</div>
                            ${statusText ? `<div class="small ${statusClass}">${statusText}</div>` : ''}
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="dropdown me-2">
                            <button class="btn btn-sm ${badgeClass} dropdown-toggle" type="button" data-bs-toggle="dropdown"
                                    data-share-id="${person.id}" ${!isActive ? 'disabled' : ''}>
                                ${badgeText}
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item change-permission" href="#" data-permission="view" data-share-id="${person.id}">Viewer</a></li>
                                <li><a class="dropdown-item change-permission" href="#" data-permission="edit" data-share-id="${person.id}">Editor</a></li>
                            </ul>
                        </div>
                        ${isActive ? `
                        <button type="button" class="btn btn-sm btn-outline-danger remove-person"
                                data-share-id="${person.id}" title="Remove access">
                            <i class="fas fa-times"></i>
                        </button>` : ''}
                    </div>
                </div>
            `;
        },

        /**
         * Attach event listeners to people list buttons
         */
        _attachPeopleListeners: function (container) {
            const self = this;
            container.querySelectorAll('.remove-person').forEach(btn => {
                btn.addEventListener('click', function () {
                    self.removePersonAccess(this.getAttribute('data-share-id'));
                });
            });

            container.querySelectorAll('.change-permission').forEach(link => {
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    self.updatePersonPermission(
                        this.getAttribute('data-share-id'),
                        this.getAttribute('data-permission')
                    );
                });
            });
        },

        /**
         * Remove person access
         */
        removePersonAccess: async function (shareId) {
            if (!confirm(this.config.translations.confirmRemove)) return;

            try {
                const response = await this.api(`/user/shares/${shareId}/remove`, { method: 'DELETE' });
                this.showAlert(response.message || this.config.translations.accessRemoved, 'success');
                await this.loadPeopleWithAccess();
            } catch (err) {
                console.error(err);
                this.showAlert(err.toString() || this.config.translations.failedToRemove, 'danger');
            }
        },

        /**
         * Update person permission
         */
        updatePersonPermission: async function (shareId, newPermission) {
            try {
                const response = await this.api(`/user/shares/${shareId}/update-permission`, {
                    method: 'PUT',
                    data: { permission: newPermission }
                });
                this.showAlert(response.message || this.config.translations.permissionUpdated, 'success');
                await this.loadPeopleWithAccess();
            } catch (err) {
                console.error(err);
                this.showAlert(err.toString() || this.config.translations.failedToUpdatePermission, 'danger');
            }
        },

        /**
         * Load people with access
         */
        loadPeopleWithAccess: async function () {
            if (!this.state.currentSharedId) return;
            try {
                const response = await this.api(`${this.config.baseUrl}/${this.state.currentSharedId}/shared-people`);
                this.renderPeopleList(response.people || [], response.owner || null, response.uploader || null);
            } catch (err) {
                console.error(err);
                this.renderPeopleList([], { email: 'Current User', name: 'Owner' }, null);
            }
        },

        /**
         * Update access status
         */
        updateAccessStatus: async function () {
            if (!this.state.currentSharedId) {
                this.showAlert(this.config.translations.fileIdMissing, 'danger');
                return;
            }

            const accessStatusEl = this.$('#swmAccessStatus');
            const accessStatus = accessStatusEl.value;
            accessStatusEl.disabled = true;

            try {
                const response = await this.api(`/user/files/${this.state.currentSharedId}/update-status`, {
                    method: 'POST',
                    data: { access_status: accessStatus },
                });

                this.state.currentDownloadLink = response.download_link || this.state.currentDownloadLink || '';
                accessStatusEl.value = response.access_status ?? accessStatus;

                this.updateCopyLinkButton();
                this.showAlert(this.config.translations.statusUpdated, 'success');
            } catch (err) {
                console.error(err);
                this.showAlert(err.toString() || this.config.translations.failedToUpdateStatus, 'danger');
            } finally {
                accessStatusEl.disabled = false;
            }
        },

        /**
         * Close typeahead dropdown
         */
        closeTypeahead: function () {
            const dd = this.$('#swmTypeahead');
            if (!dd) return;
            dd.classList.remove('show');
            this.state.suggest.open = false;
            this.state.suggest.activeIndex = -1;
        },

        /**
         * Open typeahead dropdown
         */
        openTypeahead: function () {
            const dd = this.$('#swmTypeahead');
            if (!dd) return;
            dd.classList.add('show');
            this.state.suggest.open = true;
        },

        /**
         * Render typeahead suggestions
         */
        renderTypeahead: function (q) {
            const dd = this.$('#swmTypeahead');
            if (!dd) return;

            dd.innerHTML = '';
            const emailVal = (q || '').trim();
            const items = this.state.suggest.items || [];

            if (!items.length && !emailVal) {
                this.closeTypeahead();
                return;
            }

            const self = this;
            items.forEach((r, idx) => {
                const el = document.createElement('button');
                el.type = 'button';
                el.className = 'dropdown-item' + (idx === this.state.suggest.activeIndex ? ' active' : '');
                el.innerHTML = `
                    <span class="name"><strong>${this.escapeHtml(r.name || r.email)}</strong></span>
                    ${r.name ? `<span class="email">${this.escapeHtml(r.email)}</span>` : ''}
                `;
                el.addEventListener('mousedown', (ev) => {
                    ev.preventDefault();
                    self.$('#swmEmail').value = r.email;
                    self.closeTypeahead();
                });
                dd.appendChild(el);
            });

            dd.classList.add('show');
            this.state.suggest.open = true;
        },

        /**
         * Fetch suggestions (debounced)
         */
        _fetchSuggestions: null, // Will be set in init

        /**
         * Setup modal event handlers
         */
        _setupModal: function () {
            const self = this;
            const modalEl = document.getElementById(this.config.modalId);
            if (!modalEl) return;

            // Setup debounced fetch
            this._fetchSuggestions = this.debounce(async (q = '') => {
                try {
                    const r = await self.api(`${self.config.recipientsUrl}?limit=20&q=${encodeURIComponent(q)}`);
                    self.state.suggest.items = Array.isArray(r.recipients) ? r.recipients : [];
                    self.renderTypeahead(q);
                } catch {
                    self.state.suggest.items = [];
                    self.renderTypeahead(q);
                }
            }, 200);

            // Modal show event
            modalEl.addEventListener('show.bs.modal', async function (event) {
                const button = event.relatedTarget;
                self.state.currentSharedId = button?.getAttribute('data-file-id');
                self.state.currentType = (button?.getAttribute('data-file-type') || 'file').toLowerCase();
                const fileName = button?.getAttribute('data-file-name') || '';
                const shareData = button?.getAttribute('data-share') || '';

                self.setTitle(fileName);

                const form = self.$('#swmForm');
                if (form) form.reset();
                const permissionEl = self.$('#swmPermission');
                if (permissionEl) permissionEl.value = 'view';
                const emailEl = self.$('#swmEmail');
                if (emailEl) emailEl.value = '';
                self.closeTypeahead();

                try {
                    const response = await self.api(`${self.config.baseUrl}/${self.state.currentSharedId}`);
                    self.$('#swmAccessStatus').value = response.access_status ?? '0';
                    self.state.currentDownloadLink = response.download_link || '';
                    self.updateCopyLinkButton();
                    await self.loadPeopleWithAccess();
                } catch (e) {
                    try {
                        const parsed = shareData ? JSON.parse(shareData) : {};
                        self.state.currentDownloadLink = parsed.download_link || '';
                    } catch { }
                    self.$('#swmAccessStatus').value = '0';
                    self.updateCopyLinkButton();
                    self.renderPeopleList([], null, null);
                }

                const copyBtn = self.$('#swmCopyLink');
                if (copyBtn) {
                    copyBtn.onclick = () => self.copyToClipboard(self.state.currentDownloadLink);
                }
            });

            // Modal hide event
            modalEl.addEventListener('hidden.bs.modal', function () {
                self.state.currentSharedId = null;
                self.state.currentDownloadLink = '';
                self.closeTypeahead();
            });

            // Email input handler
            const emailInput = this.$('#swmEmail');
            if (emailInput) {
                emailInput.addEventListener('input', (e) => {
                    const q = e.target.value || '';
                    self.state.suggest.lastQuery = q;

                    if (q.trim().length >= self.MIN_CHARS_TO_SUGGEST) {
                        self._fetchSuggestions(q);
                        self.openTypeahead();
                    } else {
                        self.closeTypeahead();
                    }
                });

                emailInput.addEventListener('blur', () => setTimeout(() => self.closeTypeahead(), 120));
            }

            // Access status change
            const accessStatusEl = this.$('#swmAccessStatus');
            if (accessStatusEl) {
                accessStatusEl.addEventListener('change', () => self.updateAccessStatus());
            }

            // Form submit
            const swmForm = this.$('#swmForm');
            if (swmForm) {
                swmForm.onsubmit = async (e) => {
                    e.preventDefault();

                    // Block duplicate submits
                    if (swmForm.dataset.sending === '1') return;
                    swmForm.dataset.sending = '1';

                    if (!self.state.currentSharedId) {
                        self.showAlert(self.config.translations.fileIdMissing, 'danger');
                        swmForm.dataset.sending = '0';
                        return;
                    }

                    const accessStatus = self.$('#swmAccessStatus').value;
                    const email = (emailInput?.value || '').trim();
                    const submitBtn = swmForm.querySelector('button[type="submit"]');

                    if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                        self.showAlert(self.config.translations.invalidEmail, 'danger');
                        emailInput?.focus();
                        swmForm.dataset.sending = '0';
                        return;
                    }

                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> ' + self.config.translations.sharing;
                    }

                    try {
                        const fd = new FormData(swmForm);
                        fd.set('access_status', accessStatus);

                        if (email) {
                            fd.set('recipients', email);
                            fd.set('permission', self.$('#swmPermission')?.value ?? 'view');
                        }

                        const response = await self.api(`${self.config.baseUrl}/${self.state.currentSharedId}/share`, {
                            method: 'POST',
                            data: fd,
                        });

                        if (response.download_link) self.state.currentDownloadLink = response.download_link;

                        self.showAlert(response.message || self.config.translations.sharedSuccessfully, 'success');

                        if (emailInput) emailInput.value = '';
                        self.closeTypeahead();
                        self.updateCopyLinkButton();
                        await self.loadPeopleWithAccess();

                    } catch (err) {
                        console.error(err);
                        self.showAlert(err.toString() || self.config.translations.sharingFailed, 'danger');
                    } finally {
                        swmForm.dataset.sending = '0';
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.textContent = self.config.translations.share;
                        }
                    }
                };
            }
        },

        /**
         * Open modal programmatically
         */
        openModal: function (fileId, fileName, fileType = 'file', shareData = '') {
            const modalEl = document.getElementById(this.config.modalId);
            if (!modalEl) return;

            // Create a synthetic button with data attributes
            const tempBtn = document.createElement('button');
            tempBtn.setAttribute('data-file-id', fileId);
            tempBtn.setAttribute('data-file-name', fileName);
            tempBtn.setAttribute('data-file-type', fileType);
            if (shareData) {
                tempBtn.setAttribute('data-share', typeof shareData === 'string' ? shareData : JSON.stringify(shareData));
            }

            // Show modal
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();

            // Trigger the show event manually with our button
            const showEvent = new CustomEvent('show.bs.modal', {
                detail: { relatedTarget: tempBtn }
            });
            modalEl.dispatchEvent(showEvent);
        }
    };

    // Export to global
    global.ShareWithMe = ShareWithMe;

})(window);
