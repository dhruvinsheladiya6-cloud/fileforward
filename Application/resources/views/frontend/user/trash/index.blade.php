@extends('frontend.user.layouts.dash')
@section('section', lang('User', 'user'))
{{-- @section('title', lang('Trash', 'files')) --}}
@section('content')

<div class="trash-header mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h3 class="mb-1">{{ lang('Trash', 'files') }}</h3>
            <p class="text-muted mb-0">{{ lang('Files are permanently deleted after 30 days', 'files') }}</p>
        </div>
    </div>
</div>

@if ($fileEntries->count() > 0)
    {{-- Trash Actions --}}
    <div class="filemanager-actions d-flex gap-2 align-items-center" id="filemanagerActions">
        <div id="trashHeaderFormCheck" class="form-check p-0"
            data-select="{{ __('Select All') }}"
            data-unselect="{{ __('Unselect All') }}">
            <input id="selectAll" type="checkbox" class="d-none filemanager-select-all" />
            <label type="button" class="btn btn-secondary btn-md" for="selectAll" id="selectAllLabel">
                {{ __('Select All') }}
            </label>
        </div>

        {{-- Bulk Restore --}}
        <form action="{{ route('user.trash.restore-all') }}" method="POST" id="restoreAllForm" class="d-none ms-2">
            @csrf
            <div id="restoreHiddenInputs"></div>
            <button type="submit" class="btn btn-success btn-md">
                <i class="fa fa-undo me-1"></i> {{ __('Restore Selected') }}
            </button>
        </form>

        {{-- Bulk Delete Forever --}}
        <form action="{{ route('user.trash.delete-forever-all') }}" method="POST" id="deleteForeverAllForm" class="d-none ms-2">
            @csrf
            <div id="deleteHiddenInputs"></div>
            <button type="submit" class="btn btn-danger btn-md"
                    onclick="return confirm('{{ __('Are you sure you want to permanently delete selected files?') }}')">
                <i class="fa fa-trash me-1"></i> {{ __('Delete Forever Selected') }}
            </button>
        </form>
    </div>




    <div class="file-manager-header mb-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
            <!-- Left Side: Search and Inline Filters -->
            <div class="d-flex flex-wrap align-items-center gap-2 w-100 w-md-auto">
                <!-- Search Form -->
                <div class="position-relative" style="min-width: 250px;">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-transparent border-end-0">
                            <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 24 24" 
                                class="text-muted" height="14" width="14" xmlns="http://www.w3.org/2000/svg">
                                <path d="M18.031 16.6168L22.3137 20.8995L20.8995 22.3137L16.6168 18.031C15.0769 19.263 13.124 20 11 20C6.032 20 2 15.968 2 11C2 6.032 6.032 2 11 2C15.968 2 20 6.032 20 11C20 13.124 19.263 15.0769 18.031 16.6168ZM16.0247 15.8748C17.2475 14.6146 18 12.8956 18 11C18 7.1325 14.8675 4 11 4C7.1325 4 4 7.1325 4 11C4 14.8675 7.1325 18 11 18C12.8956 18 14.6146 17.2475 15.8748 16.0247L16.0247 15.8748Z"></path>
                            </svg>
                        </span>
                        <input type="text" class="form-control search-input border-start-0" placeholder="Search in trash..." 
                            name="search" autocomplete="off" id="fileSearch" value="{{ $filters['search'] ?? '' }}">
                    </div>
                </div>
                
                <!-- Filter Toggle Button -->
                <button class="btn btn-outline-secondary btn-sm d-flex align-items-center px-2 py-1" type="button" id="filterToggle">
                    <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 24 24" 
                        class="me-1" height="14" width="14" xmlns="http://www.w3.org/2000/svg">
                        <path d="M21 4V6H20L15 13.5V22H9V13.5L4 6H3V4H21ZM6.4037 6L11 12.8944V20H13V12.8944L17.5963 6H6.4037Z"></path>
                    </svg>
                    <span id="filterButtonText">Filter</span>
                </button>

                <!-- Inline Filter Controls -->
                <div id="inlineFilters" class="d-none d-flex align-items-center gap-2 flex-wrap">
                    <!-- Filter by Type -->
                    <div class="filter-dropdown-container">
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary btn-sm dropdown-toggle filter-dropdown-compact" 
                                    type="button" id="typeFilterDropdown" data-bs-toggle="dropdown">
                                <span id="typeFilterText">Type</span>
                            </button>
                            <ul class="dropdown-menu" id="typeFilterMenu">
                                <li><a class="dropdown-item" href="#" data-type="">All Types</a></li>
                                <li><a class="dropdown-item" href="#" data-type="folder">Folders</a></li>
                                <li><a class="dropdown-item" href="#" data-type="image">Images</a></li>
                                <li><a class="dropdown-item" href="#" data-type="document">Documents</a></li>
                                <li><a class="dropdown-item" href="#" data-type="video">Videos</a></li>
                                <li><a class="dropdown-item" href="#" data-type="audio">Audio</a></li>
                                <li><a class="dropdown-item" href="#" data-type="archive">Archives</a></li>
                                <li><a class="dropdown-item" href="#" data-type="other">Others</a></li>
                            </ul>
                        </div>
                    </div>
                    
                    <!-- Sort By -->
                    <div class="filter-dropdown-container">
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary btn-sm dropdown-toggle filter-dropdown-compact" 
                                    type="button" id="sortFilterDropdown" data-bs-toggle="dropdown">
                                <span id="sortFilterText">Sort</span>
                            </button>
                            <ul class="dropdown-menu" id="sortFilterMenu">
                                <li><a class="dropdown-item" href="#" data-sort="deleted_at" data-order="desc">Recently Deleted</a></li>
                                <li><a class="dropdown-item" href="#" data-sort="deleted_at" data-order="asc">Oldest Deleted</a></li>
                                <li><a class="dropdown-item" href="#" data-sort="expiry_at" data-order="asc">Expires Soon</a></li>
                                <li><a class="dropdown-item" href="#" data-sort="name" data-order="asc">A-Z</a></li>
                                <li><a class="dropdown-item" href="#" data-sort="name" data-order="desc">Z-A</a></li>
                                <li><a class="dropdown-item" href="#" data-sort="size" data-order="desc">Largest</a></li>
                                <li><a class="dropdown-item" href="#" data-sort="size" data-order="asc">Smallest</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Clear Filters Button -->
                <button class="btn btn-danger btn-sm d-none align-items-center px-2 py-1" type="button" id="clearFilters">
                    <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 24 24" 
                        class="me-1" height="12" width="12" xmlns="http://www.w3.org/2000/svg">
                        <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"></path>
                    </svg>
                    <span>Clear</span>
                </button>
            </div>
            
            <!-- Right Side: View Toggle -->
            <div class="d-flex align-items-center gap-2">
                <div class="btn-group view-toggle-group" role="group">
                    <button type="button" class="btn btn-outline-secondary btn-sm view-toggle-btn" data-view="list" title="List View">
                        <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 24 24" 
                            height="16" width="16" xmlns="http://www.w3.org/2000/svg">
                            <path d="M11 4H21V6H11V4ZM11 8H17V10H11V8ZM11 14H21V16H11V14ZM11 18H17V20H11V18ZM3 4H9V10H3V4ZM5 6V8H7V6H5ZM3 14H9V20H3V14ZM5 16V18H7V16H5Z"></path>
                        </svg>
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm view-toggle-btn active" data-view="grid" title="Grid View">
                        <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 24 24" 
                            height="16" width="16" xmlns="http://www.w3.org/2000/svg">
                            <path d="M21 3C21.5523 3 22 3.44772 22 4V20C22 20.5523 21.5523 21 21 21H3C2.44772 21 2 20.5523 2 20V4C2 3.44772 2.44772 3 3 3H21ZM11 13H4V19H11V13ZM20 13H13V19H20V13ZM11 5H4V11H11V5ZM20 5H13V11H20V5Z"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>



    <!-- File Listing Container -->
    <div id="fileContainer">
        <!-- Grid View (Default) -->
        <div id="gridView" class="row row-cols-1 row-cols-sm-2 row-cols-md-4 row-cols-xxl-5 g-3 mb-4">
            @forelse ($fileEntries as $fileEntry)
                <div class="col-12 file-item"
                    data-file-id="{{ $fileEntry->shared_id }}"
                    data-file-name="{{ $fileEntry->name }}"
                    data-file-type="{{ $fileEntry->type }}"
                    data-file-size="{{ $fileEntry->size }}"
                    data-file-date="{{ $fileEntry->created_at }}"
                    data-deleted-date="{{ $fileEntry->deleted_at }}"
                    data-expiry-date="{{ $fileEntry->expiry_at }}">

                    <div class="filemanager-file trash-file">
                        <div class="filemanager-file-actions">
                            <div class="form-check">
