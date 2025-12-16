
document.addEventListener('DOMContentLoaded', function () {
    // File Check - Updated for Grid and List Views
    let gridFiles = document.querySelectorAll("#gridView .filemanager-file"),
        listFiles = document.querySelectorAll("#listView .filemanager-file-list"),
        filesActions = document.querySelector(".filemanager-actions"),
        fileSelectAll = document.querySelector(".filemanager-select-all"),
        filesSelectedInput = document.getElementById("filesSelectedInput"),
        filesArray = [];

    // Skip file selection setup if no files exist on page (e.g., Doc Requests page)
    if (gridFiles.length === 0 && listFiles.length === 0) {
        return;
    }

    // Get all unique files (combine grid and list)
    let allFiles = [];

    // Add grid files to allFiles array
    gridFiles.forEach(file => {
        const fileId = file.closest('[data-file-id]').getAttribute('data-file-id');
        if (!allFiles.find(f => f.id === fileId)) {
            allFiles.push({
                id: fileId,
                gridElement: file,
                listElement: document.querySelector(`#listView [data-file-id="${fileId}"]`)
            });
        }
    });

    function checkSelectedFiles() {
        let selectedFiles = allFiles.filter(file => {
            const gridCheckbox = file.gridElement.querySelector(".form-check-input");
            return gridCheckbox && gridCheckbox.checked;
        });

        // Show/hide actions bar - check if element exists first
        if (filesActions) {
            if (selectedFiles.length > 0) {
                filesActions.classList.add("show");
            } else {
                filesActions.classList.remove("show");
            }
        }

        // Update Select All button - check if element exists first
        if (fileSelectAll) {
            if (selectedFiles.length === allFiles.length) {
                fileSelectAll.checked = true;
                if (fileSelectAll.nextElementSibling && fileSelectAll.parentNode) {
                    fileSelectAll.nextElementSibling.textContent = fileSelectAll.parentNode.getAttribute("data-unselect");
                }
            } else {
                fileSelectAll.checked = false;
                if (fileSelectAll.nextElementSibling && fileSelectAll.parentNode) {
                    fileSelectAll.nextElementSibling.textContent = fileSelectAll.parentNode.getAttribute("data-select");
                }
            }
        }

        // Update filesArray and hidden input
        filesArray = selectedFiles.map(file => file.id);
        if (filesSelectedInput) {
            filesSelectedInput.value = filesArray.join(',');
        }
    }

    function syncFileSelection(fileId, isSelected) {
        const file = allFiles.find(f => f.id === fileId);
        if (!file) return;

        // Update grid view
        const gridCheckbox = file.gridElement.querySelector(".form-check-input");
        if (gridCheckbox) {
            gridCheckbox.checked = isSelected;
        }

        if (isSelected) {
            file.gridElement.classList.add("selected");
        } else {
            file.gridElement.classList.remove("selected");
        }

        // Update list view
        if (file.listElement) {
            const listCheckbox = file.listElement.querySelector(".form-check-input");
            if (listCheckbox) {
                listCheckbox.checked = isSelected;
            }

            if (isSelected) {
                file.listElement.classList.add("selected");
            } else {
                file.listElement.classList.remove("selected");
            }
        }
    }

    // Setup event handlers for each file
    allFiles.forEach(file => {
        const gridCheckbox = file.gridElement.querySelector(".form-check-input");
        const gridLinks = file.gridElement.querySelectorAll(".filemanager-link");
        const gridDropdown = file.gridElement.querySelector(".dropdown");

        let listCheckbox, listLinks, listDropdown;
        if (file.listElement) {
            listCheckbox = file.listElement.querySelector(".form-check-input");
            listLinks = file.listElement.querySelectorAll(".filemanager-link");
            listDropdown = file.listElement.querySelector(".dropdown");
        }

        // Grid checkbox change event
        if (gridCheckbox) {
            gridCheckbox.onchange = (e) => {
                e.stopPropagation();
                syncFileSelection(file.id, gridCheckbox.checked);
                checkSelectedFiles();
            };

            gridCheckbox.onclick = (e) => {
                e.stopPropagation();
            };
        }

        // List checkbox change event
        if (listCheckbox) {
            listCheckbox.onchange = (e) => {
                e.stopPropagation();
                syncFileSelection(file.id, listCheckbox.checked);
                checkSelectedFiles();
            };

            listCheckbox.onclick = (e) => {
                e.stopPropagation();
            };
        }

        // Grid file click event
        file.gridElement.onclick = (e) => {
            // Don't toggle if clicking on dropdown or links
            if (e.target.closest('.dropdown') || e.target.closest('.filemanager-link')) {
                return;
            }

            const newState = !gridCheckbox.checked;
            syncFileSelection(file.id, newState);
            checkSelectedFiles();
        };

        // List file click event
        if (file.listElement) {
            file.listElement.onclick = (e) => {
                // Don't toggle if clicking on dropdown or links
                if (e.target.closest('.dropdown') || e.target.closest('.filemanager-link')) {
                    return;
                }

                const newState = !listCheckbox.checked;
                syncFileSelection(file.id, newState);
                checkSelectedFiles();
            };
        }

        // Prevent link clicks from toggling selection
        if (gridLinks) {
            gridLinks.forEach(link => {
                link.onclick = (e) => {
                    e.stopPropagation();
                };
            });
        }

        if (listLinks) {
            listLinks.forEach(link => {
                link.onclick = (e) => {
                    e.stopPropagation();
                };
            });
        }

        // Prevent dropdown clicks from toggling selection
        if (gridDropdown) {
            gridDropdown.onclick = (e) => {
                e.stopPropagation();
            };
        }

        if (listDropdown) {
            listDropdown.onclick = (e) => {
                e.stopPropagation();
            };
        }
    });

    // Select All functionality
    if (fileSelectAll) {
        fileSelectAll.onchange = () => {
            const selectAll = fileSelectAll.checked;

            allFiles.forEach(file => {
                syncFileSelection(file.id, selectAll);
            });

            checkSelectedFiles();
        };
    }

    // View Toggle Functionality
    const viewToggleBtns = document.querySelectorAll('.view-toggle-btn');
    const gridView = document.getElementById('gridView');
    const listView = document.getElementById('listView');

    if (viewToggleBtns.length > 0) {
        viewToggleBtns.forEach(btn => {
            btn.addEventListener('click', function () {
                const viewType = this.getAttribute('data-view');

                // Update button states
                viewToggleBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');

                // Switch views
                if (viewType === 'grid') {
                    listView.classList.add('d-none');
                    gridView.classList.remove('d-none');
                } else {
                    gridView.classList.add('d-none');
                    listView.classList.remove('d-none');
                }

                // Save view preference
                localStorage.setItem('fileManagerView', viewType);
            });
        });

        // Load saved view
        const savedView = localStorage.getItem('fileManagerView');
        if (savedView) {
            const savedViewBtn = document.querySelector(`[data-view="${savedView}"]`);
            if (savedViewBtn) {
                savedViewBtn.click();
            }
        }
    }

    // Search functionality
    const searchInput = document.getElementById('fileSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const searchTerm = this.value.toLowerCase();

            allFiles.forEach(file => {
                const gridTitle = file.gridElement.querySelector('.filemanager-file-title');
                const listTitle = file.listElement ? file.listElement.querySelector('h6') : null;

                let matches = false;
                if (gridTitle && gridTitle.textContent.toLowerCase().includes(searchTerm)) {
                    matches = true;
                }
                if (listTitle && listTitle.textContent.toLowerCase().includes(searchTerm)) {
                    matches = true;
                }

                // Show/hide in grid view
                const gridContainer = file.gridElement.closest('[data-file-id]');
                if (gridContainer) {
                    gridContainer.style.display = matches ? 'block' : 'none';
                }

                // Show/hide in list view
                if (file.listElement) {
                    file.listElement.style.display = matches ? 'block' : 'none';
                }
            });
        });
    }

    // Initial check - ONLY if files exist
    checkSelectedFiles();
});




class FileManager {
    constructor() {
        this.currentView = 'grid';
        this.filters = {
            search: '',
            type: '',
            size: '',
            sort: 'created_at',
            order: 'desc'
        };
        this.allFiles = [];
        this.isLoading = false;
        this.debounceTimer = null;
        this.filtersVisible = false;

        // File type mapping for proper categorization
        this.fileTypeMap = {
            image: ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp', 'ico', 'tiff'],
            video: ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv', 'mpg', 'mpeg', '3gp'],
            audio: ['mp3', 'wav', 'ogg', 'aac', 'flac', 'wma', 'm4a'],
            document: ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'rtf', 'odt'],
            archive: ['zip', 'rar', '7z', 'tar', 'gz', 'bz2', 'xz'],
            installer: ['msi'] // ðŸ‘ˆ add this (or put 'msi' under document if you prefer)
        };


