<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ $fileRequest->title ?? 'Upload Files' }} - {{ config('app.name', 'FileForward') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Basic styles – adjust paths to your own CSS if needed --}}
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/fonts/fontawesome/css/all.min.css') }}">

    <style>
        :root {
            --ff-primary: #4f46e5;
            --ff-primary-soft: #eef2ff;
            --ff-bg: #f3f4f6;
            --ff-border: #e5e7eb;
            --ff-text-main: #111827;
            --ff-text-muted: #6b7280;
            --ff-success: #16a34a;
            --ff-danger: #dc2626;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: radial-gradient(circle at top left, #eef2ff 0, #f3f4ff 35%, #f9fafb 100%);
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: var(--ff-text-main);
        }

        .upload-page-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
        }

        .upload-card {
            max-width: 780px;
            width: 100%;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 22px;
            box-shadow:
                0 28px 80px rgba(15, 23, 42, 0.14),
                0 0 0 1px rgba(148, 163, 184, 0.12);
            overflow: hidden;
            position: relative;
        }

        .upload-card::before {
            content: "";
            position: absolute;
            inset: 0;
            pointer-events: none;
            border-radius: 22px;
            border: 1px solid rgba(129, 140, 248, 0.18);
        }

        .upload-card-header {
            padding: 22px 28px 16px;
            border-bottom: 1px solid var(--ff-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .upload-card-header-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .upload-card-header-icon {
            height: 42px;
            width: 42px;
            border-radius: 999px;
            background: linear-gradient(135deg, var(--ff-primary), #6366f1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            box-shadow: 0 10px 25px rgba(79, 70, 229, .4);
        }

        .upload-card-header h1 {
            font-size: 1.3rem;
            margin: 0 0 4px;
            font-weight: 600;
            letter-spacing: -0.02em;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .upload-card-header p {
            margin: 0;
            color: var(--ff-text-muted);
            font-size: 0.9rem;
        }

        .upload-owner-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.78rem;
            padding: 4px 9px;
            border-radius: 999px;
            background: rgba(79, 70, 229, 0.06);
            color: #4f46e5;
            border: 1px solid rgba(79, 70, 229, 0.15);
            margin-top: 4px;
        }

        .upload-owner-avatar {
            width: 18px;
            height: 18px;
            border-radius: 999px;
            background: #4f46e5;
            color: #fff;
            font-size: 0.65rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .upload-card-badge {
            font-size: 0.75rem;
            padding: 5px 10px;
            border-radius: 999px;
            background: #ecfdf3;
            color: #166534;
            border: 1px solid rgba(22, 163, 74, 0.18);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }

        .upload-meta {
            padding: 10px 28px 14px;
            background: #f9fafb;
            border-bottom: 1px solid var(--ff-border);
            font-size: 0.83rem;
            color: #4b5563;
        }

        .upload-meta-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px 20px;
            align-items: center;
        }

        .upload-meta-item-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #9ca3af;
            font-weight: 600;
        }

        .upload-meta-value {
            font-weight: 500;
            color: #374151;
        }

        .upload-card-body {
            padding: 24px 28px 22px;
        }

        .upload-card-body-top {
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
            margin-bottom: 18px;
            align-items: center;
            justify-content: space-between;
        }

        .upload-caption {
            font-size: 0.85rem;
            color: var(--ff-text-muted);
        }

        .upload-caption i {
            margin-right: 6px;
        }

        .upload-dropzone-wrapper {
            position: relative;
        }

        .upload-dropzone {
            border: 1.5px dashed rgba(129, 140, 248, 0.7);
            border-radius: 18px;
            background: radial-gradient(circle at top left, #eef2ff 0, #f9fafb 45%, #ffffff 100%);
            padding: 34px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.16s ease-in-out;
            position: relative;
            overflow: hidden;
        }

        .upload-dropzone::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image: radial-gradient(circle at 0 0, rgba(79, 70, 229, 0.08), transparent 55%);
            opacity: 0;
            transition: opacity 0.15s ease-in-out;
            pointer-events: none;
        }

        .upload-dropzone.dragover,
        .upload-dropzone:hover {
            border-color: var(--ff-primary);
            box-shadow: 0 18px 45px rgba(79, 70, 229, 0.25);
            transform: translateY(-1px);
        }

        .upload-dropzone.dragover::before,
        .upload-dropzone:hover::before {
            opacity: 1;
        }

        .upload-dropzone-icon {
            height: 54px;
            width: 54px;
            border-radius: 999px;
            background: #eef2ff;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            box-shadow: inset 0 0 0 1px rgba(129, 140, 248, 0.4);
        }

        .upload-dropzone-icon i {
            font-size: 1.7rem;
            color: var(--ff-primary);
        }

        .upload-dropzone h2 {
            font-size: 1.05rem;
            margin-bottom: 4px;
            font-weight: 600;
        }

        .upload-dropzone p {
            margin: 0;
            color: var(--ff-text-muted);
            font-size: 0.85rem;
        }

        .upload-dropzone small {
            display: block;
            margin-top: 6px;
            font-size: 0.78rem;
            color: #9ca3af;
        }

        .upload-dropzone-actions {
            margin-top: 12px;
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-main {
            background: var(--ff-primary);
            border-color: var(--ff-primary);
            color: #fff;
            border-radius: 999px;
            padding: 7px 16px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .btn-main:hover {
            background: #4338ca;
            border-color: #4338ca;
        }

        .btn-ghost {
            border-radius: 999px;
            padding: 7px 16px;
            font-size: 0.83rem;
            border: 1px solid rgba(148, 163, 184, 0.7);
            background: rgba(249, 250, 251, 0.85);
            color: #4b5563;
        }

        .btn-ghost i {
            margin-right: 6px;
            font-size: 0.78rem;
        }

        .btn-ghost:hover {
            background: #e5e7eb;
        }

        .progress-wrapper {
            margin-top: 18px;
            display: none;
            padding: 10px 12px 12px;
            border-radius: 14px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
        }

        .progress-label {
            font-size: 0.83rem;
            color: #374151;
            margin-bottom: 4px;
        }

        .progress-sub {
            font-size: 0.78rem;
            color: #9ca3af;
        }

        .progress {
            height: 6px;
            border-radius: 999px;
            background-color: #e5e7eb;
            overflow: hidden;
        }

        .progress-bar {
            border-radius: 999px;
        }

        .status-area {
            margin-top: 14px;
            font-size: 0.82rem;
            max-height: 160px;
            overflow-y: auto;
        }

        .status-area::-webkit-scrollbar {
            width: 5px;
        }

        .status-area::-webkit-scrollbar-track {
            background: transparent;
        }

        .status-area::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 999px;
        }

        .status-line {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 4px 0;
            gap: 10px;
            border-bottom: 1px dashed #e5e7eb;
        }

        .status-line:last-child {
            border-bottom: none;
        }

        .status-line span:first-child {
            flex: 1;
            min-width: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .status-line span.text-success {
            color: var(--ff-success) !important;
        }

        .status-line span.text-danger {
            color: var(--ff-danger) !important;
        }

        .footer-small {
            text-align: center;
            font-size: 0.78rem;
            color: #9ca3af;
            padding: 10px 20px 14px;
            border-top: 1px solid #f3f4f6;
            background: #f9fafb;
        }

        .footer-small span {
            opacity: 0.9;
        }

        @media (max-width: 640px) {
            .upload-card {
                border-radius: 18px;
            }
            .upload-card-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .upload-card-header-left {
                width: 100%;
            }
            .upload-card-body {
                padding: 18px 18px 18px;
            }
            .upload-meta {
                padding: 10px 18px 12px;
            }
        }
    </style>
</head>
<body>
<div class="upload-page-wrapper">
    <div class="upload-card">
        <div class="upload-card-header">
            <div class="upload-card-header-left">
                <div class="upload-card-header-icon">
                    <i class="fas fa-folder-open"></i>
                </div>
                <div>
                    <h1>
                        {{ $fileRequest->title ?? 'Upload files' }}
                    </h1>

                    @isset($owner)
                        <p>Send files directly to</p>
                        <div class="upload-owner-chip">
                            <div class="upload-owner-avatar">
                                {{ strtoupper(mb_substr($owner->name ?? $owner->email, 0, 1)) }}
                            </div>
                            <span>{{ $owner->name ?? $owner->email }}</span>
                        </div>
                    @else
                        <p>Secure, one-time link to upload files</p>
                    @endisset
                </div>
            </div>

            <div class="upload-card-header-right">
                <span class="upload-card-badge">
                    <i class="fas fa-shield-alt"></i>
                    Secure public upload
                </span>
            </div>
        </div>

        <div class="upload-meta">
            <div class="upload-meta-grid">
                <div>
                    <div class="upload-meta-item-label">Target folder</div>
                    <div class="upload-meta-value">
                        @if(!empty($folderPath))
                            {{ $folderPath }}
                        @else
                            Root of {{ $owner->name ?? $owner->email }}
                        @endif
                    </div>
                </div>
                <div>
                    <div class="upload-meta-item-label">Max file size</div>
                    <div class="upload-meta-value">
                        {{ $maxFileSizeMB }} MB per file
                    </div>
                </div>
            </div>
        </div>

        <div class="upload-card-body">
            <div class="upload-card-body-top">
                <div class="upload-caption">
                    <i class="fas fa-info-circle"></i>
                    Drag & drop multiple files or use the buttons below. You can safely close this page after uploads finish.
                </div>
            </div>

            {{-- Drop zone --}}
            <div class="upload-dropzone-wrapper">
                <div id="publicUploadDropzone" class="upload-dropzone">
                    <div class="upload-dropzone-icon">
                        <i class="fas fa-cloud-upload-alt"></i>
                    </div>
                    <h2>Drop your files here</h2>
                    <p>or click anywhere in this box to select files from your device</p>
                    <small>We’ll upload them securely and notify the recipient.</small>

                    <div class="upload-dropzone-actions">
                        <button type="button" class="btn btn-main">
                            <i class="fas fa-file-arrow-up me-1"></i>
                            Browse files
                        </button>
                        <button type="button" class="btn btn-ghost">
                            <i class="fas fa-layer-group"></i>
                            Select multiple
                        </button>
                    </div>
                </div>
            </div>

            {{-- Hidden file input --}}
            <input type="file" id="publicUploadInput" multiple style="display:none;" />

            {{-- Progress --}}
            <div id="publicUploadProgressWrapper" class="progress-wrapper">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <div>
                        <span class="progress-label">
                            <span id="publicUploadFileName">Uploading…</span>
                        </span>
                        <div class="progress-sub">
                            <span id="publicUploadStatus">0% uploaded</span>
                        </div>
                    </div>
                    <small id="publicUploadPercentText">0%</small>
                </div>
                <div class="progress">
                    <div id="publicUploadProgressBar" class="progress-bar progress-bar-striped progress-bar-animated"
                         role="progressbar" style="width:0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                    </div>
                </div>
            </div>

            {{-- Status list --}}
            <div id="publicUploadStatusArea" class="status-area"></div>

        </div>

        <div class="footer-small">
            <span>&copy; {{ date('Y') }} {{ config('app.name', 'FileForward') }} · All uploads are encrypted in transit.</span>
        </div>
    </div>
</div>

{{-- JS dependencies – adjust to your setup / mix / asset pipeline --}}
<script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>

<script>
    (function() {
        const uploadUrl   = @json(route('file-request.upload', $fileRequest->token));
        const csrfToken   = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const maxFileSize = {{ (int) $maxFileSizeMB }} * 1024 * 1024; // bytes

        const dropzone   = document.getElementById('publicUploadDropzone');
        const fileInput  = document.getElementById('publicUploadInput');

        const progressWrapper = document.getElementById('publicUploadProgressWrapper');
        const progressBar     = document.getElementById('publicUploadProgressBar');
        const percentText     = document.getElementById('publicUploadPercentText');
        const statusText      = document.getElementById('publicUploadStatus');
        const fileNameEl      = document.getElementById('publicUploadFileName');
        const statusArea      = document.getElementById('publicUploadStatusArea');

        let uploadInProgress = false;
        let pendingFiles     = [];
        let anySuccess       = false;

        // Helpers
        function showToast(type, message) {
            // Simple fallback – if you have toastr/Swal, plug here
            if (type === 'error') {
                console.error(message);
                alert('Error: ' + message);
            } else {
                console.log(message);
                alert(message);
            }
        }

        function showProgress(fileName, size) {
            fileNameEl.textContent = fileName;
            progressWrapper.style.display = 'block';
            updateProgress(0);
            statusText.textContent = 'Preparing upload… (' + formatSize(size) + ')';
        }

        function updateProgress(percent) {
            progressBar.style.width = percent + '%';
            progressBar.setAttribute('aria-valuenow', percent);
            percentText.textContent = percent + '%';
            statusText.textContent  = percent + '% uploaded';
        }

        function hideProgress() {
            setTimeout(function() {
                progressWrapper.style.display = 'none';
            }, 800);
        }

        function formatSize(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        function addStatusLine(name, text, isError) {
            const line = document.createElement('div');
            line.className = 'status-line';
            line.innerHTML = `
                <span title="${name}">${name}</span>
                <span class="${isError ? 'text-danger' : 'text-success'}">${text}</span>
            `;
            statusArea.appendChild(line);
        }

        // Handle click on dropzone
        dropzone.addEventListener('click', function() {
            if (uploadInProgress) return;
            fileInput.value = '';
            fileInput.click();
        });

        // Drag & drop support
        ;['dragenter', 'dragover'].forEach(evt => {
            dropzone.addEventListener(evt, function(e) {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.add('dragover');
            });
        });
        ;['dragleave', 'drop'].forEach(evt => {
            dropzone.addEventListener(evt, function(e) {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.remove('dragover');
            });
        });
        dropzone.addEventListener('drop', function(e) {
            if (uploadInProgress) return;
            const files = Array.from(e.dataTransfer.files || []);
            if (!files.length) return;
            startUploads(files);
        });

        // Normal input selection
        fileInput.addEventListener('change', function(e) {
            if (uploadInProgress) return;
            const files = Array.from(e.target.files || []);
            if (!files.length) return;
            startUploads(files);
        });

        function startUploads(files) {
            // Filter by size client-side
            const valid = [];
            files.forEach(file => {
                if (file.size > maxFileSize) {
                    addStatusLine(file.name, 'Too large (max ' + formatSize(maxFileSize) + ')', true);
                } else {
                    valid.push(file);
                }
            });

            if (!valid.length) return;

            uploadInProgress = true;
            pendingFiles     = valid.slice();
            anySuccess       = false;

            uploadNextFile();
        }

        function uploadNextFile() {
            if (!pendingFiles.length) {
                uploadInProgress = false;
                if (anySuccess) {
                    showToast('success', 'Upload completed.');
                }
                hideProgress();
                return;
            }

            const file = pendingFiles.shift();
            uploadSingleFile(file, function() {
                uploadNextFile();
            });
        }

        function uploadSingleFile(file, done) {
            const CHUNK_SIZE = 5 * 1024 * 1024;
            const total      = file.size;
            let offset       = 0;

            showProgress(file.name, file.size);

            function finishFile(success, message) {
                hideProgress();
                if (success) {
                    anySuccess = true;
                    addStatusLine(file.name, message || 'Uploaded', false);
                } else {
                    addStatusLine(file.name, message || 'Failed', true);
                }
                if (typeof done === 'function') done();
            }

            function sendNextChunk() {
                if (offset >= total) {
                    finishFile(true, 'Uploaded');
                    return;
                }

                const end  = Math.min(offset + CHUNK_SIZE, total);
                const blob = file.slice(offset, end);

                const formData = new FormData();
                formData.append('file', blob, file.name);
                formData.append('size', total);

                const xhr = new XMLHttpRequest();
                xhr.timeout = 0;

                xhr.upload.addEventListener('progress', function(e) {
                    if (!e.lengthComputable) return;
                    const overall = Math.floor(((offset + e.loaded) / total) * 100);
                    updateProgress(overall);
                });

                xhr.addEventListener('load', function() {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        // Server returns JSON; parse if final
                        try {
                            const res = JSON.parse(xhr.responseText || '{}');
                            if (res.type === 'error') {
                                finishFile(false, res.msg || 'Upload failed');
                                return;
                            }
                        } catch (e) {
                            // if not JSON, continue anyway for chunk
                        }
                        offset = end;
                        sendNextChunk();
                    } else {
                        finishFile(false, 'HTTP ' + xhr.status);
                    }
                });

                xhr.addEventListener('error', function() {
                    finishFile(false, 'Network error');
                });

                xhr.addEventListener('timeout', function() {
                    finishFile(false, 'Upload timed out');
                });

                xhr.open('POST', uploadUrl, true);
                xhr.setRequestHeader('Content-Range', 'bytes ' + offset + '-' + (end - 1) + '/' + total);
                if (csrfToken) {
                    xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
                }
                xhr.send(formData);
            }

            sendNextChunk();
        }
    })();
</script>
</body>
</html>
