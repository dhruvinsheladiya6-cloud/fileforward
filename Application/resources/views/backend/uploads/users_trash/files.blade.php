@extends('backend.layouts.grid')
@section('section', __('Uploads'))
@section('title', __('User trash'))

@section('content')
    {{-- === your counters row stays unchanged === --}}

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

    <div class="custom-card card">
        <div class="card-header p-3 border-bottom-small d-flex flex-wrap gap-2">
            <form action="{{ route('superadmin.uploads.users_trash.files', $user->id) }}" method="GET" class="flex-grow-1">
                <div class="input-group vironeer-custom-input-group">
                    <input type="text" name="search" class="form-control"
                           placeholder="{{ __('Search trashed items...') }}"
                           value="{{ $search }}">
                    @if(request()->has('folder'))
                        <input type="hidden" name="folder" value="{{ request('folder') }}">
                    @endif
                    <button class="btn btn-secondary" type="submit">
                        <i class="fa fa-search"></i>
                    </button>
                    @if ($search)
                        <a href="{{ route('superadmin.uploads.users_trash.files', $user->id) }}" class="btn btn-secondary">
                            {{ __('View All') }}
                        </a>
                    @endif
                </div>
            </form>

            {{-- bulk actions remain the same --}}
            <form class="multiple-select-restore-form d-none" action="{{ route('superadmin.uploads.users_trash.restore.selected') }}" method="POST">
                @csrf
                <input type="hidden" name="restore_ids" class="multiple-select-restore-ids" value="">
                <button class="btn btn-success">
                    <i class="fas fa-undo me-2"></i>{{ __('Restore Selected') }}
                </button>
            </form>

            <form class="multiple-select-delete-form d-none" action="{{ route('superadmin.uploads.users_trash.schedule.purge.selected') }}" method="POST">
                @csrf
                <input type="hidden" name="delete_ids" class="multiple-select-delete-ids" value="">
                <button class="vironeer-able-to-delete btn btn-danger">
                    <i class="far fa-trash-alt me-2"></i>{{ __('Schedule Purge') }}
                </button>
            </form>
        </div>

        {{-- Breadcrumb --}}
        <div class="px-3 pt-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-2">
                    <li class="breadcrumb-item">
                        <a href="{{ route('superadmin.uploads.users_trash.files', $user->id) }}">{{ __('Root') }}</a>
                    </li>
                    @if(isset($breadcrumbs) && $breadcrumbs->count())
                        @foreach($breadcrumbs as $node)
                            <li class="breadcrumb-item">
                                <a href="{{ route('superadmin.uploads.users_trash.files', ['user' => $user->id, 'folder' => $node->shared_id]) }}">
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
                   href="{{ route('superadmin.uploads.users_trash.files', ['user' => $user->id, 'folder' => optional($currentFolder->parent)->shared_id]) }}">
                    <i class="fas fa-level-up-alt me-1"></i>{{ __('Up one level') }}
                </a>
            @elseif(isset($currentFolder))
                <a class="btn btn-sm btn-outline-secondary mb-3"
                   href="{{ route('superadmin.uploads.users_trash.files', $user->id) }}">
                    <i class="fas fa-home me-1"></i>{{ __('Back to root') }}
                </a>
            @endif
        </div>

        {{-- Single table: folders first, then files --}}
        <div>
            @if ($entries->count() > 0)
                <div class="table-responsive">
                    <table class="vironeer-normal-table table w-100">
                        <thead>
                        <tr>
                            <th class="tb-w-3x">
                                <input class="multiple-select-check-all form-check-input" type="checkbox">
                            </th>
                            <th class="tb-w-20x">{{ __('Item') }}</th>
                            <th class="tb-w-5x text-center">{{ __('Type') }}</th>
                            <th class="tb-w-5x text-center">{{ __('Size') }}</th>
                            <th class="tb-w-3x text-center">{{ __('Downloads') }}</th>
                            <th class="tb-w-3x text-center">{{ __('Views') }}</th>
                            <th class="tb-w-7x text-center">{{ __('Storage') }}</th>
                            <th class="tb-w-5x text-center">{{ __('Deleted at') }}</th>
                            <th class="tb-w-5x text-center">{{ __('Purge at') }}</th>
                            <th class="text-end"><i class="fas fa-sliders-h me-1"></i></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($entries as $e)
                            <tr>
                                <td>
                                    <input class="form-check-input multiple-select-checkbox"
                                           data-id="{{ $e->id }}" type="checkbox">
                                </td>

                                {{-- NAME + icon, folder is clickable to drill-in using shared_id --}}
                                <td>
                                    <div class="vironeer-content-box">
                                        <div class="vironeer-content-image text-center">
                                            @if($e->type === 'folder')
                                                <i class="far fa-folder fa-lg text-warning"></i>
                                            @else
                                                {!! fileIcon($e->extension) !!}
                                            @endif
                                        </div>
                                        <div>
                                            @if($e->type === 'folder')
                                                <a class="text-reset"
                                                   href="{{ route('superadmin.uploads.users_trash.files', ['user' => $user->id, 'folder' => $e->shared_id]) }}">
                                                    {{ shortertext($e->name, 50) }}
                                                </a>
                                            @else
                                                <span class="text-reset">{{ shortertext($e->name, 50) }}</span>
                                            @endif
                                            @if($e->type !== 'folder')
                                                <p class="text-muted mb-0">{{ shortertext($e->mime, 50) ?? __('Unknown') }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <td class="text-center">
                                    {{ ucfirst($e->type) }}
                                </td>

                                <td class="text-center">
                                    {{ $e->type === 'folder' ? '—' : formatBytes($e->size) }}
                                </td>

                                <td class="text-center">{{ formatNumber($e->downloads) }}</td>
                                <td class="text-center">{{ formatNumber($e->views) }}</td>

                                <td class="text-center">
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
                                </td>

                                <td class="text-center">{{ $e->deleted_at ? vDate($e->deleted_at) : '-' }}</td>
                                <td class="text-center">{{ $e->purge_at ? vDate($e->purge_at) : __('Not scheduled') }}</td>

                                <td>
                                    {{-- Actions: folders get Restore/Schedule like files --}}
                                    <div class="text-end">
                                        <button type="button" class="btn btn-sm rounded-3" data-bs-toggle="dropdown" aria-expanded="true">
                                            <i class="fa fa-ellipsis-v fa-sm text-muted"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-md-end dropdown-menu-lg" data-popper-placement="bottom-end">
                                            <li>
                                                <form action="{{ route('superadmin.uploads.users_trash.restore', $e->shared_id) }}" method="POST">
                                                    @csrf
                                                    <button class="dropdown-item">
                                                        <i class="fas fa-undo me-2"></i>{{ __('Restore') }}
                                                    </button>
                                                </form>
                                            </li>
                                            <li>
                                                <form action="{{ route('superadmin.uploads.users_trash.schedule.purge', $e->shared_id) }}" method="POST">
                                                    @csrf
                                                    <button class="dropdown-item text-danger">
                                                        <i class="far fa-trash-alt me-2"></i>{{ __('Schedule Purge') }}
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
            @else
                @include('backend.includes.empty')
            @endif
        </div>
    </div>

    {{ $entries->links() }}

    @push('styles_libs')
        <link rel="stylesheet" href="{{ asset('assets/vendor/libs/vironeer/vironeer-icons.min.css') }}">
    @endpush
@endsection
