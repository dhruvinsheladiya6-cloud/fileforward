{{-- resources/views/frontend/user/shared/browse.blade.php --}}
@extends('frontend.user.layouts.dash')

{{-- @section('title', __('Shared folder')) --}}



@section('content')
    <div class="container py-3">
        {{-- Top: owner + folder + back --}}
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-3">
            <div>
                <h5 class="mb-1">
                    {{ __('Shared by') }}:
                    {{ $owner?->name ?? $owner?->email ?? __('Unknown') }}
                </h5>
                <div class="text-muted small">
                    {{ __('Folder') }}: {{ $root->name }}
                </div>
            </div>
            {{-- Upload UI visible only if recipient can edit --}}
            @if($share->permission === 'edit')
            @if($uploadMode === 'regular')
              <div class="add-new-dropdown">
                <button class="btn btn-primary add-new-btn" type="button" id="sharedAddNewDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                  <i class="fas fa-plus me-2"></i>{{ __('Add New') }}
                  <i class="fas fa-chevron-down ms-2"></i>
                </button>
                <ul class="dropdown-menu add-new-menu" aria-labelledby="sharedAddNewDropdown">
                  <li>
                    <a class="dropdown-item shared-upload-file-option" href="#">
                      <i class="fas fa-upload me-2"></i>{{ __('Upload File') }}
                    </a>
                  </li>
                  <li>
                    <a class="dropdown-item shared-upload-folder-option" href="#" id="sharedFolderUploadBtn">
                      <i class="fas fa-folder me-2"></i>{{ __('Upload Folder') }}
                    </a>
                  </li>
                </ul>
              </div>

              {{-- Optional hidden input for folder selection in regular mode --}}
              <input type="file" id="sharedFolderInput" webkitdirectory directory multiple style="display:none;">

            @else
              <div class="add-new-dropdown">
                <button class="btn btn-primary add-new-btn" type="button" id="sharedAddNewDropdownCustom" data-bs-toggle="dropdown" aria-expanded="false">
                  <i class="fas fa-cogs me-2"></i>{{ __('Add New') }}
                  <i class="fas fa-chevron-down ms-2"></i>
                </button>
                <ul class="dropdown-menu add-new-menu" aria-labelledby="sharedAddNewDropdownCustom">
                  <li>
                    <a class="dropdown-item js-direct-upload" href="#" id="sharedDirectUploadBtn">
                      <i class="fas fa-upload me-2"></i>{{ __('Upload File') }}
                    </a>
                  </li>
                  <li>
                    <a class="dropdown-item js-folder-upload" href="#" id="sharedFolderUploadBtn">
                      <i class="fas fa-folder me-2"></i>{{ __('Upload Folder') }}
                    </a>
                  </li>
                </ul>
              </div>

              <input type="file" id="sharedDirectInput" style="display:none;">
              <input type="file" id="sharedFolderInput" webkitdirectory directory multiple style="display:none;">
            @endif

            {{-- Context for the uploader --}}
            <input type="hidden" id="sharedUploadToken" value="{{ $token }}">
            <input type="hidden" id="sharedUploadParent" value="{{ request('folder', $current->shared_id) }}">
          @endif
        </div>

        <!-- Progress Bar Container -->
          <div id="uploadProgressContainer" class="upload-progress-container" style="display: none;">
              <div class="upload-progress-content">
                  <div class="upload-header">
                      <div class="upload-title">
                          <i class="fas fa-cloud-upload-alt upload-main-icon"></i>
                          <span>Uploading</span>
                      </div>
                      <button id="cancelUpload" class="btn-close-upload">
                          <i class="fas fa-times"></i>
                      </button>
                  </div>
                  
                  <div class="upload-item">
                      <div class="file-info">
                          <div class="file-icon-container">
                              {{-- <i id="fileTypeIcon" class="fas fa-file file-type-icon"></i> --}}
                              <div id="circularProgress" class="circular-progress">
                                  <svg class="progress-ring" width="40" height="40">
                                      <circle class="progress-ring-circle-bg" cx="20" cy="20" r="16"></circle>
                                      <circle id="progressRingCircle" class="progress-ring-circle" cx="20" cy="20" r="16"></circle>
                                  </svg>
                                  <span id="percentText" class="percent-text">0%</span>
                              </div>
                          </div>
                          <div class="file-details">
                              <div id="uploadFileName" class="file-name">Preparing...</div>
                              <div class="file-status">
                                  <span id="uploadStatus" class="upload-speed">0% uploaded</span>
                                  <span id="fileSize" class="file-size-text"></span>
                              </div>
                          </div>
                      </div>
                      <div class="progress-bar-container">
                          <div class="progress-bar-bg">
                              <div id="uploadProgressBar" class="progress-bar-fill" style="width: 0%"></div>
                          </div>
                      </div>
                  </div>
              </div>
          </div>

        {{-- Breadcrumbs --}}
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb mb-0">
                @foreach($breadcrumbs as $crumb)
                    @if ($loop->last)
                        <li class="breadcrumb-item active" aria-current="page">{{ $crumb->name }}</li>
                    @else
                        <li class="breadcrumb-item">
                            <a href="{{ route('user.shared.browse', ['token' => $token, 'folder' => $crumb->shared_id]) }}">{{ $crumb->name }}</a>
                        </li>
                    @endif
                @endforeach
            </ol>
        </nav>

        @if ($children->count() > 0)
            {{-- Bulk actions / select all --}}
            <div class="filemanager-actions mb-3" id="filemanagerActions">
                <div class="form-check p-0" data-select="{{ __('Select All') }}" data-unselect="{{ __('Unselect All') }}">
                    <input id="selectAll" type="checkbox" class="d-none filemanager-select-all" />
                    <label type="button" class="btn btn-secondary btn-sm" for="selectAll" id="selectAllLabel">
                        {{ __('Select All') }}
                    </label>
                </div>
                {{-- Bulk Move to Trash for this share --}}
                <form action="{{ route('user.shared.bulk-move-to-trash', $token) }}" method="POST" id="bulkMoveToTrashForm" class="ms-2 d-inline-block">
                    @csrf
                    <input id="filesSelectedInput" name="ids" type="hidden" value="">
                    @if(request('folder'))
                        <input type="hidden" name="folder" value="{{ request('folder') }}">
                    @endif
                    <button type="submit" class="btn btn-warning btn-sm">
                        <i class="fa fa-trash me-1"></i> {{ __('Move Selected to Trash') }}
                    </button>
                </form>
            </div>

            {{-- Header: search + filters + view toggle --}}
            <div class="file-manager-header mb-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                    <div class="d-flex flex-wrap align-items-center gap-2 w-100 w-md-auto">
                        {{-- Search inside this folder --}}
                        <div class="position-relative" style="min-width: 250px;">
                            <form method="GET" action="{{ route('user.shared.browse', ['token' => $token, 'folder' => $root->shared_id]) }}">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-transparent border-end-0">
                                        <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 24 24" height="14" width="14" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M18.031 16.6168L22.3137 20.8995L20.8995 22.3137L16.6168 18.031C15.0769 19.263 13.124 20 11 20C6.032 20 2 15.968 2 11C2 6.032 6.032 2 11 2C15.968 2 20 6.032 20 11C20 13.124 19.263 15.0769 18.031 16.6168ZM16.0247 15.8748C17.2475 14.6146 18 12.8956 18 11C18 7.1325 14.8675 4 11 4C7.1325 4 4 7.1325 4 11C4 14.8675 7.1325 18 11 18C12.8956 18 14.6146 17.2475 15.8748 16.0247L16.0247 15.8748Z"></path>
                                        </svg>
                                    </span>
                                    <input type="text"
                                           class="form-control search-input border-start-0"
                                           placeholder="{{ __('Search in this folder...') }}"
                                           name="search" autocomplete="off" id="fileSearch"
                                           value="{{ request('search') }}">
                                </div>
                            </form>
                        </div>

                        {{-- Filter toggle --}}
                        <button class="btn btn-outline-secondary btn-sm d-flex align-items-center px-2 py-1" type="button" id="filterToggle">
                            <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 24 24" class="me-1" height="14" width="14" xmlns="http://www.w3.org/2000/svg">
                                <path d="M21 4V6H20L15 13.5V22H9V13.5L4 6H3V4H21ZM6.4037 6L11 12.8944V20H13V12.8944L17.5963 6H6.4037Z"></path>
                            </svg>
                            <span id="filterButtonText">{{ __('Filter') }}</span>
                        </button>

                        {{-- Inline Filters --}}
                        <div id="inlineFilters" class="d-none d-flex align-items-center gap-2 flex-wrap">
                            {{-- Type --}}
                            <div class="filter-dropdown-container">
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle filter-dropdown-compact" type="button" id="typeFilterDropdown" data-bs-toggle="dropdown">
                                        <span id="typeFilterText">{{ __('Type') }}</span>
                                    </button>
                                    <ul class="dropdown-menu" id="typeFilterMenu">
                                        <li><a class="dropdown-item filter-type" href="#" data-type="">{{ __('All Types') }}</a></li>
                                        <li><a class="dropdown-item filter-type" href="#" data-type="folder">{{ __('Folders') }}</a></li>
                                        <li><a class="dropdown-item filter-type" href="#" data-type="image">{{ __('Images') }}</a></li>
                                        <li><a class="dropdown-item filter-type" href="#" data-type="document">{{ __('Documents') }}</a></li>
                                        <li><a class="dropdown-item filter-type" href="#" data-type="video">{{ __('Videos') }}</a></li>
                                        <li><a class="dropdown-item filter-type" href="#" data-type="audio">{{ __('Audio') }}</a></li>
                                        <li><a class="dropdown-item filter-type" href="#" data-type="archive">{{ __('Archives') }}</a></li>
                                        <li><a class="dropdown-item filter-type" href="#" data-type="other">{{ __('Others') }}</a></li>
                                    </ul>
                                </div>
                            </div>

                            {{-- Sort --}}
                            <div class="filter-dropdown-container">
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle filter-dropdown-compact" type="button" id="sortFilterDropdown" data-bs-toggle="dropdown">
                                        <span id="sortFilterText">{{ __('Sort') }}</span>
                                    </button>
                                    <ul class="dropdown-menu" id="sortFilterMenu">
                                        <li><a class="dropdown-item sort-item" href="#" data-sort="created_at" data-order="desc">{{ __('Newest') }}</a></li>
                                        <li><a class="dropdown-item sort-item" href="#" data-sort="created_at" data-order="asc">{{ __('Oldest') }}</a></li>
                                        <li><a class="dropdown-item sort-item" href="#" data-sort="name" data-order="asc">{{ __('A-Z') }}</a></li>
                                        <li><a class="dropdown-item sort-item" href="#" data-sort="name" data-order="desc">{{ __('Z-A') }}</a></li>
                                        <li><a class="dropdown-item sort-item" href="#" data-sort="size" data-order="desc">{{ __('Largest') }}</a></li>
                                        <li><a class="dropdown-item sort-item" href="#" data-sort="size" data-order="asc">{{ __('Smallest') }}</a></li>
                                    </ul>
                                </div>
                            </div>

                            {{-- Clear (JS toggles visibility) --}}
                            <button class="btn btn-danger btn-sm d-none align-items-center px-2 py-1" type="button" id="clearFilters">
                                <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 24 24" class="me-1" height="12" width="12" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"></path>
                                </svg>
                                <span>{{ __('Clear') }}</span>
                            </button>
                        </div>
                    </div>

                    {{-- View toggle --}}
                    <div class="d-flex align-items-center gap-2">
                        <div class="btn-group view-toggle-group" role="group">
                            <button type="button" class="btn btn-outline-secondary btn-sm view-toggle-btn" data-view="list" title="{{ __('List View') }}">
                                <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 24 24" height="16" width="16" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M11 4H21V6H11V4ZM11 8H17V10H11V8ZM11 14H21V16H11V14ZM11 18H17V20H11V18ZM3 4H9V10H3V4ZM5 6V8H7V6H5ZM3 14H9V20H3V14ZM5 16V18H7V16H5Z"></path>
                                </svg>
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm view-toggle-btn active" data-view="grid" title="{{ __('Grid View') }}">
                                <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 24 24" height="16" width="16" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M21 3C21.5523 3 22 3.44772 22 4V20C22 20.5523 21.5523 21 21 21H3C2.44772 21 2 20.5523 2 20V4C2 3.44772 2.44772 3 3 3H21ZM11 13H4V19H11V13ZM20 13H13V19H20V13ZM11 5H4V11H11V5ZM20 5H13V11H20V5Z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Listing container --}}
            <div id="fileContainer">
                {{-- GRID --}}
                <div id="gridView" class="row row-cols-1 row-cols-sm-2 row-cols-md-4 row-cols-xxl-5 g-3 mb-4">
                    @foreach($children as $item)
                        @php
                            // map your domain types into a friendlier bucket for filtering
                            $bucketType = $item->type === 'folder'
                                ? 'folder'
                                : (Str::contains($item->mime ?? $item->type, ['image']) ? 'image'
                                    : (Str::contains($item->mime ?? $item->type, ['pdf','doc','sheet','text','presentation']) ? 'document'
                                        : (Str::contains($item->mime ?? $item->type, ['video']) ? 'video'
                                            : (Str::contains($item->mime ?? $item->type, ['audio']) ? 'audio'
                                                : (Str::contains($item->mime ?? $item->type, ['zip','rar','7z']) ? 'archive' : 'other')))));

                            // permissions for actions in "shared browse" context
                            $isOwner   = (int)($share->owner_id ?? $share->file?->user_id) === (int)auth()->id();
                            $canEdit   = $share->canEdit(); // recipient has edit permission on this share?
                            $canReshare = $share->canReshare(); // recipient can reshare this file?
                            $canShare  = $isOwner || $canEdit || $canReshare; // can show share button?
                            // recipient can delete only the items they uploaded via THIS share
                            $canDelete = $isOwner || (
                                $canEdit
                                && (int)($item->uploaded_via_share_id ?? 0) === (int)$share->id
                                && $item->uploaded_by && (int)$item->uploaded_by === (int)auth()->id()
                            );
                        @endphp

                        <div class="col-12 file-item"
                            data-file-id="{{ $item->shared_id }}"
                            data-file-name="{{ $item->name }}"
                            data-file-type="{{ $bucketType }}"
                            data-file-size="{{ $item->size ?? 0 }}"
                            data-file-date="{{ $item->created_at }}"
                            data-permission="{{ $canEdit ? 'edit' : 'view' }}"
                            data-owner="{{ $owner?->name ?? $owner?->email ?? __('Unknown') }}"
                            data-preview-support="{{ $item->type === 'folder' ? '0' : '1' }}">
                            <div class="filemanager-file h-100">

                                {{-- Left actions --}}
                                <div class="filemanager-file-actions">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input file-checkbox"
                                              value="{{ $item->shared_id }}" id="grid_{{ $item->shared_id }}" />
                                    </div>

                                    <div class="dropdown">
                                        <a class="dropdown-toggle-custom" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <svg stroke="currentColor" fill="currentColor" stroke-width="0.5" viewBox="0 0 24 24" height="20" width="20" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M12 3c-.825 0-1.5.675-1.5 1.5S11.175 6 12 6s1.5-.675 1.5-1.5S12.825 3 12 3zm0 15c-.825 0-1.5.675-1.5 1.5S11.175 21 12 21s1.5-.675 1.5-1.5S12.825 18 12 18zM12 10.5c-.825 0-1.5.675-1.5 1.5S11.175 13.5 12 13.5s1.5-.675 1.5-1.5S12.825 10.5 12 10.5z"></path>
                                            </svg>
                                        </a>

                                        <ul class="dropdown-menu dropdown-menu-end">
                                            @if($item->type === 'folder')
                                                {{-- Open folder (always allowed for active share) --}}
                                                <li>
                                                    <a class="dropdown-item"
                                                      href="{{ route('user.shared.browse', ['token' => $token, 'folder' => $item->shared_id]) }}">
                                                        <i class="fa fa-folder-open me-2"></i>{{ __('Open folder') }}
                                                    </a>
                                                </li>

                                                {{-- (Optional) Owner-only folder controls could go here --}}

                                            @else
                                                {{-- FILE ACTIONS --}}

                                                {{-- Preview (if supported) --}}
                                                <li>
                                                    <a class="dropdown-item" target="_blank" href="{{ route('file.preview', $item->shared_id) }}">
                                                        <i class="fa fa-eye me-2"></i>{{ __('Preview') }}
                                                    </a>
                                                </li>

                                                {{-- Share (only if user has reshare/edit permission) --}}
                                                @if($canShare)
                                                <li>
                <a href="#"
                   class="dropdown-item fileManager-share-with-me"
                   data-bs-toggle="modal"
                   data-bs-target="#sharedWithMeModal"
                   data-file-id="{{ $item->shared_id }}"
                   data-file-name="{{ $item->name }}"
                   data-file-type="{{ $item->type }}"
                   data-share="{{ htmlspecialchars(json_encode([
                        'filename'      => $item->name,
                        'download_link' => null, // folders usually no direct download
                   ]), ENT_QUOTES, 'UTF-8') }}">
                    <i class="fas fa-user-friends me-2"></i>{{ __('Share') }}
                </a>
            </li>
                                                @endif


                                                {{-- Download --}}
                                                <li>
                                                    <a class="dropdown-item" target="_blank" href="{{ route('file.download', $item->shared_id) }}">
                                                        <i class="fa fa-download me-2"></i>{{ __('Download') }}
                                                    </a>
                                                </li>
                                                

                                                {{-- Edit details (owner OR recipient with edit permission) --}}
                                                @if($isOwner || $canEdit)
                                                    <li>
                                                        <a class="dropdown-item"
                                                          href="{{ route('user.shared.edit', ['token' => $token, 'shared_id' => $item->shared_id]) }}">
                                                            <i class="fa fa-edit me-2"></i>{{ __('Edit details') }}
                                                        </a>
                                                    </li>
                                                @endif

                                                {{-- Delete / Move to Trash --}}
                                                    <li>
                                                        <form action="{{ route('user.shared.delete', ['token' => $token, 'shared_id' => $item->shared_id]) }}"
                                                              method="POST">
                                                            @csrf
                                                            @method('DELETE')

                                                            {{-- keep subfolder context after redirect --}}
                                                            @if(request('folder'))
                                                                <input type="hidden" name="folder" value="{{ request('folder') }}">
                                                            @endif

                                                            <button class="dropdown-item text-danger confirm-action-form">
                                                                <i class="fa fa-trash-alt me-2"></i>{{ __('Move to Trash') }}
                                                            </button>
                                                        </form>
                                                    </li>
                                                    {{-- If you prefer POST instead of DELETE, use your "move-to-trash" route:
                                                    <li>
                                                        <form action="{{ route('user.shared.move-to-trash', ['token' => $token, 'shared_id' => $item->shared_id]) }}" method="POST">
                                                            @csrf
                                                            @if(request('folder'))
                                                                <input type="hidden" name="folder" value="{{ request('folder') }}">
                                                            @endif
                                                            <button class="dropdown-item text-warning confirm-action-form">
                                                                <i class="fa fa-trash-alt me-2"></i>{{ __('Move to Trash') }}
                                                            </button>
                                                        </form>
                                                    </li>
                                                    --}}
                                            @endif
                                        </ul>
                                    </div>
                                </div>

                                {{-- Icon + Title --}}
                                @if($item->type === 'folder')
                                    <a href="{{ route('user.shared.browse', ['token' => $token, 'folder' => $item->shared_id]) }}" class="filemanager-file-icon filemanager-link">
                                        <i class="fas fa-folder" style="font-size:48px;color:#ffc107;"></i>
                                    </a>
                                    <a href="{{ route('user.shared.browse', ['token' => $token, 'folder' => $item->shared_id]) }}" class="filemanager-file-title filemanager-link">
                                        {{ $item->name }}
                                    </a>
                                @else
                                    <a href="{{ route('file.preview', $item->shared_id) }}" target="_blank" class="filemanager-file-icon filemanager-link">
                                        <i class="far fa-file-alt" style="font-size:44px;"></i>
                                    </a>
                                    <a href="{{ route('file.preview', $item->shared_id) }}" target="_blank" class="filemanager-file-title filemanager-link">
                                        {{ $item->name }}
                                    </a>
                                @endif

                                {{-- Owner meta --}}
                                <div class="filemanager-file-info small text-muted mt-1">
                                    <div class="d-flex flex-column">
                                        <span>
                                            <strong>{{ __('Owner:') }}</strong>
                                            {{ $owner?->name ?? $owner?->email ?? __('Unknown') }}
                                        </span>
                                    </div>
                                </div>

                                {{-- Uploader (only when uploaded via this share by a non-owner) --}}
                                <div class="file-card">
                                    @if((int)($item->show_uploader ?? 0) === 1)
                                        <div class="small text-muted mt-1">
                                            {{ __('Uploaded by') }}:
                                            {{ (int)$item->uploaded_by === (int)auth()->id() ? __('You') : ($item->uploader_display_name ?? __('Unknown')) }}
                                        </div>
                                    @endif
                                </div>

                            </div>
                        </div>
                    @endforeach
                </div>
                {{-- LIST --}}
                <div id="listView" class="d-none">
                    <div class="list-group">
                        @foreach($children as $item)
                            @php
                                $bucketType = $item->type === 'folder'
                                    ? 'folder'
                                    : (Str::contains($item->mime ?? $item->type, ['image']) ? 'image'
                                        : (Str::contains($item->mime ?? $item->type, ['pdf','doc','sheet','text','presentation']) ? 'document'
                                            : (Str::contains($item->mime ?? $item->type, ['video']) ? 'video'
                                                : (Str::contains($item->mime ?? $item->type, ['audio']) ? 'audio'
                                                    : (Str::contains($item->mime ?? $item->type, ['zip','rar','7z']) ? 'archive' : 'other')))));
                            @endphp
                            <div class="list-group-item filemanager-file-list file-item"
                                 data-file-id="{{ $item->shared_id }}"
                                 data-file-name="{{ $item->name }}"
                                 data-file-type="{{ $bucketType }}"
                                 data-file-size="{{ $item->size ?? 0 }}"
                                 data-file-date="{{ $item->created_at }}"
                                 data-permission="view"
                                 data-owner="{{ $owner?->name ?? $owner?->email ?? __('Unknown') }}"
                                 data-preview-support="{{ $item->type === 'folder' ? '0' : '1' }}">

                                <div class="d-flex align-items-center">
                                    <div class="form-check me-3">
                                        <input type="checkbox" class="form-check-input file-checkbox"
                                               value="{{ $item->shared_id }}" id="list_{{ $item->shared_id }}" />
                                    </div>

                                    <div class="filemanager-file-icon-small me-3">
                                        @if($item->type === 'folder')
                                            <a href="{{ route('user.shared.browse', ['token' => $token, 'folder' => $item->shared_id]) }}"><i class="fas fa-folder" style="font-size:24px;color:#ffc107;"></i></a>
                                        @else
                                            <a href="{{ route('file.preview', $item->shared_id) }}" target="_blank"><i class="far fa-file-alt" style="font-size:22px;"></i></a>
                                        @endif
                                    </div>

                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">
                                            @if($item->type === 'folder')
                                                <a href="{{ route('user.shared.browse', ['token' => $token, 'folder' => $item->shared_id]) }}" class="filemanager-file-title filemanager-link">{{ $item->name }}</a>
                                            @else
                                                <a href="{{ route('file.preview', $item->shared_id) }}" target="_blank" class="filemanager-file-title filemanager-link">{{ $item->name }}</a>
                                            @endif
                                        </h6>
                                        <div class="text-muted small">
                                            <strong>{{ __('Owner:') }}</strong>
                                            {{ $owner?->name ?? $owner?->email ?? __('Unknown') }}
                                            &nbsp;•&nbsp; <strong>{{ __('Type:') }}</strong> {{ $item->type }}
                                            @if(!empty($item->size) && $item->type !== 'folder')
                                                &nbsp;•&nbsp; <strong>{{ __('Size:') }}</strong> {{ function_exists('formatBytes') ? formatBytes($item->size) : $item->size }}
                                            @endif
                                            &nbsp;•&nbsp; <strong>{{ __('Added:') }}</strong> {{ function_exists('vDate') ? vDate($item->created_at) : $item->created_at }}
                                        </div>
                                    </div>

                                    <div class="dropdown">
                                        <a class="dropdown-toggle-custom" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <svg stroke="currentColor" fill="currentColor" stroke-width="0.5" viewBox="0 0 24 24" height="20" width="20" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M12 3c-.825 0-1.5.675-1.5 1.5S11.175 6 12 6s1.5-.675 1.5-1.5S12.825 3 12 3zm0 15c-.825 0-1.5.675-1.5 1.5S11.175 21 12 21s1.5-.675 1.5-1.5S12.825 18 12 18zM12 10.5c-.825 0-1.5.675-1.5 1.5S11.175 13.5 12 13.5s1.5-.675 1.5-1.5S12.825 10.5 12 10.5z"></path>
                                            </svg>
                                        </a>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            @if($item->type === 'folder')
                                                <li>
                                                    <a class="dropdown-item"
                                                       href="{{ route('user.shared.browse', ['token' => $token, 'folder' => $item->shared_id]) }}">
                                                        <i class="fa fa-folder-open me-2"></i>{{ __('Open folder') }}
                                                    </a>
                                                </li>
                                            @else
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('file.preview', $item->shared_id) }}" target="_blank">
                                                        <i class="fa fa-eye me-2"></i>{{ __('Preview') }}
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('file.download', $item->shared_id) }}" target="_blank">
                                                        <i class="fa fa-download me-2"></i>{{ __('Download') }}
                                                    </a>
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

        @else
            <div class="alert alert-light mb-0">{{ __('This folder is empty.') }}</div>
        @endif
    </div>
