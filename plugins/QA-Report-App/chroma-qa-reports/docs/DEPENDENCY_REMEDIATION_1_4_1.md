# QA 1.4.1 dependency and test-harness follow-up

Date: 2026-09-20. Continues draft PR 75 from `5213c57efbd8bd9c9db8f2672d5494cf39fa1250`. No deployment, production records, credentials, or original-checkout changes.

## Changes and compatibility

- Repaired the four existing PHPUnit harness/fixture errors: added the WordPress-default `sanitize_key` test shim, asserted actual global table-prefix behavior instead of expecting a nonexistent `wpdb()` function, and supplied the school fixture's required acquisition/update columns. No tests, assertions, error conversion or gates were suppressed; assertions were strengthened.
- Updated Dompdf 2.0.8 to 3.1.6, the upstream patched release for all six reported advisories. The plugin uses the unchanged `Dompdf`, `loadHtml`, `setPaper`, `render`, and `output` APIs. Composer resolves against the plugin's existing PHP 8.0 minimum instead of the developer host's PHP 8.3; compatible `thecodingmachine/safe` 2.5.0 and dev-only `doctrine/instantiator` 1.5.0 prevent an accidental PHP 8.1 requirement. No PDF-generation production code or remote/PHP execution setting was changed.
- Updated root npm dependencies within existing declared ranges. Updated build-env's lock within existing ranges and added only same-major overrides for pinned vulnerable `minimatch` 3/9, `tar-fs` 2/3, `ws` 8, and `qs` 6. No `--force`, audit exclusions, advisory ignores, removed dependencies, or changed severity threshold.
- Added a real-renderer synthetic export test exercising the actual plugin export API, comparison/plain output, valid PDF bytes, and remote-fetch/PHP-disabled defaults. Both one-page A4 fixtures were rendered to PNG and visually inspected. This is a small fixture compatibility check, not a full production report/photo/SSO acceptance test.