        this.init();
    }

    init() {
        this.cacheFiles();
        this.bindEvents();
        this.initializeFromURL();
        // this.updateFilterVisibility();
    }

    initializeFromURL() {
        const urlParams = new URLSearchParams(window.location.search);

        if (urlParams.get('search')) {
            this.filters.search = urlParams.get('search');
            document.getElementById('fileSearch').value = this.filters.search;
        }

        if (urlParams.get('type')) {
            this.filters.type = urlParams.get('type');
            this.updateFilterButtonText('type', urlParams.get('type'));
        }

        if (urlParams.get('size')) {
            this.filters.size = urlParams.get('size');
            this.updateFilterButtonText('size', urlParams.get('size'));
        }

        if (urlParams.get('sort')) {
            this.filters.sort = urlParams.get('sort');
            this.filters.order = urlParams.get('order') || 'desc';
            this.updateFilterButtonText('sort', urlParams.get('sort'), urlParams.get('order'));
        }

        // Show filters if any are applied
        if (this.hasActiveFilters()) {
            this.showInlineFilters();
        }
    }

    cacheFiles() {
        this.allFiles = Array.from(document.querySelectorAll('.file-item')).map(item => {
            const fileName = item.dataset.fileName || '';
            const fileExtension = this.getFileExtension(fileName);
            const originalType = item.dataset.fileType || '';

            return {
                element: item,
                id: item.dataset.fileId,
                name: fileName,
                extension: fileExtension,
                originalType: originalType,
                categorizedType: this.getCategorizedType(fileExtension, originalType),
                size: parseInt(item.dataset.fileSize) || 0,
                date: new Date(item.dataset.fileDate || Date.now())
            };
        });
    }

    getFileExtension(filename) {
        if (!filename) return '';
        const parts = filename.split('.');
        return parts.length > 1 ? parts.pop().toLowerCase() : '';
    }

    getCategorizedType(extension, originalType) {
        for (const [category, extensions] of Object.entries(this.fileTypeMap)) {
            if (extensions.includes(extension)) {
                return category;
            }
        }

        if (originalType && Object.keys(this.fileTypeMap).includes(originalType)) {
            return originalType;
        }

        return 'other';
    }

    bindEvents() {
        // Real-time search
        const searchInput = document.getElementById('fileSearch');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                clearTimeout(this.debounceTimer);
                this.debounceTimer = setTimeout(() => {
                    this.filters.search = e.target.value.toLowerCase();
                    this.applyFiltersInstantly();
                    // this.updateFilterVisibility();
                }, 300);
            });
        }

        // Filter toggle
        const filterToggle = document.getElementById('filterToggle');
        if (filterToggle) {
            filterToggle.addEventListener('click', () => {
                this.toggleInlineFilters();
            });
        }

        // View toggle
        document.querySelectorAll('.view-toggle-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const view = e.currentTarget.dataset.view;
                this.switchView(view);
            });
        });

        // Real-time filter dropdowns
        this.bindFilterDropdowns();

        // Clear filters
        const clearButton = document.getElementById('clearFilters');
        if (clearButton) {
            clearButton.addEventListener('click', () => {
                this.clearFilters();
            });
        }
    }

    bindFilterDropdowns() {
        // Type filter - instant apply
        document.querySelectorAll('#typeFilterMenu .dropdown-item').forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                const type = e.target.dataset.type;
                this.filters.type = type;
                this.updateFilterButtonText('type', type);
                this.applyFiltersInstantly();
                // this.updateFilterVisibility();

                // Close dropdown
                const dropdown = bootstrap.Dropdown.getInstance(document.getElementById('typeFilterDropdown'));
                if (dropdown) dropdown.hide();
            });
        });

        // Size filter - instant apply
        document.querySelectorAll('#sizeFilterMenu .dropdown-item').forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                const size = e.target.dataset.size;
                this.filters.size = size;
                this.updateFilterButtonText('size', size);
                this.applyFiltersInstantly();
                // this.updateFilterVisibility();

                // Close dropdown
                const dropdown = bootstrap.Dropdown.getInstance(document.getElementById('sizeFilterDropdown'));
                if (dropdown) dropdown.hide();
            });
        });

        // Sort filter - instant apply with FIXED logic
        document.querySelectorAll('#sortFilterMenu .dropdown-item').forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                const sort = e.target.dataset.sort;
                const order = e.target.dataset.order;

                this.filters.sort = sort;
                this.filters.order = order;
                this.updateFilterButtonText('sort', sort, order);
                this.applyFiltersInstantly();
                // this.updateFilterVisibility();

                // Close dropdown
                const dropdown = bootstrap.Dropdown.getInstance(document.getElementById('sortFilterDropdown'));
                if (dropdown) dropdown.hide();
            });
        });
    }

    updateFilterButtonText(filterType, value, order = null) {
        let text = '';

        switch (filterType) {
            case 'type':
                switch (value) {
                    case 'image': text = 'Images'; break;
                    case 'document': text = 'Documents'; break;
                    case 'video': text = 'Videos'; break;
                    case 'audio': text = 'Audio'; break;
                    case 'archive': text = 'Archives'; break;
                    case 'other': text = 'Others'; break;
                    default: text = 'Type'; break;
                }
                document.getElementById('typeFilterText').textContent = text;
                break;

            case 'size':
                switch (value) {
                    case 'small': text = 'Small'; break;
                    case 'medium': text = 'Medium'; break;
                    case 'large': text = 'Large'; break;
                    default: text = 'Size'; break;
                }
                document.getElementById('sizeFilterText').textContent = text;
                break;

            case 'sort':
                if (value === 'name') {
                    text = order === 'asc' ? 'A-Z' : 'Z-A';
                } else if (value === 'size') {
                    text = order === 'desc' ? 'Largest' : 'Smallest';
                } else if (value === 'created_at') {
                    text = order === 'desc' ? 'Newest' : 'Oldest';
                } else {
                    text = 'Sort';
                }
                document.getElementById('sortFilterText').textContent = text;
                break;
        }
    }

    toggleInlineFilters() {
        if (this.filtersVisible) {
            this.hideInlineFilters();
        } else {
            this.showInlineFilters();
        }
    }

    showInlineFilters() {
        const btn = document.getElementById('filterToggle');
        document.getElementById('inlineFilters').classList.remove('d-none');
        document.getElementById('inlineFilters').classList.add('d-flex');
        document.getElementById('filterButtonText').textContent = 'Hide Filter';
        btn.classList.add('filter-active');
        this.filtersVisible = true;
    }

    hideInlineFilters() {
        const btn = document.getElementById('filterToggle');
        document.getElementById('inlineFilters').classList.add('d-none');
        document.getElementById('inlineFilters').classList.remove('d-flex');
        document.getElementById('filterButtonText').textContent = 'Filter';
        btn.classList.remove('filter-active');
        this.filtersVisible = false;
    }

    hasActiveFilters() {
        return this.filters.search || this.filters.type || this.filters.size ||
            (this.filters.sort !== 'created_at' || this.filters.order !== 'desc');
    }

    // updateFilterVisibility() {
    //     const hasFilters = this.hasActiveFilters();
    //     const clearButton = document.getElementById('clearFilters');

    //     if (hasFilters) {
    //         clearButton.classList.remove('d-none');
    //         clearButton.classList.add('d-flex');
    //         if (!this.filtersVisible) {
    //             this.showInlineFilters();
    //         }
    //     } else {
    //         clearButton.classList.add('d-none');
    //         clearButton.classList.remove('d-flex');
    //         // Keep filters visible if user manually opened them
    //     }
    // }

    switchView(view) {
        this.currentView = view;

        // Update buttons
        document.querySelectorAll('.view-toggle-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`[data-view="${view}"]`).classList.add('active');

        // Switch views
        if (view === 'grid') {
            document.getElementById('gridView').classList.remove('d-none');
            document.getElementById('listView').classList.add('d-none');
        } else {
            document.getElementById('gridView').classList.add('d-none');
            document.getElementById('listView').classList.remove('d-none');
        }
    }

    // FIXED: Instant filtering with proper sorting
    applyFiltersInstantly() {
        this.showLoading(true);

        setTimeout(() => {
            let filteredFiles = [...this.allFiles];

            // Search filter
            if (this.filters.search) {
                filteredFiles = filteredFiles.filter(file =>
                    file.name.toLowerCase().includes(this.filters.search.toLowerCase())
                );
            }

            // Type filter
            if (this.filters.type) {
                filteredFiles = filteredFiles.filter(file =>
                    file.categorizedType === this.filters.type
                );
            }

            // Size filter
            if (this.filters.size) {
                filteredFiles = filteredFiles.filter(file => {
                    const size = file.size;
                    switch (this.filters.size) {
                        case 'small':
                            return size < 1048576; // < 1MB
                        case 'medium':
                            return size >= 1048576 && size <= 10485760; // 1MB - 10MB
                        case 'large':
                            return size > 10485760; // > 10MB
                        default:
                            return true;
                    }
                });
            }

            // FIXED: Sort files with proper logic
            filteredFiles.sort((a, b) => {
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

                // Apply order: desc means reverse the result
                return this.filters.order === 'desc' ? -result : result;
            });

            this.renderFilteredFiles(filteredFiles);
            this.showLoading(false);

            console.log(`Filtered ${filteredFiles.length} files from ${this.allFiles.length} total`);
            console.log(`Sort: ${this.filters.sort}, Order: ${this.filters.order}`);
        }, 100);
    }

    renderFilteredFiles(filteredFiles) {
        // Remove any existing empty state
        const existingEmptyState = document.getElementById('emptyState');
        if (existingEmptyState) {
            existingEmptyState.remove();
        }

        // Hide all files first
        this.allFiles.forEach(file => {
            file.element.style.display = 'none';
        });

        // Show filtered files
        if (filteredFiles.length > 0) {
            filteredFiles.forEach(file => {
                file.element.style.display = '';
            });
        } else {
            this.showEmptyState();
        }
    }

    showEmptyState() {
        const container = document.getElementById('fileContainer');
        const emptyState = document.createElement('div');
        emptyState.id = 'emptyState';
        emptyState.className = 'text-center py-5';
        emptyState.innerHTML = `
            <div class="mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 260 192" fill="none" class="w-[200px] md:w-[280px] h-auto"><path fill="#F5F5F5" d="M57.724 52.843 9.229 85.283l47.767 71.408 48.495-32.44-47.767-71.408Z"></path><path fill="#475569" d="m80.431 120.874-.553 3.406-24.11-3.475.554-3.406 24.11 3.475Z"></path><path fill="#475569" d="m68.29 108.724 3.458.497-3.834 23.738-3.462-.502 3.838-23.733ZM46.105 78.555c.034.048-4.824 3.363-10.853 7.391-6.03 4.029-10.944 7.261-10.979 7.21-.034-.052 4.824-3.363 10.853-7.396 6.03-4.032 10.922-7.257 10.979-7.205Zm17.815-4.477c.035.051-8.047 5.515-18.049 12.205-10.001 6.691-18.153 12.072-18.179 12.025-.026-.048 8.04-5.52 18.054-12.21 10.014-6.691 18.14-12.072 18.175-12.02Zm3.575 5.342c.034.052-8.048 5.52-18.05 12.21-10 6.69-18.152 12.072-18.178 12.02-.026-.052 8.047-5.515 18.053-12.21 10.006-6.695 18.14-12.072 18.175-12.02Zm3.575 5.346c.034.052-8.048 5.52-18.05 12.206-10.001 6.686-18.153 12.076-18.153 12.024 0-.052 8.044-5.519 18.05-12.21 10.005-6.69 18.118-12.072 18.152-12.02Zm3.56 5.347c.035.052-8.047 5.515-18.048 12.206-10.002 6.69-18.154 12.072-18.175 12.02-.022-.052 8.056-5.515 18.07-12.206 10.015-6.69 18.154-12.072 18.154-12.02Z"></path><path fill="#F5F5F5" d="m219.886 100.691-40.728 27.245 40.115 59.969 40.728-27.244-40.115-59.97Z"></path><path fill="#475569" d="m238.96 157.837-.463 2.861-20.249-2.917.462-2.862 20.25 2.918Z"></path><path fill="#475569" d="m228.759 147.632 2.909.42-3.224 19.933-2.905-.419 3.22-19.934Zm-18.645-25.315c.034.052-4.042 2.84-9.107 6.224-5.066 3.385-9.198 6.09-9.232 6.051-.035-.039 4.045-2.835 9.106-6.224 5.062-3.388 9.198-6.124 9.233-6.051Zm14.976-3.782c.034.052-6.747 4.651-15.128 10.266-8.38 5.614-15.24 10.135-15.274 10.083-.035-.052 6.747-4.651 15.127-10.269 8.381-5.619 15.227-10.132 15.275-10.08Zm3.004 4.487c.034.052-6.747 4.651-15.128 10.269-8.38 5.619-15.24 10.131-15.274 10.08-.035-.052 6.725-4.629 15.127-10.248 8.403-5.619 15.249-10.149 15.275-10.101Zm2.999 4.482c.035.052-6.751 4.651-15.127 10.269-8.377 5.619-15.24 10.132-15.275 10.08-.034-.052 6.747-4.647 15.128-10.27 8.381-5.623 15.244-10.122 15.274-10.079Zm3.026 4.499c.034.052-6.747 4.647-15.128 10.266-8.38 5.618-15.24 10.131-15.274 10.083-.035-.047 6.747-4.651 15.127-10.269 8.381-5.619 15.219-10.131 15.275-10.08Z"></path><path fill="#F5F5F5" d="m155.447 55.47-44.439-23.768L76.01 97.138l44.439 23.768 34.998-65.436Z"></path><path fill="#475569" d="m112.847 100.434-2.818.964-7.157-19.783 2.818-.968 7.157 19.787Z"></path><path fill="#475569" d="m117.17 86.24 1.028 2.84-19.648 6.73-1.029-2.84 19.649-6.73Zm13.96-29.187c-.034.065-4.538-2.278-10.066-5.23-5.528-2.952-9.976-5.403-9.941-5.468.035-.064 4.543 2.278 10.071 5.23 5.528 2.952 9.971 5.398 9.936 5.468Zm10.667 11.769c-.034.065-7.494-3.86-16.662-8.761-9.167-4.901-16.566-8.93-16.532-8.995.035-.064 7.49 3.856 16.658 8.761 9.167 4.906 16.571 8.93 16.536 8.995Zm-2.619 4.897c-.034.065-7.495-3.855-16.662-8.757-9.167-4.901-16.567-8.934-16.532-8.999.034-.064 7.49 3.86 16.658 8.762 9.167 4.9 16.571 8.93 16.536 8.994Zm-2.619 4.897c-.035.065-7.495-3.856-16.662-8.757-9.167-4.901-16.567-8.916-16.532-8.994.034-.078 7.49 3.855 16.657 8.756 9.168 4.902 16.571 8.93 16.537 8.995Zm-2.619 4.897c-.035.065-7.495-3.855-16.662-8.757-9.168-4.901-16.572-8.93-16.533-8.994.039-.065 7.491 3.855 16.658 8.757 9.167 4.9 16.571 8.93 16.537 8.994Z"></path><path fill="#64748B" d="M13.814 163.032a5.462 5.462 0 0 1 3.794 1.945 9.58 9.58 0 0 1 1.933 3.89c.73 2.732.28 5.96-.497 8.679-2.957-1.059-4.785-4.214-5.62-5.874-1.296-2.619-2.07-8.151.377-8.644m9.499 17.833a4.64 4.64 0 0 1-.215-4.659 6.162 6.162 0 0 1 3.717-2.987c.721-.224 1.56-.315 2.187.104a1.98 1.98 0 0 1 .765 1.863 4.004 4.004 0 0 1-.865 1.91c-1.439 1.95-3.185 3.57-5.619 3.769"></path><path fill="#0F172A" d="M23.63 189.994a2.805 2.805 0 0 1-.135-.566c-.078-.407-.181-.93-.307-1.556a16.322 16.322 0 0 1-.32-5.234 9.943 9.943 0 0 1 1.941-4.846 6.627 6.627 0 0 1 1.12-1.136c.113-.09.231-.174.354-.251a.504.504 0 0 1 .13-.078c-.547.467-1.046.987-1.491 1.552a10.309 10.309 0 0 0-1.85 4.789c-.277 2.014.065 3.859.263 5.186.104.662.19 1.198.238 1.569.034.189.053.379.056.571Z"></path><path fill="#0F172A" d="M14.764 167.194c.05.068.09.143.122.221.077.173.181.389.302.661.264.575.631 1.409 1.076 2.442.89 2.07 2.084 4.949 3.328 8.156a210.829 210.829 0 0 1 3.026 8.264c.372 1.063.661 1.928.864 2.528.091.286.16.515.216.692.031.079.053.162.065.246a1.04 1.04 0 0 1-.103-.233c-.065-.173-.147-.398-.256-.679l-.911-2.506a472.706 472.706 0 0 0-3.095-8.239 438.159 438.159 0 0 0-3.272-8.173c-.432-1.024-.765-1.858-1.011-2.468-.113-.276-.204-.497-.273-.674a1.495 1.495 0 0 1-.078-.238Z"></path><path fill="#64748B" d="M20.115 183.951a10.882 10.882 0 0 0-8.437-5.242c-.864-.061-1.927.086-2.316.864-.39.778.13 1.729.726 2.36a9.287 9.287 0 0 0 10.058 2.248"></path><path fill="#0F172A" d="M13.01 181.051c.183-.009.367-.002.549.022.213.011.425.036.635.073.246.048.536.074.838.16.339.072.672.164.999.277.379.121.75.266 1.11.432.832.372 1.618.838 2.343 1.387a11.828 11.828 0 0 1 1.937 1.919c.245.311.472.635.678.973.187.292.357.593.51.903.13.253.246.513.346.778.087.197.16.399.22.605.064.173.112.351.143.532-.048 0-.255-.743-.838-1.85a9.447 9.447 0 0 0-.523-.864 10.987 10.987 0 0 0-.683-.947 11.516 11.516 0 0 0-4.21-3.25c-.376-.164-.735-.32-1.085-.432a9.59 9.59 0 0 0-.981-.299c-1.21-.332-1.993-.371-1.988-.419Z"></path><path fill="#334155" d="m91.92 101.372-15.3 78.577-1.99 10.287 102.004-.493c4.582-.022 8.644-4.089 10.11-10.131l18.092-74.181c1.12-4.586-1.335-9.358-4.81-9.354l-103.266.108c-2.247-.004-4.261 2.123-4.84 5.187Z"></path><path fill="#FFA000" d="m91.92 101.372-15.3 78.577-1.99 10.287 102.004-.493c4.582-.022 8.644-4.089 10.11-10.131l18.092-74.181c1.12-4.586-1.335-9.358-4.81-9.354l-103.266.108c-2.247-.004-4.261 2.123-4.84 5.187Z"></path><path fill="#FFC107" d="m166.862 174.616-10.806-87.053c-.432-3.566-3.28-6.228-6.639-6.216l-23.685.074a6.486 6.486 0 0 0-4.681 2.075l-11.238 11.959-65.87.354c-4.023.022-7.118 3.834-6.587 8.113l10.032 80.457c.433 3.562 3.28 6.22 6.63 6.216l114.819-.23c12.785.264 14.008-3.509 14.008-3.509-14.678 3.246-15.983-12.219-15.983-12.24Z"></path><path fill="#fff" d="m117.351 157.206-3.872 4.158-29.823-26.832 3.872-4.158 29.823 26.832Z"></path><path fill="#fff" d="m111.858 129.453 4.279 3.851-26.988 28.98-4.279-3.851 26.988-28.98Z"></path><path fill="#FFA000" d="M183.256 44.855a.803.803 0 0 1-.489-.704l-.173-4.011a.809.809 0 0 1 .29-.658.802.802 0 0 1 .7-.163 2.517 2.517 0 0 0 1.954-.385 2.248 2.248 0 0 0 1.007-1.478 2.309 2.309 0 0 0-.484-1.729 2.605 2.605 0 0 0-1.703-1.024 3.026 3.026 0 0 0-3.069 2.16.81.81 0 0 1-.985.529.808.808 0 0 1-.571-.96 4.65 4.65 0 0 1 4.811-3.359 4.177 4.177 0 0 1 2.805 1.656 3.96 3.96 0 0 1 .791 2.969 3.89 3.89 0 0 1-1.729 2.559 4.203 4.203 0 0 1-2.187.713l.134 3.095a.81.81 0 0 1-1.124.773l.022.017Zm1.184 1.643a.662.662 0 1 1-1.321.09.662.662 0 0 1 1.321-.09Z"></path><path fill="#FFA000" d="M169.368 39.198c.019-.086.032-.172.039-.26 0-.19.048-.432.083-.773.042-.42.113-.835.211-1.245.056-.242.087-.51.173-.782.087-.273.173-.566.268-.865a14.936 14.936 0 0 1 2.161-4.119 14.487 14.487 0 0 1 4.543-3.95 14.252 14.252 0 0 1 3.203-1.323 14.492 14.492 0 0 1 3.695-.484c.657-.01 1.314.024 1.967.104.332.034.661.12.994.177.336.058.668.139.994.242a13.83 13.83 0 0 1 3.842 1.742 14.453 14.453 0 0 1 5.714 7.175c.579 1.58.872 3.25.865 4.931a13.914 13.914 0 0 1-.904 5.075l-.194.492-.026.065.026.065c1.085 2.494 2.161 4.944 3.177 7.317l.181-.207-6.483-2.485-.078-.03-.065.06a14.472 14.472 0 0 1-6.111 3.527 14.4 14.4 0 0 1-11.67-1.811 14.77 14.77 0 0 1-3.644-3.393 14.354 14.354 0 0 1-2.766-6.63l-.134-1.124c-.039-.329 0-.596-.022-.826-.021-.229 0-.38 0-.505a.088.088 0 0 0 .074-.092.087.087 0 0 0-.145-.057.089.089 0 0 0-.028.057.088.088 0 0 0 .073.092v1.34c0 .328.074.704.117 1.132.142 1.04.39 2.062.739 3.051a15.84 15.84 0 0 0 1.98 3.674 14.91 14.91 0 0 0 3.669 3.458 14.593 14.593 0 0 0 11.847 1.893 14.695 14.695 0 0 0 6.19-3.561l-.143.03 6.483 2.498.325.126-.143-.333c-1.029-2.373-2.092-4.82-3.173-7.318v.126l.199-.506c.631-1.656.945-3.415.925-5.187a14.454 14.454 0 0 0-.864-5.04 14.702 14.702 0 0 0-5.839-7.308 13.962 13.962 0 0 0-3.942-1.772 6.885 6.885 0 0 0-1.012-.238c-.337-.06-.67-.147-1.007-.177a14.869 14.869 0 0 0-1.992-.113 14.506 14.506 0 0 0-3.752.51 14.78 14.78 0 0 0-3.246 1.358 14.505 14.505 0 0 0-4.568 4.04 14.778 14.778 0 0 0-2.131 4.185c-.091.31-.177.61-.255.864a7.35 7.35 0 0 0-.164.791 8.156 8.156 0 0 0-.182 1.254c-.026.332-.043.587-.056.778-.018.093-.024.19-.018.285Z"></path></svg>
            </div>
            <h5 class="text-muted">No files</h5>
            <p class="text-muted mb-0">Please start uploading files</p>
        `;
        container.appendChild(emptyState);
    }

    showLoading(show) {
        const container = document.getElementById('fileContainer');
        if (show) {
            container.classList.add('loading');
        } else {
            container.classList.remove('loading');
        }
    }

    clearFilters() {
        // Reset all filters
        this.filters = {
            search: '',
            type: '',
            size: '',
            sort: 'created_at',
            order: 'desc'
        };

        // Reset UI
        document.getElementById('fileSearch').value = '';
        document.getElementById('typeFilterText').textContent = 'Type';
        document.getElementById('sizeFilterText').textContent = 'Size';
        document.getElementById('sortFilterText').textContent = 'Sort';

        // Apply filters
        this.applyFiltersInstantly();
        // this.updateFilterVisibility();

        // Hide filters after clearing
        this.hideInlineFilters();

        // Update URL
        window.history.pushState({}, '', window.location.pathname);
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function () {
    window.fileManager = new FileManager();
});



document.addEventListener('DOMContentLoaded', function () {
    let rightClickMenu = null;
    let currentFileItem = null;
    let isBootstrapDropdownOpen = false;

    // Monitor Bootstrap dropdown states
    document.addEventListener('show.bs.dropdown', function (e) {
        isBootstrapDropdownOpen = true;
        hideRightClickMenu(); // Hide right-click menu when Bootstrap dropdown opens
    });

    document.addEventListener('hide.bs.dropdown', function (e) {
        isBootstrapDropdownOpen = false;
    });

    // Handle right-click on file items
    document.addEventListener('contextmenu', function (e) {
        const fileItem = e.target.closest('.file-item');
        if (fileItem) {
            e.preventDefault();

            // Don't show right-click menu if Bootstrap dropdown is open
            if (isBootstrapDropdownOpen) {
                hideAllBootstrapDropdowns();
                return;
            }

            // Hide any existing Bootstrap dropdowns first
            hideAllBootstrapDropdowns();

            showRightClickMenu(e, fileItem);
        } else {
            hideRightClickMenu();
        }
    });

    // Handle three-dot button clicks - prevent conflicts
    document.addEventListener('click', function (e) {
        const dropdownToggle = e.target.closest('[data-bs-toggle="dropdown"]');
        if (dropdownToggle) {
            // Hide right-click menu when opening Bootstrap dropdown
            hideRightClickMenu();
            // Small delay to ensure right-click menu is fully hidden
            setTimeout(() => {
                isBootstrapDropdownOpen = true;
            }, 10);
        } else if (!e.target.closest('.right-click-menu') && !e.target.closest('.dropdown-menu')) {
            // Hide both menus when clicking elsewhere
            hideRightClickMenu();
            hideAllBootstrapDropdowns();
        }
    });

    // Enhanced click handler to prevent simultaneous menus
    document.addEventListener('mousedown', function (e) {
        const dropdownToggle = e.target.closest('[data-bs-toggle="dropdown"]');
        const isRightClick = e.button === 2;

        if (dropdownToggle && !isRightClick) {
            // Left-click on three-dot button - hide right-click menu immediately
            hideRightClickMenu();
        } else if (isRightClick) {
            // Right-click - hide Bootstrap dropdown immediately
            hideAllBootstrapDropdowns();
        }
    });

    // Hide on scroll and escape key
    document.addEventListener('scroll', function () {
        hideRightClickMenu();
        hideAllBootstrapDropdowns();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            hideRightClickMenu();
            hideAllBootstrapDropdowns();
        }
    });

    function hideAllBootstrapDropdowns() {
        // Use Bootstrap's dropdown hide method if available
        const openDropdowns = document.querySelectorAll('.dropdown-menu.show');
        openDropdowns.forEach(dropdown => {
            const dropdownInstance = bootstrap?.Dropdown?.getInstance(dropdown.previousElementSibling);
            if (dropdownInstance) {
                dropdownInstance.hide();
            } else {
                dropdown.classList.remove('show');
            }
        });

        // Remove Bootstrap's backdrop if exists
        const backdrop = document.querySelector('.dropdown-backdrop');
        if (backdrop) {
            backdrop.remove();
        }

        isBootstrapDropdownOpen = false;
    }

    function showRightClickMenu(event, fileItem) {
        // Double-check that no Bootstrap dropdown is open
        if (isBootstrapDropdownOpen || document.querySelector('.dropdown-menu.show')) {
            return;
        }

        hideRightClickMenu(); // Hide any existing menu

        currentFileItem = fileItem;

        // Find the dropdown menu in this file item
        const originalDropdown = fileItem.querySelector('.dropdown-menu');
        if (!originalDropdown) return;

        // Clone the original dropdown menu
        rightClickMenu = originalDropdown.cloneNode(true);
        rightClickMenu.classList.remove('dropdown-menu', 'dropdown-menu-end', 'show');
        rightClickMenu.classList.add('right-click-menu');
        rightClickMenu.style.display = 'block';

        // Position the menu
        positionMenu(event);

        // Add to document
        document.body.appendChild(rightClickMenu);

        // Bind events to cloned menu items
        bindMenuEvents();
    }

    function hideRightClickMenu() {
        if (rightClickMenu) {
            rightClickMenu.remove();
            rightClickMenu = null;
            currentFileItem = null;
        }
    }

    function positionMenu(event) {
        if (!rightClickMenu) return;

        // Temporarily show to get dimensions
        rightClickMenu.style.visibility = 'hidden';
        rightClickMenu.style.display = 'block';

        const rect = rightClickMenu.getBoundingClientRect();
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;

        let x = event.clientX;
        let y = event.clientY;

        // Adjust position if menu would go off-screen
        if (x + rect.width > viewportWidth) {
            x = viewportWidth - rect.width - 10;
        }
        if (y + rect.height > viewportHeight) {
            y = viewportHeight - rect.height - 10;
        }

        rightClickMenu.style.left = x + 'px';
        rightClickMenu.style.top = y + 'px';
        rightClickMenu.style.visibility = 'visible';
    }

    function bindMenuEvents() {
        if (!rightClickMenu) return;

        // Handle all dropdown-item clicks
        rightClickMenu.addEventListener('click', function (e) {
            const dropdownItem = e.target.closest('.dropdown-item');
            if (!dropdownItem) return;

            e.preventDefault();
            e.stopPropagation();

            // Handle share functionality specifically
            if (dropdownItem.classList.contains('fileManager-share-file')) {
                handleShareAction(dropdownItem);
            }
            // Handle regular links
            else if (dropdownItem.tagName === 'A' && dropdownItem.href && dropdownItem.href !== '#') {
                if (dropdownItem.target === '_blank') {
                    window.open(dropdownItem.href, '_blank');
                } else {
                    window.location.href = dropdownItem.href;
                }
            }
            // Handle buttons (like delete)
            else if (dropdownItem.tagName === 'BUTTON') {
                const form = dropdownItem.closest('form');
                if (form) {
                    if (dropdownItem.classList.contains('confirm-action-form')) {
                        const fileName = getFileName();
                        if (confirm(`Are you sure you want to delete "${fileName}"?`)) {
                            form.submit();
                        }
                    } else {
                        form.submit();
                    }
                }
            }

            hideRightClickMenu();
        });

        // Prevent right-click on the menu itself
        rightClickMenu.addEventListener('contextmenu', function (e) {
            e.preventDefault();
        });
    }

    function handleShareAction(shareElement) {
        // Get share data from the element
        const shareData = shareElement.dataset.share;
        const previewData = shareElement.dataset.preview;

        // Method 1: Try to trigger the original share button directly
        const originalShareBtn = currentFileItem.querySelector('.fileManager-share-file');
        if (originalShareBtn) {
            // Create a synthetic click event
            const clickEvent = new MouseEvent('click', {
                view: window,
                bubbles: true,
                cancelable: true
            });

            // Dispatch the event on the original button
            originalShareBtn.dispatchEvent(clickEvent);
            return;
        }

        // Method 2: Call global share handler if available
        if (typeof window.handleFileShare === 'function' && shareData) {
            try {
                window.handleFileShare(JSON.parse(shareData));
                return;
            } catch (e) {
                console.error('Error parsing share data:', e);
            }
        }

        // Method 3: Try to trigger existing share modal/functionality
        if (shareData) {
            try {
                const data = JSON.parse(shareData);

                // Look for existing share modal trigger functions
                if (typeof showShareModal === 'function') {
                    showShareModal(data);
                } else if (typeof openShareDialog === 'function') {
                    openShareDialog(data);
                } else if (typeof window.shareFile === 'function') {
                    window.shareFile(data);
                } else {
                    // Fallback: create a temporary element and trigger click
                    const tempElement = document.createElement('a');
                    tempElement.href = '#';
                    tempElement.className = 'fileManager-share-file';
                    tempElement.dataset.share = shareData;
                    tempElement.dataset.preview = previewData;
                    tempElement.style.display = 'none';

                    document.body.appendChild(tempElement);
                    tempElement.click();
                    document.body.removeChild(tempElement);
                }
            } catch (e) {
                console.error('Error handling share action:', e);
                alert('Share functionality is not available');
            }
        }
    }

    function getFileName() {
        if (!currentFileItem) return 'this file';

        return currentFileItem.dataset.fileName ||
            currentFileItem.querySelector('.filemanager-file-title')?.textContent?.trim() ||
            'this file';
    }

    // Additional safeguard - monitor any dropdown state changes
    const observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                const target = mutation.target;
                if (target.classList.contains('dropdown-menu')) {
                    if (target.classList.contains('show')) {
                        isBootstrapDropdownOpen = true;
                        hideRightClickMenu();
                    } else {
                        isBootstrapDropdownOpen = false;
                    }
                }
            }
        });
    });

    // Start observing dropdown menus
    document.querySelectorAll('.dropdown-menu').forEach(dropdown => {
        observer.observe(dropdown, { attributes: true });
    });

    // Global function to ensure share functionality works
    window.triggerContextShare = function (fileId) {
        const fileItem = document.querySelector(`[data-file-id="${fileId}"]`);
        if (fileItem) {
            const shareBtn = fileItem.querySelector('.fileManager-share-file');
            if (shareBtn) {
                shareBtn.click();
            }
        }
    };
});







