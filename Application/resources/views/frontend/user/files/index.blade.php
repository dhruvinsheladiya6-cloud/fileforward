@extends('frontend.user.layouts.dash')
@section('section', lang('User', 'user'))
@section('title', lang('Manage Files', 'files'))
@section('upload', true)
@section('search', true)
@section('content')

    @if ($fileEntries->count() > 0)
        

        <!-- File Manager Actions -->
        <div class="filemanager-actions" id="filemanagerActions">
            <div class="form-check p-0" data-select="{{ lang('Select All', 'files') }}"
                data-unselect="{{ lang('Unselect All', 'files') }}">
                <input id="selectAll" type="checkbox" class="d-none filemanager-select-all" />
                <label type="button" class="btn btn-secondary btn-md" for="selectAll" id="selectAllLabel">
                    {{ lang('Select All', 'files') }}
                </label>
            </div>
            {{-- <form action="{{ route('user.files.delete.all') }}" method="POST">
                @csrf
                <input id="filesSelectedInput" name="ids" value="" hidden />
                <button class="btn btn-danger btn-md confirm-action-form">
                    <i class="fa fa-trash-alt me-2"></i>{{ lang('Delete Selected Files', 'files') }}
                </button>
            </form> --}}
            {{-- Bulk Move to Trash --}}
            <form action="{{ route('user.files.bulk-move-to-trash') }}" method="POST" id="bulkMoveToTrashForm" class="ms-2">
                @csrf
                {{-- hold selected shared_ids as comma-separated list --}}
                <input id="filesSelectedInput" name="ids" value="" type="hidden" />
                {{-- preserve folder context --}}
                @if(request('folder'))
                    <input type="hidden" name="folder" value="{{ request('folder') }}">
                @endif
                <button type="submit" class="btn btn-warning btn-md" id="bulkMoveToTrashBtn">
                    <i class="fa fa-trash me-2"></i>{{ lang('Move Selected to Trash', 'files') }}
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
                            <input type="text" class="form-control search-input border-start-0" placeholder="Search files..." 
                                name="search" autocomplete="off" id="fileSearch" value="{{ $filters['search'] ?? '' }}">
                        </div>
                    </div>
                    
                    <!-- Filter Toggle Button (Show when filters are hidden) -->
                    <button class="btn btn-outline-secondary btn-sm d-flex align-items-center px-2 py-1" type="button" id="filterToggle">
                        <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 24 24" 
                            class="me-1" height="14" width="14" xmlns="http://www.w3.org/2000/svg">
                            <path d="M21 4V6H20L15 13.5V22H9V13.5L4 6H3V4H21ZM6.4037 6L11 12.8944V20H13V12.8944L17.5963 6H6.4037Z"></path>
                        </svg>
                        <span id="filterButtonText">Filter</span>
                    </button>

                    <!-- Inline Filter Controls (Hidden by default) -->
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
                                    <li><a class="dropdown-item" href="#" data-type="image">Images</a></li>
                                    <li><a class="dropdown-item" href="#" data-type="document">Documents</a></li>
                                    <li><a class="dropdown-item" href="#" data-type="video">Videos</a></li>
                                    <li><a class="dropdown-item" href="#" data-type="audio">Audio</a></li>
                                    <li><a class="dropdown-item" href="#" data-type="archive">Archives</a></li>
                                    <li><a class="dropdown-item" href="#" data-type="other">Others</a></li>
                                </ul>
                            </div>
                        </div>
                        
                        <!-- Filter by Size -->
                        <div class="filter-dropdown-container">
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary btn-sm dropdown-toggle filter-dropdown-compact" 
                                        type="button" id="sizeFilterDropdown" data-bs-toggle="dropdown">
                                    <span id="sizeFilterText">Size</span>
                                </button>
                                <ul class="dropdown-menu" id="sizeFilterMenu">
                                    <li><a class="dropdown-item" href="#" data-size="">All Sizes</a></li>
                                    <li><a class="dropdown-item" href="#" data-size="small">Small (&lt; 1MB)</a></li>
                                    <li><a class="dropdown-item" href="#" data-size="medium">Medium (1MB - 10MB)</a></li>
                                    <li><a class="dropdown-item" href="#" data-size="large">Large (&gt; 10MB)</a></li>
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
                                    <li><a class="dropdown-item" href="#" data-sort="created_at" data-order="desc">Newest</a></li>
                                    <li><a class="dropdown-item" href="#" data-sort="created_at" data-order="asc">Oldest</a></li>
                                    <li><a class="dropdown-item" href="#" data-sort="name" data-order="asc">A-Z</a></li>
                                    <li><a class="dropdown-item" href="#" data-sort="name" data-order="desc">Z-A</a></li>
                                    <li><a class="dropdown-item" href="#" data-sort="size" data-order="desc">Largest</a></li>
                                    <li><a class="dropdown-item" href="#" data-sort="size" data-order="asc">Smallest</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Clear Filters Button (Show only when filters are applied) -->
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

        {{-- Add breadcrumb navigation --}}
        <!-- Breadcrumb Navigation -->
        @if(!empty($breadcrumbs) || $currentFolder)
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('user.files.index') }}">
                        <i class="fas fa-home me-1"></i>{{ lang('Root', 'files') }}
                    </a>
                </li>
                @if(!empty($breadcrumbs))
                    @foreach($breadcrumbs as $breadcrumb)
                        <li class="breadcrumb-item">
                            <a href="{{ route('user.files.index', ['folder' => $breadcrumb->shared_id]) }}">
                                {{ $breadcrumb->name }}
                            </a>
                        </li>
                    @endforeach
                @endif
                @if($currentFolder)
                    <li class="breadcrumb-item active" aria-current="page">
                        {{ $currentFolder->name }}
                    </li>
                @endif
            </ol>
        </nav>
        @endif



        <!-- File Listing Container -->
        <div id="fileContainer">
            <!-- Grid View (Default) -->
            <div id="gridView" class="row row-cols-1 row-cols-sm-2 row-cols-md-4 row-cols-xxl-5 g-3 mb-4">
                @foreach ($fileEntries as $fileEntry)
                    <div class="col-12 file-item" 
                        data-file-id="{{ $fileEntry->shared_id }}"
                        data-file-name="{{ $fileEntry->name }}"
                        data-file-type="{{ $fileEntry->type }}"
                        data-file-size="{{ $fileEntry->size }}"
                        data-file-date="{{ $fileEntry->created_at }}"
                        data-access-status="{{ $fileEntry->access_status ?? 1 }}"
                        data-preview-support="{{ isFileSupportPreview($fileEntry->type) ? '1' : '0' }}">
                        
                        <div class="filemanager-file">
                            <div class="filemanager-file-actions">
                                <div class="form-check">
                                    <input type="checkbox" 
                                        class="form-check-input file-checkbox" 
                                        data-file-id="{{ $fileEntry->shared_id }}"
                                        value="{{ $fileEntry->shared_id }}" 
                                        id="grid_{{ $fileEntry->shared_id }}" />
                                </div>
                                <div class="dropdown">
                                    <a class="dropdown-toggle-custom" type="button" data-bs-toggle="dropdown">
                                        <svg stroke="currentColor" fill="currentColor" stroke-width="0.5" viewBox="0 0 24 24" height="20" width="20" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M12 3C11.175 3 10.5 3.675 10.5 4.5C10.5 5.325 11.175 6 12 6C12.825 6 13.5 5.325 13.5 4.5C13.5 3.675 12.825 3 12 3ZM12 18C11.175 18 10.5 18.675 10.5 19.5C10.5 20.325 11.175 21 12 21C12.825 21 13.5 20.325 13.5 19.5C13.5 18.675 12.825 18 12 18ZM12 10.5C11.175 10.5 10.5 11.175 10.5 12C10.5 12.825 11.175 13.5 12 13.5C12.825 13.5 13.5 12.825 13.5 12C13.5 11.175 12.825 10.5 12 10.5Z"></path>
                                        </svg>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        @if($fileEntry->type === 'folder')
                                            {{-- Folder-specific actions --}}
                                            <li>
                                                <a class="dropdown-item" href="{{ route('user.files.index', ['folder' => $fileEntry->shared_id]) }}">
                                                    <i class="fa fa-folder-open me-2"></i>{{ lang('Open folder', 'files') }}
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="{{ route('user.files.edit', $fileEntry->shared_id) }}">
                                                    <i class="fa fa-edit me-2"></i>{{ lang('Rename folder', 'files') }}
                                                </a>
                                            </li>
                                            <li>
                                                <a href="#"
                                                    class="dropdown-item fileManager-share-with-me"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#sharedWithMeModal"
                                                    data-file-id="{{ $fileEntry->shared_id }}"
                                                    data-file-name="{{ $fileEntry->name }}"
                                                    data-file-type="{{ $fileEntry->type }}">
                                                    <i class="fas fa-user-friends me-2"></i>{{ lang('Share', 'files') }}
                                                </a>

                                           </li>
                                           <li>
                                                {{-- 
                                                <form action="{{ route('user.files.delete', $fileEntry->shared_id) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="dropdown-item text-danger confirm-action-form">
                                                        <i class="fa fa-trash-alt me-2"></i>{{ lang('Delete folder', 'files') }}
                                                    </button>
                                                </form>
                                                --}}
                                                
                                                {{-- NEW: Move to Trash --}}
                                                <form action="{{ route('user.files.move-to-trash', $fileEntry->shared_id) }}" method="POST">
                                                    @csrf
                                                    <button class="dropdown-item text-warning confirm-action-form">
                                                        <i class="fa fa-trash-alt me-2"></i>{{ lang('Move to Trash', 'files') }}
                                                    </button>
                                                </form>

                                            </li>
                                        @else 
                                            {{-- File-specific actions --}}
                                            {{-- @if ($fileEntry->access_status) --}}
                                                <li>
                                                    <a href="#"
                                                        class="dropdown-item fileManager-share-file"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#shareModal"
                                                        data-preview="{{ isFileSupportPreview($fileEntry->type) ? 'true' : 'false' }}"
                                                        data-share='{"filename":"{{ $fileEntry->name }}","download_link":"{{ route('file.download', $fileEntry->shared_id) }}","preview_link":"{{ route('file.preview', $fileEntry->shared_id) }}"}'>
                                                        <i class="fas fa-share-alt me-2"></i>{{ lang('Share', 'files') }}
                                                    </a>
                                                </li>
                                            {{-- @endif --}}

                                            @if (isFileSupportPreview($fileEntry->type))
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('file.preview', $fileEntry->shared_id) }}" target="_blank">
                                                        <i class="fa fa-eye me-2"></i>{{ lang('Preview', 'files') }}
                                                    </a>
                                                </li>
                                            @endif
                                            <li>
                                                <a class="dropdown-item" href="{{ route('file.download', $fileEntry->shared_id) }}" target="_blank">
                                                    <i class="fa fa-download me-2"></i>{{ lang('Download', 'files') }}
                                                </a>
                                            </li>
                                            {{-- NEW: Move to folder option --}}
                                            <li>
                                                <a class="dropdown-item file-move-btn" href="#" data-file-id="{{ $fileEntry->shared_id }}">
                                                    <i class="fa fa-folder-open me-2"></i>Move to...
                                                </a>
                                            </li>


                                            {{-- On both FILE and FOLDER items --}}
                                            <li>
                                                <a class="dropdown-item fm-cut" href="#" data-file-id="{{ $fileEntry->shared_id }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" style="height: 20px;width: 15px;margin-right: 5px;"><path d="M256 320L216.5 359.5C203.9 354.6 190.3 352 176 352C114.1 352 64 402.1 64 464C64 525.9 114.1 576 176 576C237.9 576 288 525.9 288 464C288 449.7 285.3 436.1 280.5 423.5L563.2 140.8C570.3 133.7 570.3 122.3 563.2 115.2C534.9 86.9 489.1 86.9 460.8 115.2L320 256L280.5 216.5C285.4 203.9 288 190.3 288 176C288 114.1 237.9 64 176 64C114.1 64 64 114.1 64 176C64 237.9 114.1 288 176 288C190.3 288 203.9 285.3 216.5 280.5L256 320zM353.9 417.9L460.8 524.8C489.1 553.1 534.9 553.1 563.2 524.8C570.3 517.7 570.3 506.3 563.2 499.2L417.9 353.9L353.9 417.9zM128 176C128 149.5 149.5 128 176 128C202.5 128 224 149.5 224 176C224 202.5 202.5 224 176 224C149.5 224 128 202.5 128 176zM176 416C202.5 416 224 437.5 224 464C224 490.5 202.5 512 176 512C149.5 512 128 490.5 128 464C128 437.5 149.5 416 176 416z"></path></svg>
                                                    Cut
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item fm-copy" href="#" data-file-id="{{ $fileEntry->shared_id }}">
                                                    <i class="fa fa-copy me-2"></i>Copy
                                                </a>
                                            </li>

                                            {{-- Paste is contextual: show it in folder rows and also a toolbar button for the current folder --}}
                                            <li>
                                                <a class="dropdown-item fm-paste" href="#" 
                                                    data-target-folder="{{ $fileEntry->type === 'folder' ? $fileEntry->shared_id : '' }}">
                                                    <i class="fa fa-paste me-2"></i>Paste into {{ $fileEntry->type === 'folder' ? 'this folder' : '…' }}
                                                </a>
                                            </li>

                                            {{-- share with me (simple modal) --}}
                                            <li>
                                                <a href="#"
                                                class="dropdown-item fileManager-share-with-me"
                                                data-bs-toggle="modal"
                                                data-bs-target="#sharedWithMeModal"
                                                data-file-id="{{ $fileEntry->shared_id }}"
                                                data-file-name="{{ $fileEntry->name }}"
                                                data-file-type="{{ $fileEntry->type }}"
                                                data-share='{"filename":"{{ $fileEntry->name }}","download_link":"{{ route('file.download', $fileEntry->shared_id) }}"}'>
                                                <i class="fas fa-user-friends me-2"></i>{{ lang('Share', 'files') }}
                                                </a>
                                            </li>





                                            <li>
                                                <a class="dropdown-item" href="{{ route('user.files.edit', $fileEntry->shared_id) }}">
                                                    <i class="fa fa-edit me-2"></i>{{ lang('Edit details', 'files') }}
                                                </a>
                                            </li>
                                            <li>
                                                {{-- 
                                                <form action="{{ route('user.files.delete', $fileEntry->shared_id) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="dropdown-item text-danger confirm-action-form">
                                                        <i class="fa fa-trash-alt me-2"></i>{{ lang('Delete', 'files') }}
                                                    </button>
                                                </form>
                                                --}}
                                                
                                                {{-- NEW: Move to Trash --}}
                                                <form action="{{ route('user.files.move-to-trash', $fileEntry->shared_id) }}" method="POST">
                                                    @csrf
                                                    @if(request()->has('folder'))
                                                        <input type="hidden" name="folder" value="{{ request('folder') }}">
                                                    @endif
                                                    <button class="dropdown-item text-warning confirm-action-form">
                                                        <i class="fa fa-trash-alt me-2"></i>{{ lang('Move to Trash', 'files') }}
                                                    </button>
                                                </form>

                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            </div>


                            {{-- Different handling for folders vs files --}}
                            @if($fileEntry->type === 'folder')
                                {{-- Folder Icon and Link --}}
                                <a href="{{ route('user.files.index', ['folder' => $fileEntry->shared_id]) }}" class="filemanager-file-icon filemanager-link">
                                    <i class="fas fa-folder" style="font-size: 48px; color: #ffc107;"></i>
                                </a>
                                <a href="{{ route('user.files.index', ['folder' => $fileEntry->shared_id]) }}" class="filemanager-file-title filemanager-link">
                                    {{ $fileEntry->name }}
                                </a>
                                <div class="filemanager-file-info">
                                    {{-- <small class="text-muted">Folder • {{ vDate($fileEntry->created_at) }}</small> --}}
                                </div>
                            @else
                                {{-- File Icon and Link --}}
                                <a href="{{ route('file.preview', $fileEntry->shared_id) }}" target="_blank" class="filemanager-file-icon filemanager-link">
                                        @if ($fileEntry->type == 'image')
                                        {{-- Image SVG --}}
                                        {{-- You can use a specific SVG for image files --}}
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 120" fill="none" class="w-auto h-full shrink-0"><path fill="#00AEC6" d="M69.835 24.62a10.315 10.315 0 0 1-10.31-10.31V0h-46.07A13.455 13.455 0 0 0 0 13.46v81.105a13.455 13.455 0 0 0 13.455 13.46h55.3a13.446 13.446 0 0 0 9.516-3.943 13.45 13.45 0 0 0 3.94-9.517v-69.94l-12.376-.005Z" opacity="0.3"></path><path fill="#00AEC6" d="M82.21 24.62H69.835a10.315 10.315 0 0 1-10.31-10.31V0L82.21 24.62Zm9.76 60.68H29.45a8.03 8.03 0 0 0-8.03 8.03v18.641a8.03 8.03 0 0 0 8.03 8.03h62.52a8.03 8.03 0 0 0 8.03-8.03v-18.64a8.03 8.03 0 0 0-8.03-8.03Z"></path><path fill="#fff" d="M32.352 95.453v14.545h-3.076V95.453h3.076Zm2.53 0h3.792l4.006 9.772h.17l4.006-9.772h3.793v14.545h-2.983v-9.467h-.12l-3.765 9.396H41.75l-3.764-9.432h-.121v9.503h-2.983V95.453Zm20.808 14.545h-3.296l5.022-14.545h3.963l5.014 14.545h-3.296l-3.643-11.221h-.114l-3.65 11.221Zm-.206-5.717h7.784v2.4h-7.784v-2.4Zm21.621-4.127a3.19 3.19 0 0 0-.42-.916c-.18-.27-.4-.497-.66-.681a2.834 2.834 0 0 0-.88-.434 3.71 3.71 0 0 0-1.087-.149c-.743 0-1.397.185-1.96.554-.559.37-.995.907-1.307 1.612-.313.701-.469 1.558-.469 2.571 0 1.014.154 1.875.462 2.586.308.71.743 1.252 1.307 1.626.563.369 1.228.554 1.995.554.696 0 1.29-.123 1.783-.369a2.647 2.647 0 0 0 1.136-1.059c.266-.454.398-.992.398-1.612l.625.092h-3.75v-2.315h6.087v1.833c0 1.278-.27 2.376-.81 3.295a5.514 5.514 0 0 1-2.23 2.116c-.947.493-2.031.739-3.253.739-1.364 0-2.561-.301-3.594-.902-1.032-.606-1.837-1.465-2.414-2.578-.573-1.117-.86-2.443-.86-3.977 0-1.179.17-2.23.512-3.154.345-.928.828-1.714 1.449-2.358a6.26 6.26 0 0 1 2.166-1.47 7.019 7.019 0 0 1 2.677-.504c.824 0 1.591.12 2.301.362.71.237 1.34.573 1.89 1.009a5.46 5.46 0 0 1 1.356 1.555c.35.597.575 1.255.675 1.974h-3.125Zm5.57 9.844V95.453h9.8v2.535H85.75v3.466h6.222v2.536H85.75v3.473h6.754v2.535h-9.83Z"></path><path fill="#00AEC6" d="M52.15 73.204H30.065a8.165 8.165 0 0 1-8.155-8.155V42.964a8.165 8.165 0 0 1 8.155-8.155H52.15a8.16 8.16 0 0 1 8.15 8.155v22.085a8.16 8.16 0 0 1-8.15 8.155Zm-22.085-34.79a4.555 4.555 0 0 0-4.55 4.55v22.085a4.555 4.555 0 0 0 4.55 4.55H52.15a4.55 4.55 0 0 0 4.545-4.55V42.964a4.55 4.55 0 0 0-4.545-4.55H30.065Z"></path><path fill="#00AEC6" d="M58.5 60.85v4.2a6.355 6.355 0 0 1-6.35 6.35H30.065a6.35 6.35 0 0 1-6.35-6.35V58c3.535-.76 8.92-1 15.455 1.61l4.05-3.86 2.76 7s.74-2.575 3.13-2.21c2.39.365 6.26 1.66 7.915.555a2.13 2.13 0 0 1 1.475-.245Zm-7.734-13.374a2.455 2.455 0 1 0 0-4.91 2.455 2.455 0 0 0 0 4.91Z"></path></svg>
                                    @elseif ($fileEntry->extension == 'mp4' || $fileEntry->extension == 'avi' || $fileEntry->extension == 'mov')
                                        {{-- Video SVG --}}
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 120" fill="none" class="w-auto h-full shrink-0"><path fill="#A140FF" d="M69.835 24.62a10.32 10.32 0 0 1-10.31-10.31V0h-46.07A13.455 13.455 0 0 0 0 13.45v81.11A13.454 13.454 0 0 0 13.455 108h55.3a13.446 13.446 0 0 0 9.517-3.939 13.46 13.46 0 0 0 3.943-9.516V24.62h-12.38Z" opacity="0.3"></path><path fill="#A140FF" d="M82.215 24.62h-12.38a10.32 10.32 0 0 1-10.31-10.31V0l22.69 24.62ZM91.97 85.3H29.45a8.03 8.03 0 0 0-8.03 8.03v18.641a8.03 8.03 0 0 0 8.03 8.03h62.52a8.03 8.03 0 0 0 8.03-8.03v-18.64a8.03 8.03 0 0 0-8.03-8.03Z"></path><path fill="#fff" d="m32.904 95.453 3.516 11.051h.135l3.523-11.051h3.409l-5.014 14.545H34.51l-5.022-14.545h3.416Zm15.409 0v14.545h-3.075V95.453h3.075ZM56 109.998h-5.157V95.453h5.199c1.463 0 2.722.291 3.778.873a5.894 5.894 0 0 1 2.436 2.493c.573 1.084.86 2.382.86 3.892 0 1.515-.287 2.818-.86 3.907a5.909 5.909 0 0 1-2.45 2.507c-1.06.582-2.33.873-3.807.873Zm-2.082-2.635h1.953c.91 0 1.674-.161 2.294-.483.626-.326 1.094-.831 1.407-1.512.317-.687.476-1.572.476-2.657 0-1.075-.159-1.953-.476-2.635-.313-.681-.78-1.183-1.4-1.505-.62-.322-1.384-.483-2.293-.483h-1.96v9.275Zm11.476 2.635V95.453h9.801v2.535h-6.726v3.466h6.222v2.536h-6.222v3.473h6.754v2.535h-9.83Zm25.612-7.273c0 1.587-.3 2.936-.902 4.049-.596 1.112-1.41 1.962-2.443 2.55-1.027.582-2.183.873-3.466.873-1.292 0-2.452-.293-3.48-.881-1.027-.587-1.84-1.437-2.436-2.549-.596-1.113-.895-2.46-.895-4.042 0-1.586.299-2.935.895-4.048.597-1.113 1.409-1.96 2.436-2.542 1.028-.588 2.188-.881 3.48-.881 1.284 0 2.439.293 3.466.88 1.032.583 1.847 1.43 2.443 2.543.602 1.113.902 2.462.902 4.048Zm-3.118 0c0-1.027-.153-1.893-.461-2.599-.303-.705-.732-1.24-1.286-1.605s-1.202-.547-1.946-.547c-.743 0-1.392.182-1.946.547-.554.364-.985.9-1.292 1.605-.303.706-.455 1.572-.455 2.599 0 1.028.152 1.894.455 2.6.307.705.738 1.24 1.292 1.605s1.203.547 1.946.547c.744 0 1.392-.182 1.946-.547.554-.365.983-.9 1.286-1.605.308-.706.461-1.572.461-2.6Z"></path><path fill="#A140FF" d="M59.72 65.955V42.1a1.912 1.912 0 0 0-.035-.345.576.576 0 0 0-.025-.1 1.747 1.747 0 0 0-.07-.23.878.878 0 0 0-.045-.1 1.39 1.39 0 0 0-.11-.21l-.05-.08a2.043 2.043 0 0 0-.21-.255l-.035-.03a2.416 2.416 0 0 0-.215-.18l-.09-.055-.2-.11-.105-.045a1.917 1.917 0 0 0-.24-.075h-.08a1.618 1.618 0 0 0-.345-.035h-33.68a1.866 1.866 0 0 0-1.695 1.855V66.04c-.012.307.086.608.275.85a1.854 1.854 0 0 0 1.595.91h33.5a1.87 1.87 0 0 0 1.86-1.845ZM24.5 44.74v-3.62h2.945v3.62H24.5Zm4.97 0v-3.62h2.945v3.62H29.47Zm4.97 0v-3.62h2.945v3.62H34.44Zm4.97 0v-3.62h2.945v3.62H39.41Zm4.97 0v-3.62h2.945v3.62H44.38Zm4.97 0v-3.62h2.945v3.62H49.35Zm4.97 0v-3.62h2.945v3.62H54.32Zm-30.5 17.555V45.72h34.615v16.575H23.82Zm.675 4.595v-3.62h2.945v3.62h-2.945Zm4.97 0v-3.62h2.945v3.62h-2.945Zm4.97 0v-3.62h2.945v3.62h-2.945Zm4.97 0v-3.62h2.945v3.62h-2.945Zm4.97 0v-3.62h2.945v3.62h-2.945Zm4.97 0v-3.62h2.945v3.62h-2.945Zm4.97 0v-3.62h2.945v3.62h-2.945Z"></path><path fill="#A140FF" d="m45.11 52.781-4.785-2.765a1.628 1.628 0 0 0-2.44 1.41v5.53a1.63 1.63 0 0 0 2.44 1.41l4.785-2.765a1.63 1.63 0 0 0 0-2.82Z"></path></svg>
                                    @elseif ($fileEntry->extension == 'pdf')
                                        {{-- PDF SVG --}}
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 120" fill="none" class="w-auto h-full shrink-0"><path fill="#FF3E4C" d="M69.832 24.624a10.32 10.32 0 0 1-10.31-10.31V0H13.455A13.454 13.454 0 0 0 0 13.454v81.107a13.455 13.455 0 0 0 13.454 13.435h55.303a13.454 13.454 0 0 0 13.455-13.435V24.624h-12.38Z" opacity="0.3"></path><path fill="#FF3E4C" d="M82.212 24.624h-12.38a10.32 10.32 0 0 1-10.31-10.31V0l22.69 24.624ZM65.297 75.417H13.68a1.875 1.875 0 0 1-1.875-1.875 1.875 1.875 0 0 1 1.875-1.87h51.617a1.87 1.87 0 0 1 1.73 2.587 1.87 1.87 0 0 1-1.73 1.158Zm0-21.117H13.68a1.875 1.875 0 0 1-1.875-1.87 1.875 1.875 0 0 1 1.875-1.875h51.617a1.87 1.87 0 0 1 1.87 1.875 1.87 1.87 0 0 1-1.87 1.87Zm0 10.558H13.68a1.875 1.875 0 0 1-1.875-1.875 1.875 1.875 0 0 1 1.875-1.87h51.617a1.87 1.87 0 0 1 1.73 2.587 1.87 1.87 0 0 1-1.73 1.158ZM44.938 43.737H13.68a1.875 1.875 0 0 1-1.875-1.87 1.875 1.875 0 0 1 1.875-1.875h31.258a1.875 1.875 0 0 1 1.87 1.875 1.875 1.875 0 0 1-1.87 1.87Zm0-10.559H13.68a1.875 1.875 0 0 1-1.875-1.87 1.875 1.875 0 0 1 1.875-1.874h31.258a1.875 1.875 0 0 1 1.87 1.874 1.875 1.875 0 0 1-1.87 1.87ZM91.966 85.3H29.449a8.03 8.03 0 0 0-8.03 8.03v18.64a8.03 8.03 0 0 0 8.03 8.029h62.517a8.03 8.03 0 0 0 8.03-8.029V93.33a8.03 8.03 0 0 0-8.03-8.03Z"></path><path fill="#fff" d="M41.928 106.684v3.635h-3.76v-3.635h3.76Zm5.53-1.639v5.275h-3.635V95.285h5.89c1.785 0 3.15.445 4.085 1.33a4.699 4.699 0 0 1 1.4 3.585 4.905 4.905 0 0 1-.625 2.5 4.382 4.382 0 0 1-1.86 1.725 6.543 6.543 0 0 1-3 .625l-2.255-.005Zm4.04-4.845c0-1.333-.73-2-2.19-2h-1.85v3.915h1.85c1.46.013 2.19-.625 2.19-1.915Zm18.064 6.498a6.635 6.635 0 0 1-2.72 2.67 8.717 8.717 0 0 1-4.18.955h-5.675V95.288h5.675a8.86 8.86 0 0 1 4.19.935 6.5 6.5 0 0 1 2.71 2.64 7.86 7.86 0 0 1 .95 3.91 7.937 7.937 0 0 1-.95 3.925Zm-3.91-.755a4.182 4.182 0 0 0 1.18-3.17 4.167 4.167 0 0 0-1.18-3.165 4.585 4.585 0 0 0-3.305-1.13h-1.725v8.59h1.725a4.608 4.608 0 0 0 3.305-1.125Zm16.725-10.658v2.895h-6.165v3.295h4.76v2.765h-4.76v6.08h-3.64V95.285h9.805Z"></path><path fill="#FF3E4C" d="M64.383 29.434h-8.84a2.79 2.79 0 0 0-2.79 2.79v8.84a2.79 2.79 0 0 0 2.79 2.79h8.84a2.79 2.79 0 0 0 2.79-2.79v-8.84a2.79 2.79 0 0 0-2.79-2.79Z"></path></svg>
                                    @elseif (in_array($fileEntry->extension, ['doc', 'docx']))
                                        {{-- Word Document DOC --}}
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 120" fill="none" class="w-auto h-full shrink-0"><path fill="#FF3E4C" d="M69.832 24.624a10.32 10.32 0 0 1-10.31-10.31V0H13.455A13.454 13.454 0 0 0 0 13.454v81.107a13.455 13.455 0 0 0 13.454 13.435h55.303a13.454 13.454 0 0 0 13.455-13.435V24.624h-12.38Z" opacity="0.3"></path><path fill="#FF3E4C" d="M82.212 24.624h-12.38a10.32 10.32 0 0 1-10.31-10.31V0l22.69 24.624ZM65.297 75.417H13.68a1.875 1.875 0 0 1-1.875-1.875 1.875 1.875 0 0 1 1.875-1.87h51.617a1.87 1.87 0 0 1 1.73 2.587 1.87 1.87 0 0 1-1.73 1.158Zm0-21.117H13.68a1.875 1.875 0 0 1-1.875-1.87 1.875 1.875 0 0 1 1.875-1.875h51.617a1.87 1.87 0 0 1 1.87 1.875 1.87 1.87 0 0 1-1.87 1.87Zm0 10.558H13.68a1.875 1.875 0 0 1-1.875-1.875 1.875 1.875 0 0 1 1.875-1.87h51.617a1.87 1.87 0 0 1 1.73 2.587 1.87 1.87 0 0 1-1.73 1.158ZM44.938 43.737H13.68a1.875 1.875 0 0 1-1.875-1.87 1.875 1.875 0 0 1 1.875-1.875h31.258a1.875 1.875 0 0 1 1.87 1.875 1.875 1.875 0 0 1-1.87 1.87Zm0-10.559H13.68a1.875 1.875 0 0 1-1.875-1.87 1.875 1.875 0 0 1 1.875-1.874h31.258a1.875 1.875 0 0 1 1.87 1.874 1.875 1.875 0 0 1-1.87 1.87ZM91.966 85.3H29.449a8.03 8.03 0 0 0-8.03 8.03v18.64a8.03 8.03 0 0 0 8.03 8.029h62.517a8.03 8.03 0 0 0 8.03-8.029V93.33a8.03 8.03 0 0 0-8.03-8.03Z"></path><path fill="#fff" d="M41.928 106.684v3.635h-3.76v-3.635h3.76Zm5.53-1.639v5.275h-3.635V95.285h5.89c1.785 0 3.15.445 4.085 1.33a4.699 4.699 0 0 1 1.4 3.585 4.905 4.905 0 0 1-.625 2.5 4.382 4.382 0 0 1-1.86 1.725 6.543 6.543 0 0 1-3 .625l-2.255-.005Zm4.04-4.845c0-1.333-.73-2-2.19-2h-1.85v3.915h1.85c1.46.013 2.19-.625 2.19-1.915Zm18.064 6.498a6.635 6.635 0 0 1-2.72 2.67 8.717 8.717 0 0 1-4.18.955h-5.675V95.288h5.675a8.86 8.86 0 0 1 4.19.935 6.5 6.5 0 0 1 2.71 2.64 7.86 7.86 0 0 1 .95 3.91 7.937 7.937 0 0 1-.95 3.925Zm-3.91-.755a4.182 4.182 0 0 0 1.18-3.17 4.167 4.167 0 0 0-1.18-3.165 4.585 4.585 0 0 0-3.305-1.13h-1.725v8.59h1.725a4.608 4.608 0 0 0 3.305-1.125Zm16.725-10.658v2.895h-6.165v3.295h4.76v2.765h-4.76v6.08h-3.64V95.285h9.805Z"></path><path fill="#FF3E4C" d="M64.383 29.434h-8.84a2.79 2.79 0 0 0-2.79 2.79v8.84a2.79 2.79 0 0 0 2.79 2.79h8.84a2.79 2.79 0 0 0 2.79-2.79v-8.84a2.79 2.79 0 0 0-2.79-2.79Z"></path></svg>
                                    @elseif (in_array($fileEntry->extension, ['xls', 'xlsx']))
                                        {{-- Excel xlsx --}}
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 120" fill="none" class="w-auto h-full shrink-0"><path fill="#00C650" d="M69.832 24.624a10.32 10.32 0 0 1-10.31-10.31V0H13.456A13.455 13.455 0 0 0 0 13.454v81.107a13.455 13.455 0 0 0 13.454 13.455h55.298a13.456 13.456 0 0 0 13.455-13.455V24.624H69.832Z" opacity="0.3"></path><path fill="#00C650" d="M82.207 24.624H69.832a10.32 10.32 0 0 1-10.31-10.31V0l22.685 24.624ZM91.966 85.3H29.45a8.03 8.03 0 0 0-8.03 8.03v18.64a8.03 8.03 0 0 0 8.03 8.029h62.517a8.03 8.03 0 0 0 8.03-8.029V93.33a8.03 8.03 0 0 0-8.03-8.03Z"></path><path fill="#fff" d="M43.713 106.684v3.635h-3.765v-3.635h3.765Zm11.015 3.636-3.165-4.68-2.724 4.68h-4.166l4.805-7.74-4.955-7.295h4.316l3.084 4.53 2.66-4.53h4.145l-4.74 7.57 5.06 7.465h-4.32Zm15.919-2.041a4.28 4.28 0 0 1-1.785 1.595 6.316 6.316 0 0 1-2.86.595 6.71 6.71 0 0 1-4.17-1.235 4.471 4.471 0 0 1-1.785-3.445h3.87c.028.507.244.985.605 1.34.36.336.838.516 1.33.5a1.522 1.522 0 0 0 1.105-.385 1.332 1.332 0 0 0 .405-1 1.313 1.313 0 0 0-.375-.955 2.904 2.904 0 0 0-.925-.63 22.16 22.16 0 0 0-1.53-.585 18.643 18.643 0 0 1-2.33-.945 4.25 4.25 0 0 1-1.55-1.36 3.914 3.914 0 0 1-.65-2.35 4.001 4.001 0 0 1 .68-2.32 4.31 4.31 0 0 1 1.885-1.5 6.82 6.82 0 0 1 2.755-.5 6.165 6.165 0 0 1 4 1.195 4.546 4.546 0 0 1 1.67 3.275h-3.935a2 2 0 0 0-.54-1.18 1.592 1.592 0 0 0-1.18-.44 1.535 1.535 0 0 0-1.035.34 1.26 1.26 0 0 0-.39 1 1.27 1.27 0 0 0 .35.905c.25.26.549.466.88.605.355.155.865.355 1.53.595.812.265 1.602.589 2.365.97a4.599 4.599 0 0 1 1.57 1.39 4 4 0 0 1 .66 2.385 4.13 4.13 0 0 1-.62 2.14Zm6.265-.744h4.87v2.785h-8.5V95.285h3.64l-.01 12.25Z"></path><path fill="#00C650" d="M59.443 75.419H22.784c-3.175 0-5.76-3.16-5.76-7.05V41.775c0-3.885 2.585-7.044 5.76-7.044h36.674c3.17 0 5.755 3.16 5.755 7.044V68.37c-.015 3.89-2.6 7.05-5.77 7.05ZM22.784 38.33c-1.555 0-2.815 1.545-2.815 3.445V68.37c0 1.9 1.26 3.445 2.815 3.445h36.674c1.55 0 2.81-1.545 2.81-3.445V41.775c0-1.9-1.26-3.445-2.81-3.445H22.784Z"></path><path fill="#00C650" d="M63.727 59.402H18.484v3.605h45.243v-3.605Zm0-12.359H18.484v3.605h45.243v-3.605Z"></path><path fill="#00C650" d="M52.138 36.527h-3.605v37.084h3.605V36.527Zm-18.465 0h-3.605v37.084h3.605V36.527Z"></path></svg>
                                    @elseif (in_array($fileEntry->extension, ['ppt', 'pptx']))
                                        {{-- PowerPoint ppt --}}
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 120" fill="none" class="w-auto h-full shrink-0"><path fill="#05B3FE" d="M69.832 24.624a10.325 10.325 0 0 1-10.315-10.31V0H13.454A13.455 13.455 0 0 0 0 13.454v81.107a13.455 13.455 0 0 0 13.454 13.455h55.298a13.455 13.455 0 0 0 13.455-13.455V24.624H69.832Z" opacity="0.2"></path><path fill="#05B3FE" d="M38.387 45.07a2.037 2.037 0 1 0 0-4.074 2.037 2.037 0 0 0 0 4.073Zm-10.295 0a2.037 2.037 0 1 0 0-4.074 2.037 2.037 0 0 0 0 4.073Zm-1.246 22.568c0 .052.043.103.103.103h2.286a.105.105 0 0 0 .103-.103v-3.583h-2.492v3.583Zm0-8.122h2.492v3.695h-2.492v-3.695Zm0-4.528h2.492v3.696h-2.492v-3.696Zm2.389-4.538h-2.286a.105.105 0 0 0-.103.102v3.584h2.492v-3.584c0-.043-.043-.094-.103-.103Zm10.295 0h-2.285a.105.105 0 0 0-.103.102v3.584h2.492v-3.584c0-.043-.043-.094-.103-.103Zm-2.388 4.538h2.492v3.696h-2.492v-3.696Zm0 4.528h2.492v3.695h-2.492v-3.695Zm0 8.122c0 .052.043.103.103.103h2.286a.105.105 0 0 0 .103-.103v-3.583h-2.492v3.583Zm17.006-4.314-2.458.421.602 3.558c.009.035.034.06.043.069.017.008.043.026.077.017l2.252-.387a.157.157 0 0 0 .069-.043c.008-.017.025-.043.017-.077l-.602-3.558Zm-.764-4.469-2.458.413.618 3.644 2.458-.413-.618-3.644Zm-3.22-4.039 2.457-.416.617 3.644-2.457.416-.617-3.644Zm1.724-4.785c-.009-.06-.06-.103-.12-.086l-2.252.387c-.043.008-.06.034-.069.043-.008.017-.026.043-.017.077l.602 3.532 2.458-.42-.602-3.533Zm-.481-7.565a2.043 2.043 0 0 0-2.011-1.685 2.4 2.4 0 0 0-.344.026 2.024 2.024 0 0 0-1.659 2.346c.095.533.387 1.006.826 1.315.447.318.98.438 1.52.344a2.04 2.04 0 0 0 1.668-2.346Z"></path><path fill="#05B3FE" d="m58.617 68.946-5.345-31.47a1.126 1.126 0 0 0-1.307-.92l-7.081 1.203a1.139 1.139 0 0 0-.937.997v.25c0 .017.009.042.009.06l5.354 31.453c.103.61.687 1.031 1.306.928l7.081-1.203a1.14 1.14 0 0 0 .413-.155 1.126 1.126 0 0 0 .507-1.143ZM46.568 43.302a2.879 2.879 0 0 1 2.355-3.317 2.88 2.88 0 0 1 3.317 2.355 2.88 2.88 0 0 1-2.355 3.317c-.171.017-.335.026-.49.034a2.865 2.865 0 0 1-2.827-2.389Zm8.242 24.536-2.252.386c-.051 0-.111.009-.163.009a.962.962 0 0 1-.542-.172.937.937 0 0 1-.386-.61l-.68-3.98-.755-4.45v-.026l-.757-4.46-.67-3.937a.936.936 0 0 1 .163-.704.937.937 0 0 1 .61-.387l2.252-.387a.955.955 0 0 1 1.091.774l.67 3.944.757 4.469.756 4.469.68 3.97a.955.955 0 0 1-.774 1.092ZM41.971 37.293h-7.184a1.13 1.13 0 0 0-1.126 1.023v32.106a1.131 1.131 0 0 0 1.126 1.031h7.184c.628 0 1.135-.507 1.135-1.134V38.428c0-.628-.507-1.135-1.135-1.135Zm-1.504 21.802c0 .035 0 .06-.008.086v4.366c.008.026.008.06.008.086 0 .026 0 .06-.008.086v3.919a.93.93 0 0 1-.937.945h-2.286a.939.939 0 0 1-.937-.937V50.553a.94.94 0 0 1 .937-.937h2.286a.94.94 0 0 1 .937.937v3.919c.008.026.008.06.008.086 0 .034 0 .06-.008.086v4.365a.264.264 0 0 1 .008.086Zm-2.08-13.191a2.875 2.875 0 0 1-2.87-2.87 2.875 2.875 0 0 1 2.87-2.87 2.875 2.875 0 0 1 2.871 2.87c0 1.58-1.29 2.87-2.87 2.87Zm-5.577-7.596a1.124 1.124 0 0 0-1.126-1.023H24.5v.009c-.628 0-1.135.507-1.135 1.134V70.32c0 .627.507 1.134 1.135 1.134h7.184c.593 0 1.074-.456 1.126-1.031v-.112c0-.026 0-.06.008-.086v-31.72c-.008-.025-.008-.051-.008-.086v-.111Zm-2.63 20.797c0 .034 0 .06-.008.086v4.365c.008.026.008.06.008.086 0 .017 0 .043-.008.069v3.927a.94.94 0 0 1-.937.946h-2.286a.939.939 0 0 1-.937-.937V50.554c0-.516.421-.937.937-.937h2.286a.94.94 0 0 1 .937.937v3.919a.31.31 0 0 1 .008.086c0 .025 0 .051-.008.077v4.383a.265.265 0 0 1 .008.086Zm-2.088-13.2a2.875 2.875 0 0 1-2.87-2.87 2.875 2.875 0 0 1 2.87-2.87 2.875 2.875 0 0 1 2.87 2.87c0 1.58-1.289 2.87-2.87 2.87Zm54.115-21.281H69.832a10.325 10.325 0 0 1-10.314-10.31V0l22.689 24.624ZM91.966 85.3H29.45a8.03 8.03 0 0 0-8.03 8.03v18.64a8.03 8.03 0 0 0 8.03 8.029h62.517a8.03 8.03 0 0 0 8.03-8.029V93.33a8.03 8.03 0 0 0-8.03-8.03Z"></path><path fill="#fff" d="M40 95h10.422v2.704h-7.189v3.634h6.807V104h-6.807v6H40V95Zm12.837 0h3.233v15h-3.233V95Zm6.377 15V95h3.233v12.296h6.955V110H59.214Zm12.608-15H82.33v2.704h-7.275v3.444h7.02v2.662h-7.02v3.486H82.5V110H71.822V95Z"></path></svg>
                                    @elseif ($fileEntry->extension == 'zip')
                                        {{-- ZIP SVG --}}
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 80" fill="none" class="w-14 h-auto"><path fill="#FFA000" d="M64.65 12H15.19c-4.007-.007-7.26 3.195-7.267 7.15 0 .322.02.644.064.963a1.451 1.451 0 0 0 1.63 1.24c.194-.025.38-.088.55-.186a4.161 4.161 0 0 1 2.114-.552H22.64c1.869.005 3.53 1.179 4.137 2.924l.247.786c1 2.925 3.776 4.897 6.904 4.905H67.56a4.407 4.407 0 0 1 2.173.575c.224.128.478.195.736.195.803 0 1.455-.643 1.455-1.436V19.18c0-3.965-3.257-7.179-7.274-7.179Z"></path><path fill="#FFC107" d="M71.363 27.622a7.316 7.316 0 0 0-3.655-.975H33.992a4.395 4.395 0 0 1-4.148-2.934l-.248-.79C28.593 19.988 25.81 18.01 22.675 18H12.292a7.043 7.043 0 0 0-3.57.931A7.175 7.175 0 0 0 5 25.206v34.588C5 63.774 8.265 67 12.292 67h55.416C71.735 67 75 63.774 75 59.794v-25.94a7.141 7.141 0 0 0-3.637-6.232Z"></path></svg>
                                    @elseif ($fileEntry->extension == 'json' || $fileEntry->extension == 'geojson')
                                        {{-- JSON SVG --}}
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 120" fill="none" class="w-auto h-full shrink-0"><path fill="#9679A6" d="M69.832 24.624a10.325 10.325 0 0 1-10.31-10.315V0H13.455A13.454 13.454 0 0 0 0 13.454v81.107a13.455 13.455 0 0 0 13.454 13.434h55.303a13.453 13.453 0 0 0 13.455-13.434V24.624h-12.38Z" opacity="0.3"></path><path fill="#9679A6" d="M29.871 61.18v1.832h-2.894a3.438 3.438 0 0 1-3.582-3.56v-7.768a1.957 1.957 0 0 0-2.083-2.083H21v-2.478h.312a1.958 1.958 0 0 0 2.083-2.082v-7.372a3.436 3.436 0 0 1 3.582-3.666h2.894v1.875h-1.853a2.081 2.081 0 0 0-2.27 2.332v7.039a2.916 2.916 0 0 1-2.728 3.082 2.872 2.872 0 0 1 2.728 3.04v7.455a2.207 2.207 0 0 0 2.27 2.354h1.853Zm2.645-5.144a1.582 1.582 0 0 1 1.645-1.625 1.603 1.603 0 0 1 1.666 1.625 1.581 1.581 0 0 1-1.666 1.645 1.563 1.563 0 0 1-1.645-1.645Zm6.539 0A1.646 1.646 0 1 1 40.7 57.68a1.56 1.56 0 0 1-1.645-1.645Zm6.519-.001a1.666 1.666 0 1 1 1.666 1.645 1.582 1.582 0 0 1-1.666-1.645Zm5.976 6.977V61.18h1.832a2.207 2.207 0 0 0 2.291-2.354v-7.392a2.832 2.832 0 0 1 2.728-3.04 2.875 2.875 0 0 1-2.728-3.083V38.21a2.083 2.083 0 0 0-2.29-2.332H51.55v-1.875h2.874a3.438 3.438 0 0 1 3.581 3.666v7.372a1.936 1.936 0 0 0 2.083 2.082h.312v2.478h-.312a1.937 1.937 0 0 0-2.083 2.083v7.767a3.438 3.438 0 0 1-3.581 3.645l-2.874-.084Zm30.662-38.388h-12.38a10.324 10.324 0 0 1-10.31-10.315V0l22.69 24.624ZM91.966 85.3H29.449a8.03 8.03 0 0 0-8.03 8.03v18.64a8.03 8.03 0 0 0 8.03 8.029h62.517a8.03 8.03 0 0 0 8.03-8.029V93.33a8.03 8.03 0 0 0-8.03-8.03Z"></path><path fill="#fff" d="M39.62 95.199h3.04v10.142c0 .937-.211 1.752-.633 2.443a4.179 4.179 0 0 1-1.74 1.598c-.743.374-1.608.561-2.592.561-.876 0-1.672-.154-2.387-.461a3.815 3.815 0 0 1-1.69-1.421c-.417-.639-.623-1.442-.618-2.408h3.061c.01.384.088.713.234.988.152.27.358.478.618.625.266.142.578.213.938.213.379 0 .698-.081.959-.242.265-.166.466-.407.603-.724.138-.317.206-.708.206-1.172V95.199Zm13.472 4.183c-.056-.573-.3-1.018-.731-1.335-.431-.317-1.016-.476-1.754-.476-.502 0-.926.071-1.272.213-.345.137-.61.33-.795.575-.18.247-.27.526-.27.838-.01.26.045.488.163.682.123.194.291.362.505.505.213.137.459.258.738.362.28.099.578.184.895.255l1.307.313a9.196 9.196 0 0 1 1.747.568c.53.237.99.528 1.378.874.388.345.689.753.902 1.221.218.469.329 1.006.334 1.612-.005.891-.232 1.662-.682 2.316-.445.649-1.09 1.153-1.932 1.513-.838.355-1.849.532-3.033.532-1.174 0-2.197-.18-3.068-.539-.866-.36-1.543-.893-2.031-1.599-.483-.71-.736-1.588-.76-2.634h2.976c.033.487.173.894.419 1.221.25.322.585.566 1.001.732.422.161.898.241 1.428.241.52 0 .973-.076 1.356-.227.389-.152.69-.362.902-.632.214-.27.32-.58.32-.931 0-.326-.097-.601-.291-.823-.19-.223-.469-.412-.838-.569a8.568 8.568 0 0 0-1.343-.426l-1.583-.398c-1.227-.298-2.195-.764-2.905-1.399-.71-.634-1.063-1.489-1.058-2.564-.005-.88.23-1.65.703-2.308.478-.658 1.134-1.172 1.967-1.541.833-.37 1.78-.554 2.84-.554 1.08 0 2.023.185 2.828.554.81.37 1.439.883 1.889 1.541.45.658.682 1.42.696 2.287h-2.948Zm18.582 3.09c0 1.586-.3 2.935-.902 4.048-.597 1.113-1.411 1.962-2.443 2.55-1.028.582-2.183.873-3.466.873-1.293 0-2.453-.293-3.48-.88-1.028-.588-1.84-1.438-2.437-2.55-.596-1.113-.894-2.46-.894-4.041 0-1.587.298-2.936.895-4.049.596-1.112 1.408-1.96 2.435-2.542 1.028-.587 2.188-.881 3.48-.881 1.284 0 2.44.294 3.467.88 1.032.583 1.846 1.43 2.443 2.543.601 1.113.902 2.462.902 4.049Zm-3.118 0c0-1.028-.154-1.894-.462-2.6-.303-.705-.731-1.24-1.285-1.605-.554-.364-1.203-.547-1.946-.547-.744 0-1.392.183-1.946.547-.554.365-.985.9-1.293 1.605-.303.706-.455 1.572-.455 2.6 0 1.027.152 1.894.455 2.599.308.706.739 1.241 1.293 1.605.554.365 1.202.547 1.946.547.743 0 1.392-.182 1.946-.547.554-.364.982-.899 1.285-1.605.308-.705.462-1.572.462-2.599Zm17.562-7.273v14.545h-2.656l-6.328-9.155h-.107v9.155h-3.075V95.199h2.699l6.278 9.148h.128v-9.148h3.061Z"></path></svg>
                                    @else
                                        {{-- Default SVG --}}
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 120" fill="none" class="w-auto h-full shrink-0"><path fill="#05B3FE" d="M69.832 24.624a10.325 10.325 0 0 1-10.315-10.31V0H13.454A13.455 13.455 0 0 0 0 13.454v81.107a13.455 13.455 0 0 0 13.454 13.455h55.298a13.455 13.455 0 0 0 13.455-13.455V24.624H69.832Z" opacity="0.2"></path><path fill="#05B3FE" d="M38.387 45.07a2.037 2.037 0 1 0 0-4.074 2.037 2.037 0 0 0 0 4.073Zm-10.295 0a2.037 2.037 0 1 0 0-4.074 2.037 2.037 0 0 0 0 4.073Zm-1.246 22.568c0 .052.043.103.103.103h2.286a.105.105 0 0 0 .103-.103v-3.583h-2.492v3.583Zm0-8.122h2.492v3.695h-2.492v-3.695Zm0-4.528h2.492v3.696h-2.492v-3.696Zm2.389-4.538h-2.286a.105.105 0 0 0-.103.102v3.584h2.492v-3.584c0-.043-.043-.094-.103-.103Zm10.295 0h-2.285a.105.105 0 0 0-.103.102v3.584h2.492v-3.584c0-.043-.043-.094-.103-.103Zm-2.388 4.538h2.492v3.696h-2.492v-3.696Zm0 4.528h2.492v3.695h-2.492v-3.695Zm0 8.122c0 .052.043.103.103.103h2.286a.105.105 0 0 0 .103-.103v-3.583h-2.492v3.583Zm17.006-4.314-2.458.421.602 3.558c.009.035.034.06.043.069.017.008.043.026.077.017l2.252-.387a.157.157 0 0 0 .069-.043c.008-.017.025-.043.017-.077l-.602-3.558Zm-.764-4.469-2.458.413.618 3.644 2.458-.413-.618-3.644Zm-3.22-4.039 2.457-.416.617 3.644-2.457.416-.617-3.644Zm1.724-4.785c-.009-.06-.06-.103-.12-.086l-2.252.387c-.043.008-.06.034-.069.043-.008.017-.026.043-.017.077l.602 3.532 2.458-.42-.602-3.533Zm-.481-7.565a2.043 2.043 0 0 0-2.011-1.685 2.4 2.4 0 0 0-.344.026 2.024 2.024 0 0 0-1.659 2.346c.095.533.387 1.006.826 1.315.447.318.98.438 1.52.344a2.04 2.04 0 0 0 1.668-2.346Z"></path><path fill="#05B3FE" d="m58.617 68.946-5.345-31.47a1.126 1.126 0 0 0-1.307-.92l-7.081 1.203a1.139 1.139 0 0 0-.937.997v.25c0 .017.009.042.009.06l5.354 31.453c.103.61.687 1.031 1.306.928l7.081-1.203a1.14 1.14 0 0 0 .413-.155 1.126 1.126 0 0 0 .507-1.143ZM46.568 43.302a2.879 2.879 0 0 1 2.355-3.317 2.88 2.88 0 0 1 3.317 2.355 2.88 2.88 0 0 1-2.355 3.317c-.171.017-.335.026-.49.034a2.865 2.865 0 0 1-2.827-2.389Zm8.242 24.536-2.252.386c-.051 0-.111.009-.163.009a.962.962 0 0 1-.542-.172.937.937 0 0 1-.386-.61l-.68-3.98-.755-4.45v-.026l-.757-4.46-.67-3.937a.936.936 0 0 1 .163-.704.937.937 0 0 1 .61-.387l2.252-.387a.955.955 0 0 1 1.091.774l.67 3.944.757 4.469.756 4.469.68 3.97a.955.955 0 0 1-.774 1.092ZM41.971 37.293h-7.184a1.13 1.13 0 0 0-1.126 1.023v32.106a1.131 1.131 0 0 0 1.126 1.031h7.184c.628 0 1.135-.507 1.135-1.134V38.428c0-.628-.507-1.135-1.135-1.135Zm-1.504 21.802c0 .035 0 .06-.008.086v4.366c.008.026.008.06.008.086 0 .026 0 .06-.008.086v3.919a.93.93 0 0 1-.937.945h-2.286a.939.939 0 0 1-.937-.937V50.553a.94.94 0 0 1 .937-.937h2.286a.94.94 0 0 1 .937.937v3.919c.008.026.008.06.008.086 0 .034 0 .06-.008.086v4.365a.264.264 0 0 1 .008.086Zm-2.08-13.191a2.875 2.875 0 0 1-2.87-2.87 2.875 2.875 0 0 1 2.87-2.87 2.875 2.875 0 0 1 2.871 2.87c0 1.58-1.29 2.87-2.87 2.87Zm-5.577-7.596a1.124 1.124 0 0 0-1.126-1.023H24.5v.009c-.628 0-1.135.507-1.135 1.134V70.32c0 .627.507 1.134 1.135 1.134h7.184c.593 0 1.074-.456 1.126-1.031v-.112c0-.026 0-.06.008-.086v-31.72c-.008-.025-.008-.051-.008-.086v-.111Zm-2.63 20.797c0 .034 0 .06-.008.086v4.365c.008.026.008.06.008.086 0 .017 0 .043-.008.069v3.927a.94.94 0 0 1-.937.946h-2.286a.939.939 0 0 1-.937-.937V50.554c0-.516.421-.937.937-.937h2.286a.94.94 0 0 1 .937.937v3.919a.31.31 0 0 1 .008.086c0 .025 0 .051-.008.077v4.383a.265.265 0 0 1 .008.086Zm-2.088-13.2a2.875 2.875 0 0 1-2.87-2.87 2.875 2.875 0 0 1 2.87-2.87 2.875 2.875 0 0 1 2.87 2.87c0 1.58-1.289 2.87-2.87 2.87Zm54.115-21.281H69.832a10.325 10.325 0 0 1-10.314-10.31V0l22.689 24.624ZM91.966 85.3H29.45a8.03 8.03 0 0 0-8.03 8.03v18.64a8.03 8.03 0 0 0 8.03 8.029h62.517a8.03 8.03 0 0 0 8.03-8.029V93.33a8.03 8.03 0 0 0-8.03-8.03Z"></path><path fill="#fff" d="M40 95h10.422v2.704h-7.189v3.634h6.807V104h-6.807v6H40V95Zm12.837 0h3.233v15h-3.233V95Zm6.377 15V95h3.233v12.296h6.955V110H59.214Zm12.608-15H82.33v2.704h-7.275v3.444h7.02v2.662h-7.02v3.486H82.5V110H71.822V95Z"></path></svg>
                                    @endif
                                    </a>
                                    <!-- <a href="{{ route('user.files.edit', $fileEntry->shared_id) }}" class="filemanager-file-title filemanager-link">
                                        {{ $fileEntry->name }}
                                    </a> -->
                                    <a href="{{ route('file.preview', $fileEntry->shared_id) }}" class="filemanager-file-title filemanager-link" target="_blank">
                                        {{ $fileEntry->name }}
                                    </a>
                                    @if($fileEntry->uploaded_by && $fileEntry->uploader)
                                        <div class="small text-muted mt-1">
                                            {{ __('Uploaded by') }}:
                                            @php
                                            $u = $fileEntry->uploader;
                                            $uName = $u->name ?? trim(($u->firstname ?? '').' '.($u->lastname ?? '')) ?: $u->email;
                                            @endphp
                                            {{ auth()->id() === $fileEntry->uploaded_by ? __('You') : $uName }}
                                            {{-- @if($fileEntry->uploaded_via_share_id)
                                            <span class="text-muted">· {{ __('via share') }} #{{ $fileEntry->uploaded_via_share_id }}</span>
                                            @endif --}}
                                        </div>
                                    @endif


                                <div class="filemanager-file-info">
                                    {{-- <small class="text-muted">{{ formatBytes($fileEntry->size) }} • {{ vDate($fileEntry->created_at) }}</small> --}}
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- List View -->
            <div id="listView" class="d-none">
                <div class="list-group">
                    @foreach ($fileEntries as $fileEntry)
                        <div class="list-group-item filemanager-file-list file-item" 
                            data-file-id="{{ $fileEntry->shared_id }}"
                            data-file-name="{{ $fileEntry->name }}"
                            data-file-type="{{ $fileEntry->type }}"
                            data-file-size="{{ $fileEntry->size }}"
                            data-file-date="{{ $fileEntry->created_at }}"
                            data-access-status="{{ $fileEntry->access_status ?? 1 }}"
                            data-preview-support="{{ isFileSupportPreview($fileEntry->type) ? '1' : '0' }}">
                            
                            <div class="d-flex align-items-center">
                                <div class="form-check me-3">
                                    <input type="checkbox" 
                                        class="form-check-input file-checkbox" 
                                        data-file-id="{{ $fileEntry->shared_id }}"
                                        value="{{ $fileEntry->shared_id }}" 
                                        id="list_{{ $fileEntry->shared_id }}" />
                                </div>
                                
                                <div class="filemanager-file-icon-small me-3">
                                    <a href="{{ route('file.preview', $fileEntry->shared_id) }}" target="_blank">
                                    @if($fileEntry->type === 'folder')
                                        <i class="fas fa-folder" style="font-size: 24px; color: #ffc107;"></i>
                                    @elseif ($fileEntry->type == 'image')
                                        {{-- Image SVG --}}
                                        {{-- You can use a specific SVG for image files --}}
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 120" fill="none" width="32" class="w-auto h-full shrink-0"><path fill="#00AEC6" d="M69.835 24.62a10.315 10.315 0 0 1-10.31-10.31V0h-46.07A13.455 13.455 0 0 0 0 13.46v81.105a13.455 13.455 0 0 0 13.455 13.46h55.3a13.446 13.446 0 0 0 9.516-3.943 13.45 13.45 0 0 0 3.94-9.517v-69.94l-12.376-.005Z" opacity="0.3"></path><path fill="#00AEC6" d="M82.21 24.62H69.835a10.315 10.315 0 0 1-10.31-10.31V0L82.21 24.62Zm9.76 60.68H29.45a8.03 8.03 0 0 0-8.03 8.03v18.641a8.03 8.03 0 0 0 8.03 8.03h62.52a8.03 8.03 0 0 0 8.03-8.03v-18.64a8.03 8.03 0 0 0-8.03-8.03Z"></path><path fill="#fff" d="M32.352 95.453v14.545h-3.076V95.453h3.076Zm2.53 0h3.792l4.006 9.772h.17l4.006-9.772h3.793v14.545h-2.983v-9.467h-.12l-3.765 9.396H41.75l-3.764-9.432h-.121v9.503h-2.983V95.453Zm20.808 14.545h-3.296l5.022-14.545h3.963l5.014 14.545h-3.296l-3.643-11.221h-.114l-3.65 11.221Zm-.206-5.717h7.784v2.4h-7.784v-2.4Zm21.621-4.127a3.19 3.19 0 0 0-.42-.916c-.18-.27-.4-.497-.66-.681a2.834 2.834 0 0 0-.88-.434 3.71 3.71 0 0 0-1.087-.149c-.743 0-1.397.185-1.96.554-.559.37-.995.907-1.307 1.612-.313.701-.469 1.558-.469 2.571 0 1.014.154 1.875.462 2.586.308.71.743 1.252 1.307 1.626.563.369 1.228.554 1.995.554.696 0 1.29-.123 1.783-.369a2.647 2.647 0 0 0 1.136-1.059c.266-.454.398-.992.398-1.612l.625.092h-3.75v-2.315h6.087v1.833c0 1.278-.27 2.376-.81 3.295a5.514 5.514 0 0 1-2.23 2.116c-.947.493-2.031.739-3.253.739-1.364 0-2.561-.301-3.594-.902-1.032-.606-1.837-1.465-2.414-2.578-.573-1.117-.86-2.443-.86-3.977 0-1.179.17-2.23.512-3.154.345-.928.828-1.714 1.449-2.358a6.26 6.26 0 0 1 2.166-1.47 7.019 7.019 0 0 1 2.677-.504c.824 0 1.591.12 2.301.362.71.237 1.34.573 1.89 1.009a5.46 5.46 0 0 1 1.356 1.555c.35.597.575 1.255.675 1.974h-3.125Zm5.57 9.844V95.453h9.8v2.535H85.75v3.466h6.222v2.536H85.75v3.473h6.754v2.535h-9.83Z"></path><path fill="#00AEC6" d="M52.15 73.204H30.065a8.165 8.165 0 0 1-8.155-8.155V42.964a8.165 8.165 0 0 1 8.155-8.155H52.15a8.16 8.16 0 0 1 8.15 8.155v22.085a8.16 8.16 0 0 1-8.15 8.155Zm-22.085-34.79a4.555 4.555 0 0 0-4.55 4.55v22.085a4.555 4.555 0 0 0 4.55 4.55H52.15a4.55 4.55 0 0 0 4.545-4.55V42.964a4.55 4.55 0 0 0-4.545-4.55H30.065Z"></path><path fill="#00AEC6" d="M58.5 60.85v4.2a6.355 6.355 0 0 1-6.35 6.35H30.065a6.35 6.35 0 0 1-6.35-6.35V58c3.535-.76 8.92-1 15.455 1.61l4.05-3.86 2.76 7s.74-2.575 3.13-2.21c2.39.365 6.26 1.66 7.915.555a2.13 2.13 0 0 1 1.475-.245Zm-7.734-13.374a2.455 2.455 0 1 0 0-4.91 2.455 2.455 0 0 0 0 4.91Z"></path></svg>
                                    @elseif ($fileEntry->extension == 'mp4' || $fileEntry->extension == 'avi' || $fileEntry->extension == 'mov')
                                        {{-- Video SVG --}}
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 120" fill="none" width="32" class="w-auto h-full shrink-0"><path fill="#A140FF" d="M69.835 24.62a10.32 10.32 0 0 1-10.31-10.31V0h-46.07A13.455 13.455 0 0 0 0 13.45v81.11A13.454 13.454 0 0 0 13.455 108h55.3a13.446 13.446 0 0 0 9.517-3.939 13.46 13.46 0 0 0 3.943-9.516V24.62h-12.38Z" opacity="0.3"></path><path fill="#A140FF" d="M82.215 24.62h-12.38a10.32 10.32 0 0 1-10.31-10.31V0l22.69 24.62ZM91.97 85.3H29.45a8.03 8.03 0 0 0-8.03 8.03v18.641a8.03 8.03 0 0 0 8.03 8.03h62.52a8.03 8.03 0 0 0 8.03-8.03v-18.64a8.03 8.03 0 0 0-8.03-8.03Z"></path><path fill="#fff" d="m32.904 95.453 3.516 11.051h.135l3.523-11.051h3.409l-5.014 14.545H34.51l-5.022-14.545h3.416Zm15.409 0v14.545h-3.075V95.453h3.075ZM56 109.998h-5.157V95.453h5.199c1.463 0 2.722.291 3.778.873a5.894 5.894 0 0 1 2.436 2.493c.573 1.084.86 2.382.86 3.892 0 1.515-.287 2.818-.86 3.907a5.909 5.909 0 0 1-2.45 2.507c-1.06.582-2.33.873-3.807.873Zm-2.082-2.635h1.953c.91 0 1.674-.161 2.294-.483.626-.326 1.094-.831 1.407-1.512.317-.687.476-1.572.476-2.657 0-1.075-.159-1.953-.476-2.635-.313-.681-.78-1.183-1.4-1.505-.62-.322-1.384-.483-2.293-.483h-1.96v9.275Zm11.476 2.635V95.453h9.801v2.535h-6.726v3.466h6.222v2.536h-6.222v3.473h6.754v2.535h-9.83Zm25.612-7.273c0 1.587-.3 2.936-.902 4.049-.596 1.112-1.41 1.962-2.443 2.55-1.027.582-2.183.873-3.466.873-1.292 0-2.452-.293-3.48-.881-1.027-.587-1.84-1.437-2.436-2.549-.596-1.113-.895-2.46-.895-4.042 0-1.586.299-2.935.895-4.048.597-1.113 1.409-1.96 2.436-2.542 1.028-.588 2.188-.881 3.48-.881 1.284 0 2.439.293 3.466.88 1.032.583 1.847 1.43 2.443 2.543.602 1.113.902 2.462.902 4.048Zm-3.118 0c0-1.027-.153-1.893-.461-2.599-.303-.705-.732-1.24-1.286-1.605s-1.202-.547-1.946-.547c-.743 0-1.392.182-1.946.547-.554.364-.985.9-1.292 1.605-.303.706-.455 1.572-.455 2.599 0 1.028.152 1.894.455 2.6.307.705.738 1.24 1.292 1.605s1.203.547 1.946.547c.744 0 1.392-.182 1.946-.547.554-.365.983-.9 1.286-1.605.308-.706.461-1.572.461-2.6Z"></path><path fill="#A140FF" d="M59.72 65.955V42.1a1.912 1.912 0 0 0-.035-.345.576.576 0 0 0-.025-.1 1.747 1.747 0 0 0-.07-.23.878.878 0 0 0-.045-.1 1.39 1.39 0 0 0-.11-.21l-.05-.08a2.043 2.043 0 0 0-.21-.255l-.035-.03a2.416 2.416 0 0 0-.215-.18l-.09-.055-.2-.11-.105-.045a1.917 1.917 0 0 0-.24-.075h-.08a1.618 1.618 0 0 0-.345-.035h-33.68a1.866 1.866 0 0 0-1.695 1.855V66.04c-.012.307.086.608.275.85a1.854 1.854 0 0 0 1.595.91h33.5a1.87 1.87 0 0 0 1.86-1.845ZM24.5 44.74v-3.62h2.945v3.62H24.5Zm4.97 0v-3.62h2.945v3.62H29.47Zm4.97 0v-3.62h2.945v3.62H34.44Zm4.97 0v-3.62h2.945v3.62H39.41Zm4.97 0v-3.62h2.945v3.62H44.38Zm4.97 0v-3.62h2.945v3.62H49.35Zm4.97 0v-3.62h2.945v3.62H54.32Zm-30.5 17.555V45.72h34.615v16.575H23.82Zm.675 4.595v-3.62h2.945v3.62h-2.945Zm4.97 0v-3.62h2.945v3.62h-2.945Zm4.97 0v-3.62h2.945v3.62h-2.945Zm4.97 0v-3.62h2.945v3.62h-2.945Zm4.97 0v-3.62h2.945v3.62h-2.945Zm4.97 0v-3.62h2.945v3.62h-2.945Zm4.97 0v-3.62h2.945v3.62h-2.945Z"></path><path fill="#A140FF" d="m45.11 52.781-4.785-2.765a1.628 1.628 0 0 0-2.44 1.41v5.53a1.63 1.63 0 0 0 2.44 1.41l4.785-2.765a1.63 1.63 0 0 0 0-2.82Z"></path></svg>
                                    @elseif ($fileEntry->extension == 'pdf')
                                        {{-- PDF SVG --}}
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 120" fill="none" width="32" class="w-auto h-full shrink-0"><path fill="#FF3E4C" d="M69.832 24.624a10.32 10.32 0 0 1-10.31-10.31V0H13.455A13.454 13.454 0 0 0 0 13.454v81.107a13.455 13.455 0 0 0 13.454 13.435h55.303a13.454 13.454 0 0 0 13.455-13.435V24.624h-12.38Z" opacity="0.3"></path><path fill="#FF3E4C" d="M82.212 24.624h-12.38a10.32 10.32 0 0 1-10.31-10.31V0l22.69 24.624ZM65.297 75.417H13.68a1.875 1.875 0 0 1-1.875-1.875 1.875 1.875 0 0 1 1.875-1.87h51.617a1.87 1.87 0 0 1 1.73 2.587 1.87 1.87 0 0 1-1.73 1.158Zm0-21.117H13.68a1.875 1.875 0 0 1-1.875-1.87 1.875 1.875 0 0 1 1.875-1.875h51.617a1.87 1.87 0 0 1 1.87 1.875 1.87 1.87 0 0 1-1.87 1.87Zm0 10.558H13.68a1.875 1.875 0 0 1-1.875-1.875 1.875 1.875 0 0 1 1.875-1.87h51.617a1.87 1.87 0 0 1 1.73 2.587 1.87 1.87 0 0 1-1.73 1.158ZM44.938 43.737H13.68a1.875 1.875 0 0 1-1.875-1.87 1.875 1.875 0 0 1 1.875-1.875h31.258a1.875 1.875 0 0 1 1.87 1.875 1.875 1.875 0 0 1-1.87 1.87Zm0-10.559H13.68a1.875 1.875 0 0 1-1.875-1.87 1.875 1.875 0 0 1 1.875-1.874h31.258a1.875 1.875 0 0 1 1.87 1.874 1.875 1.875 0 0 1-1.87 1.87ZM91.966 85.3H29.449a8.03 8.03 0 0 0-8.03 8.03v18.64a8.03 8.03 0 0 0 8.03 8.029h62.517a8.03 8.03 0 0 0 8.03-8.029V93.33a8.03 8.03 0 0 0-8.03-8.03Z"></path><path fill="#fff" d="M41.928 106.684v3.635h-3.76v-3.635h3.76Zm5.53-1.639v5.275h-3.635V95.285h5.89c1.785 0 3.15.445 4.085 1.33a4.699 4.699 0 0 1 1.4 3.585 4.905 4.905 0 0 1-.625 2.5 4.382 4.382 0 0 1-1.86 1.725 6.543 6.543 0 0 1-3 .625l-2.255-.005Zm4.04-4.845c0-1.333-.73-2-2.19-2h-1.85v3.915h1.85c1.46.013 2.19-.625 2.19-1.915Zm18.064 6.498a6.635 6.635 0 0 1-2.72 2.67 8.717 8.717 0 0 1-4.18.955h-5.675V95.288h5.675a8.86 8.86 0 0 1 4.19.935 6.5 6.5 0 0 1 2.71 2.64 7.86 7.86 0 0 1 .95 3.91 7.937 7.937 0 0 1-.95 3.925Zm-3.91-.755a4.182 4.182 0 0 0 1.18-3.17 4.167 4.167 0 0 0-1.18-3.165 4.585 4.585 0 0 0-3.305-1.13h-1.725v8.59h1.725a4.608 4.608 0 0 0 3.305-1.125Zm16.725-10.658v2.895h-6.165v3.295h4.76v2.765h-4.76v6.08h-3.64V95.285h9.805Z"></path><path fill="#FF3E4C" d="M64.383 29.434h-8.84a2.79 2.79 0 0 0-2.79 2.79v8.84a2.79 2.79 0 0 0 2.79 2.79h8.84a2.79 2.79 0 0 0 2.79-2.79v-8.84a2.79 2.79 0 0 0-2.79-2.79Z"></path></svg>
                                    @elseif (in_array($fileEntry->extension, ['doc', 'docx']))
                                        {{-- Word Document SVG --}}
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 120" fill="none" width="32" class="w-auto h-full shrink-0"><path fill="#FF3E4C" d="M69.832 24.624a10.32 10.32 0 0 1-10.31-10.31V0H13.455A13.454 13.454 0 0 0 0 13.454v81.107a13.455 13.455 0 0 0 13.454 13.435h55.303a13.454 13.454 0 0 0 13.455-13.435V24.624h-12.38Z" opacity="0.3"></path><path fill="#FF3E4C" d="M82.212 24.624h-12.38a10.32 10.32 0 0 1-10.31-10.31V0l22.69 24.624ZM65.297 75.417H13.68a1.875 1.875 0 0 1-1.875-1.875 1.875 1.875 0 0 1 1.875-1.87h51.617a1.87 1.87 0 0 1 1.73 2.587 1.87 1.87 0 0 1-1.73 1.158Zm0-21.117H13.68a1.875 1.875 0 0 1-1.875-1.87 1.875 1.875 0 0 1 1.875-1.875h51.617a1.87 1.87 0 0 1 1.87 1.875 1.87 1.87 0 0 1-1.87 1.87Zm0 10.558H13.68a1.875 1.875 0 0 1-1.875-1.875 1.875 1.875 0 0 1 1.875-1.87h51.617a1.87 1.87 0 0 1 1.73 2.587 1.87 1.87 0 0 1-1.73 1.158ZM44.938 43.737H13.68a1.875 1.875 0 0 1-1.875-1.87 1.875 1.875 0 0 1 1.875-1.875h31.258a1.875 1.875 0 0 1 1.87 1.875 1.875 1.875 0 0 1-1.87 1.87Zm0-10.559H13.68a1.875 1.875 0 0 1-1.875-1.87 1.875 1.875 0 0 1 1.875-1.874h31.258a1.875 1.875 0 0 1 1.87 1.874 1.875 1.875 0 0 1-1.87 1.87ZM91.966 85.3H29.449a8.03 8.03 0 0 0-8.03 8.03v18.64a8.03 8.03 0 0 0 8.03 8.029h62.517a8.03 8.03 0 0 0 8.03-8.029V93.33a8.03 8.03 0 0 0-8.03-8.03Z"></path><path fill="#fff" d="M41.928 106.684v3.635h-3.76v-3.635h3.76Zm5.53-1.639v5.275h-3.635V95.285h5.89c1.785 0 3.15.445 4.085 1.33a4.699 4.699 0 0 1 1.4 3.585 4.905 4.905 0 0 1-.625 2.5 4.382 4.382 0 0 1-1.86 1.725 6.543 6.543 0 0 1-3 .625l-2.255-.005Zm4.04-4.845c0-1.333-.73-2-2.19-2h-1.85v3.915h1.85c1.46.013 2.19-.625 2.19-1.915Zm18.064 6.498a6.635 6.635 0 0 1-2.72 2.67 8.717 8.717 0 0 1-4.18.955h-5.675V95.288h5.675a8.86 8.86 0 0 1 4.19.935 6.5 6.5 0 0 1 2.71 2.64 7.86 7.86 0 0 1 .95 3.91 7.937 7.937 0 0 1-.95 3.925Zm-3.91-.755a4.182 4.182 0 0 0 1.18-3.17 4.167 4.167 0 0 0-1.18-3.165 4.585 4.585 0 0 0-3.305-1.13h-1.725v8.59h1.725a4.608 4.608 0 0 0 3.305-1.125Zm16.725-10.658v2.895h-6.165v3.295h4.76v2.765h-4.76v6.08h-3.64V95.285h9.805Z"></path><path fill="#FF3E4C" d="M64.383 29.434h-8.84a2.79 2.79 0 0 0-2.79 2.79v8.84a2.79 2.79 0 0 0 2.79 2.79h8.84a2.79 2.79 0 0 0 2.79-2.79v-8.84a2.79 2.79 0 0 0-2.79-2.79Z"></path></svg>
                                    @elseif (in_array($fileEntry->extension, ['xls', 'xlsx']))
                                        {{-- Excel xlsx --}}
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 120" fill="none" width="32" class="w-auto h-full shrink-0"><path fill="#00C650" d="M69.832 24.624a10.32 10.32 0 0 1-10.31-10.31V0H13.456A13.455 13.455 0 0 0 0 13.454v81.107a13.455 13.455 0 0 0 13.454 13.455h55.298a13.456 13.456 0 0 0 13.455-13.455V24.624H69.832Z" opacity="0.3"></path><path fill="#00C650" d="M82.207 24.624H69.832a10.32 10.32 0 0 1-10.31-10.31V0l22.685 24.624ZM91.966 85.3H29.45a8.03 8.03 0 0 0-8.03 8.03v18.64a8.03 8.03 0 0 0 8.03 8.029h62.517a8.03 8.03 0 0 0 8.03-8.029V93.33a8.03 8.03 0 0 0-8.03-8.03Z"></path><path fill="#fff" d="M43.713 106.684v3.635h-3.765v-3.635h3.765Zm11.015 3.636-3.165-4.68-2.724 4.68h-4.166l4.805-7.74-4.955-7.295h4.316l3.084 4.53 2.66-4.53h4.145l-4.74 7.57 5.06 7.465h-4.32Zm15.919-2.041a4.28 4.28 0 0 1-1.785 1.595 6.316 6.316 0 0 1-2.86.595 6.71 6.71 0 0 1-4.17-1.235 4.471 4.471 0 0 1-1.785-3.445h3.87c.028.507.244.985.605 1.34.36.336.838.516 1.33.5a1.522 1.522 0 0 0 1.105-.385 1.332 1.332 0 0 0 .405-1 1.313 1.313 0 0 0-.375-.955 2.904 2.904 0 0 0-.925-.63 22.16 22.16 0 0 0-1.53-.585 18.643 18.643 0 0 1-2.33-.945 4.25 4.25 0 0 1-1.55-1.36 3.914 3.914 0 0 1-.65-2.35 4.001 4.001 0 0 1 .68-2.32 4.31 4.31 0 0 1 1.885-1.5 6.82 6.82 0 0 1 2.755-.5 6.165 6.165 0 0 1 4 1.195 4.546 4.546 0 0 1 1.67 3.275h-3.935a2 2 0 0 0-.54-1.18 1.592 1.592 0 0 0-1.18-.44 1.535 1.535 0 0 0-1.035.34 1.26 1.26 0 0 0-.39 1 1.27 1.27 0 0 0 .35.905c.25.26.549.466.88.605.355.155.865.355 1.53.595.812.265 1.602.589 2.365.97a4.599 4.599 0 0 1 1.57 1.39 4 4 0 0 1 .66 2.385 4.13 4.13 0 0 1-.62 2.14Zm6.265-.744h4.87v2.785h-8.5V95.285h3.64l-.01 12.25Z"></path><path fill="#00C650" d="M59.443 75.419H22.784c-3.175 0-5.76-3.16-5.76-7.05V41.775c0-3.885 2.585-7.044 5.76-7.044h36.674c3.17 0 5.755 3.16 5.755 7.044V68.37c-.015 3.89-2.6 7.05-5.77 7.05ZM22.784 38.33c-1.555 0-2.815 1.545-2.815 3.445V68.37c0 1.9 1.26 3.445 2.815 3.445h36.674c1.55 0 2.81-1.545 2.81-3.445V41.775c0-1.9-1.26-3.445-2.81-3.445H22.784Z"></path><path fill="#00C650" d="M63.727 59.402H18.484v3.605h45.243v-3.605Zm0-12.359H18.484v3.605h45.243v-3.605Z"></path><path fill="#00C650" d="M52.138 36.527h-3.605v37.084h3.605V36.527Zm-18.465 0h-3.605v37.084h3.605V36.527Z"></path></svg>
                                    @elseif (in_array($fileEntry->extension, ['ppt', 'pptx']))
                                        {{-- PowerPoint SVG --}}
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 120" fill="none" width="32" class="w-auto h-full shrink-0"><path fill="#05B3FE" d="M69.832 24.624a10.325 10.325 0 0 1-10.315-10.31V0H13.454A13.455 13.455 0 0 0 0 13.454v81.107a13.455 13.455 0 0 0 13.454 13.455h55.298a13.455 13.455 0 0 0 13.455-13.455V24.624H69.832Z" opacity="0.2"></path><path fill="#05B3FE" d="M38.387 45.07a2.037 2.037 0 1 0 0-4.074 2.037 2.037 0 0 0 0 4.073Zm-10.295 0a2.037 2.037 0 1 0 0-4.074 2.037 2.037 0 0 0 0 4.073Zm-1.246 22.568c0 .052.043.103.103.103h2.286a.105.105 0 0 0 .103-.103v-3.583h-2.492v3.583Zm0-8.122h2.492v3.695h-2.492v-3.695Zm0-4.528h2.492v3.696h-2.492v-3.696Zm2.389-4.538h-2.286a.105.105 0 0 0-.103.102v3.584h2.492v-3.584c0-.043-.043-.094-.103-.103Zm10.295 0h-2.285a.105.105 0 0 0-.103.102v3.584h2.492v-3.584c0-.043-.043-.094-.103-.103Zm-2.388 4.538h2.492v3.696h-2.492v-3.696Zm0 4.528h2.492v3.695h-2.492v-3.695Zm0 8.122c0 .052.043.103.103.103h2.286a.105.105 0 0 0 .103-.103v-3.583h-2.492v3.583Zm17.006-4.314-2.458.421.602 3.558c.009.035.034.06.043.069.017.008.043.026.077.017l2.252-.387a.157.157 0 0 0 .069-.043c.008-.017.025-.043.017-.077l-.602-3.558Zm-.764-4.469-2.458.413.618 3.644 2.458-.413-.618-3.644Zm-3.22-4.039 2.457-.416.617 3.644-2.457.416-.617-3.644Zm1.724-4.785c-.009-.06-.06-.103-.12-.086l-2.252.387c-.043.008-.06.034-.069.043-.008.017-.026.043-.017.077l.602 3.532 2.458-.42-.602-3.533Zm-.481-7.565a2.043 2.043 0 0 0-2.011-1.685 2.4 2.4 0 0 0-.344.026 2.024 2.024 0 0 0-1.659 2.346c.095.533.387 1.006.826 1.315.447.318.98.438 1.52.344a2.04 2.04 0 0 0 1.668-2.346Z"></path><path fill="#05B3FE" d="m58.617 68.946-5.345-31.47a1.126 1.126 0 0 0-1.307-.92l-7.081 1.203a1.139 1.139 0 0 0-.937.997v.25c0 .017.009.042.009.06l5.354 31.453c.103.61.687 1.031 1.306.928l7.081-1.203a1.14 1.14 0 0 0 .413-.155 1.126 1.126 0 0 0 .507-1.143ZM46.568 43.302a2.879 2.879 0 0 1 2.355-3.317 2.88 2.88 0 0 1 3.317 2.355 2.88 2.88 0 0 1-2.355 3.317c-.171.017-.335.026-.49.034a2.865 2.865 0 0 1-2.827-2.389Zm8.242 24.536-2.252.386c-.051 0-.111.009-.163.009a.962.962 0 0 1-.542-.172.937.937 0 0 1-.386-.61l-.68-3.98-.755-4.45v-.026l-.757-4.46-.67-3.937a.936.936 0 0 1 .163-.704.937.937 0 0 1 .61-.387l2.252-.387a.955.955 0 0 1 1.091.774l.67 3.944.757 4.469.756 4.469.68 3.97a.955.955 0 0 1-.774 1.092ZM41.971 37.293h-7.184a1.13 1.13 0 0 0-1.126 1.023v32.106a1.131 1.131 0 0 0 1.126 1.031h7.184c.628 0 1.135-.507 1.135-1.134V38.428c0-.628-.507-1.135-1.135-1.135Zm-1.504 21.802c0 .035 0 .06-.008.086v4.366c.008.026.008.06.008.086 0 .026 0 .06-.008.086v3.919a.93.93 0 0 1-.937.945h-2.286a.939.939 0 0 1-.937-.937V50.553a.94.94 0 0 1 .937-.937h2.286a.94.94 0 0 1 .937.937v3.919c.008.026.008.06.008.086 0 .034 0 .06-.008.086v4.365a.264.264 0 0 1 .008.086Zm-2.08-13.191a2.875 2.875 0 0 1-2.87-2.87 2.875 2.875 0 0 1 2.87-2.87 2.875 2.875 0 0 1 2.871 2.87c0 1.58-1.29 2.87-2.87 2.87Zm-5.577-7.596a1.124 1.124 0 0 0-1.126-1.023H24.5v.009c-.628 0-1.135.507-1.135 1.134V70.32c0 .627.507 1.134 1.135 1.134h7.184c.593 0 1.074-.456 1.126-1.031v-.112c0-.026 0-.06.008-.086v-31.72c-.008-.025-.008-.051-.008-.086v-.111Zm-2.63 20.797c0 .034 0 .06-.008.086v4.365c.008.026.008.06.008.086 0 .017 0 .043-.008.069v3.927a.94.94 0 0 1-.937.946h-2.286a.939.939 0 0 1-.937-.937V50.554c0-.516.421-.937.937-.937h2.286a.94.94 0 0 1 .937.937v3.919a.31.31 0 0 1 .008.086c0 .025 0 .051-.008.077v4.383a.265.265 0 0 1 .008.086Zm-2.088-13.2a2.875 2.875 0 0 1-2.87-2.87 2.875 2.875 0 0 1 2.87-2.87 2.875 2.875 0 0 1 2.87 2.87c0 1.58-1.289 2.87-2.87 2.87Zm54.115-21.281H69.832a10.325 10.325 0 0 1-10.314-10.31V0l22.689 24.624ZM91.966 85.3H29.45a8.03 8.03 0 0 0-8.03 8.03v18.64a8.03 8.03 0 0 0 8.03 8.029h62.517a8.03 8.03 0 0 0 8.03-8.029V93.33a8.03 8.03 0 0 0-8.03-8.03Z"></path><path fill="#fff" d="M40 95h10.422v2.704h-7.189v3.634h6.807V104h-6.807v6H40V95Zm12.837 0h3.233v15h-3.233V95Zm6.377 15V95h3.233v12.296h6.955V110H59.214Zm12.608-15H82.33v2.704h-7.275v3.444h7.02v2.662h-7.02v3.486H82.5V110H71.822V95Z"></path></svg>
                                    @elseif ($fileEntry->extension == 'json' || $fileEntry->extension == 'geojson')
                                        {{-- JSON SVG --}}
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 120" fill="none" width="32" class="w-auto h-full shrink-0"><path fill="#9679A6" d="M69.832 24.624a10.325 10.325 0 0 1-10.31-10.315V0H13.455A13.454 13.454 0 0 0 0 13.454v81.107a13.455 13.455 0 0 0 13.454 13.434h55.303a13.453 13.453 0 0 0 13.455-13.434V24.624h-12.38Z" opacity="0.3"></path><path fill="#9679A6" d="M29.871 61.18v1.832h-2.894a3.438 3.438 0 0 1-3.582-3.56v-7.768a1.957 1.957 0 0 0-2.083-2.083H21v-2.478h.312a1.958 1.958 0 0 0 2.083-2.082v-7.372a3.436 3.436 0 0 1 3.582-3.666h2.894v1.875h-1.853a2.081 2.081 0 0 0-2.27 2.332v7.039a2.916 2.916 0 0 1-2.728 3.082 2.872 2.872 0 0 1 2.728 3.04v7.455a2.207 2.207 0 0 0 2.27 2.354h1.853Zm2.645-5.144a1.582 1.582 0 0 1 1.645-1.625 1.603 1.603 0 0 1 1.666 1.625 1.581 1.581 0 0 1-1.666 1.645 1.563 1.563 0 0 1-1.645-1.645Zm6.539 0A1.646 1.646 0 1 1 40.7 57.68a1.56 1.56 0 0 1-1.645-1.645Zm6.519-.001a1.666 1.666 0 1 1 1.666 1.645 1.582 1.582 0 0 1-1.666-1.645Zm5.976 6.977V61.18h1.832a2.207 2.207 0 0 0 2.291-2.354v-7.392a2.832 2.832 0 0 1 2.728-3.04 2.875 2.875 0 0 1-2.728-3.083V38.21a2.083 2.083 0 0 0-2.29-2.332H51.55v-1.875h2.874a3.438 3.438 0 0 1 3.581 3.666v7.372a1.936 1.936 0 0 0 2.083 2.082h.312v2.478h-.312a1.937 1.937 0 0 0-2.083 2.083v7.767a3.438 3.438 0 0 1-3.581 3.645l-2.874-.084Zm30.662-38.388h-12.38a10.324 10.324 0 0 1-10.31-10.315V0l22.69 24.624ZM91.966 85.3H29.449a8.03 8.03 0 0 0-8.03 8.03v18.64a8.03 8.03 0 0 0 8.03 8.029h62.517a8.03 8.03 0 0 0 8.03-8.029V93.33a8.03 8.03 0 0 0-8.03-8.03Z"></path><path fill="#fff" d="M39.62 95.199h3.04v10.142c0 .937-.211 1.752-.633 2.443a4.179 4.179 0 0 1-1.74 1.598c-.743.374-1.608.561-2.592.561-.876 0-1.672-.154-2.387-.461a3.815 3.815 0 0 1-1.69-1.421c-.417-.639-.623-1.442-.618-2.408h3.061c.01.384.088.713.234.988.152.27.358.478.618.625.266.142.578.213.938.213.379 0 .698-.081.959-.242.265-.166.466-.407.603-.724.138-.317.206-.708.206-1.172V95.199Zm13.472 4.183c-.056-.573-.3-1.018-.731-1.335-.431-.317-1.016-.476-1.754-.476-.502 0-.926.071-1.272.213-.345.137-.61.33-.795.575-.18.247-.27.526-.27.838-.01.26.045.488.163.682.123.194.291.362.505.505.213.137.459.258.738.362.28.099.578.184.895.255l1.307.313a9.196 9.196 0 0 1 1.747.568c.53.237.99.528 1.378.874.388.345.689.753.902 1.221.218.469.329 1.006.334 1.612-.005.891-.232 1.662-.682 2.316-.445.649-1.09 1.153-1.932 1.513-.838.355-1.849.532-3.033.532-1.174 0-2.197-.18-3.068-.539-.866-.36-1.543-.893-2.031-1.599-.483-.71-.736-1.588-.76-2.634h2.976c.033.487.173.894.419 1.221.25.322.585.566 1.001.732.422.161.898.241 1.428.241.52 0 .973-.076 1.356-.227.389-.152.69-.362.902-.632.214-.27.32-.58.32-.931 0-.326-.097-.601-.291-.823-.19-.223-.469-.412-.838-.569a8.568 8.568 0 0 0-1.343-.426l-1.583-.398c-1.227-.298-2.195-.764-2.905-1.399-.71-.634-1.063-1.489-1.058-2.564-.005-.88.23-1.65.703-2.308.478-.658 1.134-1.172 1.967-1.541.833-.37 1.78-.554 2.84-.554 1.08 0 2.023.185 2.828.554.81.37 1.439.883 1.889 1.541.45.658.682 1.42.696 2.287h-2.948Zm18.582 3.09c0 1.586-.3 2.935-.902 4.048-.597 1.113-1.411 1.962-2.443 2.55-1.028.582-2.183.873-3.466.873-1.293 0-2.453-.293-3.48-.88-1.028-.588-1.84-1.438-2.437-2.55-.596-1.113-.894-2.46-.894-4.041 0-1.587.298-2.936.895-4.049.596-1.112 1.408-1.96 2.435-2.542 1.028-.587 2.188-.881 3.48-.881 1.284 0 2.44.294 3.467.88 1.032.583 1.846 1.43 2.443 2.543.601 1.113.902 2.462.902 4.049Zm-3.118 0c0-1.028-.154-1.894-.462-2.6-.303-.705-.731-1.24-1.285-1.605-.554-.364-1.203-.547-1.946-.547-.744 0-1.392.183-1.946.547-.554.365-.985.9-1.293 1.605-.303.706-.455 1.572-.455 2.6 0 1.027.152 1.894.455 2.599.308.706.739 1.241 1.293 1.605.554.365 1.202.547 1.946.547.743 0 1.392-.182 1.946-.547.554-.364.982-.899 1.285-1.605.308-.705.462-1.572.462-2.599Zm17.562-7.273v14.545h-2.656l-6.328-9.155h-.107v9.155h-3.075V95.199h2.699l6.278 9.148h.128v-9.148h3.061Z"></path></svg>
                                    @elseif ($fileEntry->extension == 'zip')
                                        {{-- ZIP SVG --}}
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 80" fill="none" class="w-14 h-auto"><path fill="#FFA000" d="M64.65 12H15.19c-4.007-.007-7.26 3.195-7.267 7.15 0 .322.02.644.064.963a1.451 1.451 0 0 0 1.63 1.24c.194-.025.38-.088.55-.186a4.161 4.161 0 0 1 2.114-.552H22.64c1.869.005 3.53 1.179 4.137 2.924l.247.786c1 2.925 3.776 4.897 6.904 4.905H67.56a4.407 4.407 0 0 1 2.173.575c.224.128.478.195.736.195.803 0 1.455-.643 1.455-1.436V19.18c0-3.965-3.257-7.179-7.274-7.179Z"></path><path fill="#FFC107" d="M71.363 27.622a7.316 7.316 0 0 0-3.655-.975H33.992a4.395 4.395 0 0 1-4.148-2.934l-.248-.79C28.593 19.988 25.81 18.01 22.675 18H12.292a7.043 7.043 0 0 0-3.57.931A7.175 7.175 0 0 0 5 25.206v34.588C5 63.774 8.265 67 12.292 67h55.416C71.735 67 75 63.774 75 59.794v-25.94a7.141 7.141 0 0 0-3.637-6.232Z"></path></svg>
                                    @else
                                        {{-- Default SVG --}}
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 120" fill="none" width="32" class="w-auto h-full shrink-0"><path fill="#05B3FE" d="M69.832 24.624a10.325 10.325 0 0 1-10.315-10.31V0H13.454A13.455 13.455 0 0 0 0 13.454v81.107a13.455 13.455 0 0 0 13.454 13.455h55.298a13.455 13.455 0 0 0 13.455-13.455V24.624H69.832Z" opacity="0.2"></path><path fill="#05B3FE" d="M38.387 45.07a2.037 2.037 0 1 0 0-4.074 2.037 2.037 0 0 0 0 4.073Zm-10.295 0a2.037 2.037 0 1 0 0-4.074 2.037 2.037 0 0 0 0 4.073Zm-1.246 22.568c0 .052.043.103.103.103h2.286a.105.105 0 0 0 .103-.103v-3.583h-2.492v3.583Zm0-8.122h2.492v3.695h-2.492v-3.695Zm0-4.528h2.492v3.696h-2.492v-3.696Zm2.389-4.538h-2.286a.105.105 0 0 0-.103.102v3.584h2.492v-3.584c0-.043-.043-.094-.103-.103Zm10.295 0h-2.285a.105.105 0 0 0-.103.102v3.584h2.492v-3.584c0-.043-.043-.094-.103-.103Zm-2.388 4.538h2.492v3.696h-2.492v-3.696Zm0 4.528h2.492v3.695h-2.492v-3.695Zm0 8.122c0 .052.043.103.103.103h2.286a.105.105 0 0 0 .103-.103v-3.583h-2.492v3.583Zm17.006-4.314-2.458.421.602 3.558c.009.035.034.06.043.069.017.008.043.026.077.017l2.252-.387a.157.157 0 0 0 .069-.043c.008-.017.025-.043.017-.077l-.602-3.558Zm-.764-4.469-2.458.413.618 3.644 2.458-.413-.618-3.644Zm-3.22-4.039 2.457-.416.617 3.644-2.457.416-.617-3.644Zm1.724-4.785c-.009-.06-.06-.103-.12-.086l-2.252.387c-.043.008-.06.034-.069.043-.008.017-.026.043-.017.077l.602 3.532 2.458-.42-.602-3.533Zm-.481-7.565a2.043 2.043 0 0 0-2.011-1.685 2.4 2.4 0 0 0-.344.026 2.024 2.024 0 0 0-1.659 2.346c.095.533.387 1.006.826 1.315.447.318.98.438 1.52.344a2.04 2.04 0 0 0 1.668-2.346Z"></path><path fill="#05B3FE" d="m58.617 68.946-5.345-31.47a1.126 1.126 0 0 0-1.307-.92l-7.081 1.203a1.139 1.139 0 0 0-.937.997v.25c0 .017.009.042.009.06l5.354 31.453c.103.61.687 1.031 1.306.928l7.081-1.203a1.14 1.14 0 0 0 .413-.155 1.126 1.126 0 0 0 .507-1.143ZM46.568 43.302a2.879 2.879 0 0 1 2.355-3.317 2.88 2.88 0 0 1 3.317 2.355 2.88 2.88 0 0 1-2.355 3.317c-.171.017-.335.026-.49.034a2.865 2.865 0 0 1-2.827-2.389Zm8.242 24.536-2.252.386c-.051 0-.111.009-.163.009a.962.962 0 0 1-.542-.172.937.937 0 0 1-.386-.61l-.68-3.98-.755-4.45v-.026l-.757-4.46-.67-3.937a.936.936 0 0 1 .163-.704.937.937 0 0 1 .61-.387l2.252-.387a.955.955 0 0 1 1.091.774l.67 3.944.757 4.469.756 4.469.68 3.97a.955.955 0 0 1-.774 1.092ZM41.971 37.293h-7.184a1.13 1.13 0 0 0-1.126 1.023v32.106a1.131 1.131 0 0 0 1.126 1.031h7.184c.628 0 1.135-.507 1.135-1.134V38.428c0-.628-.507-1.135-1.135-1.135Zm-1.504 21.802c0 .035 0 .06-.008.086v4.366c.008.026.008.06.008.086 0 .026 0 .06-.008.086v3.919a.93.93 0 0 1-.937.945h-2.286a.939.939 0 0 1-.937-.937V50.553a.94.94 0 0 1 .937-.937h2.286a.94.94 0 0 1 .937.937v3.919c.008.026.008.06.008.086 0 .034 0 .06-.008.086v4.365a.264.264 0 0 1 .008.086Zm-2.08-13.191a2.875 2.875 0 0 1-2.87-2.87 2.875 2.875 0 0 1 2.87-2.87 2.875 2.875 0 0 1 2.871 2.87c0 1.58-1.29 2.87-2.87 2.87Zm-5.577-7.596a1.124 1.124 0 0 0-1.126-1.023H24.5v.009c-.628 0-1.135.507-1.135 1.134V70.32c0 .627.507 1.134 1.135 1.134h7.184c.593 0 1.074-.456 1.126-1.031v-.112c0-.026 0-.06.008-.086v-31.72c-.008-.025-.008-.051-.008-.086v-.111Zm-2.63 20.797c0 .034 0 .06-.008.086v4.365c.008.026.008.06.008.086 0 .017 0 .043-.008.069v3.927a.94.94 0 0 1-.937.946h-2.286a.939.939 0 0 1-.937-.937V50.554c0-.516.421-.937.937-.937h2.286a.94.94 0 0 1 .937.937v3.919a.31.31 0 0 1 .008.086c0 .025 0 .051-.008.077v4.383a.265.265 0 0 1 .008.086Zm-2.088-13.2a2.875 2.875 0 0 1-2.87-2.87 2.875 2.875 0 0 1 2.87-2.87 2.875 2.875 0 0 1 2.87 2.87c0 1.58-1.289 2.87-2.87 2.87Zm54.115-21.281H69.832a10.325 10.325 0 0 1-10.314-10.31V0l22.689 24.624ZM91.966 85.3H29.45a8.03 8.03 0 0 0-8.03 8.03v18.64a8.03 8.03 0 0 0 8.03 8.029h62.517a8.03 8.03 0 0 0 8.03-8.029V93.33a8.03 8.03 0 0 0-8.03-8.03Z"></path><path fill="#fff" d="M40 95h10.422v2.704h-7.189v3.634h6.807V104h-6.807v6H40V95Zm12.837 0h3.233v15h-3.233V95Zm6.377 15V95h3.233v12.296h6.955V110H59.214Zm12.608-15H82.33v2.704h-7.275v3.444h7.02v2.662h-7.02v3.486H82.5V110H71.822V95Z"></path></svg>
                                    @endif
                                    </a>
                                </div>
                                
                                <div class="flex-grow-1">
                                    <div class="filemanager-file-info">
                                        @if($fileEntry->type === 'folder')
                                            <h6 class="mb-0">
                                                <a href="{{ route('user.files.index', ['folder' => $fileEntry->shared_id]) }}" class="filemanager-file-title filemanager-link">
                                                    {{ $fileEntry->name }}
                                                </a>
                                            </h6>
                                            <small class="text-muted">Folder • {{ vDate($fileEntry->created_at) }}</small>
                                        @else
                                            <h6 class="mb-0">
                                                <a href="{{ route('file.preview', $fileEntry->shared_id) }}" target="_blank" class="filemanager-file-title filemanager-link">
                                                    {{ $fileEntry->name }}
                                                </a>
                                            </h6>
                                            <small class="text-muted">{{ formatBytes($fileEntry->size) }} • {{ vDate($fileEntry->created_at) }}</small>
                                        @endif
                                    </div>
                                </div>
                                
                                <div class="dropdown">
                                    <a class="dropdown-toggle-custom" type="button" data-bs-toggle="dropdown">
                                        <svg stroke="currentColor" fill="currentColor" stroke-width="0.5" viewBox="0 0 24 24" height="20" width="20" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M12 3C11.175 3 10.5 3.675 10.5 4.5C10.5 5.325 11.175 6 12 6C12.825 6 13.5 5.325 13.5 4.5C13.5 3.675 12.825 3 12 3ZM12 18C11.175 18 10.5 18.675 10.5 19.5C10.5 20.325 11.175 21 12 21C12.825 21 13.5 20.325 13.5 19.5C13.5 18.675 12.825 18 12 18ZM12 10.5C11.175 10.5 10.5 11.175 10.5 12C10.5 12.825 11.175 13.5 12 13.5C12.825 13.5 13.5 12.825 13.5 12C13.5 11.175 12.825 10.5 12 10.5Z"></path>
                                        </svg>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        @if($fileEntry->type === 'folder')
                                            <li>
                                                <a class="dropdown-item" href="{{ route('user.files.index', ['folder' => $fileEntry->shared_id]) }}">
                                                    <i class="fa fa-folder-open me-2"></i>{{ lang('Open folder', 'files') }}
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="{{ route('user.files.edit', $fileEntry->shared_id) }}">
                                                    <i class="fa fa-edit me-2"></i>{{ lang('Rename folder', 'files') }}
                                                </a>
                                            </li>
                                            <li>
                                                <form action="{{ route('user.files.delete', $fileEntry->shared_id) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="dropdown-item text-danger confirm-action-form">
                                                        <i class="fa fa-trash-alt me-2"></i>{{ lang('Delete folder', 'files') }}
                                                    </button>
                                                </form>
                                            </li>
                                        @else
                                            @if ($fileEntry->access_status)
                                                <li>
                                                    <a href="#" class="dropdown-item fileManager-share-file"
                                                        data-preview="{{ isFileSupportPreview($fileEntry->type) ? 'true' : 'false' }}"
                                                        data-share='{"filename":"{{ $fileEntry->name }}","download_link":"{{ route('file.download', $fileEntry->shared_id) }}","preview_link":"{{ route('file.preview', $fileEntry->shared_id) }}"}'>
                                                        <i class="fas fa-share-alt me-2"></i>{{ lang('Share', 'files') }}
                                                    </a>
                                                </li>
                                            @endif
                                            <li>
                                                <a class="dropdown-item" href="{{ route('file.download', $fileEntry->shared_id) }}" target="_blank">
                                                    <i class="fa fa-download me-2"></i>{{ lang('Download', 'files') }}
                                                </a>
                                            </li>
                                            {{-- NEW: Move to folder option --}}
                                            <li>
                                                <a class="dropdown-item file-move-btn" href="#" data-file-id="{{ $fileEntry->shared_id }}">
                                                    <i class="fa fa-folder-open me-2"></i>Move to...
                                                </a>
                                            </li>


                                            {{-- On both FILE and FOLDER items --}}
                                            <li>
                                                <a class="dropdown-item fm-cut" href="#" data-file-id="{{ $fileEntry->shared_id }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" style="height: 20px;width: 15px;margin-right: 5px;"><path d="M256 320L216.5 359.5C203.9 354.6 190.3 352 176 352C114.1 352 64 402.1 64 464C64 525.9 114.1 576 176 576C237.9 576 288 525.9 288 464C288 449.7 285.3 436.1 280.5 423.5L563.2 140.8C570.3 133.7 570.3 122.3 563.2 115.2C534.9 86.9 489.1 86.9 460.8 115.2L320 256L280.5 216.5C285.4 203.9 288 190.3 288 176C288 114.1 237.9 64 176 64C114.1 64 64 114.1 64 176C64 237.9 114.1 288 176 288C190.3 288 203.9 285.3 216.5 280.5L256 320zM353.9 417.9L460.8 524.8C489.1 553.1 534.9 553.1 563.2 524.8C570.3 517.7 570.3 506.3 563.2 499.2L417.9 353.9L353.9 417.9zM128 176C128 149.5 149.5 128 176 128C202.5 128 224 149.5 224 176C224 202.5 202.5 224 176 224C149.5 224 128 202.5 128 176zM176 416C202.5 416 224 437.5 224 464C224 490.5 202.5 512 176 512C149.5 512 128 490.5 128 464C128 437.5 149.5 416 176 416z"></path></svg>
                                                    Cut
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item fm-copy" href="#" data-file-id="{{ $fileEntry->shared_id }}">
                                                    <i class="fa fa-copy me-2"></i>Copy
                                                </a>
                                            </li>

                                            {{-- Paste is contextual: show it in folder rows and also a toolbar button for the current folder --}}
                                            <li>
                                                <a class="dropdown-item fm-paste" href="#" 
                                                    data-target-folder="{{ $fileEntry->type === 'folder' ? $fileEntry->shared_id : '' }}">
                                                    <i class="fa fa-paste me-2"></i>Paste into {{ $fileEntry->type === 'folder' ? 'this folder' : '…' }}
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="{{ route('user.files.edit', $fileEntry->shared_id) }}">
                                                    <i class="fa fa-edit me-2"></i>{{ lang('Edit details', 'files') }}
                                                </a>
                                            </li>
                                            <li>
                                                <form action="{{ route('user.files.delete', $fileEntry->shared_id) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="dropdown-item text-danger confirm-action-form">
                                                        <i class="fa fa-trash-alt me-2"></i>{{ lang('Delete', 'files') }}
                                                    </button>
                                                </form>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>


        
        {{ $fileEntries->links() }}
        <div id="shareModal" class="modal fade share-modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form class="modal-content">
                    <div class="modal-header border-0 pb-0">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4 pt-1 mb-1">
                        <h5 class="mb-4"><i class="fas fa-share-alt me-2"></i>{{ lang('Share this file') }}</h5>
                        <p class="mb-4 text-ellipsis filename"></p>
                        <div class="mb-3">
                            <div class="share"></div>
                        </div>
                        <div class="preview-link mb-3">
                            <label class="form-label"><strong>{{ lang('Preview link') }}</strong></label>
                            <div class="input-group">
                                <input id="copy-preview-link" type="text" class="form-control" value="" readonly>
                                <button type="button" class="btn btn-primary btn-md copy"
                                    data-clipboard-target="#copy-preview-link"><i class="far fa-clone"></i></button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><strong>{{ lang('Download link') }}</strong></label>
                            <div class="input-group">
                                <input id="copy-download-link" type="text" class="form-control" value="ddd" readonly>
                                <button type="button" class="btn btn-primary btn-md copy"
                                    data-clipboard-target="#copy-download-link"><i class="far fa-clone"></i></button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @else
        @include('frontend.user.includes.empty')
    @endif
