# QA Reports Audit — Test Plan

## 1) Manual Test Scripts (by Role)

### Admin (Super Admin / Administrator)
1. **Dashboard Stats**
   - Open QA Reports Dashboard.
   - Verify stats load without errors (schools, compliance, trends).
2. **Report Wizard (Create)**
   - Create report → select school → ensure “No Link” option persists.
   - Complete checklist, add photos, generate AI summary.
   - Save draft and reload; confirm responses/photos persist.
3. **Report Wizard (Edit/View)**
   - Edit existing report, upload and delete photos.
   - Ensure conflict modal shows correct last editor.
4. **Settings**
   - Open settings → update Google/Gemini keys → verify persistence.
5. **PDF**
   - Generate PDF from approved report → verify correct output.

### Regional Director / QA Officer
1. **Dashboard + Reports**
   - Verify personal reports list and filters.
2. **Photo Upload**
   - Upload photos to report; verify thumbnails display.
3. **Permission**
   - Attempt to access Settings (should be denied).

### Program Manager (View-only)
1. **Reports View**
   - Access report list and view report details.
2. **Permission**
   - Attempt photo edit/delete (should be denied).
   - Attempt settings access (should be denied).

---

## 2) API Tests (Curl Examples)

### Auth/User
- `GET /wp-json/cqa/v1/me`

### Schools
- `GET /wp-json/cqa/v1/schools?per_page=20&page=1`
- `POST /wp-json/cqa/v1/schools` with name/location

### Reports
- `GET /wp-json/cqa/v1/reports?per_page=20&page=1&status=approved`
- `POST /wp-json/cqa/v1/reports` with required fields
- `PUT /wp-json/cqa/v1/reports/{id}` to update status

### Responses
- `GET /wp-json/cqa/v1/reports/{id}/responses`
- `POST /wp-json/cqa/v1/reports/{id}/responses`

### Photos
- `POST /wp-json/cqa/v1/reports/{id}/photos` (multipart)
- `DELETE /wp-json/cqa/v1/photos/{id}`

### AI Summary
- `POST /wp-json/cqa/v1/reports/{id}/generate-summary`

### Settings (Admin only)
- `GET /wp-json/cqa/v1/settings`
- `POST /wp-json/cqa/v1/settings`

### Stats
- `GET /wp-json/cqa/v1/stats`

---

## 3) PDF Tests
1. Generate PDF for approved report.
2. Verify data mapping: school name, inspection date, ratings, checklist responses.
3. Validate images and comparison columns.
4. Confirm temp files cleaned (post-fix).

---

## 4) Regression Checklist
- Dashboard loads without errors.
- Wizard autosaves responses/photos.
- Photo upload/delete works for editors only.
- Settings inaccessible to non-admins.
- AI summary generates and persists edits.
- Reports/Schools lists use server-side pagination.
- PDF generation works and cleans temp files.