// header
// header
document.addEventListener('DOMContentLoaded', function () {
    const createFolderBtn = document.getElementById('createFolderBtn');
    const createFolderForm = document.getElementById('createFolderForm');
    const folderNameInput = document.getElementById('folderName');
    const createFolderModal = document.getElementById('createFolderModal');
    const directUploadBtn = document.getElementById('directUploadBtn');
    const createUploadLinkBtn = document.querySelector('.create-upload-link-option');

    // Create Upload Link modal elements
    const createFileRequestModalEl = document.getElementById('createFileRequestModal');
    const createFileRequestForm = document.getElementById('createFileRequestForm');
    const submitFileRequestBtn = document.getElementById('submitFileRequestBtn');

    let createFileRequestModal = null;
    if (createFileRequestModalEl && typeof bootstrap !== 'undefined') {
        createFileRequestModal = new bootstrap.Modal(createFileRequestModalEl);
    }

    const urlParams = new URLSearchParams(window.location.search);
    const currentFolderId = urlParams.get('folder');

    // Get CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    // Get routes with better fallback handling
    const createFolderUrl = window.APP_ROUTES?.createFolder || '/user/files/create-folder';
    const uploadUrl = window.APP_ROUTES?.upload || '/upload';
    const fileRequestUrl = window.APP_ROUTES?.fileRequestCreate || '/user/file-requests';

    // ----------------------------
    // Hard reload helper (cache-busting)
    // ----------------------------
    function hardReload() {
        const url = new URL(window.location.href);
        url.searchParams.set('_r', Date.now()); // bust caches/CDNs
        window.location.replace(url.toString());
    }

    // DIRECT UPLOAD FUNCTIONALITY - START
    let directFileInput = document.getElementById('directFileInput');
    let uploadInProgress = false;
    let uploadCooldown = false;

    // Track selection-wide completion for direct uploads
    let pendingDirectUploads = 0;
    let anyDirectUploadSucceeded = false;

    // Create hidden file input if it doesn't exist
    if (!directFileInput) {
        directFileInput = document.createElement('input');
        directFileInput.type = 'file';
        directFileInput.id = 'directFileInput';
        directFileInput.style.display = 'none';
        directFileInput.multiple = true;
        directFileInput.accept = '*/*';
        document.body.appendChild(directFileInput);
    }

    // Handle direct upload button click
    if (directUploadBtn) {
        directUploadBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            if (uploadInProgress || uploadCooldown) {
                console.log('Upload blocked - in progress or cooldown');
                return false;
            }

            console.log('Opening file selector');
            directFileInput.value = '';
            directFileInput.click();
            return false;
        });
    }

    // Handle file selection
    if (directFileInput) {
        directFileInput.addEventListener('change', function (e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            if (uploadInProgress || uploadCooldown) {
                console.log('File selection blocked');
                this.value = '';
                return false;
            }

            const files = e.target.files;
            console.log('Files selected:', files.length);

            if (files.length > 0) {
                uploadInProgress = true;
                uploadCooldown = true;

                // Initialize selection-wide tracking
                pendingDirectUploads = files.length;
                anyDirectUploadSucceeded = false;

                uploadFilesSequentially(Array.from(files));
            }

            this.value = '';
            return false;
        });
    }

    // ----------------------
    // CREATE UPLOAD LINK FEATURE - MOVED TO dashboard-header.js
    // This code was causing duplicate API calls because dashboard-header.js already handles it
    // Commented out to prevent conflicts
    // ----------------------
    /*
    if (createUploadLinkBtn) {
        createUploadLinkBtn.addEventListener('click', function (e) {
            e.preventDefault();

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

            createFileRequestModal.show();
        });
    }

    if (submitFileRequestBtn && createFileRequestForm) {
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

                    if (createFileRequestModal) {
                        createFileRequestModal.hide();
                    }

                    console.log('Upload link:', link);
                })
                .catch(err => {
                    console.error(err);
                    submitFileRequestBtn.disabled = false;
                    submitFileRequestBtn.innerHTML = originalHtml;
                    showToast('error', 'Failed to create upload link');
                });
        });
    }
    */
    // CREATE UPLOAD LINK FEATURE
    // Moved to dashboard-header.js to prevent duplicate calls
    // and better code organization

    // Upload files sequentially
    function uploadFilesSequentially(files) {
        if (files.length === 0) {
            resetUploadState();
            return;
        }

        const file = files.shift();
        uploadFileDirectly(file, function () {
            uploadFilesSequentially(files);
        });
    }

    // Chunked uploader (works for any size, no timeouts)
    function uploadFileDirectly(file, callback) {
        const CHUNK_SIZE = 5 * 1024 * 1024; // 5MB chunks (tune as you wish)
        const total = file.size;
        let offset = 0;

        showUploadProgress(file.name, file.size);

        const parentFolderId = (new URLSearchParams(location.search)).get('folder');

        function finishOneFile(succeeded) {
            try { hideUploadProgress(); } catch (_) { }
            if (succeeded) anyDirectUploadSucceeded = true;

            pendingDirectUploads = Math.max(0, pendingDirectUploads - 1);

            if (typeof callback === 'function') callback();

            // If this was the last file in the current selection, reload once
            if (pendingDirectUploads === 0) {
                resetUploadState();
                if (anyDirectUploadSucceeded) {
                    hardReload();
                }
            }
        }

        function sendNextChunk() {
            if (offset >= total) {
                // done
                showToast('success', `File "${file.name}" uploaded successfully`);
                finishOneFile(true);
                return;
            }

            const end = Math.min(offset + CHUNK_SIZE, total);
            const blob = file.slice(offset, end);

            const formData = new FormData();
            formData.append('file', blob, file.name);
            formData.append('size', total);
            formData.append('upload_auto_delete', '0');
            if (parentFolderId) formData.append('parent_folder_id', parentFolderId);
            if (csrfToken) formData.append('_token', csrfToken);

            const xhr = new XMLHttpRequest();

            // NO TIMEOUTS for big uploads
            xhr.timeout = 0;

            // progress for this chunk -> compute overall %
            xhr.upload.addEventListener('progress', (e) => {
                if (!e.lengthComputable) return;
                const overall = Math.floor(((offset + e.loaded) / total) * 100);
                updateUploadProgress(overall);
            });

            xhr.addEventListener('load', () => {
                if (xhr.status >= 200 && xhr.status < 300) {
                    // server may respond "Chunk received" until the final one
                    offset = end;
                    sendNextChunk();
                } else {
                    showToast('error', `Upload failed for ${file.name} (HTTP ${xhr.status})`);
                    finishOneFile(false);
                }
            });

            xhr.addEventListener('error', () => {
                showToast('error', `Upload failed - network error for ${file.name}`);
                finishOneFile(false);
            });

            xhr.addEventListener('timeout', () => {
                // Should never fire because timeout=0, but keep a message if a proxy kills it:
                showToast('error', `Upload timed out (proxy/edge) for ${file.name}`);
                finishOneFile(false);
            });

            xhr.open('POST', uploadUrl);

            // Send Content-Range so Pion can assemble
            // bytes <start>-<end-1>/<total>
            xhr.setRequestHeader('Content-Range', `bytes ${offset}-${end - 1}/${total}`);
            if (csrfToken) xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);

            xhr.send(formData);
        }

        sendNextChunk();
    }

    // Function to reset upload states
    function resetUploadState() {
        uploadInProgress = false;

        setTimeout(() => {
            uploadCooldown = false;
            console.log('Upload cooldown lifted');
        }, 2000);
    }

    // Progress functions
    function showUploadProgress(fileName, fileSize) {
        const container = document.getElementById('uploadProgressContainer');
        const fileNameEl = document.getElementById('uploadFileName');
        const fileSizeEl = document.getElementById('fileSize');

        if (container && fileNameEl && fileSizeEl) {
            fileNameEl.textContent = fileName;
            fileSizeEl.textContent = formatFileSize(fileSize);
            container.style.display = 'block';
            updateUploadProgress(0);
        }
    }

    function updateUploadProgress(percent) {
        const progressBar = document.getElementById('uploadProgressBar');
        const percentText = document.getElementById('percentText');
        const uploadStatus = document.getElementById('uploadStatus');
        const progressRingCircle = document.getElementById('progressRingCircle');

        if (progressBar) progressBar.style.width = percent + '%';
        if (percentText) percentText.textContent = percent + '%';
        if (uploadStatus) uploadStatus.textContent = percent + '% uploaded';

        if (progressRingCircle) {
            const circumference = 2 * Math.PI * 16;
            const offset = circumference - (percent / 100 * circumference);
            progressRingCircle.style.strokeDasharray = circumference;
            progressRingCircle.style.strokeDashoffset = offset;
        }
    }

    function hideUploadProgress() {
        const container = document.getElementById('uploadProgressContainer');
        if (container) {
            setTimeout(() => {
                container.style.display = 'none';
            }, 1000);
        }
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    // Cancel upload
    const cancelUploadBtn = document.getElementById('cancelUpload');
    if (cancelUploadBtn) {
        cancelUploadBtn.addEventListener('click', function () {
            console.log('Upload cancelled');
            resetUploadState();
            hideUploadProgress();
            // Note: this doesn't abort in-flight XHRs — add xhr.abort() tracking if you need true cancel.
        });
    }
    // DIRECT UPLOAD FUNCTIONALITY - END

    // DROPZONE FUNCTIONALITY
    const uploadZone = document.getElementById('uploadZone');
    if (uploadZone && typeof Dropzone !== 'undefined') {
        Dropzone.autoDiscover = false;

        const myDropzone = new Dropzone(uploadZone, {
            url: uploadUrl,
            // maxFilesize: 100,
            timeout: 0,
            sending: function (file, xhr, formData) {
                if (currentFolderId) {
                    formData.append('parent_folder_id', currentFolderId);
                }

                const autoDelete = document.querySelector('input[name="upload_auto_delete"]:checked');
                formData.append('upload_auto_delete', autoDelete ? autoDelete.value : '0');

                if (csrfToken) {
                    formData.append('_token', csrfToken);
                }
            },
            success: function (file, response) {
                if (response?.type === 'success') {
                    showToast('success', `File "${file.name}" uploaded successfully`);
                    // No per-file reload here — we reload once at queuecomplete.
                } else {
                    showToast('error', (response && (response.msg || response.message)) || 'Upload failed');
                }
            },
            error: function (file, message) {
                console.error('Dropzone upload error:', message);
                showToast('error', `Upload failed for ${file.name}`);
            },
            init: function () {
                // Reload once after ALL files in the Dropzone queue are processed
                this.on('queuecomplete', function () {
                    hardReload();
                });
            }
        });
    }

    // FOLDER CREATION FUNCTIONALITY
    if (createFolderBtn) {
        createFolderBtn.addEventListener('click', function (e) {
            e.preventDefault();
            console.log('Create folder button clicked');
            handleCreateFolder();
        });
    }

    if (folderNameInput) {
        folderNameInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                handleCreateFolder();
            }
        });

        folderNameInput.addEventListener('input', function () {
            validateFolderName(this.value);
        });
    }

    if (createFolderModal) {
        createFolderModal.addEventListener('hidden.bs.modal', function () {
            resetForm();
        });

        createFolderModal.addEventListener('shown.bs.modal', function () {
            if (folderNameInput) {
                folderNameInput.focus();
            }
        });
    }

    function handleCreateFolder() {
        console.log('handleCreateFolder called');
        const folderName = folderNameInput ? folderNameInput.value.trim() : '';

        console.log('Folder name:', folderName);

        if (!validateFolderName(folderName)) {
            console.log('Validation failed');
            return;
        }

        setLoadingState(true);
        createFolder(folderName);
    }

    function validateFolderName(name) {
        if (!folderNameInput) {
            console.log('folderNameInput not found');
            return false;
        }

        const feedback = folderNameInput.nextElementSibling;

        folderNameInput.classList.remove('is-invalid', 'is-valid');

        if (!name || name.trim() === '') {
            showValidationError(folderNameInput, feedback, 'Folder name is required');
            return false;
        }

        const invalidChars = /[\/\\:*?"<>|]/;
        if (invalidChars.test(name)) {
            showValidationError(folderNameInput, feedback, 'Folder name contains invalid characters');
            return false;
        }

        if (name.length > 255) {
            showValidationError(folderNameInput, feedback, 'Folder name is too long (max 255 characters)');
            return false;
        }

        const reservedNames = ['CON', 'PRN', 'AUX', 'NUL', 'COM1', 'COM2', 'COM3', 'COM4', 'COM5', 'COM6', 'COM7', 'COM8', 'COM9', 'LPT1', 'LPT2', 'LPT3', 'LPT4', 'LPT5', 'LPT6', 'LPT7', 'LPT8', 'LPT9'];
        if (reservedNames.includes(name.toUpperCase())) {
            showValidationError(folderNameInput, feedback, 'This folder name is reserved and cannot be used');
            return false;
        }

        folderNameInput.classList.add('is-valid');
        if (feedback) {
            feedback.textContent = '';
        }
        return true;
    }

    function showValidationError(input, feedback, message) {
        input.classList.add('is-invalid');
        if (feedback) {
            feedback.textContent = message;
        }
    }

    function createFolder(folderName) {
        console.log('Creating folder:', folderName);
        console.log('URL:', createFolderUrl);
        console.log('Current folder ID:', currentFolderId);

        const formData = new FormData();
        formData.append('folder_name', folderName);

        if (currentFolderId) {
            formData.append('parent_folder_id', currentFolderId);
        }

        if (csrfToken) {
            formData.append('_token', csrfToken);
        } else {
            console.error('CSRF token is missing!');
            showToast('error', 'CSRF token is missing. Please refresh the page.');
            setLoadingState(false);
            return;
        }

        // Log form data for debugging
        for (let [key, value] of formData.entries()) {
            console.log('Form data:', key, value);
        }

        fetch(createFolderUrl, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            }
        })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                setLoadingState(false);

                if (data.type === 'success') {
                    showToast('success', data.message || 'Folder created successfully');

                    // Close modal
                    const modalElement = document.getElementById('createFolderModal');
                    if (modalElement && typeof bootstrap !== 'undefined') {
                        const modal = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
                        modal.hide();
                    }

                    // Hard reload to show new folder
                    setTimeout(() => {
                        hardReload();
                    }, 800);
                } else {
                    showToast('error', data.msg || 'Failed to create folder');

                    // Handle validation errors
                    if (data.errors && data.errors.folder_name && folderNameInput) {
                        const feedback = folderNameInput.nextElementSibling;
                        showValidationError(folderNameInput, feedback, data.errors.folder_name[0]);
                    }
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                setLoadingState(false);
                showToast('error', 'An error occurred while creating the folder: ' + error.message);
            });
    }

    function setLoadingState(loading) {
        if (!createFolderBtn) {
            console.log('createFolderBtn not found');
            return;
        }

        if (loading) {
            createFolderBtn.disabled = true;
            createFolderBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating...';
        } else {
            createFolderBtn.disabled = false;
            createFolderBtn.innerHTML = '<i class="fas fa-folder-plus me-2"></i>Create Folder';
        }
    }

    function resetForm() {
        if (createFolderForm) {
            createFolderForm.reset();
        }

        if (folderNameInput) {
            folderNameInput.classList.remove('is-invalid', 'is-valid');
            const feedback = folderNameInput.nextElementSibling;
            if (feedback) {
                feedback.textContent = '';
            }
        }

        setLoadingState(false);
    }

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
});








