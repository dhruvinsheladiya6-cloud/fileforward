<div class="row g-3 mb-4">
    <div class="col-12 col-lg col-xxl">
        <div class="vironeer-counter-card bg-primary">
            <div class="vironeer-counter-card-icon">
                <i class="fas fa-cloud-upload-alt"></i>
            </div>
            <div class="vironeer-counter-card-meta">
                <p class="vironeer-counter-card-title">{{ __('Users Uploads') }}</p>
                <p class="vironeer-counter-card-number">{{ $totalUsersUploads }}</p>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg col-xxl">
        <div class="vironeer-counter-card bg-lg-5">
            <div class="vironeer-counter-card-icon">
                <i class="fas fa-cloud-upload-alt"></i>
            </div>
            <div class="vironeer-counter-card-meta">
                <p class="vironeer-counter-card-title">{{ __('Guests Uploads') }}</p>
                <p class="vironeer-counter-card-number">{{ $totalGuestsUploads }}</p>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg col-xxl">
        <div class="vironeer-counter-card bg-lg-10">
            <div class="vironeer-counter-card-icon">
                <i class="fas fa-database"></i>
            </div>
            <div class="vironeer-counter-card-meta">
                <p class="vironeer-counter-card-title">{{ __('Total Used Space') }}</p>
                <p class="vironeer-counter-card-number">{{ $totalUsedSpace }}</p>
            </div>
        </div>
    </div>
</div>
@if (licenseType(2))
    <div class="row g-3 mb-3">
        <div class="col-12 col-lg-4 col-xxl">
            <div class="vironeer-counter-card bg-lg-1">
                <div class="vironeer-counter-card-icon">
                    <i class="fas fa-tags"></i>
                </div>
                <div class="vironeer-counter-card-meta">
                    <p class="vironeer-counter-card-title">{{ __('Pricing Plans') }}</p>
                    <p class="vironeer-counter-card-number">{{ $totalPlans }}</p>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4 col-xxl">
            <div class="vironeer-counter-card bg-c-3">
                <div class="vironeer-counter-card-icon">
                    <i class="far fa-gem"></i>
                </div>
                <div class="vironeer-counter-card-meta">
                    <p class="vironeer-counter-card-title">{{ __('Subscriptions') }}</p>
                    <p class="vironeer-counter-card-number">{{ $totalSubscriptions }}</p>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4 col-xxl">
            <div class="vironeer-counter-card bg-c-5">
                <div class="vironeer-counter-card-icon">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <div class="vironeer-counter-card-meta">
                    <p class="vironeer-counter-card-title">{{ __('Transactions') }}</p>
                    <p class="vironeer-counter-card-number">{{ $totalTransactions }}</p>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4 col-xxl">
            <div class="vironeer-counter-card bg-c-7">
                <div class="vironeer-counter-card-icon">
                    <i class="fas fa-percent"></i>
                </div>
                <div class="vironeer-counter-card-meta">
                    <p class="vironeer-counter-card-title">{{ __('Coupons') }}</p>
                    <p class="vironeer-counter-card-number">{{ $totalCoupons }}</p>
                </div>
            </div>
        </div>
    </div>
@endif
<div class="row g-3 mb-3">
    <div class="col-12 col-lg-6 col-xxl">
        <div class="vironeer-counter-card bg-c-6">
            <div class="vironeer-counter-card-icon">
                <i class="fa fa-users"></i>
            </div>
            <div class="vironeer-counter-card-meta">
                <p class="vironeer-counter-card-title">{{ __('Users') }}</p>
                <p class="vironeer-counter-card-number">{{ $totalUsers }}</p>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-6 col-xxl">
        <div class="vironeer-counter-card bg-c-12">
            <div class="vironeer-counter-card-icon">
                <i class="far fa-flag"></i>
            </div>
            <div class="vironeer-counter-card-meta">
                <p class="vironeer-counter-card-title">{{ __('Reports') }}</p>
                <p class="vironeer-counter-card-number">{{ $totalReportedFiles }}</p>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-6 col-xxl">
        <div class="vironeer-counter-card bg-c-8">
            <div class="vironeer-counter-card-icon">
                <i class="far fa-file-alt"></i>
            </div>
            <div class="vironeer-counter-card-meta">
                <p class="vironeer-counter-card-title">{{ __('Pages') }}</p>
                <p class="vironeer-counter-card-number">{{ $totalPages }}</p>
            </div>
        </div>
    </div>
    @if ($settings['website_blog_status'])
        <div class="col-12 col-lg-6 col-xxl">
            <div class="vironeer-counter-card bg-c-9">
                <div class="vironeer-counter-card-icon">
                    <i class="fas fa-rss"></i>
                </div>
                <div class="vironeer-counter-card-meta">
                    <p class="vironeer-counter-card-title">{{ __('Blog Articles') }}</p>
                    <p class="vironeer-counter-card-number">{{ $totalArticles }}</p>
                </div>
            </div>
        </div>
    @endif
</div>
