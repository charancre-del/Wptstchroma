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
  navy: '#1a2332',     // Dark accent
}
chroma: {
  red: '#D67D6B',
  teal: '#4A9B8E',
  green: '#8DA399',
  yellow: '#E6BE75',
}
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

## 🔧 Deployment Checklist

- [ ] Install theme + plugins
- [ ] Install ACF Pro (optional)
- [ ] Configure Chroma Settings (global options)
- [ ] Set up menus (Primary + Footer)
- [ ] Set permalinks to "Post name"
- [ ] Add Programs
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