// ---- FILE: script.js (clipboard: cut / copy / paste) ----
document.addEventListener('DOMContentLoaded', function () {
    const $doc = document;

    // Get CSRF + current folder + routes (with fallbacks)
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const urlParams = new URLSearchParams(window.location.search);
    const currentFolder = urlParams.get('folder') || null;

    const listBase = (window.APP_ROUTES && window.APP_ROUTES.listBase) || '/user/files';
    const routeSet = (window.APP_ROUTES && window.APP_ROUTES.clipboardSet) || '/user/files/clipboard/set';
    const routeClear = (window.APP_ROUTES && window.APP_ROUTES.clipboardClear) || '/user/files/clipboard/clear';
    const routePaste = (window.APP_ROUTES && window.APP_ROUTES.clipboardPaste) || '/user/files/clipboard/paste';

    // Toast helper that works with/without toastr/Swal
    function toast(type, msg) {
        if (typeof toastr !== 'undefined' && toastr?.[type]) return toastr[type](msg);
        if (typeof Swal !== 'undefined' && Swal?.fire) {
            const icon = (type === 'success' ? 'success' : type === 'info' ? 'info' : type === 'warning' ? 'warning' : 'error');
            return Swal.fire({ icon, title: msg, timer: 2500, showConfirmButton: false });
        }
        console[type === 'error' ? 'error' : 'log']('[toast]', type, msg);
    }

    // URL helper
    function folderUrl(id) {
        return id ? (listBase + '?folder=' + encodeURIComponent(id)) : listBase;
    }

    // Selection helper
    function selectedIds() {
        return Array.from($doc.querySelectorAll('.file-checkbox:checked')).map(el => el.value);
    }

    // ----- Local clipboard (UI-only mirror of server clipboard) -----
    function setLocalClipboard(action, ids) {
        const data = { action, ids, at: Date.now() };
        localStorage.setItem('fm_clipboard', JSON.stringify(data));
        return data;
    }
    function getLocalClipboard() {
        try { return JSON.parse(localStorage.getItem('fm_clipboard') || ''); } catch (e) { return null; }
    }
    function clearLocalClipboard() {
        localStorage.removeItem('fm_clipboard');
    }

    // ----- Ajax helper -----
    async function post(url, payload) {
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                // Laravel will also accept the token in header:
                'X-CSRF-TOKEN': csrfToken
            },
            credentials: 'same-origin',
            body: JSON.stringify(payload || {})
        });
        // Try parsing JSON; if fails, throw a nicer error
        let data;
        try { data = await res.json(); }
        catch (e) { throw new Error('Invalid JSON response'); }

        if (!res.ok) {
            // unify error shape
            throw new Error(data?.message || data?.msg || ('HTTP ' + res.status));
        }
        return data;
    }

    // ----- Visual + selection helpers -----
    function removeCutVisuals() {
        $doc.querySelectorAll('.file-item.is-cut').forEach(el => el.classList.remove('is-cut'));
    }
    function applyCutVisualsFromClipboard() {
        removeCutVisuals();
        const clip = getLocalClipboard();
        if (!clip || clip.action !== 'cut' || !Array.isArray(clip.ids)) return;
        clip.ids.forEach(id => {
            $doc.querySelectorAll(`[data-file-id="${CSS.escape(String(id))}"].file-item`).forEach(el => el.classList.add('is-cut'));
        });
    }
    function clearSelections() {
        $doc.querySelectorAll('.file-checkbox:checked').forEach(cb => { cb.checked = false; });
    }

    // ----- Server clipboard API -----
    async function apiSetClipboard(action, ids) {
        if (!ids.length) { toast('error', 'Select something first.'); return { type: 'error' }; }
        try {
            const resp = await post(routeSet, { action, ids });
            if (resp.type === 'success') {
                setLocalClipboard(action, ids);
                toast('success', resp.msg || 'Done');
            } else {
                toast('error', resp.msg || 'Failed.');
            }
            return resp;
        } catch (err) {
            toast('error', err.message || 'Request failed.');
            return { type: 'error' };
        }
    }

    async function apiClearClipboard() {
        try {
            const resp = await post(routeClear, {});
            clearLocalClipboard();
            toast('info', 'Clipboard cleared.');
            return resp;
        } catch (err) {
            toast('error', err.message || 'Failed to clear clipboard.');
            return { type: 'error' };
        }
    }

    // ----- Public actions (keep visuals in sync) -----
    async function setClipboard(action, ids) {
        const resp = await apiSetClipboard(action, ids);
        if (resp?.type === 'success') {
            if (action === 'cut') applyCutVisualsFromClipboard();
            else removeCutVisuals(); // copy: no ghosting
        }
        return resp;
    }

    async function clearClipboard() {
        await apiClearClipboard();
        removeCutVisuals();
        clearSelections();
    }

    // Paste then redirect/reload properly
    async function pasteInto(targetFolderId, redirectUrl) {
        const local = getLocalClipboard(); // capture current action (cut/copy)
        try {
            const resp = await post(routePaste, { target_folder_id: targetFolderId || null });

            if (resp.type === 'success') {
                toast('success', resp.msg || 'Pasted.');
                if (resp.errors && resp.errors.length) resp.errors.forEach(e => toast('warning', e));

                // Clear local UI state for CUT
                if (local && local.action === 'cut') {
                    clearLocalClipboard();
                    removeCutVisuals();
                    clearSelections();
                }

                // After paste, ensure hard reload on BFCache return
                sessionStorage.setItem('fm_after_paste', '1');

                if (redirectUrl) window.location.assign(redirectUrl);
                else window.location.reload();
            } else {
                toast('error', resp.msg || 'Paste failed.');
            }
        } catch (err) {
            toast('error', err.message || 'Paste failed.');
        }
    }

    // ----- UI bindings (toolbar) -----
    const btnCutSelected = $doc.querySelector('.fm-cut-selected');
    const btnCopySelected = $doc.querySelector('.fm-copy-selected');
    const btnPasteHere = $doc.querySelector('.fm-paste-here');
    const btnClearClipboard = $doc.querySelector('.fm-clear-clipboard');

    if (btnCutSelected) btnCutSelected.addEventListener('click', () => setClipboard('cut', selectedIds()));
    if (btnCopySelected) btnCopySelected.addEventListener('click', () => setClipboard('copy', selectedIds()));
    if (btnPasteHere) btnPasteHere.addEventListener('click', () => pasteInto(currentFolder || null, null));
    if (btnClearClipboard) btnClearClipboard.addEventListener('click', clearClipboard);

    // ----- Row menus (delegated so it works on dynamic lists) -----
    $doc.addEventListener('click', (e) => {
        const cutEl = e.target.closest?.('.fm-cut');
        const copyEl = e.target.closest?.('.fm-copy');
        const pasteEl = e.target.closest?.('.fm-paste');

        if (cutEl) {
            e.preventDefault();
            const id = cutEl.dataset.fileId;
            if (id) setClipboard('cut', [id]);
            return;
        }
        if (copyEl) {
            e.preventDefault();
            const id = copyEl.dataset.fileId;
            if (id) setClipboard('copy', [id]);
            return;
        }
        if (pasteEl) {
            e.preventDefault();
            const target = pasteEl.dataset.targetFolder || '';
            if (!target) { toast('info', 'Open a folder or use â€œPaste hereâ€.'); return; }
            pasteInto(target, folderUrl(target)); // redirect into that folder after paste
            return;
        }
    });

    // ----- Keyboard shortcuts (Cmd/Ctrl + C / X / V) -----
    $doc.addEventListener('keydown', (e) => {
        const inInput = ['INPUT', 'TEXTAREA'].includes((e.target && e.target.tagName) || '') || e.target.isContentEditable;
        if (inInput) return;

        const ctrl = e.ctrlKey || e.metaKey;
        if (!ctrl) return;

        const k = e.key.toLowerCase();
        if (k === 'c') { e.preventDefault(); setClipboard('copy', selectedIds()); }
        else if (k === 'x') { e.preventDefault(); setClipboard('cut', selectedIds()); }
        else if (k === 'v') { e.preventDefault(); pasteInto(currentFolder || null, null); }
    });

    // ----- Handle BFCache + keep visuals in sync -----
    window.addEventListener('pageshow', (e) => {
        const flag = sessionStorage.getItem('fm_after_paste') === '1';
        const nav = (performance.getEntriesByType && performance.getEntriesByType('navigation')[0]) || null;
        const isBackForward = (e.persisted === true) || (nav && nav.type === 'back_forward');

        if (flag && isBackForward) {
            sessionStorage.removeItem('fm_after_paste'); // consume flag
            window.location.reload(); // force a network reload
        } else {
            // if not reloading, at least refresh cut visuals to match clipboard
            applyCutVisualsFromClipboard();
        }
    });

    // Initial visuals on first load
    applyCutVisualsFromClipboard();
});





