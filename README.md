# Chroma Excellence WordPress Theme & Plugins

Complete WordPress solution for Chroma Early Learning Academy featuring custom theme, SEO engine, and lead management system.

## 📦 What's Included

### 1. **chroma-excellence-theme** (WordPress Theme)
Custom theme with:
- Hardcoded homepage defaults (override via Customizer, no ACF dependency)
- 2 Custom Post Types (Programs, Locations)
- Advanced SEO engine with schema.org markup
- Sitemap.xml and robots.txt management
- Spanish variant support (hreflang)
- City-slug logic for location URLs
- Monthly SEO cron for search engine pings
- Tailwind CSS design system
- Leaflet maps integration
- Data-attribute based modular JavaScript

### 2. **chroma-plugins** (3 WordPress Plugins)
- **chroma-tour-form** - Tour request form with lead routing
- **chroma-acquisitions-form** - Acquisitions inquiry form
- **chroma-lead-log** - Lead logging CPT for centralized tracking

## 🚀 Installation

### Step 1: Install Theme

```bash
# Upload theme to WordPress
cd wp-content/themes/
# Upload chroma-excellence-theme folder

# Install dependencies
cd chroma-excellence-theme
npm install

# Build CSS
npm run build
```

### Step 2: Install Plugins

```bash
# Upload plugins to WordPress
cd wp-content/plugins/
# Upload all 3 plugin folders from chroma-plugins/

# Activate in WordPress admin:
# 1. Chroma Lead Log (activate first)
# 2. Chroma Tour Form
# 3. Chroma Acquisitions Form
```

### Step 3: Configure Theme

1. **Activate Theme:** Appearance → Themes → Chroma Excellence
2. **Set Permalinks:** Settings → Permalinks → Post name → Save
3. **Configure Menus:** Appearance → Menus
   - Create "Primary Menu" and assign to Primary location
   - Create "Footer Menu" and assign to Footer location

> ℹ️ **ACF plugin optional:** The homepage and global helpers use hardcoded defaults and WordPress options. You can run the site without installing ACF, and no templates will break if the plugin is absent.

### Step 4: Create Content

**Programs:**
1. Add Programs (Programs → Add New)
2. Required fields: program_age_range, program_description
3. Optional: program_locations (relationship to locations)

**Locations:**
1. Add Locations (Locations → Add New)
2. Required fields:
   - location_address, location_city, location_state, location_zip
   - location_phone, location_email
   - location_latitude, location_longitude (for maps)
3. Optional: location_capacity, location_enrollment

**Homepage:**
1. Create a page called "Home"
2. Settings → Reading → Set "Home" as homepage
3. Optional: Appearance → Customize → **Chroma Homepage** to edit hero text, stats, Prismpath cards, wizard options, curriculum radar data, schedule tabs, FAQs, and the locations callout (JSON textareas provided for list-based sections).

## 📁 Theme Architecture

```
chroma-excellence-theme/
├── style.css                    # Theme header
├── functions.php                # Main loader
├── header.php / footer.php      # Layout shell
├── front-page.php               # Homepage
├── index.php                    # Fallback
├── archive-program.php          # Programs listing
├── single-program.php           # Program detail
├── single-location.php          # Location detail
├── /inc                         # Core functionality
│   ├── setup.php                # Theme setup
│   ├── enqueue.php              # Assets loading
│   ├── nav-menus.php            # Navigation with Tailwind
│   ├── cpt-programs.php         # Program CPT
│   ├── cpt-locations.php        # Location CPT
│   ├── acf-options.php          # Global helpers
│   ├── acf-homepage.php         # Home helpers
│   ├── template-tags.php        # Utility functions
│   ├── cleanup.php              # WordPress cleanup
│   ├── seo-engine.php           # Schema, sitemap, OG tags
│   ├── city-slug-logic.php      # Location URL suggestions
│   ├── spanish-variant-generator.php  # Language switching
│   └── monthly-seo-cron.php     # SEO maintenance cron
├── /template-parts              # Modular sections
│   └── /home                    # Homepage sections
├── /assets
│   ├── /css
│   │   ├── input.css            # Tailwind entry
│   │   └── main.css             # Compiled CSS
│   └── /js
│       ├── main.js              # Main JavaScript
│       └── map-layer.js         # Leaflet maps
├── /acf-json                    # Legacy ACF field groups (reference only)
├── tailwind.config.js           # Tailwind config
├── postcss.config.js            # PostCSS config
└── package.json                 # NPM dependencies
```

## 🛠️ Development

### CSS Development (Tailwind)

```bash
# Watch mode (development)
npm run dev

# Build for production
npm run build
```

