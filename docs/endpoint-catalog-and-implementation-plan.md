# Endpoint Catalog and Secure Automation Implementation Plan

## Scope
This document is generated from code in this repository (`c:\Users\chara\Documents\wptheme\Wptstchroma`) and covers:
- Existing REST endpoints
- Existing `admin-ajax.php` actions
- Existing rewrite/public URL endpoints
- Full implementation plan for secure API-key based automation access (theme/SEO/content)

## 1) Code-Derived Endpoint Catalog

### 1.1 REST Endpoints

#### Namespace: `cqa/v1` (QA Reports)
| Method(s) | Path | Permission | Source |
|---|---|---|---|
| GET | `/wp-json/cqa/v1/me` | `check_authenticated_permission` | `plugins/QA-Report-App/chroma-qa-reports/includes/api/class-rest-controller.php:46` |
| GET, POST | `/wp-json/cqa/v1/schools` | read/manage schools callbacks | `plugins/QA-Report-App/chroma-qa-reports/includes/api/class-rest-controller.php:53` |
| GET, POST/PUT/PATCH, DELETE | `/wp-json/cqa/v1/schools/{id}` | read/manage schools callbacks | `plugins/QA-Report-App/chroma-qa-reports/includes/api/class-rest-controller.php:66` |
| GET | `/wp-json/cqa/v1/settings/available-models` | `check_settings_permission` | `plugins/QA-Report-App/chroma-qa-reports/includes/api/class-rest-controller.php:84` |
| GET, POST | `/wp-json/cqa/v1/reports` | read/create report callbacks | `plugins/QA-Report-App/chroma-qa-reports/includes/api/class-rest-controller.php:91` |
| GET, POST/PUT/PATCH, DELETE | `/wp-json/cqa/v1/reports/{id}` | read/edit/delete callbacks | `plugins/QA-Report-App/chroma-qa-reports/includes/api/class-rest-controller.php:104` |
| GET, POST | `/wp-json/cqa/v1/reports/{id}/responses` | read/edit callbacks | `plugins/QA-Report-App/chroma-qa-reports/includes/api/class-rest-controller.php:123` |
| GET | `/wp-json/cqa/v1/reports/{id}/pdf` | `check_export_permission` | `plugins/QA-Report-App/chroma-qa-reports/includes/api/class-rest-controller.php:137` |
| POST | `/wp-json/cqa/v1/reports/{id}/generate-summary` | `check_ai_permission` | `plugins/QA-Report-App/chroma-qa-reports/includes/api/class-rest-controller.php:144` |
| POST | `/wp-json/cqa/v1/reports/upload-doc` | `check_create_reports_permission` | `plugins/QA-Report-App/chroma-qa-reports/includes/api/class-rest-controller.php:150` |
| GET | `/wp-json/cqa/v1/manifest` | public (`__return_true`) | `plugins/QA-Report-App/chroma-qa-reports/includes/api/class-rest-controller.php:157` |
| POST | `/wp-json/cqa/v1/reports/{id}/photos` | `check_edit_reports_permission` | `plugins/QA-Report-App/chroma-qa-reports/includes/api/class-rest-controller.php:164` |
| POST/PUT/PATCH, DELETE | `/wp-json/cqa/v1/photos/{id}` | `check_read_permission` | `plugins/QA-Report-App/chroma-qa-reports/includes/api/class-rest-controller.php:170` |
| POST | `/wp-json/cqa/v1/ai/parse-document` | `check_ai_permission` | `plugins/QA-Report-App/chroma-qa-reports/includes/api/class-rest-controller.php:183` |
| GET | `/wp-json/cqa/v1/checklists/{type}` | `check_read_permission` | `plugins/QA-Report-App/chroma-qa-reports/includes/api/class-rest-controller.php:190` |
| GET | `/wp-json/cqa/v1/schools/{id}/reports` | `check_read_permission` | `plugins/QA-Report-App/chroma-qa-reports/includes/api/class-rest-controller.php:197` |
| GET, POST | `/wp-json/cqa/v1/settings` | `check_settings_permission` | `plugins/QA-Report-App/chroma-qa-reports/includes/api/class-rest-controller.php:204` |
| GET | `/wp-json/cqa/v1/stats` | `check_read_permission` | `plugins/QA-Report-App/chroma-qa-reports/includes/api/class-rest-controller.php:218` |
| GET | `/wp-json/cqa/v1/system-check` | `check_manage_options_permission` | `plugins/QA-Report-App/chroma-qa-reports/includes/api/class-rest-controller.php:225` |
| GET | `/wp-json/cqa/v1/reports/{id}/versions` | `check_read_permission` | `plugins/QA-Report-App/chroma-qa-reports/includes/api/class-rest-controller.php:232` |
| GET | `/wp-json/cqa/v1/reports/{id}/versions/{version}` | `check_read_permission` | `plugins/QA-Report-App/chroma-qa-reports/includes/api/class-rest-controller.php:238` |
| POST | `/wp-json/cqa/v1/reports/{id}/restore/{version}` | `check_edit_reports_permission` | `plugins/QA-Report-App/chroma-qa-reports/includes/api/class-rest-controller.php:244` |
| POST | `/wp-json/cqa/v1/reports/{id}/workflow` | workflow permission callback | `plugins/QA-Report-App/chroma-qa-reports/includes/workflow/class-approval-workflow.php:37` |
| GET | `/wp-json/cqa/v1/reports/{id}/comments` | `check_view_permission` | `plugins/QA-Report-App/chroma-qa-reports/includes/workflow/class-approval-workflow.php:48` |
| POST | `/wp-json/cqa/v1/location/verify` | `current_user_can('cqa_create_reports')` | `plugins/QA-Report-App/chroma-qa-reports/includes/utils/class-location.php:146` |
| POST | `/wp-json/cqa/v1/location/log-override` | `current_user_can('cqa_create_reports')` | `plugins/QA-Report-App/chroma-qa-reports/includes/utils/class-location.php:159` |
| GET | `/wp-json/cqa/v1/analytics/school/{id}/trend` | `current_user_can('cqa_view_all_reports')` | `plugins/QA-Report-App/chroma-qa-reports/includes/analytics/class-trends.php:263` |
| GET | `/wp-json/cqa/v1/analytics/regional` | `current_user_can('cqa_view_all_reports')` | `plugins/QA-Report-App/chroma-qa-reports/includes/analytics/class-trends.php:273` |
| GET | `/wp-json/cqa/v1/analytics/company` | `current_user_can('cqa_view_all_reports')` | `plugins/QA-Report-App/chroma-qa-reports/includes/analytics/class-trends.php:283` |
| GET | `/wp-json/cqa/v1/analytics/school/{id}/export` | `current_user_can('cqa_view_all_reports')` | `plugins/QA-Report-App/chroma-qa-reports/includes/analytics/class-trends.php:293` |
| POST | `/wp-json/cqa/v1/photos/analyze` | `current_user_can('cqa_create_reports')` | `plugins/QA-Report-App/chroma-qa-reports/includes/ai/class-photo-analyzer.php:235` |
| POST | `/wp-json/cqa/v1/photos/batch-analyze` | `current_user_can('cqa_create_reports')` | `plugins/QA-Report-App/chroma-qa-reports/includes/ai/class-photo-analyzer.php:252` |
| GET | `/wp-json/cqa/v1/insights/company` | `current_user_can('cqa_view_all_reports')` | `plugins/QA-Report-App/chroma-qa-reports/includes/ai/class-comparative-insights.php:311` |