// FileManager class to handle file operations0000000-----------------------------------------------


// ---- Robust navbar loader (reference-counted, XHR/fetch safe) ----
(function () {
    // prevent double-install
    if (window.__navbarLoaderInstalled) return;
    window.__navbarLoaderInstalled = true;

    const bar = document.getElementById('navbarLoadingBar');
    const fill = document.getElementById('navbarLoadingFill');
    if (!bar || !fill) return;

    let active = 0;
    let hideTimer = null;
    let failsafeTimer = null;
    const HIDE_DELAY = 200;
    const FAILSAFE_MS = 15000; // longer for big uploads

    const reallyHide = () => {
        clearTimeout(hideTimer);
        clearTimeout(failsafeTimer);
        fill.classList.remove('animate');
        bar.classList.remove('active');
    };

    const activate = () => {
        fill.classList.remove('animate');
        bar.classList.remove('active');
        // force reflow to restart keyframes
        // eslint-disable-next-line no-unused-expressions
        bar.offsetHeight;
        bar.classList.add('active');
        requestAnimationFrame(() => fill.classList.add('animate'));

        clearTimeout(failsafeTimer);
        failsafeTimer = setTimeout(() => {
            active = 0;            // only hides bar, never touches network
            reallyHide();
        }, FAILSAFE_MS);
    };

    const start = () => {
        if (active === 0) activate();
        active++;
    };

    const stop = () => {
        if (active > 0) active--;
        if (active === 0) {
            clearTimeout(hideTimer);
            hideTimer = setTimeout(reallyHide, HIDE_DELAY);
        }
    };

    // expose helpers
    window.showLoadingForOperation = (promise) => {
        start();
        return Promise.resolve(promise).finally(stop);
    };
    window.showLoadingForElement = (el) => {
        if (el) el.addEventListener('click', () => start(), { passive: true });
    };

    // ---- Patch fetch (bind to window to avoid Illegal invocation) ----
    if (typeof window.fetch === 'function') {
        const originalFetch = window.fetch;
        window.fetch = function (...args) {
            start();
            const p = originalFetch.apply(window, args); // bind to window (not arbitrary this)
            return (typeof p.finally === 'function')
                ? p.finally(stop)
                : p.then((v) => { stop(); return v; }, (e) => { stop(); throw e; });
        };
    }

    // ---- Patch XMLHttpRequest via prototype (do NOT replace constructor) ----
    const XHRProto = window.XMLHttpRequest && window.XMLHttpRequest.prototype;
    if (XHRProto) {
        const origOpen = XHRProto.open;
        const origSend = XHRProto.send;

        XHRProto.open = function (...args) {
            try { this.__uxCounted = false; } catch (_) { }
            return origOpen.call(this, ...args);   // call with correct this
        };

        XHRProto.send = function (...args) {
            try {
                if (!this.__uxCounted) {
                    this.__uxCounted = true;
                    start();
                    const once = { once: true };
                    if (typeof this.addEventListener === 'function') {
                        this.addEventListener('loadend', stop, once);
                        this.addEventListener('error', stop, once);
                        this.addEventListener('abort', stop, once);
                        this.addEventListener('timeout', stop, once);
                    } else {
                        // very old engines fallback
                        const stopOnce = () => { stop(); this.onloadend = this.onerror = this.onabort = this.ontimeout = null; };
                        this.onloadend = stopOnce;
                        this.onerror = stopOnce;
                        this.onabort = stopOnce;
                        this.ontimeout = stopOnce;
                    }
                }
            } catch (_) { /* never block the request */ }
            return origSend.call(this, ...args);   // call with correct this
        };
    }

    // ---- Forms ----
    document.addEventListener('submit', () => {
        start();
        setTimeout(stop, 5000);
    });

    // ---- Links that navigate ----
    document.addEventListener('click', (e) => {
        const link = e.target.closest('a[href]');
        if (!link) return;
        if (link.getAttribute('href').startsWith('#')) return;
        if (link.hasAttribute('data-bs-toggle')) return;
        if (link.target === '_blank' || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

        let prevented = false;
        const onBefore = (evt) => { if (evt.defaultPrevented) prevented = true; };
        document.addEventListener('click', onBefore, { capture: true, once: true });

        setTimeout(() => {
            if (!prevented) start();
            if (prevented) setTimeout(stop, 3000);
        }, 0);
    }, { passive: true });

    // ---- Page lifecycle ----
    window.addEventListener('beforeunload', () => { start(); });
    window.addEventListener('load', () => { active = 0; reallyHide(); });
})();








function uploadFolder(files) {
    const progressContainer = document.getElementById('uploadProgressContainer');
    const fileName = document.getElementById('uploadFileName');
    const uploadStatus = document.getElementById('uploadStatus');

    progressContainer.style.display = 'block';
    fileName.textContent = `Preparing folder upload...`;
    updateProgressDisplay(0);

    processFolderStructure(files);
}

function uploadFilesWithFolders(allFiles, index, totalFiles) {
    if (index >= allFiles.length) {
        updateProgressDisplay(100);
        const uploadStatus = document.getElementById('uploadStatus');
        uploadStatus.textContent = 'Upload complete!';
        setTimeout(() => {
            document.getElementById('uploadProgressContainer').style.display = 'none';
            window.location.reload();
        }, 1500);
        return;
    }

    const fileInfo = allFiles[index];
    const file = fileInfo.file;
    const fileName = document.getElementById('uploadFileName');
    fileName.textContent = `${fileInfo.relativePath} (${index + 1}/${totalFiles})`;

    const formData = new FormData();
    formData.append('file', file);
    formData.append('size', file.size);
    formData.append('parent_folder_id', fileInfo.targetFolderId || ''); // Using parent_folder_id consistently
    formData.append('upload_auto_delete', '0');
    formData.append('password', '');
    formData.append('access_status', '0');

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (csrfToken) {
        formData.append('_token', csrfToken);
    }

    console.log('Uploading file:', {
        filename: file.name,
        relativePath: fileInfo.relativePath,
        targetFolderId: fileInfo.targetFolderId,
        index: index + 1,
        total: totalFiles
    });

    const xhr = new XMLHttpRequest();

    xhr.upload.addEventListener('progress', function (e) {
        if (e.lengthComputable) {
            const fileProgress = (e.loaded / e.total) * 100;
            const totalProgress = ((index * 100) + fileProgress) / totalFiles;
            updateProgressDisplay(Math.round(totalProgress));
        }
    });

    xhr.addEventListener('load', function () {
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.type === 'success') {
                    console.log('File uploaded successfully:', file.name);
                    uploadFilesWithFolders(allFiles, index + 1, totalFiles);
                } else {
                    console.error('Upload failed:', response.msg);
                    showError(response.msg || 'Upload failed');
                }
            } catch (e) {
                console.error('Response parsing error:', e);
                showError('Server response error');
            }
        } else {
            console.error('HTTP error:', xhr.status);
            showError('Upload failed');
        }
    });

    xhr.addEventListener('error', function () {
        console.error('Network error occurred');
        showError('Network error occurred');
    });

    xhr.open('POST', '/upload');
    if (csrfToken) {
        xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
    }
    xhr.send(formData);
}

