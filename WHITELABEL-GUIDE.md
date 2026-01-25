# Whitelabeling & Tenant Onboarding Guide

**Platform Version:** 1.0.1  
**Target:** Senior Engineers / DevOps

This guide outlines the exhaustive process for cloning the "Chroma Preschool Platform" for a new tenant (e.g., "Acme Academy"). Following these steps ensures a clean separation of brand identity while maintaining the integrity of the underlying engine.

---

## Phase 1: Codebase Preparation

### 1.1 Folder Renaming
Rename the primary directories to match the new tenant's slug.
*   **Theme:** `chroma-excellence-theme` -> `acme-academy-theme`
*   **Plugins:** 
    *   `chroma-parent-portal` -> `acme-parent-portal`
    *   `chroma-school-dashboard` -> `acme-school-dashboard`
    *   `chroma-seo-pro` -> `acme-seo-pro`

### 1.2 Global Search & Replace (Namespace)
Perform a case-sensitive search and replace across the entire repository:

| Pattern Type | Original | Replacement | Example |
| :--- | :--- | :--- | :--- |
| **PHP Prefix** | `chroma_` | `acme_` | `chroma_get_meta` -> `acme_get_meta` |
| **PHP Constant** | `CHROMA_` | `ACME_` | `CHROMA_VERSION` -> `ACME_VERSION` |
| **CSS Prefix** | `chroma-` | `acme-` | `.chroma-logo` -> `.acme-logo` |
| **Text Domain** | `chroma-excellence` | `acme-academy` | `__('Text', 'chroma-excellence')` |
| **Database CPT** | `chroma_school` | `acme_school` | (Optionally rename meta keys if starting fresh) |

> [!WARNING]
> Be careful with common words. Only replace prefixes like `chroma_` or `CHROMA_`. Do not replace "chrome" in user-agent checks or system files.

---

## Phase 2: Visual Branding (Theme)

### 2.1 Design Tokens (`tailwind.config.js`)
Update the theme configuration to reflect the brand's color palette and typography.

1.  **Colors:** Modify the `colors.chroma` object.
    ```javascript
    colors: {
        brand: { ink: '#[NEW_INK_COLOR]', cream: '#[NEW_CREAM_COLOR]' },
        acme: { // was chroma
            red: '#[BRAND_PRIMARY]',
            blue: '#[BRAND_SECONDARY]',
            green: '#[BRAND_ACCENT]',
            // ... etc
        }
    }
    ```
2.  **Typography:** Update the `fontFamily` stack.
    *   Replace `.woff2` files in `assets/webfonts/`.
    *   Update `header.php` `<link rel="preload">` tags to match new filenames.

### 2.2 Asset Replacement (`assets/images/`)
Replace the following critical assets. Maintain the exact dimensions and WebP format for performance.

*   `logo_chromacropped_70x70.webp`: Header Logo (Mobile/Desktop).
*   `logo_chromacropped_140x140.webp`: Retina Header Logo.
*   `favicon.png`: Standard 32x32.
*   `logo_icon_*.webp`: Used in footer and loading states.

### 2.3 Critical CSS Update
Inline styles in `inc/critical-css.php` may contain hardcoded HEX codes for the initial paint. Update these manually to prevent a "flash of old brand colors".

---

## Phase 3: Plugin Configuration

### 3.1 Parent Portal Rebranding
The Parent Portal is a React app. Its branding is controlled via:
1.  **Global Object:** `chromaPortalSettings` in `chroma-parent-portal.php`.
    *   Update `logoUrl` to point to the new branded logo.
2.  **Styles:** Rebuild the React app (`npm run build`) after updating colors in the React source's own CSS/Tailwind config (if applicable).

### 3.2 School Dashboard (TV System)
*   **Google Auth:** Every tenant needs their own **Google Cloud Console Project**.
    *   **OAuth Scopes:** Needs `openid`, `email`, and `profile`.
    *   **Redirect URI:** Must set to `https://[tenant-site].com/wp-admin/edit.php?post_type=chroma_school&page=chroma_school_settings`.
    *   Update the `GOOGLE_CLIENT_ID` in **Global Settings > API Configuration**.
*   **Weather:** If using the dashboard, set a per-tenant `OPENWEATHER_API_KEY` in the School Dashboard settings or as a constant in `wp-config.php`.

### 3.3 Multilingual / I18n Setup
The platform uses a custom translation layer (often paired with Polylang/WPML).
1.  **Pot Files:** Update the `.pot` file in `languages/` with the new text domain.
2.  **Meta Translation:** The function `acme_get_translated_meta()` (renamed from `chroma_`) looks for meta keys with `_es` suffixes.
    *   Example: `location_description` (English) and `location_description_es` (Spanish).
3.  **Language Switcher:** Ensure `chroma_render_language_switcher()` is called in the header to allow users to toggle locales.

---

## Phase 4: Database & Integration

### 4.1 Content Structure
1.  **Create Pages:**
    *   `Locations` (Template: `page-locations.php`)
    *   `Programs` (Template: `page-programs.php`)
    *   `Parent Portal` (Add shortcode `[acme_parent_portal]`)
2.  **Assign Menus:**
    *   Register `primary-nav`, `mobile-nav`, and `footer-nav` in the customized menu sync.

### 4.2 SEO & Social
*   **Schema:** Update the Organization Name and Social URLs in `inc/general-seo-meta.php`.
*   **Sitemap:** Ensure the monthly cron `acme_monthly_seo_event` is firing to ping search engines.

---

## Phase 5: Deployment & QA

### Pre-Launch Build
Run these commands in the theme directory before syncing to production:
```bash
npm install
npm run build:css
```

### 10-Point QA Checklist
1.  [ ] **Header/Footer:** High-res logo appears correctly; no "broken image" alt text.
2.  [ ] **Colors:** Spot-check buttons and hovers for old "Chroma Red".
3.  [ ] **Typography:** Correct fonts are loading (Check Network tab in DevTools).
4.  [ ] **Locations:** Search filter works (Vanilla JS logic test).
5.  [ ] **Parent Portal:** React app mounts; PIN login doesn't return 404 (API check).
6.  [ ] **TV Dash:** `/tv/campus-name` renders weather and slideshow.
7.  [ ] **Social:** Facebook/Instagram links in footer point to the new brand.
8.  [ ] **SEO:** Page titles end with `| Acme Academy` (not Chroma).
9.  [ ] **Performance:** GTMetrix/PageSpeed score for LCP is < 2.5s.
10. [ ] **Forms:** "Book a Tour" CTA points to the correct local school or global contact page.

---

## Troubleshooting New Tenants

*   **Old logo persists:** Likely cached by LiteSpeed or Cloudflare. Clear "All Cache".
*   **Portal shows PHP "Waiting for React":** The directory name in `wp_enqueue_script` may still be pointing to the `chroma-` path. Check `acme-parent-portal.php`.
*   **Meta boxes missing:** Ensure the file names in `inc/` match the `require_once` lines in `functions.php` after your renaming binge.