<input type="checkbox"
       class="form-check-input file-checkbox"
       value="{{ $fileEntry->shared_id }}"
       id="trash_{{ $fileEntry->shared_id }}" />

                            </div>
                            <div class="dropdown">
                                <a class="dropdown-toggle-custom" type="button" data-bs-toggle="dropdown">
                                    <svg stroke="currentColor" fill="currentColor" stroke-width="0.5" viewBox="0 0 24 24" height="20" width="20" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M12 3C11.175 3 10.5 3.675 10.5 4.5C10.5 5.325 11.175 6 12 6C12.825 6 13.5 5.325 13.5 4.5C13.5 3.675 12.825 3 12 3ZM12 18C11.175 18 10.5 18.675 10.5 19.5C10.5 20.325 11.175 21 12 21C12.825 21 13.5 20.325 13.5 19.5C13.5 18.675 12.825 18 12 18ZM12 10.5C11.175 10.5 10.5 11.175 10.5 12C10.5 12.825 11.175 13.5 12 13.5C12.825 13.5 13.5 12.825 13.5 12C13.5 11.175 12.825 10.5 12 10.5Z"></path>
                                    </svg>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <!-- Restore Option -->
                                    <li>
                                        <form action="{{ route('user.trash.restore', $fileEntry->shared_id) }}" method="POST">
                                            @csrf
                                            <button class="dropdown-item text-success confirm-action-form">
                                                <i class="fas fa-undo me-2"></i>{{ lang('Restore', 'files') }}
                                            </button>
                                        </form>
                                    </li>

                                    <!-- Delete Forever Option -->
                                    <li>
                                        <form action="{{ route('user.trash.delete-forever', $fileEntry->shared_id) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button class="dropdown-item text-danger confirm-action-form">
                                                <i class="fas fa-times me-2"></i>{{ lang('Delete Forever', 'files') }}
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        {{-- Different handling for folders vs files --}}
                        @if($fileEntry->type === 'folder')
                            {{-- Folder Icon and Link (navigate inside Trash) --}}
                            <a href="{{ route('user.trash.index', ['folder' => $fileEntry->shared_id] + request()->except('page')) }}"
                            class="filemanager-file-icon filemanager-link">
                                <i class="fas fa-folder" style="font-size: 48px; color: #ffc107;"></i>
                            </a>

                            <a href="{{ route('user.trash.index', ['folder' => $fileEntry->shared_id] + request()->except('page')) }}"
                            class="filemanager-file-title filemanager-link">
                                {{ $fileEntry->name }}
                            </a>

                            <div class="filemanager-file-info">
                                <small class="text-muted">
                                    Deleted: {{ optional($fileEntry->deleted_at)->format('M d, Y') ?? '-' }}<br>
                                    <span class="text-danger">Expires: {{ optional($fileEntry->expiry_at)->format('M d, Y') ?? '-' }}</span>
                                </small>
                            </div>
                        @else
                            {{-- File Icon and Link (preview) --}}
                            <a href="{{ route('file.preview', $fileEntry->shared_id) }}"
                            target="_blank"
                            class="filemanager-file-icon filemanager-link">
                                @php $t = $fileEntry->type; @endphp
                                @if($t === 'image')
                                    <i class="fas fa-file-image" style="font-size: 48px; color: #6c757d;"></i>
                                @elseif($t === 'pdf')
                                    <i class="fas fa-file-pdf" style="font-size: 48px; color: #dc3545;"></i>
                                @elseif($t === 'video')
                                    <i class="fas fa-file-video" style="font-size: 48px; color: #6c757d;"></i>
                                @elseif($t === 'audio')
                                    <i class="fas fa-file-audio" style="font-size: 48px; color: #6c757d;"></i>
                                @elseif($t === 'archive')
                                    <i class="fas fa-file-archive" style="font-size: 48px; color: #6c757d;"></i>
                                @elseif($t === 'document')
                                    <i class="fas fa-file-alt" style="font-size: 48px; color: #6c757d;"></i>
                                @else
                                    <i class="fas fa-file" style="font-size: 48px; color: #6c757d;"></i>
                                @endif
                            </a>

                            <a href="{{ route('file.preview', $fileEntry->shared_id) }}"
                            target="_blank"
                            class="filemanager-file-title filemanager-link">
                                {{ $fileEntry->name }}
                            </a>

                            <div class="filemanager-file-info">
                                <small class="text-muted">
                                    {{ formatBytes($fileEntry->size) }}<br>
                                    Deleted: {{ optional($fileEntry->deleted_at)->format('M d, Y') ?? '-' }}<br>
                                    <span class="text-danger">Expires: {{ optional($fileEntry->expiry_at)->format('M d, Y') ?? '-' }}</span>
                                </small>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <p class="text-muted text-center mb-0">{{ __('No items in this folder.') }}</p>
                </div>
            @endforelse
        </div>
    </div>


    <!-- Pagination -->
    <div class="mt-4">
        {{ $fileEntries->links() }}
    </div>

