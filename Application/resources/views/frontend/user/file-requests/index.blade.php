@extends('frontend.user.layouts.dash')

@section('section', __('User'))
@section('title', __('Doc requests'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">{{ __('File requests') }}</h4>
        <div class="text-muted small">
            {{ __('Create links where others can upload files directly to your folders.') }}
        </div>
    </div>
    <button class="btn btn-primary" id="btnCreateFileRequest">
        <i class="fas fa-plus me-2"></i>{{ __('Request file') }}
    </button>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="fileRequestsTable">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Created') }}</th>
                        <th>{{ __('Expiration') }}</th>
                        <th>{{ __('Location') }}</th>
                        <th class="text-center">{{ __('Uploads') }}</th>
                        <th class="text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($fileRequests as $req)
                        <tr data-id="{{ $req->id }}" class="file-request-row">
                            <td class="fw-medium">
                                @if($req->folder && $req->folder->shared_id)
                                    <a href="{{ route('user.files.index') }}?folder={{ $req->folder->shared_id }}" 
                                       class="text-decoration-none text-primary" 
                                       title="{{ __('View uploaded files') }}">
                                        {{ $req->title }}
                                    </a>
                                @else
                                    {{ $req->title }}
                                @endif
                                @if(!$req->is_active)
                                    <span class="badge bg-secondary ms-1">{{ __('Closed') }}</span>
                                @elseif($req->expires_at && $req->expires_at->isPast())
                                    <span class="badge bg-warning ms-1">{{ __('Expired') }}</span>
                                @endif
                            </td>
                            <td>{{ optional($req->created_at)->format('d M Y, H:i') }}</td>
                            <td>
                                {{ $req->expires_at ? $req->expires_at->format('d M Y, H:i') : '—' }}
                            </td>
                            <td>{{ $req->folder_path }}</td>
                            <td class="text-center">{{ $req->uploads_count ?? 0 }}</td>
                            <td class="text-end">
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-secondary js-copy-link"
                                            data-url="{{ $req->public_url }}">
                                        <i class="fas fa-link"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary js-manage-request"
                                            data-id="{{ $req->id }}">
                                        <i class="fas fa-sliders-h"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                {{ __('No file requests yet. Click "Request file" to create one.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($fileRequests->hasPages())
        <div class="card-footer">
            {{ $fileRequests->links() }}
        </div>
    @endif
</div>


@include('frontend.user.file-requests.partials.modals')

@endsection

@push('scripts')
<script>
    window.DEFAULT_FILE_REQUEST_FOLDER       = @json($defaultFolderSharedId ?? null);
    window.DEFAULT_FILE_REQUEST_FOLDER_NAME  = @json($defaultFolderName ?? null);
    window.DEFAULT_FILE_REQUEST_FOLDER_LABEL = @json($defaultFolderLabel ?? null);
</script>

<script src="{{ asset('assets/js/file-requests.js') }}"></script>
@endpush

