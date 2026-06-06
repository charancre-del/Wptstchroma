# Chroma Excellence Theme - SEO Features

This theme includes a built-in, lightweight SEO engine designed specifically for child care centers. It handles meta tags, schema markup, and social sharing cards automatically, while giving you full manual control when needed.

## 1. Meta Descriptions & Keywords

The theme uses a "3-Layer Fallback" system to ensure every page has optimized meta tags.

### How it Works (Priority Order):
1.  **Manual Override:** If you enter text in the **"SEO Meta"** box on a page/post, that text is used.
2.  **Dynamic Template:** If the manual box is empty, the theme generates a description based on the page content (e.g., Location details, Program excerpt).
3.  **Global Default:** If neither of the above exists, a global default description is used.

### Managing SEO Metadata

#### General Pages & Posts
1.  Edit any Page or Post.
2.  Look for the **"SEO Meta"** box in the right-hand sidebar.
3.  **Meta Description:** Enter a custom description (150-160 characters recommended).
4.  **Meta Keywords:** Enter comma-separated keywords (e.g., `preschool, daycare, Atlanta`).

#### Location Pages
Location pages have a special dynamic template:
*"Visit our [Location Name] campus in [City], [State]. [Tagline]. Serving families in [Service Areas]. [Phone]."*

To optimize this:
1.  Edit a **Location** post.
2.  Scroll down to the **"Service Areas"** field in the "Location Details" box.
3.  Enter nearby cities/neighborhoods (e.g., *Kennesaw, Acworth, Marietta*).
4.  These will be automatically inserted into the meta description and keywords.

## 2. Schema Markup (Structured Data)

The theme automatically outputs Schema.org JSON-LD markup to help search engines understand your content.

*   **Homepage:** `Organization` (Logo, Social Links, Contact) and `WebSite` (Search Box).
*   **Location Pages:** `ChildCare` / `LocalBusiness` (Address, Phone, Geo-coordinates, Rating, Hours).
*   **Program Pages:** `Service` (Provider, Area Served, Description).
*   **Breadcrumbs:** `BreadcrumbList` on all inner pages.
*   **FAQ:** `FAQPage` on the homepage (if FAQ section is active).

## 3. Social Media Cards

*   **Open Graph (Facebook/LinkedIn):** Automatically sets Title, Description, URL, and Featured Image.
*   **Twitter Cards:** Sets Summary Card with Large Image.

## 4. Other Features

*   **Sitemap:** A lightweight XML sitemap is generated at `yourdomain.com/?sitemap=xml`.
*   **Robots.txt:** Automatically adds the sitemap link to `robots.txt`.
*   **Hreflang:** Supports English/Spanish alternates if configured.

## Best Practices
*   **Locations:** Always fill out the **"Service Areas"** field for every location to capture local search traffic (e.g., "daycare near Acworth").
*   **Programs:** Ensure every Program has a **Featured Image** and a custom **Excerpt** for the best appearance in search results and social shares.
*   **Images:** Use descriptive filenames (e.g., `preschool-classroom-marietta.jpg`) before uploading.
