# Chroma Early Learning Academy WordPress Theme

Custom WordPress theme for Chroma Early Learning Academy featuring the Prismpath™ curriculum model, ACF Flexible Content, and Tailwind CSS design system.

## Features

- **20+ Metro Atlanta Locations** - Multi-location support with individual pages
- **Custom Post Types** - Programs, Locations, and Team Members
- **ACF Flexible Content** - Modular homepage layout
- **Tailwind CSS** - Modern utility-first CSS framework
- **Prismpath™ Curriculum** - Interactive curriculum radar charts
- **SEO Optimized** - Schema.org structured data for childcare businesses
- **Responsive Design** - Mobile-first approach
- **Performance** - Optimized assets with build system

## Requirements

- WordPress 6.0+
- PHP 8.0+
- Node.js 18+ (for development)
- ACF Pro plugin (required)

## Installation

1. Upload the `chroma-ela` folder to `/wp-content/themes/`
2. Install and activate **Advanced Custom Fields Pro**
3. Activate the theme from WordPress admin
4. Configure ACF field groups (see ACF Configuration below)
5. Set up Chroma Settings (Appearance → Chroma Settings)

## Development Setup

### Install Dependencies

```bash
cd wp-content/themes/chroma-ela
npm install
```

### Development Mode (Watch for changes)

```bash
npm run dev
```

This will:
- Watch Tailwind CSS changes and compile to `dist/app.css`
- Watch JavaScript changes and bundle to `dist/app.js`

### Production Build

```bash
npm run build
```

This will create minified production assets in the `dist/` folder.

## Theme Structure

```
chroma-ela/
├── style.css              # Theme header file
├── functions.php          # Main functions file
├── header.php             # Site header
├── footer.php             # Site footer
├── front-page.php         # Homepage template
├── page.php               # Default page template
├── single.php             # Single post template
├── single-program.php     # Single program template
├── single-location.php    # Single location template
├── single-team.php        # Single team member template
├── index.php              # Fallback template
├── /inc                   # Core functionality
│   ├── setup.php          # Theme setup
│   ├── enqueue.php        # Scripts & styles
│   ├── nav-menus.php      # Navigation menus
│   ├── cpt-program.php    # Program CPT
│   ├── cpt-location.php   # Location CPT
│   ├── cpt-team.php       # Team CPT
│   ├── acf.php            # ACF configuration
│   ├── seo.php            # SEO & schema
│   └── patterns.php       # Block patterns
├── /template-parts        # Modular template parts
│   ├── /hero              # Hero sections
│   ├── /home              # Homepage sections
│   ├── /program           # Program components
│   └── /location          # Location components
├── /resources             # Source files
│   ├── /css
│   │   └── app.css        # Tailwind entry
│   └── /js
│       └── app.js         # Main JavaScript
├── /dist                  # Compiled assets
│   ├── app.css            # Compiled Tailwind CSS
│   └── app.js             # Bundled JavaScript
├── tailwind.config.js     # Tailwind configuration
├── package.json           # NPM dependencies
└── .gitignore
```

## ACF Configuration

### Required Field Groups

1. **Chroma Settings (Options Page)**
   - Logo (light/dark)
   - Header CTA (text + link)
   - Contact info (phone, email, address)
   - Social media links
   - Footer tagline
   - SEO defaults

2. **Homepage Layout (Flexible Content)**
   - `hero_warm` - Hero section
   - `prismpath_section` - About Prismpath
   - `stats_strip` - Statistics
   - `programs_wizard` - Program finder
   - `curriculum_radar` - Interactive chart
   - `schedule_strip` - Daily schedule
   - `locations_grid` - Locations list
   - `tour_cta` - Tour form
   - `faq_strip` - FAQ accordion

3. **Program Fields**
   - Age range
   - Tagline
   - Curriculum focus (repeater)
   - Offered locations (relationship)
   - Features

4. **Location Fields**
   - Address, city, state, zip
   - County
   - Phone, email
   - Hours
   - Enrollment status
   - Google Maps embed
   - Programs offered (relationship)

5. **Team Fields**
   - Role
   - Credentials
   - Locations served (relationship)

## Custom Post Types

### Programs (`program`)
- Slug: `/program/{slug}`
- Archive: `/programs`
- Features: Title, Content, Featured Image, ACF Fields

### Locations (`location`)
- Slug: `/location/{slug}`
- Archive: `/locations`
- Features: Title, Content, Featured Image, ACF Fields

### Team (`team`)
- Slug: `/team/{slug}`
- Archive: `/team`
- Features: Title, Content, Featured Image, ACF Fields

## Navigation Menus

Register two menu locations:
1. **Primary Menu** - Main header navigation
2. **Footer Menu** - Footer links

## Brand Colors

The theme uses a custom color palette defined in `tailwind.config.js`:

- **Brand Colors**
  - `brand-ink`: #263238 (primary text)
  - `brand-cream`: #FFFCF8 (background)

- **Chroma Colors**
  - `chroma-red`: #D67D6B
  - `chroma-blue`: #4A6C7C
  - `chroma-green`: #8DA399
  - `chroma-yellow`: #E6BE75

## JavaScript Features

- Mobile menu toggle
- Program wizard (age-based program finder)
- Curriculum radar chart (Chart.js)
- Schedule tab switching
- Smooth scrolling
- Form validation

## SEO & Schema

The theme automatically generates:
- Organization schema (homepage)
- LocalBusiness schema (location pages)
- Open Graph tags
- Custom meta descriptions

## Support

For theme support and customization:
- Internal development team
- ACF documentation: https://www.advancedcustomfields.com/
- Tailwind CSS: https://tailwindcss.com/

## License

Proprietary - All rights reserved © 2025 Chroma Early Learning Academy