// Update regular file upload to use parent_folder_id consistently
function uploadFiles(files) {
    const formData = new FormData();
    const progressContainer = document.getElementById('uploadProgressContainer');
    const progressBar = document.getElementById('uploadProgressBar');
    const fileName = document.getElementById('uploadFileName');
    const uploadStatus = document.getElementById('uploadStatus');

    // Set default values
    formData.append('upload_auto_delete', '0');
    formData.append('password', '');
    formData.append('parent_folder_id', getCurrentFolderId() || ''); // Changed to parent_folder_id
    formData.append('access_status', '0');

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (csrfToken) {
        formData.append('_token', csrfToken);
    }

    progressContainer.style.display = 'block';
    fileName.textContent = files.length > 1 ? `${files.length} files` : files[0].name;

    updateProgressDisplay(0);
    uploadFilesSequentially(files, 0, formData, progressContainer, progressBar, fileName, uploadStatus);
}

function uploadFilesSequentially(files, index, baseFormData, progressContainer, progressBar, fileName, uploadStatus) {
    if (index >= files.length) {
        updateProgressDisplay(100);
        uploadStatus.textContent = 'Upload complete!';
        setTimeout(() => {
            progressContainer.style.display = 'none';
            window.location.reload();
        }, 1500);
        return;
    }

    const file = files[index];
    const formData = new FormData();

    // Copy base form data
    for (let pair of baseFormData.entries()) {
        if (pair[0] !== 'file') {
            formData.append(pair[0], pair[1]);
        }
    }

    // Add current file
    formData.append('file', file);
    formData.append('size', file.size);

    fileName.textContent = `${file.name} (${index + 1}/${files.length})`;
    updateFileInfo(file);

    console.log('Uploading file:', {
        filename: file.name,
        index: index + 1,
        total: files.length,
        parent_folder_id: baseFormData.get('parent_folder_id')
    });

    const xhr = new XMLHttpRequest();

    xhr.upload.addEventListener('progress', function (e) {
        if (e.lengthComputable) {
            const fileProgress = (e.loaded / e.total) * 100;
            const totalProgress = ((index * 100) + fileProgress) / files.length;
            updateProgressDisplay(Math.round(totalProgress));
        }
    });

    xhr.addEventListener('load', function () {
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.type === 'success') {
                    uploadFilesSequentially(files, index + 1, baseFormData, progressContainer, progressBar, fileName, uploadStatus);
                } else {
                    showError(response.msg || 'Upload failed');
                }
            } catch (e) {
                showError('Server response error');
            }
        } else {
            showError('Upload failed');
        }
    });

    xhr.addEventListener('error', function () {
        showError('Network error occurred');
    });

    document.getElementById('cancelUpload').onclick = function () {
        xhr.abort();
        progressContainer.style.display = 'none';
    };

    xhr.open('POST', '/upload');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (csrfToken) {
        xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
    }
    xhr.send(formData);
}