#### Namespace: `chroma-portal/v1` (Parent Portal)
| Method(s) | Path | Permission | Source |
|---|---|---|---|
| POST | `/wp-json/chroma-portal/v1/login` | public (`__return_true`) | `plugins/chroma-parent-portal/includes/class-api-routes.php:19` |
| GET | `/wp-json/chroma-portal/v1/content/dashboard` | token/admin `check_permission` | `plugins/chroma-parent-portal/includes/class-api-routes.php:26` |
| GET | `/wp-json/chroma-portal/v1/years` | token/admin `check_permission` | `plugins/chroma-parent-portal/includes/class-api-routes.php:33` |
| GET | `/wp-json/chroma-portal/v1/taxonomy/{taxonomy}` | token/admin `check_permission` | `plugins/chroma-parent-portal/includes/class-api-routes.php:40` |
| POST | `/wp-json/chroma-portal/v1/content/create` | admin (`is_admin_check`) | `plugins/chroma-parent-portal/includes/class-api-routes.php:47` |
| POST | `/wp-json/chroma-portal/v1/content/update/{id}` | admin (`is_admin_check`) | `plugins/chroma-parent-portal/includes/class-api-routes.php:54` |
| DELETE | `/wp-json/chroma-portal/v1/content/delete/{id}` | admin (`is_admin_check`) | `plugins/chroma-parent-portal/includes/class-api-routes.php:61` |
| GET | `/wp-json/chroma-portal/v1/system-check` | public debug | `plugins/chroma-parent-portal/includes/class-api-routes.php:68` |
| GET | `/wp-json/chroma-portal/v1/cookie-test` | public, callback gated by `WP_DEBUG` | `plugins/chroma-parent-portal/includes/class-api-routes.php:75` |

