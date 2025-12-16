<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ $fileRequest->title ?? 'Upload Files' }} - {{ config('app.name', 'FileForward') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Basic styles --}}
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/fonts/fontawesome/css/all.min.css') }}">

    <style>
        :root {
            --ff-primary: #4f46e5;
            --ff-primary-hover: #4338ca;
            --ff-bg: #f8fafc;
            --ff-card-bg: #ffffff;
            --ff-text-main: #0f172a;
            --ff-text-muted: #64748b;
            --ff-border: #e2e8f0;
            --ff-success: #10b981;
            --ff-danger: #ef4444;
        }

        body {
            background-color: var(--ff-bg);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            color: var(--ff-text-main);
            margin: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .main-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .upload-card {
            background: var(--ff-card-bg);
            width: 100%;
            max-width: 680px;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            overflow: hidden;
            border: 1px solid var(--ff-border);
        }

        /* Header Section */
        .card-header-custom {
            padding: 2rem 2rem 1.5rem;
            border-bottom: 1px solid var(--ff-border);
            background: linear-gradient(to right, #ffffff, #f8fafc);
        }

        .header-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.35rem 0.75rem;
            background-color: #eff6ff;
            color: var(--ff-primary);
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .request-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0 0 0.5rem;
            line-height: 1.3;
            color: var(--ff-text-main);
        }

        .request-desc {
            color: var(--ff-text-muted);
            font-size: 0.95rem;
            line-height: 1.5;
            margin-bottom: 1.5rem;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 1rem;
            background: #f8fafc;
            padding: 1rem;
            border-radius: 12px;
            border: 1px solid var(--ff-border);
        }

        .meta-item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .meta-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #94a3b8;
            font-weight: 600;
        }

        .meta-value {
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--ff-text-main);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .owner-avatar {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: var(--ff-primary);
            color: white;
            font-size: 0.65rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Upload Body */
        .card-body-custom {
            padding: 2rem;
        }

        .dropzone-container {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .dropzone {
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            padding: 3rem 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            background: #f8fafc;
        }

        .dropzone:hover, .dropzone.dragover {
            border-color: var(--ff-primary);
            background: #eff6ff;
            transform: translateY(-2px);
        }

        .dropzone-icon {
            font-size: 2.5rem;
            color: #94a3b8;
            margin-bottom: 1rem;
            transition: color 0.2s;
        }

        .dropzone:hover .dropzone-icon {
            color: var(--ff-primary);
        }

        .dropzone-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--ff-text-main);
        }

        .dropzone-subtitle {
            font-size: 0.9rem;
            color: var(--ff-text-muted);
            margin-bottom: 1.5rem;
        }

        .btn-upload {
            background: var(--ff-primary);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: background 0.2s;
        }

        .btn-upload:hover {
            background: var(--ff-primary-hover);
        }

        /* Progress & Status */
        .progress-container {
            display: none;
            margin-top: 1.5rem;
            background: #f1f5f9;
            padding: 1rem;
            border-radius: 10px;
        }

        .progress-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .progress-bar-bg {
            height: 8px;
            background: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-bar-fill {
            height: 100%;
            background: var(--ff-primary);
            width: 0%;
            transition: width 0.2s linear;
        }

        .status-list {
            margin-top: 1.5rem;
            max-height: 200px;
            overflow-y: auto;
            border-top: 1px solid var(--ff-border);
            padding-top: 1rem;
        }

        .status-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            font-size: 0.9rem;
            border-bottom: 1px dashed #e2e8f0;
        }

        .status-item:last-child {
            border-bottom: none;
        }

        .status-name {
            font-weight: 500;
            color: var(--ff-text-main);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 70%;
        }

        .status-msg {
            font-size: 0.85rem;
        }

        .text-success { color: var(--ff-success); }
        .text-danger { color: var(--ff-danger); }

        /* Footer */
        .footer-custom {
            text-align: center;
            padding: 1.5rem;
            color: #94a3b8;
            font-size: 0.85rem;
            border-top: 1px solid var(--ff-border);
            background: #f8fafc;
        }


        .section-title {
            font-weight: 700;
            color: #0f172a;
            margin: 1rem;
        }

        .uploaded-file-list {
            background: #fff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }

        .uploaded-file-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 16px;
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.2s;
        }

        .uploaded-file-item:last-child {
            border-bottom: none;
        }

        .uploaded-file-item:hover {
            background: #f8fafc;
        }

        .file-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .file-icon {
            width: 38px;
            height: 38px;
            background: #eff6ff;
            color: #2563eb;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .file-icon i {
            font-size: 18px;
        }

        .file-info {
            display: flex;
            flex-direction: column;
            max-width: 400px;
        }

        .file-name {
            font-weight: 600;
            font-size: 0.95rem;
            color: #0f172a;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .file-meta {
            font-size: 0.8rem;
            color: #6b7280;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .file-meta .dot {
            font-size: 6px;
        }

        /* Cancel Upload Button Design */
        .cancel-upload-btn {
            margin-top: 12px;
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
            font-size: 0.85rem;
            font-weight: 600;
            padding: 6px 14px;
            border-radius: 8px;
            cursor: pointer;
            transition: all .2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .cancel-upload-btn:hover {
            background: #fecaca;
            border-color: #fca5a5;
        }

        .cancel-upload-btn i {
            font-size: 14px;
        }


        @media (max-width: 640px) {
            .card-header-custom { padding: 1.5rem; }
            .card-body-custom { padding: 1.5rem; }
            .meta-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="main-wrapper">
    <div class="upload-card">
        {{-- Header --}}
        <div class="card-header-custom">
            <div class="header-badge">
                <i class="fas fa-shield-alt"></i> Secure File Request
            </div>
            
            <h1 class="request-title">{{ $fileRequest->title ?? 'File Request' }}</h1>
            
            @if($fileRequest->description)
                <p class="request-desc">{{ $fileRequest->description }}</p>
            @endif

            <div class="meta-grid">
                {{-- Owner --}}
                <div class="meta-item">
                    <span class="meta-label">Requested By</span>
                    <div class="meta-value">
                        @if($owner)
                            <div class="owner-avatar">
                                {{ strtoupper(mb_substr($owner->name ?? $owner->email, 0, 1)) }}
                            </div>
                            {{ $owner->name ?? $owner->email }}
                        @else
                            System
                        @endif
                    </div>
                </div>

                {{-- Expiration --}}
                @if($fileRequest->expires_at)
                <div class="meta-item">
                    <span class="meta-label">Expires</span>
                    <div class="meta-value">
                        <i class="far fa-clock text-muted me-1"></i>
                        {{ $fileRequest->expires_at->format('M d, Y H:i') }}
                    </div>
                </div>
                @endif

                {{-- Max Size --}}
                <div class="meta-item">
                    <span class="meta-label">Max File Size</span>
                    <div class="meta-value">
                        @if($maxFileSizeMB >= 2048)
                            No limit
                        @else
                            {{ $maxFileSizeMB }} MB / file
                        @endif
                    </div>
                </div>

                {{-- Storage Limit --}}
                @if($storageLimit)
                <div class="meta-item">
                    <span class="meta-label">Storage Remaining</span>
                    <div class="meta-value">
                        {{ formatBytes(max(0, $storageLimit - $usedStorage)) }}
                        <span style="color:#94a3b8; font-weight:400; font-size:0.8em; margin-left:4px;">
                            (of {{ formatBytes($storageLimit) }})
                        </span>
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- Body --}}
        <div class="card-body-custom">
            <div class="dropzone-container">
                <div id="dropzone" class="dropzone">
                    <i class="fas fa-cloud-upload-alt dropzone-icon"></i>
                    <div class="dropzone-title">Drag & Drop files here</div>
                    <div class="dropzone-subtitle">or click to browse from your device</div>
                    
                    <button type="button" class="btn-upload" id="browseBtn">
                        <i class="fas fa-folder-open"></i> Select Files
                    </button>
                    
                    <input type="file" id="fileInput" multiple style="display:none;">
                </div>
            </div>

            {{-- Progress Area --}}
            <div id="progressContainer" class="progress-container">
                <div class="progress-info">
                    <span id="uploadingFileName">Uploading...</span>
                    <span id="uploadPercent">0%</span>
                </div>
                <div class="progress-bar-bg">
                    <div id="progressBar" class="progress-bar-fill"></div>
                </div>
            </div>

            {{-- Status List --}}
            <div id="statusList" class="status-list">
                {{-- Items will be added here --}}
            </div>
        </div>

