/**
 * ShareWithMe - Unified jQuery Module
 * Handles all share functionality across index.blade.php and browse.blade.php
 * Optimized, no duplication, fast loading
 */
(function ($) {
    'use strict';

    if (typeof $ === 'undefined') {
        console.error('ShareWithMe: jQuery required');
        return;
    }

    // Prevent duplicate initialization
    if (window.ShareWithMe && window.ShareWithMe._ready) return;

    var ShareWithMe = {
        _ready: false,

        // Configuration
        config: {
            baseUrl: '/user/files',
            csrfToken: '',
            translations: {
                owner: 'Owner',
                editor: 'Editor',
                viewer: 'Viewer',
                share: 'Share',
                sharing: 'Sharing...',
                copied: 'Link copied!',
                noCopy: 'Failed to copy',
                noLink: 'No link available',
                statusUpdated: 'Status updated',
                accessRemoved: 'Access removed',
                permUpdated: 'Permission updated',
                sharedSuccess: 'Shared successfully',
                invalidEmail: 'Please enter a valid email',
                confirmRemove: 'Remove access?'
            }
        },

        // State
        state: {
            currentId: null,
            currentType: null,
            downloadLink: '',
            suggestItems: [],
            suggestOpen: false,
            suggestIndex: -1
        },

        /**
         * Initialize module
         */
        init: function (options) {
            if (this._ready) return this;

            $.extend(true, this.config, options || {});
            this.config.csrfToken = $('meta[name="csrf-token"]').attr('content') || '';

            this._bindModal();
            this._ready = true;
            return this;
        },

        /**
         * Show alert in modal
         */
        alert: function (msg, type) {
            var $box = $('#swmAlert');
            if (!$box.length) return;

            $box.removeClass('d-none alert-success alert-danger alert-warning')
                .addClass('alert-' + (type || 'danger'))
                .text(msg);

            setTimeout(function () { $box.addClass('d-none'); }, 3500);
        },

        /**
         * Bind modal events
         */
        _bindModal: function () {
            var self = this;
            var $modal = $('#sharedWithMeModal');

            if (!$modal.length) return;

            // Modal open
            $modal.on('show.bs.modal', function (e) {
                var $btn = $(e.relatedTarget);
                self.state.currentId = $btn.data('file-id');
                self.state.currentType = $btn.data('type') || 'file';

                var name = $btn.data('file-name') || '';
                $('#swmFileName').text(name ? '"' + name + '"' : '');

                // Reset
                $('#swmForm')[0] && $('#swmForm')[0].reset();
                $('#swmEmail').val('');
                $('#swmAlert').addClass('d-none');
                self._closeTypeahead();

                // Load data
                self._loadData();
            });

            // Modal close
            $modal.on('hidden.bs.modal', function () {
                self.state.currentId = null;
                self.state.downloadLink = '';
                self._closeTypeahead();
            });

            // Form submit
            $('#swmForm').on('submit', function (e) {
                e.preventDefault();
                self._submit();
            });

            // Access status change
            $('#swmAccessStatus').on('change', function () {
                self._updateStatus();
            });

            // Copy link
            $('#swmCopyLink').on('click', function () {
                self._copyLink();
            });

            // Email typeahead
            this._bindTypeahead();

            // Delegated events for people list
            $(document).on('click', '.swm-remove-person', function () {
                var id = $(this).data('id');
                if (confirm(self.config.translations.confirmRemove)) {
                    self._removePerson(id);
                }
            });

            $(document).on('click', '.swm-change-perm', function (e) {
                e.preventDefault();
                self._changePerm($(this).data('id'), $(this).data('perm'));
            });
        },

        /**
         * Bind email typeahead
         */
        _bindTypeahead: function () {
            var self = this;
            var $input = $('#swmEmail');
            var $dropdown = $('#swmTypeahead');

            if (!$input.length || !$dropdown.length) return;

            $input.on('input', function () {
                var q = $(this).val().trim();
                if (q.length >= 1) {
                    self._fetchSuggestions(q);
                } else {
                    self._closeTypeahead();
                }
            });

            $input.on('keydown', function (e) {
                if (!self.state.suggestOpen) return;

                var len = self.state.suggestItems.length;
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    self.state.suggestIndex = (self.state.suggestIndex + 1) % len;
                    self._renderTypeahead();
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    self.state.suggestIndex = (self.state.suggestIndex - 1 + len) % len;
                    self._renderTypeahead();
                } else if (e.key === 'Enter' && self.state.suggestIndex >= 0) {
                    e.preventDefault();
                    self._selectSuggestion(self.state.suggestIndex);
                } else if (e.key === 'Escape') {
                    self._closeTypeahead();
                }
            });

            $input.on('blur', function () {
                setTimeout(function () { self._closeTypeahead(); }, 150);
            });
        },

        /**
         * Fetch email suggestions
         */
        _fetchSuggestions: function (query) {
            var self = this;

            $.ajax({
                url: '/user/contacts/search',
                data: { q: query },
                headers: this._headers(),
                success: function (data) {
                    self.state.suggestItems = data.contacts || data || [];
                    self.state.suggestIndex = -1;
                    if (self.state.suggestItems.length) {
                        self.state.suggestOpen = true;
                        self._renderTypeahead();
                    } else {
                        self._closeTypeahead();
                    }
                },
                error: function () {
                    self._closeTypeahead();
                }
            });
        },

        /**
         * Render typeahead dropdown
         */
        _renderTypeahead: function () {
            var $dropdown = $('#swmTypeahead');
            var items = this.state.suggestItems;
            var html = '';

            for (var i = 0; i < items.length; i++) {
                var item = items[i];
                var email = item.email || '';
                var name = item.name || email;
                var active = i === this.state.suggestIndex ? 'active' : '';

                html += '<a class="dropdown-item ' + active + '" href="#" data-idx="' + i + '">' +
                    '<div class="fw-medium">' + this._escape(name) + '</div>' +
                    '<small class="text-muted">' + this._escape(email) + '</small></a>';
            }

            $dropdown.html(html).addClass('show');

            var self = this;
            $dropdown.find('.dropdown-item').on('click', function (e) {
                e.preventDefault();
                self._selectSuggestion($(this).data('idx'));
            });
        },

        /**
         * Select suggestion
         */
        _selectSuggestion: function (idx) {
            var item = this.state.suggestItems[idx];
            if (item) {
                $('#swmEmail').val(item.email || '');
            }
            this._closeTypeahead();
        },

        /**
         * Close typeahead
         */
        _closeTypeahead: function () {
            this.state.suggestOpen = false;
            this.state.suggestIndex = -1;
            $('#swmTypeahead').removeClass('show').html('');
        },

        /**
         * Load file data
         */
        _loadData: function () {
            var self = this;
            if (!this.state.currentId) return;

            $.ajax({
                url: this.config.baseUrl + '/' + this.state.currentId,
                headers: this._headers(),
                success: function (r) {
                    $('#swmAccessStatus').val(r.access_status || '0');
                    self.state.downloadLink = r.download_link || '';
                    self._updateUI();
                    self._loadPeople();
                },
                error: function () {
                    self._updateUI();
                }
            });
        },

        /**
         * Load people with access
         */
        _loadPeople: function () {
            var self = this;
            if (!this.state.currentId) return;

            $.ajax({
                url: this.config.baseUrl + '/' + this.state.currentId + '/shared-people',
                headers: this._headers(),
                success: function (r) {
                    self._renderPeople(r.people || [], r.owner, r.uploader);
                },
                error: function () {
                    self._renderPeople([], null, null);
                }
            });
        },

        /**
         * Render people list
         */
        _renderPeople: function (people, owner, uploader) {
            var $list = $('#swmPeopleList');
            var $section = $('#swmPeopleSection');

            if (!$list.length) return;

            if (!people.length && !owner) {
                $section.hide();
                return;
            }

            $section.show();
            var html = '';
            var T = this.config.translations;

            // Owner
            if (owner) {
                var ownerName = owner.name || owner.email || 'Owner';
                html += '<div class="person-item d-flex align-items-center py-2 border-bottom">' +
                    '<div class="avatar me-2"><div class="bg-success rounded-circle d-flex align-items-center justify-content-center text-white" style="width:32px;height:32px;">' +
                    this._escape(ownerName.charAt(0).toUpperCase()) + '</div></div>' +
                    '<div class="flex-grow-1"><div class="fw-medium">' + this._escape(ownerName) + '</div>' +
                    '<div class="text-muted small">' + this._escape(owner.email || '') + '</div></div>' +
                    '<span class="badge bg-success">' + T.owner + '</span></div>';
            }

            // People
            for (var i = 0; i < people.length; i++) {
                var p = people[i];
                var email = p.recipient_email || p.email || '';
                var name = p.recipient_name || p.name || email;
                var perm = p.permission === 'edit' ? T.editor : T.viewer;
                var active = p.status === 'active' && !p.revoked_at;

                html += '<div class="person-item d-flex align-items-center justify-content-between py-2 border-bottom">' +
                    '<div class="d-flex align-items-center flex-grow-1">' +
                    '<div class="avatar me-2"><div class="bg-primary rounded-circle d-flex align-items-center justify-content-center text-white" style="width:32px;height:32px;">' +
                    this._escape(name.charAt(0).toUpperCase()) + '</div></div>' +
                    '<div class="flex-grow-1"><div class="fw-medium">' + this._escape(name) + '</div>' +
                    '<div class="text-muted small">' + this._escape(email) + '</div></div></div>' +
                    '<div class="d-flex align-items-center">' +
                    '<div class="dropdown me-2"><button class="btn btn-sm bg-primary text-white dropdown-toggle" data-bs-toggle="dropdown"' + (active ? '' : ' disabled') + '>' + perm + '</button>' +
                    '<ul class="dropdown-menu dropdown-menu-end">' +
                    '<li><a class="dropdown-item swm-change-perm" href="#" data-perm="view" data-id="' + p.id + '">' + T.viewer + '</a></li>' +
                    '<li><a class="dropdown-item swm-change-perm" href="#" data-perm="edit" data-id="' + p.id + '">' + T.editor + '</a></li></ul></div>' +
                    (active ? '<button class="btn btn-sm btn-outline-danger swm-remove-person" data-id="' + p.id + '"><i class="fas fa-times"></i></button>' : '') +
                    '</div></div>';
            }

            $list.html(html);
        },

        /**
         * Update UI elements
         */
        _updateUI: function () {
            var isPublic = $('#swmAccessStatus').val() === '1';
            $('#swmCopyLinkGroup').toggle(isPublic);
            $('#swmSocialIcons').toggle(isPublic);
        },

        /**
         * Update access status
         */
        _updateStatus: function () {
            var self = this;
            var status = $('#swmAccessStatus').val();

            $('#swmAccessStatus').prop('disabled', true);

            $.ajax({
                url: '/user/files/' + this.state.currentId + '/update-status',
                type: 'POST',
                contentType: 'application/json',
                headers: this._headers(),
                data: JSON.stringify({ access_status: status }),
                success: function (r) {
                    self.state.downloadLink = r.download_link || self.state.downloadLink;
                    self._updateUI();
                    self.alert(self.config.translations.statusUpdated, 'success');
                },
                error: function (xhr) {
                    self.alert(xhr.responseJSON?.message || 'Error', 'danger');
                },
                complete: function () {
                    $('#swmAccessStatus').prop('disabled', false);
                }
            });
        },

        /**
         * Submit share form
         */
        _submit: function () {
            var self = this;
            var T = this.config.translations;
            var email = $('#swmEmail').val().trim();
            var permission = $('#swmPermission').val() || 'view';
            var status = $('#swmAccessStatus').val();
            var $btn = $('#swmForm button[type="submit"]');

            // Validate
            if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                self.alert(T.invalidEmail, 'danger');
                $('#swmEmail').focus();
                return;
            }

            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> ' + T.sharing);

            var data = { access_status: status };
            if (email) {
                data.recipients = email;
                data.permission = permission;
            }

            $.ajax({
                url: this.config.baseUrl + '/' + this.state.currentId + '/share',
                type: 'POST',
                contentType: 'application/json',
                headers: this._headers(),
                data: JSON.stringify(data),
                success: function (r) {
                    self.alert(r.message || T.sharedSuccess, 'success');
                    $('#swmEmail').val('');
                    if (r.download_link) self.state.downloadLink = r.download_link;
                    self._updateUI();
                    self._loadPeople();
                },
                error: function (xhr) {
                    self.alert(xhr.responseJSON?.message || 'Error', 'danger');
                },
                complete: function () {
                    $btn.prop('disabled', false).text(T.share);
                }
            });
        },

        /**
         * Remove person
         */
        _removePerson: function (id) {
            var self = this;

            $.ajax({
                url: '/user/shares/' + id + '/remove',
                type: 'DELETE',
                headers: this._headers(),
                success: function (r) {
                    self.alert(r.message || self.config.translations.accessRemoved, 'success');
                    self._loadPeople();
                },
                error: function (xhr) {
                    self.alert(xhr.responseJSON?.message || 'Error', 'danger');
                }
            });
        },

        /**
         * Change permission
         */
        _changePerm: function (id, perm) {
            var self = this;

            $.ajax({
                url: '/user/shares/' + id + '/update-permission',
                type: 'PUT',
                contentType: 'application/json',
                headers: this._headers(),
                data: JSON.stringify({ permission: perm }),
                success: function (r) {
                    self.alert(r.message || self.config.translations.permUpdated, 'success');
                    self._loadPeople();
                },
                error: function (xhr) {
                    self.alert(xhr.responseJSON?.message || 'Error', 'danger');
                }
            });
        },

        /**
         * Copy link
         */
        _copyLink: function () {
            var self = this;
            var T = this.config.translations;
            var link = this.state.downloadLink;

            if (!link) {
                self.alert(T.noLink, 'danger');
                return;
            }

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(link)
                    .then(function () { self.alert(T.copied, 'success'); })
                    .catch(function () { self._fallbackCopy(link); });
            } else {
                this._fallbackCopy(link);
            }
        },

        /**
         * Fallback copy
         */
        _fallbackCopy: function (text) {
            var self = this;
            var $t = $('<textarea>').val(text).css({ position: 'fixed', left: '-9999px' }).appendTo('body');
            $t[0].select();
            try {
                document.execCommand('copy');
                self.alert(self.config.translations.copied, 'success');
            } catch (e) {
                self.alert(self.config.translations.noCopy, 'danger');
            }
            $t.remove();
        },

        /**
         * Get headers
         */
        _headers: function () {
            return {
                'X-CSRF-TOKEN': this.config.csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            };
        },

        /**
         * Escape HTML
         */
        _escape: function (s) {
            return $('<div>').text(s || '').html();
        }
    };

    // Auto-init on DOM ready
    $(function () {
        ShareWithMe.init();
    });

    // Export
    window.ShareWithMe = ShareWithMe;

})(jQuery);