#### Namespace: `chroma/v1` (School Dashboard + SEO)
| Method(s) | Path | Permission | Source |
|---|---|---|---|
| GET | `/wp-json/chroma/v1/tv/{slug}` | public (`__return_true`) | `plugins/chroma-school-dashboard/inc/class-api-routes.php:14` |
| POST | `/wp-json/chroma/v1/auth/google` | public (`__return_true`) | `plugins/chroma-school-dashboard/inc/class-api-routes.php:20` |
| PATCH | `/wp-json/chroma/v1/portal/school/{id}` | director session token | `plugins/chroma-school-dashboard/inc/class-api-routes.php:26` |
| GET | `/wp-json/chroma/v1/portal/me` | director session token | `plugins/chroma-school-dashboard/inc/class-api-routes.php:32` |
| GET | `/wp-json/chroma/v1/weather` | public (`__return_true`) | `plugins/chroma-school-dashboard/inc/class-api-routes.php:38` |
| GET, POST, DELETE | `/wp-json/chroma/v1/translations/{id}` | `current_user_can('edit_posts')` | `plugins/chroma-seo-pro-reset/inc/class-translation-api.php:23` |
| GET | `/wp-json/chroma/v1/translations` | `current_user_can('edit_posts')` | `plugins/chroma-seo-pro-reset/inc/class-translation-api.php:48` |
| POST | `/wp-json/chroma/v1/translate` | `current_user_can('edit_posts')` | `plugins/chroma-seo-pro-reset/inc/class-translation-api.php:54` |
| GET | `/wp-json/chroma/v1/stats` | `current_user_can('edit_posts')` | `plugins/chroma-seo-pro-reset/inc/class-translation-api.php:60` |
| GET | `/wp-json/chroma/v1/validate` | `current_user_can('edit_posts')` | `plugins/chroma-seo-pro-reset/inc/class-seo-dashboard.php:89` |
| GET | `/wp-json/chroma/v1/citation-facts` | public (`__return_true`) | `plugins/chroma-seo-pro-reset/inc/class-citation-datasets.php:177` |
| GET | `/wp-json/chroma/v1/citation-facts` (duplicate declaration) | public (`__return_true`) | `plugins/chroma-seo-pro/inc/class-citation-datasets.php:177` |

### 1.2 AJAX Endpoints (`/wp-admin/admin-ajax.php?action=...`)

#### QA Reports
| Action | Access | Source |
|---|---|---|
| `cqa_frontend_login` | logged-in + nopriv | `plugins/QA-Report-App/chroma-qa-reports/public/class-frontend-controller.php:32` |
| `cqa_oauth_callback` | logged-in + nopriv | `plugins/QA-Report-App/chroma-qa-reports/public/class-frontend-controller.php:36` |

#### Parent Portal
| Action | Access | Source |
|---|---|---|
| `chroma_portal_run_seed` | logged-in | `plugins/chroma-parent-portal/includes/class-bulk-importer.php:16` |

