# QA Reports Audit: PDF Pipeline

## Pipeline Overview
1. **Trigger**: User clicks "Download PDF" (FE) -> `GET /reports/{id}/pdf`.
2. **Data Gathering**: `REST_Controller` fetches `Report`, `School`, `Responses`, and `AI Summary`.
3. **HTML Rendering**: `PDF_Generator::get_report_html` uses PHP buffer to render a standalone HTML document with inline CSS.
4. **Engine Selection**:
    - **A**: `TCPDF` (if class exists)
    - **B**: `Dompdf` (if class exists)
    - **C**: **Fallback**: Return raw HTML file path.
5. **Streaming**: `REST_Controller` streams the file to the browser.

## Current Pipeline Blockers
- **Missing Engines**: Neither TCPDF nor Dompdf are bundled in the current repo.
- **Mime Inconsistency**: If the fallback (HTML) is used, the browser may try to render it as a webpage instead of a document download, leading to "Broken Output" reports by users.
- **Resource Limits**: Generating complex PDFs with many Base64 images (Evidence Photos) is prone to PHP Memory Limit (`memory_limit`) and Execution Timeout (`max_execution_time`) errors on shared hosting.

## Recommended Fixes
1. **Mandatory Dompdf**: Force installation via Composer.
2. **Image Optimization**: Pass photo paths to Dompdf instead of Base64 strings to reduce memory footprint.
3. **Deterministic Output**: Remove the HTML fallback; if PDF generation fails, return a standard WP_Error with a 500 status.