@else
    <!-- Empty Trash State -->
    <div class="text-center py-5">
        <div class="mb-4">
            <svg width="120" height="120" viewBox="0 0 24 24" fill="none" class="mx-auto text-muted">
                <path d="M7.5 2.5V2a1 1 0 0 1 1-1h3a1 1 0 0 1 1 1v.5h4a.5.5 0 0 1 0 1h-1v11a2 2 0 0 1-2 2h-8a2 2 0 0 1-2-2v-11h-1a.5.5 0 0 1 0-1h4ZM8.5 2v.5h3V2h-3ZM4.5 4.5v11a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1v-11h-10Z" fill="currentColor"/>
                <path d="M7 6.5a.5.5 0 0 1 1 0v6a.5.5 0 0 1-1 0v-6ZM9.5 6.5a.5.5 0 0 1 1 0v6a.5.5 0 0 1-1 0v-6ZM12 6.5a.5.5 0 0 1 1 0v6a.5.5 0 0 1-1 0v-6Z" fill="currentColor"/>
            </svg>
        </div>
        <h4 class="text-muted mb-2">{{ lang('Trash is empty', 'files') }}</h4>
        <p class="text-muted mb-4">{{ lang('Deleted files will appear here and be permanently removed after 30 days.', 'files') }}</p>
        <a href="{{ route('user.files.index') }}" class="btn btn-primary">
            <i class="fas fa-arrow-left me-2"></i>{{ lang('Back to Files', 'files') }}
        </a>
    </div>