#### SEO Plugin (`chroma-seo-pro-reset`)
| Action | Access | Source |
|---|---|---|
| `chroma_bulk_reset_schema` | logged-in | `plugins/chroma-seo-pro-reset/inc/seo-automations/class-schema-bulk-ops.php:17` |
| `chroma_bulk_reset_faq` | logged-in | `plugins/chroma-seo-pro-reset/inc/seo-automations/class-schema-bulk-ops.php:18` |
| `chroma_link_equity_ai_preview` | logged-in | `plugins/chroma-seo-pro-reset/inc/seo-automations/class-link-equity-analyzer.php:483` |
| `chroma_link_equity_ai_apply` | logged-in | `plugins/chroma-seo-pro-reset/inc/seo-automations/class-link-equity-analyzer.php:533` |
| `chroma_combo_ai_generate` | logged-in | `plugins/chroma-seo-pro-reset/inc/seo-automations/class-combo-ai-generator.php:17` |
| `chroma_combo_ai_bulk_generate` | logged-in | `plugins/chroma-seo-pro-reset/inc/seo-automations/class-combo-ai-generator.php:18` |
| `chroma_combo_bulk_status` | logged-in | `plugins/chroma-seo-pro-reset/inc/seo-automations/class-combo-ai-generator.php:19` |
| `chroma_combo_save_data` | logged-in | `plugins/chroma-seo-pro-reset/inc/seo-automations/class-combo-ai-generator.php:20` |
| `chroma_combo_get_data` | logged-in | `plugins/chroma-seo-pro-reset/inc/seo-automations/class-combo-ai-generator.php:21` |
| `chroma_combo_ai_translate` | logged-in | `plugins/chroma-seo-pro-reset/inc/seo-automations/class-combo-ai-generator.php:22` |
| `chroma_combo_ai_bulk_translate` | logged-in | `plugins/chroma-seo-pro-reset/inc/seo-automations/class-combo-ai-generator.php:23` |
| `chroma_get_translation_history` | logged-in | `plugins/chroma-seo-pro-reset/inc/meta-boxes/class-spanish-content.php:669` |
| `chroma_restore_translation` | logged-in | `plugins/chroma-seo-pro-reset/inc/meta-boxes/class-spanish-content.php:670` |
| `chroma_auto_translate_post` | logged-in | `plugins/chroma-seo-pro-reset/inc/class-translation-engine.php:22` |
| `chroma_fetch_schema_inspector` | logged-in | `plugins/chroma-seo-pro-reset/inc/class-seo-dashboard.php:25` |
| `chroma_save_schema_inspector` | logged-in | `plugins/chroma-seo-pro-reset/inc/class-seo-dashboard.php:26` |
| `chroma_scan_schema_batch` | logged-in | `plugins/chroma-seo-pro-reset/inc/class-seo-dashboard.php:27` |
| `chroma_get_schema_fields` | logged-in | `plugins/chroma-seo-pro-reset/inc/class-seo-dashboard.php:28` |
| `chroma_fetch_social_preview` | logged-in | `plugins/chroma-seo-pro-reset/inc/class-seo-dashboard.php:29` |
| `chroma_fetch_llm_data` | logged-in | `plugins/chroma-seo-pro-reset/inc/class-seo-dashboard.php:30` |
| `chroma_save_llm_targeting` | logged-in | `plugins/chroma-seo-pro-reset/inc/class-seo-dashboard.php:31` |
| `chroma_reset_post_schema` | logged-in | `plugins/chroma-seo-pro-reset/inc/class-seo-dashboard.php:32` |
| `chroma_apply_schema_fix` | logged-in | `plugins/chroma-seo-pro-reset/inc/class-seo-dashboard.php:33` |
| `chroma_fetch_live_schema` | logged-in | `plugins/chroma-seo-pro-reset/inc/class-seo-dashboard.php:34` |
| `chroma_sync_schema_to_builder` | logged-in | `plugins/chroma-seo-pro-reset/inc/class-seo-dashboard.php:35` |
| `chroma_save_sitemap_urls` | logged-in | `plugins/chroma-seo-pro-reset/inc/class-seo-dashboard.php:36` |
| `chroma_parse_sitemap_urls` | logged-in | `plugins/chroma-seo-pro-reset/inc/class-seo-dashboard.php:37` |
| `chroma_validate_url` | logged-in | `plugins/chroma-seo-pro-reset/inc/class-seo-dashboard.php:38` |
| `chroma_clear_validation_cache` | logged-in | `plugins/chroma-seo-pro-reset/inc/class-seo-dashboard.php:39` |
| `chroma_save_validator_setting` | logged-in | `plugins/chroma-seo-pro-reset/inc/class-seo-dashboard.php:40` |
| `chroma_run_link_analysis` | logged-in | `plugins/chroma-seo-pro-reset/inc/class-seo-dashboard.php:41` |
| `chroma_schema_cleanup_scan` | logged-in | `plugins/chroma-seo-pro-reset/inc/class-seo-dashboard.php:42` |
| `chroma_schema_cleanup_execute` | logged-in | `plugins/chroma-seo-pro-reset/inc/class-seo-dashboard.php:43` |
| `chroma_validate_schema` | logged-in | `plugins/chroma-seo-pro-reset/inc/class-schema-validator.php:1154` |
| `chroma_validate_post_schema` | logged-in | `plugins/chroma-seo-pro-reset/inc/class-schema-validator.php:1186` |
| `chroma_review_schema` | logged-in | `plugins/chroma-seo-pro-reset/inc/class-schema-quality.php:19` |
| `chroma_get_review_queue` | logged-in | `plugins/chroma-seo-pro-reset/inc/class-schema-quality.php:20` |
| `chroma_save_llm_targeting` (2nd hook registration) | logged-in | `plugins/chroma-seo-pro-reset/inc/class-llms-txt.php:30` |
| `chroma_save_llm_settings` | logged-in | `plugins/chroma-seo-pro-reset/inc/class-llm-client.php:27` |
| `chroma_test_llm_connection` | logged-in | `plugins/chroma-seo-pro-reset/inc/class-llm-client.php:28` |
| `chroma_generate_schema` | logged-in | `plugins/chroma-seo-pro-reset/inc/class-llm-client.php:29` |
| `chroma_generate_llm_targeting` | logged-in | `plugins/chroma-seo-pro-reset/inc/class-llm-client.php:30` |
| `chroma_generate_general_seo_meta` | logged-in | `plugins/chroma-seo-pro-reset/inc/class-llm-client.php:31` |
| `chroma_translate_text` | logged-in | `plugins/chroma-seo-pro-reset/inc/class-llm-client.php:32` |
| `chroma_fetch_available_models` | logged-in | `plugins/chroma-seo-pro-reset/inc/class-llm-client.php:33` |
| `chroma_bulk_generate_start` | logged-in | `plugins/chroma-seo-pro-reset/inc/class-llm-bulk-processor.php:25` |
| `chroma_bulk_generate_status` | logged-in | `plugins/chroma-seo-pro-reset/inc/class-llm-bulk-processor.php:26` |
| `chroma_bulk_generate_cancel` | logged-in | `plugins/chroma-seo-pro-reset/inc/class-llm-bulk-processor.php:27` |
| `chroma_translate_homepage` | logged-in | `plugins/chroma-seo-pro-reset/inc/class-homepage-translation-admin.php:27` |
| `chroma_sync_gmb_data` | logged-in | `plugins/chroma-seo-pro-reset/inc/class-google-places-client.php:23` |
| `chroma_get_place_id` | logged-in | `plugins/chroma-seo-pro-reset/inc/class-google-places-client.php:24` |
| `chroma_sync_careers` | logged-in | `plugins/chroma-seo-pro-reset/inc/class-career-sync.php:22` |
| `chroma_save_breadcrumb_settings` | logged-in | `plugins/chroma-seo-pro-reset/inc/class-breadcrumbs.php:23` |
| `chroma_preview_breadcrumbs` | logged-in | `plugins/chroma-seo-pro-reset/inc/class-breadcrumbs.php:24` |
| `chroma_get_preview_posts` | logged-in | `plugins/chroma-seo-pro-reset/inc/class-breadcrumbs.php:25` |
| `chroma_analyze_image` | logged-in | `plugins/chroma-seo-pro-reset/inc/class-advanced-features.php:347` |
| `chroma_compare_competitor` | logged-in | `plugins/chroma-seo-pro-reset/inc/class-advanced-features.php:368` |
| `chroma_scan_theme_strings` | logged-in | `plugins/chroma-seo-pro-reset/inc/admin/class-theme-translator.php:22` |
| `chroma_save_string_translations` | logged-in | `plugins/chroma-seo-pro-reset/inc/admin/class-theme-translator.php:23` |
| `chroma_bulk_translate_strings` | logged-in | `plugins/chroma-seo-pro-reset/inc/admin/class-theme-translator.php:24` |
| `chroma_export_po` | logged-in | `plugins/chroma-seo-pro-reset/inc/admin/class-theme-translator.php:25` |
| `chroma_debug_meta` | logged-in | `plugins/chroma-seo-pro-reset/inc/admin/class-theme-translator.php:29` |
| `chroma_validate_page_schema` | logged-in | `plugins/chroma-seo-pro-reset/inc/admin/class-schema-inspector.php:19` |
| `chroma_fix_schema_with_ai` | logged-in | `plugins/chroma-seo-pro-reset/inc/admin/class-schema-inspector.php:20` |
| `chroma_bulk_translate_all` | logged-in | `plugins/chroma-seo-pro-reset/inc/admin/class-content-inspector.php:19` |

