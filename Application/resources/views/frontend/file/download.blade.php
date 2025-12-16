@extends('frontend.layouts.pages')
@section('section', lang('Download', 'download page'))
@section('title', $fileEntry->name)
@section('hide_title', true)
@section('header_version', 'v3')
@section('bg', 'only-nav px-3')
@section('content')
    {!! ads_download_page_header() !!}
    <div class="section-content py-4">
        <div class="contain">
            <div class="row g-3">
                @if (subscription()->plan->advertisements)
                    <div class="col-12 col-lg-4 order-2 order-lg-1">
                        <div class="d-flex justify-content-center flex-lg-column flex-wrap">
                            {!! ads_download_page_left_sidebar_top() !!}
                            {!! ads_download_page_left_sidebar_bottom() !!}
                        </div>
                    </div>
                @endif
                <div class="col-12  {{ subscription()->plan->advertisements ? 'col-lg-8' : '' }} order-1 order-lg-5">
                    <div class="filebox">
                        <div class="filebox-info">
                            {!! fileIcon($fileEntry->extension) !!}
                            <div class="filebox-desc mx-3">
                                <p class="filebox-title mb-1">{{ $fileEntry->name }}</p>
                                <div class="filebox-actions">
                                    <a data-bs-toggle="modal" data-bs-target="#share" rel="tooltip" data-bs-placement="top"
                                        title="{{ lang('Share File', 'download page') }}">
                                        <i class="fas fa-share-alt"></i>
                                    </a>
                                    @php
                                        $reportFileStatus = auth()->user() && $fileEntry->user_id == userAuthInfo()->id ? false : true;
                                    @endphp
                                    @if ($reportFileStatus)
                                        <a data-bs-toggle="modal" data-bs-target="#report" rel="tooltip"
                                            data-bs-placement="top" title="{{ lang('Report File', 'download page') }}">
                                            <i class="far fa-flag"></i>
                                        </a>
                                    @endif
                                    @if (isFileSupportPreview($fileEntry->type))
                                        <a href="{{ route('file.preview', $fileEntry->shared_id) }}" target="_blank"
                                            rel="tooltip" data-bs-placement="top"
                                            title="{{ lang('Preview File', 'download page') }}">
                                            <i class="far fa-eye"></i>
                                        </a>
                                    @endif
                                </div>
                            </div>
                            <div class="filebox-download">
                                @if ($settings['website_download_waiting_time'] != 0)
                                    @php
                                        $seconds = $settings['website_download_waiting_time'] > 1 ? lang('Seconds') : lang('Second');
                                    @endphp
                                    <button class="download-counter" disabled>
                                        {!! str_replace(
                                            '{seconds}',
                                            '<span class="counter-number">' . $settings['website_download_waiting_time'] . '</span>' . $seconds,
                                            lang('Please Wait {seconds}', 'download page'),
                                        ) !!}
                                    </button>
                                @else
                                    <a class="download-link"
                                        href="{{ route('file.download.approval', [$fileEntry->shared_id, hashid($downloadLink->id), $fileEntry->name]) }}">{{ str_replace('{fileSize}', formatBytes($fileEntry->size), lang('Download ({fileSize})', 'download page')) }}</a>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="row mt-4">
                        <div class="col-12 col-md-8">
                            <div class="px-3">
                                <div class="d-flex align-items-center mb-3">
                                    {!! fileIcon($fileEntry->extension, 'flex-shrink-0') !!}
                                    <div class="ms-3">
                                        <p class="mb-0 text-ellipsis">{{ $fileEntry->name }}</p>
                                        @if ($fileEntry->extension)
                                            <span class="h6">
                                                <strong>{{ str_replace('{file_extension}', strtoupper($fileEntry->extension), lang('File extension (.{file_extension})', 'download page')) }}</strong>
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <p class="mb-1 small">
                                    <strong>{{ lang('File size', 'download page') }} :</strong>
                                    {{ formatBytes($fileEntry->size) }}
                                </p>
                                <p class="mb-1 small">
                                    <strong>{{ lang('Uploaded at', 'download page') }}:</strong>
                                    {{ vDate($fileEntry->created_at) }}
                                </p>
                                <div class="small mt-3">
                                    <p class="mb-2">
                                        <strong>{{ str_replace('{filename}', $fileEntry->name, lang('About {filename}', 'download page')) }}</strong>
                                    </p>
                                    <p clas="mb-0">{{ fileDescription($fileEntry) }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="card p-0 mb-3 shadow-none">
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between">
                                        <strong><i
                                                class="fas fa-download me-1"></i>{{ lang('Downloads', 'download page') }}</strong>
                                        <span>{{ formatNumber($fileEntry->downloads) }}</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <strong><i class="fa fa-eye me-1"></i>{{ lang('Views', 'download page') }}</strong>
                                        <span>{{ formatNumber($fileEntry->views) }}</span>
                                    </li>
                                </ul>
                            </div>
                            {!! ads_download_page_description() !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @if ($additionals['download_page_center_section_status'])
        <section class="section-content py-5">
            <div class="contain">
                <div class="section-content-header left mb-4">
                    <p class="section-content-title h4 mb-0">{{ $additionals['download_page_center_section_title'] }}
                    </p>
                </div>
                {!! $additionals['download_page_center_section_content'] !!}
            </div>
        </section>
    @endif
    @if (
        $settings['website_download_page_blog_posts_status'] &&
            count($blogArticles) > 0 &&
            $settings['website_blog_status']
    )
        <section class="section-content py-5">
            <div class="contain">
                <div class="section-content-header left mb-4">
                    <p class="section-content-title h4 mb-0">{{ lang('Latest blog posts', 'download page') }}</p>
                </div>
                <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-3">
                    @foreach ($blogArticles as $blogArticle)
                        <div class="col">
                            <div class="post post-sm">
                                <div class="post-header">
                                    <a href="{{ route('blog.article', $blogArticle->slug) }}">
                                        <div class="post-img"
                                            style="background-image: url({{ asset($blogArticle->image) }});">
                                        </div>
                                    </a>
                                    <a class="post-section"
                                        href="{{ route('blog.category', $blogArticle->blogCategory->slug) }}">{{ $blogArticle->blogCategory->name }}
                                    </a>
                                </div>
                                <div class="post-body">
                                    <div class="post-meta">
                                        <p class="post-author mb-0">
                                            <i class="fa fa-user"></i>
                                            {{ $blogArticle->admin->firstname }}
                                        </p>
                                        <time class="post-date">
                                            <i class="fa fa-calendar-alt"></i>
                                            {{ vDate($blogArticle->created_at) }}
                                        </time>
                                    </div>
                                    <a href="{{ route('blog.article', $blogArticle->slug) }}"
                                        class="post-title">{{ shortertext($blogArticle->title, 60) }}</a>
                                    <p class="post-text">{{ shortertext($blogArticle->short_description, 100) }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="d-flex justify-content-center mt-4">
                    <a href="{{ route('blog.index') }}" class="btn btn-primary btn-sm py-2">
                        {{ lang('View more', 'download page') }}<i class="fas fa-arrow-right fa-sm ms-2"></i>
                    </a>
                </div>
            </div>
        </section>
    @endif
    {!! ads_download_page_down_bottom() !!}
    @if ($additionals['download_page_bottom_section_status'])
        <section class="section-content py-5">
            <div class="contain">
                <div class="section-content-header left mb-4">
                    <p class="section-content-title h4 mb-0">
                        {{ $additionals['download_page_bottom_section_title'] }}
                    </p>
                </div>
                {!! $additionals['download_page_bottom_section_content'] !!}
            </div>
        </section>
    @endif
    @if ($reportFileStatus)
        <div id="report" class="modal fade" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ lang('Report this file', 'download page') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form action="{{ route('file.report', $fileEntry->shared_id) }}" method="POST">
                            @csrf
                            <div class="row g-3 mb-3">
                                <div class="col-lg-6">
                                    <label class="form-label">{{ lang('Name', 'download page') }} : <span
                                            class="red">*</span></label>
                                    <input type="name" name="name" class="form-control form-control-lg"
                                        value="{{ userAuthInfo()->name ?? '' }}" required>
                                </div>
                                <div class="col-lg-6">
                                    <label class="form-label">{{ lang('Email', 'download page') }} : <span
                                            class="red">*</span></label>
                                    <input type="email" name="email" class="form-control form-control-lg"
                                        value="{{ userAuthInfo()->email ?? '' }}" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ lang('Reason for reporting', 'download page') }} :
                                    <span class="red">*</span></label>
                                <select name="reason" class="form-select form-select-lg" required>
                                    @foreach (reportReasons() as $reasonsKey => $reasonsValue)
                                        <option value="{{ $reasonsKey }}">{{ $reasonsValue }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ lang('Details', 'download page') }} : <span
                                        class="red">*</span></label>
                                <textarea name="details" class="form-control" rows="7"
                                    placeholder="{{ lang('Describe the reason why you reported the file to a maximum of 600 characters', 'download page') }}"
                                    required></textarea>
                            </div>
                            {!! display_captcha() !!}
                            <button type="submit" class="btn btn-primary">{{ lang('Send', 'download page') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
    @include('frontend.includes.shareModal')
    @push('styles_libs')
        <link rel="stylesheet" href="{{ asset('assets/vendor/libs/vironeer/vironeer-icons.min.css') }}">
    @endpush
    @push('top_scripts')
        @php
            $downloadBtnTxt = str_replace('{fileSize}', formatBytes($fileEntry->size), lang('Download ({fileSize})', 'download page'));
        @endphp
        <script>
            "use strict";
            const downloadWaitingTime = "{{ $settings['website_download_waiting_time'] }}";
            const downloadBtnTxt = "{{ $downloadBtnTxt }}";
            const downloadingBtnTxt = "{{ lang('Downloading...', 'download page') }}";
            const reDownloadBtnTxt = "{{ lang('Re-download', 'download page') }}";
            const clickHereTxt = "{{ lang('Click here', 'download page') }}";
            const downloadId = "{{ $fileEntry->shared_id }}";
        </script>
    @endpush
    @push('scripts')
        {!! google_captcha() !!}
    @endpush
@endsection