{{-- Uploaded Files List --}}
@if($uploadedFiles->count() > 0)
<div class="uploaded-files mt-5">

    <h5 class="section-title">
        <i class="fas fa-folder-open text-primary me-2"></i>
        Uploaded Files
    </h5>

    <div class="uploaded-file-list">
        @foreach($uploadedFiles as $file)
        <div class="uploaded-file-item">

            <div class="file-left">
                <div class="file-icon">
                    <i class="fas fa-file-alt"></i>
                </div>

                <div class="file-info">
                    <div class="file-name" title="{{ $file->name }}">{{ $file->name }}</div>
                    <div class="file-meta">
                        <span>{{ formatBytes($file->size) }}</span>
                        <span class="dot">•</span>
                        <span>{{ $file->created_at->format('M d, Y H:i') }}</span>
                    </div>
                </div>
            </div>

        </div>
        @endforeach
    </div>

</div>
@endif



        {{-- Footer --}}
        <div class="footer-custom">
            &copy; {{ date('Y') }} {{ config('app.name', 'FileForward') }} · Secure & Encrypted Transfer
        </div>
    </div>
</div>

<script>
(function () {

    const uploadUrl = @json(route('file-request.upload', $fileRequest->token));
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    const maxFileSize = {{ (int) $maxFileSizeMB }} * 1024 * 1024; 
    const remainingStorage = {{ max(0, $storageLimit - $usedStorage) ?? 0 }};

    const dropzone = document.getElementById("dropzone");
    const fileInput = document.getElementById("fileInput");
    const browseBtn = document.getElementById("browseBtn");

    const progressContainer = document.getElementById("progressContainer");
    const progressBar = document.getElementById("progressBar");
    const uploadPercent = document.getElementById("uploadPercent");
    const uploadingFileName = document.getElementById("uploadingFileName");
    const statusList = document.getElementById("statusList");

    let currentXhr = null;
    let isUploading = false;
    let queue = [];

    /* ===========================
       EVENT LISTENERS
    ============================== */
    browseBtn.addEventListener("click", () => fileInput.click());
    dropzone.addEventListener("click", (e) => {
        if (e.target !== browseBtn) fileInput.click();
    });
    fileInput.addEventListener("change", (e) => handleFiles(e.target.files));

    ["dragenter", "dragover"].forEach(event => {
        dropzone.addEventListener(event, (e) => {
            e.preventDefault();
            dropzone.classList.add("dragover");
        });
    });
    ["dragleave", "drop"].forEach(event => {
        dropzone.addEventListener(event, (e) => {
            e.preventDefault();
            dropzone.classList.remove("dragover");
        });
    });
    dropzone.addEventListener("drop", (e) => {
        handleFiles(e.dataTransfer.files);
    });

    /* ===========================
       HANDLE FILE SELECTION
    ============================== */
    function handleFiles(files) {
        if (isUploading) return;

        const validFiles = [];

        Array.from(files).forEach(file => {

            // Hard check: Remaining storage (instant error)
            if (file.size > remainingStorage) {
                addStatus(file.name, `Not enough storage. Remaining: ${formatSize(remainingStorage)}`, true);
                return;
            }

            // Per-file limit (instant error)
            if (file.size > maxFileSize) {
                addStatus(file.name, `File too large. Max allowed per file: ${formatSize(maxFileSize)}`, true);
                return;
            }

            validFiles.push(file);
        });

        if (validFiles.length > 0) {
            queue = validFiles;
            isUploading = true;
            processQueue();
        }

        fileInput.value = "";
    }

    /* ===========================
       PROCESS UPLOAD QUEUE
    ============================== */
    function processQueue() {
        if (queue.length === 0) {
            isUploading = false;
            hideProgress();
            return;
        }
        uploadFile(queue.shift());
    }

    /* ===========================
       UPLOAD FILE (10MB CHUNKS)
    ============================== */
    function uploadFile(file) {

        showProgress(file.name);

        const CHUNK_SIZE = 10 * 1024 * 1024;  // 10 MB chunks
        const total = file.size;
        let offset = 0;

        function sendChunk() {

            const end = Math.min(offset + CHUNK_SIZE, total);
            const chunk = file.slice(offset, end);

            const formData = new FormData();
            formData.append("file", chunk, file.name);
            formData.append("size", total);

            currentXhr = new XMLHttpRequest();
            const xhr = currentXhr;

            xhr.open("POST", uploadUrl, true);
            xhr.setRequestHeader("X-CSRF-TOKEN", csrfToken);
            xhr.setRequestHeader("Content-Range", `bytes ${offset}-${end - 1}/${total}`);

            xhr.upload.onprogress = (e) => {
                if (e.lengthComputable) {
                    const totalLoaded = offset + e.loaded;
                    const percent = Math.round((totalLoaded / total) * 100);
                    updateProgress(percent);
                }
            };

            xhr.onload = () => {

                let response = {};
                try {
                    response = JSON.parse(xhr.responseText);
                } catch (e) {
                    response = { msg: "Server error" };
                }

                if (xhr.status >= 200 && xhr.status < 300) {

                    if (end < total) {
                        offset = end;
                        sendChunk();
                    } else {
                        addStatus(file.name, "Uploaded successfully", false);
                        processQueue();
                    }

                } else {
                    addStatus(file.name, response.msg || "Upload error", true);
                    processQueue();
                }
            };

            xhr.onerror = () => {
                addStatus(file.name, "Network Error", true);
                processQueue();
            };

            xhr.send(formData);
        }

        sendChunk();
    }

    /* ===========================
       UI HELPERS
    ============================== */

    function showProgress(name) {
        progressContainer.style.display = "block";
        uploadingFileName.textContent = name;
        document.getElementById("cancelUpload")?.remove();

        // Add cancel button
        const cancelBtn = document.createElement("button");
        cancelBtn.id = "cancelUpload";
        cancelBtn.className = "cancel-upload-btn";
        cancelBtn.innerHTML = `<i class="fas fa-times-circle me-1"></i> Cancel Upload`;
        cancelBtn.onclick = () => cancelUpload(name);

        progressContainer.appendChild(cancelBtn);

        updateProgress(0);
    }

    function cancelUpload(name) {
        if (currentXhr) {
            currentXhr.abort();
            addStatus(name, "Upload cancelled", true);
            queue = [];
            isUploading = false;
            hideProgress();
        }
    }

    function updateProgress(percent) {
        progressBar.style.width = percent + "%";
        uploadPercent.textContent = percent + "%";
    }

    function hideProgress() {
        progressContainer.style.display = "none";
    }

    function addStatus(name, msg, isError) {
        const div = document.createElement("div");
        div.className = "status-item";
        div.innerHTML = `
            <span class="status-name">${name}</span>
            <span class="status-msg ${isError ? 'text-danger' : 'text-success'}">
                ${isError ? '<i class="fas fa-exclamation-circle"></i>' : '<i class="fas fa-check-circle"></i>'}
                ${msg}
            </span>
        `;
        statusList.prepend(div);
    }

    function formatSize(bytes) {
        if (bytes === 0) return "0 B";
        const units = ["B", "KB", "MB", "GB", "TB"];
        const i = Math.floor(Math.log(bytes) / Math.log(1024));
        return (bytes / Math.pow(1024, i)).toFixed(2) + " " + units[i];
    }

})();
</script>


</body>
</html>
