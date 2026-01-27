## Session Artifact: QA Reports API metadata update

### What was done
- Updated the Schools REST list endpoint to request report metadata for each school (`include_report_meta`).
- Included `address` as an alias for `location` in school responses.
- Added `last_inspection_date` and `reports_count` to the school response payload.
- Extended the School model to support optional joins that fetch report metadata in one query and hydrate it into model instances.

### Files updated
- `plugins/QA-Report-App/chroma-qa-reports/includes/api/class-rest-controller.php`
- `plugins/QA-Report-App/chroma-qa-reports/includes/models/class-school.php`