@endsection




{{-- Share With Me --}}
@push('modals')
    <div class="modal fade swm-modal" id="sharedWithMeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h5 class="modal-title mb-0 d-flex align-items-center">
                        <span>{{ __('Share') }}</span>
                        <span id="swmFileName"
                              class="text-muted ms-2 text-truncate d-inline-block"
                              style="max-width:60%; display:none;"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body pt-2">
                    <div id="swmAlert" class="alert py-2 px-3 d-none"></div>

                    {{-- IMPORTANT: prevent normal GET submit --}}
                    <form id="swmForm" class="mb-0" method="POST" action="javascript:void(0);">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">
                                {{ __('Access status') }} <span class="text-danger">*</span>
                            </label>
                            <select id="swmAccessStatus" name="access_status" class="form-select form-select-sm" required>
                                <option value="0">{{ __('Private') }}</option>
                                <option value="1">{{ __('Public') }}</option>
                            </select>
                        </div>

                        <div id="swmPublicOptions" style="display:block;">
                            <label class="form-label mb-1">{{ __('Invite by email') }}</label>

                            <div class="position-relative">
                                <div class="input-group input-group-sm mb-2">
                                    <input type="email" name="email" id="swmEmail"
                                           class="form-control"
                                           placeholder="user@example.com"
                                           autocomplete="off">

                                    <button class="btn btn-primary" type="submit">
                                        {{ __('Share') }}
                                    </button>
                                </div>

                                <div id="swmTypeahead" class="dropdown-menu w-100 shadow-sm"></div>
                            </div>

                            <div class="row g-2 align-items-center">
                                <div class="col-12 col-sm-6">
                                    <label class="form-label mb-1">{{ __('Permission') }}</label>
                                    <select id="swmPermission" name="permission" class="form-select form-select-sm">
                                        <option value="view">{{ __('Viewer') }}</option>
                                        <option value="edit">{{ __('Editor') }}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-text mt-2">
                                {{ __('They will receive a link to open this item.') }}
                            </div>

                            <input type="hidden" name="can_download" value="1">
                            <input type="hidden" name="can_reshare" value="0">
                        </div>
                    </form>

                    {{-- People with access --}}
                    <div id="swmPeopleSection" class="mt-4" style="display:none;">
                        <hr class="my-3">
                        <h6 class="mb-3">{{ __('People with access') }}</h6>
                        <div id="swmPeopleList" class="people-list"></div>
                    </div>

                    <div class="mb-3 mt-3" id="swmSocialIcons" style="display:none;">
                        <label class="form-label"><strong>{{ __('Share via') }}</strong></label>
                        <div class="share"></div>
                    </div>
                </div>

                <div class="modal-footer py-2">
                    <div id="swmCopyLinkGroup" class="me-auto" style="display:none;">
                        <button type="button" id="swmCopyLink" class="btn btn-primary btn-md">
                            <i class="far fa-clone me-1"></i>{{ __('Copy Link') }}
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endpush


