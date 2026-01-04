jQuery(document).ready(function ($) {
    const $body = $('body');
    const modalId = 'chroma-schema-modal';

    // Create Modal HTML
    if (!$('#' + modalId).length) {
        $body.append(`
            <div id="${modalId}">
                <div class="chroma-modal-content">
                    <div id="chroma-schema-modal-header">
                        <h2>🤖 Schema Inspector</h2>
                        <span id="chroma-schema-close">&times;</span>
                    </div>
                    <div id="chroma-schema-modal-body">
                        <div class="chroma-loading" style="text-align:center; padding: 40px;">
                            <span class="chroma-spinner" style="width:30px; height:30px;"></span>
                            <p>Analyzing Page Schema...</p>
                        </div>
                        <div id="chroma-schema-results"></div>
                    </div>
                </div>
            </div>
        `);
    }

    const $modal = $('#' + modalId);
    const $results = $('#chroma-schema-results');
    const $loading = $modal.find('.chroma-loading');

    // Open Modal
    $(document).on('click', '.chroma-inspector-trigger', function (e) {
        e.preventDefault();

        $modal.show();
        $results.empty();
        $loading.show();

        // 1. Scrape Schema
        const schemas = [];
        $('script[type="application/ld+json"]').each(function () {
            const content = $(this).html();
            if (content && content.trim()) {
                schemas.push(content);
            }
        });

        if (schemas.length === 0) {
            $loading.hide();
            $results.html('<p style="padding:20px; color:#666;">No JSON-LD schema found on this page.</p>');
            return;
        }

        // 2. Send to Backend
        $.post(ChromaInspector.ajaxUrl, {
            action: 'chroma_validate_page_schema',
            nonce: ChromaInspector.nonce,
            schemas: schemas
        }, function (response) {
            $loading.hide();

            if (response.success && response.data.results) {
                renderResults(response.data.results);
            } else {
                $results.html('<p style="color:red; padding:20px;">Error analyzing schema: ' + (response.data.message || 'Unknown error') + '</p>');
            }
        }).fail(function () {
            $loading.hide();
            $results.html('<p style="color:red; padding:20px;">Request failed. Please try again.</p>');
        });
    });

    // Render Results
    function renderResults(results) {
        results.forEach(function (item) {
            const type = item.parsed && item.parsed['@type'] ?
                (Array.isArray(item.parsed['@type']) ? item.parsed['@type'][0] : item.parsed['@type'])
                : 'Unknown Type';

            const statusClass = item.valid ? (item.warnings.length ? 'warning' : 'valid') : 'invalid';
            const statusIcon = item.valid ? (item.warnings.length ? '⚠️' : '✅') : '❌';
            const statusText = item.valid ? (item.warnings.length ? 'Valid with Warnings' : 'Valid') : 'Invalid';

            let html = `
                <div class="chroma-schema-item">
                    <div class="chroma-schema-header ${statusClass}" data-index="${item.index}">
                        <span><strong>${statusIcon} ${type}</strong> <span style="color:#666; font-size:12px;">(${statusText})</span></span>
                        <span style="font-size:12px; color:#555;">Toggle Details ▼</span>
                    </div>
                    <div class="chroma-schema-details" id="detail-${item.index}">
            `;

            // Errors
            if (item.errors && item.errors.length) {
                html += '<h4>Errors</h4><ul class="chroma-error-list">';
                item.errors.forEach(e => html += `<li>${e}</li>`);
                html += '</ul>';

                // Fix Button
                html += `
                    <div style="margin-bottom:15px;">
                        <button class="chroma-fix-btn" data-schema="${encodeURIComponent(item.raw)}" data-errors="${encodeURIComponent(JSON.stringify(item.errors))}" data-index="${item.index}">
                            ✨ Auto-Fix with AI
                        </button>
                        <div class="fix-result-container" id="fix-result-${item.index}" style="display:none; margin-top:10px;"></div>
                    </div>
                `;
            }

            // Warnings
            if (item.warnings && item.warnings.length) {
                html += '<h4>Warnings</h4><ul class="chroma-warning-list">';
                item.warnings.forEach(w => html += `<li>${w}</li>`);
                html += '</ul>';
            }

            // Raw JSON
            html += `
                <h4>JSON-LD Source</h4>
                <div class="chroma-json-pre">${escapeHtml(item.raw)}</div>
                <button class="chroma-copy-btn" onclick="navigator.clipboard.writeText(decodeURIComponent('${encodeURIComponent(item.raw)}')); alert('Copied!');">Copy JSON</button>
            `;

            html += `</div></div>`;
            $results.append(html);
        });
    }

    // Fix with AI Click
    $(document).on('click', '.chroma-fix-btn', function () {
        const btn = $(this);
        const container = $('#fix-result-' + btn.data('index'));
        const schema = decodeURIComponent(btn.data('schema'));
        const errors = JSON.parse(decodeURIComponent(btn.data('errors')));

        btn.prop('disabled', true).html('<span class="chroma-spinner"></span> Fixing...');

        $.post(ChromaInspector.ajaxUrl, {
            action: 'chroma_fix_schema_with_ai',
            nonce: ChromaInspector.nonce,
            schema: schema,
            errors: errors
        }, function (response) {
            btn.prop('disabled', false).text('✨ Auto-Fix with AI');

            if (response.success) {
                const fixed = response.data.fixed_schema;
                container.show().html(`
                    <div style="background:#e8fdf5; border:1px solid #46b450; padding:15px; border-radius:4px;">
                        <h4 style="margin-top:0; color:#2e7d32;">✅ AI Fixed Implementation</h4>
                        <p style="font-size:12px;">Replace the existing schema with this code:</p>
                        <div class="chroma-json-pre" style="max-height:300px; overflow-y:auto;">${escapeHtml(fixed)}</div>
                        <button class="chroma-copy-btn" onclick="navigator.clipboard.writeText(decodeURIComponent('${encodeURIComponent(fixed)}')); alert('Copied!');">Copy Fixed JSON</button>
                    </div>
                `);
            } else {
                alert('AI Fix Failed: ' + (response.data.message || 'Unknown error'));
            }
        }).fail(function () {
            btn.prop('disabled', false).text('✨ Auto-Fix with AI');
            alert('Request failed. Check console.');
        });
    });

    // Toggle Details
    $(document).on('click', '.chroma-schema-header', function () {
        $(this).next('.chroma-schema-details').slideToggle(200);
    });

    // Close Modal
    $(document).on('click', '#chroma-schema-close', function () {
        $modal.hide();
    });

    // Close on click outside
    $(window).on('click', function (e) {
        if ($(e.target).is($modal)) {
            $modal.hide();
        }
    });

    function escapeHtml(text) {
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
});
