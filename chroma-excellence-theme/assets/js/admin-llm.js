/**
 * Chroma LLM Admin JavaScript
 */
jQuery(function ($) {
    'use strict';

    // Schema Preview Generator
    window.ChromaSchemaPreview = {
        generate: function (schema) {
            if (!schema || !schema['@type']) return '';

            var type = schema['@type'];
            var html = '<div class="chroma-schema-preview"><div class="serp-result">';

            // Title
            html += '<div class="serp-title">' + (schema.name || 'Untitled') + '</div>';

            // URL
            html += '<div class="serp-url">' + (schema.url || window.location.href) + '</div>';

            // Description
            if (schema.description) {
                html += '<div class="serp-description">' + schema.description.substring(0, 160) + '...</div>';
            }

            // Rating
            if (schema.aggregateRating) {
                var rating = schema.aggregateRating;
                var stars = '★'.repeat(Math.round(rating.ratingValue)) + '☆'.repeat(5 - Math.round(rating.ratingValue));
                html += '<div class="serp-rating">' + stars + ' ' + rating.ratingValue + ' (' + rating.reviewCount + ' reviews)</div>';
            }

            // Hours
            if (type === 'LocalBusiness' || type === 'ChildCare') {
                html += '<div class="serp-hours">Hours: Mon-Fri 6am-6pm</div>';
            }

            html += '</div></div>';
            return html;
        }
    };

    // Confidence Bar Renderer
    window.ChromaConfidence = {
        render: function (score) {
            var percent = Math.round(score * 100);
            var level = percent >= 80 ? 'high' : (percent >= 50 ? 'medium' : 'low');

            return '<div class="confidence-bar">' +
                '<div class="fill ' + level + '" style="width: ' + percent + '%;"></div>' +
                '</div>' + percent + '%';
        }
    };

    // Bulk Operations Handler
    var BulkOps = {
        init: function () {
            $('#select-all-gaps').on('change', function () {
                $('.gap-checkbox').prop('checked', $(this).is(':checked'));
            });

            // Inject Reset Buttons
            if ($('#chroma-generate-selected').length && !$('#chroma-reset-schema').length) {
                $('<button id="chroma-reset-schema" class="button button-secodary" style="margin-left: 10px; color: #d63638; border-color: #d63638;">Reset Schema</button>').insertAfter('#chroma-generate-selected');
                $('<button id="chroma-reset-faq" class="button button-secondary" style="margin-left: 10px; color: #d63638; border-color: #d63638;">Reset FAQs</button>').insertAfter('#chroma-reset-schema');

                // Master Resets (Floating or separate? We stick to bulk selection for now based on context, but add Master as option if no selection?)
                // User asked for "Master Reset". We can add a confirm-all logic.
            }

            $('#chroma-generate-selected').on('click', this.startBulk.bind(this));
            $('#chroma-reset-schema').on('click', function (e) {
                e.preventDefault();
                BulkOps.resetBulk('schema');
            });
            $('#chroma-reset-faq').on('click', function (e) {
                e.preventDefault();
                BulkOps.resetBulk('faq');
            });
            $('#chroma-cancel-bulk').on('click', this.cancelBulk.bind(this));

            // Poll status if in progress
            if ($('.chroma-bulk-status').length) {
                this.pollStatus();
            }
        },

        startBulk: function () {
            var selected = $('.gap-checkbox:checked').map(function () {
                return $(this).val();
            }).get();

            if (!selected.length) {
                alert('Please select at least one post');
                return;
            }

            $.post(chromaLLM.ajaxUrl, {
                action: 'chroma_bulk_generate_start',
                nonce: chromaLLM.nonce,
                post_ids: selected,
                type: 'schema'
            }, function (response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + response.data.message);
                }
            });
        },

        cancelBulk: function () {
            $.post(chromaLLM.ajaxUrl, {
                action: 'chroma_bulk_generate_cancel',
                nonce: chromaLLM.nonce
            }, function () {
                location.reload();
            });
        },

        resetBulk: function (type) {
            var selected = $('.gap-checkbox:checked').map(function () {
                return $(this).val();
            }).get();

            var resetAll = false;

            if (!selected.length) {
                if (confirm('No posts selected. Do you want to perform a MASTER RESET of ALL ' + type + ' data across the entire site? This cannot be undone.')) {
                    resetAll = true;
                } else {
                    return;
                }
            } else {
                if (!confirm('Are you sure you want to reset ' + type + ' for the ' + selected.length + ' selected items?')) {
                    return;
                }
            }

            var actionName = type === 'faq' ? 'chroma_bulk_reset_faq' : 'chroma_bulk_reset_schema';

            $.post(chromaLLM.ajaxUrl, {
                action: actionName,
                nonce: chromaLLM.nonce, // Using same nonce
                post_ids: selected,
                reset_all: resetAll
            }, function (response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert('Error: ' + (response.data.message || 'Unknown error'));
                }
            });
        },

        pollStatus: function () {
            var self = this;

            $.post(chromaLLM.ajaxUrl, {
                action: 'chroma_bulk_generate_status',
                nonce: chromaLLM.nonce
            }, function (response) {
                if (response.success && response.data.status.in_progress) {
                    var s = response.data.status;
                    var percent = Math.round((s.completed / s.total) * 100);

                    $('.chroma-progress .bar').css('width', percent + '%');
                    $('.chroma-progress-text').text(s.completed + ' / ' + s.total);

                    setTimeout(function () { self.pollStatus(); }, 3000);
                } else {
                    location.reload();
                }
            });
        }
    };

    // Review Queue Handler
    var ReviewQueue = {
        init: function () {
            $('.approve-btn').on('click', function () {
                var postId = $(this).data('post');
                var $row = $(this).closest('tr');

                $.post(chromaLLM.ajaxUrl, {
                    action: 'chroma_review_schema',
                    nonce: chromaLLM.nonce,
                    post_id: postId,
                    review_action: 'approve'
                }, function (response) {
                    if (response.success) {
                        $row.fadeOut();
                    }
                });
            });
        }
    };

    // GMB Sync Handler
    var GMBSync = {
        init: function () {
            $('#chroma-sync-gmb').on('click', function () {
                var postId = $(this).data('post');
                var $btn = $(this);

                $btn.prop('disabled', true).text('Syncing...');

                $.post(chromaLLM.ajaxUrl, {
                    action: 'chroma_sync_gmb_data',
                    nonce: chromaLLM.nonce,
                    post_id: postId
                }, function (response) {
                    if (response.success) {
                        $btn.text('Synced!');
                        setTimeout(function () { location.reload(); }, 1000);
                    } else {
                        $btn.text('Error').prop('disabled', false);
                        alert(response.data.message);
                    }
                });
            });
        }
    };

    // Initialize all
    BulkOps.init();
    ReviewQueue.init();
    GMBSync.init();
});
