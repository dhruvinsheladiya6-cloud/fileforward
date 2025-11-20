@extends('backend.layouts.grid')
@section('section', __('Uploads'))
@section('title', __('Trash overview'))

@section('content')
    {{-- SUMMARY ROW --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-lg-4 col-xxl">
            <div class="vironeer-counter-card bg-primary">
                <div class="vironeer-counter-card-icon"><i class="fas fa-file-alt"></i></div>
                <div class="vironeer-counter-card-meta">
                    <p class="vironeer-counter-card-title">{{ __('Trashed Files & Documents') }}</p>
                    <p class="vironeer-counter-card-number">{{ formatNumber($summaryDocs ?? 0) }}</p>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4 col-xxl">
            <div class="vironeer-counter-card bg-c-12">
                <div class="vironeer-counter-card-icon"><i class="fas fa-images"></i></div>
                <div class="vironeer-counter-card-meta">
                    <p class="vironeer-counter-card-title">{{ __('Trashed Images') }}</p>
                    <p class="vironeer-counter-card-number">{{ formatNumber($summaryImages ?? 0) }}</p>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4 col-xxl">
            <div class="vironeer-counter-card bg-c-7">
                <div class="vironeer-counter-card-icon"><i class="fas fa-database"></i></div>
                <div class="vironeer-counter-card-meta">
                    <p class="vironeer-counter-card-title">{{ __('Trash Used Space') }}</p>
                    <p class="vironeer-counter-card-number">{{ $summaryUsedSpace ?? '0 B' }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="custom-card card">
        <div>
            @if ($users->count() > 0)
                <div class="table-responsive">
                    <table class="vironeer-normal-table table w-100" id="datatable">
                        <thead>
                        <tr>
                            <th class="tb-w-10x">{{ __('User details') }}</th>
                            <th class="tb-w-7x text-center">{{ __('Trash Used Space') }}</th>
                            <th class="tb-w-5x text-center">{{ __('Downloads') }}</th>
                            <th class="tb-w-5x text-center">{{ __('Views') }}</th>
                            <th class="tb-w-5x text-center">{{ __('Trashed Files') }}</th>
                            <th class="text-end"><i class="fas fa-sliders-h me-1"></i></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <td>
                                    <div class="vironeer-user-box">
                                        <a class="vironeer-user-avatar" href="{{ route('superadmin.users.edit', $user->id) }}">
                                            <img src="{{ asset($user->avatar) }}" alt="User" />
                                        </a>
                                        <div>
                                            <a class="text-reset" href="{{ route('superadmin.users.edit', $user->id) }}">
                                                {{ shortertext($user->firstname . ' ' . $user->lastname, 50) }}
                                            </a>
                                            <p class="text-muted mb-0">{{ shortertext($user->email, 50) }}</p>
                                            <small class="text-muted">{{ __('Trashed Items') }}: {{ formatNumber($user->files_count ?? 0) }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center" data-order="{{ $user->total_size ?? 0 }}">
                                    {{ isset($user->total_size) ? formatBytes($user->total_size) : '0 B' }}
                                </td>
                                <td class="text-center" data-order="{{ $user->total_downloads ?? 0 }}">
                                    {{ formatNumber($user->total_downloads ?? 0) }}
                                </td>
                                <td class="text-center" data-order="{{ $user->total_views ?? 0 }}">
                                    {{ formatNumber($user->total_views ?? 0) }}
                                </td>
                                <td class="text-center" data-order="{{ $user->files_documents_count ?? 0 }}">
                                    {{ formatNumber($user->files_documents_count ?? 0) }}
                                </td>
                                <td>
                                    <div class="text-end">
                                        <a class="btn btn-sm btn-primary"
                                           href="{{ route('superadmin.uploads.users_trash.files', $user->id) }}">
                                            &gt;
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                @include('backend.includes.empty')
            @endif
        </div>
    </div>

    {{ $users->links() }}
@endsection
