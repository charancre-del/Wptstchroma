# Full Theme Audit Plan (Performance, Animation, UX, Regression)

## Scope
Audit the entire WordPress theme and integrated custom plugins for:
- Animation defects and jank
- Mobile/desktop layout breakage
- Script/CSS loading inefficiencies
- Accessibility regressions
- SEO/schema output correctness
- Functional regressions in portals/forms/dashboards
- Security/safety regressions in front-end and API-connected flows

## Audit Objectives
1. Identify root causes (not symptoms) with file/function references.
2. Rank issues by user impact and business risk.
3. Produce minimal, safe fix recommendations.
4. Define validation and rollback for every fix.

## Environments and Inputs
- Staging URL + production URL (read-only audit first)
- Lighthouse (mobile + desktop)
- Chrome DevTools Performance trace
- Query Monitor
- WordPress debug log (if enabled)
- Git diff of latest releases

## Page Matrix (Must Test)
- Home
- About
- Programs index + at least 2 program detail pages
- Locations index + 5 location pages
- Blog index + 3 recent post pages
- Contact
- Careers
- Schedule a Tour
- Parent Portal
- School Dashboard
- QA Reports login + wizard + print
- TV Dashboard

## Workstream A - Animation and Motion Audit
### Checks
- Entrance animations causing CLS/jumps
- Stagger sequences blocking main thread
- Scroll-linked effects causing frame drops
- Sticky/fixed CTA shifts on mobile
- Animations running before layout settles
- Transition conflicts at breakpoints

### Method
- Record 60fps timeline in DevTools on mobile viewport
- Inspect layout shift regions + style recalculation spikes
- Identify animation source file/selector/script handle

### Output
- Issue list with: selector/component, file path, trigger condition, FPS/CLS impact, fix.

## Workstream B - Layout and Responsive Stability
### Checks
- Overflow-x on mobile
- Unsized media (img/video/iframe/avatar)
- Late font swaps changing text metrics
- Inconsistent container widths between templates
- Hero and card sections without intrinsic size

### Method
- Validate at 375, 390, 430, 768, 1024, 1280, 1440 widths
- Capture before/after screenshots + CLS traces

### Output
- Component-by-component breakpoints table with PASS/FAIL.

## Workstream C - JavaScript/CSS Loading and Execution
### Checks
- Render-blocking CSS/JS in head
- Duplicate third-party scripts
- Route-irrelevant global bundles
- Large script eval/parse contributors
- Unused CSS chunks on critical templates

### Method
- Build script/style inventory by route
- Attribute CPU cost by file/handle
- Confirm defer/async policy and conditional enqueue logic

### Output
- Ranked optimization backlog with expected savings (ms, KB).

## Workstream D - Functional Regression
### Checks
- Forms submission (contact/tour/career)
- Careers sync status timestamp accuracy
- Weekly cron schedule existence and run behavior
- QA report save draft/submit/print consistency
- Parent portal login/password gate behavior

### Method
- Step-by-step smoke flow per feature
- Validate network + server response + UI state updates

### Output
- PASS/FAIL matrix with reproduction steps for failures.

## Workstream E - SEO and Structured Data Quality
### Checks
- JSON-LD duplication/conflicts
- Location schema (LocalBusiness/ChildCare) structure validity
- FAQ auto-injection behavior vs explicit schema blocks
- Person/Profile coverage on About and Location pages
- JobPosting schema freshness on career pages

### Method
- Crawl target pages and parse JSON-LD blocks
- Validate through schema rules (Google rich result relevant types first)

### Output
- Error/warning register with affected URLs and schema block IDs.

## Workstream F - Accessibility and UX Integrity
### Checks
- Keyboard interaction for nav/accordion/modal
- Focus visibility and trap behavior
- Color contrast on key CTA/content blocks
- Form labels/errors/aria for dynamic fields

### Output
- WCAG-focused defect list (AA baseline).

## Workstream G - Security and Safety Sanity
### Checks
- Nonce/capability checks for AJAX/REST writes
- Escaping/sanitization in rendered dynamic content
- No sensitive data leakage in front-end payloads/logs
- Third-party script injection scope and duplication risk

### Output
- Security findings with severity and minimal remediations.

## Severity Model
- BLOCKER: breaks core flow, data integrity, or security
- HIGH: major UX/SEO/performance impact in production paths
- MEDIUM: noticeable issue without core outage
- LOW: minor polish/edge-case issue

## Deliverables
1. Full findings report (ranked by severity)
2. Route-based script/style inventory
3. Animation + CLS root-cause map
4. Functional regression PASS/FAIL matrix
5. Patch plan with file paths and validation steps

## Execution Sequence
1. Baseline capture (metrics + route inventory)
2. Animation/layout audit
3. Script/CSS audit
4. Functional regression
5. Schema + SEO validation
6. Security sanity pass
7. Consolidated remediation plan

## Exit Criteria
- No BLOCKER/HIGH unresolved in core routes
- Mobile CLS target <= 0.10 on priority templates
- No unexpected render-blocking assets on critical routes
- Core forms/portals/dashboards pass regression matrix
- Schema critical errors resolved on core/program/location/career pages