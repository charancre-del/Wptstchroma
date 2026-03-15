/**
 * Chroma Pro PDF Viewer
 *
 * Optimized for quicker perceived load:
 * - caches opened PDFs by URL
 * - paints page 1 at a lower scale first, then sharpens it
 * - warms poster previews for triggers and reuses them in the loader
 */

document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('chroma-pdf-modal');
    if (!modal) {
        return;
    }

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

    const LOW_RES_SCALE_CAP = 0.72;
    const ENHANCE_SCALE_DELTA = 0.05;
    const POSTER_MAX_WIDTH = 240;
    const MAX_TARGET_SCALE = 2.6;
    const HIGH_RES_OUTPUT_SCALE = Math.max(1.75, Math.min(window.devicePixelRatio || 1, 2.5));

    const viewerState = {
        pdfDoc: null,
        pageNum: 1,
        pageRendering: false,
        pageNumPending: null,
        canvas: null,
        ctx: null,
        currentUrl: '',
        renderGeneration: 0,
        enhanceTimer: null
    };

    const defaultLoaderMarkup = loadingSpinner ? loadingSpinner.innerHTML : '';
    const pdfDocCache = new Map();
    const pdfDocPromiseCache = new Map();
    const posterCache = new Map();
    const posterPromiseCache = new Map();
    let pdfLibraryPromise = null;

    function getPdfConfig() {
        if (window.chromaPdfConfig && window.chromaPdfConfig.pdfJsUrl && window.chromaPdfConfig.pdfWorkerUrl) {
            return window.chromaPdfConfig;
        }

        const script = document.querySelector('script[src*="/assets/js/chroma-pdf-viewer.js"]');
        if (!script || !script.src) {
            return null;
        }

        const scriptUrl = new URL(script.src, window.location.href);
        const viewerPath = '/assets/js/chroma-pdf-viewer.js';
        const pathname = scriptUrl.pathname;
        const basePath = pathname.endsWith(viewerPath)
            ? pathname.slice(0, pathname.length - viewerPath.length)
            : '';

        if (basePath === '') {
            return null;
        }

        return {
            pdfJsUrl: scriptUrl.origin + basePath + '/assets/js/pdf/pdf.min.js',
            pdfWorkerUrl: scriptUrl.origin + basePath + '/assets/js/pdf/pdf.worker.min.js'
        };
    }

    function getClosestTrigger(target) {
        const element = target && target.nodeType === 1 ? target : (target && target.parentElement ? target.parentElement : null);
        if (!element || typeof element.closest !== 'function') {
            return null;
        }

        return element.closest('.chroma-pdf-trigger');
    }

    function isPdfUrl(url) {
        return /\.pdf(?:\?.*)?$/i.test(String(url || ''));
    }

    function ensureCanvas() {
        if (!viewerState.canvas) {
            viewerState.canvas = document.getElementById('chroma-pdf-canvas');
            if (viewerState.canvas) {
                viewerState.ctx = viewerState.canvas.getContext('2d');
            }
        }

        return !!(viewerState.canvas && viewerState.ctx);
    }

    function clearPendingEnhancement() {
        if (viewerState.enhanceTimer) {
            window.clearTimeout(viewerState.enhanceTimer);
            viewerState.enhanceTimer = null;
        }
    }

    function hideLoader() {
        if (!loadingSpinner) {
            return;
        }

        loadingSpinner.style.display = 'none';
    }

    function showLoader(posterUrl) {
        if (!loadingSpinner) {
            return;
        }

        loadingSpinner.innerHTML = defaultLoaderMarkup;
        loadingSpinner.style.display = 'flex';

        if (posterUrl) {
            loadingSpinner.style.backgroundImage =
                'linear-gradient(rgba(15, 30, 38, 0.28), rgba(15, 30, 38, 0.55)), url("' + posterUrl + '")';
            loadingSpinner.style.backgroundPosition = 'center center';
            loadingSpinner.style.backgroundRepeat = 'no-repeat';
            loadingSpinner.style.backgroundSize = 'contain';
        } else {
            loadingSpinner.style.backgroundImage = '';
            loadingSpinner.style.backgroundPosition = '';
            loadingSpinner.style.backgroundRepeat = '';
            loadingSpinner.style.backgroundSize = '';
        }
    }

    function updateNavButtons() {
        const numPages = viewerState.pdfDoc ? viewerState.pdfDoc.numPages : 0;

        if (prevBtn) {
            prevBtn.disabled = viewerState.pageNum <= 1;
        }

        if (nextBtn) {
            nextBtn.disabled = !numPages || viewerState.pageNum >= numPages;
        }
    }

    function ensurePdfLibrary() {
        if (window.pdfjsLib) {
            return Promise.resolve(window.pdfjsLib);
        }

        if (pdfLibraryPromise) {
            return pdfLibraryPromise;
        }

        pdfLibraryPromise = new Promise(function (resolve, reject) {
            const pdfConfig = getPdfConfig();
            if (!pdfConfig) {
                reject(new Error('chromaPdfConfig is not available.'));
                return;
            }

            const script = document.createElement('script');
            script.src = pdfConfig.pdfJsUrl;
            script.onload = function () {
                window.pdfjsLib.GlobalWorkerOptions.workerSrc = pdfConfig.pdfWorkerUrl;
                resolve(window.pdfjsLib);
            };
            script.onerror = reject;
            document.body.appendChild(script);
        });

        return pdfLibraryPromise;
    }

    function getPdfDocument(url) {
        if (pdfDocCache.has(url)) {
            return Promise.resolve(pdfDocCache.get(url));
        }

        if (pdfDocPromiseCache.has(url)) {
            return pdfDocPromiseCache.get(url);
        }

        const docPromise = ensurePdfLibrary()
            .then(function () {
                return window.pdfjsLib.getDocument(url).promise;
            })
            .then(function (pdfDoc) {
                pdfDocCache.set(url, pdfDoc);
                pdfDocPromiseCache.delete(url);
                return pdfDoc;
            })
            .catch(function (error) {
                pdfDocPromiseCache.delete(url);
                throw error;
            });

        pdfDocPromiseCache.set(url, docPromise);
        return docPromise;
    }

    function getTargetScale(page) {
        let containerWidth = canvasContainer ? canvasContainer.clientWidth : 0;
        if (containerWidth <= 0) {
            containerWidth = modal.clientWidth || window.innerWidth || 800;
        }

        const unscaledViewport = page.getViewport({ scale: 1 });
        let desiredScale = (containerWidth - 60) / unscaledViewport.width;

        if (desiredScale > MAX_TARGET_SCALE) {
            desiredScale = MAX_TARGET_SCALE;
        }

        if (desiredScale < 0.6) {
            desiredScale = 0.6;
        }

        return desiredScale;
    }

    function renderCanvasPage(page, scale, outputScale) {
        if (!ensureCanvas()) {
            return Promise.reject(new Error('PDF canvas is not available.'));
        }

        const backingScale = Math.max(1, outputScale || 1);
        const viewport = page.getViewport({ scale: scale });
        viewerState.canvas.width = Math.ceil(viewport.width * backingScale);
        viewerState.canvas.height = Math.ceil(viewport.height * backingScale);
        viewerState.canvas.style.width = Math.ceil(viewport.width) + 'px';
        viewerState.canvas.style.height = Math.ceil(viewport.height) + 'px';
        viewerState.ctx.setTransform(backingScale, 0, 0, backingScale, 0, 0);
        viewerState.ctx.clearRect(0, 0, viewport.width, viewport.height);

        return page.render({
            canvasContext: viewerState.ctx,
            viewport: viewport
        }).promise;
    }

    function buildPoster(url) {
        if (!isPdfUrl(url)) {
            return Promise.resolve('');
        }

        if (posterCache.has(url)) {
            return Promise.resolve(posterCache.get(url));
        }

        if (posterPromiseCache.has(url)) {
            return posterPromiseCache.get(url);
        }

        const posterPromise = getPdfDocument(url)
            .then(function (pdfDoc) {
                return pdfDoc.getPage(1);
            })
            .then(function (page) {
                const unscaledViewport = page.getViewport({ scale: 1 });
                const posterScale = Math.min(POSTER_MAX_WIDTH / unscaledViewport.width, 0.6);
                const viewport = page.getViewport({ scale: posterScale });
                const posterCanvas = document.createElement('canvas');
                const posterCtx = posterCanvas.getContext('2d');

                posterCanvas.width = viewport.width;
                posterCanvas.height = viewport.height;

                return page.render({
                    canvasContext: posterCtx,
                    viewport: viewport
                }).promise.then(function () {
                    let dataUrl = '';

                    try {
                        dataUrl = posterCanvas.toDataURL('image/jpeg', 0.72);
                    } catch (error) {
                        dataUrl = '';
                    }

                    posterCache.set(url, dataUrl);
                    posterPromiseCache.delete(url);

                    return dataUrl;
                });
            })
            .catch(function () {
                posterPromiseCache.delete(url);
                return '';
            });

        posterPromiseCache.set(url, posterPromise);
        return posterPromise;
    }

    function warmTriggerPoster(trigger) {
        if (!trigger) {
            return;
        }

        const url = trigger.getAttribute('data-pdf-url');
        if (!isPdfUrl(url)) {
            return;
        }

        buildPoster(url).then(function (posterUrl) {
            if (posterUrl) {
                trigger.setAttribute('data-pdf-poster', posterUrl);
            }
        });
    }

    function queueRenderPage(num) {
        clearPendingEnhancement();

        if (viewerState.pageRendering) {
            viewerState.pageNumPending = num;
        } else {
            renderPage(num, {
                lowResFirst: false,
                showLoader: false
            });
        }
    }

    function renderPage(num, options) {
        options = options || {};

        if (!viewerState.pdfDoc || !ensureCanvas()) {
            return;
        }

        viewerState.pageRendering = true;
        viewerState.pageNum = num;
        viewerState.pageNumPending = null;
        updateNavButtons();

        if (pageNumSpan) {
            pageNumSpan.textContent = String(num);
        }

        const renderGeneration = ++viewerState.renderGeneration;

        if (options.showLoader) {
            showLoader(options.posterUrl || posterCache.get(viewerState.currentUrl) || '');
        }

        viewerState.pdfDoc.getPage(num).then(function (page) {
            const targetScale = getTargetScale(page);
            const firstPassScale = options.lowResFirst
                ? Math.min(targetScale, LOW_RES_SCALE_CAP)
                : targetScale;
            const shouldEnhance = options.lowResFirst && targetScale > (firstPassScale + ENHANCE_SCALE_DELTA);

            return renderCanvasPage(page, firstPassScale, 1).then(function () {
                if (renderGeneration !== viewerState.renderGeneration) {
                    return;
                }

                if (!posterCache.has(viewerState.currentUrl)) {
                    try {
                        posterCache.set(viewerState.currentUrl, viewerState.canvas.toDataURL('image/jpeg', 0.72));
                    } catch (error) {
                        // Ignore poster failures. The main render succeeded.
                    }
                }

                hideLoader();

                if (viewerState.pageNumPending !== null) {
                    const pendingPage = viewerState.pageNumPending;
                    viewerState.pageNumPending = null;
                    viewerState.pageRendering = false;
                    renderPage(pendingPage, {
                        lowResFirst: false,
                        showLoader: false
                    });
                    return;
                }

                viewerState.pageRendering = false;

                if (!shouldEnhance) {
                    return;
                }

                clearPendingEnhancement();
                viewerState.enhanceTimer = window.setTimeout(function () {
                    if (
                        renderGeneration !== viewerState.renderGeneration ||
                        viewerState.pageNum !== num ||
                        viewerState.currentUrl !== options.urlKey
                    ) {
                        return;
                    }

                    renderCanvasPage(page, targetScale, HIGH_RES_OUTPUT_SCALE).catch(function () {
                        // The low-res render already succeeded. Ignore enhancement failures.
                    });
                }, 80);
            });
        }).catch(function (error) {
            if (renderGeneration !== viewerState.renderGeneration) {
                return;
            }

            viewerState.pageRendering = false;

            if (loadingSpinner) {
                loadingSpinner.innerHTML =
                    '<div class="text-white text-center p-10"><i class="fa-solid fa-circle-exclamation text-4xl mb-4 text-chroma-red"></i><br>Failed to load document.<br>Please use the download button above.</div>';
                loadingSpinner.style.display = 'flex';
            }

            console.error('PDF Error:', error);
        });
    }

    function openViewer(url, title) {
        viewerState.currentUrl = url;
        viewerState.pageNum = 1;
        viewerState.pdfDoc = null;
        viewerState.pageNumPending = null;
        viewerState.pageRendering = false;
        clearPendingEnhancement();
        viewerState.renderGeneration += 1;

        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';

        if (titleSpan) {
            titleSpan.textContent = title || 'Document';
        }

        if (downloadBtn) {
            downloadBtn.href = url;
        }

        showLoader(posterCache.get(url) || '');

        if (ensureCanvas()) {
            viewerState.ctx.clearRect(0, 0, viewerState.canvas.width, viewerState.canvas.height);
        }

        buildPoster(url).then(function (posterUrl) {
            if (posterUrl && viewerState.currentUrl === url && !viewerState.pdfDoc) {
                showLoader(posterUrl);
            }
        });

        getPdfDocument(url).then(function (pdfDoc) {
            if (viewerState.currentUrl !== url) {
                return;
            }

            viewerState.pdfDoc = pdfDoc;

            if (pageCountSpan) {
                pageCountSpan.textContent = String(pdfDoc.numPages);
            }

            updateNavButtons();

            renderPage(1, {
                lowResFirst: true,
                showLoader: true,
                posterUrl: posterCache.get(url) || '',
                urlKey: url
            });
        }).catch(function (error) {
            if (loadingSpinner) {
                loadingSpinner.innerHTML =
                    '<div class="text-white text-center p-10"><i class="fa-solid fa-circle-exclamation text-4xl mb-4 text-chroma-red"></i><br>Failed to load document.<br>Please use the download button above.</div>';
                loadingSpinner.style.display = 'flex';
            }

            console.error('PDF Error:', error);
        });
    }

    function closeViewer() {
        clearPendingEnhancement();
        viewerState.pageNumPending = null;
        viewerState.pageRendering = false;
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }

    function onPrevPage() {
        if (viewerState.pageNum <= 1) {
            return;
        }

        queueRenderPage(viewerState.pageNum - 1);
    }

    function onNextPage() {
        if (!viewerState.pdfDoc || viewerState.pageNum >= viewerState.pdfDoc.numPages) {
            return;
        }

        queueRenderPage(viewerState.pageNum + 1);
    }

    function onKeyDown(event) {
        if (modal.classList.contains('hidden')) {
            return;
        }

        if (event.key === 'Escape') {
            closeViewer();
        }

        if (event.key === 'ArrowLeft') {
            onPrevPage();
        }

        if (event.key === 'ArrowRight') {
            onNextPage();
        }
    }

    let resizeTimeout;
    function onResize() {
        if (modal.classList.contains('hidden') || !viewerState.pdfDoc) {
            return;
        }

        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function () {
            renderPage(viewerState.pageNum, {
                lowResFirst: false,
                showLoader: false,
                urlKey: viewerState.currentUrl
            });
        }, 200);
    }

    if (prevBtn) {
        prevBtn.addEventListener('click', onPrevPage);
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', onNextPage);
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', closeViewer);
    }

    if (backdrop) {
        backdrop.addEventListener('click', closeViewer);
    }

    document.addEventListener('keydown', onKeyDown);
    window.addEventListener('resize', onResize);

    document.addEventListener('click', function (event) {
        const trigger = getClosestTrigger(event.target);
        if (!trigger) {
            return;
        }

        event.preventDefault();

        const url = trigger.getAttribute('data-pdf-url');
        const title = trigger.getAttribute('data-pdf-title');

        if (!url) {
            return;
        }

        openViewer(url, title);
    });

    document.addEventListener('mouseenter', function (event) {
        const trigger = getClosestTrigger(event.target);
        if (trigger) {
            warmTriggerPoster(trigger);
        }
    }, true);

    document.addEventListener('focusin', function (event) {
        const trigger = getClosestTrigger(event.target);
        if (trigger) {
            warmTriggerPoster(trigger);
        }
    });

    document.addEventListener('touchstart', function (event) {
        const trigger = getClosestTrigger(event.target);
        if (trigger) {
            warmTriggerPoster(trigger);
        }
    }, { passive: true });

    window.chromaOpenPdf = openViewer;
});
