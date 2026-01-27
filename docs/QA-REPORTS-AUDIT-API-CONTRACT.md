# QA Reports Audit — API Contract (Source of Truth)

**Namespace:** `/wp-json/cqa/v1`

## Auth & Headers
- **Auth:** WordPress cookie + `X-WP-Nonce`
- **Standard error format:** `WP_Error` with `code`, `message`, `status`

---

## 1) User
### `GET /me`
**Purpose:** Current user profile, capabilities, flags.
**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 123,
    "name": "Jane Doe",
    "email": "jane@example.com",
    "role": "cqa_qa_officer",
    "capabilities": {
      "cqa_view_own_reports": true,
      "cqa_create_reports": true
    },
    "flags": {
      "cqa_flag_dashboard": true
    },
    "googleConnected": false,
    "nonceExpiresAt": 1730000000
  }
}
```

---

## 2) Schools
### `GET /schools`
**Params:** `per_page`, `page`, `status`, `region`, `search`, `orderby`, `order`
**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "School A",
      "location": "City, ST",
      "region": "East",
      "tier": 1,
      "acquired_date": "2023-01-01",
      "status": "active",
      "drive_folder_id": "...",
      "classroom_config": [],
      "created_at": "2024-01-01 12:00:00",
      "last_inspection_date": "2024-02-01",
      "reports_count": 10
    }
  ],
  "meta": {
    "total": 100,
    "total_pages": 10,
    "current_page": 1,
    "per_page": 10
  }
}
```

### `GET /schools/{id}`
**Response (200):**
```json
{
  "id": 1,
  "name": "School A",
  "location": "City, ST",
  "region": "East",
  "tier": 1,
  "acquired_date": "2023-01-01",
  "status": "active"
}
```

### `POST /schools`
**Body:** `name`, `location`, `region`, `acquired_date`, `status`, `drive_folder_id`, `classroom_config`

### `PUT /schools/{id}`
Same fields as POST.

### `DELETE /schools/{id}`
Returns 204.

---

## 3) Reports
### `GET /reports`
**Params:** `per_page`, `page`, `school_id`, `report_type`, `status`, `search`, `orderby`, `order`, `author=me`
**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 99,
      "school_id": 1,
      "user_id": 5,
      "author_name": "Jane Doe",
      "report_type": "tier1",
      "report_type_label": "Tier 1",
      "tier": 1,
      "inspection_date": "2024-03-01",
      "visit_date": "2024-03-01",
      "previous_report_id": 88,
      "overall_rating": "meets",
      "rating": "Meets",
      "status": "draft",
      "created_at": "2024-03-02 10:00:00",
      "updated_at": "2024-03-02 11:00:00",
      "school_name": "School A",
      "is_mine": true
    }
  ]
}
```

### `GET /reports/{id}`
**Response (200):** report object + details
```json
{
  "id": 99,
  "school_id": 1,
  "report_type": "tier1",
  "inspection_date": "2024-03-01",
  "responses": { "section_key": { "item_key": { "rating": "yes" } } },
  "photos": [
    { "id": 1, "section_key": "general", "thumbnail_url": "...", "view_url": "..." }
  ],
  "ai_summary": {
    "executive_summary": "...",
    "issues": [],
    "poi": []
  }
}
```

### `POST /reports`
**Body (required):** `school_id`, `inspection_date`, `report_type`
**Body (optional):** `previous_report_id`, `overall_rating`, `closing_notes`, `status`, `responses`, `photos`

### `PUT /reports/{id}`
**Body:** any report fields + `responses` for bulk save. Optional concurrency headers: `If-Unmodified-Since`, `X-CQA-Version`.

### `DELETE /reports/{id}`
Returns 204.

---

## 4) Responses
### `GET /reports/{id}/responses`
Returns grouped checklist responses.

### `POST /reports/{id}/responses`
**Body:** `{ "responses": {"section": {"item": {"rating": "yes"}}}}`

---

## 5) Photos
### `POST /reports/{id}/photos`
**Body:** `multipart/form-data` with `photos[]`.
**Response (200):**
```json
{ "success": true, "photos": [ { "id": 10, "filename": "..." } ] }
```

### `PUT /photos/{id}`
**Body:** `caption`, `section_key`

### `DELETE /photos/{id}`
**Response:** `{ "success": true }`

---

## 6) AI
### `POST /reports/{id}/generate-summary`
**Response:** JSON with `executive_summary`, `growth_opportunities`, `support_and_growth_plan`, `comparison`.

### `POST /ai/parse-document`
**Body:** `multipart/form-data` with `document`.

---

## 7) Checklists
### `GET /checklists/{type}`
Returns checklist definition: sections and items.

---

## 8) Settings
### `GET /settings`
Returns integration settings.

### `POST /settings`
Updates integration settings.

---

## 9) Stats
### `GET /stats`
Returns dashboard metrics, compliance, trend, and action items.

---

## 10) System Health
### `GET /system-check`
Returns connection diagnostics.
