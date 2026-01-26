# Full Performance & Scalability Roadmap

This plan moves beyond "quick fixes" to implement a robust, enterprise-grade architecture for the Chroma Preschool Platform. Our goal is to ensure the system can support 100+ campuses and multiple tenants with zero performance degradation.

## 1. DATA ARCHITECTURE (Full Query Elimination)

### 1.1 Implement a "Service Layer" for Data
- **Objective**: Move all logic out of template files (N+1 query patterns).
- **Action**: Create a `Chroma_Data_Service` class that pre-fetches all Location, Program, and Meta data into a single, high-speed memory object at the start of the request.
- **Full Win**: Templates will 0 database queries; they will simply pull from the pre-warmed memory object.

### 1.2 Persistent Object Caching (Redis)
- **Objective**: Move away from the slow `wp_options` table for transients.
- **Action**: Formalize Redis as a requirement for all tenants. Update `chroma_cached_query` to utilize a tiered caching strategy (In-memory > Redis > DB).

## 2. BRANDING & MULTI-TENANCY (Dynamic White-labeling)

### 2.1 The "Magic Switch" Branding Engine
- **Objective**: Eliminate manual search-and-replace for new tenants.
- **Action**: Implement a `theme-settings.json` that drives **CSS Variables** (`--brand-primary`, `--brand-secondary`). 
- **Full Win**: Launching a new brand will require zero code changes—just updating a JSON file. This eliminates "brand leakage" and reduces deployment time from hours to seconds.

### 2.2 Dynamic Critical CSS Generation
- **Objective**: Stop hardcoding brand colors in `inc/critical-css.php`.
- **Action**: Update the critical CSS generator to pull from the new Branding Engine. Ensure initial paint matches the specific tenant's brand perfectly without manual edits.

## 3. ENGINE CLEANUP & MODERNIZATION

### 3.1 Legacy Code Decommissioning
- **Objective**: Remove "dead weight" from the Request Lifecycle.
- **Action**: DELETE `inc/seo-engine.php` (56KB) and move the necessary HTTP header signals into the modern `Chroma_SEO_Pro` plugin. 

### 3.2 Modular Plugin Bootstrap
- **Objective**: Stop loading heavy React back-ends on public-facing pages.
- **Action**: Refactor `chroma-school-dashboard` and `chroma-parent-portal` to use a singleton pattern that ONLY registers hooks if specific conditions are met (e.g., `is_admin()` or the specific CPT page is active).

## 4. FRONTEND PERFORMANCE (CWV Excellence)

### 4.1 Global Third-Party Interceptor
- **Objective**: Reclaim the main thread from LeadConnector and other heavy trackers.
- **Action**: Build a custom "Third-Party Proxy" in `main.js` that intercepts all external script injections and only fires them after the `LCP` event is confirmed or the user interacts with the page.
- **Full Win**: A perfect 100/100 performance score on mobile by delaying non-essential logic until the "Visual Paint" is finished.
