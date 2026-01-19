/**
 * Chroma Pro PDF Viewer
 * 
 * Handles lazy-loading of PDF.js and rendering of PDF documents inside a custom modal.
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
    if (!modal) return; // Exit if modal not present

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
        viewerState.pageRendering = true;

        // Fetch page
        viewerState.pdfDoc.getPage(num).then(function (page) {
            // Calculate responsive scale
            const containerWidth = canvasContainer.clientWidth;
            // Get unscaled viewport to determine aspect ratio
            const unscaledViewport = page.getViewport({ scale: 1 });
            // Determine scale to fit width (minus some padding)
            let desiredScale = (containerWidth - 40) / unscaledViewport.width;

            // Limit max scale to keep quality, but ensure mobile readability
            if (desiredScale > 2.0) desiredScale = 2.0;
            if (desiredScale < 0.6) desiredScale = 0.6;

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

        // Update button states
        if (prevBtn) prevBtn.disabled = num <= 1;
        if (nextBtn) nextBtn.disabled = num >= viewerState.pdfDoc.numPages;
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
        script.src = chromaPdfConfig.pdfJsUrl; // Passed via localization
        script.onload = function () {
            // Set worker source
            window.pdfjsLib.GlobalWorkerOptions.workerSrc = chromaPdfConfig.pdfWorkerUrl;
            callback();
        };
        document.body.appendChild(script);
    }

    /**
     * Opens the viewer for a specific URL
     */
    function openViewer(url, title) {
        // Show Modal
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden'; // Prevent scrolling bg
        loadingSpinner.style.display = 'flex';

        // Reset State
        viewerState.pageNum = 1;
        viewerState.pdfDoc = null;
        if (viewerState.ctx && viewerState.canvas) {
            viewerState.ctx.clearRect(0, 0, viewerState.canvas.width, viewerState.canvas.height);
        }

        // Set UI
        if (titleSpan) titleSpan.textContent = title || 'Document';
        if (downloadBtn) downloadBtn.href = url;

        // Lazy Load Lib, then Load PDF
        loadPdfLibrary(function () {
            const loadingTask = pdfjsLib.getDocument(url);
            loadingTask.promise.then(function (pdfDoc_) {
                viewerState.pdfDoc = pdfDoc_;
                if (pageCountSpan) pageCountSpan.textContent = pdfDoc_.numPages;

                // Render first page
                renderPage(viewerState.pageNum);
            }, function (reason) {
                // PDF loading error
                console.error('Error loading PDF:', reason);
                loadingSpinner.innerHTML = '<div class="text-red-500">Error loading document.<br>Please use the Download button.</div>';
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

    // Expose Global Opener (for manual triggers)
    window.chromaOpenPdf = openViewer;

    // Attach to triggers
    // Finds elements with class 'chroma-pdf-trigger' and attribute 'data-pdf-url'
    function attachTriggers() {
        const triggers = document.querySelectorAll('.chroma-pdf-trigger');
        triggers.forEach(trigger => {
            trigger.addEventListener('click', function (e) {
                e.preventDefault();
                const url = this.getAttribute('data-pdf-url');
                const title = this.getAttribute('data-pdf-title');
                if (url) openViewer(url, title);
            });
        });
    }

    // Run on init
    attachTriggers();
});
