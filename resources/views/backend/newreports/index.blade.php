@extends('backend.layouts.grid')
@section('title', __('New Reports (by File)'))
@section('content')
    <div class="row g-3 mb-4">
        <div class="col-12 col-lg-4 col-xxl">
            <div class="vironeer-counter-card bg-c-7">
                <div class="vironeer-counter-card-icon">
                    <i class="far fa-clock"></i>
                </div>
                <div class="vironeer-counter-card-meta">
                    <p class="vironeer-counter-card-title">{{ __('Files with waiting review') }}</p>
                    <p class="vironeer-counter-card-number">{{ formatNumber($waitingReviewFiles) }}</p>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4 col-xxl">
            <div class="vironeer-counter-card bg-c-4">
                <div class="vironeer-counter-card-icon">
                    <i class="far fa-check-circle"></i>
                </div>
                <div class="vironeer-counter-card-meta">
                    <p class="vironeer-counter-card-title">{{ __('Files fully reviewed') }}</p>
                    <p class="vironeer-counter-card-number">{{ formatNumber($reviewedFiles) }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card custom-card">
        <table id="datatable" class="table w-100">
            <thead>
                <tr>
                    <th class="tb-w-3x">{{ __('#') }}</th>
                    <th class="tb-w-20x">{{ __('File details') }}</th>
                    <th class="tb-w-3x">{{ __('Total reports') }}</th>
                    <th class="tb-w-3x">{{ __('Waiting') }}</th>
                    <th class="tb-w-3x">{{ __('Reviewed') }}</th>
                    <th class="tb-w-7x">{{ __('Last reported at') }}</th>
                    <th class="tb-w-5x">{{ __('Status') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    @php $file = $row->file_entry; @endphp
                    <tr class="item">
                        <td>{{ $file->id }}</td>
                        <td>
                            <div class="vironeer-content-box">
                                <a class="vironeer-content-image text-center"
                                   href="{{ route($file->user_id ? 'superadmin.uploads.users.view' : 'superadmin.uploads.guests.view', $file->shared_id) }}">
                                    @if ($file->type == 'image')
                                        <img src="{{ route('superadmin.uploads.secure', hashid($file->id)) }}" alt="{{ $file->name }}">
                                    @else
                                        {!! fileIcon($file->extension) !!}
                                    @endif
                                </a>
                                <div>
                                    <a class="text-reset"
                                       href="{{ route($file->user_id ? 'superadmin.uploads.users.view' : 'superadmin.uploads.guests.view', $file->shared_id) }}">
                                        {{ shortertext($file->name, 50) }}
                                    </a>
                                    <p class="text-muted mb-0">{{ shortertext($file->mime, 50) ?? __('Unknown') }}</p>
                                </div>
                            </div>
                        </td>
                        <td>{{ formatNumber($row->reports_count) }}</td>
                        <td>{{ formatNumber($row->waiting_count) }}</td>
                        <td>{{ formatNumber($row->reviewed_count) }}</td>
                        <td>{{ vDate($row->last_report_at) }}</td>
                        <td>
                            @if ($row->status === 'waiting')
                                <span class="badge bg-c-7">{{ __('Waiting review') }}</span>
                            @else
                                <span class="badge bg-success">{{ __('Reviewed') }}</span>
                            @endif
                        </td>
                        <td>
                            <div class="text-end">
                                <button type="button" class="btn btn-sm rounded-3" data-bs-toggle="dropdown" aria-expanded="true">
                                    <i class="fa fa-ellipsis-v fa-sm text-muted"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-sm-end dropdown-menu-lg" data-popper-placement="bottom-end">
                                    <li>
                                        <a class="dropdown-item"
                                           href="{{ route('superadmin.newreports.reasons', $file->id) }}">
                                            <i class="fa fa-list-ul me-2"></i>{{ __('Reasons & counts') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item"
                                           href="{{ route('superadmin.newreports.reporters', $file->id) }}">
                                            <i class="fa fa-users me-2"></i>{{ __('View all reporters') }}
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"/></li>
                                    <li>
                                        <a class="dropdown-item"
                                           href="{{ route($file->user_id ? 'superadmin.uploads.users.view' : 'superadmin.uploads.guests.view', $file->shared_id) }}"
                                           target="_blank">
                                            <i class="fa fa-eye me-2"></i>{{ __('Reported File') }}
                                        </a>
                                    </li>
                                    {{-- Optional bulk actions --}}
                                    {{-- 
                                    <li><hr class="dropdown-divider"/></li>
                                    <li>
                                        <form action="{{ route('superadmin.newreports.markAllReviewed', $file->id) }}" method="POST">
                                            @csrf
                                            <button class="vironeer-form-confirm dropdown-item">
                                                <i class="far fa-check-circle me-2"></i>{{ __('Mark all as reviewed') }}
                                            </button>
                                        </form>
                                    </li>--}}
                                    <li>
                                        <form action="{{ route('superadmin.newreports.destroyAll', $file->id) }}" method="POST">
                                            @csrf @method('DELETE')
                                            <button class="vironeer-able-to-delete dropdown-item text-danger">
                                                <i class="far fa-trash-alt me-2"></i>{{ __('Delete all reports') }}
                                            </button>
                                        </form>
                                    </li>
                                    
                                </ul>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @push('styles_libs')
        <link rel="stylesheet" href="{{ asset('assets/vendor/libs/vironeer/vironeer-icons.min.css') }}">
    @endpush
@endsection
