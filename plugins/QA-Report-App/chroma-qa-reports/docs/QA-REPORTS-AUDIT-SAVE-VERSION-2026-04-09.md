# QA Reports Audit: Blank Overwrite and Missing History

Date: 2026-04-09

## Scope

This audit focuses on two reported symptoms:

1. Opening an existing QA report eventually overwrites saved data with blanks.
2. Historical versions are not being saved or cannot be restored reliably.

This review is based on the current codebase. It was not runtime-validated in a live WordPress instance during this pass.

## Active Execution Path

The active production path is the legacy PHP/jQuery admin flow, not the React flow.

- `admin/class-admin-menu.php`
- `admin/views/report-create.php`
- `admin/js/report-wizard.js`
- `admin/views/report-view.php`
- `admin/js/version-history.js`

`Admin_Menu::is_react_enabled()` currently forces `dashboard`, `reports`, `schools`, `wizard`, and `settings` to `false`, so the legacy wizard is the first priority for both symptoms.

## Issue 1: Saved Data Gets Overwritten With Blanks

### Confirmed Causes

1. `previous_report_id` can be blanked on edit-page load.
   - `admin/views/report-create.php` renders the correct selected `previous_report_id`.
   - `admin/js/report-wizard.js` calls `loadSchoolReports()` during `init()`.
   - `loadSchoolReports()` rebuilds `#previous_report_id` from AJAX results and does not restore the saved selection.
   - Result: the DOM can lose the previously linked baseline report before the user saves anything.
   - On the next autosave or manual save, `collectStepData(1)` reads the blank DOM value and sends `previous_report_id: null`.

2. The legacy wizard does not send any concurrency/version header on save.
   - `admin/js/admin-scripts.js` sends plain `POST` and `PUT` requests with only `X-WP-Nonce`.
   - `includes/api/class-rest-controller.php::update_report()` only enforces conflict protection if `If-Unmodified-Since` or `X-CQA-Version` is sent.
   - Result: any stale tab or second user session can overwrite current server state with whatever is in the currently open page, including blanked fields.

3. The legacy save path rebuilds payload from the DOM on every save.
   - `admin/js/report-wizard.js::buildReportPayload()` always calls `collectStepData(1)`, `collectStepData(2)`, and `collectStepData(5)`.
   - If the DOM has already drifted from persisted state, the next autosave or manual save persists the drift.

### High-Probability Causes

1. Self-linking or wrong-linking of `previous_report_id`.
   - PHP setup excludes the current report from the comparison dropdown.
   - JS `loadSchoolReports()` uses `/schools/{id}/reports` and does not exclude the current report.
   - The list can be rebuilt with the current report present and the original selection lost.

2. Stale page autosave after background edits.
   - Because the legacy wizard has autosave every 30 seconds and no version lock, simply leaving an older edit page open is enough to overwrite newer data later.

3. Any future field that is pre-rendered in PHP but re-rendered in JS without restoring selection/value will behave like `previous_report_id`.

### Lower-Probability But Real Possibilities

1. School filter can indirectly clear the currently selected school.
   - `filterSchools()` rebuilds the school select options.
   - If the selected value is filtered out, the select falls back to the placeholder.
   - A subsequent save could submit `school_id: 0`.

2. Hidden-step field drift.
   - The wizard reads step 5 fields even when the user is not on step 5.
   - The current markup should preserve those values, but any future DOM refactor could cause empty hidden controls to overwrite saved content.

3. Runtime DOM/plugin interference.
   - Browser autofill, third-party admin scripts, or custom admin JS could trigger unexpected `change` events and mark the form dirty.

### Things That Look Risky But Are Currently Mitigated

1. Checklist responses are less likely to be wiped by opening alone.
   - `collectChecklistResponses()` returns existing in-memory responses when step 2 is not loaded.
   - `update_report()` only calls `Checklist_Response::bulk_save()` if `responses` is a non-empty array.
   - Missing responses are therefore usually omitted from payload, not replaced with an empty object.

2. `loadSavedResponses()` has a race window, but `nextStep()` blocks navigation while hydration is in progress.
   - This reduces, but does not eliminate, open/save race conditions.

## Issue 2: Historical Versions Are Not Saved or Cannot Be Restored

### Confirmed Causes

1. Version history UI calls the wrong endpoint.
   - `cqaAdmin.restUrl` is already `.../wp-json/cqa/v1/`.
   - `admin/js/version-history.js` appends `cqa/v1/...` again.
   - Result: load, compare, and restore calls point at the wrong URL and can fail even if snapshots exist.

2. The compare UI reads the wrong response shape.
   - API returns raw snapshot rows with `snapshot_data`.
   - `admin/js/version-history.js` expects `response.snapshot.data`.
   - Result: comparisons can render empty or broken even when snapshot rows are returned.

3. Snapshots are created only before updates, not after saves.
   - `includes/models/class-report.php::save()` calls `Report_Snapshot::create_snapshot()` before updating an existing report.
   - No snapshot is created for the newly saved current state.
   - Result: the snapshots table always lags behind the live report by one save.

4. No snapshot exists on initial report creation.
   - `Report::save()` only snapshots when `$this->id` already exists.
   - Result: a newly created report has no version history until the second save.

5. The version-history UI labels the newest snapshot as "Current" even though it is not the live current state.
   - `admin/js/version-history.js` treats `versions[0]` as current.
   - `includes/api/class-rest-controller.php::get_report_versions()` separately returns the live report `current_version`.
   - Result: the UI can show a stale snapshot as the current version.