// Update progress display function
function updateProgressDisplay(percent) {
    // Ensure percent is 0-100
    percent = Math.max(0, Math.min(100, percent));

    const progressBar = document.getElementById('uploadProgressBar');
    const percentText = document.getElementById('percentText');
    const progressRing = document.getElementById('progressRingCircle');
    const uploadStatus = document.getElementById('uploadStatus');

    // Update linear progress
    if (progressBar) {
        progressBar.style.width = percent + '%';
    }

    // Update circular progress
    if (progressRing) {
        const circumference = 2 * Math.PI * 16;
        const offset = circumference - (percent / 100) * circumference;
        progressRing.style.strokeDashoffset = offset;
    }

    // Update text
    if (percentText) {
        percentText.textContent = percent + '%';
    }
    if (uploadStatus) {
        uploadStatus.textContent = percent + '% uploaded';
    }

    // Add uploading animation
    const iconContainer = document.querySelector('.file-icon-container');
    if (iconContainer) {
        if (percent > 0 && percent < 100) {
            iconContainer.classList.add('uploading');
        } else {
            iconContainer.classList.remove('uploading');
        }
    }
}

// Update file information
function updateFileInfo(file) {
    const fileSize = document.getElementById('fileSize');
    const fileIcon = document.getElementById('fileTypeIcon');

    // Set file size
    if (fileSize) {
        fileSize.textContent = formatFileSize(file.size);
    }

    // Set file icon
    if (fileIcon) {
        const extension = file.name.split('.').pop().toLowerCase();
        fileIcon.className = 'fas file-type-icon';

        if (['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'].includes(extension)) {
            fileIcon.classList.add('fa-file-image', 'file-type-image');
        } else if (['pdf'].includes(extension)) {
            fileIcon.classList.add('fa-file-pdf', 'file-type-pdf');
        } else if (['doc', 'docx'].includes(extension)) {
            fileIcon.classList.add('fa-file-word', 'file-type-doc');
        } else if (['mp4', 'avi', 'mkv', 'mov'].includes(extension)) {
            fileIcon.classList.add('fa-file-video', 'file-type-video');
        } else if (['mp3', 'wav', 'flac'].includes(extension)) {
            fileIcon.classList.add('fa-file-audio', 'file-type-audio');
        } else if (['zip', 'rar', '7z'].includes(extension)) {
            fileIcon.classList.add('fa-file-archive', 'file-type-zip');
        } else {
            fileIcon.classList.add('fa-file');
        }
    }
}

// Format file size
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Show error
function showError(message) {
    const uploadStatus = document.getElementById('uploadStatus');
    const progressContainer = document.getElementById('uploadProgressContainer');

    if (uploadStatus) {
        uploadStatus.textContent = 'Error: ' + message;
        uploadStatus.style.color = '#ea4335';
    }

    setTimeout(() => {
        if (progressContainer) {
            progressContainer.style.display = 'none';
        }
        if (uploadStatus) {
            uploadStatus.style.color = '';
        }
    }, 3000);
}

// Get current folder ID
function getCurrentFolderId() {
    return document.querySelector('[data-current-folder-id]')?.getAttribute('data-current-folder-id') || null;
}






// DOM ready
document.addEventListener('DOMContentLoaded', function () {
    // Prevent double-binding if the script is included twice
    if (window.__uploadBindingsInstalled) return;
    window.__uploadBindingsInstalled = true;

    // --- Single hidden inputs (created once) ---
    let fileInput = document.getElementById('uxHiddenFileInput');
    if (!fileInput) {
        fileInput = document.createElement('input');
        fileInput.type = 'file';
        fileInput.multiple = true;
        fileInput.style.display = 'none';
        fileInput.id = 'uxHiddenFileInput';
        document.body.appendChild(fileInput);
    }

    let folderInput = document.getElementById('uxHiddenFolderInput');
    if (!folderInput) {
        folderInput = document.createElement('input');
        folderInput.type = 'file';
        folderInput.multiple = true;
        folderInput.webkitdirectory = true;
        folderInput.directory = true;
        folderInput.style.display = 'none';
        folderInput.id = 'uxHiddenFolderInput';
        document.body.appendChild(folderInput);
    }

    // --- Locking w/ timestamp + safe cancel fallback ---
    const state = {
        lock: false,
        openedAt: 0,
        cancelTimer: null
    };

    function safelyOpenPicker(input, originEl) {
        if (state.lock) return;
        state.lock = true;
        state.openedAt = Date.now();

        try { input.value = ''; } catch (e) { }

        // Close the dropdown then open the picker in the next tick
        hideAnyOpenDropdown(originEl);
        setTimeout(() => {
            // Prefer showPicker() if available (prevents synthetic click quirks)
            if (typeof input.showPicker === 'function') {
                input.showPicker();
            } else {
                input.click();
            }

            // Fallback: if user cancels (no 'change'), unlock after we come back visible
            // but not immediately (debounced with a small grace period).
            if (state.cancelTimer) clearTimeout(state.cancelTimer);
            state.cancelTimer = setTimeout(() => {
                // If still locked and nothing changed after 1200ms of being visible,
                // we consider it canceled and unlock.
                document.addEventListener('visibilitychange', onVisibleOnce, { once: true });
            }, 150);
        }, 40);
    }

    function onVisibleOnce() {
        // Wait a moment to avoid re-trigger from focus bounce
        setTimeout(() => {
            state.lock = false;
        }, 400);
    }

    // --- Bind ALL â€œUpload Fileâ€ buttons (normal + shared) ---
    document.querySelectorAll('.js-direct-upload').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            safelyOpenPicker(fileInput, btn);
        }, { passive: false });
    });

    // --- Bind ALL â€œUpload Folderâ€ buttons (normal + shared) ---
    document.querySelectorAll('.js-folder-upload').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            safelyOpenPicker(folderInput, btn);
        }, { passive: false });
    });

    // --- Change handlers unlock and start your upload ---
    fileInput.addEventListener('change', function () {
        state.lock = false;
        if (state.cancelTimer) { clearTimeout(state.cancelTimer); state.cancelTimer = null; }

        const files = Array.from(this.files || []);
        if (!files.length) return;

        // OPTIONAL: Kick off your progress UI
        startUploadUI(files[0]); // shows the panel with file name & 0%

        // Your existing upload
        if (typeof uploadFiles === 'function') {
            uploadFiles(files); // implement progress callbacks to update UI below
        }

        this.value = ''; // reset so picking the same file again works
    });

    folderInput.addEventListener('change', function () {
        state.lock = false;
        if (state.cancelTimer) { clearTimeout(state.cancelTimer); state.cancelTimer = null; }

        const allFiles = Array.from(this.files || []);
        if (!allFiles.length) return;

        const filtered = (typeof filterFilesBySize === 'function') ? filterFilesBySize(allFiles) : allFiles;
        if (typeof showFileFilteringInfo === 'function') showFileFilteringInfo(allFiles, filtered);

        if (filtered.length) {
            // OPTIONAL: Kick off your progress UI with the first file
            startUploadUI(filtered[0]);

            if (typeof uploadFolder === 'function') {
                uploadFolder(filtered);
            }
        }

        this.value = '';
    });

    // Close whichever dropdown was used (works for both menus)
    function hideAnyOpenDropdown(originEl) {
        if (typeof bootstrap === 'undefined') return;
        const menu = originEl.closest('.dropdown-menu');
        if (!menu) return;
        const toggleId = menu.getAttribute('aria-labelledby');
        if (!toggleId) return;
        const toggleEl = document.getElementById(toggleId);
        if (!toggleEl) return;
        const dd = bootstrap.Dropdown.getInstance(toggleEl) || new bootstrap.Dropdown(toggleEl);
        dd.hide();
    }

    // ---- PROGRESS UI helpers (wire these to your uploader) ----
    function startUploadUI(firstFile) {
        const panel = document.getElementById('uploadProgressContainer');
        if (!panel) return;
        panel.style.display = 'block';
        setText('uploadFileName', firstFile?.name || 'Preparing...');
        setText('uploadStatus', '0% uploaded');
        setText('fileSize', firstFile ? formatSize(firstFile.size) : '');
        setProgress(0);
    }

    // Call this periodically from your upload code:
    //   updateUploadUI(percent, speedText)
    window.updateUploadUI = function (percent, speedText) {
        setProgress(percent);
        if (typeof speedText === 'string') setText('uploadStatus', speedText);
    };

    // Call when the upload finishes:
    window.finishUploadUI = function () {
        setProgress(100);
        setText('uploadStatus', 'Upload complete');
        setTimeout(() => {
            const panel = document.getElementById('uploadProgressContainer');
            if (panel) panel.style.display = 'none';
        }, 800);
    };

    // Call if the user cancels or an error occurs:
    window.errorUploadUI = function (msg) {
        setText('uploadStatus', msg || 'Upload canceled');
        const panel = document.getElementById('uploadProgressContainer');
        if (panel) panel.style.display = 'none';
    };

    function setText(id, txt) {
        const el = document.getElementById(id);
        if (el) el.textContent = txt;
    }

    function setProgress(pct) {
        pct = Math.max(0, Math.min(100, Math.round(pct)));
        const bar = document.getElementById('uploadProgressBar');
        const percentText = document.getElementById('percentText');
        const ring = document.getElementById('progressRingCircle');

        if (bar) bar.style.width = pct + '%';
        if (percentText) percentText.textContent = pct + '%';

        // Circular ring (SVG)
        if (ring) {
            const r = 16;                       // matches your SVG circle r
            const C = 2 * Math.PI * r;
            ring.style.strokeDasharray = C;
            ring.style.strokeDashoffset = C * (1 - pct / 100);
        }
    }

    function formatSize(bytes) {
        if (!Number.isFinite(bytes)) return '';
        const units = ['B', 'KB', 'MB', 'GB', 'TB'];
        let i = 0, n = bytes;
        while (n >= 1024 && i < units.length - 1) { n /= 1024; i++; }
        return n.toFixed(n < 10 && i > 0 ? 1 : 0) + ' ' + units[i];
    }

    // Optional: cancel button for your progress UI
    const cancelBtn = document.getElementById('cancelUpload');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', (e) => {
            e.preventDefault();
            if (typeof cancelCurrentUpload === 'function') cancelCurrentUpload();
            errorUploadUI('Upload canceled');
            state.lock = false;
        });
    }
});

// REPLACE the whole function with this
function filterFilesBySize(files) {
    // No size limit anymore â€” return as-is
    console.log(`Files accepted (no size filtering): ${files.length}`);
    return Array.from(files);
}

// **NEW: Show file filtering information**
function showFileFilteringInfo(originalFiles, filteredFiles) {
    const FILE_LIMIT = 25;
    const oversizedCount = originalFiles.length - filteredFiles.length;

    console.log(`Files after size filtering: ${filteredFiles.length} (removed ${oversizedCount} oversized files)`);

    // Show filtering notification
    if (oversizedCount > 0) {
        showToast('warning', `${oversizedCount} files removed (exceeds 100MB limit). ${filteredFiles.length} files remain.`);
    }

    // Show count info for remaining files
    if (filteredFiles.length > FILE_LIMIT) {
        showToast('info', `${filteredFiles.length} valid files found. Will upload maximum ${FILE_LIMIT} files.`);
    } else if (filteredFiles.length > 0) {
        showToast('info', `${filteredFiles.length} valid files found. All will be uploaded.`);
    }
}

