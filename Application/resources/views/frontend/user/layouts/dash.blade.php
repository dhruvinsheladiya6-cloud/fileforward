<!DOCTYPE html>
<html lang="{{ getLang() }}">

<head>
    @include('frontend.user.includes.head')
    @include('frontend.user.includes.styles')
</head>

@include('frontend.includes.adblock-detection')

<body>
    <div class="dash">
        <div class="dash-navbar">
            <div class="logo">
                <a href="{{ url('/') }}">
                    <img src="{{ asset($settings['website_light_logo']) }}" alt="{{ $settings['website_name'] }}" />
                </a>
            </div>
            <div class="dash-navbar-content">
                <div class="dash-nav-link dash-sidebar-btn">
                    <i class="fa fa-bars"></i>
                </div>
                @hasSection('search')
                    <div class="search">
                        <div class="search-input">
                            <label for="search" class="search-icon">
                                <i class="fa fa-search"></i>
                            </label>
                            <form action="{{ url()->current() }}" method="GET">
                                <input id="search" type="text" name="search"
                                    placeholder="{{ lang('Type to search', 'user') }}"
                                    value="{{ request()->search ?? '' }}" />
                            </form>
                            <div class="search-close">
                                <i class="fa fa-times"></i>
                            </div>
                        </div>
                    </div>
                @endif
                <div class="dash-navbar-actions">
                    @hasSection('search')
                        <div class="dash-nav-link search-btn">
                            <i class="fa fa-search"></i>
                        </div>
                    @endif
                    <div class="dropdown language v2">
                        <button class="dash-nav-link v2" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="language-icon">
                                <i class="fas fa-globe"></i>
                            </div>
                            <span class="language-title">{{ getLangName() }}</span>
                            <div class="language-arrow">
                                <i class="fas fa-chevron-down fa-xs me-0"></i>
                            </div>
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                            @foreach ($languages as $language)
                                <li><a class="dropdown-item {{ getLang() == $language->code ? 'active' : '' }}"
                                        href="{{ langURL($language->code) }}">{{ $language->name }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                    @include('frontend.user.includes.notification-menu')
                    <div class="user-menu mx-0" data-dropdown>
                        <div class="dash-nav-link v2">
                            <div class="user-avatar">
                                <img src="{{ asset(userAuthInfo()->avatar) }}" alt="{{ userAuthInfo()->name }}" />
                            </div>
                            <p class="user-name mb-0 ms-2 d-none d-sm-block">{{ userAuthInfo()->name }}</p>
                            <div class="nav-bar-user-dropdown-icon ms-2 d-none d-sm-block">
                                <i class="fas fa-chevron-down fa-xs me-0"></i>
                            </div>
                        </div>
                        <div class="user-menu-dropdown">
                            <a class="user-menu-link" href="{{ route('user.settings') }}">
                                <i class="fa fa-cog"></i>
                                {{ lang('Settings', 'user') }}
                            </a>
                            <a class="user-menu-link text-danger" href="#"
                                onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <i class="fa fa-power-off"></i>
                                {{ lang('Logout', 'user') }}
                            </a>
                        </div>
                        <form id="logout-form" class="d-inline" action="{{ route('logout') }}" method="POST">
                            @csrf
                        </form>
                    </div>
                </div>
            </div>
            <div class="navbar-loading-bar" id="navbarLoadingBar">
                <div class="navbar-loading-fill" id="navbarLoadingFill"></div>
            </div>
        </div>
        <div class="dash-sidebar">
            <div class="overlay"></div>
            <div class="dash-sidebar-container">
                <div class="dash-sidebar-body pt-3">
                    <div class="dash-sidebar-links">
                        <a href="{{ route('user.dashboard') }}"
                            class="dash-sidebar-link {{ request()->routeIs('user.dashboard') ? 'current' : '' }}">
                            <div class="dash-sidebar-link-title">
                                {{-- <i class="fas fa-columns fa-lg"></i> --}}
                                <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" class="flex-shrink-0 w-5 h-auto text-custom-gray/90" style="width: 1.25rem;margin-right: 20px;"><path fill="currentColor" d="M9.182 3.455v3.272a2.455 2.455 0 0 1-2.455 2.455H3.455A2.455 2.455 0 0 1 1 6.727V3.455A2.455 2.455 0 0 1 3.455 1h3.272a2.455 2.455 0 0 1 2.455 2.455ZM16.546 1h-3.273a2.454 2.454 0 0 0-2.455 2.455v3.272a2.455 2.455 0 0 0 2.455 2.455h3.273A2.455 2.455 0 0 0 19 6.727V3.455A2.455 2.455 0 0 0 16.546 1Zm-9.819 9.818H3.455A2.455 2.455 0 0 0 1 13.273v3.273A2.455 2.455 0 0 0 3.455 19h3.272a2.455 2.455 0 0 0 2.455-2.454v-3.273a2.454 2.454 0 0 0-2.455-2.455Zm9.819 0h-3.273a2.454 2.454 0 0 0-2.455 2.455v3.273A2.455 2.455 0 0 0 13.273 19h3.273A2.455 2.455 0 0 0 19 16.546v-3.273a2.454 2.454 0 0 0-2.454-2.455Z"></path></svg>
                                <span>{{ lang('Dashboard', 'user') }}</span>
                            </div>
                        </a>
                        <a href="{{ route('user.files.index') }}"
                            class="dash-sidebar-link {{ request()->routeIs('user.files.index') || request()->routeIs('user.files.edit') ? 'current' : '' }}">
                            <div class="dash-sidebar-link-title">
                                {{-- <i class="fas fa-folder fa-lg"></i> --}}
                                <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" class="flex-shrink-0 w-5 h-auto text-custom-gray/90" style="width: 1.25rem;margin-right: 20px;"><path fill="currentColor" d="M12.457 6.273c-.678 0-1.23-.552-1.23-1.23V1H4.934A1.936 1.936 0 0 0 3 2.934v14.132C3 18.133 3.867 19 4.934 19h9.843a1.936 1.936 0 0 0 1.934-1.934V6.273h-4.254Zm-6.574 7.383h2.556a.527.527 0 0 1 0 1.055H5.883a.527.527 0 0 1 0-1.055Zm-.528-2.285c0-.291.237-.527.528-.527h7.734a.527.527 0 0 1 0 1.054H5.883a.527.527 0 0 1-.528-.527Zm8.262-3.34a.527.527 0 0 1 0 1.055H5.883a.527.527 0 0 1 0-1.055h7.734Z"></path><path fill="currentColor" d="M12.281 5.043c0 .097.08.176.176.176h4.019a1.93 1.93 0 0 0-.37-.483l-3.39-3.207a1.936 1.936 0 0 0-.435-.31v3.824Z"></path></svg>
                                <span>{{ lang('Files', 'user') }}</span>
                            </div>
                        </a>
                        {{-- NEW: Trash Link --}}
                        <a href="{{ route('user.trash.index') }}"
                            class="dash-sidebar-link {{ request()->routeIs('user.trash.*') ? 'current' : '' }}">
                            <div class="dash-sidebar-link-title">
                                <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" class="flex-shrink-0 w-5 h-auto text-custom-gray/90" style="width: 1.25rem;margin-right: 20px;"><path fill="currentColor" d="M17.272 5.664c.413 0 .7-.418.545-.801a3.717 3.717 0 0 0-3.442-2.324h-.72C13.388 1.122 12.146 0 10.626 0h-1.25c-1.52 0-2.762 1.121-3.03 2.54h-.72a3.717 3.717 0 0 0-3.442 2.323.585.585 0 0 0 .545.801h14.544ZM9.375 1.172h1.25c.855 0 1.582.575 1.826 1.367H7.55a1.922 1.922 0 0 1 1.826-1.367ZM4.338 18.367A1.764 1.764 0 0 0 6.091 20h7.818c.918 0 1.688-.717 1.753-1.633l.821-11.531H3.517l.821 11.531Zm7.264-8.396a.586.586 0 0 1 1.17.058l-.312 6.25a.586.586 0 0 1-1.17-.058l.312-6.25Zm-3.819-.556a.586.586 0 0 1 .615.556l.312 6.25a.586.586 0 0 1-1.17.058l-.313-6.25a.586.586 0 0 1 .556-.614Z"></path></svg>
                                <span>{{ lang('Trash', 'user') }}</span>
                            </div>
                        </a>
                        {{-- Doc requests --}}
<a href="{{ route('user.file-requests.index') }}"
   class="dash-sidebar-link {{ request()->routeIs('user.file-requests.*') ? 'current' : '' }}">
    <div class="dash-sidebar-link-title">
        <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"
             class="flex-shrink-0 w-5 h-auto text-custom-gray/90"
             style="width: 1.25rem;margin-right: 20px;">
            <path fill="currentColor"
                  d="M4 2.5A2.5 2.5 0 0 1 6.5 0h5A2.5 2.5 0 0 1 14 2.5V5h1.5A2.5 2.5 0 0 1 18 7.5v8A2.5 2.5 0 0 1 15.5 18H4.5A2.5 2.5 0 0 1 2 15.5v-11A2.5 2.5 0 0 1 4.5 2h-.5Zm3 0a.5.5 0 0 0-.5.5V5h7V2.5a.5.5 0 0 0-.5-.5h-6Z" />
        </svg>
        <span>{{ __('Doc requests') }}</span>
    </div>
</a>

                        {{-- share with me --}}
                        <a href="{{ route('user.shared.index') }}"
                            class="dash-sidebar-link {{ request()->routeIs('user.shared.*') ? 'current' : '' }}">
                            <div class="dash-sidebar-link-title">
                                <i class="fas fa-user-friends fa-lg"></i>
                                <span>{{ __('Shared with me') }}</span>
                            </div>
                        </a>

                        @if (licenseType(2))
                            <a href="{{ route('user.subscription') }}"
                                class="dash-sidebar-link {{ request()->routeIs('user.subscription') ? 'current' : '' }}">
                                <div class="dash-sidebar-link-title">
                                    <i class="far fa-gem fa-lg"></i>
                                    <span>{{ lang('My subscription', 'user') }}</span>
                                </div>
                            </a>
                        @endif
                        <a href="{{ route('user.settings') }}"
                            class="dash-sidebar-link 
                            {{ request()->routeIs('user.settings') || request()->routeIs('user.settings.2fa') || request()->routeIs('user.settings.password') ? 'current' : '' }}">
                            <div class="dash-sidebar-link-title">
                                <i class="fas fa-cog fa-lg"></i>
                                <span>{{ lang('Settings', 'user') }}</span>
                            </div>
                        </a>
                    </div>
                </div>
                <div class="dash-sidebar-footer">
                    @php
                        if (subscription()->storage->fullness > 80) {
                            $progressClass = 'class="bg-danger"';
                        } elseif (subscription()->storage->fullness < 80 && subscription()->storage->fullness > 60) {
                            $progressClass = 'class="bg-warning"';
                        } else {
                            $progressClass = '';
                        }
                    @endphp
                    <div class="dash-storage">
                        <div class="dash-storage-info">
                            <p class="dash-storage-text mb-0">{{ subscription()->storage->used->format }} /
                                {{ subscription()->formates->storage_space }}</p>
                        </div>
                        @if (!subscription()->is_lifetime && subscription()->plan->storage_space)
                            <div class="dash-storage-progress">
                                <span style="width: {{ subscription()->storage->fullness }}%"
                                    {!! $progressClass !!}></span>
                            </div>
                        @endif
                        @if (licenseType(2) && $countPlans > 1)
                            <a href="{{ route('user.plans') }}" class="btn btn-primary btn-md w-100 mt-3"><i
                                    class="fas fa-arrow-up me-2"></i>{{ lang('Upgrade', 'user') }}</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="dash-body">
            <div class="dash-container">
                <div class="dash-page-header">
                    <div class="row justify-content-between align-items-center g-3">
                        <div class="col-auto">
                            <h4 class="dash-page-title">@yield('title')</h4>
                            @include('frontend.user.includes.breadcrumb')
                        </div>
                        <div class="col-auto">
                            @hasSection('back')
                                <a href="@yield('back')" class="btn btn-gradient btn-md me-2"><i
                                        class="fas fa-arrow-left me-2"></i>{{ lang('Back', 'user') }}</a>
                            @endif
                            @hasSection('link')
                                <a href="@yield('link')" class="btn btn-primary btn-md me-2"><i
                                        class="fa fa-plus"></i></a>
                            @endif
                            @hasSection('upload')
                            @if($uploadMode === 'regular')
                                {{-- Regular Upload Interface --}}
                                <div class="add-new-dropdown">
                                    <button class="btn btn-primary add-new-btn" type="button" id="addNewDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-plus me-2"></i>{{ lang('Add New', 'user') }}
                                        <i class="fas fa-chevron-down ms-2"></i>
                                    </button>
                                    <ul class="dropdown-menu add-new-menu" aria-labelledby="addNewDropdown">
                                        <li>
                                            <a class="dropdown-item upload-file-option" href="#" data-dz-click>
                                                <i class="fas fa-upload me-2"></i>{{ lang('Upload File', 'user') }}
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item upload-folder-option" href="#" id="folderUploadBtn">
                                                <i class="fas fa-folder me-2"></i>{{ lang('Upload Folder', 'user') }}
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item create-folder-option" href="#" data-bs-toggle="modal" data-bs-target="#createFolderModal">
                                                <i class="fas fa-folder-plus me-2"></i>{{ lang('Create Folder', 'user') }}
                                            </a>
                                        </li>
<li>
    <a class="dropdown-item"
       href="{{ route('user.file-requests.index', ['folder' => request('folder')]) }}">
        <i class="fas fa-link me-2"></i>{{ lang('Create Upload Link', 'user') }}
    </a>
</li>


                                    </ul>
                                </div>
                            @else
                                {{-- Custom Upload Interface --}}
                                <div class="add-new-dropdown">
                                    <button class="btn btn-primary add-new-btn" type="button" id="addNewDropdownCustom" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-cogs me-2"></i>{{ lang('Add New', 'user') }}
                                        <i class="fas fa-chevron-down ms-2"></i>
                                    </button>
                                    <ul class="dropdown-menu add-new-menu" aria-labelledby="addNewDropdownCustom">
                                        <li>
                                            <a class="dropdown-item upload-file-option js-direct-upload" href="#" id="directUploadBtn">
                                                <i class="fas fa-upload me-2"></i>{{ lang('Upload File', 'user') }}
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item upload-folder-option js-folder-upload" href="#" id="folderUploadBtn">
                                                <i class="fas fa-folder me-2"></i>{{ lang('Upload Folder', 'user') }}
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item create-folder-option" href="#" data-bs-toggle="modal" data-bs-target="#createFolderModal">
                                                <i class="fas fa-folder-plus me-2"></i>{{ lang('Create Folder', 'user') }}
                                            </a>
                                        </li>
<li>
    <a class="dropdown-item"
       href="{{ route('user.file-requests.index', ['folder' => request('folder')]) }}">
        <i class="fas fa-link me-2"></i>{{ lang('Create Upload Link', 'user') }}
    </a>
</li>


                                    </ul>

                                    <!-- Hidden folder input for custom mode -->
                                    <input type="file" id="folderInput" webkitdirectory directory multiple style="display: none;">
                                </div>
                            @endif

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



                            <!-- Create Folder Modal -->
                            <div class="modal fade" id="createFolderModal" tabindex="-1" aria-labelledby="createFolderModalLabel" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="createFolderModalLabel">
                                                <i class="fas fa-folder-plus me-2"></i>{{ lang('Create New Folder', 'user') }}
                                            </h5>
                                            <!-- Hidden file input for direct upload -->
                                            <input type="file" id="directFileInput" style="display: none;" multiple accept="*/*">

                                            <input type="hidden" id="currentFolderId" name="current_folder_id" value="{{ $currentFolderId ?? '' }}">
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <form id="createFolderForm">
                                                @csrf
                                                <div class="mb-3">
                                                    <label for="folderName" class="form-label">{{ lang('Folder Name', 'user') }}</label>
                                                    <input type="text" class="form-control" id="folderName" name="folder_name" 
                                                        placeholder="{{ lang('Enter folder name', 'user') }}" required>
                                                    <div class="invalid-feedback"></div>
                                                </div>
                                                <div class="mb-3">
                                                    <small class="text-muted">
                                                        <i class="fas fa-info-circle me-1"></i>
                                                        {{ lang('Folder names cannot contain special characters: / \ : * ? " < > |', 'user') }}
                                                    </small>
                                                </div>
                                            </form>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                {{ lang('Cancel', 'user') }}
                                            </button>
                                            <button type="button" class="btn btn-primary" id="createFolderBtn">
                                                <i class="fas fa-folder-plus me-2"></i>{{ lang('Create Folder', 'user') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif


                            <!-- Create File Request Modal -->
                            <div class="modal fade" id="createFileRequestModal" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">
                                                <i class="fas fa-link me-2"></i>{{ lang('Create Upload Link', 'user') }}
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <form id="createFileRequestForm">
                                                <!-- Hidden username for accessibility (browser autofill) -->
                                                <input type="text" name="username" autocomplete="username" style="display:none;" aria-hidden="true">
                                                <div class="mb-3">
                                                    <label class="form-label">{{ lang('Password Protection (Optional)', 'user') }}</label>
                                                    <input type="password" class="form-control" name="password" placeholder="{{ lang('Enter password to protect link', 'user') }}" autocomplete="new-password">
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">{{ lang('Expiration Date', 'user') }}</label>
                                                        <input type="date" class="form-control" name="expiration_date" min="{{ date('Y-m-d') }}">
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">{{ lang('Expiration Time', 'user') }}</label>
                                                        <input type="time" class="form-control" name="expiration_time">
                                                    </div>
                                                </div>
                                                <div class="alert alert-info small">
                                                    <i class="fas fa-info-circle me-1"></i>
                                                    {{ lang('If no expiration is set, the link will expire in 24 hours.', 'user') }}
                                                </div>
                                            </form>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ lang('Cancel', 'user') }}</button>
                                            <button type="button" class="btn btn-primary" id="submitFileRequestBtn">
                                                <i class="fas fa-check me-2"></i>{{ lang('Create Link', 'user') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>


                            @if (request()->routeIs('user.subscription'))
                                @if (!subscription()->is_canceled)
                                    @if (!subscription()->plan->free_plan && !subscription()->is_lifetime)
                                        <form class="d-inline me-2"
                                            action="{{ route('subscribe', [hashid(subscription()->plan->id), 'renew']) }}"
                                            method="POST">
                                            @csrf
                                            <button class="confirm-action-form btn btn-green"><i
                                                    class="fas fa-sync-alt"></i>
                                                <span
                                                    class="ms-2 d-none d-lg-inline">{{ lang('Renew Subscription', 'subscription') }}</span>
                                            </button>
                                        </form>
                                    @endif
                                    <a href="{{ $countPlans > 1 ? route('user.plans') : '#' }}"
                                        class="btn btn-primary {{ $countPlans > 1 ? '' : 'disabled' }}"><i
                                            class="far fa-gem"></i>
                                        <span
                                            class="ms-2 d-none d-lg-inline">{{ lang('Upgrade', 'subscription') }}</span>
                                    </a>
                                @endif
                            @endif
                            @if (request()->routeIs('user.notifications'))
                                @if ($unreadUserNotifications)
                                    <a class="confirm-action btn btn-gradient"
                                        href="{{ route('user.notifications.readall') }}">{{ lang('Make All as Read', 'user') }}</a>
                                @else
                                    <button class="btn btn-gradient"
                                        disabled>{{ lang('Make All as Read', 'user') }}</button>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
                <div class="dash-page-body"> 
                    @yield('content')
                </div>
            </div>
            <footer class="dash-page-footer mt-auto">
                <div class="row justify-content-between">
                    <div class="col-auto">
                        <p class="mb-0">&copy; <span data-year></span> {{ $settings['website_name'] }} -
                            {{ lang('All rights reserved') }}.</p>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    {{-- move to --}}
    <div class="modal fade" id="moveFileModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Move File</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <nav id="moveBreadcrumb" aria-label="breadcrumb" class="mb-2"></nav>
                <div id="folderTree"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="moveHereBtn" disabled>Move Here</button>
            </div>
            </div>
        </div>
    </div>



    @hasSection('upload')
        @include('frontend.global.includes.uploadbox')
    @endif
    {{-- Modal HTML must be rendered BEFORE scripts so event listeners can find elements --}}
    @stack('modals')
    @include('frontend.configurations.config')
    @include('frontend.configurations.widgets')
    @include('frontend.user.includes.scripts')

    {{-- Additional page-specific scripts --}}
    @stack('scripts')
</body>

<style>
  .swm-modal .form-label { font-size:.875rem; }
  .swm-modal .form-text { font-size:.75rem; }
  .swm-modal .modal-body { padding-top:.5rem; }
  .swm-modal .swm-file-name { font-weight:600; vertical-align:middle; }
  .swm-general { background:#fafafa; }
  .swm-dialog { max-width: 650px; }
</style>

<style>
  /* Ghost the item when it’s in a CUT clipboard */
  .file-item.is-cut {
    opacity: .45;
  }
  .file-item.is-cut .filemanager-file-title {
    text-decoration: line-through;
    opacity: .9;
  }


  #swmTypeahead { max-height: 280px; overflow: auto; }
  #swmTypeahead .dropdown-item { display:flex; flex-direction:column; padding:.5rem .75rem; }
  #swmTypeahead .dropdown-item .email { opacity:.75; font-size:.8125rem; }
  #swmTypeahead .nothing { padding:.5rem .75rem; color: var(--bs-secondary-color); }
  #swmTypeahead .invite { display:flex; align-items:center; gap:.5rem; }
  #swmTypeahead .kbd { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace; font-size:.75rem; border:1px solid var(--bs-border-color); padding:0 .25rem; border-radius:.25rem; }
</style>


<script>


document.addEventListener('DOMContentLoaded', function() {
    let currentMoveFileId = null;
    let selectedFolderId = null;
    let currentPath = [];
    const moveHereBtn = document.getElementById('moveHereBtn');
    const moveModal = document.getElementById('moveFileModal');
    const folderTree = document.getElementById('folderTree');
    const moveBreadcrumb = document.getElementById('moveBreadcrumb');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    // Handle move to button click
    document.querySelectorAll('.file-move-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            currentMoveFileId = btn.dataset.fileId;
            selectedFolderId = null;
            currentPath = [];
            moveHereBtn.disabled = false;
            showFolderTree();
            if (moveModal && typeof bootstrap !== 'undefined') {
                let modal = bootstrap.Modal.getOrCreateInstance(moveModal);
                modal.show();
            }
        });
    });

    function showBreadcrumb() {
        if (!moveBreadcrumb) return;
        if (currentPath.length === 0) {
            moveBreadcrumb.innerHTML = `<ol class="breadcrumb mb-1"><li class="breadcrumb-item active">Root</li></ol>`;
        } else {
            let html = `<ol class="breadcrumb mb-1">`;
            html += `<li class="breadcrumb-item"><a href="#" data-folder-id="">Root</a></li>`;
            currentPath.forEach((item, idx) => {
                if (idx === currentPath.length-1) {
                    html += `<li class="breadcrumb-item active">${item.name}</li>`;
                } else {
                    html += `<li class="breadcrumb-item"><a href="#" data-folder-id="${item.id}">${item.name}</a></li>`;
                }
            });
            html += `</ol>`;
            moveBreadcrumb.innerHTML = html;
            
            moveBreadcrumb.querySelectorAll('a[data-folder-id]').forEach(a => {
                a.onclick = function(e) {
                    e.preventDefault();
                    const id = this.dataset.folderId;
                    if (!id) {
                        currentPath = [];
                        showFolderTree();
                    } else {
                        const idx = currentPath.findIndex(i=>i.id===id);
                        currentPath = currentPath.slice(0, idx+1);
                        showFolderTree(id);
                    }
                }
            });
        }
    }

    function showFolderTree(parentId = null) {
        showBreadcrumb();
        folderTree.innerHTML = `<div class="text-center text-muted my-3"><i class="fas fa-spinner fa-spin"></i> Loading...</div>`;
        fetch('/user/files/folders-tree' + (parentId ? '?parent_id='+parentId : ''))
        .then(r=>r.json())
        .then(data=>{
            folderTree.innerHTML = '';
            let ul = document.createElement('ul');
            ul.className = 'list-group';
            if (data.folders.length === 0) {
                ul.innerHTML = '<li class="list-group-item text-muted">No subfolders here</li>';
            } else {
                data.folders.forEach(folder=>{
                    let li = document.createElement('li');
                    li.className = 'list-group-item d-flex align-items-center justify-content-between';
                    li.innerHTML = `<span style="cursor:pointer"><i class="fas fa-folder me-1"></i>${folder.name}</span>`;
                    li.querySelector('span').onclick = () => {
                        // Go inside this folder
                        currentPath.push({id: folder.shared_id, name: folder.name});
                        selectedFolderId = folder.shared_id;
                        showFolderTree(folder.shared_id);
                        moveHereBtn.disabled = false;
                    };
                    ul.appendChild(li);
                });
            }
            folderTree.appendChild(ul);
        });
        moveHereBtn.disabled = false;
    }

    if (moveHereBtn) {
        moveHereBtn.onclick = function() {
            if (currentPath.length === 0) selectedFolderId = null;
            else selectedFolderId = currentPath[currentPath.length-1].id;
            moveHereBtn.disabled = true;
            fetch(`/user/files/${currentMoveFileId}/move`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'target_folder_id=' + encodeURIComponent(selectedFolderId || '')
            })
            .then(r=>r.json())
            .then(data=>{
                if (data.type === 'success') {
                    if (typeof toastr !== 'undefined') toastr.success(data.msg);
                    setTimeout(()=>window.location.reload(), 1000);
                } else {
                    if (typeof toastr !== 'undefined') toastr.error(data.msg);
                    moveHereBtn.disabled = false;
                }
            })
            .catch(()=>{
                if (typeof toastr !== 'undefined') toastr.error('Move failed');
                moveHereBtn.disabled = false;
            });
        };
    }
});