@endsection



@if($share->permission === 'edit')
<script>
(function(){
  const token  = @json($token);
  const parent = @json(request('folder', $current->shared_id));
  const uploadUrl = @json(route('user.shared.upload', $token));
  const csrf = document.querySelector('meta[name="csrf-token"]').content;

  // ----- REGULAR MODE (Dropzone-like) -----
  @if($uploadMode === 'regular')
    // If you already have a global Dropzone init that binds to [data-dz-click],
    // you can keep using it. We just ensure it targets #sharedUploadForm when in this view.
    // Example minimal re-init (adjust to your Dropzone setup):

    if (window.Dropzone) {
      // Create a dedicated Dropzone if you don't have a global one
      let dz = new Dropzone(document.body, {
        url: uploadUrl,
        clickable: ".shared-upload-file-option",
        paramName: "file",
        params: { parent_folder_id: parent, upload_auto_delete: 0 },
        headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
        autoQueue: true,
        maxFilesize: 100, // MB, align with your server check
        init: function() {
          this.on("addedfile", (file) => {
            // send file size like your UploadController expects
            this.options.params.size = file.size;
          });
          this.on("success", () => { location.reload(); });
          this.on("error", (file, msg) => { console.error(msg); });
        }
      });
    }

    // Optional: folder upload button -> open hidden directory input (handled server-side as multiple)
    const folderBtn = document.getElementById('sharedFolderUploadBtn');
    const folderInput = document.getElementById('sharedFolderInput');
    if (folderBtn && folderInput) {
      folderBtn.addEventListener('click', (e) => { e.preventDefault(); folderInput.click(); });
      folderInput.addEventListener('change', () => {
        // If you support client-side folder traversal, push files to Dropzone
        if (window.Dropzone && folderInput.files) {
          const dz = Dropzone.forElement(document.body);
          Array.from(folderInput.files).forEach(f => dz.addFile(f));
        }
      });
    }

  @else
  // ----- CUSTOM MODE (direct upload with fetch) -----
    const openFilePicker = (input) => new Promise(res => { input.onchange = () => res(input.files); input.click(); });

    const directBtn = document.getElementById('sharedDirectUploadBtn');
    const directInput = document.getElementById('sharedDirectInput');
    const folderBtn  = document.getElementById('sharedFolderUploadBtn');
    const folderInput= document.getElementById('sharedFolderInput');

    if (directBtn && directInput) {
      directBtn.addEventListener('click', async (e) => {
        e.preventDefault();
        const files = await openFilePicker(directInput);
        if (!files || !files.length) return;

        const file = files[0];
        const fd = new FormData();
        fd.append('file', file);
        fd.append('parent_folder_id', parent);
        fd.append('upload_auto_delete', 0);
        fd.append('size', file.size);

        const res = await fetch(uploadUrl, {
          method: 'POST',
          headers: {'X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN': csrf},
          body: fd
        });
        const json = await res.json().catch(() => ({}));
        if (!res.ok || json.type === 'error') return alert(json.msg || 'Upload failed');
        location.reload();
      });
    }

    if (folderBtn && folderInput) {
      folderBtn.addEventListener('click', async (e) => {
        e.preventDefault();
        const files = await openFilePicker(folderInput);
        if (!files || !files.length) return;

        // Upload sequentially (keep it simple); switch to parallel if you prefer
        for (const file of files) {
          const fd = new FormData();
          fd.append('file', file);
          fd.append('parent_folder_id', parent);
          fd.append('upload_auto_delete', 0);
          fd.append('size', file.size);

          const res = await fetch(uploadUrl, {
            method: 'POST',
            headers: {'X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN': csrf},
            body: fd
          });
          const json = await res.json().catch(() => ({}));
          if (!res.ok || json.type === 'error') {
            alert((json && json.msg) ? json.msg : ('Upload failed: ' + (res.status || '')));
            break;
          }
        }
        location.reload();
      });
    }
  @endif
})();
</script>
@endif
@push('scripts')
<script>
(function () {
  // Prevent double init if template inserts script twice
  if (window.__SINGLE_UPLOAD_INIT__) return;
  window.__SINGLE_UPLOAD_INIT__ = true;

  // ---- Server bits (Blade) ----
  const uploadUrl = @json(route('user.shared.upload', $token));
  const parent    = @json(request('folder', $current->shared_id));
  const csrf      = document.querySelector('meta[name="csrf-token"]').content;

  // ---- Elements
  const directBtn   = document.getElementById('sharedDirectUploadBtn');
  const folderBtn   = document.getElementById('sharedFolderUploadBtn');
  const directInput = document.getElementById('sharedDirectInput');
  const folderInput = document.getElementById('sharedFolderInput');

  // Safety: stop Dropzone or other libs from auto-binding
  if (window.Dropzone) { window.Dropzone.autoDiscover = false; }

  // ---- Dialog open lock (prevents double-open)
  let pickerLock = false;
  function openPicker(input, originEl) {
    if (!input) return;
    if (pickerLock) return;
    pickerLock = true;
    try { input.value = ''; } catch(e){}
    closeDropdown(originEl);
    setTimeout(() => {
      if (typeof input.showPicker === 'function') input.showPicker();
      else input.click();
    }, 20);
  }
  function unlockPicker() { pickerLock = false; }

  function closeDropdown(originEl) {
    if (typeof bootstrap === 'undefined' || !originEl) return;
    const menu = originEl.closest('.dropdown-menu'); if (!menu) return;
    const toggleId = menu.getAttribute('aria-labelledby'); if (!toggleId) return;
    const toggleEl = document.getElementById(toggleId); if (!toggleEl) return;
    const dd = bootstrap.Dropdown.getInstance(toggleEl) || new bootstrap.Dropdown(toggleEl);
    dd.hide();
  }

  // ---- Button bindings (ONLY here)
  if (directBtn) directBtn.addEventListener('click', (e) => {
    e.preventDefault(); e.stopPropagation(); openPicker(directInput, directBtn);
  }, { passive: false });

  if (folderBtn) folderBtn.addEventListener('click', (e) => {
    e.preventDefault(); e.stopPropagation(); openPicker(folderInput, folderBtn);
  }, { passive: false });

  // ---- Input change handlers (ONLY here)
  if (directInput) directInput.addEventListener('change', async function () {
    unlockPicker();
    const files = Array.from(this.files || []);
    this.value = '';
    if (!files.length) return;
    await uploadFilesSequential(files);
  });

  if (folderInput) folderInput.addEventListener('change', async function () {
    unlockPicker();
    const files = Array.from(this.files || []);
    this.value = '';
    if (!files.length) return;
    await uploadFilesSequential(files);
  });

  // ---- Upload (XMLHttpRequest so we can show progress)
  async function uploadFilesSequential(files) {
    if (files.length) startUI(files[0]);
    for (let i = 0; i < files.length; i++) {
      try {
        await uploadOne(files[i]);
      } catch (err) {
        errorUI(err?.message || 'Upload failed');
        return;
      }
    }
    finishUI();
    location.reload();
  }

  function uploadOne(file) {
    return new Promise((resolve, reject) => {
      const fd = new FormData();
      fd.append('file', file);
      fd.append('parent_folder_id', parent);
      fd.append('upload_auto_delete', 0);
      fd.append('size', file.size);

      const xhr = new XMLHttpRequest();
      xhr.open('POST', uploadUrl, true);
      xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
      xhr.setRequestHeader('X-CSRF-TOKEN', csrf);

      xhr.upload.onprogress = (e) => {
        if (!e.lengthComputable) return;
        const pct = Math.round((e.loaded / e.total) * 100);
        updateUI(pct, `${pct}% uploaded`);
      };

      xhr.onload = () => {
        let json = {};
        try { json = JSON.parse(xhr.responseText || '{}'); } catch(e){}
        if (xhr.status >= 200 && xhr.status < 300 && json.type !== 'error') {
          updateUI(100, 'Upload complete');
          resolve();
        } else {
          reject(new Error(json.msg || `Upload failed (${xhr.status})`));
        }
      };

      xhr.onerror = () => reject(new Error('Network error'));
      xhr.send(fd);
    });
  }

  // ---- Progress UI (uses your existing DOM)
  function startUI(file) {
    const panel = document.getElementById('uploadProgressContainer');
    if (panel) panel.style.display = 'block';
    setText('uploadFileName', file?.name || 'Preparing...');
    setText('fileSize', formatSize(file?.size));
    setText('uploadStatus', '0% uploaded');
    setProgress(0);
  }
  function updateUI(percent, text) {
    setProgress(percent);
    if (text) setText('uploadStatus', text);
  }
  function finishUI() {
    setProgress(100);
    setText('uploadStatus', 'All done');
    setTimeout(() => {
      const panel = document.getElementById('uploadProgressContainer');
      if (panel) panel.style.display = 'none';
    }, 600);
  }
  function errorUI(msg) {
    setText('uploadStatus', msg || 'Upload failed');
    const panel = document.getElementById('uploadProgressContainer');
    if (panel) panel.style.display = 'none';
  }

  // ---- Small UI helpers
  function setText(id, txt) {
    const el = document.getElementById(id);
    if (el) el.textContent = txt || '';
  }
  function setProgress(pct) {
    pct = Math.max(0, Math.min(100, Math.round(pct)));
    const bar = document.getElementById('uploadProgressBar');
    const percentText = document.getElementById('percentText');
    const ring = document.getElementById('progressRingCircle');
    if (bar) bar.style.width = pct + '%';
    if (percentText) percentText.textContent = pct + '%';
    if (ring) {
      const r = 16, C = 2 * Math.PI * r;
      ring.style.strokeDasharray = C;
      ring.style.strokeDashoffset = C * (1 - pct / 100);
    }
  }
  function formatSize(bytes) {
    if (!Number.isFinite(bytes)) return '';
    const units = ['B','KB','MB','GB','TB'];
    let i = 0, n = bytes;
    while (n >= 1024 && i < units.length - 1) { n /= 1024; i++; }
    return n.toFixed(n < 10 && i > 0 ? 1 : 0) + ' ' + units[i];
  }

  // Optional: cancel button (no abort wiring here; add if you keep an xhr ref)
  const cancelBtn = document.getElementById('cancelUpload');
  if (cancelBtn) {
    cancelBtn.addEventListener('click', (e) => {
      e.preventDefault();
      errorUI('Upload canceled');
      unlockPicker();
    });
  }
})();
</script>

