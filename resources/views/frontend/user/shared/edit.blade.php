@extends('frontend.user.layouts.dash')

@section('section', lang('My Files', 'files'))
@section('title', $fileEntry->name)
{{-- go back to the shared browser using the token --}}
@section('back', route('user.shared.browse', ['token' => $token]))

@section('content')

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="text-center mb-4">
                    <div class="mb-3">
                        @if ($fileEntry->type == 'image')
                            <img src="{{ route('secure.file', hashid($fileEntry->id)) }}"
                                 alt="{{ $fileEntry->name }}" class="rounded-2" width="150" height="150">
                        @else
                            {!! fileIcon($fileEntry->extension, 'vi-3x') !!}
                        @endif
                    </div>
                    <h5>{{ $fileEntry->name }}</h5>
                </div>

                {{-- IMPORTANT: post to the SHARED update route --}}
                <form action="{{ route('user.shared.update', ['token' => $token, 'shared_id' => $fileEntry->shared_id]) }}" method="POST">
                    @csrf

                    {{-- Filename (everyone with edit perm can change) --}}
                    <div class="mb-4">
                        <label class="form-label">
                            {{ lang('File Name', 'files') }} : <span class="red">*</span>
                        </label>
                        <input type="text" name="filename" class="form-control form-control-lg"
                               value="{{ old('filename', $fileEntry->name) }}">
                    </div>

                    {{-- Owner-only controls --}}
                        <div class="mb-4">
                            <label class="form-label">
                                {{ lang('Access status', 'files') }} : <span class="red">*</span>
                            </label>
                            <select name="access_status" class="form-select form-control-lg">
                                <option value="1" {{ (int)$fileEntry->access_status === 1 ? 'selected' : '' }}>
                                    {{ lang('Public', 'files') }}
                                </option>
                                <option value="0" {{ (int)$fileEntry->access_status === 0 ? 'selected' : '' }}>
                                    {{ lang('Private', 'files') }}
                                </option>
                            </select>
                        </div>

                        @if (subscription()->plan->password_protection)
                            <div class="mb-3">
                                <label class="form-label">{{ lang('File Password (Optional)', 'files') }}</label>
                                <div class="form-group input-password">
                                    <input type="password" name="password" id="password"
                                           class="form-control form-control-lg"
                                           placeholder="{{ lang('Enter Password', 'files') }}">
                                    <button type="button" class="text-muted">
                                        <i class="fa fa-eye"></i>
                                    </button>
                                </div>
                                <small class="text-muted">
                                    {{ lang('Leave password empty to remove it', 'files') }}
                                </small>
                            </div>
                        @endif

                        @if ($fileEntry->password)
                            <div class="alert alert-success">
                                <i class="fa fa-lock me-2"></i>{{ lang('File protected by password', 'files') }}
                            </div>
                        @endif

                    <button class="btn btn-primary">{{ lang('Save changes', 'files') }}</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                {{-- Right sidebar: show only safe actions for recipients.
                     For owners, you can keep everything as on the normal page. --}}
                    @if ($fileEntry->access_status)
                        <button class="btn btn-success btn-lg w-100 mb-3 fileManager-share-file"
                                data-bs-toggle="modal" data-bs-target="#shareModal"
                                data-preview="{{ isFileSupportPreview($fileEntry->type) ? 'true' : 'false' }}"
                                data-share='{"filename":"{{ $fileEntry->name }}","download_link":"{{ route('file.download', $fileEntry->shared_id) }}","preview_link":"{{ route('file.preview', $fileEntry->shared_id) }}"}'>
                            <i class="fas fa-share-alt me-2"></i>{{ lang('Share', 'files') }}
                        </button>
                    @endif

                @if (isFileSupportPreview($fileEntry->type))
                    <a href="{{ route('file.preview', $fileEntry->shared_id) }}" target="_blank"
                       class="btn btn-dark btn-lg w-100 mb-3">
                        <i class="fa fa-eye me-2"></i>{{ lang('Preview', 'files') }}
                    </a>
                @endif

                <a href="{{ route('file.download', $fileEntry->shared_id) }}" target="_blank"
                   class="btn btn-primary btn-lg w-100 mb-3">
                    <i class="fa fa-download me-2"></i>{{ lang('Download', 'files') }}
                </a>
<form action="{{ route('user.shared.delete', ['token' => $token, 'shared_id' => $fileEntry->shared_id]) }}" method="POST" class="mb-0">
  @csrf
  @method('DELETE')
  @if (request('folder'))
    <input type="hidden" name="folder" value="{{ request('folder') }}">
  @endif
  <button class="btn btn-danger w-100 btn-lg confirm-action-form">
    <i class="fa fa-trash-alt me-2"></i>{{ __('Delete') }}
  </button>
</form>


            </div>
        </div>
    </div>
</div>

{{-- Optional share modal for owner --}}
<div id="shareModal" class="modal fade share-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content">
            <div class="modal-header border-0 pb-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 pt-1 mb-1">
                <h5 class="mb-4"><i class="fas fa-share-alt me-2"></i>{{ lang('Share this file') }}</h5>
                <p class="mb-4 text-ellipsis filename"></p>
                <div class="mb-3"><div class="share"></div></div>
                <div class="preview-link mb-3">
                    <label class="form-label"><strong>{{ lang('Preview link') }}</strong></label>
                    <div class="input-group">
                        <input id="copy-preview-link" type="text" class="form-control" readonly>
                        <button type="button" class="btn btn-primary btn-md copy"
                                data-clipboard-target="#copy-preview-link"><i class="far fa-clone"></i></button>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label"><strong>{{ lang('Download link') }}</strong></label>
                    <div class="input-group">
                        <input id="copy-download-link" type="text" class="form-control" readonly>
                        <button type="button" class="btn btn-primary btn-md copy"
                                data-clipboard-target="#copy-download-link"><i class="far fa-clone"></i></button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
