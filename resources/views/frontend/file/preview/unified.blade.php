@extends('frontend.layouts.previews')
@section('section', lang('Preview', 'preview'))
@section('title', $fileEntry->name)
@section('og_image', $previewUrl)
@section('description', $fileEntry->description)
@section('body', 'overflow-hidden body-bg')

@push('styles')
<style>
    :root {
        --bg-primary: #1a1a1a;
        --bg-secondary: #2d2d2d;
        --border-color: #404040;
        --text-primary: #ffffff;
        --text-secondary: #9ca3af;
        --accent-color: #60a5fa;
        --error-color: #ef4444;
        --success-color: #10b981;
    }

    .fileviewer {
        height: 100vh;
        display: flex;
        flex-direction: column;
        background: var(--bg-primary);
        color: var(--text-primary);
    }

    .file-preview-wrapper {
        flex: 1;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .preview-content {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        overflow: hidden;
        background: #121212;
    }

    /* Image Preview Styles */
    .preview-image {
        max-width: 90%;
        max-height: 90%;
        object-fit: contain;
        transition: transform 0.3s ease;
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    }

    /* PDF Preview Styles */
    .pdf-container {
        width: 100%;
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        padding: 20px 0;
        margin-top: 130px;
    }

    .pdf-controls {
        position: absolute;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(0, 0, 0, 0.9);
        padding: 12px 20px;
        border-radius: 25px;
        display: flex;
        align-items: center;
        gap: 15px;
        font-size: 14px;
        z-index: 10;
        backdrop-filter: blur(10px);
        border: 1px solid var(--border-color);
    }

    .pdf-canvas-container {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        overflow: auto;
        padding: 60px 20px 20px;
        margin-top: 100px;
    }

    #pdfCanvas {
        border: 2px solid var(--border-color);
        border-radius: 8px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
        background: white;
        max-width: 100%;
        height: auto;
    }

    /* Media Preview Styles */
    .preview-video, .preview-audio {
        max-width: 90%;
        max-height: 90%;
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    }

    .audio-container {
        text-align: center;
        max-width: 600px;
        margin-top: 130px;
    }

    .audio-icon {
        font-size: 80px;
        color: var(--accent-color);
        margin-bottom: 20px;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }

    /* Text Preview Styles */
    .text-container {
        width: 100%;
        height: 100%;
        display: flex;
        flex-direction: column;
        padding: 90px 20px 25px 20px;
    }

    .preview-text {
        flex: 1;
        background: #1e1e1e;
        color: #d4d4d4;
        padding: 30px;
        overflow: auto;
        font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
        font-size: 14px;
        line-height: 1.6;
        white-space: pre-wrap;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        word-wrap: break-word;
    }

    /* Control Overlays */
    .controls-overlay {
        position: absolute;
        bottom: 30px;
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        gap: 12px;
        background: rgba(0, 0, 0, 0.9);
        padding: 15px 20px;
        border-radius: 25px;
        opacity: 0;
        transition: opacity 0.3s ease;
        backdrop-filter: blur(10px);
        border: 1px solid var(--border-color);
    }

    .file-preview-wrapper:hover .controls-overlay {
        opacity: 1;
    }

    .control-btn {
        background: transparent;
        border: 1px solid var(--border-color);
        color: var(--text-primary);
        font-size: 16px;
        cursor: pointer;
        padding: 10px;
        border-radius: 8px;
        transition: all 0.2s ease;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .control-btn:hover:not(:disabled) {
        background: var(--accent-color);
        transform: translateY(-2px);
    }

    .control-btn:disabled {
        opacity: 0.4;
        cursor: not-allowed;
    }

    /* Loading and Error States */
    .loading-container, .error-container, .unsupported-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 40px;
        gap: 20px;
    }

    .loading-spinner {
        width: 50px;
        height: 50px;
        border: 4px solid rgba(96, 165, 250, 0.3);
        border-radius: 50%;
        border-top-color: var(--accent-color);
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    .error-icon, .unsupported-icon {
        font-size: 64px;
        margin-bottom: 16px;
    }

    .error-icon {
        color: var(--error-color);
    }

    .unsupported-icon {
        color: var(--text-secondary);
    }

    .status-message {
        font-size: 18px;
        font-weight: 500;
        margin-bottom: 8px;
    }

    .status-description {
        color: var(--text-secondary);
        margin-bottom: 20px;
        max-width: 400px;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .pdf-controls {
            padding: 8px 12px;
            gap: 10px;
            font-size: 12px;
        }
        
        .controls-overlay {
            opacity: 1;
            bottom: 20px;
            padding: 10px 15px;
            gap: 8px;
        }
        
        .control-btn {
            width: 36px;
            height: 36px;
            font-size: 14px;
        }
    }
</style>
@endpush

@section('content')
<div class="file-preview-wrapper">
    <div class="preview-content" id="previewContent">
        @if(!$isPreviewSupported)
            <div class="unsupported-container">
                <div class="unsupported-icon">
                    <i class="fas fa-file-times"></i>
                </div>
                <h3 class="status-message">Preview Not Available</h3>
                <p class="status-description">
                    This file type ({{ strtoupper($fileEntry->extension) }}) cannot be previewed in the browser. 
                    Please download the file to view its contents.
                </p>
                <a href="{{ $downloadUrl }}" class="fileviewer-action" style="background: var(--accent-color); color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none;">
                    <i class="fas fa-download"></i>
                    <span>Download File</span>
                </a>
            </div>
        @elseif($fileCategory === 'image')
            <div class="loading-container" id="loadingContainer">
                <div class="loading-spinner"></div>
                <p class="status-message">Loading image...</p>
            </div>
            <img src="{{ $previewUrl }}" alt="{{ $fileEntry->name }}" class="preview-image" id="previewImage" style="display: none;"
                 onload="imageLoaded()" onerror="imageError()">
            <div class="controls-overlay" id="imageControls" style="display: none;">
                <button class="control-btn" onclick="rotateImage(-90)" title="Rotate Left">
                    <i class="fas fa-undo"></i>
                </button>
                <button class="control-btn" onclick="rotateImage(90)" title="Rotate Right">
                    <i class="fas fa-redo"></i>
                </button>
                <button class="control-btn" onclick="zoomImage(-0.2)" title="Zoom Out">
                    <i class="fas fa-search-minus"></i>
                </button>
                <button class="control-btn" onclick="zoomImage(0.2)" title="Zoom In">
                    <i class="fas fa-search-plus"></i>
                </button>
                <button class="control-btn" onclick="resetImage()" title="Reset">
                    <i class="fas fa-expand-arrows-alt"></i>
                </button>
            </div>
        @elseif($fileCategory === 'pdf')
            <div class="loading-container" id="pdfLoading">
                <div class="loading-spinner"></div>
                <p class="status-message">Loading PDF...</p>
            </div>
            <div class="pdf-container" id="pdfContainer" style="display: none;">
                <div class="pdf-controls">
                    <button class="control-btn" onclick="prevPage()" title="Previous Page" id="prevBtn">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <span id="pageInfo">1 / 1</span>
                    <button class="control-btn" onclick="nextPage()" title="Next Page" id="nextBtn">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                    <span style="margin: 0 10px; color: var(--text-secondary);">|</span>
                    <button class="control-btn" onclick="zoomPdf(-0.2)" title="Zoom Out">
                        <i class="fas fa-search-minus"></i>
                    </button>
                    <button class="control-btn" onclick="zoomPdf(0.2)" title="Zoom In">
                        <i class="fas fa-search-plus"></i>
                    </button>
                    <span style="margin: 0 10px; color: var(--text-secondary);">|</span>
                    <button class="control-btn" onclick="resetPdf()" title="Reset Zoom">
                        <i class="fas fa-expand-arrows-alt"></i>
                    </button>
                </div>
                <div class="pdf-canvas-container">
                    <canvas id="pdfCanvas"></canvas>
                </div>
            </div>
        @elseif($fileCategory === 'video')
            <div class="loading-container" id="videoLoading">
                <div class="loading-spinner"></div>
                <p class="status-message">Loading video...</p>
            </div>
            <video controls class="preview-video" preload="metadata" style="display: none;" id="previewVideo"
                   onloadstart="hideLoading('video')" onloadeddata="mediaLoaded('video')" onerror="mediaError('video')">
                <source src="{{ $previewUrl }}" type="{{ $fileEntry->mime }}">
                Your browser does not support the video tag.
            </video>
        @elseif($fileCategory === 'audio')
            <div class="loading-container" id="audioLoading">
                <div class="loading-spinner"></div>
                <p class="status-message">Loading audio...</p>
            </div>
            <div class="audio-container" id="audioContainer" style="display: none;">
                <div class="audio-icon">
                    <i class="fas fa-music"></i>
                </div>
                <h3>{{ $fileEntry->name }}</h3>
                <p style="color: var(--text-secondary); margin-bottom: 20px;">
                    {{ formatBytes($fileEntry->size) }} • {{ strtoupper($fileEntry->extension) }}
                </p>
                <audio controls class="preview-audio" preload="metadata"
                       onloadstart="hideLoading('audio')" onloadeddata="mediaLoaded('audio')" onerror="mediaError('audio')">
                    <source src="{{ $previewUrl }}" type="{{ $fileEntry->mime }}">
                    Your browser does not support the audio element.
                </audio>
            </div>
        @elseif($fileCategory === 'text')
            <div class="loading-container" id="textLoading">
                <div class="loading-spinner"></div>
                <p class="status-message">Loading content...</p>
            </div>
            <div class="text-container" id="textContainer" style="display: none;">
                <div class="preview-text" id="textContent"></div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts_libs')
@if($fileCategory === 'pdf')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
@endif
@endpush

@push('scripts')
<script>
// Global variables
let currentRotation = 0;
let currentZoom = 1;
let pdfDoc = null;
let currentPage = 1;
let pdfScale = 1.2;

// Utility functions
function showError(message, type = 'general') {
    const content = document.getElementById('previewContent');
    content.innerHTML = `
        <div class="error-container">
            <div class="error-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 class="status-message">Error Loading ${type.charAt(0).toUpperCase() + type.slice(1)}</h3>
            <p class="status-description">${message}</p>
            <a href="{{ $downloadUrl }}" class="fileviewer-action" style="background: var(--accent-color); color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; margin-top: 10px;">
                <i class="fas fa-download"></i>
                <span>Download File</span>
            </a>
        </div>
    `;
}

function hideLoading(type) {
    const loadingEl = document.getElementById(type + 'Loading');
    if (loadingEl) loadingEl.style.display = 'none';
}

// Image handling
function imageLoaded() {
    document.getElementById('loadingContainer').style.display = 'none';
    document.getElementById('previewImage').style.display = 'block';
    document.getElementById('imageControls').style.display = 'flex';
}

function imageError() {
    showError('The image could not be loaded. It may be corrupted or in an unsupported format.', 'image');
}

function rotateImage(degrees) {
    currentRotation += degrees;
    const img = document.getElementById('previewImage');
    if (img) {
        img.style.transform = `rotate(${currentRotation}deg) scale(${currentZoom})`;
    }
}

function zoomImage(delta) {
    currentZoom = Math.max(0.1, Math.min(5, currentZoom + delta));
    const img = document.getElementById('previewImage');
    if (img) {
        img.style.transform = `rotate(${currentRotation}deg) scale(${currentZoom})`;
    }
}

function resetImage() {
    currentRotation = 0;
    currentZoom = 1;
    const img = document.getElementById('previewImage');
    if (img) {
        img.style.transform = 'none';
    }
}

// Media handling
function mediaLoaded(type) {
    hideLoading(type);
    const element = document.getElementById(type === 'video' ? 'previewVideo' : 'audioContainer');
    if (element) {
        element.style.display = type === 'video' ? 'block' : 'flex';
    }
}

function mediaError(type) {
    showError(`The ${type} could not be loaded. It may be in an unsupported format or corrupted.`, type);
}

// PDF handling
@if($fileCategory === 'pdf')
document.addEventListener('DOMContentLoaded', function() {
    if (typeof pdfjsLib !== 'undefined') {
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
        
        const loadingTask = pdfjsLib.getDocument('{{ $previewUrl }}');
        loadingTask.promise.then(function(pdf) {
            pdfDoc = pdf;
            hideLoading('pdf');
            document.getElementById('pdfContainer').style.display = 'flex';
            renderPage(currentPage);
            updatePageInfo();
            updatePdfButtons();
        }).catch(function(error) {
            console.error('PDF loading error:', error);
            showError('The PDF file could not be loaded. It may be corrupted, password protected, or too large.', 'PDF');
        });
    } else {
        showError('PDF viewer library is not available. Please refresh the page and try again.', 'PDF');
    }
});

function renderPage(pageNum) {
    if (!pdfDoc || pageNum < 1 || pageNum > pdfDoc.numPages) return;
    
    pdfDoc.getPage(pageNum).then(function(page) {
        const canvas = document.getElementById('pdfCanvas');
        const ctx = canvas.getContext('2d');
        
        // Calculate scale to fit container
        const container = document.querySelector('.pdf-canvas-container');
        const containerWidth = container.clientWidth - 40; // Account for padding
        const containerHeight = container.clientHeight - 40;
        
        let viewport = page.getViewport({scale: 1});
        let scale = Math.min(
            (containerWidth / viewport.width) * pdfScale,
            (containerHeight / viewport.height) * pdfScale
        );
        
        viewport = page.getViewport({scale: scale});
        
        canvas.height = viewport.height;
        canvas.width = viewport.width;
        
        const renderContext = {
            canvasContext: ctx,
            viewport: viewport
        };
        
        page.render(renderContext);
    }).catch(function(error) {
        console.error('PDF rendering error:', error);
        showError('Error rendering PDF page. The file may be corrupted.', 'PDF');
    });
}

function nextPage() {
    if (pdfDoc && currentPage < pdfDoc.numPages) {
        currentPage++;
        renderPage(currentPage);
        updatePageInfo();
        updatePdfButtons();
    }
}

function prevPage() {
    if (pdfDoc && currentPage > 1) {
        currentPage--;
        renderPage(currentPage);
        updatePageInfo();
        updatePdfButtons();
    }
}

function zoomPdf(delta) {
    pdfScale = Math.max(0.3, Math.min(3, pdfScale + delta));
    renderPage(currentPage);
}

function resetPdf() {
    pdfScale = 1.2;
    renderPage(currentPage);
}

function updatePageInfo() {
    if (pdfDoc) {
        document.getElementById('pageInfo').textContent = `${currentPage} / ${pdfDoc.numPages}`;
    }
}

function updatePdfButtons() {
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    
    if (prevBtn) prevBtn.disabled = currentPage <= 1;
    if (nextBtn) nextBtn.disabled = !pdfDoc || currentPage >= pdfDoc.numPages;
}
@endif

// Text content loading with better error handling
@if($fileCategory === 'text')
document.addEventListener('DOMContentLoaded', function() {
    // Use a proxy approach to avoid CORS issues
    const xhr = new XMLHttpRequest();
    xhr.open('GET', '{{ $previewUrl }}', true);
    xhr.responseType = 'blob';
    
    xhr.onload = function() {
        if (xhr.status === 200) {
            const blob = xhr.response;
            const reader = new FileReader();
            
            reader.onload = function(e) {
                hideLoading('text');
                document.getElementById('textContainer').style.display = 'flex';
                const content = e.target.result;
                
                // Handle different text formats
                const extension = '{{ strtolower($fileEntry->extension) }}';
                if (extension === 'html') {
                    // For HTML, show both source and rendered view
                    document.getElementById('textContent').innerHTML = 
                        '<div style="margin-bottom: 20px; padding: 10px; background: #2d2d2d; border-radius: 5px;">' +
                        '<strong>HTML Source:</strong></div>' +
                        '<pre style="white-space: pre-wrap; word-wrap: break-word;">' + 
                        escapeHtml(content) + '</pre>';
                } else {
                    document.getElementById('textContent').textContent = content;
                }
            };
            
            reader.onerror = function() {
                showError('Failed to read the file content. The file may be corrupted or too large.', 'text');
            };
            
            reader.readAsText(blob, 'utf-8');
        } else {
            showError('Failed to load the file. Server returned error code: ' + xhr.status, 'text');
        }
    };
    
    xhr.onerror = function() {
        showError('Network error occurred while loading the file.', 'text');
    };
    
    xhr.ontimeout = function() {
        showError('Request timed out. The file may be too large.', 'text');
    };
    
    xhr.timeout = 30000; // 30 second timeout
    xhr.send();
});

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}
@endif

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    @if($fileCategory === 'image')
    if (e.key === 'ArrowLeft') rotateImage(-90);
    if (e.key === 'ArrowRight') rotateImage(90);
    if (e.key === '+' || e.key === '=') zoomImage(0.2);
    if (e.key === '-') zoomImage(-0.2);
    if (e.key === '0') resetImage();
    @elseif($fileCategory === 'pdf')
    if (e.key === 'ArrowLeft' || e.key === 'PageUp') { e.preventDefault(); prevPage(); }
    if (e.key === 'ArrowRight' || e.key === 'PageDown') { e.preventDefault(); nextPage(); }
    if (e.key === '+' || e.key === '=') { e.preventDefault(); zoomPdf(0.2); }
    if (e.key === '-') { e.preventDefault(); zoomPdf(-0.2); }
    if (e.key === '0') { e.preventDefault(); resetPdf(); }
    @endif
    
    if (e.key === 'Escape') window.close();
});

// Window resize handler for PDF
@if($fileCategory === 'pdf')
window.addEventListener('resize', function() {
    if (pdfDoc && currentPage) {
        setTimeout(() => renderPage(currentPage), 100);
    }
});
@endif
</script>
@endpush