@endpush








@push('modals')
    {{-- Shared-With-Me / Share modal (same as owner side) --}}
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

                    <form id="swmForm" class="mb-0">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">
                                {{ __('Access status') }} <span class="text-danger">*</span>
                            </label>
                            <select id="swmAccessStatus" name="access_status"
                                    class="form-select form-select-sm" required>
                                <option value="0">{{ __('Private') }}</option>
                                <option value="1">{{ __('Public') }}</option>
                            </select>
                        </div>

                        <div id="swmPublicOptions" style="display: block;">
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
                                    <select id="swmPermission" name="permission"
                                            class="form-select form-select-sm">
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
                    <div id="swmPeopleSection" class="mt-4" style="display: none;">
                        <hr class="my-3">
                        <h6 class="mb-3">{{ __('People with access') }}</h6>
                        <div id="swmPeopleList" class="people-list"></div>
                    </div>

                    <div class="mb-3 mt-3" id="swmSocialIcons" style="display: none;">
                        <label class="form-label"><strong>{{ __('Share via') }}</strong></label>
                        <div class="share"></div>
                    </div>
                </div>

                <div class="modal-footer py-2">
                    <div id="swmCopyLinkGroup" class="me-auto" style="display: none;">
                        <button type="button" id="swmCopyLink" class="btn btn-primary btn-md">
                            <i class="far fa-clone me-1"></i>{{ __('Copy Link') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endpush




{{-- Share With Me script (jQuery optimized) --}}
@push('scripts')
<script>
// Share With Me - jQuery Optimized
(function($) {
    'use strict';
    if (typeof $ === 'undefined') return;

    var currentSharedId = null;
    var currentType = null;
    var currentDownloadLink = null;
    var clipboard = null;
    var csrfToken = $('meta[name="csrf-token"]').attr('content') || '';
    var baseUrl = '{{ url("user/files") }}';

    var MIN_CHARS_TO_SUGGEST = 1;
    var suggest = {
        items: [],
        open: false,
        activeIndex: -1,
        lastQuery: ''
    };

    function showAlert(msg, type) {
        type = type || 'success';
        var $box = $('#swmAlert');
        $box.removeClass('d-none alert-success alert-danger')
            .addClass('alert-' + (type === 'success' ? 'success' : 'danger'))
            .text(msg);
        setTimeout(function() { $box.addClass('d-none'); }, 3000);
    }

    function setTitle(fileName) {
        const span = $('#swmFileName');
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
        if (!res.ok) throw json.message || 'Request failed';
        return json;
    }

    function escapeHtml(str) {
        return (str || '').replace(/[&<>"']/g, (s) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[s]));
    }

    // Render people with access list including owner
    // Render people with access list including owner/uploader logic
    function renderPeopleList(people, ownerInfo, uploaderInfo = null) {
        const container = $('#swmPeopleList');
        const section   = $('#swmPeopleSection');

        // Hide section if nothing to show
        if ((!people || !Array.isArray(people) || people.length === 0) && !ownerInfo && !uploaderInfo) {
            section.style.display = 'none';
            container.innerHTML = '';
            return;
        }

        section.style.display = 'block';

        let peopleHtml = '';

        // Normalize owner/uploader data
        const ownerId    = ownerInfo?.id ?? null;
        const ownerEmail = ownerInfo?.email || '';
        const ownerName  = ownerInfo?.name  || ownerEmail;

        const uploaderId    = uploaderInfo?.id ?? null;
        const uploaderEmail = uploaderInfo?.email || '';
        const uploaderName  = uploaderInfo?.name  || uploaderEmail;

        const hasDifferentUploader =
            uploaderInfo &&
            ownerInfo &&
            uploaderId &&
            ownerId &&
            uploaderId !== ownerId;

        // 🔹 If uploader and owner are different:
        //    - show uploader as Owner
        //    - show original owner as Editor
        if (hasDifferentUploader) {
            // Uploader row as OWNER
            peopleHtml += `
                <div class="person-item d-flex align-items-center justify-content-between py-2 border-bottom">
                    <div class="d-flex align-items-center flex-grow-1">
                        <div class="avatar me-2">
                            <div class="bg-success rounded-circle d-flex align-items-center justify-content-center text-white"
                                style="width: 32px; height: 32px; font-size: 14px;">
                                ${uploaderName.charAt(0).toUpperCase()}
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-medium">${escapeHtml(uploaderName)}</div>
                            <div class="text-muted small">${escapeHtml(uploaderEmail)}</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="badge bg-success">Owner</span>
                    </div>
                </div>
            `;

            // Original owner row as EDITOR
            peopleHtml += `
                <div class="person-item d-flex align-items-center justify-content-between py-2 border-bottom">
                    <div class="d-flex align-items-center flex-grow-1">
                        <div class="avatar me-2">
                            <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center text-white"
                                style="width: 32px; height: 32px; font-size: 14px;">
                                ${ownerName.charAt(0).toUpperCase()}
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-medium">${escapeHtml(ownerName)}</div>
                            <div class="text-muted small">${escapeHtml(ownerEmail)}</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="badge bg-primary">Editor</span>
                    </div>
                </div>
            `;
        } else if (ownerInfo) {
            // 🔹 Normal case: single owner
            peopleHtml += `
                <div class="person-item d-flex align-items-center justify-content-between py-2 border-bottom">
                    <div class="d-flex align-items-center flex-grow-1">
                        <div class="avatar me-2">
                            <div class="bg-success rounded-circle d-flex align-items-center justify-content-center text-white"
                                style="width: 32px; height: 32px; font-size: 14px;">
                                ${ownerName.charAt(0).toUpperCase()}
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-medium">${escapeHtml(ownerName)}</div>
                            <div class="text-muted small">${escapeHtml(ownerEmail)}</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="badge bg-success">Owner</span>
                    </div>
                </div>
            `;
        }

        // 🔹 Existing shared people list (unchanged, except function signature)
        if (people && Array.isArray(people)) {
            people.forEach(person => {
                const email      = person.recipient_email || person.email || '';
                const name       = person.recipient_name || person.name || email;
                const permission = person.permission || 'view';
                const status     = person.status || 'active';
                const isActive   = status === 'active' && !person.revoked_at && !person.expired;

                // Permission badge
                let badgeClass = 'bg-primary text-white';
                let badgeText  = 'Viewer';

                if (permission === 'edit') {
                    badgeClass = 'bg-primary text-white';
                    badgeText  = 'Editor';
                } else if (permission === 'comment') {
                    badgeClass = 'bg-info';
                    badgeText  = 'Commenter';
                }

                // Status indicator
                let statusText  = '';
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
                                    style="width: 32px; height: 32px; font-size: 14px;">
                                    ${name.charAt(0).toUpperCase()}
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
                            </button>
                            ` : ''}
                        </div>
                    </div>
                `;
            });
        }

        container.innerHTML = peopleHtml;

        // Re-attach event listeners to newly rendered elements
        container.querySelectorAll('.remove-person').forEach(button => {
            button.addEventListener('click', function() {
                const shareId = this.getAttribute('data-share-id');
                removePersonAccess(shareId);
            });
        });

        container.querySelectorAll('.change-permission').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const shareId       = this.getAttribute('data-share-id');
                const newPermission = this.getAttribute('data-permission');
                updatePersonPermission(shareId, newPermission);
            });
        });
    }


    // Remove person access - DELETE record
    async function removePersonAccess(shareId) {
        if (!confirm('Are you sure you want to remove access for this person?')) {
            return;
        }

        try {
            const response = await api(`/user/shares/${shareId}/remove`, {
                method: 'DELETE'
            });

            showAlert(response.message || 'Access removed successfully', 'success');
            // Reload people list
            await loadPeopleWithAccess();
        } catch (err) {
            console.error('Remove access error:', err);
            showAlert(err.toString() || 'Failed to remove access', 'danger');
        }
    }

    // Update person permission
    async function updatePersonPermission(shareId, newPermission) {
        try {
            const response = await api(`/user/shares/${shareId}/update-permission`, {
                method: 'PUT',
                data: { permission: newPermission }
            });

            showAlert(response.message || 'Permission updated successfully', 'success');
            // Reload people list
            await loadPeopleWithAccess();
        } catch (err) {
            console.error('Update permission error:', err);
            showAlert(err.toString() || 'Failed to update permission', 'danger');
        }
    }

    // Load people with access including owner info
    async function loadPeopleWithAccess() {
        if (!currentSharedId) return;

        try {
            const response = await api(`{{ url('user/files') }}/${currentSharedId}/shared-people`);
            console.log('People with access response:', response); // Debug log

            const ownerInfo    = response.owner || null;
            const uploaderInfo = response.uploader || null;

            // Fallback for owner if API fails to send it for some reason
            const normalizedOwner = ownerInfo || {
                email: 'Current User',
                name: 'Owner'
            };

            renderPeopleList(response.people || [], normalizedOwner, uploaderInfo);
        } catch (err) {
            console.error('Failed to load people with access:', err);
            // Fallback: try to get user info from page
            const currentUser = await getCurrentUserInfo();
            renderPeopleList([], currentUser, null);
        }
    }

    // Get current user info - simplified version
    async function getCurrentUserInfo() {
        try {
            // Try to get user info from common Laravel patterns
            let userEmail = 'Current User';
            let userName = 'Owner';
            
            // Check if user data is available in window object (common in Laravel)
            if (window.Laravel && window.Laravel.user) {
                userEmail = window.Laravel.user.email || userEmail;
                userName = window.Laravel.user.name || userName;
            }
            
            // Check for meta tags
            const userEmailMeta = document.querySelector('meta[name="user-email"]');
            const userNameMeta = document.querySelector('meta[name="user-name"]');
            
            if (userEmailMeta) userEmail = userEmailMeta.content;
            if (userNameMeta) userName = userNameMeta.content;
            
            return {
                email: userEmail,
                name: userName
            };
        } catch (error) {
            console.error('Failed to get user info:', error);
            return {
                email: 'Current User',
                name: 'Owner'
            };
        }
    }

    function renderTypeahead(q) {
        const dd = $('#swmTypeahead');
        dd.innerHTML = '';

        const emailVal = (q || '').trim();
        const items = suggest.items || [];

        if (!items.length && !emailVal) {
            dd.classList.remove('show');
            suggest.open = false;
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
                selectSuggestion(idx);
            });
            dd.appendChild(el);
        });

        const looksEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailVal);
        const dup = items.find((x) => (x.email || '').toLowerCase() === emailVal.toLowerCase());
        if (looksEmail && !dup) {
            const invite = document.createElement('div');
            invite.className = 'dropdown-item invite';
            invite.innerHTML = `
                <span class="bi bi-person-plus"></span>
                <span>${escapeHtml(`Share with ${emailVal}`)}</span>
            `;
            invite.addEventListener('mousedown', (ev) => {
                ev.preventDefault();
                fillEmail(emailVal);
                closeTypeahead();
            });
            dd.appendChild(invite);
        }

        if (!items.length && !looksEmail && emailVal) {
            const nothing = document.createElement('div');
            nothing.className = 'nothing';
            nothing.textContent = '{{ __("No matches") }}';
            dd.appendChild(nothing);
        }

        dd.classList.add('show');
        suggest.open = true;
    }

    function openTypeahead() {
        $('#swmTypeahead').classList.add('show');
        suggest.open = true;
    }

    function closeTypeahead() {
        $('#swmTypeahead').classList.remove('show');
        suggest.open = false;
        suggest.activeIndex = -1;
    }

    function fillEmail(email) {
        $('#swmEmail').value = email;
    }

    function selectSuggestion(idx) {
        const it = suggest.items[idx];
        if (!it) return;
        fillEmail(it.email);
        closeTypeahead();
    }

    function debounce(fn, ms = 200) {
        let t;
        return (...args) => {
            clearTimeout(t);
            t = setTimeout(() => fn(...args), ms);
        };
    }

    const fetchSuggestions = debounce(async (q = '') => {
        try {
            const r = await api(`{{ url('user/shares/recipients') }}?limit=20&q=${encodeURIComponent(q)}`);
            suggest.items = Array.isArray(r.recipients) ? r.recipients : [];
            renderTypeahead(q);
        } catch (e) {
            suggest.items = [];
            renderTypeahead(q);
        }
    }, 200);

    function updateCopyLinkButton() {
        const accessStatus = $('#swmAccessStatus').value;
        const copyLinkGroup = $('#swmCopyLinkGroup');
        const copyButton = $('#swmCopyLink');

        console.log('updateCopyLinkButton:', {
            accessStatus,
            currentDownloadLink,
            hasDownloadLink: !!currentDownloadLink
        });

        if (accessStatus === '1') {
            copyLinkGroup.style.display = 'block';
            copyButton.disabled = false;
            console.log('Showing copy link button - Public access');
        } else {
            copyLinkGroup.style.display = 'none';
            copyButton.disabled = true;
            console.log('Hiding copy link button - Private access');
        }
    }

    async function copyToClipboard(text) {
        console.log('Attempting to copy:', text);
        if (!text || !text.trim()) {
            showAlert('{{ __("No link available to copy.") }}', 'danger');
            return false;
        }

        if (navigator.clipboard && window.isSecureContext) {
            try {
                await navigator.clipboard.writeText(text);
                showAlert('{{ __("Link copied to clipboard!") }}', 'success');
                return true;
            } catch (err) {
                console.error('Clipboard API error:', err);
            }
        }

        if (clipboard) {
            clipboard.destroy();
        }
        const tempInput = document.createElement('input');
        tempInput.style.position = 'absolute';
        tempInput.style.left = '-9999px';
        tempInput.value = text;
        document.body.appendChild(tempInput);

        clipboard = new ClipboardJS('#swmCopyLink', {
            target: () => tempInput,
        });

        return new Promise((resolve) => {
            clipboard.on('success', (e) => {
                showAlert('{{ __("Link copied to clipboard!") }}', 'success');
                e.clearSelection();
                document.body.removeChild(tempInput);
                resolve(true);
            });
            clipboard.on('error', (e) => {
                console.error('Clipboard.js error:', e);
                showAlert('{{ __("Failed to copy link. Please try again.") }}', 'danger');
                document.body.removeChild(tempInput);
                resolve(false);
            });
        });
    }

    async function updateAccessStatus() {
        if (!currentSharedId) {
            showAlert('{{ __("File ID is missing. Please close and try again.") }}', 'danger');
            return;
        }

        const accessStatus = $('#swmAccessStatus').value;
        const selectElement = $('#swmAccessStatus');
        selectElement.disabled = true;

        try {
            const response = await api(`/user/files/${currentSharedId}/update-status`, {
                method: 'POST',
                data: { access_status: accessStatus },
            });

            currentDownloadLink = response.download_link || '';
            $('#swmAccessStatus').value = response.access_status || accessStatus;
            
            updateCopyLinkButton();

            const statusText = accessStatus === '1' ? '{{ __("Public") }}' : '{{ __("Private") }}';
            showAlert(`{{ __("Status updated to") }} ${statusText}`, 'success');

        } catch (err) {
            console.error('Update status error:', err);
            showAlert(err.toString() || '{{ __("Failed to update access status. Please try again.") }}', 'danger');
            const currentStatus = $('#swmAccessStatus').value;
            $('#swmAccessStatus').value = currentStatus === '1' ? '0' : '1';
            updateCopyLinkButton();
        } finally {
            selectElement.disabled = false;
        }
    }

    document.getElementById('sharedWithMeModal').addEventListener('show.bs.modal', async function (event) {
        const button = event.relatedTarget;
        currentSharedId = button.getAttribute('data-file-id');
        currentType = (button.getAttribute('data-file-type') || 'file').toLowerCase();
        const fileName = button.getAttribute('data-file-name');
        const shareData = button.getAttribute('data-share');

        console.log('Modal opened:', { currentSharedId });

        setTitle(fileName);
        $('#swmForm').reset();
        $('#swmPermission').value = 'view';
        $('#swmEmail').value = '';
        closeTypeahead();

        try {
            const response = await api(`{{ url('user/files') }}/${currentSharedId}`);
            const accessStatus = response.access_status || '0';
            const downloadLink = response.download_link || '';

            console.log('Initial file data:', { accessStatus, downloadLink });

            $('#swmAccessStatus').value = accessStatus;
            currentDownloadLink = downloadLink;
            
            updateCopyLinkButton();

            // Load people with access
            await loadPeopleWithAccess();

        } catch (e) {
            console.error('Failed to load file data:', e);
            try {
                const parsedShareData = shareData ? JSON.parse(shareData) : {};
                currentDownloadLink = parsedShareData.download_link || '';
                console.log('Fallback to data-share:', { currentDownloadLink });
            } catch (parseError) {
                console.error('Failed to parse data-share:', parseError);
                currentDownloadLink = '';
            }
            
            $('#swmAccessStatus').value = '0';
            updateCopyLinkButton();
            renderPeopleList([]);
        }

        const copyButton = $('#swmCopyLink');
        copyButton.onclick = () => {
            if (currentDownloadLink) {
                copyToClipboard(currentDownloadLink);
            } else {
                showAlert('{{ __("No download link available.") }}', 'danger');
            }
        };
    });

    document.getElementById('sharedWithMeModal').addEventListener('hidden.bs.modal', function () {
        if (clipboard) {
            clipboard.destroy();
            clipboard = null;
        }
        currentSharedId = null;
        currentDownloadLink = '';
        console.log('Modal closed');
    });

    const emailInput = $('#swmEmail');
    let focusFromPointer = false;
    emailInput.addEventListener('pointerdown', () => {
        focusFromPointer = true;
    });
    emailInput.addEventListener('focus', () => {
        if (focusFromPointer) {
            fetchSuggestions('');
            openTypeahead();
        }
        focusFromPointer = false;
    });

    emailInput.addEventListener('input', (e) => {
        const q = e.target.value || '';
        suggest.lastQuery = q;

        if (q.trim().length >= MIN_CHARS_TO_SUGGEST) {
            fetchSuggestions(q);
            openTypeahead();
        } else {
            if (!focusFromPointer) closeTypeahead();
        }
    });

    emailInput.addEventListener('keydown', (e) => {
        if (!suggest.open && e.key === 'ArrowDown') {
            const q = (emailInput.value || '').trim();
            if (q.length >= MIN_CHARS_TO_SUGGEST) {
                e.preventDefault();
                fetchSuggestions(q);
                openTypeahead();
            }
            return;
        }

        if (!suggest.open) return;

        const count = suggest.items.length;
        if (['ArrowDown', 'ArrowUp', 'Enter', 'Escape', 'Tab'].includes(e.key)) e.preventDefault();

        if (e.key === 'ArrowDown') {
            if (count === 0) return;
            suggest.activeIndex = (suggest.activeIndex + 1) % count;
            renderTypeahead(suggest.lastQuery);
        } else if (e.key === 'ArrowUp') {
            if (count === 0) return;
            suggest.activeIndex = (suggest.activeIndex - 1 + count) % count;
            renderTypeahead(suggest.lastQuery);
        } else if (e.key === 'Enter' || e.key === 'Tab') {
            if (suggest.activeIndex >= 0 && suggest.activeIndex < count) {
                selectSuggestion(suggest.activeIndex);
            } else {
                closeTypeahead();
            }
        } else if (e.key === 'Escape') {
            closeTypeahead();
        }
    });

    emailInput.addEventListener('blur', () => {
        setTimeout(closeTypeahead, 120);
    });

    $('#swmAccessStatus').addEventListener('change', updateAccessStatus);

    $('#swmForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        if (!currentSharedId) {
            showAlert('{{ __("File ID is missing. Please close and try again.") }}', 'danger');
            return;
        }

        const accessStatus = $('#swmAccessStatus').value;
        const email = (emailInput.value || '').trim();
        const submitBtn = e.target.querySelector('button[type="submit"]');

        if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            showAlert('{{ __("Please enter a valid email address.") }}', 'danger');
            emailInput.focus();
            return;
        }

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> {{ __("Sharing...") }}';
        }

        try {
            const fd = new FormData(e.target);
            fd.set('access_status', accessStatus);
            if (email) {
                fd.set('recipients', email);
                fd.set('permission', $('#swmPermission')?.value ?? 'view');
            }

            const response = await api(`{{ url('user/files') }}/${currentSharedId}/share`, {
                method: 'POST',
                data: fd,
            });

            if (response.download_link) {
                currentDownloadLink = response.download_link;
            }

            showAlert(response.message, 'success');

            if (email) {
                emailInput.value = '';
                closeTypeahead();
            }

            updateCopyLinkButton();
            
            // Reload people list after sharing
            await loadPeopleWithAccess();
            
        } catch (err) {
            console.error('Share error:', err);
            showAlert(err.toString() || '{{ __("Sharing failed. Please try again.") }}', 'danger');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = '{{ __("Share") }}';
            }
        }
    });
})();
</script>
@endpush