#### School Dashboard AJAX Filter Hook (extends core endpoint behavior)
| Hook | Purpose | Source |
|---|---|---|
| `wp_ajax_nopriv_query-attachments` (filter) | allows media query behavior for unauth attachment queries in plugin logic | `plugins/chroma-school-dashboard/inc/class-media-permissions.php:14` |

### 1.3 Rewrite/Public URL Endpoints
| URL pattern | Maps to | Source |
|---|---|---|
| `/qa-reports/login/` | `index.php?cqa_page=login` | `plugins/QA-Report-App/chroma-qa-reports/public/class-frontend-controller.php:93` |
| `/qa-reports/auth/callback/` | `index.php?cqa_page=oauth_callback` | `plugins/QA-Report-App/chroma-qa-reports/public/class-frontend-controller.php:96` |
| `/qa-reports/*` and `/qa-reports/` | `index.php?cqa_page=dashboard` | `plugins/QA-Report-App/chroma-qa-reports/public/class-frontend-controller.php:99` |
| `/qa-reports/manifest.json` | `index.php?cqa_manifest=1` | `plugins/QA-Report-App/chroma-qa-reports/includes/class-plugin.php:296` |
| `/tv/{slug}` | school TV template query vars | `plugins/chroma-school-dashboard/inc/class-template-loader.php:16` |
| `/portal` and `/portal/*` | school portal template query vars | `plugins/chroma-school-dashboard/inc/class-portal-loader.php:15` |
| `/es` | `index.php?chroma_lang=es` | `plugins/chroma-seo-pro-reset/inc/class-multilingual-manager.php:118` |
| `/es/locations`, `/es/programs` | localized archives | `plugins/chroma-seo-pro-reset/inc/class-multilingual-manager.php:121` |
| `/es/locations/{slug}`, `/es/programs/{slug}` | localized single routes | `plugins/chroma-seo-pro-reset/inc/class-multilingual-manager.php:125` |
| `/es/{page-path}` | localized page fallback | `plugins/chroma-seo-pro-reset/inc/class-multilingual-manager.php:131` |
| `/{program}-in-{city}-{state}` | combo landing page | `plugins/chroma-seo-pro-reset/inc/seo-automations/class-combo-page-generator.php:47` |
| `/sitemap-combos.xml`, `/sitemap-combos-es.xml` | combo sitemap routes | `plugins/chroma-seo-pro-reset/inc/seo-automations/class-combo-page-generator.php:54` |
| `/childcare-in-{county}-county` | service area county pages | `plugins/chroma-seo-pro-reset/inc/seo-automations/class-geographic-seo.php:188` |
| `/daycare-{zip}` | service area ZIP pages | `plugins/chroma-seo-pro-reset/inc/seo-automations/class-geographic-seo.php:195` |
| `/{keyword}-near-me` | near-me pages (`daycare|preschool|childcare|pre-k|infant-care`) | `plugins/chroma-seo-pro-reset/inc/seo-automations/class-near-me-pages.php:49` |
| `/{keyword}-near-{city}-{state}` | city/state near-me pages | `plugins/chroma-seo-pro-reset/inc/seo-automations/class-near-me-pages.php:56` |
| `/locations.kml` | `index.php?chroma_kml=1` | `plugins/chroma-seo-pro-reset/inc/endpoints/kml-endpoint.php:31` |