Upstream references: [Dompdf 3.0 compatibility/release notes](https://github.com/dompdf/dompdf/releases/tag/v3.0.0), [Dompdf 3.1.6 security fixes](https://github.com/dompdf/dompdf/releases/tag/v3.1.6).

## Final gates

PHP commands used WSL PHP 8.3.6 with isolated `-d extension=...` flags for dom, mbstring, xml and xmlwriter (no system installs or ignored platform requirements).

| Command | Result |
| --- | --- |
| `php vendor/bin/phpunit --do-not-cache-result` | Exit 0; 71 tests, 503 assertions, no errors/failures |
| `php vendor/bin/phpunit --testsuite Export --do-not-cache-result` | Exit 0; 1 test, 17 assertions; real Dompdf output |
| PHP syntax on includes/admin/public/tests/php | Exit 0; all 62 PHP files |
| `npm test -- --runInBand --detectOpenHandles` | Exit 0; 2 suites, 15 tests |
| `php composer.phar validate --strict` | Exit 0 |
| `php composer.phar check-platform-reqs --no-dev` | Exit 0 on PHP 8.3.6; PHP 8.0 compatibility is resolver-checked, not runtime-tested |
| `php composer.phar audit --locked --no-interaction` | Exit 0; zero advisories (was 6) |
| `npm audit --audit-level=high` at plugin root | Exit 0; zero advisories (was 8) |
| `npm ci --ignore-scripts` for isolated build-env copy | Exit 0; 1,569 packages installed from updated lock |
| `npm run lint:js`, `npm run lint:css`, `npm run build` in that copy | Each exit 0; build retains existing 331 KiB vendor-size warning |
| `npm audit --package-lock-only --json` for updated build-env | Exit 1; 21 affected packages: 3 low, 8 moderate, 10 high, 0 critical (was 53 including 2 critical) |
| Diagnostic `npm audit --omit=dev --audit-level=high` | Exit 0 at high threshold but **2 moderate affected router packages remain**; this is not a substitute for the failing full audit |

The repository tracks 58,614 files under build-env/node_modules. They are deliberately unchanged rather than rewritten in this bounded packet. Validation used an isolated copy of the exact source/config/manifests and a clean dependency install; generated build assets were copied back. The updated package-lock.json is authoritative for clean builds. The old tracked dependency tree is not evidence of remediation and must not be reused or shipped as a clean dependency installation. Production bundles were rebuilt from the updated lock.

The fresh security-skill review-agent request failed at the agent-thread limit; preflight and candidate compatibility review were therefore separate local passes. Reviewed declared minimums, resolved ranges/origins, test assertions, unchanged renderer calls/defaults, and generated output. The earlier authorization packet did receive its independent source review.

## Residual findings and exact deferral reasons

The complete final audit, including every advisory URL, vulnerable range, transitive effect and proposed fix, is retained in [qa-141-build-env-audit.json](qa-141-build-env-audit.json). Counts are affected-package counts, not unique CVE counts. None are suppressed.

| Remaining dependency chain | Why it is not safely removed by this compatible update |
| --- | --- |
| react-router-dom / react-router 6.30.6 | GHSA-wrjc-x8rr-h8h6 and GHSA-337j-9hxr-rhxg are patched only in 7.18.0+. No patched 6.x release is available. A v7 runtime migration needs navigation, WordPress React-version compatibility and browser regression testing. The SSR advisory specifically excludes declarative mode, but the audit remains recorded; this is not a claim that both advisories are exploitable here. |
| @wordpress/scripts -> adm-zip 0.5.18 | Three archive allocation/path advisories require 0.6.1, outside scripts27's `^0.5.9` contract. Archive/package behavior needs an explicit compatible-toolchain migration, not an untested override across a 0.x minor boundary. |
| @wordpress/scripts -> cross-spawn 5.1.0 | GHSA-3xgq-45jj-v275 fixes begin at 6.0.6/7.0.5, outside the declared `^5.1.0` API range; Windows process-spawn compatibility must be tested in a tooling migration. |
| scripts -> puppeteer-core / @puppeteer/browsers -> extract-zip 2.0.1 | GHSA-jmr9-qjv8-65gv and GHSA-7pqw-9j4j-h8q3 affect all released extract-zip versions (`*`). There is no in-place patched version; remove/replace the owning browser-download chain via its supported upstream migration. |
| scripts -> e2e-test-utils-playwright -> lighthouse -> @sentry/node -> cookie 0.4.2 | Cookie fix begins at 0.7.0, outside this chain's old contract. Upgrade the owning Lighthouse/Sentry/E2E chain with browser integration coverage instead of silently overriding serialization semantics. |
| scripts -> markdownlint-cli -> markdownlint -> markdown-it 12.3.2 -> linkify-it 3.0.3 | No patched releases in the pinned parser generations; patched markdown-it/linkify-it require parser-major/tooling changes. Markdown parsing/lint contracts need migration testing. |
| scripts -> copy-webpack-plugin -> serialize-javascript 6.0.2 | GHSA-5c6j-r48x-rmvq and GHSA-qj8w-gfj5-8c6v require versions beyond scripts27/copy10's `^6.0.0` serialization dependency. Upgrade the supported plugin/toolchain, not its serialization implementation blindly. |
| scripts -> webpack-dev-server 4.15.2 -> sockjs -> uuid 8.3.2 | Dev-server security fixes require v5 changes; uuid's fix begins at 11.1.1, outside SockJS's `^8.3.2` range. Changes affect development transport and config, not just a patch version. Full advisory URLs appear in the audit artifact. |

The audit's suggested scripts35 upgrade changes ESLint 8 to 10, Jest 29 to 30, stylelint 14 to 17 and the Node minimum, while this project uses legacy `.eslintrc.js`/WordPress configs. It also does not inherently remove every residual dependency chain. A forced major upgrade is not a demonstrated safe compatible fix.

Outcome remains **verification blocked**, not fully fixed/release-ready: full build-env audit is red. WordPress integration/UAT, account-scope provisioning and direct-media/Drive/export/CDN confidentiality gates from the original security record also remain open. No production deployment occurred.
