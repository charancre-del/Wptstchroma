# Repo Audit Second Pass Fix Log - 20260504

- Branch: `claude/create-wordpress-theme-01N9YNziMjoPyBiwj3iLobdB`
- Baseline: `bac58e30d23b904be977d0d6d4eaad81d70d6b9e`
- Scope: safe deterministic fixes plus report artifacts. No ambiguous scratch files were deleted or committed.

## Fixes Applied

- `.gitignore`: Ignore local Composer audit tooling under tools/composer.phar and tools/composer-setup.php.
- `chroma-plugins/chroma-acquisitions-form/chroma-acquisitions-form.php`: Sanitize/validate webhook URL, require HTTPS, and use wp_safe_remote_post.
- `chroma-plugins/chroma-career-form/chroma-career-form.php`: Sanitize/validate webhook URL, require HTTPS, and use wp_safe_remote_post.
- `chroma-plugins/chroma-contact-form/chroma-contact-form.php`: Sanitize/validate webhook URL, require HTTPS, and use wp_safe_remote_post.
- `chroma-plugins/chroma-lead-log/chroma-lead-log.php`: Sanitize/validate lead webhook URL, require HTTPS, and use wp_safe_remote_post.
- `plugins/QA-Report-App/chroma-qa-reports/includes/ai/class-photo-analyzer.php`: Restrict local analysis to uploads images; validate safe remote image URLs; limit response size; enforce image MIME.
- `plugins/chroma-seo-pro-reset/inc/class-careers-api.php`: Sanitize careers feed URL, allowlist host, and fetch through wp_safe_remote_get.
- `plugins/chroma-parent-portal/includes/class-api-routes.php`: Require admin permission for debug-only REST routes.
- `plugins/chroma-seo-pro/composer.json`: Add proprietary license metadata.
- `plugins/chroma-seo-pro-reset/composer.json`: Add proprietary license metadata.
- `plugins/QA-Report-App/chroma-qa-reports/composer.json`: Add proprietary license metadata.
- `director-portal/package.json`: Upgrade Next within 14.x, add ESLint dependencies, and keep lint/build green.
- `director-portal/.eslintrc.json`: Add Next core-web-vitals lint config.
- `director-portal/app/page.tsx`: Escape apostrophe for React lint compliance.
- `package-lock files`: Apply non-forced npm audit updates where safe; remaining major upgrades documented.

## Verification Run

- `PHP lint: git ls-files *.php excluding vendor/node_modules -> php -l`: passed - 336 owned PHP files checked, 0 failures
- `Composer validate: php tools/composer.phar validate --no-check-publish`: passed - 3 manifests checked and valid
- `npm run build (chroma-excellence-theme)`: passed_with_warnings - Build and asset rev passed; manifest wrote 8 entries; Browserslist/baseline data freshness warnings
- `npm run build (plugins/chroma-school-dashboard)`: passed_with_warnings - esbuild/tailwind build passed; Browserslist data freshness warning
- `npm run lint (director-portal)`: passed_with_warnings - Next lint passed; existing @next/next/no-img-element warning at app/dashboard/page.tsx:31
- `npm run build (director-portal)`: passed_with_warnings - Next 14.2.35 production build passed; same img performance warning
- `npm run build (plugins/chroma-parent-portal/build-env)`: passed_with_warnings - Build passed; Sass legacy API warning and 490.js asset size warning
- `npm install && npm run lint:js (plugins/QA-Report-App/chroma-qa-reports/build-env)`: passed - JS lint passed after installing lockfile-described dependencies
- `npm run lint:css (plugins/QA-Report-App/chroma-qa-reports/build-env)`: passed - CSS lint passed
- `npm run build (plugins/QA-Report-App/chroma-qa-reports/build-env)`: passed_with_warnings - Build passed; cqa-ui-vendor chunk size warning
- `npm test -- --runInBand (plugins/QA-Report-App/chroma-qa-reports)`: passed - 2 suites, 15 tests passed
- `node --check on owned source JS excluding dependency backups/build artifacts`: passed - 101 source JS files checked, 0 failures
- `Theme asset manifest validation`: passed - 8 entries checked, 0 missing referenced files
- `Callback-aware AJAX scanner`: reviewed - 76 AJAX hooks found; 7 candidates reviewed as delegated permission/OAuth-state callbacks; no confirmed unpatched gap
- `REST route scanner`: reviewed - 32 route patterns found by scanner; parent portal debug routes hardened; remaining public flags reviewed as scanner limitations or intentional public routes

## Deferred Work

- Major dependency migrations remain deferred: Next 16, @wordpress/scripts 32, react-pdf 10, jest-environment-jsdom 30, and fast-xml-parser 5.
- WordPress runtime/browser checks remain pending until a usable WP URL/bootstrap/API key is available.
