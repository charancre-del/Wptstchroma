# Chroma Platform System Blueprint & Developer README

**Version:** 1.0.2 (Plugin Deep Dive)  
**Last Updated:** 2025-05-20  
**Target Audience:** DevOps, Lead Developers, System Architects

---

## 1. System Overview

This repository houses the **Chroma Early Learning Platform**, a specialized WordPress distribution that serves as the central hub for the company's digital presence. unlike a standard "theme + plugins" site, this is a **Platform Distribution** where the theme and plugins are tightly coupled to deliver specific business logic.

### Component Map

```ascii
[Request Lifecycle]
    |
    v
[WordPress Core]
    |
    +--- [Theme: chroma-excellence-theme] (The "Core Platform")
    |       |-- Controls 95% of frontend rendering
    |       |-- Defines Custom Post Types (Locations, Programs)
    |       |-- Manages SEO, Schema, and Performance
    |       +-- Tailwind CSS Build Pipeline
    |
    +--- [Plugin: chroma-parent-portal] (The "App")
    |       |-- Headless React SPA (embedded via shortcode)
    |       +-- Private API for PIN Authentication
    |
    +--- [Plugin: chroma-school-dashboard] (The "Hardware Controller")
            |-- Director Configuration (CPT: chroma_school)
            +-- Digital Signage (TV) Rendering Logic
```

---

## 2. Core Theme Deep Dive (`/chroma-excellence-theme`)

This is the "Source of Truth" for the platform. It is a **hybrid theme** that combines standard WordPress templating with modern build tools.

*   **Type:** Classic Theme (PHP-based) with Tailwind CSS.
*   **Engine:** Vanilla PHP 8+ (No generic page builders like Elementor or Divi).
*   **CSS Engine:** Tailwind CSS v3 (JIT Mode) via PostCSS.
*   **Data Strategy:** Native PHP Meta Boxes (No ACF dependency for core pages).

### Directory Structure & Module System
*   **`functions.php`**: The bootstrapper. Loads the "Service Container" files from `inc/`.
*   **`inc/`**: The brain of the theme.
    *   **Post Types:** `cpt-locations.php`, `cpt-programs.php`, `cpt-cities.php`.
    *   **Data Models:** `*-page-meta.php` files define fields for specific templates.
    *   **Performance:** `critical-css.php` (inlines LCP CSS), `enqueue.php`.
    *   **Cron Jobs:** `monthly-seo-cron.php`.
*   **`page-*.php`**: specialized templates.
    *   `page-locations.php`: Archive with JS search/filter.
    *   `page-curriculum.php`: Complex, multi-section layout.
*   **`assets/`**:
    *   `css/input.css`: The Tailwind source file.
    *   `js/`: Vanilla JS modules.

### Request Lifecycle (Example: `/locations/`)
1.  **Routing:** WP Core selects `page-locations.php`.
2.  **Bootstrapping:** `functions.php` registers `location` CPT via `inc/cpt-locations.php`.
3.  **Data Fetching:** Template queries `location` posts and uses `chroma_get_location_fields()` helper.
4.  **Rendering:** Outputs HTML with Tailwind classes. JS filters handle interactivity.
5.  **Styles:** `inc/enqueue.php` loads compiled `assets/css/main.css`.

---

## 3. Plugin Deep Dive: Chroma Parent Portal

**Path:** `plugins/chroma-parent-portal`  
**Purpose:** Secure, "Headless" React App for enrolled families.

### Architecture
*   **Front-End:** React 18 SPA (Single Page Application).
*   **Mount Point:** A WordPress page containing the shortcode `[chroma_parent_portal]`.
*   **Asset Loading:** `chroma-parent-portal.php` detects the shortcode and **unloads** theme assets (header, footer, styles) to provide a blank canvas for the React app.

### Data Model (Custom Post Types)
Defined in `includes/class-cpt-registrar.php`. All represent downloadable or viewable content.

| Post Type | Purpose | Taxonomy Assoc. |
| :--- | :--- | :--- |
| `cp_lesson_plan` | Weekly/Monthly PDF guides. | `portal_year`, `portal_month` |
| `cp_meal_plan` | Lunch menus. | `portal_year`, `portal_quarter` |
| `cp_resource` | Policies & Handbooks. | `portal_year`, `portal_category` |
| `cp_form` | Permission slips/Forms. | `portal_year`, `portal_category` |
| `cp_announcement`| News updates (Text editor). | `portal_year`, `portal_month` |
| `cp_event` | Calendar events. | `portal_year`, `portal_month` |
| `cp_family` | **Authentication.** Title = Family Name. PIN = Meta Field. | - |

### API Endpoints (`includes/class-api-routes.php`)
Namespace: `chroma-portal/v1`

*   `POST /login`: Accepts `{ pin: "1234" }`. Validates against `cp_family` meta. Returns JWT-like token.
*   `GET /content/dashboard`: Fetches all assigned content (Lessons, Meals, etc.) for the current year.
*   `GET /years`: Lists available academic years (Taxonomy `portal_year`).
*   `GET /taxonomy/{tax}`: Helper to list terms (e.g. "January", "Q1").