document.addEventListener('DOMContentLoaded', function () {
  const modalEl = document.getElementById('shareModal');
  if (!modalEl) return;

  modalEl.addEventListener('show.bs.modal', function (event) {
    // The link/button that opened the modal
    const trigger = event.relatedTarget;
    if (!trigger) return;

    // Parse data from the trigger
    let data = {};
    try {
      data = JSON.parse(trigger.getAttribute('data-share') || '{}');
    } catch (e) {
      console.error('Bad data-share JSON', e);
    }
    const canPreview = trigger.getAttribute('data-preview') === 'true';

    // Populate fields
    const filenameEl = modalEl.querySelector('.filename');
    const dlInput    = modalEl.querySelector('#copy-download-link');
    const pvGroup    = modalEl.querySelector('.preview-link');
    const pvInput    = modalEl.querySelector('#copy-preview-link');

    if (filenameEl) filenameEl.textContent = data.filename || '';
    if (dlInput) dlInput.value = data.download_link || '';

    if (pvGroup && pvInput) {
      if (canPreview && data.preview_link) {
        pvGroup.style.display = '';
        pvInput.value = data.preview_link;
      } else {
        pvGroup.style.display = 'none';
        pvInput.value = '';
      }
    }
  });

  // Optional: clipboard support for the copy buttons
  // If you use clipboard.js, ensure it’s included, then:
  if (window.ClipboardJS) {
    new ClipboardJS('#shareModal .copy');
  }
});
</script>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Premium Navbar Loading Bar with Inline Styles -->
<style>
.navbar-loading-bar {
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
    height: 3px;
    background: rgba(0,0,0,0.05);
    overflow: hidden;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.2s ease, visibility 0.2s ease;
    z-index: 9999;
}
.navbar-loading-bar.active {
    opacity: 1;
    visibility: visible;
}
.navbar-loading-fill {
    height: 100%;
    width: 0%;
    position: relative;
    border-radius: 0 4px 4px 0;
    transition: width 0.2s ease-out, box-shadow 0.3s ease;
    background: linear-gradient(90deg, #3b82f6, #8b5cf6, #ec4899, #f43f5e);
    background-size: 300% 100%;
    animation: gradientMove 2s ease infinite;
}
.navbar-loading-fill::after {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.6), transparent);
    animation: shimmer 1.2s ease-in-out infinite;
}
.navbar-loading-fill::before {
    content: '';
    position: absolute;
    right: -5px;
    top: -4px;
    width: 12px;
    height: 12px;
    background: #f43f5e;
    border-radius: 50%;
    box-shadow: 0 0 15px 5px rgba(244, 63, 94, 0.7);
    animation: pulse 0.8s ease-in-out infinite;
}
@keyframes gradientMove {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}
@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(200%); }
}
@keyframes pulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.3); opacity: 0.7; }
}
</style>

