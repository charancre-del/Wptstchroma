/**
 * Chroma TV Dashboard - AJAX Poller
 * Handles live updates without page reload.
 */

document.addEventListener('DOMContentLoaded', function () {
    const config = window.ChromaConfig || {};
    if (!config.slug) return; // Not configured

    // State
    let slideImages = window.slideImages || [];
    let currentSlideIndex = 0;
    const UPDATE_INTERVAL = 60000; // 1 minute
    const SLIDE_INTERVAL = 8000;   // 8 seconds

    // Elements
    const els = {
        clock: document.getElementById('clock'),
        ampm: document.getElementById('ampm'),
        date: document.getElementById('current-date'),
        weatherTemp: document.getElementById('weather-temp'),
        weatherDesc: document.getElementById('weather-desc'),
        weatherIcon: document.getElementById('weather-icon'),
        weatherContainer: document.getElementById('weather-widget'),
        notices: document.getElementById('notices-container'),
        today: document.getElementById('today-container'),
        slideshowImg: document.getElementById('slideshow-img'),
        slideshowTitle: document.getElementById('slideshow-title'),
        newsletter: document.getElementById('newsletter-container'),
        eom: document.getElementById('eom-container'),
        cares: document.getElementById('cares-container'),
        celebrations: document.getElementById('celebrations-container'),
        alert: document.getElementById('global-alert-container'),
        menu: document.getElementById('menu-container')
    };

    /**
     * Start the system
     */
    function init() {
        updateClock();
        setInterval(updateClock, 1000);

        // Start Slideshow Loop (Independent of data fetch)
        startSlideshow();

        // Initial Fetch (defer slightly to not block render)
        setTimeout(fetchDashboardData, 5000);

        // Poll
        setInterval(fetchDashboardData, UPDATE_INTERVAL);
    }

    /**
     * Fetch Data from API
     */
    async function fetchDashboardData() {
        try {
            // 1. Fetch School Data
            const res = await fetch(`${config.apiUrl}chroma/v1/tv/${config.slug}?t=${Date.now()}`);
            if (!res.ok) throw new Error('API Error');
            const data = await res.json();

            updateUI(data);

            // 2. Fetch Weather (if lat/lon exists)
            if (config.lat && config.lon) {
                fetchWeather();
            }

        } catch (err) {
            console.error('TV Sync Error:', err);
        }
    }

    /**
     * Fetch Weather
     */
    async function fetchWeather() {
        try {
            const res = await fetch(`${config.apiUrl}chroma/v1/weather?lat=${config.lat}&lon=${config.lon}`);
            if (res.ok) {
                const w = await res.json();
                if (w && !w.error) updateWeatherUI(w);
            }
        } catch (e) { console.error('Weather Sync Error', e); }
    }

    /**
     * Update UI Components
     */
    function updateUI(data) {
        const c = data.content;

        // Notices
        if (els.notices) {
            const html = (c.announcements || []).map(n => `
                <div class="animate-fade-in">
                    ${(n.priority === 'high') ? '<span class="inline-block px-3 py-1 rounded-lg bg-chroma-red text-white text-xs font-extra-bold uppercase tracking-wider mb-2">Important</span>' : ''}
                    <h3 class="font-bold text-xl leading-tight mb-2 text-brand-ink">${esc(n.title)}</h3>
                    <p class="text-brand-ink/70 text-base leading-relaxed">${esc(n.body)}</p>
                </div>
            `).join('');
            if (els.notices.innerHTML !== html) els.notices.innerHTML = html;
        }

        // Today
        if (els.today) {
            if (!c.today || c.today.length === 0) {
                els.today.innerHTML = '<div class="p-6 rounded-2xl bg-brand-cream text-center opacity-60"><p class="font-medium">Have a wonderful day!</p></div>';
            } else {
                const html = c.today.map(t => `
                    <div class="flex flex-col p-4 rounded-2xl bg-white border border-chroma-blue/5 shadow-sm">
                        ${t.time ? `<span class="text-chroma-blue font-bold text-lg mb-1">${esc(t.time)}</span>` : ''}
                        <span class="font-bold text-xl text-brand-ink leading-tight">${esc(t.label)}</span>
                    </div>
                `).join('');
                if (els.today.innerHTML !== html) els.today.innerHTML = html;
            }
        }

        // Slideshow List Update (Preserve current index if possible)
        if (c.slideshow && Array.isArray(c.slideshow)) {
            // Check if changed
            if (JSON.stringify(slideImages) !== JSON.stringify(c.slideshow)) {
                slideImages = c.slideshow;
                // Reset if index out of bounds
                if (currentSlideIndex >= slideImages.length) currentSlideIndex = 0;
            }
        }

        // Star Educator (EOM)
        if (els.eom && c.eom && c.eom.name) {
            els.eom.style.display = 'flex';
            const html = `
                <div class="h-full w-48 shrink-0 rounded-[2rem] overflow-hidden relative shadow-inner">
                    ${c.eom.photo_url ? `<img src="${esc(c.eom.photo_url)}" class="w-full h-full object-cover">` : '<div class="w-full h-full bg-chroma-blue flex items-center justify-center text-white text-5xl"><i class="fa-solid fa-user"></i></div>'}
                </div>
                <div class="flex-1 py-2 pr-6 z-10">
                    <div class="flex flex-col justify-center h-full">
                        <h2 class="font-serif text-4xl font-bold text-brand-ink mb-1">${esc(c.eom.name)}</h2>
                        <p class="text-chroma-blue font-bold uppercase tracking-widest text-xs mb-3">
                            ${esc(c.eom.role || 'Educator')} • ${esc(c.eom.classroom || 'Classroom')}
                        </p>
                        <p class="text-brand-ink/60 text-lg leading-snug line-clamp-2 italic">"${esc(c.eom.blurb || '')}"</p>
                    </div>
                </div>
                <div class="absolute top-6 right-6 text-chroma-yellow text-5xl opacity-40"><i class="fa-solid fa-certificate"></i></div>
            `;
            if (els.eom.innerHTML !== html) els.eom.innerHTML = html;
        } else if (els.eom) {
            els.eom.style.display = 'none';
        }

        // Newsletter
        if (els.newsletter && c.newsletter && c.newsletter.title) {
            els.newsletter.style.display = 'flex';
            const qrSrc = `https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${encodeURIComponent(c.newsletter.url || '')}`;
            const html = `
                <div class="bg-[#F8EEEB] rounded-[2rem] p-6 shadow-card flex-1 flex flex-col h-full">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-xl bg-white/80 flex items-center justify-center text-chroma-red text-xl shadow-sm"><i class="fa-regular fa-newspaper"></i></div>
                        <h2 class="font-serif text-2xl font-bold text-chroma-red">Newsletter</h2>
                    </div>
                    <div class="flex-1 flex flex-col gap-4">
                        <div>
                            <h3 class="font-bold text-xl text-brand-ink mb-1">${esc(c.newsletter.title)}</h3>
                            <p class="text-brand-ink/60 text-sm leading-relaxed line-clamp-3">${esc(c.newsletter.body || '')}</p>
                        </div>
                        ${c.newsletter.url ? `
                        <div class="bg-white rounded-2xl p-3 flex items-center gap-3 shadow-sm mt-auto">
                            <div class="bg-brand-ink p-1 rounded-lg shrink-0"><img src="${qrSrc}" class="w-12 h-12"></div>
                            <div class="leading-tight"><p class="font-bold text-base text-brand-ink">Read Full Issue</p><p class="text-xs text-brand-ink/50">Scan with phone</p></div>
                        </div>` : ''}
                    </div>
                </div>
            `;
            if (els.newsletter.innerHTML !== html) els.newsletter.innerHTML = html;
        } else if (els.newsletter) {
            els.newsletter.style.display = 'none';
        }

        // Chroma Cares (Local with Global fallback)
        const cares = (c.chroma_cares && c.chroma_cares.title) ? c.chroma_cares : (data.global ? data.global.chroma_cares : null);
        if (els.cares && cares && cares.title) {
            els.cares.style.display = 'flex';
            const html = `
                <div class="bg-[#E6EFEC] rounded-[2rem] p-6 shadow-card flex-1 relative overflow-hidden flex flex-col justify-between">
                    <div class="absolute top-4 right-4 text-8xl text-chroma-green opacity-[0.08] rotate-12"><i class="fa-solid fa-heart"></i></div>
                    <div class="relative z-10">
                        <span class="text-chroma-green font-bold uppercase tracking-widest text-xs mb-2 block">Chroma Cares</span>
                        <h2 class="font-serif text-3xl font-bold text-brand-ink mb-2 leading-tight">${esc(cares.title)}</h2>
                        <p class="text-brand-ink/60 text-sm leading-relaxed">${esc(cares.body || '')}</p>
                    </div>
                </div>
            `;
            if (els.cares.innerHTML !== html) els.cares.innerHTML = html;
        } else if (els.cares) {
            els.cares.style.display = 'none';
        }

        // Celebrations (Matches logic above, just ensuring cells are filtered)
        if (els.celebrations && c.celebrations && c.celebrations.length > 0) {
            els.celebrations.style.display = 'flex';
            const cells = c.celebrations.filter(v => !!v).map(v => `<p>${esc(v)}</p>`).join('');
            const html = `
                <div class="bg-white rounded-[2rem] p-6 shadow-card border border-chroma-blue/10 flex-1 flex flex-col justify-center text-center relative overflow-hidden">
                    <div class="flex items-center justify-center gap-3 mb-2">
                        <i class="fa-solid fa-cake-candles text-2xl text-transparent bg-clip-text bg-gradient-to-r from-purple-400 to-pink-600"></i>
                        <h3 class="font-serif text-2xl font-bold text-brand-ink">Celebrations</h3>
                    </div>
                    <div class="space-y-1 relative z-10 text-brand-ink/60 text-lg">${cells}</div>
                </div>
            `;
            if (els.celebrations.innerHTML !== html) els.celebrations.innerHTML = html;
        } else if (els.celebrations) {
            els.celebrations.style.display = 'none';
        }

        // Global Alert
        const alert = data.global ? data.global.alert : null;
        if (els.alert && alert && alert.enabled && alert.message) {
            els.alert.style.display = 'block';
            const html = `
                <div class="absolute bottom-6 left-6 right-6 bg-chroma-blueDark text-white p-5 rounded-2xl shadow-2xl z-50 flex items-center gap-5 animate-fade-in border-l-[10px] border-chroma-yellow">
                    <div class="bg-chroma-yellow text-brand-ink w-10 h-10 rounded-full flex items-center justify-center font-bold shrink-0 text-xl"><i class="fa-solid fa-info"></i></div>
                    <p class="font-bold text-2xl tracking-wide">${esc(alert.message)}</p>
                </div>
            `;
            if (els.alert.innerHTML !== html) els.alert.innerHTML = html;
        } else if (els.alert) {
            els.alert.style.display = 'none';
        }
    }

    function updateWeatherUI(w) {
        if (!els.weatherContainer) return;

        // If hidden, show it
        els.weatherContainer.style.display = 'block';

        if (els.weatherTemp) els.weatherTemp.textContent = w.temp + '°';
        if (els.weatherDesc) els.weatherDesc.textContent = w.description;
        if (els.weatherIcon) {
            els.weatherIcon.className = `fa-solid ${w.code < 2 ? 'fa-sun' : 'fa-cloud-sun'} text-5xl text-chroma-yellow`;
        }
    }

    /**
     * Slideshow Logic
     */
    function startSlideshow() {
        if (!els.slideshowImg) return;

        setInterval(() => {
            if (slideImages.length === 0) return;

            // Increment
            currentSlideIndex = (currentSlideIndex + 1) % slideImages.length;
            const nextSrc = slideImages[currentSlideIndex];

            // Transition
            els.slideshowImg.style.opacity = 0;
            setTimeout(() => {
                els.slideshowImg.src = nextSrc;
                els.slideshowImg.style.opacity = 1;
            }, 500);

        }, SLIDE_INTERVAL);
    }

    function updateClock() {
        const now = new Date();
        if (els.clock) els.clock.textContent = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }).replace(' AM', '').replace(' PM', '');
        if (els.ampm) els.ampm.textContent = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }).slice(-2);
        if (els.date) els.date.textContent = now.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' });
    }

    // HTML Escape Helper
    function esc(unsafe) {
        if (!unsafe) return '';
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    init();
});
