# Homepage Component Audit Report
**Date:** 2025-11-23
**Comparing:** Provided HTML mockup vs. Current WordPress Theme

## Executive Summary

The WordPress theme is **structurally sound** but is **missing 5 major interactive sections** required to match the provided HTML homepage design. The current homepage follows a different content approach focused on static content cards rather than interactive wizards and data visualizations.

---

## ✅ Components PRESENT and Working

### 1. Header/Navigation
- **Status:** ✅ COMPLETE
- **File:** `header.php`
- **Features:**
  - Chroma logo with colored dots
  - Sticky header with backdrop blur
  - Desktop navigation
  - Mobile menu with slide-in panel
  - "Book A Tour" CTA button
  - JavaScript toggle working (`main.js:10-28`)

### 2. Hero Section
- **Status:** ⚠️ PARTIAL (70%)
- **File:** `template-parts/home/hero.php`
- **Present:**
  - Gradient background with blur effects
  - Headline with serif font
  - Subheading text
  - Two CTA buttons (primary + secondary)
  - Image/video placeholder area
  - Decorative background shapes
- **Missing:**
  - ❌ Meta badges row ("4.8 Rating", "Licensed • Quality Rated • GA Pre-K Partner")
  - ❌ Floating "Kindergarten Ready" overlay card on hero image
  - ❌ Italic styling for "growing up" (HTML uses `<em>` tag)
  - ❌ Location count pill ("19+ Metro Atlanta Locations") - exists but styled differently

### 3. Stats Strip
- **Status:** ⚠️ PARTIAL (60%)
- **File:** `template-parts/home/stats-strip.php`
- **Present:**
  - 4-stat grid layout
  - Colored numbers (yellow, teal, red, green)
  - Labels below numbers
- **Issues:**
  - ❌ Wrong styling: Current uses `bg-brand-navy py-16` (dark navy background)
  - ❌ HTML expects: White background, border, centered on page, hover effects
  - ❌ HTML shows: "19+ Metro campuses", "2,000+ Children enrolled", "4.8 Avg parent rating", "6w–12y Age range"

### 4. FAQ Accordion
- **Status:** ✅ COMPLETE
- **File:** `template-parts/home/faq.php`
- **Features:**
  - Accordion with expand/collapse
  - JavaScript interaction (`main.js:30-58`)
  - ACF-driven questions and answers
  - Optional CTA at bottom

### 5. Tour Form Section
- **Status:** ✅ COMPLETE
- **File:** `template-parts/home/tour-cta.php`
- **Features:**
  - Gradient background
  - Shortcode integration `[chroma_tour_form]`
  - White card container for form
  - Trust indicators text
  - Plugin detection fallback

### 6. Locations Preview
- **Status:** ⚠️ PARTIAL (40%)
- **File:** `template-parts/home/locations-preview.php`
- **Present:**
  - Leaflet map integration
  - Featured locations grid (3 cards)
  - Location data from CPT
- **Missing:**
  - ❌ HTML expects 4-column grid grouped by county (Cobb, Gwinnett, North Metro, South Metro)
  - ❌ HTML shows 19+ locations in compact cards with emoji headers
  - ❌ "Now Enrolling" badges on specific locations
  - ❌ Hover effects with colored borders (red/blue/yellow/green)
  - ❌ Different layout: rows of cards within county groups

### 7. Footer
- **Status:** ✅ COMPLETE
- **File:** `footer.php`
- Chroma branding with colored dots present in header, footer pattern similar

---

## ❌ Components MISSING Entirely

### 1. **PRISMPATH "Grounded in Expertise" Section**
- **Status:** ❌ NOT PRESENT
- **Required:** Large colored cards section after hero
- **Expected Content:**
  - Section heading: "Grounded in Expertise. Wrapped in Love."
  - **Blue Card (large, 7-col span):** "The Prismpath™ Curriculum" with badge "Proprietary Model", includes nested card "Kindergarten Readiness"
  - **Red Card (large, 5-col span, tall):** "Expert Care, Extended Family" with user icon and "Meet the Team" button
  - **Green Card:** "Wholesome Fuel" with apple icon
  - **White Card:** "Uncompromised Safety" with shield icon
- **HTML Reference:** Lines 220-275 (bento grid layout with `md:grid-cols-12`)
- **Impact:** HIGH - This is the main "why choose us" section

### 2. **PROGRAMS WIZARD (Interactive)**
- **Status:** ❌ NOT PRESENT
- **Current:** `programs-preview.php` shows static 3-program grid
- **Required:** Interactive age-selection wizard
- **Expected Behavior:**
  1. User clicks age button (Infant/Toddler/Preschool/Prep/Pre-K/After School)
  2. Result panel appears with:
     - Program title (e.g., "Infant Care (6 weeks–12 months)")
     - Description paragraph
     - "Learn more" link
     - "Speak to enrollment" CTA
  3. "Start Over" button to reset
- **HTML Reference:** Lines 286-323
- **JavaScript Required:** Program config data + click handlers
- **Impact:** HIGH - Key conversion tool for parents

### 3. **CURRICULUM WITH CHART.JS RADAR**
- **Status:** ❌ NOT PRESENT
- **Current:** `curriculum.php` shows 3 static cards (Philosophy, Approach, Outcomes)
- **Required:** Interactive curriculum focus visualization
- **Expected Features:**
  - Age tabs: Infant/Toddler/Preschool/Prep/Pre-K/After School
  - Radar chart showing 5 pillars: Physical, Emotional, Social, Academic, Creative
  - Data changes when clicking age tabs
  - Description panel updates per age (e.g., "Foundation Phase" for infants)
- **HTML Reference:** Lines 325-365
- **Dependencies:**
  - ❌ Chart.js library (NOT enqueued)
  - ❌ Curriculum config data object
  - ❌ Tab switching JavaScript
  - ❌ Chart update logic
