/**
 * Map Facade
 * Lazy loads Leaflet and initializes maps on scroll.
 */
document.addEventListener('DOMContentLoaded', function () {
    const mapContainers = document.querySelectorAll('[data-chroma-map]');
    if (!mapContainers.length) return;

    const loadMapAssets = () => {
        // Prevent multiple loads
        if (window.chromaMapAssetsLoaded) return;
        window.chromaMapAssetsLoaded = true;

        // Load Leaflet CSS
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
        document.head.appendChild(link);

        // Load Leaflet JS
        const script = document.createElement('script');
        script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
        script.async = true;
        script.onload = () => {
            // Load Map Layer Logic
            const mapLayerScript = document.createElement('script');
            mapLayerScript.src = chromaData.themeUrl + '/assets/js/map-layer.js';
            mapLayerScript.async = true;
            document.body.appendChild(mapLayerScript);
        };
        document.body.appendChild(script);
    };

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
});