@endif

<!-- JavaScript for bulk actions -->
<script>
document.addEventListener('DOMContentLoaded', function () {
  // ----- DOM refs (scoped to header) -----
  const actionsBar  = document.getElementById('filemanagerActions');
  const headerCheck = actionsBar.querySelector('#selectAll');
  const label       = actionsBar.querySelector('#selectAllLabel');
  const headerFormCheck = actionsBar.querySelector('#trashHeaderFormCheck');

  const selectText   = headerFormCheck?.dataset.select   || 'Select All';
  const unselectText = headerFormCheck?.dataset.unselect || 'Unselect All';

  const restoreForm  = document.getElementById('restoreAllForm');
  const deleteForm   = document.getElementById('deleteForeverAllForm');
  const restoreWrap  = document.getElementById('restoreHiddenInputs');
  const deleteWrap   = document.getElementById('deleteHiddenInputs');

  const fileContainer = document.getElementById('fileContainer') || document;

  // ----- helpers -----
  function boxes() {
    return Array.from(document.querySelectorAll('.file-checkbox'));
  }

  function selectedIds() {
    return boxes().filter(cb => cb.checked).map(cb => cb.value);
  }

  function rebuildHiddenInputs(container, ids) {
    container.innerHTML = '';
    ids.forEach(id => {
      const input = document.createElement('input');
      input.type  = 'hidden';
      input.name  = 'file_ids[]';
      input.value = id;
      container.appendChild(input);
    });
  }

  function refreshHiddenInputs() {
    const ids = selectedIds();
    rebuildHiddenInputs(restoreWrap, ids);
    rebuildHiddenInputs(deleteWrap, ids);
    return ids;
  }

  function updateHeaderUI() {
    const all = boxes();
    const allChecked = all.length > 0 && all.every(cb => cb.checked);
    headerCheck.checked = allChecked;
    label.textContent = allChecked ? unselectText : selectText;
  }

  function updateVisibility() {
    const count = selectedIds().length;
    const show = count > 0;
    restoreForm.classList.toggle('d-none', !show);
    deleteForm.classList.toggle('d-none', !show);
  }

  function syncUI() {
    refreshHiddenInputs();
    updateHeaderUI();
    updateVisibility();
  }

  // ----- events -----

  // 1) Direct change on any file checkbox
  function bindToCheckbox(cb) {
    if (cb._boundChange) return;
    cb.addEventListener('change', syncUI);
    cb._boundChange = true;
  }
  boxes().forEach(bindToCheckbox);

  // 2) Select All toggler
  headerCheck.addEventListener('change', function () {
    const checked = headerCheck.checked;
    boxes().forEach(cb => { cb.checked = checked; });
    syncUI();
  });

  // 3) Click on card area toggles its checkbox (nice UX)
  //    Avoid toggling when the click is on actual controls (inputs, buttons, links, dropdowns)
  fileContainer.addEventListener('click', function (e) {
    const isControl = e.target.closest('input,button,label,a,.dropdown,.dropdown-menu,.form-check');
    if (isControl) return;
    const item = e.target.closest('.file-item');
    if (!item) return;
    const cb = item.querySelector('.file-checkbox');
    if (!cb) return;
    cb.checked = !cb.checked;
    syncUI();
  });

  // 4) If DOM changes (pagination, ajax), (re)bind new checkboxes
  const mo = new MutationObserver(() => {
    boxes().forEach(bindToCheckbox);
    syncUI();
  });
  mo.observe(fileContainer, { childList: true, subtree: true });

  // 5) Final guard: right before submit, rebuild inputs from CURRENT state
  [restoreForm, deleteForm].forEach(form => {
    form.addEventListener('submit', function (e) {
      const ids = refreshHiddenInputs(); // rebuild from live checkboxes
      if (ids.length === 0) {
        e.preventDefault(); // nothing selected — do nothing
      }
    });
  });

  // initial paint
  syncUI();
});
</script>


@endsection
