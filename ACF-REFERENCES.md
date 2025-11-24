# ACF references audit

The current codebase no longer relies on ACF runtime fields, but the term "ACF" still appears in a few places for legacy context:

- `chroma-excellence-theme/functions.php` loads `inc/acf-options.php` and `inc/acf-homepage.php` (helpers originally written for ACF data). These files remain for backward compatibility but the homepage now pulls hardcoded data and the helpers rely only on core WordPress functions (no ACF API calls).
- `README.md` and other documentation files reference legacy ACF field groups and optional installation steps.
- Theme header copy now notes hardcoded sections (ACF optional); other templates no longer describe ACF-driven architecture.

A repository-wide search for "acf" can be reproduced with:

```bash
rg -ni "acf"
```

To confirm that no runtime ACF PHP functions are invoked, run:

```bash
rg "get_field" chroma-excellence-theme chroma-plugins
```
