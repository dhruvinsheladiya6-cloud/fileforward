@extends('backend.layouts.grid')
@section('section', __('Uploads'))
@section('title', __('User uploads'))

@section('content')
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="d-flex align-items-center">
                <a class="me-3" href="{{ route('superadmin.uploads.users_overview.index') }}">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h5 class="mb-0">
                    {{ __('Uploads for') }}:
                    <a class="text-reset" href="{{ route('superadmin.users.edit', $user->id) }}">
                        {{ $user->firstname }} {{ $user->lastname }}
                    </a>
                </h5>
            </div>
        </div>

        <div class="col-12 col-lg-4 col-xxl">
            <div class="vironeer-counter-card bg-primary">
                <div class="vironeer-counter-card-icon"><i class="fas fa-file-alt"></i></div>
                <div class="vironeer-counter-card-meta">
                    <p class="vironeer-counter-card-title">{{ __('Files And Documents') }}</p>
                    <p class="vironeer-counter-card-number">{{ formatNumber($totalFileDocuments) }}</p>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4 col-xxl">
            <div class="vironeer-counter-card bg-c-12">
                <div class="vironeer-counter-card-icon"><i class="fas fa-images"></i></div>
                <div class="vironeer-counter-card-meta">
                    <p class="vironeer-counter-card-title">{{ __('Images') }}</p>
                    <p class="vironeer-counter-card-number">{{ formatNumber($totalImages) }}</p>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4 col-xxl">
            <div class="vironeer-counter-card bg-c-7">
                <div class="vironeer-counter-card-icon"><i class="fas fa-database"></i></div>
                <div class="vironeer-counter-card-meta">
                    <p class="vironeer-counter-card-title">{{ __('Used Space') }}</p>
                    <p class="vironeer-counter-card-number">{{ $usedSpace }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Upload Mode toggle, if you want it here 
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-3 p-3 bg-light rounded">
            <div>
                <h6 class="mb-1">
                    <i class="fas fa-{{ $uploadMode === 'custom' ? 'cogs' : 'upload' }}"></i>
                    {{ __('Upload Mode') }}
                </h6>
                <small class="text-muted">
                    {{ __('Current') }}:
                    <span class="badge bg-{{ $uploadMode === 'custom' ? 'success' : 'primary' }}">
                        {{ $uploadMode === 'custom' ? __('Custom') : __('Regular') }}
                    </span>
                </small>
            </div>
            <form method="POST" action="{{ route('superadmin.uploads.users.toggle.upload.mode') }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-sm btn-{{ $uploadMode === 'custom' ? 'outline-primary' : 'outline-success' }}">
                    <i class="fas fa-toggle-{{ $uploadMode === 'custom' ? 'off' : 'on' }}"></i>
                    {{ __('Switch to') }} {{ $uploadMode === 'custom' ? __('Regular') : __('Custom') }}
                </button>
            </form>
        </div>
    </div>
    --}}
    <div class="custom-card card">

        <div class="px-3 pt-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-2">
                    <li class="breadcrumb-item">
                        <a href="{{ route('superadmin.uploads.users_overview.files', $user->id) }}">{{ __('Root') }}</a>
                    </li>

                    @if(isset($breadcrumbs) && $breadcrumbs->count())
                        @foreach($breadcrumbs as $node)
                            <li class="breadcrumb-item">
                                <a href="{{ route('superadmin.uploads.users_overview.files', ['user' => $user->id, 'folder' => $node->shared_id]) }}">
                                    {{ shortertext($node->name, 40) }}
                                </a>
                            </li>
                        @endforeach
                    @endif

                    @if(isset($currentFolder))
                        <li class="breadcrumb-item active">{{ shortertext($currentFolder->name, 40) }}</li>
                    @endif
                </ol>
            </nav>

            @if(isset($currentFolder) && $currentFolder->parent_id)
                <a class="btn btn-sm btn-outline-secondary mb-3"
                href="{{ route('superadmin.uploads.users_overview.files', ['user' => $user->id, 'folder' => optional($currentFolder->parent)->shared_id]) }}">
                    <i class="fas fa-level-up-alt me-1"></i>{{ __('Up one level') }}
                </a>
            @elseif(isset($currentFolder))
                <a class="btn btn-sm btn-outline-secondary mb-3"
                href="{{ route('superadmin.uploads.users_overview.files', $user->id) }}">
                    <i class="fas fa-home me-1"></i>{{ __('Back to root') }}
                </a>
            @endif
        </div>


        <div>
            @if ($entries->count() > 0)
                <div class="table-responsive">
                    <table class="vironeer-normal-table table w-100" id="datatable">
                        <thead>
                            <tr>
                                <th class="tb-w-3x">
                                    <input class="multiple-select-check-all form-check-input" type="checkbox">
                                </th>
                                <th class="tb-w-20x">{{ __('File details') }}</th>
                                <th class="tb-w-5x">{{ __('File size') }}</th>
                                <th class="tb-w-3x text-center">{{ __('Downloads') }}</th>
                                <th class="tb-w-3x text-center">{{ __('Views') }}</th>
                                <th class="tb-w-7x text-center">{{ __('Storage') }}</th>
                                <th class="tb-w-3x text-center">{{ __('File expiration') }}</th>
                                <th class="tb-w-3x text-center">{{ __('File Upload date') }}</th>
                                <th class="text-end"><i class="fas fa-sliders-h me-1"></i></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($entries as $e)
                                <tr>
                                    <td>
                                        <input class="form-check-input multiple-select-checkbox" data-id="{{ $e->id }}" type="checkbox">
                                    </td>

                                    <td>
                                        <div class="vironeer-content-box">
                                            <div class="vironeer-content-image text-center">
                                                @if($e->type === 'folder')
                                                    <i class="far fa-folder fa-lg text-warning"></i>
                                                @else
                                                    @if ($e->type == 'image')
                                                        <a class="text-reset" href="{{ route('superadmin.uploads.users.view', $e->shared_id) }}">
                                                            <img src="{{ route('superadmin.uploads.secure', hashid($e->id)) }}" alt="{{ $e->name }}">
                                                        </a>
                                                    @else
                                                        {!! fileIcon($e->extension) !!}
                                                    @endif
                                                @endif
                                            </div>
                                            <div>
                                                @if($e->type === 'folder')
                                                    <a class="text-reset"
                                                    href="{{ route('superadmin.uploads.users_overview.files', ['user' => $user->id, 'folder' => $e->shared_id]) }}">
                                                        {{ shortertext($e->name, 50) }}
                                                    </a>
                                                @else
                                                    <a class="text-reset"
                                                    href="{{ route('superadmin.uploads.users.view', $e->shared_id) }}">
                                                        {{ shortertext($e->name, 50) }}
                                                    </a>
                                                    <p class="text-muted mb-0">{{ shortertext($e->mime, 50) ?? __('Unknown') }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    {{-- File size --}}
                                    <td>{{ $e->type === 'folder' ? '—' : formatBytes($e->size) }}</td>

                                    {{-- Downloads / Views --}}
                                    <td class="text-center">{{ formatNumber($e->downloads) }}</td>
                                    <td class="text-center">{{ formatNumber($e->views) }}</td>

                                    {{-- Storage --}}
                                    <td class="text-center">
                                        @if ($e->type === 'folder')
                                            —
                                        @else
                                            @if ($e->storageProvider && $e->storageProvider->symbol == 'local')
                                                <span><i class="fas fa-server me-2"></i>{{ $e->storageProvider->symbol }}</span>
                                            @elseif($e->storageProvider)
                                                <a class="text-dark capitalize"
                                                href="{{ route('superadmin.settings.storage.edit', $e->storageProvider->id) }}">
                                                    <i class="fas fa-server me-2"></i>{{ $e->storageProvider->symbol }}
                                                </a>
                                            @else
                                                —
                                            @endif
                                        @endif
                                    </td>

                                    {{-- File expiration --}}
                                    <td class="text-center">
                                        {{ $e->type === 'folder' ? '—' : ($e->expiry_at ? vDate($e->expiry_at) : __('Unlimited time')) }}
                                    </td>

                                    {{-- Upload date (works for folders too) --}}
                                    <td class="text-center">{{ vDate($e->created_at) }}</td>

                                    {{-- Actions --}}
                                    <td>
                                        <div class="text-end">
                                            <button type="button" class="btn btn-sm rounded-3" data-bs-toggle="dropdown" aria-expanded="true">
                                                <i class="fa fa-ellipsis-v fa-sm text-muted"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-md-end dropdown-menu-lg" data-popper-placement="bottom-end">
                                                @if ($e->type === 'folder')
                                                    <li>
                                                        <a class="dropdown-item"
                                                        href="{{ route('superadmin.uploads.users_overview.files', ['user' => $user->id, 'folder' => $e->shared_id]) }}">
                                                            <i class="far fa-folder-open me-2"></i>{{ __('Open Folder') }}
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider" /></li>
                                                    <li>
                                                        <form action="{{ route('superadmin.uploads.users.destroy', $e->shared_id) }}" method="POST">
                                                            @csrf @method('DELETE')
                                                            <button class="vironeer-able-to-delete dropdown-item text-danger">
                                                                <i class="far fa-trash-alt me-2"></i>{{ __('Delete') }}
                                                            </button>
                                                        </form>
                                                    </li>
                                                @else
                                                    @if ($e->access_status)
                                                        <li>
                                                            <a class="dropdown-item" target="_blank"
                                                            href="{{ route('file.download', $e->shared_id) }}">
                                                                <i class="fas fa-external-link-alt me-2"></i>{{ __('Preview') }}
                                                            </a>
                                                        </li>
                                                    @endif
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('superadmin.uploads.users.download', $e->shared_id) }}">
                                                            <i class="fas fa-download me-2"></i>{{ __('Download') }}
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('superadmin.uploads.users.view', $e->shared_id) }}">
                                                            <i class="fas fa-desktop me-2"></i>{{ __('File details') }}
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider" /></li>
                                                    <li>
                                                        <form action="{{ route('superadmin.uploads.users.destroy', $e->shared_id) }}" method="POST">
                                                            @csrf @method('DELETE')
                                                            <button class="vironeer-able-to-delete dropdown-item text-danger">
                                                                <i class="far fa-trash-alt me-2"></i>{{ __('Delete') }}
                                                            </button>
                                                        </form>
                                                    </li>
                                                @endif
                                            </ul>
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

    @push('styles_libs')
        <link rel="stylesheet" href="{{ asset('assets/vendor/libs/vironeer/vironeer-icons.min.css') }}">
    @endpush
@endsection