---

## 4. Plugin Deep Dive: Chroma School Dashboard

**Path:** `plugins/chroma-school-dashboard`  
**Purpose:** Digital Signage (TV) controller and School Director configuration interface.

### Architecture
*   **Front-End:** PHP Template with auto-refreshing JS (`assets/js/tv-dashboard.js`).
*   **Access:** Rewrite rule `/tv/{slug}` maps to `templates/tv-dashboard.php`.
*   **Auth:** School Directors log in via Google Auth to a private admin page.

### Data Model
*   **CPT `chroma_school`**: One post per physical location. Hidden from public view.
    *   **Meta `_chroma_school_config`**: Lat, Lon, Timezone.
    *   **Meta `_chroma_school_director_email`**: Maps a Google User Email to this school.
    *   **Meta `_chroma_school_slideshow`**: List of image URLs for the TV.

### Key Logic
*   **`inc/class-api-routes.php`**:
    *   `GET /chroma/v1/tv/{slug}`: Returns JSON config for the TV JS (Weather, slideshow URLs, ticker text).
*   **`inc/class-weather.php`**: Proxy to OpenWeatherMap API (caches results in transients to save API quotas).
*   **`assets/js/tv-dashboard.js`**: Handles the infinite loop animation, slideshow transitions, and weather polling.

---

## 5. Plugin Deep Dive: Chroma SEO Pro

**Path:** `plugins/chroma-seo-pro`  
**Purpose:** Experimental / Advanced Data Layer.

### Overview
This plugin currently serves as a container for advanced datasets (`inc/class-citation-datasets.php`) that may be used for programmatic SEO or "Near Me" page generation. It appears to be in an early or auxiliary state compared to the other two core plugins.

---

## 6. Frontend Construction & Build System

The theme uses a **CSS-Only Build Step**. JavaScript is written as modern Vanilla JS and does not require a build step.

### CSS Pipeline
*   **Source:** `themes/chroma-excellence-theme/assets/css/input.css`
*   **Config:** `themes/chroma-excellence-theme/tailwind.config.js`
    *   Defines the "Design Tokens" (Colors, Fonts, Spacing).
    *   **Tenant Customization:** This is where you change the "Brand Ink" color (e.g., `#263238`) or "Brand Red" for a new client.
*   **Command:** `npm run build:css` invokes PostCSS + Tailwind CLI -> `assets/css/main.css`.

### Critical CSS
The file `inc/critical-css.php` handles inlining critical styles above the fold to ensure high Core Web Vitals (LCP/CLS).

---

## 7. How to Clone Process (White-Labeling)

To deploy this platform for a new client (e.g., "Acme Academy"), follow this exact sequence:

### Phase 1: Codebase Updates
1.  **Rename Theme Folder:** `chroma-excellence-theme` -> `acme-academy-theme`.
2.  **Update `style.css`:** Change "Theme Name" and "Text Domain".
3.  **Search & Replace:** Globally replace `chroma_` function prefixes with `acme_` (Optional, but good for namespace purity).
4.  **Update Design Tokens (`tailwind.config.js`):**
    *   Update `colors.brand.ink`, `colors.chroma.red`, etc.
    *   Update `fontFamily` stack if fonts change (ensure font files are in `assets/webfonts`).
5.  **Rebuild CSS:** Run `npm install && npm run build:css` in the theme folder.

### Phase 2: Configuration Updates
1.  **Logos:** Replace images in `assets/images/`.
    *   Critical: `logo_chromacropped_70x70.webp` (Used in Header).
    *   Critical: `favicon.png`.
2.  **Constants (`functions.php`):** Update `CHROMA_VERSION` or any contact info constants.
3.  **Default Content:**
    *   Create pages for `Locations`, `Programs`, `About`, `Contact`.
    *   Assign the correct "Template Name" to each page.

### Phase 3: Plugin Setup
1.  **Parent Portal:**
    *   Create a page `/parent-portal` with shortcode `[chroma_parent_portal]`.
    *   Create `cp_family` posts for testing pins.
2.  **TV System:**
    *   Activate `chroma-school-dashboard`.
    *   Go to **TV Dashboards** CPT and create a school.
    *   Set the **Director Email** to your Google Email to test the admin panel.

---

## 8. Troubleshooting

*   **Issue:** CSS changes aren't satisfying.
    *   **Fix:** You must run `npm run build:css` (or `dev:css` for watch mode) inside the theme folder. PHP changes are instant; CSS changes require a build.
*   **Issue:** Portal Login says "Invalid Token" immediately.
    *   **Fix:** Check `class-auth-handler.php` in the parent portal plugin. Salt/Secret constants might be missing in `wp-config.php`.
*   **Issue:** TV Weather isn't updating.
    *   **Fix:** Check `chroma-school-dashboard/inc/class-weather.php`. It requires an OpenWeatherMap API key (usually defined in `WP_DEBUG` or a constant). Also check Transients in DB (`_transient_chroma_weather_...`).
