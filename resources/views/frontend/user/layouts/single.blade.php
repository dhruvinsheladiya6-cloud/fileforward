<!DOCTYPE html>
<html lang="{{ getLang() }}">

<head>
    @include('frontend.user.includes.head')
    @include('frontend.user.includes.styles')
</head>

@include('frontend.includes.adblock-detection')

<body>
    <div class="single-page">
        <div class="single-page-container">
            <nav class="single-page-navbar">
                <div class="container-lg d-flex align-items-center">
                    <a href="{{ url()->current() }}" class="logo">
                        <img src="{{ asset($settings['website_light_logo']) }}" alt="{{ $settings['website_name'] }}"
                            title="{{ $settings['website_name'] }}" />
                    </a>
                    <div class="single-page-navbar-actions">
                        <div class="dropdown language v2">
                            <button class="nav-link" data-bs-toggle="dropdown" aria-expanded="false">
                                <div class="language-icon">
                                    <i class="fas fa-globe"></i>
                                </div>
                                <span class="language-title">{{ getLangName() }}</span>
                                <div class="language-arrow">
                                    <i class="fas fa-chevron-down fa-xs me-0"></i>
                                </div>
                            </button>
                            <ul class="dropdown-menu">
                                @foreach ($languages as $language)
                                    <li><a class="dropdown-item {{ getLang() == $language->code ? 'active' : '' }}"
                                            href="{{ langURL($language->code) }}">{{ $language->name }}</a></li>
                                @endforeach
                            </ul>
                        </div>
                        <div class="user-menu mx-0" data-dropdown>
                            <div class="nav-link">
                                <div class="user-avatar">
                                    <img src="{{ asset(userAuthInfo()->avatar) }}" alt="{{ userAuthInfo()->name }}" />
                                </div>
                                <p class="user-name mb-0 ms-2 d-none d-sm-block">{{ userAuthInfo()->name }}</p>
                                <div class="nav-bar-user-dropdown-icon ms-2 d-none d-sm-block">
                                    <i class="fas fa-chevron-down fa-xs me-0"></i>
                                </div>
                            </div>
                            <div class="user-menu-dropdown">
                                @if (subscription()->is_subscribed)
                                    <a class="user-menu-link" href="{{ route('user.files.index') }}">
                                        <i class="fas fa-folder-open"></i>
                                        {{ lang('My files', 'user') }}
                                    </a>
                                    @if (licenseType(2))
                                        <a class="user-menu-link" href="{{ route('user.subscription') }}">
                                            <i class="fas fa-gem"></i>
                                            {{ lang('My subscription', 'user') }}
                                        </a>
                                    @endif
                                    <a class="user-menu-link" href="{{ route('user.settings') }}">
                                        <i class="fa fa-cog"></i>
                                        {{ lang('Settings', 'user') }}
                                    </a>
                                @endif
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
            </nav>
            <div class="single-page-content">
                <div class="container-lg">
                    @if (!$__env->yieldContent('hide_breadcrumbs'))
                        <div class="row justify-content-between align-items-center mb-4 g-3">
                            <div class="col-auto">
                                <h5 class="fs-5 mb-2">@yield('title')</h5>
                                @include('frontend.user.includes.breadcrumb')
                            </div>
                        </div>
                    @endif
                    @yield('content')
                </div>
            </div>
            <footer class="footer mt-auto">
                <div class="container">
                    <div class="d-flex align-items-center flex-column flex-md-row">
                        <p class="text-muted mb-3 mb-md-0">&copy; <span data-year></span>
                            {{ $settings['website_name'] }} -
                            {{ lang('All rights reserved') }}.</p>
                        @if (count($footerMenuLinks) > 0)
                            <div class="footer-links ms-md-auto">
                                @foreach ($footerMenuLinks as $footerMenuLink)
                                    <div class="footer-link">
                                        <a href="{{ $footerMenuLink->link }}"
                                            class="link">{{ $footerMenuLink->name }}</a>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </footer>
        </div>
    </div>
    @include('frontend.configurations.config')
    @include('frontend.configurations.widgets')
    @include('frontend.user.includes.scripts')
</body>

</html>
