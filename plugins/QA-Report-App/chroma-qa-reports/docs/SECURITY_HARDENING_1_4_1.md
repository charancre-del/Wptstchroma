# QA 1.4.1 authorization hardening

Baseline: Wptstchroma `162a79b5d76c540b4eed7e15c062a017f7b5447a`.

Latest dependency/harness results: [dependency remediation follow-up](DEPENDENCY_REMEDIATION_1_4_1.md). The follow-up resolves the four initial PHPUnit errors and clears Composer/root npm audits; build-env, staging and direct-media gates remain open.

## Invariants and compatibility

Every interactive QA data request requires an active account, an applicable capability, and an explicit data scope. Own-report capabilities additionally require authorship. Broad view/edit capabilities do not imply global school access. Approval requires `cqa_approve_reports` through creation, updates, workflow, legacy actions, and both restore paths. Historical school and previous-report references must also satisfy current scope.

`Access_Policy::guard_rest` is installed at `rest_request_before_callbacks`, before any cqa/v1 controller callback, including analytics, workflow, AI, location and Monday modules. Unknown route families fail closed. Model collection/count queries apply identical scope predicates, including their report metadata. Single-record model lookups remain internal primitives; interactive callers must use the policy. Explicit WordPress scheduler and CLI contexts retain system collection access for maintenance jobs; URL parameters cannot select that context.

The 1.4.1 versioning, concurrency responses, report/school features, approval hooks, PDF, AI, photo upload, and Monday synchronization remain in this lineage. Google Picker file selection now requires the authenticated user's Drive metadata to identify an image in the selected school's configured Drive folder. Arbitrary `wp_` attachment aliases cannot be submitted as Drive file IDs. Photo-analysis API clients must send `photo_id` or `photo_ids` instead of arbitrary image URLs/paths; the server resolves the authorized image records.

## Account provisioning before an approved rollout

No deployment or live account migration is included. Review existing QA account assignments before release. All non-WordPress-administrator accounts require `cqa_account_status=active` and at least one of:

- `cqa_school_id`: one positive school ID, or `cqa_school_ids`: an array of positive school IDs.
- `cqa_region`: an exact stored school region, or `cqa_regions`: an array of exact region values.
- `cqa_scope=global` together with `cqa_view_all_reports`: explicit organization-wide authorization.

Use the existing controlled WordPress administration/CLI process to assign these values and the least necessary QA role. Do not derive identities or grants from names or email alone. Empty or missing assignments deny access. A status other than `active` denies access. Existing WordPress administrators with no QA account status retain recovery access; an explicit pending/disabled state still denies them. New SSO identities always remain subscribers with `pending_approval`, regardless of stored auto-approval/default-role settings.

Regional approvers can review and approve reports inside their assigned scope. General editors without approval capability cannot approve. School scope changes revoke even an author's access to reports outside the new assignment. Administrative integration mappings and settings remain restricted to global settings administrators.

## Credentials

Settings responses return `*_configured` booleans instead of stored credential values. Blank, whitespace and mask-only updates preserve both consolidated and legacy values. There is no implicit delete-by-blank behavior. Review or rotate previously exposed Google OAuth, Gemini and Monday credentials through the existing secret-management process; no credentials are read or rotated by this change.

The generic admin bootstrap no longer exposes `cqa_google_developer_key`. Google Picker can use the separate `cqa_google_picker_browser_key` option, visible only to scoped report creators; provision a referrer/API-restricted browser credential if Picker is needed. Existing server credential values are not copied into this option.

## Outstanding deployment boundary

QA photos may exist as ordinary WordPress media files and Drive files; generated PDF/HTML files use `uploads/cqa-temp`. Application-route authorization alone cannot protect direct storage URLs, web-server aliases, CDN caches, or Drive sharing rules. Before production release, verify anonymous and cross-school direct URL denial (including cached copies), choose private storage/authenticated delivery, and validate cleanup/retention. Do not claim this source change proves media-hosting confidentiality.

An isolated staging WordPress instance with synthetic accounts must exercise REST cookie/nonce and legacy forms, SSO provisioning, scoped SQL results, exports, and stubbed provider failures before rollout. Unit tests do not establish live deployment readiness. Record approval, artifact digest, rollback package, and account-assignment evidence separately.

