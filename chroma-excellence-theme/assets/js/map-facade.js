/**
 * Map Facade
 * Lazy loads Leaflet and initializes maps on scroll.
 */
document.addEventListener('DOMContentLoaded', function () {
    const mapContainers = document.querySelectorAll('[data-chroma-map]');
    if (!mapContainers.length) return;

    const loadMapAssets = () => {
        // Prevent multiple loads
        if (window.chromaMapAssetsLoaded || window.chromaMapAssetsLoading) return;
        window.chromaMapAssetsLoading = true;

        const loadMapLayer = () => {
            if (window.chromaMapLayerLoading) return;
            window.chromaMapLayerLoading = true;

            const themeUrl = window.chromaData && window.chromaData.themeUrl;
            if (!themeUrl) {
                window.chromaMapAssetsLoading = false;
                return;
            }

            const mapLayerScript = document.createElement('script');
            mapLayerScript.src = (window.chromaData && window.chromaData.mapLayerUrl)
                ? window.chromaData.mapLayerUrl
                : themeUrl + '/assets/js/map-layer.js';
            mapLayerScript.async = true;
            mapLayerScript.onload = () => {
                window.chromaMapAssetsLoaded = true;
                window.chromaMapAssetsLoading = false;
            };
            mapLayerScript.onerror = () => {
                window.chromaMapLayerLoading = false;
                window.chromaMapAssetsLoading = false;
            };
            document.body.appendChild(mapLayerScript);
        };

        if (window.L) {
            loadMapLayer();
            return;
        }

        const themeUrl = window.chromaData && window.chromaData.themeUrl;
        if (!themeUrl) {
            window.chromaMapAssetsLoading = false;
            return;
        }

        const leafletBaseUrl = themeUrl + '/assets/vendor/leaflet-1.9.4';

        // Load Leaflet CSS
        if (!document.querySelector('link[data-chroma-leaflet-css]')) {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = leafletBaseUrl + '/leaflet.min.css';
            link.setAttribute('data-chroma-leaflet-css', 'true');
            document.head.appendChild(link);
        }

        // Load Leaflet JS
        const script = document.createElement('script');
        script.src = leafletBaseUrl + '/leaflet.min.js';
        script.async = true;
        script.onload = loadMapLayer;
        script.onerror = () => {
            window.chromaMapAssetsLoading = false;
        };
        document.body.appendChild(script);
    };

    if (!('IntersectionObserver' in window)) {
        loadMapAssets();
        return;
    }

    // Intersection Observer
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                loadMapAssets();
                observer.disconnect();
            }
        });
    }, {
        rootMargin: '200px' // Start loading before it comes into view
    });

    mapContainers.forEach(container => observer.observe(container));

    mapContainers.forEach((container) => {
        ['pointerdown', 'touchstart', 'focusin', 'keydown'].forEach((eventName) => {
            container.addEventListener(eventName, loadMapAssets, { once: true, passive: eventName !== 'keydown' });
        });

        const explorer = container.closest('[data-location-explorer]');
        if (explorer) {
            explorer.querySelectorAll('[data-location-filter], [data-location-card]').forEach((control) => {
                control.addEventListener('click', loadMapAssets, { once: true });
            });
        }
    });
});
