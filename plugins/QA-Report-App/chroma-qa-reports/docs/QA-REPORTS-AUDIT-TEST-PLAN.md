# QA Reports Audit: Test Plan

## 1. Manual Verification Scripts

### Admin Role: Comprehensive Report Cycle
1. **Create**: Go to "Create Report". Select a school. Ensure "Next Step" is disabled until school is picked.
2. **Metadata**: Set a date. Verify `inspection_date` is sent in payload.
3. **Checklist**: Fill 10+ items. Verify "Save Draft" updates the database.
4. **Photos**: Upload a photo. Verify it appears in the gallery immediately (Frontend) and is stored in WP Media / Drive (Backend).
5. **AI Summary**: Click "Generate Summary". Verify the text appears. **CRITICAL**: Ensure no crash occurs on the Review step.
6. **Submit**: Click "Submit". Verify redirection to "All Reports" and status change to "submitted".

### Director Role: Approval Workflow
1. **View**: Open a "submitted" report.
2. **Approve**: Click "Approve". Verify status transitions to "approved".
3. **PDF**: Click "Download PDF". Verify the file opens and contains the data (not just HTML source).

## 2. API Verification (Curl)

### Nonce Validation
```bash
curl -X GET "https://yourdomain.com/wp-json/cqa/v1/me" \
     -H "X-WP-Nonce: YOUR_NONCE"
```
*Expected: 200 OK with user details.*

### Concurrency Conflict Test
1. User A opens Report #1.
2. User B opens Report #1 and saves.
3. User A attempts to save.
*Expected: 409 Conflict error with "Modified by User B" message.*

## 3. PDF Integrity Check
1. Generate PDF for a report with 5+ photos.
2. Verify:
    - [ ] Company logo is present.
    - [ ] Inspection date is correct.
    - [ ] Photos are rendered (not broken icons).
    - [ ] "Comparison" column shows changes if a previous report exists.

## 4. Regression Checklist
- [ ] Check if `cleanup_orphaned_media` deletes photos attached to "draft" reports (It should NOT).
- [ ] Verify `subscriber` users cannot access `/wp-json/cqa/v1/settings`.
- [ ] Verify large photo uploads (>5MB) do not time out the REST API.
