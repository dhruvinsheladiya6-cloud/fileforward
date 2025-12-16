<div class="row g-3 mb-4">
    {{-- Total --}}
    <div class="col-12 col-lg-3">
        <div class="vironeer-counter-card bg-c-7">
            <div class="vironeer-counter-card-icon"><i class="far fa-trash-alt"></i></div>
            <div class="vironeer-counter-card-meta">
                <p class="vironeer-counter-card-title">{{ __('Total scheduled') }}</p>
                <p class="vironeer-counter-card-number">{{ formatNumber($stats['total'] ?? 0) }}</p>
            </div>
        </div>
    </div>
    {{-- Today --}}
    <div class="col-12 col-lg-3">
        <div class="vironeer-counter-card bg-c-4">
            <div class="vironeer-counter-card-icon"><i class="far fa-clock"></i></div>
            <div class="vironeer-counter-card-meta">
                <p class="vironeer-counter-card-title">{{ __('Due today') }}</p>
                <p class="vironeer-counter-card-number">{{ formatNumber($stats['today'] ?? 0) }}</p>
            </div>
        </div>
    </div>
    {{-- Next 7 --}}
    <div class="col-12 col-lg-3">
        <div class="vironeer-counter-card bg-c-2">
            <div class="vironeer-counter-card-icon"><i class="far fa-calendar-alt"></i></div>
            <div class="vironeer-counter-card-meta">
                <p class="vironeer-counter-card-title">{{ __('Next 7 days') }}</p>
                <p class="vironeer-counter-card-number">{{ formatNumber($stats['next7'] ?? 0) }}</p>
            </div>
        </div>
    </div>
    {{-- Overdue --}}
    <div class="col-12 col-lg-3">
        <div class="vironeer-counter-card bg-danger">
            <div class="vironeer-counter-card-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="vironeer-counter-card-meta">
                <p class="vironeer-counter-card-title">{{ __('Overdue') }}</p>
                <p class="vironeer-counter-card-number">{{ formatNumber($stats['overdue'] ?? 0) }}</p>
            </div>
        </div>
    </div>
</div>
