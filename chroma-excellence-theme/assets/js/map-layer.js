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
      ? `<img class="chroma-map-popup-photo" src="${escapeHTML(location.image)}" alt="${escapeHTML(location.name)}" width="640" height="360" loading="lazy" decoding="async">`
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
        attribution: '&copy; OpenStreetMap contributors',
        updateWhenIdle: true,
        keepBuffer: 2,
        detectRetina: true,
      }).addTo(map);

      const markerById = new Map();
      let activeMarkerIds = null;
      const compactMap = window.matchMedia('(max-width: 767px)').matches;
      const popupPaddingTopLeft = compactMap ? [20, 72] : [92, 168];
      const popupPaddingBottomRight = compactMap ? [20, 44] : [92, 112];
      const themeUrl = window.chromaData && window.chromaData.themeUrl;
      const markerIconUrl = themeUrl
        ? `${themeUrl}/assets/vendor/leaflet-1.9.4/images/marker-icon-2x.png`
        : '';
      const markerIcon = L.divIcon({
        className: 'chroma-map-marker-target',
        html: markerIconUrl ? `<img src="${escapeHTML(markerIconUrl)}" alt="">` : '',
        iconSize: [44, 44],
        iconAnchor: [22, 41],
        popupAnchor: [0, -36],
      });
      locations.forEach((location) => {
        const marker = L.marker([location.lat, location.lng], {
          icon: markerIcon,
          interactive: false,
          keyboard: false,
          title: '',
          alt: '',
        }).addTo(map);
        marker.bindPopup(createPopupContent(location), {
          autoPan: !compactMap,
          autoPanPaddingTopLeft: popupPaddingTopLeft,
          autoPanPaddingBottomRight: popupPaddingBottomRight,
          offset: [0, -18],
          keepInView: !compactMap,
          maxWidth: compactMap ? 272 : 300,
          minWidth: compactMap ? 208 : 240,
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

      const keepPopupInsideMap = (popup) => {
        if (!popup || !popup.isOpen || !popup.isOpen()) return;

        popup.update();
        window.requestAnimationFrame(() => {
          const popupElement = popup.getElement && popup.getElement();
          const mapElement = map.getContainer();
          if (!popupElement || !mapElement) return;

          const popupRect = popupElement.getBoundingClientRect();
          const mapRect = mapElement.getBoundingClientRect();
          const inset = compactMap ? 8 : 16;

          if (compactMap) {
            popupElement.classList.add('chroma-map-popup-contained');
            popupElement.style.setProperty(
              '--chroma-popup-left',
              `${Math.max(inset, Math.round((mapRect.width - popupRect.width) / 2))}px`
            );
            popupElement.style.setProperty('--chroma-popup-top', `${inset}px`);
            return;
          }

          let panX = 0;
          let panY = 0;

          if (popupRect.left < mapRect.left + inset) {
            panX = popupRect.left - (mapRect.left + inset);
          } else if (popupRect.right > mapRect.right - inset) {
            panX = popupRect.right - (mapRect.right - inset);
          }

          if (popupRect.top < mapRect.top + inset) {
            panY = popupRect.top - (mapRect.top + inset);
          } else if (popupRect.bottom > mapRect.bottom - inset) {
            panY = popupRect.bottom - (mapRect.bottom - inset);
          }

          if (panX || panY) {
            map.panBy([panX, panY], { animate: false });
          }
        });
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
              keepPopupInsideMap(popup);
              const popupImage = popup.getElement()?.querySelector('.chroma-map-popup-photo');
              if (popupImage && !popupImage.complete) {
                popupImage.addEventListener('load', () => keepPopupInsideMap(popup), { once: true });
              }
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

        activeMarkerIds = allowedIds;

        markerById.forEach((marker, id) => {
          marker.setOpacity(!allowedIds || allowedIds.has(id) ? 1 : 0.28);
        });

        fitLocations(map, locations, ids);
      };

      // Pins are visual map affordances while the adjacent campus cards are the
      // fully accessible keyboard controls. Resolve pointer clicks through the
      // map itself so dense overlapping pins do not create undersized or
      // competing touch targets.
      map.on('click', (event) => {
        const clickPoint = map.latLngToContainerPoint(event.latlng);
        let closestId = null;
        let closestDistance = 29;

        markerById.forEach((marker, id) => {
          if (activeMarkerIds && !activeMarkerIds.has(id)) {
            return;
          }

          const markerPoint = map.latLngToContainerPoint(marker.getLatLng());
          const distance = clickPoint.distanceTo(markerPoint);
          if (distance < closestDistance) {
            closestId = id;
            closestDistance = distance;
          }
        });

        if (closestId !== null) {
          focusLocation(closestId);
        }
      });

      container.chromaMapApi = {
        filterLocations,
        focusLocation,
      };

      if ('ResizeObserver' in window) {
        const resizeObserver = new ResizeObserver(() => {
          window.requestAnimationFrame(() => map.invalidateSize({ pan: false }));
        });
        resizeObserver.observe(container);
      }

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