### Brand Colors

```javascript
brand: {
  ink: '#263238',      // Primary text
  cream: '#FFFCF8',    // Background
  navy: '#4A6C7C',     // Dark accent
}
chroma: {
  red: '#D67D6B',
  redLight: '#F4E5E2',
  orange: '#E89654',        // NEW
  orangeLight: '#FEF0E6',   // NEW
  blue: '#4A6C7C',
  blueDark: '#2F4858',
  blueLight: '#E3E9EC',
  teal: '#4A6C7C',
  tealLight: '#E3E9EC',
  green: '#8DA399',
  greenLight: '#E3EBE8',
  yellow: '#E6BE75',
  yellowLight: '#FDF6E3',
}
```

### Font Awesome Icons

**Version:** Font Awesome 6.4.0 (Free)

The theme loads Font Awesome from CDN via `inc/enqueue.php`:

```php
wp_enqueue_style(
    'font-awesome',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
    array(),
    '6.4.0'
);
```

**Verify Icons Are Loading:**

1. **Check Browser Console** - No 404 errors for Font Awesome CSS
2. **Inspect Icon Element** - Should have classes like `fa-solid fa-heart`
3. **Test Page** - Icons should display (not show as squares/blanks)

**Common Icon Classes Used:**

```html
<!-- Solid Icons -->
<i class="fa-solid fa-heart"></i>
<i class="fa-solid fa-graduation-cap"></i>
<i class="fa-solid fa-shapes"></i>
<i class="fa-solid fa-shield-halved"></i>
<i class="fa-solid fa-apple-whole"></i>
<i class="fa-solid fa-image"></i>

<!-- Brand Icons -->
<i class="fa-brands fa-connectdevelop"></i>

<!-- Regular Icons -->
<i class="fa-regular fa-star"></i>
```

**Where Font Awesome Is Used:**
- Prismpath section (bento box cards)
- Hero section (graduation cap badge)
- About page (section icons)
- Locations (map markers)
- Hardcoded in template files (NOT in meta boxes)

**⚠️ Important: Program Icons vs Font Awesome**

**Program Icon Meta Box** uses **EMOJIS** (not Font Awesome):
```
✅ Correct:  👶 (paste emoji)
❌ Wrong:    fa-solid fa-baby (don't use Font Awesome classes)
```

**Font Awesome icons** are hardcoded in template files:
```php
<i class="fa-solid fa-heart"></i>  <!-- In template files only -->
```

**Quick Reference:**
- **In WordPress Admin meta boxes** → Use emojis (👶, 🎨, 🎓)
- **In theme template files** → Use Font Awesome (`fa-solid fa-heart`)

**Troubleshooting:**

If icons don't show up (showing as blank squares or missing):

**Step 1: Verify Font Awesome is Loading**
1. **Open browser DevTools** (F12 or Right-click → Inspect)
2. **Go to Console tab** - Check for Font Awesome 404 errors
3. **Go to Network tab** - Refresh page, filter "CSS", look for `font-awesome`
4. **Check it loaded** - Should show `all.min.css` with status 200

**Step 2: Check Icon HTML**
1. **Right-click the missing icon** → Inspect Element
2. **Verify the HTML** - Should look like: `<i class="fa-solid fa-heart"></i>`
3. **Check computed styles** - Font family should be "Font Awesome 6 Free"

**Step 3: Common Fixes**

**Issue: Icons show as squares (□)**
- **Cause**: Font Awesome CSS not loaded
- **Fix**: Clear cache (Ctrl+Shift+R), check CDN is accessible
- **Test CDN**: Visit `https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css`

**Issue: Wrong icon classes in Customizer**
- **Cause**: JSON uses old Font Awesome 4 syntax (fa fa-heart)
- **Fix**: Update icon classes to Font Awesome 6 syntax
- **Old**: `"fa fa-heart"` ❌
- **New**: `"fa-solid fa-heart"` ✅

**Step 4: Fix Prismpath Icons in Customizer**

Icons in the Prismpath bento box section can be customized via **Appearance → Customize → Homepage → Prismpath Section → Cards JSON**.

**JSON Format for Prismpath Cards:**

**Simple Cards** (Card 3, Card 4):
```json
{
  "heading": "Wholesome Fuel",
  "text": "Organic, balanced meals...",
  "icon": "fa-solid fa-apple-whole"
}
```

**Complex Cards** (Card 1, Card 2):
```json
{
  "badge": "Proprietary Model",
  "heading": "The Prismpath™ Curriculum",
  "text": "Just as a prism refracts...",
  "icon_bg": "fa-solid fa-shapes",
  "icon_badge": "fa-brands fa-connectdevelop",
  "icon_check": "fa-solid fa-check-circle"
}
```