// Enhanced folder upload function with file size + count limits
// REPLACE the whole function with this (keeps optional count limit)
async function uploadFolder(files) {
    const FILE_LIMIT = 25;                 // keep or remove as you wish
    const originalCount = files.length;

    console.log(`Starting upload for ${originalCount} files (no size limit)`);

    // No size filtering anymore:
    let limitedFiles = Array.from(files);
    let isCountLimited = false;

    if (limitedFiles.length > FILE_LIMIT) {
        const proceed = confirm(
            `âš ï¸ Upload count limit\n\n` +
            `Selected: ${limitedFiles.length} files\n` +
            `Limit: ${FILE_LIMIT} files maximum\n\n` +
            `Only the FIRST ${FILE_LIMIT} files will be uploaded.\n\n` +
            `Continue?`
        );
        if (!proceed) {
            showToast('info', 'Folder upload cancelled by user');
            return;
        }
        limitedFiles = limitedFiles.slice(0, FILE_LIMIT);
        isCountLimited = true;
        showToast('warning', `Uploading ${limitedFiles.length} of ${files.length} files`);
    }

    const progressContainer = document.getElementById('uploadProgressContainer');
    const fileName = document.getElementById('uploadFileName');
    progressContainer.style.display = 'block';
    fileName.textContent = isCountLimited
        ? `Preparing ${limitedFiles.length} files (limited from ${files.length})...`
        : `Preparing ${limitedFiles.length} files for upload...`;

    updateProgressDisplay(0);

    try {
        const folderStructure = await processFolderStructure(limitedFiles);
        await uploadFilesWithStructure(folderStructure);
        updateProgressDisplay(100);

        document.getElementById('uploadStatus').textContent =
            isCountLimited
                ? `Successfully uploaded ${limitedFiles.length} of ${files.length} files!`
                : `Successfully uploaded all ${limitedFiles.length} files!`;

        setTimeout(() => {
            progressContainer.style.display = 'none';
            window.location.reload();
        }, 2000);
    } catch (error) {
        console.error('Folder upload failed:', error);
        showError(`Upload failed: ${error.message}`);
    }
}


// **ENHANCED: Upload single file with size validation**
// REPLACE the whole function with this
function uploadSingleFile(file, targetFolderId) {
    return new Promise((resolve, reject) => {
        const formData = new FormData();
        formData.append('file', file);
        formData.append('size', file.size);
        formData.append('parent_folder_id', targetFolderId || '');
        formData.append('upload_auto_delete', '0');
        formData.append('password', '');
        formData.append('access_status', '0');
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (token) formData.append('_token', token);

        const xhr = new XMLHttpRequest();
        xhr.addEventListener('load', function () {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.type === 'success') {
                        console.log(`âœ… Uploaded: ${file.name} (${formatFileSize(file.size)})`);
                        resolve(response);
                    } else {
                        reject(new Error(response.msg || 'Upload failed'));
                    }
                } catch {
                    reject(new Error('Server response error'));
                }
            } else {
                reject(new Error('HTTP error: ' + xhr.status));
            }
        });
        xhr.addEventListener('error', () => reject(new Error('Network error')));
        xhr.open('POST', '/upload');
        if (token) xhr.setRequestHeader('X-CSRF-TOKEN', token);
        xhr.send(formData);
    });
}


// Process folder structure and create folders as needed
async function processFolderStructure(files) {
    const folderMap = new Map();
    const currentFolderId = getCurrentFolderId();

    // Extract unique folder paths
    const folderPaths = [...new Set(
        files
            .map(file => file.webkitRelativePath.split('/').slice(0, -1).join('/'))
            .filter(path => path !== '')
    )].sort();

    console.log('Folder paths to create:', folderPaths);

    // Create folders in order (parent first)
    for (const folderPath of folderPaths) {
        const pathParts = folderPath.split('/');
        let currentPath = '';
        let parentId = currentFolderId;

        for (let i = 0; i < pathParts.length; i++) {
            const folderName = pathParts[i];
            currentPath = currentPath ? `${currentPath}/${folderName}` : folderName;

            if (!folderMap.has(currentPath)) {
                try {
                    console.log(`Creating folder: ${folderName} in parent: ${parentId || 'root'}`);

                    const folderId = await createFolderOnServer(folderName, parentId);
                    folderMap.set(currentPath, folderId);
                    parentId = folderId;

                } catch (error) {
                    console.error(`Failed to create folder ${folderName}:`, error);
                    throw new Error(`Failed to create folder structure: ${folderName}`);
                }
            } else {
                parentId = folderMap.get(currentPath);
            }
        }
    }

    // Map files to their target folders
    const fileStructure = files.map(file => {
        const relativePath = file.webkitRelativePath;
        const folderPath = relativePath.split('/').slice(0, -1).join('/');
        const targetFolderId = folderPath ? folderMap.get(folderPath) : currentFolderId;

        return {
            file: file,
            relativePath: relativePath,
            targetFolderId: targetFolderId || currentFolderId
        };
    });

    return fileStructure;
}

// Create folder on server
function createFolderOnServer(folderName, parentFolderId = null) {
    return new Promise((resolve, reject) => {
        const formData = new FormData();
        formData.append('folder_name', folderName);
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

        if (parentFolderId) {
            formData.append('parent_folder_id', parentFolderId);
        }

        fetch('/user/files/create-folder', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            }
        })
            .then(response => response.json())
            .then(data => {
                if (data.type === 'success') {
                    resolve(data.folder.id);
                } else {
                    reject(new Error(data.msg || 'Failed to create folder'));
                }
            })
            .catch(error => {
                reject(error);
            });
    });
}

// Upload files with their structure
async function uploadFilesWithStructure(fileStructure) {
    const totalFiles = fileStructure.length;

    for (let i = 0; i < totalFiles; i++) {
        const fileInfo = fileStructure[i];

        // Update progress
        const fileName = document.getElementById('uploadFileName');
        fileName.textContent = `${fileInfo.relativePath} (${i + 1}/${totalFiles}) - ${formatFileSize(fileInfo.file.size)}`;

        // Upload file
        await uploadSingleFile(fileInfo.file, fileInfo.targetFolderId);

        // Update overall progress
        const progress = Math.round(((i + 1) / totalFiles) * 100);
        updateProgressDisplay(progress);
    }
}

// **ENHANCED: Regular file upload with size filtering**
// REPLACE the whole function with this
function uploadFiles(files) {
    const validFiles = Array.from(files); // no size filtering

    if (validFiles.length === 0) {
        showToast('error', 'No files selected.');
        return;
    }

    const formData = new FormData();
    const progressContainer = document.getElementById('uploadProgressContainer');
    const fileName = document.getElementById('uploadFileName');
    const uploadStatus = document.getElementById('uploadStatus');

    formData.append('upload_auto_delete', '0');
    formData.append('password', '');
    formData.append('parent_folder_id', getCurrentFolderId() || '');
    formData.append('access_status', '0');

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (csrfToken) formData.append('_token', csrfToken);

    progressContainer.style.display = 'block';
    fileName.textContent = validFiles.length > 1 ? `${validFiles.length} files` : validFiles[0].name;

    updateProgressDisplay(0);
    uploadFilesSequentially(validFiles, 0, formData, progressContainer, fileName, uploadStatus);
}


// Upload files sequentially (unchanged)
function uploadFilesSequentially(files, index, baseFormData, progressContainer, fileName, uploadStatus) {
    if (index >= files.length) {
        updateProgressDisplay(100);
        uploadStatus.textContent = 'Upload complete!';
        setTimeout(() => {
            progressContainer.style.display = 'none';
            window.location.reload();
        }, 1500);
        return;
    }

    const file = files[index];
    const formData = new FormData();

    // Copy base form data
    for (let pair of baseFormData.entries()) {
        if (pair[0] !== 'file') {
            formData.append(pair[0], pair[1]);
        }
    }

    // Add current file
    formData.append('file', file);
    formData.append('size', file.size);

    fileName.textContent = `${file.name} (${index + 1}/${files.length}) - ${formatFileSize(file.size)}`;
    updateFileInfo(file);

    const xhr = new XMLHttpRequest();

    xhr.upload.addEventListener('progress', function (e) {
        if (e.lengthComputable) {
            const fileProgress = (e.loaded / e.total) * 100;
            const totalProgress = ((index * 100) + fileProgress) / files.length;
            updateProgressDisplay(Math.round(totalProgress));
        }
    });

    xhr.addEventListener('load', function () {
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.type === 'success') {
                    uploadFilesSequentially(files, index + 1, baseFormData, progressContainer, fileName, uploadStatus);
                } else {
                    showError(response.msg || 'Upload failed');
                }
            } catch (e) {
                showError('Server response error');
            }
        } else {
            showError('Upload failed');
        }
    });

    xhr.addEventListener('error', function () {
        showError('Network error occurred');
    });

    const cancelBtn = document.getElementById('cancelUpload');
    if (cancelBtn) {
        cancelBtn.onclick = function () {
            xhr.abort();
            progressContainer.style.display = 'none';
        };
    }

    xhr.open('POST', '/upload');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (csrfToken) {
        xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
    }
    xhr.send(formData);
}

// Update progress display function
function updateProgressDisplay(percent) {
    percent = Math.max(0, Math.min(100, percent));

    const progressBar = document.getElementById('uploadProgressBar');
    const percentText = document.getElementById('percentText');
    const progressRing = document.getElementById('progressRingCircle');
    const uploadStatus = document.getElementById('uploadStatus');

    if (progressBar) {
        progressBar.style.width = percent + '%';
    }

    if (progressRing) {
        const circumference = 2 * Math.PI * 16;
        const offset = circumference - (percent / 100) * circumference;
        progressRing.style.strokeDasharray = circumference;
        progressRing.style.strokeDashoffset = offset;
    }

    if (percentText) {
        percentText.textContent = percent + '%';
    }
    if (uploadStatus) {
        uploadStatus.textContent = percent + '% uploaded';
    }

    const iconContainer = document.querySelector('.file-icon-container');
    if (iconContainer) {
        if (percent > 0 && percent < 100) {
            iconContainer.classList.add('uploading');
        } else {
            iconContainer.classList.remove('uploading');
        }
    }
}

// Update file information
function updateFileInfo(file) {
    const fileSize = document.getElementById('fileSize');
    const fileIcon = document.getElementById('fileTypeIcon');

    if (fileSize) {
        fileSize.textContent = formatFileSize(file.size);
    }

    if (fileIcon) {
        const extension = file.name.split('.').pop().toLowerCase();
        fileIcon.className = 'fas file-type-icon';

        if (['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'].includes(extension)) {
            fileIcon.classList.add('fa-file-image', 'file-type-image');
        } else if (['pdf'].includes(extension)) {
            fileIcon.classList.add('fa-file-pdf', 'file-type-pdf');
        } else if (['doc', 'docx'].includes(extension)) {
            fileIcon.classList.add('fa-file-word', 'file-type-doc');
        } else if (['mp4', 'avi', 'mkv', 'mov'].includes(extension)) {
            fileIcon.classList.add('fa-file-video', 'file-type-video');
        } else if (['mp3', 'wav', 'flac'].includes(extension)) {
            fileIcon.classList.add('fa-file-audio', 'file-type-audio');
        } else if (['zip', 'rar', '7z'].includes(extension)) {
            fileIcon.classList.add('fa-file-archive', 'file-type-zip');
        } else {
            fileIcon.classList.add('fa-file');
        }
    }
}

// Format file size
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Show error function
function showError(message) {
    const uploadStatus = document.getElementById('uploadStatus');
    const progressContainer = document.getElementById('uploadProgressContainer');

    if (uploadStatus) {
        uploadStatus.textContent = 'Error: ' + message;
        uploadStatus.style.color = '#ea4335';
    }

    setTimeout(() => {
        if (progressContainer) {
            progressContainer.style.display = 'none';
        }
        if (uploadStatus) {
            uploadStatus.style.color = '';
        }
    }, 3000);
}

// Show toast notification
function showToast(type, message) {
    if (typeof toastr !== 'undefined') {
        toastr[type](message);
    } else if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: type === 'error' ? 'error' : type === 'warning' ? 'warning' : 'info',
            title: message,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 4000
        });
    } else {
        console.log(`${type.toUpperCase()}: ${message}`);
        alert(message);
    }
}

// Get current folder ID
function getCurrentFolderId() {
    const urlParams = new URLSearchParams(window.location.search);
    const fromUrl = urlParams.get('folder');

    const fromElement = document.querySelector('[data-current-folder-id]')?.getAttribute('data-current-folder-id');
    const fromInput = document.getElementById('currentFolderId')?.value;

    return fromUrl || fromElement || fromInput || null;
}





function handleCustomUpload(files) {
    // Your custom upload logic here
    console.log('Processing custom upload for', files.length, 'files');

    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        console.log('File:', file.name, 'Path:', file.webkitRelativePath);
    }
}
