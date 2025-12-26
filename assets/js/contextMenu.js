/**
 * Context Menu Module
 * Handles right-click context menu for file items
 * Extracted from extrachange.js for modularity
 *
 * Usage:
 *   ContextMenu.init({
 *     fileItemClass: 'file-item'
 *   });
 */
(function (global) {
    'use strict';

    const ContextMenu = {
        config: {
            fileItemClass: 'file-item',
            menuClass: 'right-click-menu',
            menuZIndex: 1050
        },

        state: {
            menu: null,
            currentFileItem: null,
            isBootstrapDropdownOpen: false,
            initialized: false
        },

        /**
         * Initialize the context menu
         */
        init: function (options = {}) {
            if (this.state.initialized) return;
            this.state.initialized = true;

            Object.assign(this.config, options);
            this._bindEvents();
        },

        /**
         * Bind all event listeners
         */
        _bindEvents: function () {
            const self = this;

            // Monitor Bootstrap dropdown states
            document.addEventListener('show.bs.dropdown', () => {
                self.state.isBootstrapDropdownOpen = true;
                self.hide();
            });

            document.addEventListener('hide.bs.dropdown', () => {
                self.state.isBootstrapDropdownOpen = false;
            });

            // Handle right-click on file items
            document.addEventListener('contextmenu', (e) => self._onContextMenu(e));

            // Handle clicks
            document.addEventListener('click', (e) => self._onClick(e));

            // Handle mousedown for early detection
            document.addEventListener('mousedown', (e) => self._onMouseDown(e));

            // Hide on scroll and escape
            document.addEventListener('scroll', () => {
                self.hide();
                self._hideBootstrapDropdowns();
            });

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    self.hide();
                    self._hideBootstrapDropdowns();
                }
            });
        },

        /**
         * Handle context menu event
         */
        _onContextMenu: function (e) {
            const fileItem = e.target.closest('.' + this.config.fileItemClass);

            if (fileItem) {
                e.preventDefault();

                // Don't show if Bootstrap dropdown is open
                if (this.state.isBootstrapDropdownOpen) {
                    this._hideBootstrapDropdowns();
                    return;
                }

                this._hideBootstrapDropdowns();
                this.show(e, fileItem);
            } else {
                this.hide();
            }
        },

        /**
         * Handle click event
         */
        _onClick: function (e) {
            const dropdownToggle = e.target.closest('[data-bs-toggle="dropdown"]');

            if (dropdownToggle) {
                this.hide();
                setTimeout(() => {
                    this.state.isBootstrapDropdownOpen = true;
                }, 10);
            } else if (!e.target.closest('.' + this.config.menuClass) && !e.target.closest('.dropdown-menu')) {
                this.hide();
                this._hideBootstrapDropdowns();
            }
        },

        /**
         * Handle mousedown event
         */
        _onMouseDown: function (e) {
            const dropdownToggle = e.target.closest('[data-bs-toggle="dropdown"]');
            const isRightClick = e.button === 2;

            if (dropdownToggle && !isRightClick) {
                this.hide();
            } else if (isRightClick) {
                this._hideBootstrapDropdowns();
            }
        },

        /**
         * Show context menu at position
         */
        show: function (event, fileItem) {
            // Check if Bootstrap dropdown is open
            if (this.state.isBootstrapDropdownOpen || document.querySelector('.dropdown-menu.show')) {
                return;
            }

            this.hide(); // Hide existing menu

            this.state.currentFileItem = fileItem;

            // Find the dropdown menu in this file item
            const originalDropdown = fileItem.querySelector('.dropdown-menu');
            if (!originalDropdown) return;

            // Clone the dropdown menu
            this.state.menu = originalDropdown.cloneNode(true);
            this.state.menu.classList.remove('dropdown-menu', 'dropdown-menu-end', 'show');
            this.state.menu.classList.add(this.config.menuClass);
            this.state.menu.style.display = 'block';
            this.state.menu.style.position = 'fixed';
            this.state.menu.style.zIndex = this.config.menuZIndex;

            // Add custom styles
            this.state.menu.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
            this.state.menu.style.borderRadius = '8px';
            this.state.menu.style.padding = '8px 0';
            this.state.menu.style.minWidth = '180px';
            this.state.menu.style.backgroundColor = '#fff';
            this.state.menu.style.border = '1px solid rgba(0,0,0,0.1)';

            // Position the menu
            this._position(event);

            // Add to document
            document.body.appendChild(this.state.menu);

            // Bind events to cloned menu items
            this._bindMenuEvents();
        },

        /**
         * Hide context menu
         */
        hide: function () {
            if (this.state.menu) {
                this.state.menu.remove();
                this.state.menu = null;
                this.state.currentFileItem = null;
            }
        },

        /**
         * Position menu at event location
         */
        _position: function (event) {
            if (!this.state.menu) return;

            // Temporarily show to get dimensions
            this.state.menu.style.visibility = 'hidden';
            this.state.menu.style.display = 'block';

            const rect = this.state.menu.getBoundingClientRect();
            const viewportWidth = window.innerWidth;
            const viewportHeight = window.innerHeight;

            let x = event.clientX;
            let y = event.clientY;

            // Adjust if menu would go off-screen
            if (x + rect.width > viewportWidth) {
                x = viewportWidth - rect.width - 10;
            }
            if (y + rect.height > viewportHeight) {
                y = viewportHeight - rect.height - 10;
            }

            this.state.menu.style.left = `${x}px`;
            this.state.menu.style.top = `${y}px`;
            this.state.menu.style.visibility = 'visible';
        },

        /**
         * Bind events to menu items
         */
        _bindMenuEvents: function () {
            if (!this.state.menu) return;

            const self = this;

            // Handle link clicks
            this.state.menu.querySelectorAll('a.dropdown-item').forEach(link => {
                link.addEventListener('click', (e) => {
                    // Don't prevent default - let the link work normally
                    self.hide();
                });
            });

            // Handle form submissions
            this.state.menu.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', () => {
                    self.hide();
                });
            });

            // Handle button clicks
            this.state.menu.querySelectorAll('button.dropdown-item').forEach(btn => {
                btn.addEventListener('click', () => {
                    // The original click event should still work
                    self.hide();
                });
            });
        },

        /**
         * Hide all Bootstrap dropdowns
         */
        _hideBootstrapDropdowns: function () {
            const openDropdowns = document.querySelectorAll('.dropdown-menu.show');
            openDropdowns.forEach(dropdown => {
                if (typeof bootstrap !== 'undefined') {
                    const dropdownInstance = bootstrap.Dropdown?.getInstance(dropdown.previousElementSibling);
                    if (dropdownInstance) {
                        dropdownInstance.hide();
                        return;
                    }
                }
                dropdown.classList.remove('show');
            });

            // Remove backdrop if exists
            const backdrop = document.querySelector('.dropdown-backdrop');
            if (backdrop) backdrop.remove();

            this.state.isBootstrapDropdownOpen = false;
        }
    };

    // Auto-initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => ContextMenu.init());
    } else {
        ContextMenu.init();
    }

    // Export to global
    global.ContextMenu = ContextMenu;

})(window);
