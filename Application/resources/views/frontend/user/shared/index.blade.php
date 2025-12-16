{{-- resources/views/frontend/user/shared/index.blade.php --}}
@extends('frontend.user.layouts.dash')

@section('section', lang('User', 'user'))
@section('title', lang('Shared with me', 'files'))
@section('upload', false)
@section('search', true)

@section('content')
    @if ($shares->count() > 0)

        {{-- Top Actions (Select all for bulk operations later if you add them) --}}
        <div class="filemanager-actions" id="filemanagerActions">
            <div class="form-check p-0" data-select="{{ lang('Select All', 'files') }}"
                 data-unselect="{{ lang('Unselect All', 'files') }}">
                <input id="selectAll" type="checkbox" class="d-none filemanager-select-all" />
                <label type="button" class="btn btn-secondary btn-md" for="selectAll" id="selectAllLabel">
                    {{ lang('Select All', 'files') }}
                </label>
            </div>
        </div>

        {{-- Header: search + filters + view toggle --}}
        <div class="file-manager-header mb-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                <div class="d-flex flex-wrap align-items-center gap-2 w-100 w-md-auto">

                    {{-- Search (client or server — preserve existing query if any) --}}
                    <div class="position-relative" style="min-width: 250px;">
                        <form method="GET" action="{{ route('user.shared.index') }}">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-transparent border-end-0">
                                    <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 24 24" height="14" width="14" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M18.031 16.6168L22.3137 20.8995L20.8995 22.3137L16.6168 18.031C15.0769 19.263 13.124 20 11 20C6.032 20 2 15.968 2 11C2 6.032 6.032 2 11 2C15.968 2 20 6.032 20 11C20 13.124 19.263 15.0769 18.031 16.6168ZM16.0247 15.8748C17.2475 14.6146 18 12.8956 18 11C18 7.1325 14.8675 4 11 4C7.1325 4 4 7.1325 4 11C4 14.8675 7.1325 18 11 18C12.8956 18 14.6146 17.2475 15.8748 16.0247L16.0247 15.8748Z"></path>
                                    </svg>
                                </span>
                                <input type="text" class="form-control search-input border-start-0" placeholder="{{ __('Search shared files...') }}"
                                       name="search" autocomplete="off" id="fileSearch" value="{{ request('search') }}">
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

                        {{-- Permission --}}
                        <div class="filter-dropdown-container">
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary btn-sm dropdown-toggle filter-dropdown-compact" type="button" id="permFilterDropdown" data-bs-toggle="dropdown">
                                    <span id="permFilterText">{{ __('Permission') }}</span>
                                </button>
                                <ul class="dropdown-menu" id="permFilterMenu">
                                    <li><a class="dropdown-item filter-perm" href="#" data-perm="">{{ __('All') }}</a></li>
                                    <li><a class="dropdown-item filter-perm" href="#" data-perm="view">{{ __('View') }}</a></li>
                                    <li><a class="dropdown-item filter-perm" href="#" data-perm="edit">{{ __('Edit') }}</a></li>
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
                    </div>

                    {{-- Clear (only when active; JS will toggle) --}}
                    <button class="btn btn-danger btn-sm d-none align-items-center px-2 py-1" type="button" id="clearFilters">
                        <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 24 24" class="me-1" height="12" width="12" xmlns="http://www.w3.org/2000/svg">
                            <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"></path>
                        </svg>
                        <span>{{ __('Clear') }}</span>
                    </button>
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

        {{-- Listing --}}
        <div id="fileContainer">
            {{-- GRID --}}
            <div id="gridView" class="row row-cols-1 row-cols-sm-2 row-cols-md-4 row-cols-xxl-5 g-3 mb-4">
                @foreach ($shares as $s)
                    @php
                        $file = $s->file;
                    @endphp

                    {{-- skip if file missing (stale share row) --}}
                    @if (!$file)
                        @continue
                    @endif

                    @php
                        $perm        = strtolower($s->permission ?? 'view');
                        $canView     = true;
                        $canDownload = (bool) ($s->can_download ?? false);
                        $canReshare  = (bool) ($s->can_reshare  ?? false);
                        $canEdit     = ($perm === 'edit');
                        $isOwner     = (int)($s->owner_id ?? 0) === (int)auth()->id();
                        $ownerName   = $file->uploader
                            ? ($file->uploader->name
                                ?? trim(($file->uploader->firstname ?? '').' '.($file->uploader->lastname ?? ''))
                                ?: $file->uploader->email)
                            : ($file->owner->name
                                ?? trim(($file->owner->firstname ?? '').' '.($file->owner->lastname ?? ''))
                                ?: $file->owner->email);
                    @endphp

                    <div class="col-12 file-item"
                        data-file-id="{{ $file->shared_id }}"
                        data-file-name="{{ $file->name }}"
                        data-file-type="{{ $file->type }}"
                        data-file-size="{{ $file->size ?? 0 }}"
                        data-file-date="{{ $file->created_at }}"
                        data-permission="{{ $perm }}"
                        data-owner="{{ $ownerName }}"
                        data-preview-support="{{ isFileSupportPreview($file->type) ? '1' : '0' }}">
                        <div class="filemanager-file h-100">

                            {{-- Left actions --}}
                            <div class="filemanager-file-actions">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input file-checkbox"
                                        value="{{ $file->shared_id }}" id="grid_{{ $file->shared_id }}" />
                                </div>

                                <div class="dropdown">
                                    <a class="dropdown-toggle-custom" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <svg stroke="currentColor" fill="currentColor" stroke-width="0.5" viewBox="0 0 24 24" height="20" width="20" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M12 3c-.825 0-1.5.675-1.5 1.5S11.175 6 12 6s1.5-.675 1.5-1.5S12.825 3 12 3zm0 15c-.825 0-1.5.675-1.5 1.5S11.175 21 12 21s1.5-.675 1.5-1.5S12.825 18 12 18zM12 10.5c-.825 0-1.5.675-1.5 1.5S11.175 13.5 12 13.5s1.5-.675 1.5-1.5S12.825 10.5 12 10.5z"></path>
                                        </svg>
                                    </a>

                                    <ul class="dropdown-menu dropdown-menu-end">
                                        @if($file->type === 'folder')
                                            {{-- Open folder (always allowed for active share) --}}
                                            <li>
                                                <a class="dropdown-item" href="{{ route('user.shared.open', $s->token) }}">
                                                    <i class="fa fa-folder-open me-2"></i>{{ __('Open folder') }}
                                                </a>
                                            </li>
                                            {{-- Shared hard/soft delete (policy decides) --}}
                                            <li>
                                            <form action="{{ route('user.shared.delete', ['token' => $s->token, 'shared_id' => $file->shared_id]) }}" method="POST">
                                                @csrf @method('DELETE')
                                                @if(request()->has('folder'))
                                                    <input type="hidden" name="folder" value="{{ request('folder') }}">
                                                @endif
                                                <button class="dropdown-item text-danger confirm-action-form">
                                                    <i class="fa fa-trash-alt me-2"></i>{{ __('Delete') }}
                                                </button>
                                            </form>
                                            </li>

                                            {{-- Reshare (only if allowed) --}}
                                            @if($canReshare)
                                                <li>
                                                    <a href="#"
                                                    class="dropdown-item fileManager-share-with-me"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#sharedWithMeModal"
                                                    data-file-id="{{ $file->shared_id }}"
                                                    data-file-name="{{ $file->name }}"
                                                    data-file-type="{{ $file->type }}">
                                                        <i class="fas fa-user-friends me-2"></i>{{ __('Share') }}
                                                    </a>
                                                </li>
                                            @endif

                                            {{-- Owner-only folder controls --}}
                                            @if($isOwner)
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('user.files.edit', $file->shared_id) }}">
                                                        <i class="fa fa-edit me-2"></i>{{ __('Rename folder') }}
                                                    </a>
                                                </li>
                                                <li>
                                                    <form action="{{ route('user.files.move-to-trash', $file->shared_id) }}" method="POST">
                                                        @csrf
                                                        <button class="dropdown-item text-warning confirm-action-form">
                                                            <i class="fa fa-trash-alt me-2"></i>{{ __('Move to Trash') }}
                                                        </button>
                                                    </form>
                                                </li>
                                            @endif

                                            {{-- If you support recipient edits for folders, add UI here when $canEdit && !$isOwner --}}

                                        @else
                                            {{-- FILE ACTIONS --}}

                                            {{-- Preview (if supported) --}}
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('file.preview', $file->shared_id) }}" target="_blank">
                                                        <i class="fa fa-eye me-2"></i>{{ __('Preview') }}
                                                    </a>
                                                </li>

                                            {{-- Download (only if can_download) --}}
                                            @if ($canDownload)
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('file.download', $file->shared_id) }}" target="_blank">
                                                        <i class="fa fa-download me-2"></i>{{ __('Download') }}
                                                    </a>
                                                </li>
                                            @endif

                                            {{-- Reshare (only if can_reshare) --}}
                                            @if ($canReshare)
                                                <li>
                                                    <a href="#"
                                                    class="dropdown-item fileManager-share-file"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#shareModal"
                                                    data-preview="{{ isFileSupportPreview($file->type) ? 'true' : 'false' }}"
                                                    data-share='{"filename":"{{ $file->name }}","download_link":"{{ route('file.download', $file->shared_id) }}","preview_link":"{{ route('file.preview', $file->shared_id) }}"}'>
                                                        <i class="fas fa-share-alt me-2"></i>{{ __('Share') }}
                                                    </a>
                                                </li>
                                            @endif
                                            @if ($canEdit && !$isOwner)
                                            <li>
                                                <a href="#"
                                                class="dropdown-item fileManager-share-with-me"
                                                data-bs-toggle="modal"
                                                data-bs-target="#sharedWithMeModal"
                                                data-file-id="{{ $file->shared_id }}"
                                                data-file-name="{{ $file->name }}"
                                                data-file-type="{{ $file->type }}"
                                                data-share="{{ htmlspecialchars(json_encode([
                                                        'filename' => $file->name,
                                                        'download_link' => route('file.download', $file->shared_id),
                                                        'preview_link' => route('file.preview', $file->shared_id),
                                                ]), ENT_QUOTES, 'UTF-8') }}">
                                                    <i class="fas fa-user-friends me-2"></i>{{ __('Share') }}
                                                </a>
                                            </li>


                                                <li>
                                                    <a class="dropdown-item" href="{{ route('user.shared.edit', [$s->token, $file->shared_id]) }}">
                                                        <i class="fa fa-edit me-2"></i>{{ __('Edit Details') }}
                                                    </a>
                                                </li>
                                                {{-- Shared hard/soft delete (policy decides) --}}
                                                <li>
                                                <form action="{{ route('user.shared.delete', ['token' => $s->token, 'shared_id' => $file->shared_id]) }}" method="POST">
                                                    @csrf @method('DELETE')
                                                    @if(request()->has('folder'))
                                                        <input type="hidden" name="folder" value="{{ request('folder') }}">
                                                    @endif
                                                    <button class="dropdown-item text-danger confirm-action-form">
                                                        <i class="fa fa-trash-alt me-2"></i>{{ __('Delete') }}
                                                    </button>
                                                </form>
                                                </li>

                                            @endif

                                            {{-- Owner-only file controls --}}
                                            @if ($isOwner)
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('user.files.edit', $file->shared_id) }}">
                                                        <i class="fa fa-edit me-2"></i>{{ __('Edit details') }}
                                                    </a>
                                                </li>
                                                <li>
                                                    <form action="{{ route('user.files.move-to-trash', $file->shared_id) }}" method="POST">
                                                        @csrf
                                                        <button class="dropdown-item text-warning confirm-action-form">
                                                            <i class="fa fa-trash-alt me-2"></i>{{ __('Move to Trash') }}
                                                        </button>
                                                    </form>
                                                </li>

                                            @endif

                                            {{-- If you support recipient edits for files, add UI here when $canEdit && !$isOwner --}}
                                        @endif
                                    </ul>
                                </div>
                            </div>

                            {{-- Icon + Title --}}
                            @if($file->type === 'folder')
                                <a href="{{ route('user.shared.open', $s->token) }}" class="filemanager-file-icon filemanager-link">
                                    <i class="fas fa-folder" style="font-size:48px;color:#ffc107;"></i>
                                </a>
                                <a href="{{ route('user.shared.open', $s->token) }}" class="filemanager-file-title filemanager-link">
                                    {{ $file->name }}
                                </a>
                            @else
                                <a href="{{ route('file.preview', $file->shared_id) }}" target="_blank" class="filemanager-file-icon filemanager-link">
                                    <i class="far fa-file-alt" style="font-size:44px;"></i>
                                </a>
                                <a href="{{ route('file.preview', $file->shared_id) }}" target="_blank" class="filemanager-file-title filemanager-link">
                                    {{ $file->name }}
                                </a>
                            @endif

                            {{-- Meta line --}}
                            <div class="filemanager-file-info small text-muted mt-1">
                                <div class="d-flex flex-column">
                                    <span><strong>{{ __('Owner:') }}</strong> {{ $ownerName }}</span>
                                    <span><strong>{{ __('Permission:') }}</strong> {{ ucfirst($perm) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- LIST --}}
            <div id="listView" class="d-none">
                <div class="list-group">
                    @foreach ($shares as $s)
                        @php
                            $file = $s->file;
                        @endphp

                        {{-- skip if file missing (stale share row) --}}
                        @if (!$file)
                            @continue
                        @endif

                        @php
                            $perm        = strtolower($s->permission ?? 'view');
                            $canView     = true;
                            $canDownload = (bool) ($s->can_download ?? false);
                            $canReshare  = (bool) ($s->can_reshare  ?? false);
                            $canEdit     = ($perm === 'edit');
                            $isOwner     = (int)($s->owner_id ?? 0) === (int)auth()->id();
                            $ownerName   = $file->uploader
                                ? ($file->uploader->name
                                    ?? trim(($file->uploader->firstname ?? '').' '.($file->uploader->lastname ?? ''))
                                    ?: $file->uploader->email)
                                : ($file->owner->name
                                    ?? trim(($file->owner->firstname ?? '').' '.($file->owner->lastname ?? ''))
                                    ?: $file->owner->email);
                        @endphp

                        <div class="list-group-item filemanager-file-list file-item"
                            data-file-id="{{ $file->shared_id }}"
                            data-file-name="{{ $file->name }}"
                            data-file-type="{{ $file->type }}"
                            data-file-size="{{ $file->size ?? 0 }}"
                            data-file-date="{{ $file->created_at }}"
                            data-permission="{{ $perm }}"
                            data-owner="{{ $ownerName }}"
                            data-preview-support="{{ isFileSupportPreview($file->type) ? '1' : '0' }}">

                            <div class="d-flex align-items-center">
                                <div class="form-check me-3">
                                    <input type="checkbox" class="form-check-input file-checkbox"
                                        value="{{ $file->shared_id }}" id="list_{{ $file->shared_id }}" />
                                </div>

                                <div class="filemanager-file-icon-small me-3">
                                    @if($file->type === 'folder')
                                        <a href="{{ route('user.shared.open', $s->token) }}"><i class="fas fa-folder" style="font-size:24px;color:#ffc107;"></i></a>
                                    @else
                                        <a href="{{ route('file.preview', $file->shared_id) }}" target="_blank"><i class="far fa-file-alt" style="font-size:22px;"></i></a>
                                    @endif
                                </div>

                                <div class="flex-grow-1">
                                    <h6 class="mb-1">
                                        @if($file->type === 'folder')
                                            <a href="{{ route('user.shared.open', $s->token) }}" class="filemanager-file-title filemanager-link">{{ $file->name }}</a>
                                        @else
                                            <a href="{{ route('file.preview', $file->shared_id) }}" target="_blank" class="filemanager-file-title filemanager-link">{{ $file->name }}</a>
                                        @endif
                                    </h6>
                                    <div class="text-muted small">
                                        <strong>{{ __('Owner:') }}</strong> {{ $ownerName }}
                                        &nbsp;•&nbsp; <strong>{{ __('Permission:') }}</strong> {{ ucfirst($perm) }}
                                        @if(!empty($file->size) && $file->type !== 'folder')
                                            &nbsp;•&nbsp; <strong>{{ __('Size:') }}</strong> {{ formatBytes($file->size) }}
                                        @endif
                                        &nbsp;•&nbsp; <strong>{{ __('Shared on:') }}</strong> {{ vDate($file->created_at) }}
                                    </div>
                                </div>

                                <div class="dropdown">
                                    <a class="dropdown-toggle-custom" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <svg stroke="currentColor" fill="currentColor" stroke-width="0.5" viewBox="0 0 24 24" height="20" width="20" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M12 3c-.825 0-1.5.675-1.5 1.5S11.175 6 12 6s1.5-.675 1.5-1.5S12.825 3 12 3zm0 15c-.825 0-1.5.675-1.5 1.5S11.175 21 12 21s1.5-.675 1.5-1.5S12.825 18 12 18zM12 10.5c-.825 0-1.5.675-1.5 1.5S11.175 13.5 12 13.5s1.5-.675 1.5-1.5S12.825 10.5 12 10.5z"></path>
                                        </svg>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        @if($file->type === 'folder')
                                            <li>
                                                <a class="dropdown-item" href="{{ route('user.shared.open', $s->token) }}">
                                                    <i class="fa fa-folder-open me-2"></i>{{ __('Open folder') }}
                                                </a>
                                            </li>

                                            @if($canReshare)
                                                <li>
                                                    <a href="#"
                                                    class="dropdown-item fileManager-share-with-me"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#sharedWithMeModal"
                                                    data-file-id="{{ $file->shared_id }}"
                                                    data-file-name="{{ $file->name }}"
                                                    data-file-type="{{ $file->type }}">
                                                        <i class="fas fa-user-friends me-2"></i>{{ __('Share') }}
                                                    </a>
                                                </li>
                                            @endif

                                            @if($isOwner)
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('user.files.edit', $file->shared_id) }}">
                                                        <i class="fa fa-edit me-2"></i>{{ __('Rename folder') }}
                                                    </a>
                                                </li>
                                                <li>
                                                    <form action="{{ route('user.files.move-to-trash', $file->shared_id) }}" method="POST">
                                                        @csrf
                                                        <button class="dropdown-item text-warning confirm-action-form">
                                                            <i class="fa fa-trash-alt me-2"></i>{{ __('Move to Trash') }}
                                                        </button>
                                                    </form>
                                                </li>
                                            @endif
                                        @else
                                            @if (isFileSupportPreview($file->type) && $canView)
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('file.preview', $file->shared_id) }}" target="_blank">
                                                        <i class="fa fa-eye me-2"></i>{{ __('Preview') }}
                                                    </a>
                                                </li>
                                            @endif

                                            @if ($canDownload)
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('file.download', $file->shared_id) }}" target="_blank">
                                                        <i class="fa fa-download me-2"></i>{{ __('Download') }}
                                                    </a>
                                                </li>
                                            @endif

                                            @if ($canReshare)
                                                <li>
                                                    <a href="#"
                                                    class="dropdown-item fileManager-share-file"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#shareModal"
                                                    data-preview="{{ isFileSupportPreview($file->type) ? 'true' : 'false' }}"
                                                    data-share='{"filename":"{{ $file->name }}","download_link":"{{ route('file.download', $file->shared_id) }}","preview_link":"{{ route('file.preview', $file->shared_id) }}"}'>
                                                        <i class="fas fa-share-alt me-2"></i>{{ __('Share') }}
                                                    </a>
                                                </li>
                                            @endif

                                            @if ($isOwner)
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('user.files.edit', $file->shared_id) }}">
                                                        <i class="fa fa-edit me-2"></i>{{ __('Edit details') }}
                                                    </a>
                                                </li>
                                                <li>
                                                    <form action="{{ route('user.files.move-to-trash', $file->shared_id) }}" method="POST">
                                                        @csrf
                                                        <button class="dropdown-item text-warning confirm-action-form">
                                                            <i class="fa fa-trash-alt me-2"></i>{{ __('Move to Trash') }}
                                                        </button>
                                                    </form>
                                                </li>
                                            @endif
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
        <p class="text-muted">{{ __('Nothing here yet.') }}</p>
    @endif
@endsection



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




{{-- Share With Me script --}}
@push('scripts')
<script>
// Share With Me
(function () {
    const $ = (s, c = document) => c.querySelector(s);

    let currentSharedId = null;
    let currentType = null;
    let currentDownloadLink = null;
    let clipboard = null;

    const MIN_CHARS_TO_SUGGEST = 1;
    let suggest = {
        items: [],
        open: false,
        activeIndex: -1,
        lastQuery: '',
    };

    function showAlert(msg, type = 'success') {
        const box = $('#swmAlert');
        box.className = 'alert py-2 px-3 alert-' + (type === 'success' ? 'success' : 'danger');
        box.textContent = msg;
        box.classList.remove('d-none');
        setTimeout(() => box.classList.add('d-none'), 3000);
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