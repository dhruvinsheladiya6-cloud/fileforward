<!DOCTYPE html>
<html lang="{{ getLang() }}">

<head>
    @include('frontend.includes.head')
    @include('frontend.includes.styles')
    {!! head_code() !!}
</head>

@include('frontend.includes.adblock-detection')

<body class="@yield('bg')">
    <header class="header @yield('header_version')">
        @include('frontend.includes.navbar')
        @if (!$__env->yieldContent('hide_title'))
            <div class="container">
                <h2 class="page-title text-white text-center mb-0 mt-4">
                    {{ shortertext($__env->yieldContent('title'), 40) }}</h2>
            </div>
        @endif
    </header>
    @yield('content')
    @include('frontend.includes.footer')
    @include('frontend.configurations.config')
    @include('frontend.configurations.widgets')
    @include('frontend.includes.scripts')
</body>

</html>
