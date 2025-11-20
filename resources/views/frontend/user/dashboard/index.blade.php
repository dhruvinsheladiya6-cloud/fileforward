@extends('frontend.user.layouts.dash')
@section('section', lang('User', 'user'))
@section('title', lang('Dashboard', 'dashboard'))
@section('upload', true)
@section('content')
    <div class="row g-3 mb-3">
        {{-- Active Files --}}
        <div class="col-12 col-md-6 col-lg-4 col-xl-3">
            <div class="dash-card card-dash">
                <div class="dash-card-body">
                    <div class="dash-card-info">
                        <h6 class="dash-card-title">{{ formatNumber($activeFiles) }}</h6>
                        <p class="dash-card-text">{{ lang('Active Files', 'dashboard') }}</p>
                    </div>
                    <div class="dash-card-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Trashed Files --}}
        <div class="col-12 col-md-6 col-lg-4 col-xl-3">
            <div class="dash-card card-dash color_2">
                <div class="dash-card-body">
                    <div class="dash-card-info">
                        <h6 class="dash-card-title">{{ formatNumber($trashedFiles) }}</h6>
                        <p class="dash-card-text">{{ lang('Trashed Files', 'dashboard') }}</p>
                    </div>
                    <div class="dash-card-icon">
                        <i class="fas fa-trash"></i>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Total Downloads --}}
        <div class="col-12 col-md-6 col-lg-4 col-xl-3">
            <div class="dash-card card-dash color_3">
                <div class="dash-card-body">
                    <div class="dash-card-info">
                        <h6 class="dash-card-title">{{ formatNumber($totalDownloads) }}</h6>
                        <p class="dash-card-text">{{ lang('Downloads', 'dashboard') }}</p>
                    </div>
                    <div class="dash-card-icon">
                        <i class="fas fa-download"></i>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Total Views --}}
        <div class="col-12 col-md-6 col-lg-4 col-xl-3">
            <div class="dash-card card-dash color_4">
                <div class="dash-card-body">
                    <div class="dash-card-info">
                        <h6 class="dash-card-title">{{ formatNumber($totalViews) }}</h6>
                        <p class="dash-card-text">{{ lang('Total Views', 'dashboard') }}</p>
                    </div>
                    <div class="dash-card-icon">
                        <i class="fa fa-eye"></i>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Shared Files --}}
        <div class="col-12 col-md-6 col-lg-4 col-xl-3">
            <div class="dash-card card-dash color_5">
                <div class="dash-card-body">
                    <div class="dash-card-info">
                        <h6 class="dash-card-title">{{ formatNumber($sharedFiles) }}</h6>
                        <p class="dash-card-text">{{ lang('Shared Files', 'dashboard') }}</p>
                    </div>
                    <div class="dash-card-icon">
                        <i class="fas fa-share-alt"></i>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Storage Used --}}
        <div class="col-12 col-md-6 col-lg-4 col-xl-3">
            <div class="dash-card card-dash color_6">
                <div class="dash-card-body">
                    <div class="dash-card-info">
                        <h6 class="dash-card-title">{{ formatBytes($totalStorageUsed) }}</h6>
                        <p class="dash-card-text">{{ lang('Storage Used', 'dashboard') }}</p>
                    </div>
                    <div class="dash-card-icon">
                        <i class="fas fa-hdd"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="dash-card">
        <div class="dash-card-header py-3 border-0">
            <h5 class="mb-0">{{ lang('Your upload statistics for current month', 'dashboard') }}</h5>
        </div>
        <div class="dash-card-body pt-2">
            <div class="dash-chart">
                <canvas id="uploads-chart"></canvas>
            </div>
        </div>
    </div>
    
    @push('scripts_libs')
        <script src="{{ asset('assets/vendor/libs/chartjs/chart.min.js') }}"></script>
    @endpush
@endsection