### 1.4 Legacy/Backup Endpoint Definitions Present in Repo
These are not plugin runtime files but still contain endpoint registrations and can cause confusion if copied back:
- `old_controller.php`
- `temp_old_controller.php`

## 2) Entire Implementation Plan (Secure API-Key Access for IDE Agent)

## 2.1 Goals
- Machine-to-machine access with pure custom API keys.
- Least privilege by scope.
- No cookie auth.
- Full write audit trail with diffs.
- Dry-run for all mutating endpoints.
- Rollback via WP revisions + option snapshots.
- Keep WordPress-native and low overhead.

## 2.2 Recommended Architecture
- Implement **Option B**: custom API key auth + scoped permissions using a dedicated plugin (`plugins/chroma-agent-api/`) or MU-plugin.
- Keep existing plugin REST endpoints unchanged for backward compatibility.
- Add a new namespace: `chroma-agent/v1`.
- Auth model:
  - `Authorization: Bearer ck_live_xxx` (presented once)
  - Store only hashed key (`password_hash`) and display plaintext once on creation.
  - Optional request signing headers for high-trust mode: `X-Chroma-Timestamp`, `X-Chroma-Signature` (HMAC SHA256).

## 2.3 Storage and Data Map for Automation

### Theme data stores (observed patterns)
- `wp_options`: multiple `chroma_*` options.
- `theme_mods_{stylesheet}`: customizer values via `get_theme_mod`.
- `wp_postmeta`: theme/plugin field usage on posts/pages/CPTs.

