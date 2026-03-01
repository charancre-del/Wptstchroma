# Blog CLS Remediation Plan (All Post Pages)

## Scope
- Applies to all blog single-post pages rendered by `chroma-excellence-theme/single.php`.
- Targets desktop + mobile CLS regressions (current reported CLS up to `1.151`).

## Root Causes Confirmed
1. `single.php` renders key images without intrinsic dimensions:
- Author avatar uses `get_avatar_url()` and raw `<img>` without `width`/`height`.
- Featured image uses raw URL output without `width`/`height` attributes.
- Location: `chroma-excellence-theme/single.php:17`, `chroma-excellence-theme/single.php:137`, `chroma-excellence-theme/single.php:149`.

2. Main stylesheet can load asynchronously on post pages:
- `chroma_async_styles()` switches `chroma-main` to `media='print' onload=...` unless route is considered layout-critical.
- Current critical-route function does not include blog posts.
- Location: `chroma-excellence-theme/inc/enqueue.php:428`, `chroma-excellence-theme/inc/enqueue.php:438`.

3. Header logo in `single.php` also lacks intrinsic dimensions:
- Can contribute to minor shift in top bar.
- Location: `chroma-excellence-theme/single.php:113`.

## Fix Plan (Implementation Order)

### P0. Keep main CSS synchronous on blog posts
- File: `chroma-excellence-theme/inc/enqueue.php`
- Change:
  - Update `chroma_is_layout_critical_route()` to return `true` for `is_singular('post')` (or `is_single()` if only blog posts use this template).
- Why:
  - Prevents late application of layout classes (`max-w-*`, spacing, typography) on posts.
  - Eliminates large shifts from late style application.

### P0. Add intrinsic size for avatar and logo in single template
- File: `chroma-excellence-theme/single.php`
- Change:
  - Replace raw avatar URL flow with `get_avatar()` and explicit attributes:
    - `width="48" height="48" loading="eager" decoding="async"`.
  - Add `width`/`height` to header logo `<img>` to match rendered size.
- Why:
  - Removes Lighthouse “Unsized image element” issue.
  - Stabilizes author block and top header during first render.

### P0. Render featured image with intrinsic dimensions
- File: `chroma-excellence-theme/single.php`
- Change:
  - Replace raw featured image URL output with `wp_get_attachment_image()` (preferred) using attachment ID and explicit attributes:
    - `width`, `height`, `decoding="async"`.
    - `fetchpriority="high"` on single-post hero image.
    - `loading="eager"` for above-the-fold hero.
- Why:
  - Reserves hero image space before file decode.
  - Prevents content container jump (`max-w-5xl`/`max-w-3xl` areas flagged in Lighthouse).

## Optional Hardening (P1)
- File: `chroma-excellence-theme/single.php`
- Add a lightweight intrinsic wrapper on hero media:
  - Use `style="aspect-ratio: X / Y"` based on featured image metadata when available.
- Why:
  - Extra protection when image dimensions cannot be resolved immediately.

## Validation Plan

### Functional
1. Open 3 representative blog posts (long, medium, short content).
2. Confirm no visual jump in:
- Header avatar row.
- Featured image block.
- First paragraph container.

### Technical
1. In DevTools Elements:
- Verify avatar `<img>` has `width` and `height`.
- Verify featured image has `width` and `height`.
2. In Network:
- Verify `chroma-main` is not loaded via `media='print'` on post pages.
3. In Lighthouse (desktop + mobile) on 3 posts:
- CLS target per page: `< 0.10` (stretch `< 0.05`).
- No “Unsized image element” warning for author avatar.

### Regression
1. Home, locations, programs, contact pages still render correctly.
2. Ensure non-blog routes that intentionally async-load CSS keep current behavior.

## Success Criteria
- Blog single pages no longer show large CLS spikes.
- Largest shift contributors in Lighthouse no longer point to:
  - `article > header ... img.w-12`
  - `max-w-5xl` featured image block.
- Performance score improves due reduced CLS penalty on desktop/mobile.
