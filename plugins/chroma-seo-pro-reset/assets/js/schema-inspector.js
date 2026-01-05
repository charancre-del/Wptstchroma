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
    // Target the specific admin bar node ID (and its link child) for robustness
    $(document).on('click', '#wp-admin-bar-chroma-validate-schema > .ab-item, .chroma-inspector-trigger', function (e) {
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
        let hasErrors = false;
        let itemsHtml = '';

        results.forEach(function (item) {
            const type = item.parsed && item.parsed['@type'] ?
                (Array.isArray(item.parsed['@type']) ? item.parsed['@type'][0] : item.parsed['@type'])
                : 'Unknown Type';

            const statusClass = item.valid ? (item.warnings.length ? 'warning' : 'valid') : 'invalid';
            const statusIcon = item.valid ? (item.warnings.length ? '⚠️' : '✅') : '❌';
            const statusText = item.valid ? (item.warnings.length ? 'Valid with Warnings' : 'Valid') : 'Invalid';

            if (!item.valid) hasErrors = true;

            itemsHtml += `
                <div class="chroma-schema-item">
                    <div class="chroma-schema-header ${statusClass}" data-index="${item.index}">
                        <span><strong>${statusIcon} ${type}</strong> <span style="color:#666; font-size:12px;">(${statusText})</span></span>
                        <span style="font-size:12px; color:#555;">Toggle Details ▼</span>
                    </div>
                    <div class="chroma-schema-details" id="detail-${item.index}">
            `;

            // Combine Errors and Warnings for Fixing
            const allIssues = (item.errors || []).concat(item.warnings || []);
            const hasIssuesItem = allIssues.length > 0;

            if (hasIssuesItem) {
                // Determine header color
                if ((item.errors || []).length > 0) hasErrors = true;

                // Show Errors
                if (item.errors && item.errors.length) {
                    itemsHtml += '<h4>Errors</h4><ul class="chroma-error-list">';
                    item.errors.forEach(e => itemsHtml += `<li>${e}</li>`);
                    itemsHtml += '</ul>';
                }

                // Show Warnings
                if (item.warnings && item.warnings.length) {
                    itemsHtml += '<h4>Warnings</h4><ul class="chroma-warning-list">';
                    item.warnings.forEach(w => itemsHtml += `<li>${w}</li>`);
                    itemsHtml += '</ul>';
                }

                // Fix Button (Available for both errors and warnings)
                itemsHtml += `
                    <div style="margin-bottom:15px;">
                        <button class="chroma-fix-btn" data-schema="${encodeURIComponent(item.raw)}" data-errors="${encodeURIComponent(JSON.stringify(allIssues))}" data-index="${item.index}">
                            ✨ Auto-Fix with AI
                        </button>
                        <div class="fix-result-container" id="fix-result-${item.index}" style="display:none; margin-top:10px;"></div>
                    </div>
                `;
            } else {
                // Just Warnings (if any left over logic?) - actually handled above.
                // If valid and no warnings, show nothing extra.
            }

            // Raw JSON
            itemsHtml += `
                <h4>JSON-LD Source</h4>
                <div class="chroma-json-pre">${escapeHtml(item.raw)}</div>
                <button class="chroma-copy-btn" onclick="navigator.clipboard.writeText(decodeURIComponent('${encodeURIComponent(item.raw)}')); alert('Copied!');">Copy JSON</button>
            `;

            itemsHtml += `</div></div>`;
        });

        // Prepend Actions Toolbar if issues exist
        if (hasErrors) { // Keep red toolbar mainly for errors, or change to cover both?
            // User asked to fix warnings too, so let's allow bulk fix if there are errors OR warnings?
            // For now, let's keep "Fix All" focused on Errors to avoid overwhelming API costs on minor warnings
            // unless we want to expand scope. Let's stick to hasErrors for the big red button for now,
            // but the individual buttons now work for warnings.
            const toolbar = `
                <div style="margin-bottom:20px; padding:15px; background:#fff1f0; border:1px solid #ffccc7; border-radius:4px; display:flex; justify-content:space-between; align-items:center;">
                    <span style="color:#d32f2f; font-weight:bold;">⚠️ Issues Detected</span>
                    <button id="chroma-fix-all-btn" class="chroma-fix-btn" style="background:#d32f2f;">
                        ✨ Fix All Issues with AI
                    </button>
                </div>
            `;
            $results.append(toolbar);
        }

        $results.append(itemsHtml);
    }

    // Fix All Click
    $(document).on('click', '#chroma-fix-all-btn', function () {
        if (!confirm('This will process all schemas with errors sequentially. Continue?')) return;

        const $btn = $(this);
        $btn.prop('disabled', true).text('Processing...');

        // Find all fix buttons
        const $fixQueue = $('.chroma-fix-btn[data-index]');
        let currentIndex = 0;

        function processNext() {
            if (currentIndex >= $fixQueue.length) {
                $btn.text('✅ All Done!');
                alert('All AI fixes generated! Please review and apply them.');
                return;
            }

            const $currentBtn = $($fixQueue[currentIndex]);
            const btnIndex = $currentBtn.data('index');

            // Scroll to item
            $('html, body, #chroma-schema-modal-body').animate({
                scrollTop: $currentBtn.offset().top - 200
            }, 500);

            // Trigger click on individual button logic (simulated)
            $currentBtn.prop('disabled', true).html('<span class="chroma-spinner"></span> Fixing...');

            const schema = decodeURIComponent($currentBtn.data('schema'));
            const errors = JSON.parse(decodeURIComponent($currentBtn.data('errors')));

            $.post(ChromaInspector.ajaxUrl, {
                action: 'chroma_fix_schema_with_ai',
                nonce: ChromaInspector.nonce,
                schema: schema,
                errors: errors
            }, function (response) {
                $currentBtn.prop('disabled', false).text('✨ Auto-Fix with AI');

                if (response.success) {
                    const fixed = response.data.fixed_schema;
                    $('#fix-result-' + btnIndex).show().html(`
                        <div style="background:#e8fdf5; border:1px solid #46b450; padding:15px; border-radius:4px;">
                            <h4 style="margin-top:0; color:#2e7d32;">✅ AI Fixed Implementation</h4>
                            <p style="font-size:12px;">Replace the existing schema with this code:</p>
                            <div class="chroma-json-pre" style="max-height:300px; overflow-y:auto;">${escapeHtml(fixed)}</div>
                            <button class="chroma-copy-btn" onclick="navigator.clipboard.writeText(decodeURIComponent('${encodeURIComponent(fixed)}')); alert('Copied!');">Copy Fixed JSON</button>
                        </div>
                    `);
                    // Open details if closed
                    $('#detail-' + btnIndex).slideDown();
                }

                // Next
                currentIndex++;
                setTimeout(processNext, 500); // Small delay

            }).fail(function () {
                $currentBtn.prop('disabled', false).text('Failed');
                // Even if failed, try next
                currentIndex++;
                processNext();
            });
        }

        processNext();
    });

    // Fix with AI Click
    $(document).on('click', '.chroma-fix-btn:not(#chroma-fix-all-btn)', function () {
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
