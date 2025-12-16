(function($) {
    "use strict";

    let pdfCanvas = document.getElementById('pdfCanvas');
    if (pdfCanvas) {
        PDFJS.GlobalWorkerOptions.workerSrc = getConfig.baseURL + '/assets/vendor/libs/pdf/pdf.worker.min.js';
        var urlPreview = document.querySelector(".fileviewer-pdf").getAttribute("data-pdfDoc");
        var url = urlPreview;
        renderPDF(url, pdfCanvas);
    }

    function renderPDF(url, canvasContainer, options) {
        var pageNum = 1;
        options = options || { scale: 1.5 };

        function renderPage(page) {
            var viewport = page.getViewport(options.scale);
            var wrapper = document.createElement("div");
            wrapper.className = "canvas-wrapper";
            var canvas = document.createElement('canvas');
            var ctx = canvas.getContext('2d');
            var renderContext = {
                canvasContext: ctx,
                viewport: viewport
            };
            canvas.height = viewport.height;
            canvas.width = viewport.width;
            wrapper.appendChild(canvas)
            canvasContainer.appendChild(wrapper);

            page.render(renderContext);
        }

        function renderPages(pdfDoc) {
            for (var num = 1; num <= pdfDoc.numPages; num++)
                pdfDoc.getPage(num).then(renderPage);
        }
        PDFJS.disableWorker = true;
        PDFJS.getDocument(url).then(renderPages);
        PDFJS.getDocument(url).promise.then(function(pdfDoc_) {
            var pdfDoc = pdfDoc_;
            document.getElementById("page_count").textContent = pdfDoc.numPages;
        });

        function currentPage() {
            document.querySelectorAll(".canvas-wrapper").forEach((el, id) => {
                if (el.offsetTop - el.offsetHeight / 2 <= window.scrollY) {
                    document.getElementById("page_num").textContent = id + 1;
                }
            });
        }
        document.getElementById("page_num").textContent = 1;
        window.addEventListener("scroll", currentPage);
    }
})(jQuery);