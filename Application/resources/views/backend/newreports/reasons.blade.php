@extends('backend.layouts.grid')
@section('section', __('New Reports'))
@section('title', __('Reasons | File #') . $fileEntry->id)
@section('back', route('superadmin.newreports.index'))

@section('content')
    <div class="card custom-card mb-2">
        <div class="card-header bg-c-7 text-white">
            {{ __('Reported file') }}
        </div>
        <div class="card-body">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0">
                    <a href="{{ route($fileEntry->user_id ? 'superadmin.uploads.users.view' : 'superadmin.uploads.guests.view', $fileEntry->shared_id) }}" target="_blank">
                        @if ($fileEntry->type == 'image')
                            <img class="rounded-2"
                                 src="{{ route('superadmin.uploads.secure', hashid($fileEntry->id)) }}"
                                 alt="{{ $fileEntry->name }}" width="60" height="60">
                        @else
                            {!! fileIcon($fileEntry->extension) !!}
                        @endif
                    </a>
                </div>
                <div class="flex-grow-1 ms-3">
                    <a href="{{ route($fileEntry->user_id ? 'superadmin.uploads.users.view' : 'superadmin.uploads.guests.view', $fileEntry->shared_id) }}" target="_blank" class="text-dark">
                        <h5 class="mb-1">{{ shortertext($fileEntry->name, 100) }}</h5>
                        <p class="mb-0 text-muted">{{ shortertext($fileEntry->mime, 50) ?? __('Unknown') }}</p>
                    </a>
                </div>
                <div class="flex-grow-3 ms-3">
                    <a href="{{ route('superadmin.newreports.reporters', $fileEntry->id) }}" class="btn btn-dark">
                        <i class="fa fa-users me-2"></i>{{ __('View all reporters') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Highlight most common reason --}}
    @if($topReason)
        <div class="vironeer-alert alert alert-info mb-3">
            <strong>{{ __('Most common reason:') }}</strong>
            {{ reportReasons()[$topReason->reason] ?? $topReason->reason }}
            <span class="badge bg-dark ms-2">{{ formatNumber($topReason->count_reason) }}</span>
            <span class="text-muted ms-2">/ {{ formatNumber($totalReports) }} {{ __('total reports') }}</span>
        </div>
    @endif

    <div class="card custom-card">
        <div class="card-header">{{ __('All reasons') }}</div>
        <table class="table mb-0">
            <thead>
                <tr>
                    <th class="tb-w-3x">{{ __('#') }}</th>
                    <th class="tb-w-20x">{{ __('Reason') }}</th>
                    <th class="tb-w-3x">{{ __('Count') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($reasons as $i => $r)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ shortertext(reportReasons()[$r->reason] ?? $r->reason, 80) }}</td>
                        <td><span class="badge bg-secondary">{{ formatNumber($r->count_reason) }}</span></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-dark"
                               href="{{ route('superadmin.newreports.reporters', ['fileEntryId' => $fileEntry->id, 'reason' => $r->reason]) }}">
                                <i class="fa fa-users me-2"></i>{{ __('View reporters for this reason') }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">{{ __('No reasons found') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @push('styles_libs')
        <link rel="stylesheet" href="{{ asset('assets/vendor/libs/vironeer/vironeer-icons.min.css') }}">
    @endpush
@endsection
