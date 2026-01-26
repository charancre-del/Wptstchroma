# Performance Audit Report

## 1. REQUEST LIFECYCLE & TTFB AUDIT
### 1.1 Theme Bootstrap Analysis
- **Finding**: `functions.php` unconditionally includes ~40 files in the `inc/` directory.
- **Impact**: Increased memory usage and bootstrap time for every request (including REST API and AJAX).
- **Critical Files**: `inc/about-page-meta.php` (56KB), `inc/curriculum-page-meta.php` (43KB), `inc/parents-page-meta.php` (48KB).
- **Code Reference**: [functions.php:L127-L201](file:///c:/Users/chara/Documents/wptheme/Wptstchroma/chroma-excellence-theme/functions.php#L127-L201)
- **Recommendation**: Wrap administrative meta box includes in `is_admin()`.

### 1.2 Plugin Bootstrap
- **Finding**: `chroma-school-dashboard` and `chroma-parent-portal` load all logic on every request.
- **Recommendation**: Implement conditional loading for specific page types or REST endpoints.

## 2. DATABASE & QUERY AUDIT
### 2.1 N+1 Query Patterns
- **Finding**: In `page-locations.php`, `wp_get_post_terms` is called inside the loop for every location.
- **Code Reference**: [page-locations.php:L132](file:///c:/Users/chara/Documents/wptheme/Wptstchroma/chroma-excellence-theme/page-locations.php#L132)
- **Impact**: For 20 campuses, this adds 20 extra taxonomy queries.
- **Fix**: Use `update_post_term_cache` or pass `_prime_term_cache` to the main query if possible.

- **Finding**: Multiple `get_post_meta` and `chroma_get_location_fields` calls in loops.
- **Impact**: While meta is cached by post ID, the sheer volume of calls adds overhead.

### 2.2 Query Caching Strategy
- **Success**: `chroma_cached_query` in `functions.php` is used effectively in `page-programs.php` and `inc/acf-homepage.php`.
- **Note**: The manual transient cleanup in `functions.php:L105` is efficient but relies on `LIKE` queries in `wp_options`.

## 3. ACF & DATA MODEL PERFORMANCE
- **Finding**: The site does NOT use the ACF plugin. It uses native WordPress meta but organizes files with "ACF" naming conventions.
- **Problem**: Large JSON payloads are stored in meta fields (e.g., `home_stats_json`, `home_prismpath_cards_json`).
- **Impact**: `json_decode` overhead on every front-page load.
- **Fix**: Cache the decoded array in a runtime static variable or object cache.

## 5. FRONTEND ASSET AUDIT
### 5.1 CSS Delivery
- **Success**: Critical CSS is implemented in `inc/critical-css.php` and inlined in `header.php`.
- **Finding**: Main CSS is deferred via `media='print'` trick in `inc/enqueue.php`.
- **Recommendation**: Ensure Critical CSS covers all major page types to prevent FOUC.

### 5.2 JavaScript Execution
- **Observation**: jQuery is moved to footer for non-logged-in users.
- **Finding**: LeadConnector (GoHighLevel) scripts are dequeued in `functions.php:L427` to prevent render-blocking. They are likely injected manually via the `[chroma_tour_form]` shortcode.
- **Recommendation**: Ensure the manual injection uses `loading="lazy"` or waits for user interaction (e.g., clicking the "Book Tour" button) to improve TBT (Total Blocking Time).

## 4. TEMPLATE & RENDER AUDIT
### 4.1 Post Meta Complexity
- **Finding**: Templates like `single-location.php` retrieve ~20-30 meta fields per page load.
- **Code Reference**: [single-location.php:L12-L82](file:///c:/Users/chara/Documents/wptheme/Wptstchroma/chroma-excellence-theme/single-location.php#L12-L82)
- **Impact**: While cached, the repeated calls to `chroma_get_translated_meta` (which does logic checks) add to execution time.

## 6. CORE WEB VITALS (CWV) AUDIT
### 6.1 LCP (Largest Contentful Paint)
- **Status**: Good. Hero images use `fetchpriority="high"` and `loading="eager"`.
- **Note**: The hero carousel in `single-location.php` is well-implemented with correct hints.

### 6.2 CLS (Cumulative Layout Shift)
- **Success**: Critical CSS includes font-fallback metric overrides (size-adjust) to prevent shifts during font swap.
- **Risk**: Carousel height might shift if images are not exactly the same aspect ratio (addressed in CSS but needs monitoring).

## 10. MULTI-TENANT RISK ANALYSIS
### 10.1 Whitelabeling Scalability
- **Finding**: The onboarding process is manual and high-risk (rename files, search-and-replace strings).
- **Risk**: Prefix collision or missed strings in hardcoded files (like `inc/critical-css.php`).
- **Impact**: High maintenance cost and high probability of "brand leakage" (old brand colors or names appearing).
- **Recommendation**: Implement a `theme-settings.json` or environment variables to drive brand colors and names dynamically.

## 8. PLUGIN PERFORMANCE BREAKDOWN
| Plugin | Weight | Primary Bottleneck | Optimization |
| :--- | :--- | :--- | :--- |
| `QA-Report-App` | Heavy | 164+ files, significant React build overhead | Conditional loading via `is_page()` or API detection |
| `chroma-parent-portal` | Medium-Heavy | Force cache clearing (timestamped versioning) | Use `filemtime` for versioning instead of `time()` |
| `chroma-school-dashboard` | Medium | Google Auth and external API calls (Weather) | Cache API responses in Transients (verified as implemented) |
| `chroma-seo-pro` | Light | Minimal overhead | N/A |

## 9. ADMIN & EDITOR PERFORMANCE
### 9.1 AI Auto-Fill Overhead
- **Finding**: `inc/general-seo-meta.php` implements an AJAX-based AI auto-fill for SEO metadata.
- **Problem**: While asynchronous, large-scale usage (e.g., bulk editing) could hit internal API rate limits.
- **Recommendation**: Ensure the AI generation is throttled and verify the endpoint security (nonces are correctly verified).

### 9.2 Customizer Complexity
- **Finding**: Theme includes extensive Customizer controls (`inc/customizer-*`).
- **Impact**: High memory usage when the Customizer is active.
- **Note**: Dequeueing these on the Parent Portal (line 35 of `chroma-parent-portal.php`) is a good defensive measure.

### 9.3 Legacy Code Overhead
- **Finding**: `inc/seo-engine.php` (56KB) is largely deprecated by the "Chroma SEO Pro" plugin but still included in every request. 
- **Impact**: Unnecessary code evaluation.
- **Recommendation**: Fully migrate remaining HTTP header logic to the plugin and remove the legacy file.
