<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ lang('Protected Upload Link', 'user') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Bootstrap CSS (remove if your layout already includes it) --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    {{-- Font Awesome (optional, for icons) --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        body {
            background: #f5f6f8;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        .card {
            border-radius: 0.75rem;
            border: none;
            box-shadow: 0 12px 25px rgba(15, 23, 42, 0.12);
        }
        .card-header {
            border-bottom: none;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #fff;
            border-radius: 0.75rem 0.75rem 0 0;
        }
        .card-header h5 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: .5rem;
            font-weight: 600;
        }
        .card-body {
            padding: 1.75rem 1.75rem 1.5rem;
        }
        .card-footer {
            border-top: none;
            background: #f9fafb;
            border-radius: 0 0 0.75rem 0.75rem;
        }
        .form-control {
            border-radius: .5rem;
        }
        .btn-primary {
            border-radius: .5rem;
        }
        .badge-soft {
            border-radius: 999px;
            padding: .35rem .75rem;
            font-size: .75rem;
            font-weight: 500;
        }
        .badge-soft-warning {
            background: rgba(245, 158, 11, .12);
            color: #92400e;
        }
        .badge-soft-success {
            background: rgba(22, 163, 74, .12);
            color: #166534;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5>
                            <i class="fas fa-lock"></i>
                            {{ lang('Protected Upload Link', 'user') }}
                        </h5>
                    </div>

                    <div class="card-body">
                        <p class="text-muted mb-3">
                            {{ lang('This upload link is protected. Please enter the password to continue and upload files.', 'user') }}
                        </p>

                        {{-- Status chip: active + expiry (if available) --}}
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="badge badge-soft-success">
                                <i class="fas fa-check-circle me-1"></i>
                                {{ lang('Active link', 'user') }}
                            </span>

                            @if (!empty($fileRequest->expires_at))
                                <span class="badge badge-soft-warning">
                                    <i class="fas fa-clock me-1"></i>
                                    {{ lang('Expires:', 'user') }} {{ $fileRequest->expires_at->format('Y-m-d H:i') }}
                                </span>
                            @endif
                        </div>

                        {{-- Error messages --}}
                        @if ($errors->any())
                            <div class="alert alert-danger py-2">
                                <i class="fas fa-exclamation-circle me-1"></i>
                                {{ $errors->first() }}
                            </div>
                        @endif

                        {{-- Unlock form --}}
                        <form method="POST" action="{{ route('file-request.unlock', $fileRequest->token) }}">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label">
                                    {{ lang('Password', 'user') }}
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-key"></i>
                                    </span>
                                    <input
                                        type="password"
                                        name="password"
                                        class="form-control @error('password') is-invalid @enderror"
                                        placeholder="{{ lang('Enter link password', 'user') }}"
                                        required
                                        autocomplete="new-password"
                                    >
                                </div>
                                @error('password')
                                    <div class="invalid-feedback d-block">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-unlock-alt me-1"></i>
                                {{ lang('Unlock & Continue', 'user') }}
                            </button>
                        </form>
                    </div>

                    <div class="card-footer small text-muted d-flex justify-content-between align-items-center">
                        <span>
                            <i class="fas fa-user-circle me-1"></i>
                            {{ lang('Owner:', 'user') }}
                            {{ $fileRequest->owner?->email }}
                        </span>
                        <span>
                            <i class="fas fa-folder-open me-1"></i>
                            {{ $fileRequest->title ?? lang('Upload request', 'user') }}
                        </span>
                    </div>
                </div>

                <p class="text-center text-muted mt-3 small">
                    {{ config('app.name') }} &middot; {{ lang('Secure file uploads', 'user') }}
                </p>
            </div>
        </div>
    </div>

    {{-- Bootstrap JS (remove if your layout already provides it) --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
