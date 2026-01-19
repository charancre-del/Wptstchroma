/**
 * Chroma Pro PDF Viewer - Vertical Scroll Edition
 * 
 * Handles lazy-loading of PDF.js and rendering of PDF documents inside a custom modal
 * with a continuous vertical scroll experience.
 */

document.addEventListener('DOMContentLoaded', function () {
    // Viewer State
    const viewerState = {
        pdfDoc: null,
        pageNum: 1,
        pagesRendered: new Set(),
        scale: 1.5,
        loading: false,
        container: null,
        scrollWrapper: null,
        observer: null
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

    /**
     * Renders a specific page into its reserved canvas
     */
    function renderPage(num) {
        if (viewerState.pagesRendered.has(num)) return;
        viewerState.pagesRendered.add(num);

        const pageWrapper = document.querySelector(`[data-page-wrapper="${num}"]`);
        if (!pageWrapper) return;

        const canvas = pageWrapper.querySelector('canvas');
        const ctx = canvas.getContext('2d');

        viewerState.pdfDoc.getPage(num).then(function (page) {
            const containerWidth = canvasContainer.clientWidth;
            const unscaledViewport = page.getViewport({ scale: 1 });

            // Calculate scale to fit width
            let desiredScale = (containerWidth - 60) / unscaledViewport.width;
            if (desiredScale > 2.0) desiredScale = 2.0;
            if (desiredScale < 0.8) desiredScale = 0.8;

            const viewport = page.getViewport({ scale: desiredScale });
            canvas.height = viewport.height;
            canvas.width = viewport.width;

            const renderContext = {
                canvasContext: ctx,
                viewport: viewport
            };

            page.render(renderContext).promise.then(function () {
                pageWrapper.classList.add('rendered');
                // Hide main loader if it's the first page
                if (num === 1) loadingSpinner.style.display = 'none';
            });
        });
    }

    /**
     * Initializes the scrollable page list
     */
    function initScrollArea() {
        // Clear previous content
        const existingWrapper = canvasContainer.querySelector('.pdf-scroll-wrapper');
        if (existingWrapper) existingWrapper.remove();

        const scrollWrapper = document.createElement('div');
        scrollWrapper.className = 'pdf-scroll-wrapper w-full h-full overflow-y-auto overflow-x-hidden custom-scrollbar flex flex-col items-center gap-8 py-10';
        viewerState.scrollWrapper = scrollWrapper;
        canvasContainer.appendChild(scrollWrapper);

        // Disconnect old observer
        if (viewerState.observer) viewerState.observer.disconnect();

        // Setup Intersection Observer for lazy loading and current page tracking
        viewerState.observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const pageNum = parseInt(entry.target.getAttribute('data-page-wrapper'));

                    // Lazy Render
                    renderPage(pageNum);

                    // Update UI page number (when page is significantly visible)
                    if (entry.intersectionRatio > 0.5) {
                        viewerState.pageNum = pageNum;
                        if (pageNumSpan) pageNumSpan.textContent = pageNum;
                        updateNavButtons();
                    }
                }
            });
        }, {
            root: scrollWrapper,
            threshold: [0.1, 0.5, 0.9]
        });

        // Create placeholders for all pages
        for (let i = 1; i <= viewerState.pdfDoc.numPages; i++) {
            const pageDiv = document.createElement('div');
            pageDiv.className = 'pdf-page-container relative bg-white shadow-2xl rounded transition-opacity duration-500 opacity-0';
            pageDiv.setAttribute('data-page-wrapper', i);
            pageDiv.style.minHeight = '500px'; // Initial guess
            pageDiv.style.width = 'fit-content';

            // Ensure first page loads fast
            if (i === 1) pageDiv.style.opacity = '1';

            const canvas = document.createElement('canvas');
            canvas.className = 'block';
            pageDiv.appendChild(canvas);

            scrollWrapper.appendChild(pageDiv);
            viewerState.observer.observe(pageDiv);
        }
    }

    function updateNavButtons() {
        if (prevBtn) prevBtn.disabled = viewerState.pageNum <= 1;
        if (nextBtn) nextBtn.disabled = viewerState.pageNum >= viewerState.pdfDoc.numPages;
    }

    function scrollToPage(num) {
        const target = document.querySelector(`[data-page-wrapper="${num}"]`);
        if (target && viewerState.scrollWrapper) {
            viewerState.scrollWrapper.scrollTo({
                top: target.offsetTop - 20,
                behavior: 'smooth'
            });
        }
    }

    /**
     * Library Loading
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
     * Public Interface
     */
    function openViewer(url, title) {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        loadingSpinner.style.display = 'flex';

        // Reset State
        viewerState.pagesRendered.clear();
        viewerState.pageNum = 1;

        if (titleSpan) titleSpan.textContent = title || 'Document';
        if (downloadBtn) downloadBtn.href = url;

        loadPdfLibrary(function () {
            pdfjsLib.getDocument(url).promise.then(function (pdfDoc_) {
                viewerState.pdfDoc = pdfDoc_;
                if (pageCountSpan) pageCountSpan.textContent = pdfDoc_.numPages;
                initScrollArea();
            }).catch(err => {
                console.error('PDF Error:', err);
                loadingSpinner.innerHTML = '<div class="text-white text-center p-10"><i class="fa-solid fa-circle-exclamation text-4xl mb-4 text-chroma-red"></i><br>Failed to load document.<br>Please use the download button above.</div>';
            });
        });
    }

    function closeViewer() {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
        if (viewerState.scrollWrapper) viewerState.scrollWrapper.remove();
        if (viewerState.observer) viewerState.observer.disconnect();
    }

    // Controls
    if (prevBtn) prevBtn.addEventListener('click', () => scrollToPage(viewerState.pageNum - 1));
    if (nextBtn) nextBtn.addEventListener('click', () => scrollToPage(viewerState.pageNum + 1));
    if (closeBtn) closeBtn.addEventListener('click', closeViewer);
    if (backdrop) backdrop.addEventListener('click', closeViewer);

    // Keyboard support
    document.addEventListener('keydown', function (e) {
        if (modal.classList.contains('hidden')) return;
        if (e.key === 'Escape') closeViewer();
        if (e.key === 'ArrowUp' || e.key === 'ArrowLeft') scrollToPage(viewerState.pageNum - 1);
        if (e.key === 'ArrowDown' || e.key === 'ArrowRight') scrollToPage(viewerState.pageNum + 1);
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