**Icon Fields:**
- `icon` - Single icon for simple cards
- `icon_bg` - Background decorative icon (large, faded)
- `icon_badge` - Badge icon (small, in colored box)
- `icon_check` - Checkmark icon for readiness section

**Default Icon Classes:**
- **Card 1** (Blue): `icon_bg: fa-solid fa-shapes`, `icon_badge: fa-brands fa-connectdevelop`, `icon_check: fa-solid fa-check-circle`
- **Card 2** (Red): `icon_bg: fa-solid fa-heart`, `icon_badge: fa-solid fa-user-check`
- **Card 3** (Green): `icon: fa-solid fa-apple-whole`
- **Card 4** (White): `icon: fa-solid fa-shield-halved`

**IMPORTANT**: Always use Font Awesome 6 syntax:
- ✅ Correct: `fa-solid fa-heart`, `fa-brands fa-connectdevelop`
- ❌ Wrong: `fa fa-heart`, `fa fa-apple`

**Step 5: WordPress Admin Cache**
1. **Disable caching plugins** temporarily
2. **Clear WordPress cache** (if using W3 Total Cache, WP Rocket, etc.)
3. **Regenerate CSS** - Go to theme folder, run `npm run build`
4. **Hard refresh** browser (Ctrl+Shift+R)

**Self-Hosting (Optional):**

To avoid CDN dependency:

```bash
# Download Font Awesome
cd chroma-excellence-theme/assets
mkdir fonts
# Download and extract Font Awesome 6.4.0
# Update enqueue.php to use local path
```

### ACF Field Groups

Legacy ACF JSON files remain for reference, but the theme no longer requires the plugin.

### Runtime without ACF

- No templates or helpers call `get_field()` or other ACF PHP APIs. All homepage data and global defaults are hardcoded or stored in standard WordPress options.
- The legacy `inc/acf-*.php` helpers rely only on WordPress functions, so they load safely even if the Advanced Custom Fields plugin is missing or deactivated.
- You can confirm the absence of ACF function calls with:

```bash
rg "get_field" chroma-excellence-theme chroma-plugins
```

## 🔍 SEO Features

- **Automatic Schema.org markup:**
  - Organization (homepage)
  - ChildCare + LocalBusiness (locations)
  - Service (programs)

- **Sitemap:** `https://yourdomain.com/?sitemap=xml`

- **Robots.txt:** Automatically includes sitemap URL

- **Hreflang:** Set `alternate_url_en` and `alternate_url_es` post meta fields (ACF optional)

- **Monthly cron:** Automatically pings Google & Bing with sitemap

## 📝 Using Forms

### Tour Form
Add to any page: `[chroma_tour_form]`

- Routes to location email if location selected
- Falls back to global_tour_email
- Logs to Lead Log CPT

### Acquisitions Form
Add to acquisitions page: `[chroma_acquisition_form]`

- Sends to acquisitions@chromaela.com
- Logs to Lead Log CPT

### Lead Log
View all leads: **Lead Log** menu in WordPress admin

## 🌍 Spanish Support

1. Create Spanish version of page/post
2. Add post meta fields (ACF optional):
   - `alternate_url_en` - English URL
   - `alternate_url_es` - Spanish URL
3. Theme automatically adds hreflang tags

Display language switcher:
```php
<?php chroma_render_language_switcher(); ?>
```

## 📍 Location URL Management

For each location, the theme suggests SEO-friendly slugs:
- Pattern: `service-areas-{city}-{state}`
- Example: `service-areas-johns-creek-ga`

Manually update permalink to preserve existing URLs.

## ✨ Recent Improvements (Nov 2025)

### Quality Fixes Applied
**Theme Score:** 98/100 ⭐⭐⭐⭐⭐ (Excellent - Production Ready)

All code quality issues resolved:
- ✅ **Stats colors** - Now cycle through red → yellow → blue → green
- ✅ **Hero section** - 4-level fallback system (featured image → customizer → video → gradient)
- ✅ **Schedule tabs** - Color-coded per program (timeline, badges, titles)
- ✅ **Locations grid** - 4 columns with region-specific hover colors
- ✅ **Orange color** - Added to Tailwind palette

See audit reports: `AUDIT-2-*.md` and `COMPREHENSIVE-FIX-PLAN.md`

### 📸 Schedule Tab Images Setup

**Manual Step Required (10-15 minutes):**

Schedule tabs need classroom photos. Follow these steps:

1. **Get Photos** - Download from provided Unsplash links or use your own
2. **Upload** - WordPress Admin → Media → Add New
3. **Set Featured Images** - Programs → Edit each program → Set Featured Image
4. **Verify** - Check homepage schedule tabs

**Detailed Guide:** See `QUICK-FIX-SCHEDULE-IMAGES.md` for step-by-step instructions with troubleshooting.

**Programs Needing Images:**
- Infant Care (6 weeks-12 months)
- Toddler Program (1-2 years)
- Preschool (2-3 years)
- Pre-K Prep (3 years)
- GA Pre-K (4 years)
- After-School (5-12 years)

## 🔧 Deployment Checklist

- [ ] Install theme + plugins
- [ ] Install ACF Pro (optional)
- [ ] Configure Chroma Settings (global options)
- [ ] Set up menus (Primary + Footer)
- [ ] Set permalinks to "Post name"
- [ ] Add Programs
- [ ] **Set featured images on Programs** (for schedule tabs) ⭐ NEW
- [ ] Add Locations with lat/lng for maps
- [ ] Review homepage defaults (hardcoded in theme)
- [ ] Run `npm run build` for production CSS
- [ ] Test tour form submission
- [ ] Verify sitemap: `/?sitemap=xml`
- [ ] Check schema markup (Google Rich Results Test)

## 📞 Support

- GitHub: https://github.com/charancre-del/Wptstchroma
- Internal development team

## 📄 License

Proprietary - All rights reserved © 2025 Chroma Early Learning Academy

## Agent API (Secure Automation Guide)

This repository now includes a dedicated plugin: `plugins/chroma-agent-api`.

It provides machine-to-machine API-key access for programmatic auditing and updates of:
- posts/pages/custom post types
- theme options and theme mods
- SEO plugin options and per-post SEO meta
- media listing/upload/attachment

### Security Guarantees

- API-key auth only (no cookies required for automation)
- HTTPS-only enforcement
- key hashing with `password_hash` (no plaintext storage)
- scoped permissions (least privilege)
- per-key rate limiting
- optional request signing (HMAC)
- per-write audit logs with before/after/diff
- dry-run support on mutating routes
- rollback support (post revisions and option/theme snapshots)

### Install and Enable

1. Ensure plugin exists at `wp-content/plugins/chroma-agent-api`.
2. Activate plugin in WP Admin, or with WP-CLI:

```bash
wp plugin activate chroma-agent-api
```

3. Plugin activation creates DB tables:
- `wp_chroma_api_keys`
- `wp_chroma_api_audit_log`
- `wp_chroma_api_option_snapshots`

4. Feature flag option:
- `chroma_agent_api_enabled` (default `1`)

To disable API without uninstalling:

```bash
wp option update chroma_agent_api_enabled 0
```

### Bootstrap First API Key

Use WP-CLI for initial key creation:

```bash
wp chroma-agent key create --label="IDE Agent" --scopes="admin:keys,admin:audit,read:content,write:content,read:theme,write:theme,read:seo,write:seo,read:media,write:media" --rate=120
```

The command prints the key once. Store it securely.

### Authentication

Use bearer auth:

```http
Authorization: Bearer ck_live_<id>.<secret>
```

Optional signed-request mode:
- `X-Chroma-Timestamp: <unix-ts>`
- `X-Chroma-Signature: <hmac_sha256(method + "\n" + route + "\n" + timestamp + "\n" + body, api_key)>`

### Scopes

- `read:content`
- `write:content`
- `read:theme`
- `write:theme`
- `read:seo`
- `write:seo`
- `read:media`
- `write:media`
- `admin:keys`
- `admin:audit`

### Core Endpoints (`/wp-json/chroma-agent/v1`)

#### Discovery
- `GET /discovery`
- `GET /resources`

#### Key Management
- `GET /keys`
- `POST /keys`
- `POST /keys/{id}/revoke`
- `POST /keys/{id}/rotate`

#### Content
- `GET /content`
- `POST /content`
- `GET /content/{id}`
- `PATCH|POST|PUT /content/{id}`
- `DELETE /content/{id}`
- `POST /content/{id}/rollback` (requires `revision_id`)

#### Theme
- `GET /theme/options`
- `PATCH|POST /theme/options`
- `GET /theme/mods`
- `PATCH|POST /theme/mods`

#### SEO
- `GET /seo/options`
- `PATCH|POST /seo/options`
- `GET /seo/meta/{post_id}`
- `PATCH|POST /seo/meta/{post_id}`

#### Media
- `GET /media`
- `POST /media` (multipart field: `file`)
- `POST /media/attach`

#### Audit and Rollback
- `GET /audit`
- `GET /audit/{id}`
- `GET /snapshots`
- `POST /rollback/snapshot`

