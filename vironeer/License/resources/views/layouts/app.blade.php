<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="icon" href="{{ asset('assets/vendor/install/img/favicon.ico') }}" />
    <title>{{ __('Vironeer Installer') }} - @yield('title')</title>
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/bootstrap/bootstrap.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/fontawesome/fontawesome.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/install/css/app.min.css') }}" />
</head>

<body>
    <div class="vironeer-pagecontent">
        <div class="vironeer-background">
            <a class="vironeer-background-logo" href="{{ url()->current() }}">
                <img src="{{ asset('assets/vendor/install/img/logo.png') }}" alt="vironeer install" />
                <span>{{ __('Installer') }}</span>
            </a>
            <div class="vironeer-background-img d-none d-lg-block">
                <h4 class="vironeer-background-title mb-5">{{ __('A few clicks to get your website ready to use') }}
                </h4>
                <img src="{{ asset('assets/vendor/install/img/steps.svg') }}" alt="{{ __('Vironeer Installer') }}" />
            </div>
        </div>
    </div>
    <div class="vironeer-form">
        <div class="vironeer-form-cont">
            @if (!is_null(request()->segment(2)))
                <div class="vironeer-steps mb-5 d-none d-lg-block">
                    <div class="row row-cols-2 row-cols-md-4 g-4">
                        <div class="col">
                            <div
                                class="vironeer-step text-center @if (request()->segment(2) == 'requirements') active @elseif(env('VIRONEER_REQUIREMENTS')) complete @endif">
                                <div class="vironeer-step-wrapper">
                                    <div class="vironeer-step-icon">
                                        <i class="fas fa-server"></i>
                                    </div>
                                </div>
                                <p class="vironeer-step-title">{{ __('Requirements') }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <div
                                class="vironeer-step text-center @if (request()->segment(2) == 'permissions') active @elseif(env('VIRONEER_FILEPERMISSIONS')) complete @endif">
                                <div class="vironeer-step-wrapper">
                                    <div class="vironeer-step-icon">
                                        <i class="fas fa-folder-open"></i>
                                    </div>
                                </div>
                                <p class="vironeer-step-title">{{ __('File Permissions') }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <div
                                class="vironeer-step text-center @if (request()->segment(2) == 'licence') active @elseif(env('VIRONEER_LICENCE')) complete @endif">
                                <div class="vironeer-step-wrapper">
                                    <div class="vironeer-step-icon">
                                        <i class="fas fa-key"></i>
                                    </div>
                                </div>
                                <p class="vironeer-step-title">{{ __('Licence Validation') }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <div
                                class="vironeer-step text-center @if (request()->segment(2) == 'information') active @elseif(env('VIRONEER_INFORMATION')) complete @endif">
                                <div class="vironeer-step-wrapper">
                                    <div class="vironeer-step-icon">
                                        <i class="fas fa-database"></i>
                                    </div>
                                </div>
                                <p class="vironeer-step-title">{{ __('General Information') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            @yield('content')
        </div>
        <footer class="vironeer-footer">
            <div class="row align-items-center h-100">
                <div class="col-12 col-sm-6 mb-3 mb-sm-0">
                    <div class="vironeer-copyright d-flex justify-content-center justify-content-sm-start">
                        <p class="mb-0">&copy; <span data-year></span>
                            {{ __('Vironeer - All rights reserved.') }}
                        </p>
                    </div>
                </div>
                <div class="col-12 col-sm-6">
                    <div class="d-flex justify-content-center justify-content-sm-end gap-3">
                        <a href="https://t.me/vironeer" target="_blank">{{ __('Get Help') }}</a>
                        <a href="https://twitter.com/vironeer" target="_blank">{{ __('Twitter') }}</a>
                    </div>
                </div>
            </div>
        </footer>
    </div>
    <script src="{{ asset('assets/vendor/libs/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/bootstrap/bootstrap.bundle.min.js') }}"></script>
    <script>
        "use strict";
        document.querySelectorAll('[data-year]').forEach((el) => {
            el.textContent = " " + new Date().getFullYear();
        });
    </script>
</body>

</html>