- **Impact:** MEDIUM-HIGH - Unique differentiator showing Prismpath™ methodology

### 4. **SCHEDULE/DAY-IN-LIFE SECTION**
- **Status:** ❌ NOT PRESENT
- **Required:** Tabbed daily schedule view
- **Expected Content:**
  - 3 tabs: Infants, Toddlers, Pre-K
  - Each tab shows:
    - Colored background (blue/yellow/red)
    - Section title (e.g., "The Nurturing Nest")
    - Description paragraph
    - Timeline with 3 time blocks (AM, Mid, PM or specific times)
    - Accompanying image
- **HTML Reference:** Lines 367-451
- **JavaScript Required:** Tab switching logic
- **Impact:** MEDIUM - Helps parents visualize the day

---

## 📊 Technical Requirements Missing

### 1. Chart.js Library
- **Status:** ❌ NOT ENQUEUED
- **Required For:** Curriculum radar chart
- **Fix:** Add to `inc/enqueue.php`:
```php
// Chart.js for curriculum visualization
wp_enqueue_script(
    'chartjs',
    'https://cdn.jsdelivr.net/npm/chart.js',
    array(),
    '4.4.0',
    true
);
```

### 2. Homepage JavaScript
- **Status:** ❌ NOT PRESENT
- **Current:** `assets/js/main.js` only has mobile nav + accordion
- **Required:** New file or expanded `main.js` with:
  - Program wizard config data + handlers
  - Curriculum chart initialization + tab handlers
  - Schedule tab switchers
  - Chart.js configuration

### 3. ACF Fields for New Sections
- **Status:** ❌ NOT CONFIGURED
- **Required Fields:**
  - `home_prismpath_cards` (repeater for expertise cards)
  - `home_program_wizard_data` (repeater for age-based program info)
  - `home_curriculum_data` (repeater for age-based curriculum focus)
  - `home_schedule_tabs` (repeater for daily schedule content)

---

## 🎨 Styling Discrepancies

### Colors
- **Theme:** Uses `chroma-red`, `chroma-yellow`, `chroma-green`, `chroma-teal`
- **HTML:** Uses slightly different palette with `home-` prefix variants
- **Recommendation:** Verify Tailwind config matches HTML color values

### Stats Strip
- **Current:** Dark navy background (`bg-brand-navy`)
- **HTML:** White background with subtle border
- **Fix:** Change in `stats-strip.php` line 15

### Hero Video
- **Current:** Static image or gradient placeholder
- **HTML:** `<video autoplay muted loop>` with mp4 source
- **Note:** Video file `hero-classroom.mp4` not provided

---

## 📝 File-by-File Status

| File | Status | Notes |
|------|--------|-------|
| `front-page.php` | ⚠️ Incomplete | Missing 4 section includes |
| `template-parts/home/hero.php` | ⚠️ 70% | Missing meta badges, floating card |
| `template-parts/home/stats-strip.php` | ⚠️ 60% | Wrong styling |
| `template-parts/home/programs-preview.php` | ❌ Wrong approach | Needs wizard replacement |
| `template-parts/home/curriculum.php` | ❌ Wrong approach | Needs Chart.js version |
| `template-parts/home/locations-preview.php` | ⚠️ 40% | Wrong layout |
| `template-parts/home/faq.php` | ✅ Complete | Working |
| `template-parts/home/tour-cta.php` | ✅ Complete | Working |
| `assets/js/main.js` | ⚠️ Basic only | Needs homepage interactivity |
| `inc/enqueue.php` | ⚠️ Missing Chart.js | Needs library addition |

---

## 🚧 Missing Template Parts (Need Creation)

1. ❌ `template-parts/home/prismpath-expertise.php` - Colored cards bento grid
2. ❌ `template-parts/home/programs-wizard.php` - Interactive age selector
3. ❌ `template-parts/home/curriculum-chart.php` - Chart.js radar with tabs
4. ❌ `template-parts/home/schedule-tabs.php` - Daily rhythm tabs

---

## Priority Recommendations

### Critical (Must Have)
1. **Create Prismpath Expertise Section** - Core value proposition
2. **Build Programs Wizard** - Key conversion tool
3. **Enqueue Chart.js** - Required for curriculum viz
4. **Add Schedule Tabs** - Important for parent decision-making

### Important (Should Have)
5. Update stats strip styling to match HTML
6. Enhance hero with meta badges and floating card
7. Rebuild locations grid with county grouping
8. Create curriculum chart component with Chart.js

### Nice to Have
9. Add video support to hero (if video assets provided)
10. Refine color palette consistency
11. Add hover animations to match HTML

---

## Estimated Development Effort

- **Prismpath Section:** 2-3 hours (new template-part + ACF fields)
- **Programs Wizard:** 3-4 hours (template + JS + ACF)
- **Curriculum Chart:** 4-5 hours (Chart.js integration + tabs + data)
- **Schedule Tabs:** 2-3 hours (template + tab JS)
- **Minor fixes:** 2-3 hours (hero enhancements, stats styling, locations)

**Total:** ~15-20 hours for full parity with HTML design

---

## Conclusion

The WordPress theme has a **solid foundation** with proper architecture, ACF integration, and working basic sections. However, it's currently implementing a **simpler, more static version** of the homepage compared to the provided HTML mockup.

To achieve the interactive, engaging homepage shown in the HTML:
- **4 new template-parts** must be created
- **Chart.js** must be integrated
- **~500 lines of JavaScript** must be added for interactivity
- **15+ new ACF fields** must be configured

The current theme would work for a basic launch but lacks the "wow factor" and interactive elements that differentiate Chroma's Prismpath™ approach in the provided design.
