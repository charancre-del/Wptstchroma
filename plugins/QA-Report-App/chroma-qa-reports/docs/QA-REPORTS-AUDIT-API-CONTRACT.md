# QA Reports Audit: API Contract

**Namespace**: `cqa/v1`  
**Base URL**: `https://{domain}/wp-json/cqa/v1`

## Authentication
All requests require a valid WordPress Nonce passed in the `X-WP-Nonce` header.

## Endpoints Summary

### 1. User Info (`GET /me`)
- **Response**:
```json
{
  "success": true,
  "data": {
    "id": 123,
    "name": "Jane Doe",
    "role": "cqa_qa_officer",
    "capabilities": { "cqa_create_reports": true, ... }
  }
}
```

### 2. Reports (`GET/POST /reports`)
- **POST Schema**:
```json
{
  "school_id": "int",
  "report_type": "string (tier1|tier1_tier2|new_acquisition)",
  "inspection_date": "YYYY-MM-DD",
  "responses": "object (section_key -> item_key -> rating/notes)",
  "status": "string (draft|submitted|approved)"
}
```
- **GET Response**: Array of Report objects.

### 3. Report Detail (`GET/PUT /reports/{id}`)
- **PUT Schema** (Supports Concurrency):
    - **Header**: `If-Unmodified-Since: {timestamp}` OR `X-CQA-Version: {int}`
- **Response**: Single Report object with `responses` and `photos` included.

### 4. Checklist Definition (`GET /checklists/{type}`)
- **Response**: Nested structure of sections and items.

## Known Contract Violations (Audit Findings)
1. **Field Alias**: FE uses `visit_date` but BE expects `inspection_date`.
2. **AI Summary Type**: BE sends `ai_summary` as `object`, FE code treats as `string`.
3. **Mime-Type**: `/reports/{id}/pdf` may return `text/html` instead of `application/pdf`.
