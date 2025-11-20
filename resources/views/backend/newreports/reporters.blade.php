@extends('backend.layouts.grid')
@section('section', __('New Reports'))
@section('title', __('Reporters | File #') . $fileEntry->id)
@section('back', route('superadmin.newreports.reasons', $fileEntry->id))
@section('content')

    <div class="card custom-card mb-3">
        <div class="card-body d-flex align-items-center">
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
                <a href="{{ route($fileEntry->user_id ? 'superadmin.uploads.users.view' : 'superadmin.uploads.guests.view', $fileEntry->shared_id) }}"
                   target="_blank" class="text-dark">
                    <h5 class="mb-1">{{ shortertext($fileEntry->name, 100) }}</h5>
                    <p class="mb-0 text-muted">{{ shortertext($fileEntry->mime, 50) ?? __('Unknown') }}</p>
                </a>
            </div>
            <div class="flex-grow-3 ms-3">
                <a href="{{ route('superadmin.newreports.reasons', $fileEntry->id) }}" class="btn btn-outline-dark">
                    <i class="fa fa-list-ul me-2"></i>{{ __('Reasons & counts') }}
                </a>
            </div>
        </div>
    </div>

    @if ($reasonFilter)
        <div class="alert alert-secondary d-flex justify-content-between align-items-center">
            <div>
                <strong>{{ __('Filtered by reason:') }}</strong>
                {{ reportReasons()[$reasonFilter] ?? $reasonFilter }}
            </div>
            <a class="btn btn-sm btn-outline-dark" href="{{ route('superadmin.newreports.reporters', $fileEntry->id) }}">
                {{ __('Clear filter') }}
            </a>
        </div>
    @endif

    <div class="card custom-card">
        <table class="table w-100">
            <thead>
                <tr>
                    <th class="tb-w-3x">{{ __('#') }}</th>
                    <th class="tb-w-7x">{{ __('Reported by') }}</th>
                    <th class="tb-w-7x">{{ __('Email') }}</th>
                    <th class="tb-w-7x">{{ __('Reason') }}</th>
                    <th class="tb-w-20x">{{ __('Details') }}</th>
                    <th class="tb-w-7x">{{ __('Status') }}</th>
                    <th class="tb-w-7x">{{ __('Report date') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($reports as $rep)
                    <tr>
                        <td>{{ $rep->id }}</td>
                        <td>{{ shortertext($rep->name, 40) }}</td>
                        <td>{{ shortertext($rep->email, 60) }}</td>
                        <td>{{ shortertext(reportReasons()[$rep->reason] ?? $rep->reason, 60) }}</td>
                        <td>{{ shortertext($rep->details, 120) }}</td>
                        <td>
                            @if ($rep->admin_has_viewed)
                                <span class="badge bg-success">{{ __('Reviewed') }}</span>
                            @else
                                <span class="badge bg-c-7">{{ __('Waiting review') }}</span>
                            @endif
                        </td>
                        <td>{{ vDate($rep->created_at) }}</td>
                        <td class="text-end">
                            <div class="dropdown">
                                <button type="button" class="btn btn-sm rounded-3" data-bs-toggle="dropdown" aria-expanded="true">
                                    <i class="fa fa-ellipsis-v fa-sm text-muted"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-sm-end dropdown-menu-lg" data-popper-placement="bottom-end">
                                    <li>
                                        <a class="dropdown-item"
                                           href="{{ route('superadmin.reports.view', $rep->id) }}">
                                            <i class="fa fa-desktop me-2"></i>{{ __('Report Details (original)') }}
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"/></li>
                                    <li>
                                        <form action="{{ route('superadmin.reports.destroy', $rep->id) }}" method="POST">
                                            @csrf @method('DELETE')
                                            <button class="vironeer-able-to-delete dropdown-item text-danger">
                                                <i class="far fa-trash-alt me-2"></i>{{ __('Delete') }}
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">{{ __('No reports found') }}</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="card-footer">
            {{ $reports->links() }}
        </div>
    </div>

    @push('styles_libs')
        <link rel="stylesheet" href="{{ asset('assets/vendor/libs/vironeer/vironeer-icons.min.css') }}">
    @endpush
@endsection
