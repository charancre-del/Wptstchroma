/**
 * Chroma Pro PDF Viewer - Paginated Edition
 * 
 * Handles lazy-loading of PDF.js and rendering of PDF documents inside a custom modal
 * with a classic single-page paginated experience.
 */

document.addEventListener('DOMContentLoaded', function () {
    // Viewer State
    const viewerState = {
        pdfDoc: null,
        pageNum: 1,
        pageRendering: false,
        pageNumPending: null,
        scale: 1.5, // Base scale, will be responsive
        canvas: null,
        ctx: null,
        loading: false
    };

    // Cache DOM Elements
    const modal = document.getElementById('chroma-pdf-modal');
    if (!modal) return;

    const canvasContainer = document.getElementById('chroma-pdf-canvas-container');
    const loadingSpinner = document.getElementById('chroma-pdf-loader');
    const closeBtn = document.getElementById('chroma-pdf-close');
    const prevBtn = document.getElementById('chroma-pdf-prev');
    const nextBtn = document.getElementById('chroma-pdf-next');
    const pageNumSpan = document.getElementById('chroma-pdf-page-num');
    const pageCountSpan = document.getElementById('chroma-pdf-page-count');
    const downloadBtn = document.getElementById('chroma-pdf-download');
    const titleSpan = document.getElementById('chroma-pdf-title');
    const backdrop = document.getElementById('chroma-pdf-backdrop');

    // Initialize Canvas
    viewerState.canvas = document.getElementById('chroma-pdf-canvas');
    if (viewerState.canvas) {
        viewerState.ctx = viewerState.canvas.getContext('2d');
    }

    /**
     * Get page info from document, resize canvas accordingly, and render page.
     * @param num Page number.
     */
    function renderPage(num) {
        if (!viewerState.pdfDoc) return;
        viewerState.pageRendering = true;

        // Fetch page
        viewerState.pdfDoc.getPage(num).then(function (page) {
            // Calculate responsive scale
            const containerWidth = canvasContainer.clientWidth;
            const unscaledViewport = page.getViewport({ scale: 1 });

            // Determine scale to fit width (minus some padding)
            let desiredScale = (containerWidth - 60) / unscaledViewport.width;

            // Limit max scale to keep quality, but ensure mobile readability
            if (desiredScale > 2.0) desiredScale = 2.0;
            if (desiredScale < 0.8) desiredScale = 0.8;

            const viewport = page.getViewport({ scale: desiredScale });

            viewerState.canvas.height = viewport.height;
            viewerState.canvas.width = viewport.width;

            // Render task
            const renderContext = {
                canvasContext: viewerState.ctx,
                viewport: viewport
            };

            const renderTask = page.render(renderContext);

            // Wait for render to finish
            renderTask.promise.then(function () {
                viewerState.pageRendering = false;

                // Hide loader
                loadingSpinner.style.display = 'none';

                if (viewerState.pageNumPending !== null) {
                    // New page rendering is pending
                    renderPage(viewerState.pageNumPending);
                    viewerState.pageNumPending = null;
                }
            });
        });

        // Update page counters
        if (pageNumSpan) pageNumSpan.textContent = num;
        viewerState.pageNum = num;

        // Update button states
        updateNavButtons();
    }

    function updateNavButtons() {
        if (prevBtn) prevBtn.disabled = viewerState.pageNum <= 1;
        if (nextBtn) nextBtn.disabled = viewerState.pageNum >= viewerState.pdfDoc.numPages;
    }

    /**
     * If another page rendering in progress, waits until the rendering is
     * finised. Otherwise, executes rendering immediately.
     */
    function queueRenderPage(num) {
        if (viewerState.pageRendering) {
            viewerState.pageNumPending = num;
        } else {
            renderPage(num);
        }
    }

    /**
     * Displays previous page.
     */
    function onPrevPage() {
        if (viewerState.pageNum <= 1) return;
        viewerState.pageNum--;
        queueRenderPage(viewerState.pageNum);
    }

    /**
     * Displays next page.
     */
    function onNextPage() {
        if (viewerState.pageNum >= viewerState.pdfDoc.numPages) return;
        viewerState.pageNum++;
        queueRenderPage(viewerState.pageNum);
    }

    /**
     * Asynchronously downloads PDF.js
     */
    function loadPdfLibrary(callback) {
        if (window.pdfjsLib) {
            callback();
            return;
        }

        const script = document.createElement('script');
        script.src = chromaPdfConfig.pdfJsUrl;
        script.onload = function () {
            window.pdfjsLib.GlobalWorkerOptions.workerSrc = chromaPdfConfig.pdfWorkerUrl;
            callback();
        };
        document.body.appendChild(script);
    }

    /**
     * Opens the viewer for a specific URL
     */
    function openViewer(url, title) {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        loadingSpinner.style.display = 'flex';

        // Reset State
        viewerState.pageNum = 1;
        viewerState.pdfDoc = null;
        if (viewerState.ctx && viewerState.canvas) {
            viewerState.ctx.clearRect(0, 0, viewerState.canvas.width, viewerState.canvas.height);
        }

        if (titleSpan) titleSpan.textContent = title || 'Document';
        if (downloadBtn) downloadBtn.href = url;

        loadPdfLibrary(function () {
            pdfjsLib.getDocument(url).promise.then(function (pdfDoc_) {
                viewerState.pdfDoc = pdfDoc_;
                if (pageCountSpan) pageCountSpan.textContent = pdfDoc_.numPages;

                // Render first page
                renderPage(viewerState.pageNum);
            }).catch(err => {
                console.error('PDF Error:', err);
                loadingSpinner.innerHTML = '<div class="text-white text-center p-10"><i class="fa-solid fa-circle-exclamation text-4xl mb-4 text-chroma-red"></i><br>Failed to load document.<br>Please use the download button above.</div>';
            });
        });
    }

    function closeViewer() {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }

    // Event Listeners
    if (prevBtn) prevBtn.addEventListener('click', onPrevPage);
    if (nextBtn) nextBtn.addEventListener('click', onNextPage);
    if (closeBtn) closeBtn.addEventListener('click', closeViewer);
    if (backdrop) backdrop.addEventListener('click', closeViewer);

    // Keyboard support
    document.addEventListener('keydown', function (e) {
        if (modal.classList.contains('hidden')) return;
        if (e.key === 'Escape') closeViewer();
        if (e.key === 'ArrowLeft') onPrevPage();
        if (e.key === 'ArrowRight') onNextPage();
    });

    // Handle Window Resize (Debounced re-render)
    let resizeTimeout;
    window.addEventListener('resize', function () {
        if (modal.classList.contains('hidden') || !viewerState.pdfDoc) return;

        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function () {
            renderPage(viewerState.pageNum);
        }, 200);
    });

    window.chromaOpenPdf = openViewer;

    function attachTriggers() {
        document.addEventListener('click', function (e) {
            const trigger = e.target.closest('.chroma-pdf-trigger');
            if (trigger) {
                e.preventDefault();
                const url = trigger.getAttribute('data-pdf-url');
                const title = trigger.getAttribute('data-pdf-title');
                if (url) openViewer(url, title);
            }
        });
    }

    attachTriggers();
});
