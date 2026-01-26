# Performance Fixes Plan & Baseline

## PHASE 0 — Baseline & Instrumentation

### Theme Analysis
- **Active Theme**: `chroma-excellence-theme`
- **Entry Points**: `functions.php`, `header.php`, `inc/setup.php`, `inc/enqueue.php`
- **Asset Pipeline**: Tailwind CSS (PostCSS) with manual `npm run build`. No Vite/Webpack detected for JS (mostly modular Vanilla JS).
- **Enqueue Strategy**: Scripts are mostly enqueued in `inc/enqueue.php`. CSS has a Critical CSS component in `inc/critical-css.php`.

### Profiling Toggle
- **Constant**: `CHROMA_PERF_PROFILE` (to be added to `wp-config.php` or `functions.php`).
- **Feature**: Appends a hidden HTML comment at the end of the page with total queries and execution time.

### Baseline Metrics (Estimated)
- **Home Page Queries**: ~85-110 queries (due to complex customizer pulls and repeated meta calls).
- **TTFB (Uncached)**: ~450ms - 650ms.
- **Total JS Bytes**: ~120KB (including jQuery and LeadConnector).
- **Total CSS Bytes**: ~85KB (including Tailwind bundle).
- **CLS Risk**: Moderate (Carousels and dynamic meta fields).
- **LCP Risk**: Low (already has eager/fetchpriority hints).

---

## PHASE 1 — Server/Backend Improvements
*To be populated during implementation.*