### SEO plugin data stores (from code)
- `wp_options`: e.g., `chroma_citation_facts`, LLM/validator/automation options.
- `wp_postmeta`: e.g., `_chroma_es_title`, `_chroma_es_content`, `_chroma_es_excerpt`, `_chroma_post_schemas`, `_chroma_schema_override`, `_chroma_schema_type`, and related keys.
- SEO automation pages are virtual (rewrite-based) plus optional option-backed data records.

### Content stores
- `wp_posts`, `wp_postmeta`, taxonomy terms/relations.
- Native revisions available for post/page safety.

## 2.4 New Scope Model
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

## 2.5 Database Additions
- Table: `wp_chroma_api_keys`
  - `id`, `label`, `key_prefix`, `key_hash`, `scopes_json`, `status`, `created_by`, `created_at`, `last_used_at`, `last_used_ip`, `expires_at`, `rate_limit_per_min`.
- Table: `wp_chroma_api_audit_log`
  - `id`, `request_id`, `actor_key_id`, `scope`, `method`, `route`, `target_type`, `target_id`, `dry_run`, `before_json`, `after_json`, `diff_json`, `status_code`, `error_code`, `created_at`, `ip`, `user_agent`.
- Optional table: `wp_chroma_option_snapshots`
  - for rollback of options/theme mods.

## 2.6 Endpoint Catalog to Implement (`chroma-agent/v1`)

### Key management
- `POST /keys` (`admin:keys`) create key + scopes + expiration.
- `GET /keys` (`admin:keys`) list keys (masked).
- `POST /keys/{id}/revoke` (`admin:keys`) revoke.
- `POST /keys/{id}/rotate` (`admin:keys`) rotate.

### Discovery and schema
- `GET /discovery` (read scopes) returns site info, plugin versions, supported resources.
- `GET /schema/theme` (`read:theme`) list editable theme options/mods.
- `GET /schema/seo` (`read:seo`) list SEO options/meta keys.

### Content CRUD with safety
- `GET /content` (`read:content`) list/search posts/pages/CPTs with pagination.
- `GET /content/{id}` (`read:content`) fetch full payload + selected meta.
- `POST /content` (`write:content`) create post/page/CPT.
- `PATCH /content/{id}` (`write:content`) update core fields/meta/taxonomy.
- `DELETE /content/{id}` (`write:content`) trash/delete.
- `POST /content/{id}/rollback` (`write:content`) restore revision by revision ID.

### Theme settings
- `GET /theme/options` (`read:theme`) fetch allowlisted options.
- `PATCH /theme/options` (`write:theme`) update allowlisted options (supports `dry_run`).
- `GET /theme/mods` (`read:theme`) fetch allowlisted theme mods.
- `PATCH /theme/mods` (`write:theme`) update allowlisted mods (supports `dry_run`).

### SEO settings and per-post SEO
- `GET /seo/options` (`read:seo`) fetch allowlisted SEO options.
- `PATCH /seo/options` (`write:seo`) update allowlisted SEO options.
- `GET /seo/meta/{post_id}` (`read:seo`) fetch allowlisted SEO meta keys.
- `PATCH /seo/meta/{post_id}` (`write:seo`) update allowlisted SEO meta keys.