@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ✅ Prevent script from installing twice (very important)
    if (window.__swmInstalled) return;
    window.__swmInstalled = true;

(function () {
    const $ = (s, c = document) => c.querySelector(s);

    let currentSharedId = null;
    let currentType = null;
    let currentDownloadLink = '';
    let clipboard = null;

    const MIN_CHARS_TO_SUGGEST = 1;
    let suggest = { items: [], open: false, activeIndex: -1, lastQuery: '' };

    function showAlert(msg, type = 'success') {
        const box = $('#swmAlert');
        if (!box) return;
        box.className = 'alert py-2 px-3 alert-' + (type === 'success' ? 'success' : 'danger');
        box.textContent = msg;
        box.classList.remove('d-none');
        setTimeout(() => box.classList.add('d-none'), 3000);
    }

    function setTitle(fileName) {
        const span = $('#swmFileName');
        if (!span) return;

        fileName = (fileName || '').trim();
        if (fileName) {
            span.textContent = '“' + fileName + '”';
            span.title = fileName;
            span.style.display = '';
        } else {
            span.textContent = '';
            span.removeAttribute('title');
            span.style.display = 'none';
        }
    }

    async function api(url, { method = 'GET', data = null } = {}) {
        const opt = {
            method,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
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
    }

    function escapeHtml(str) {
        return (str || '').replace(/[&<>"']/g, (s) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
        }[s]));
    }

    function renderPeopleList(people, ownerInfo, uploaderInfo = null) {
        const container = $('#swmPeopleList');
        const section   = $('#swmPeopleSection');
        if (!container || !section) return;

        if ((!people || !Array.isArray(people) || people.length === 0) && !ownerInfo && !uploaderInfo) {
            section.style.display = 'none';
            container.innerHTML = '';
            return;
        }

        section.style.display = 'block';
        let peopleHtml = '';

        const ownerId    = ownerInfo?.id ?? null;
        const ownerEmail = ownerInfo?.email || '';
        const ownerName  = ownerInfo?.name  || ownerEmail;

        const uploaderId    = uploaderInfo?.id ?? null;
        const uploaderEmail = uploaderInfo?.email || '';
        const uploaderName  = uploaderInfo?.name  || uploaderEmail;

        const hasDifferentUploader =
            uploaderInfo && ownerInfo && uploaderId && ownerId && uploaderId !== ownerId;

        if (hasDifferentUploader) {
            peopleHtml += `
                <div class="person-item d-flex align-items-center justify-content-between py-2 border-bottom">
                    <div class="d-flex align-items-center flex-grow-1">
                        <div class="avatar me-2">
                            <div class="bg-success rounded-circle d-flex align-items-center justify-content-center text-white"
                                style="width:32px;height:32px;font-size:14px;">
                                ${escapeHtml(uploaderName).charAt(0).toUpperCase()}
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-medium">${escapeHtml(uploaderName)}</div>
                            <div class="text-muted small">${escapeHtml(uploaderEmail)}</div>
                        </div>
                    </div>
                    <span class="badge bg-success">Owner</span>
                </div>
            `;

            peopleHtml += `
                <div class="person-item d-flex align-items-center justify-content-between py-2 border-bottom">
                    <div class="d-flex align-items-center flex-grow-1">
                        <div class="avatar me-2">
                            <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center text-white"
                                style="width:32px;height:32px;font-size:14px;">
                                ${escapeHtml(ownerName).charAt(0).toUpperCase()}
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-medium">${escapeHtml(ownerName)}</div>
                            <div class="text-muted small">${escapeHtml(ownerEmail)}</div>
                        </div>
                    </div>
                    <span class="badge bg-primary">Editor</span>
                </div>
            `;
        } else if (ownerInfo) {
            peopleHtml += `
                <div class="person-item d-flex align-items-center justify-content-between py-2 border-bottom">
                    <div class="d-flex align-items-center flex-grow-1">
                        <div class="avatar me-2">
                            <div class="bg-success rounded-circle d-flex align-items-center justify-content-center text-white"
                                style="width:32px;height:32px;font-size:14px;">
                                ${escapeHtml(ownerName).charAt(0).toUpperCase()}
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-medium">${escapeHtml(ownerName)}</div>
                            <div class="text-muted small">${escapeHtml(ownerEmail)}</div>
                        </div>
                    </div>
                    <span class="badge bg-success">Owner</span>
                </div>
            `;
        }

        if (people && Array.isArray(people)) {
            people.forEach(person => {
                const email      = person.recipient_email || person.email || '';
                const name       = person.recipient_name || person.name || email;
                const permission = person.permission || 'view';
                const status     = person.status || 'active';
                const isActive   = status === 'active' && !person.revoked_at && !person.expired;

                let badgeText = permission === 'edit' ? 'Editor' : 'Viewer';
                let badgeClass = 'bg-primary text-white';

                let statusText = '';
                let statusClass = '';
                if (!isActive) {
                    statusText  = person.revoked_at ? 'Revoked' : 'Expired';
                    statusClass = 'text-danger';
                }

                peopleHtml += `
                    <div class="person-item d-flex align-items-center justify-content-between py-2 border-bottom">
                        <div class="d-flex align-items-center flex-grow-1">
                            <div class="avatar me-2">
                                <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center text-white"
                                    style="width:32px;height:32px;font-size:14px;">
                                    ${escapeHtml(name).charAt(0).toUpperCase()}
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-medium">${escapeHtml(name)}</div>
                                <div class="text-muted small">${escapeHtml(email)}</div>
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
            });
        }

        container.innerHTML = peopleHtml;

        container.querySelectorAll('.remove-person').forEach(btn => {
            btn.addEventListener('click', function () {
                removePersonAccess(this.getAttribute('data-share-id'));
            });
        });

        container.querySelectorAll('.change-permission').forEach(link => {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                updatePersonPermission(this.getAttribute('data-share-id'), this.getAttribute('data-permission'));
            });
        });
    }

    async function removePersonAccess(shareId) {
        if (!confirm('Are you sure you want to remove access for this person?')) return;

        try {
            const response = await api(`/user/shares/${shareId}/remove`, { method: 'DELETE' });
            showAlert(response.message || 'Access removed successfully', 'success');
            await loadPeopleWithAccess();
        } catch (err) {
            console.error(err);
            showAlert(err.toString() || 'Failed to remove access', 'danger');
        }
    }

    async function updatePersonPermission(shareId, newPermission) {
        try {
            const response = await api(`/user/shares/${shareId}/update-permission`, {
                method: 'PUT',
                data: { permission: newPermission }
            });
            showAlert(response.message || 'Permission updated successfully', 'success');
            await loadPeopleWithAccess();
        } catch (err) {
            console.error(err);
            showAlert(err.toString() || 'Failed to update permission', 'danger');
        }
    }

    async function loadPeopleWithAccess() {
        if (!currentSharedId) return;
        try {
            const response = await api(`{{ url('user/files') }}/${currentSharedId}/shared-people`);
            renderPeopleList(response.people || [], response.owner || null, response.uploader || null);
        } catch (err) {
            console.error(err);
            renderPeopleList([], { email: 'Current User', name: 'Owner' }, null);
        }
    }

    function debounce(fn, ms = 200) {
        let t;
        return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
    }

    function closeTypeahead() {
        const dd = $('#swmTypeahead');
        if (!dd) return;
        dd.classList.remove('show');
        suggest.open = false;
        suggest.activeIndex = -1;
    }

    function openTypeahead() {
        const dd = $('#swmTypeahead');
        if (!dd) return;
        dd.classList.add('show');
        suggest.open = true;
    }

    function renderTypeahead(q) {
        const dd = $('#swmTypeahead');
        if (!dd) return;

        dd.innerHTML = '';
        const emailVal = (q || '').trim();
        const items = suggest.items || [];

        if (!items.length && !emailVal) {
            closeTypeahead();
            return;
        }

        items.forEach((r, idx) => {
            const el = document.createElement('button');
            el.type = 'button';
            el.className = 'dropdown-item' + (idx === suggest.activeIndex ? ' active' : '');
            el.innerHTML = `
                <span class="name"><strong>${escapeHtml(r.name || r.email)}</strong></span>
                ${r.name ? `<span class="email">${escapeHtml(r.email)}</span>` : ''}
            `;
            el.addEventListener('mousedown', (ev) => {
                ev.preventDefault();
                $('#swmEmail').value = r.email;
                closeTypeahead();
            });
            dd.appendChild(el);
        });

        dd.classList.add('show');
        suggest.open = true;
    }

    const fetchSuggestions = debounce(async (q = '') => {
        try {
            const r = await api(`{{ url('user/shares/recipients') }}?limit=20&q=${encodeURIComponent(q)}`);
            suggest.items = Array.isArray(r.recipients) ? r.recipients : [];
            renderTypeahead(q);
        } catch {
            suggest.items = [];
            renderTypeahead(q);
        }
    }, 200);

    function updateCopyLinkButton() {
        const accessStatus = $('#swmAccessStatus')?.value;
        const copyGroup = $('#swmCopyLinkGroup');
        const copyBtn = $('#swmCopyLink');
        if (!copyGroup || !copyBtn) return;

        if (accessStatus === '1') {
            copyGroup.style.display = 'block';
            copyBtn.disabled = false;
        } else {
            copyGroup.style.display = 'none';
            copyBtn.disabled = true;
        }
    }

    async function copyToClipboard(text) {
        if (!text || !text.trim()) {
            showAlert('{{ __("No link available to copy.") }}', 'danger');
            return;
        }

        if (navigator.clipboard && window.isSecureContext) {
            try {
                await navigator.clipboard.writeText(text);
                showAlert('{{ __("Link copied to clipboard!") }}', 'success');
                return;
            } catch (e) {}
        }

        const temp = document.createElement('textarea');
        temp.value = text;
        temp.style.position = 'fixed';
        temp.style.left = '-9999px';
        document.body.appendChild(temp);
        temp.select();
        try {
            document.execCommand('copy');
            showAlert('{{ __("Link copied to clipboard!") }}', 'success');
        } catch (e) {
            showAlert('{{ __("Failed to copy link.") }}', 'danger');
        }
        document.body.removeChild(temp);
    }

    async function updateAccessStatus() {
        if (!currentSharedId) {
            showAlert('{{ __("File ID is missing. Please close and try again.") }}', 'danger');
            return;
        }

        const accessStatus = $('#swmAccessStatus').value;
        $('#swmAccessStatus').disabled = true;

        try {
            const response = await api(`/user/files/${currentSharedId}/update-status`, {
                method: 'POST',
                data: { access_status: accessStatus },
            });

            currentDownloadLink = response.download_link || currentDownloadLink || '';
            $('#swmAccessStatus').value = response.access_status ?? accessStatus;

            updateCopyLinkButton();
            showAlert('{{ __("Status updated") }}', 'success');
        } catch (err) {
            console.error(err);
            showAlert(err.toString() || '{{ __("Failed to update access status.") }}', 'danger');
        } finally {
            $('#swmAccessStatus').disabled = false;
        }
    }

    // ===== modal wiring =====
    const sharedWithMeModalEl = document.getElementById('sharedWithMeModal');
    if (!sharedWithMeModalEl) return;

    sharedWithMeModalEl.addEventListener('show.bs.modal', async function (event) {
        const button = event.relatedTarget;
        currentSharedId = button?.getAttribute('data-file-id');
        currentType = (button?.getAttribute('data-file-type') || 'file').toLowerCase();
        const fileName = button?.getAttribute('data-file-name') || '';
        const shareData = button?.getAttribute('data-share') || '';

        setTitle(fileName);

        $('#swmForm')?.reset();
        if ($('#swmPermission')) $('#swmPermission').value = 'view';
        if ($('#swmEmail')) $('#swmEmail').value = '';
        closeTypeahead();

        try {
            const response = await api(`{{ url('user/files') }}/${currentSharedId}`);
            $('#swmAccessStatus').value = response.access_status ?? '0';
            currentDownloadLink = response.download_link || '';
            updateCopyLinkButton();
            await loadPeopleWithAccess();
        } catch (e) {
            try {
                const parsed = shareData ? JSON.parse(shareData) : {};
                currentDownloadLink = parsed.download_link || '';
            } catch {}
            $('#swmAccessStatus').value = '0';
            updateCopyLinkButton();
            renderPeopleList([], null, null);
        }

        $('#swmCopyLink').onclick = () => copyToClipboard(currentDownloadLink);
    });

    sharedWithMeModalEl.addEventListener('hidden.bs.modal', function () {
        currentSharedId = null;
        currentDownloadLink = '';
        closeTypeahead();
    });

    // ===== inputs wiring =====
    const emailInput = $('#swmEmail');
    if (emailInput) {
        emailInput.addEventListener('input', (e) => {
            const q = e.target.value || '';
            suggest.lastQuery = q;

            if (q.trim().length >= MIN_CHARS_TO_SUGGEST) {
                fetchSuggestions(q);
                openTypeahead();
            } else {
                closeTypeahead();
            }
        });

        emailInput.addEventListener('blur', () => setTimeout(closeTypeahead, 120));
    }

    $('#swmAccessStatus')?.addEventListener('change', updateAccessStatus);

    // ===== form submit (AJAX) =====
    const swmForm = $('#swmForm');
    if (swmForm) {

        // ✅ overwrite any old submit handler (prevents double binding)
        swmForm.onsubmit = async (e) => {
            e.preventDefault();

            // ✅ block duplicate submits (this stops 2nd request)
            if (swmForm.dataset.sending === '1') return;
            swmForm.dataset.sending = '1';

            if (!currentSharedId) {
                showAlert('{{ __("File ID is missing. Please close and try again.") }}', 'danger');
                swmForm.dataset.sending = '0';
                return;
            }

            const accessStatus = $('#swmAccessStatus').value;
            const email = (emailInput?.value || '').trim();
            const submitBtn = swmForm.querySelector('button[type="submit"]');

            if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                showAlert('{{ __("Please enter a valid email address.") }}', 'danger');
                emailInput?.focus();
                swmForm.dataset.sending = '0';
                return;
            }

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> {{ __("Sharing...") }}';
            }

            try {
                const fd = new FormData(swmForm);
                fd.set('access_status', accessStatus);

                if (email) {
                    fd.set('recipients', email);
                    fd.set('permission', $('#swmPermission')?.value ?? 'view');
                }

                const response = await api(`{{ url('user/files') }}/${currentSharedId}/share`, {
                    method: 'POST',
                    data: fd,
                });

                if (response.download_link) currentDownloadLink = response.download_link;

                showAlert(response.message || '{{ __("Shared successfully") }}', 'success');

                if (emailInput) emailInput.value = '';
                closeTypeahead();
                updateCopyLinkButton();
                await loadPeopleWithAccess();

            } catch (err) {
                console.error(err);
                showAlert(err.toString() || '{{ __("Sharing failed. Please try again.") }}', 'danger');
            } finally {
                // ✅ always unlock + re-enable button
                swmForm.dataset.sending = '0';
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = '{{ __("Share") }}';
                }
            }
        };
    }

})(); // executes the IIFE

});
</script>
@endpush