## Initial authorization-only verification record (2026-09-20, commit 5213c57ef)

Outcome: implemented, **verification blocked**; this is not a release-ready or fully verified finding closure.

An independent read-only candidate review identified and the patch addressed REST case aliases, URL versus effective-parameter shadowing, normalized admin page aliases, a retained legacy secret input, and unchanged-region edits for school-scoped managers. The final source review found no additional concrete surviving bypasses. Route normalization follows WordPress's [case-insensitive dispatcher](https://developer.wordpress.org/reference/classes/wp_rest_server/match_request_to_handler/), [parameter precedence](https://developer.wordpress.org/reference/classes/wp_rest_request/get_parameter_order/), and normalized `plugin_page`. Real WordPress integration remains a required gate.

Commands run from this plugin directory unless noted:

| Check | Result |
| --- | --- |
| PHP 8.3 `find includes admin public tests/php -name '*.php' -print0` piped to `xargs -0 -n 1 php -l` | Exit 0; all 61 PHP files pass syntax |
| `php vendor/bin/phpunit --testsuite Security --do-not-cache-result` | Exit 0; 48 tests, 387 assertions |
| `php vendor/bin/phpunit --do-not-cache-result` | Exit 1; 70 tests, 442 assertions, four existing harness/fixture errors below |
| `npm ci --ignore-scripts` | Exit 0; test dependencies installed from unchanged lock |
| `npm test -- --runInBand --detectOpenHandles` | Exit 0; 2 suites, 15 tests |
| `npm run lint:js` in `build-env` | Exit 0; baseline StepReview indentation corrected without logic changes |
| `npm run lint:css` in `build-env` | Exit 0 |
| `npm run build` in `build-env` | Exit 0; existing large-vendor-chunk and stale Browserslist warnings remain |
| `php composer.phar install --no-interaction --prefer-dist` | Exit 0; unchanged lock, isolated WSL extension files, no system install |
| `php composer.phar audit --locked --no-interaction` | Exit 1; six dompdf advisories (four medium, two low) in unchanged lock |
| `npm audit --audit-level=high` | Exit 1; 8 advisories (1 low, 1 moderate, 6 high) in unchanged lock |
| `npm audit --audit-level=high --json` in `build-env` | Exit 1; 53 advisories (6 low, 14 moderate, 31 high, 2 critical) in unchanged lock |
| `git diff --cached --check` and staged credential-pattern scan | Exit 0; no whitespace errors or AWS/Google/GitHub/private-key/OpenAI/JWT credential-pattern matches; all 27 staged files inside this plugin |

PHPUnit and Composer used PHP `-d extension=...` flags for `dom`, `mbstring`, `xml`, and `xmlwriter`, extracted under an isolated `/tmp/cqa-php-*/root/usr/lib/php/20230831` directory. No extension requirement was bypassed.

The four unchanged existing-suite errors are two `ReportVersioningRegressionTest` cases missing the `sanitize_key()` WordPress test shim; `SchoolTest::test_get_table_name` expecting a nonexistent `wpdb()` function call; and `SchoolTest::test_from_row_factory` omitting `acquired_date`. These tests are retained and failures are not suppressed. The PHPUnit configuration now includes the actual Security directory instead of three nonexistent test directories; the existing Models suite remains intact.

Focused tests run the real central policy with synthetic accounts/records and a read-only fixture database. They cover inactive/unscoped/anonymous denial across 34 route families, scope/ownership/approval locks, full and selective restore, same-school linkage, shadowed identifiers, case and admin aliases, SQL list/count predicates, photo-AI records, Drive attachment aliases, secret read/update behavior and authorized controls. Legacy HTML secret masking additionally has a source-contract assertion. This is not a live SQL, dispatcher, SSO or external-provider integration test.

Remaining gates: repair the existing test harness in its own bounded change; remediate the existing dependency advisories; execute synthetic WordPress integration/UAT; provision explicit account scopes and the separate restricted Picker browser key; protect direct media/export storage. No live records, credentials, sends, account changes or deployment were performed.