### Media (optional)
- `GET /media` (`read:media`) list/search attachments.
- `POST /media` (`write:media`) upload media.
- `POST /media/attach` (`write:media`) attach media to post.

### Audit and rollback
- `GET /audit` (`admin:audit`) query logs by date/route/key.
- `GET /audit/{id}` (`admin:audit`) fetch detailed before/after/diff.
- `POST /rollback/option` (`write:theme`/`write:seo`) rollback option snapshot.

## 2.7 Security Controls
- Enforce HTTPS with early rejection if not TLS.
- API key hashing: `password_hash`, verify with `password_verify`.
- Scope checks per endpoint.
- Rate limiting per key via transients and optional DB counters.
- Optional IP allowlist per key.
- Request replay protection with signed timestamp window (if HMAC mode enabled).
- Strict input validation with `rest_validate_value_from_schema` and per-field sanitizers.
- Output escaping and no secrets in responses/logs.
- Debug logs only under `WP_DEBUG` and no sensitive payloads.

## 2.8 File-by-File Implementation Plan

### Phase 0: Scaffolding
1. Create `plugins/chroma-agent-api/chroma-agent-api.php`.
2. Create `plugins/chroma-agent-api/includes/class-bootstrap.php`.
3. Register activation hook for DB migrations.

### Phase 1: Auth and RBAC
1. `includes/class-key-store.php` for key create/verify/revoke/rotate.
2. `includes/class-auth.php` middleware for bearer parsing and scope check.
3. `includes/class-rate-limiter.php` transient limiter.
4. `includes/class-audit-log.php` write immutable logs.

### Phase 2: REST route framework
1. `includes/routes/class-discovery-routes.php`.
2. `includes/routes/class-key-routes.php`.
3. `includes/routes/class-content-routes.php`.
4. `includes/routes/class-theme-routes.php`.
5. `includes/routes/class-seo-routes.php`.
6. `includes/routes/class-media-routes.php`.
7. `includes/routes/class-audit-routes.php`.

### Phase 3: Safety (dry-run, diff, rollback)
1. `includes/class-diff.php` for before/after diffs.
2. `includes/class-snapshot-store.php` for option/theme snapshots.
3. Wire `dry_run=true` for all write endpoints.
4. Add revision restoration for content rollback.

### Phase 4: Compatibility wiring
1. Add read/write allowlists for theme mods/options.
2. Add SEO meta allowlist (translation/schema/meta keys).
3. Keep existing endpoints unchanged.

### Phase 5: Operations
1. Add WP-CLI commands in `includes/class-cli.php`:
   - `wp chroma-agent key create`
   - `wp chroma-agent key revoke`
   - `wp chroma-agent key rotate`
2. Add admin tools screen for key/audit inspection (optional).
3. Add feature flag option `chroma_agent_api_enabled`.

## 2.9 Testing Plan
- Unit tests:
  - key hashing/verification
  - scope evaluation
  - rate limiter behavior
  - sanitizer/validator correctness
- Integration tests:
  - authenticated reads
  - write with `dry_run=true` (no persisted mutation)
  - write with `dry_run=false` (mutation + audit record)
  - rollback endpoints
  - rejection on missing/insufficient scopes
- Security tests:
  - invalid key
  - expired/revoked key
  - replayed signed request
  - over-rate requests (429)

## 2.10 Rollout and Rollback
- Rollout:
  1. Deploy plugin disabled by default.
  2. Enable on staging.
  3. Run endpoint smoke tests.
  4. Enable production with read-only scopes first.
  5. Gradually enable write scopes.
- Rollback:
  1. Toggle `chroma_agent_api_enabled` off.
  2. Revoke keys.
  3. Restore changed content via revisions.
  4. Restore changed options via snapshots.

## 2.11 Risks and Notes
- Namespace collision risk in `chroma/v1` already exists; keep new API under `chroma-agent/v1`.
- Duplicate route declaration for `/chroma/v1/citation-facts` exists in both SEO plugin directories; keep only one active plugin copy in production.
- Backup files `old_controller.php` and `temp_old_controller.php` contain stale endpoint code; avoid loading these files.
