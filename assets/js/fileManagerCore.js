/**
 * FileManager Core Module
 * Consolidated file management functionality
 * Extracted and refactored from extrachange.js for better maintainability
 *
 * Usage:
 *   const fm = new FileManagerCore({
 *     gridViewId: 'gridView',
 *     listViewId: 'listView',
 *     searchInputId: 'fileSearch',
 *     filterToggleId: 'filterToggle'
 *   });
 */
(function (global) {
    'use strict';

    /**
     * File type categories for filtering
     */
    const FILE_TYPE_MAP = {
        image: ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp', 'ico', 'tiff'],
        video: ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv', 'mpg', 'mpeg', '3gp'],
        audio: ['mp3', 'wav', 'ogg', 'aac', 'flac', 'wma', 'm4a'],
        document: ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'rtf', 'odt'],
        archive: ['zip', 'rar', '7z', 'tar', 'gz', 'bz2', 'xz'],
        installer: ['msi', 'exe', 'dmg', 'deb', 'rpm']
    };

    /**
     * Size filter thresholds in bytes
     */
    const SIZE_THRESHOLDS = {
        small: 1048576,       // < 1MB
        mediumMin: 1048576,   // >= 1MB
        mediumMax: 10485760,  // <= 10MB
        large: 10485760       // > 10MB
    };

    class FileManagerCore {
        /**
         * @param {Object} options - Configuration options
         */
        constructor(options = {}) {
            // Default configuration
            this.config = {
                gridViewId: 'gridView',
                listViewId: 'listView',
                searchInputId: 'fileSearch',
                filterToggleId: 'filterToggle',
                inlineFiltersId: 'inlineFilters',
                fileContainerId: 'fileContainer',
                selectAllId: 'selectAll',
                filesActionsClass: 'filemanager-actions',
                fileItemClass: 'file-item',
                viewStorageKey: 'fileManagerView',
                debounceMs: 300,
                ...options
            };

            // State
            this.currentView = 'grid';
            this.filters = {
                search: '',
                type: '',
                size: '',
                sort: 'created_at',
                order: 'desc'
            };
            this.allFiles = [];
            this.selectedFiles = [];
            this.isLoading = false;
            this.filtersVisible = false;
            this._debounceTimer = null;
            this._initialized = false;

            // Auto-initialize if DOM is ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => this.init());
            } else {
                this.init();
            }
        }

        /**
         * Initialize the file manager
         */
        init() {
            if (this._initialized) return;
            this._initialized = true;

            this._cacheElements();
            if (!this._hasFiles()) return;

            this._cacheFiles();
            this._bindEvents();
            this._initializeFromURL();
            this._loadSavedView();
        }

        /**
         * Cache DOM elements
         */
        _cacheElements() {
            this.$gridView = document.getElementById(this.config.gridViewId);
            this.$listView = document.getElementById(this.config.listViewId);
            this.$searchInput = document.getElementById(this.config.searchInputId);
            this.$filterToggle = document.getElementById(this.config.filterToggleId);
            this.$inlineFilters = document.getElementById(this.config.inlineFiltersId);
            this.$fileContainer = document.getElementById(this.config.fileContainerId);
            this.$selectAll = document.getElementById(this.config.selectAllId);
            this.$filesActions = document.querySelector('.' + this.config.filesActionsClass);
        }

        /**
         * Check if files exist on page
         */
        _hasFiles() {
            const gridFiles = this.$gridView?.querySelectorAll('.filemanager-file') || [];
            const listFiles = this.$listView?.querySelectorAll('.filemanager-file-list') || [];
            return gridFiles.length > 0 || listFiles.length > 0;
        }

        /**
         * Cache file data for filtering/sorting
         */
        _cacheFiles() {
            const fileItems = document.querySelectorAll('.' + this.config.fileItemClass);
            this.allFiles = Array.from(fileItems).map(item => {
                const fileName = item.dataset.fileName || '';
                const extension = this._getFileExtension(fileName);
                const originalType = item.dataset.fileType || '';

                return {
                    element: item,
                    id: item.dataset.fileId,
                    name: fileName,
                    extension: extension,
                    originalType: originalType,
                    categorizedType: this._getCategorizedType(extension, originalType),
                    size: parseInt(item.dataset.fileSize) || 0,
                    date: new Date(item.dataset.fileDate || Date.now()),
                    gridElement: this.$gridView?.querySelector(`[data-file-id="${item.dataset.fileId}"] .filemanager-file`),
                    listElement: this.$listView?.querySelector(`[data-file-id="${item.dataset.fileId}"]`)
                };
            });
        }

        /**
         * Get file extension from filename
         */
        _getFileExtension(filename) {
            if (!filename) return '';
            const parts = filename.split('.');
            return parts.length > 1 ? parts.pop().toLowerCase() : '';
        }

        /**
         * Get categorized file type
         */
        _getCategorizedType(extension, originalType) {
            for (const [category, extensions] of Object.entries(FILE_TYPE_MAP)) {
                if (extensions.includes(extension)) {
                    return category;
                }
            }
            if (originalType && Object.keys(FILE_TYPE_MAP).includes(originalType)) {
                return originalType;
            }
            return 'other';
        }

        /**
         * Bind all event listeners
         */
        _bindEvents() {
            this._bindSearchEvents();
            this._bindFilterEvents();
            this._bindViewToggle();
            this._bindSelectionEvents();
        }

        /**
         * Bind search input events
         */
        _bindSearchEvents() {
            if (!this.$searchInput) return;

            this.$searchInput.addEventListener('input', (e) => {
                clearTimeout(this._debounceTimer);
                this._debounceTimer = setTimeout(() => {
                    this.filters.search = e.target.value.toLowerCase();
                    this.applyFilters();
                }, this.config.debounceMs);
            });
        }

        /**
         * Bind filter dropdown events
         */
        _bindFilterEvents() {
            // Filter toggle
            this.$filterToggle?.addEventListener('click', () => this.toggleFilters());

            // Type filter
            this._bindFilterDropdown('#typeFilterMenu', (item) => {
                this.filters.type = item.dataset.type || '';
                this._updateFilterText('typeFilterText', this._getTypeLabel(this.filters.type));
                this.applyFilters();
            });

            // Size filter
            this._bindFilterDropdown('#sizeFilterMenu', (item) => {
                this.filters.size = item.dataset.size || '';
                this._updateFilterText('sizeFilterText', this._getSizeLabel(this.filters.size));
                this.applyFilters();
            });

            // Sort filter
            this._bindFilterDropdown('#sortFilterMenu', (item) => {
                this.filters.sort = item.dataset.sort || 'created_at';
                this.filters.order = item.dataset.order || 'desc';
                this._updateFilterText('sortFilterText', this._getSortLabel(this.filters.sort, this.filters.order));
                this.applyFilters();
            });

            // Clear filters
            document.getElementById('clearFilters')?.addEventListener('click', () => this.clearFilters());
        }

        /**
         * Bind a filter dropdown menu
         */
        _bindFilterDropdown(menuSelector, callback) {
            document.querySelectorAll(`${menuSelector} .dropdown-item`).forEach(item => {
                item.addEventListener('click', (e) => {
                    e.preventDefault();
                    callback(item);
                    // Close dropdown
                    const dropdownBtn = item.closest('.dropdown')?.querySelector('[data-bs-toggle="dropdown"]');
                    if (dropdownBtn && typeof bootstrap !== 'undefined') {
                        const dropdown = bootstrap.Dropdown.getInstance(dropdownBtn);
                        if (dropdown) dropdown.hide();
                    }
                });
            });
        }

        /**
         * Bind view toggle buttons
         */
        _bindViewToggle() {
            document.querySelectorAll('.view-toggle-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    this.switchView(e.currentTarget.dataset.view);
                });
            });
        }

        /**
         * Bind file selection events
         */
        _bindSelectionEvents() {
            // Select all checkbox
            this.$selectAll?.addEventListener('change', () => {
                const selectAll = this.$selectAll.checked;
                this.allFiles.forEach(file => this._setFileSelected(file, selectAll));
                this._updateSelectionUI();
            });

            // Individual file selection
            this.allFiles.forEach(file => {
                const checkbox = file.element.querySelector('.form-check-input');
                if (checkbox) {
                    checkbox.addEventListener('change', (e) => {
                        e.stopPropagation();
                        this._setFileSelected(file, checkbox.checked);
                        this._updateSelectionUI();
                    });
                }

                // Click on file item to toggle selection
                file.element.addEventListener('click', (e) => {
                    if (e.target.closest('.dropdown') || e.target.closest('.filemanager-link') || e.target.closest('.form-check-input')) {
                        return;
                    }
                    const checkbox = file.element.querySelector('.form-check-input');
                    if (checkbox) {
                        checkbox.checked = !checkbox.checked;
                        this._setFileSelected(file, checkbox.checked);
                        this._updateSelectionUI();
                    }
                });
            });
        }

        /**
         * Set file selection state
         */
        _setFileSelected(file, isSelected) {
            // Update grid view
            if (file.gridElement) {
                const checkbox = file.gridElement.querySelector('.form-check-input');
                if (checkbox) checkbox.checked = isSelected;
                file.gridElement.classList.toggle('selected', isSelected);
            }

            // Update list view
            if (file.listElement) {
                const checkbox = file.listElement.querySelector('.form-check-input');
                if (checkbox) checkbox.checked = isSelected;
                file.listElement.classList.toggle('selected', isSelected);
            }

            // Update selected files array
            if (isSelected) {
                if (!this.selectedFiles.includes(file.id)) {
                    this.selectedFiles.push(file.id);
                }
            } else {
                this.selectedFiles = this.selectedFiles.filter(id => id !== file.id);
            }
        }

        /**
         * Update selection UI (actions bar, select all checkbox)
         */
        _updateSelectionUI() {
            // Show/hide actions bar
            if (this.$filesActions) {
                this.$filesActions.classList.toggle('show', this.selectedFiles.length > 0);
            }

            // Update select all checkbox
            if (this.$selectAll) {
                const allSelected = this.selectedFiles.length === this.allFiles.length && this.allFiles.length > 0;
                this.$selectAll.checked = allSelected;

                const label = this.$selectAll.nextElementSibling;
                const parent = this.$selectAll.parentNode;
                if (label && parent) {
                    label.textContent = allSelected ? (parent.dataset.unselect || 'Unselect All') : (parent.dataset.select || 'Select All');
                }
            }

            // Update hidden input if exists
            const hiddenInput = document.getElementById('filesSelectedInput');
            if (hiddenInput) {
                hiddenInput.value = this.selectedFiles.join(',');
            }
        }

        /**
         * Initialize filters from URL parameters
         */
        _initializeFromURL() {
            const params = new URLSearchParams(window.location.search);

            if (params.get('search')) {
                this.filters.search = params.get('search');
                if (this.$searchInput) this.$searchInput.value = this.filters.search;
            }

            if (params.get('type')) {
                this.filters.type = params.get('type');
                this._updateFilterText('typeFilterText', this._getTypeLabel(this.filters.type));
            }

            if (params.get('size')) {
                this.filters.size = params.get('size');
                this._updateFilterText('sizeFilterText', this._getSizeLabel(this.filters.size));
            }

            if (params.get('sort')) {
                this.filters.sort = params.get('sort');
                this.filters.order = params.get('order') || 'desc';
                this._updateFilterText('sortFilterText', this._getSortLabel(this.filters.sort, this.filters.order));
            }

            if (this._hasActiveFilters()) {
                this.showFilters();
            }
        }

        /**
         * Load saved view preference
         */
        _loadSavedView() {
            const savedView = localStorage.getItem(this.config.viewStorageKey);
            if (savedView) {
                this.switchView(savedView);
            }
        }

        /**
         * Switch between grid and list view
         */
        switchView(view) {
            this.currentView = view;

            // Update buttons
            document.querySelectorAll('.view-toggle-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.view === view);
            });

            // Switch views
            if (this.$gridView && this.$listView) {
                this.$gridView.classList.toggle('d-none', view !== 'grid');
                this.$listView.classList.toggle('d-none', view !== 'list');
            }

            // Save preference
            localStorage.setItem(this.config.viewStorageKey, view);
        }

        /**
         * Toggle inline filters visibility
         */
        toggleFilters() {
            if (this.filtersVisible) {
                this.hideFilters();
            } else {
                this.showFilters();
            }
        }

        /**
         * Show inline filters
         */
        showFilters() {
            if (!this.$inlineFilters || !this.$filterToggle) return;

            this.$inlineFilters.classList.remove('d-none');
            this.$inlineFilters.classList.add('d-flex');
            this.$filterToggle.classList.add('filter-active');

            const btnText = document.getElementById('filterButtonText');
            if (btnText) btnText.textContent = 'Hide Filter';

            this.filtersVisible = true;
        }

        /**
         * Hide inline filters
         */
        hideFilters() {
            if (!this.$inlineFilters || !this.$filterToggle) return;

            this.$inlineFilters.classList.add('d-none');
            this.$inlineFilters.classList.remove('d-flex');
            this.$filterToggle.classList.remove('filter-active');

            const btnText = document.getElementById('filterButtonText');
            if (btnText) btnText.textContent = 'Filter';

            this.filtersVisible = false;
        }

        /**
         * Check if any filters are active
         */
        _hasActiveFilters() {
            return this.filters.search ||
                this.filters.type ||
                this.filters.size ||
                this.filters.sort !== 'created_at' ||
                this.filters.order !== 'desc';
        }

        /**
         * Apply all filters and sort
         */
        applyFilters() {
            this._showLoading(true);

            // Use setTimeout to allow UI to update
            setTimeout(() => {
                let filtered = [...this.allFiles];

                // Search filter
                if (this.filters.search) {
                    const search = this.filters.search.toLowerCase();
                    filtered = filtered.filter(f => f.name.toLowerCase().includes(search));
                }

                // Type filter
                if (this.filters.type) {
                    filtered = filtered.filter(f => f.categorizedType === this.filters.type);
                }

                // Size filter
                if (this.filters.size) {
                    filtered = filtered.filter(f => this._matchesSizeFilter(f.size, this.filters.size));
                }

                // Sort
                filtered.sort((a, b) => {
                    let result = 0;
                    switch (this.filters.sort) {
                        case 'name':
                            result = a.name.toLowerCase().localeCompare(b.name.toLowerCase());
                            break;
                        case 'size':
                            result = a.size - b.size;
                            break;
                        case 'created_at':
                        default:
                            result = a.date.getTime() - b.date.getTime();
                            break;
                    }
                    return this.filters.order === 'desc' ? -result : result;
                });

                this._renderFiltered(filtered);
                this._showLoading(false);
            }, 50);
        }

        /**
         * Check if file size matches filter
         */
        _matchesSizeFilter(size, filter) {
            switch (filter) {
                case 'small': return size < SIZE_THRESHOLDS.small;
                case 'medium': return size >= SIZE_THRESHOLDS.mediumMin && size <= SIZE_THRESHOLDS.mediumMax;
                case 'large': return size > SIZE_THRESHOLDS.large;
                default: return true;
            }
        }

        /**
         * Render filtered files
         */
        _renderFiltered(filteredFiles) {
            // Remove existing empty state
            const emptyState = document.getElementById('emptyState');
            if (emptyState) emptyState.remove();

            // Hide all files
            this.allFiles.forEach(f => f.element.style.display = 'none');

            // Show filtered files
            if (filteredFiles.length > 0) {
                filteredFiles.forEach(f => f.element.style.display = '');
            } else {
                this._showEmptyState();
            }
        }

        /**
         * Show empty state
         */
        _showEmptyState() {
            if (!this.$fileContainer) return;

            const emptyState = document.createElement('div');
            emptyState.id = 'emptyState';
            emptyState.className = 'text-center py-5';
            emptyState.innerHTML = `
                <div class="mb-3">
                    <i class="fas fa-folder-open fa-4x text-muted"></i>
                </div>
                <h5 class="text-muted">No files found</h5>
                <p class="text-muted mb-0">Try adjusting your search or filters</p>
            `;
            this.$fileContainer.appendChild(emptyState);
        }

        /**
         * Show/hide loading state
         */
        _showLoading(show) {
            this.isLoading = show;
            this.$fileContainer?.classList.toggle('loading', show);
        }

        /**
         * Clear all filters
         */
        clearFilters() {
            this.filters = {
                search: '',
                type: '',
                size: '',
                sort: 'created_at',
                order: 'desc'
            };

            // Reset UI
            if (this.$searchInput) this.$searchInput.value = '';
            this._updateFilterText('typeFilterText', 'Type');
            this._updateFilterText('sizeFilterText', 'Size');
            this._updateFilterText('sortFilterText', 'Sort');

            this.applyFilters();
            this.hideFilters();

            // Update URL
            window.history.pushState({}, '', window.location.pathname);
        }

        /**
         * Update filter button text
         */
        _updateFilterText(elementId, text) {
            const el = document.getElementById(elementId);
            if (el) el.textContent = text;
        }

        /**
         * Get type filter label
         */
        _getTypeLabel(type) {
            const labels = {
                image: 'Images',
                document: 'Documents',
                video: 'Videos',
                audio: 'Audio',
                archive: 'Archives',
                other: 'Others'
            };
            return labels[type] || 'Type';
        }

        /**
         * Get size filter label
         */
        _getSizeLabel(size) {
            const labels = {
                small: 'Small (<1MB)',
                medium: 'Medium (1-10MB)',
                large: 'Large (>10MB)'
            };
            return labels[size] || 'Size';
        }

        /**
         * Get sort filter label
         */
        _getSortLabel(sort, order) {
            if (sort === 'name') return order === 'asc' ? 'A-Z' : 'Z-A';
            if (sort === 'size') return order === 'desc' ? 'Largest' : 'Smallest';
            if (sort === 'created_at') return order === 'desc' ? 'Newest' : 'Oldest';
            return 'Sort';
        }

        /**
         * Get selected file IDs
         */
        getSelectedFiles() {
            return [...this.selectedFiles];
        }

        /**
         * Clear file selection
         */
        clearSelection() {
            this.allFiles.forEach(file => this._setFileSelected(file, false));
            this.selectedFiles = [];
            this._updateSelectionUI();
        }
    }

    // Export to global
    global.FileManagerCore = FileManagerCore;

})(window);
