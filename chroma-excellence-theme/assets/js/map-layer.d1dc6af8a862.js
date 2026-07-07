/**
 * Leaflet Map Layer
 *
 * @package Chroma_Excellence
 */

(function () {
  const escapeHTML = (value) => String(value || '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');

  const createPopupContent = (location) => {
    const image = location.image
      ? `<img class="chroma-map-popup-photo" src="${escapeHTML(location.image)}" alt="${escapeHTML(location.name)}" loading="lazy">`
      : '';
    const cityState = [location.city, location.state].filter(Boolean).join(', ');
    const addressLine = location.address ? `<p>${escapeHTML(location.address)}</p>` : '';
    const phoneLine = location.phone ? `<p>${escapeHTML(location.phone)}</p>` : '';
    const emailLine = location.email
      ? `<a class="chroma-map-popup-email" href="mailto:${escapeHTML(location.email)}">${escapeHTML(location.email)}</a>`
      : '';
    const viewCampus = (window.chromaData && window.chromaData.viewCampus) || 'View campus';

    return `
      <div class="chroma-map-popup">
        ${image}
        <div class="chroma-map-popup-body">
          <strong>${escapeHTML(location.name)}</strong>
          ${cityState ? `<p>${escapeHTML(cityState)}</p>` : ''}
          ${addressLine}
          ${phoneLine}
          ${emailLine}
          <a class="chroma-map-popup-link" href="${escapeHTML(location.url)}">${escapeHTML(viewCampus)}</a>
        </div>
      </div>
    `;
  };

  const fitLocations = (map, locations, ids) => {
    const allowedIds = Array.isArray(ids) && ids.length
      ? new Set(ids.map((id) => parseInt(id, 10)))
      : null;
    const bounds = locations
      .filter((location) => !allowedIds || allowedIds.has(parseInt(location.id, 10)))
      .map((location) => [location.lat, location.lng]);

    if (bounds.length > 1) {
      map.fitBounds(bounds, { padding: [46, 46], maxZoom: 13 });
    } else if (bounds.length === 1) {
      map.flyTo(bounds[0], 13, { duration: 0.65 });
    }
  };

  const initMaps = () => {
    const mapContainers = document.querySelectorAll('[data-chroma-map]');

    if (!mapContainers.length || typeof L === 'undefined') {
      return;
    }

    mapContainers.forEach((container) => {
      if (container._leaflet_id) return;

      const locationsData = container.getAttribute('data-chroma-locations');

      if (!locationsData) {
        return;
      }

      let locations;
      try {
        locations = JSON.parse(locationsData);
      } catch (e) {
        console.error('Invalid JSON in data-chroma-locations');
        return;
      }

      if (!locations || !locations.length) {
        return;
      }

      const map = L.map(container, {
        scrollWheelZoom: false,
      }).setView([locations[0].lat, locations[0].lng], 11);

      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
      }).addTo(map);

      const markerById = new Map();
      locations.forEach((location) => {
        const marker = L.marker([location.lat, location.lng]).addTo(map);
        marker.bindPopup(createPopupContent(location), {
          autoPan: true,
          autoPanPaddingTopLeft: [92, 168],
          autoPanPaddingBottomRight: [92, 112],
          offset: [0, -18],
          keepInView: true,
          maxWidth: 300,
          minWidth: 240,
          className: 'chroma-map-popup-shell',
        });
        markerById.set(parseInt(location.id, 10), marker);
      });

      const ensureMapVisible = () => {
        const mapShell = container.closest('.chroma-location-map-panel') || container;
        const rect = mapShell.getBoundingClientRect();
        const header = document.querySelector('header');
        const headerHeight = header ? header.getBoundingClientRect().height : 0;
        const topLimit = headerHeight + 24;

        if (rect.top < topLimit || rect.top > topLimit + 120) {
          const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
          window.scrollTo({
            top: Math.max(0, window.scrollY + rect.top - topLimit),
            behavior: prefersReducedMotion ? 'auto' : 'smooth',
          });

          window.setTimeout(() => {
            map.invalidateSize();
          }, prefersReducedMotion ? 0 : 280);
          return;
        }

        map.invalidateSize();
      };

      const focusLocation = (locationId) => {
        const id = parseInt(locationId, 10);
        const location = locations.find((item) => parseInt(item.id, 10) === id);
        const marker = markerById.get(id);
        if (!location || !marker) return;

        ensureMapVisible();

        const target = [location.lat, location.lng];
        const openPopup = () => {
          marker.openPopup();
          window.setTimeout(() => {
            const popup = marker.getPopup && marker.getPopup();
            if (popup && popup.isOpen && popup.isOpen()) {
              map.panInside(marker.getLatLng(), {
                paddingTopLeft: [160, 250],
                paddingBottomRight: [160, 130],
              });
            }
          }, 80);
        };

        map.once('moveend', openPopup);
        map.flyTo(target, 14, { duration: 0.48 });
        window.setTimeout(() => {
          if (!marker.isPopupOpen || !marker.isPopupOpen()) {
            openPopup();
          }
        }, 650);
      };

      const filterLocations = (ids) => {
        const allowedIds = Array.isArray(ids) && ids.length
          ? new Set(ids.map((id) => parseInt(id, 10)))
          : null;

        markerById.forEach((marker, id) => {
          marker.setOpacity(!allowedIds || allowedIds.has(id) ? 1 : 0.28);
        });

        fitLocations(map, locations, ids);
      };

      container.chromaMapApi = {
        filterLocations,
        focusLocation,
      };

      fitLocations(map, locations);

      const mapId = container.id;
      const pendingState = window.chromaLocationExplorerState && window.chromaLocationExplorerState[mapId];
      if (pendingState && Array.isArray(pendingState.ids)) {
        filterLocations(pendingState.ids);
      }
      if (pendingState && pendingState.focusId) {
        focusLocation(pendingState.focusId);
      }

      window.dispatchEvent(new CustomEvent('chroma:map-ready', {
        detail: { mapId, map },
      }));
    });
  };

  window.addEventListener('chroma:locations-filter', (event) => {
    const detail = event.detail || {};
    const container = document.getElementById(detail.mapId);
    if (container && container.chromaMapApi) {
      container.chromaMapApi.filterLocations(detail.ids || []);
    }
  });

  window.addEventListener('chroma:locations-focus', (event) => {
    const detail = event.detail || {};
    const container = document.getElementById(detail.mapId);
    if (container && container.chromaMapApi) {
      container.chromaMapApi.focusLocation(detail.id);
    }
  });

  if (typeof L !== 'undefined') {
    initMaps();
  } else {
    document.addEventListener('DOMContentLoaded', initMaps);
  }
})();