6. Many content changes bypass snapshot creation entirely.
   - Checklist responses can be saved via `/reports/{id}/responses`.
   - AI summary changes are saved via `Executive_Summary::save_summary()`.
   - Photo uploads, photo edits, and photo deletes use separate endpoints and `Photo::save()`.
   - None of those paths create a `Report_Snapshot`.
   - Result: history is incomplete even when the report row itself versions correctly.

7. The main update path snapshots before responses and summary are saved.
   - `update_report()` calls `$report->save()` first.
   - Then it saves responses.
   - Then it saves AI summary POI changes.
   - Result: the snapshot taken for that save does not reflect the final post-save state.

8. Restore is only partial.
   - `Report_Snapshot::restore()` restores:
     - `overall_rating`
     - `closing_notes`
     - `status`
     - checklist responses
   - It does not restore:
     - `school_id`
     - `report_type`
     - `inspection_date`
     - `previous_report_id`
     - photos
     - AI summary
   - Result: "restore" can succeed technically while still not returning the full historical state.

9. Restore does not clear responses when the target snapshot has none.
   - `restore()` only calls `Checklist_Response::bulk_save(..., true)` if `data['responses']` is non-empty.
   - Result: restoring to an older mostly-empty report cannot remove later-added responses.

10. Restore ignores write failures.
    - `Report_Snapshot::restore()` does not check the return value of:
      - `$report->save(...)`
      - `Checklist_Response::bulk_save(...)`
    - Result: restore can commit and return success after partial failure.

11. Snapshot creation failures are silent from the save path.
    - `Report::save()` does not check whether `Report_Snapshot::create_snapshot()` succeeded.
    - Result: report saves can continue normally while no history row is written.

### High-Probability Causes

1. Snapshot table exists but history appears empty because the UI is broken.
   - This is likely if users say history is missing but the DB actually contains rows.

2. Users expect the latest saved state to appear as a version immediately.
   - The current design only stores the pre-update state.
   - Even a functioning UI will feel "one version behind."

3. Users expect restore to bring back photos and AI summary.
   - Current restore cannot do that.

### Lower-Probability But Real Possibilities

1. `cqa_report_snapshots` table was never created or failed during activation/upgrade.
   - `includes/class-activator.php` creates the table.
   - `includes/class-upgrade-manager.php` reruns activation logic on version upgrade.
   - Environments with failed activation or schema drift can still be missing the table.

2. Duplicate version numbers under concurrent saves.
   - `Report_Snapshot::get_latest_version()` uses `MAX(version_number)` with no locking.
   - Table schema has only a non-unique key on `(report_id, version_number)`.
   - Concurrent saves can race and produce duplicate version numbers.

3. Version pruning removes older history earlier than users expect.
   - Retention defaults to 20 snapshots in `Report_Snapshot::MAX_VERSIONS`.
   - Daily cleanup prunes historical versions via `Cleanup_Service`.

4. Missing automated test coverage let version regressions ship unnoticed.
   - No snapshot/version-history-specific tests were found in `tests/php`, `tests/js`, or `tests/e2e`.

## Verification Checklist

### Blank Overwrite

1. Open an existing draft with a non-null `previous_report_id`.
2. Before touching the page, inspect `#previous_report_id` in the DOM.
3. Wait for `loadSchoolReports()` to finish and inspect the same field again.
4. Confirm whether the selected option was lost before any save occurred.
5. Make one unrelated change and save.
6. Confirm whether `previous_report_id` becomes `NULL` in the database.
7. Repeat with two tabs open and save from each tab in sequence.
8. Confirm whether the older tab overwrites newer changes without conflict.

### Version History

1. Save a report once after creation.
2. Check `cqa_reports.version_id`.
3. Check `cqa_report_snapshots` rows for that report.
4. Confirm whether the first save after creation produced zero snapshots.
5. Save again and confirm whether the snapshot row reflects the pre-update state rather than the latest current state.
6. Open report view and inspect browser network calls for `/versions`, `/versions/{n}`, and `/restore/{n}`.
7. Confirm whether the UI is calling `.../cqa/v1/cqa/v1/...`.
8. Restore a historical version that differs in:
   - `previous_report_id`
   - `inspection_date`
   - photos
   - AI summary
9. Confirm which parts restore and which parts remain current.
10. Attempt restoring a snapshot with empty responses and confirm whether later responses remain in place.

## Priority Order For Fixing

1. Fix legacy version-history URLs and response parsing so the UI can read actual history.
2. Fix `loadSchoolReports()` to preserve the selected `previous_report_id` and exclude the current report.
3. Add optimistic locking to legacy saves by sending `X-CQA-Version` or `If-Unmodified-Since`.
4. Redesign snapshot timing so a save records the final post-save state, not only the pre-update state.
5. Expand restore to cover all historically important fields, including photos and AI summary.
6. Make restore fail hard on partial-save errors instead of returning success.
7. Add tests for:
   - edit-page hydration
   - compare dropdown preservation
   - version creation on first save
   - version list rendering
   - restore completeness

## Bottom Line

Both reported symptoms are credible from the current code.

- The blank-overwrite problem is most strongly explained by the legacy wizard rebuilding selected fields on load and then saving without any version lock.
- The missing-history problem is a combination of storage gaps, stale snapshot design, and a broken legacy history UI.

This means the user report is not pointing to one bug. It is pointing to a chain of related save/versioning defects across the active legacy flow.
