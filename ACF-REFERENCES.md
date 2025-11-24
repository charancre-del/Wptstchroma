# ACF references audit

The current codebase no longer relies on ACF runtime fields, but the term "ACF" still appears in a few places for legacy context:

- `chroma-excellence-theme/functions.php` loads `inc/acf-options.php` and `inc/acf-homepage.php` (helpers originally written for ACF data). These files remain for backward compatibility but the homepage now pulls hardcoded data.
- `README.md` and other documentation files reference legacy ACF field groups and optional installation steps.
- Comments in theme headers and templates (e.g., `front-page.php`, `style.css`) describe the original ACF-driven architecture.

A repository-wide search for "acf" can be reproduced with:

```bash
rg -ni "acf"
```