### Dry-Run

All mutating routes support dry-run.

Send either:
- JSON body field: `"dry_run": true`
- or parameter: `dry_run=1`

Dry-run behavior:
- validates payload and permissions
- computes proposed output and diff
- does not persist changes
- still logs action as dry-run in audit table

### Rollback Strategy

#### Content
Use WordPress revisions:

```http
POST /wp-json/chroma-agent/v1/content/{id}/rollback
{
  "revision_id": 12345
}
```

#### Theme/SEO Options
Each write stores a snapshot row. Roll back by snapshot id:

```http
POST /wp-json/chroma-agent/v1/rollback/snapshot
{
  "snapshot_id": 42
}
```

### Allowlists

For safety, theme and SEO writes are restricted by allowlists.

Options:
- `chroma_agent_api_theme_option_allowlist`
- `chroma_agent_api_theme_mod_allowlist`
- `chroma_agent_api_seo_option_allowlist`
- `chroma_agent_api_seo_meta_allowlist`

Update allowlists with WP-CLI if needed:

```bash
wp option get chroma_agent_api_seo_meta_allowlist --format=json
wp option update chroma_agent_api_seo_meta_allowlist '["_chroma_es_title","_chroma_es_content"]' --format=json
```

### Example Requests

#### List content

```bash
curl -sS "https://example.com/wp-json/chroma-agent/v1/content?post_type=page&per_page=10" \
  -H "Authorization: Bearer $CHROMA_AGENT_KEY"
```

#### Update post (dry-run)

```bash
curl -sS "https://example.com/wp-json/chroma-agent/v1/content/123" \
  -X PATCH \
  -H "Authorization: Bearer $CHROMA_AGENT_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "post_title":"Updated Title",
    "meta":{"_chroma_es_title":"Titulo actualizado"},
    "dry_run":true
  }'
```

#### Set SEO option

```bash
curl -sS "https://example.com/wp-json/chroma-agent/v1/seo/options" \
  -X PATCH \
  -H "Authorization: Bearer $CHROMA_AGENT_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "updates": {
      "chroma_llm_brand_voice": "Professional, concise"
    }
  }'
```

### Key Rotation and Revocation

Rotate:

```bash
wp chroma-agent key rotate <id>
```

Revoke:

```bash
wp chroma-agent key revoke <id>
```

List:

```bash
wp chroma-agent key list
```

### Audit Operations

- Every write records actor key id, route, method, target, dry-run flag, before/after/diff, status code, timestamp, IP.
- Query logs with:
  - `GET /audit?route=/content&target_type=post&limit=50`
- Inspect one log with:
  - `GET /audit/{id}`

### Operational Safety Checklist

Before enabling write scopes in production:
1. Test all read routes on staging.
2. Test write routes with `dry_run=true`.
3. Validate audit log and snapshots are created.
4. Test rollback for both content revision and snapshot restore.
5. Rotate initial bootstrap key and store in secret manager.

### Troubleshooting

- `401 Missing API key`: send `Authorization: Bearer ...`
- `403 HTTPS required`: call over HTTPS only
- `403 Scope denied`: key is missing required scope
- `429 Rate limit`: lower request burst or increase per-key rate
- `404 Snapshot not found`: verify snapshot id from `GET /snapshots`

### Performance Notes

- Plugin is WordPress-native and only initializes route handlers.
- No frontend assets are enqueued.
- DB writes occur only for key usage updates, write audit, and snapshots.

### Code Locations

- Plugin bootstrap: `plugins/chroma-agent-api/chroma-agent-api.php`
- Auth/scopes/rate limiting: `plugins/chroma-agent-api/includes/class-auth.php`
- Key store: `plugins/chroma-agent-api/includes/class-key-store.php`
- Audit log + diff + snapshots:
  - `plugins/chroma-agent-api/includes/class-audit-log.php`
  - `plugins/chroma-agent-api/includes/class-diff.php`
  - `plugins/chroma-agent-api/includes/class-snapshot-store.php`
- REST routes:
  - `plugins/chroma-agent-api/includes/routes/class-discovery-routes.php`
  - `plugins/chroma-agent-api/includes/routes/class-key-routes.php`
  - `plugins/chroma-agent-api/includes/routes/class-content-routes.php`
  - `plugins/chroma-agent-api/includes/routes/class-theme-routes.php`
  - `plugins/chroma-agent-api/includes/routes/class-seo-routes.php`
  - `plugins/chroma-agent-api/includes/routes/class-media-routes.php`
  - `plugins/chroma-agent-api/includes/routes/class-audit-routes.php`