<!-- jQuery-based Loader Script -->
<script>
(function($) {
    if (window.__loaderReady) return;
    window.__loaderReady = true;
    
    var $bar = $('#navbarLoadingBar');
    var $fill = $('#navbarLoadingFill');
    if (!$bar.length || !$fill.length) return;
    
    // State
    var running = false;
    var progress = 0;
    var timer = null;
    var hideTimer = null;
    var initTime = Date.now();
    var GRACE_MS = 800;
    
    function show() {
        $bar.addClass('active');
        $fill.css('opacity', '1');
    }
    
    function hide() {
        $fill.css('opacity', '0');
        setTimeout(function() {
            $bar.removeClass('active');
            $fill.css('width', '0%').css('opacity', '1');
            progress = 0;
            running = false;
        }, 350);
    }
    
    function setWidth(pct) {
        progress = pct;
        // Increase glow as progress increases
        var glow = Math.min(20, pct / 5);
        $fill.css({
            'width': pct + '%',
            'box-shadow': '0 0 ' + glow + 'px rgba(139, 92, 246, 0.8), 0 0 ' + (glow * 2) + 'px rgba(244, 63, 94, 0.5)'
        });
    }
    
    function tick() {
        if (!running || progress >= 92) return;
        
        var inc;
        if (progress < 25) inc = 5 + Math.random() * 4;
        else if (progress < 50) inc = 3 + Math.random() * 2;
        else if (progress < 75) inc = 1 + Math.random() * 1;
        else inc = 0.3 + Math.random() * 0.4;
        
        setWidth(Math.min(92, progress + inc));
        
        var delay = progress < 40 ? 100 : (progress < 70 ? 180 : 280);
        timer = setTimeout(tick, delay);
    }
    
    function start() {
        clearTimeout(hideTimer);
        if (running) return;
        
        running = true;
        window._loaderStartTime = Date.now();
        setWidth(0);
        show();
        
        setTimeout(function() {
            if (running) {
                setWidth(12);
                tick();
            }
        }, 30);
    }
    
    function done() {
        if (!running) return;
        clearTimeout(timer);
        setWidth(100);
        // Max glow at 100%
        $fill.css('box-shadow', '0 0 25px rgba(139, 92, 246, 1), 0 0 50px rgba(244, 63, 94, 0.8)');
        hideTimer = setTimeout(hide, 300);
    }
    
    function reset() {
        clearTimeout(timer);
        clearTimeout(hideTimer);
        $bar.removeClass('active');
        $fill.css({'width': '0%', 'opacity': '1', 'box-shadow': 'none'});
        progress = 0;
        running = false;
    }
    
    // Expose globally
    window.NProgress = { start: start, done: done, reset: reset };
    
    // Request tracking with grace period
    var pending = 0, doneCheck = null;
    
    function reqStart() {
        if ((Date.now() - initTime) < GRACE_MS) return;
        pending++;
        start();
    }
    
    function reqEnd() {
        pending = Math.max(0, pending - 1);
        clearTimeout(doneCheck);
        doneCheck = setTimeout(function() {
            if (pending === 0) done();
        }, 120);
    }
    
    // Patch jQuery AJAX
    $(document).ajaxStart(reqStart).ajaxStop(function() {
        pending = 0;
        done();
    });
    
    // Patch native fetch
    if (window.fetch) {
        var origFetch = window.fetch;
        window.fetch = function() {
            reqStart();
            return origFetch.apply(window, arguments)
                .then(function(r) { reqEnd(); return r; })
                .catch(function(e) { reqEnd(); throw e; });
        };
    }
    
    // Patch XMLHttpRequest
    var XHR = XMLHttpRequest.prototype;
    var oOpen = XHR.open, oSend = XHR.send;
    XHR.open = function() { this._ldr = false; return oOpen.apply(this, arguments); };
    XHR.send = function() {
        var x = this;
        if (!x._ldr) {
            x._ldr = true;
            reqStart();
            $(x).on('loadend', reqEnd);
        }
        return oSend.apply(this, arguments);
    };
    
    // Links
    $(document).on('click', 'a[href]', function(e) {
        var $a = $(this);
        var href = $a.attr('href') || '';
        if (href[0] === '#' || href.indexOf('javascript:') === 0) return;
        if ($a.data('bs-toggle') || $a.data('toggle')) return;
        if ($a.attr('target') === '_blank') return;
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.isDefaultPrevented()) return;
        start();
    });
    
    // Forms
    $(document).on('submit', 'form', function(e) {
        if (!e.isDefaultPrevented()) start();
    });
    
    // Page lifecycle
    $(window).on('beforeunload', start);
    $(window).on('load', reset);
    $(window).on('pageshow', function() {
        reset();
        initTime = Date.now();
    });
    
    // Failsafe
    setInterval(function() {
        if (running && window._loaderStartTime && (Date.now() - window._loaderStartTime > 25000)) {
            reset();
        }
    }, 5000);
    
})(jQuery);
</script>
</html>
