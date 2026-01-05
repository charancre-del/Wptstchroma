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

            $('#chroma-generate-selected').on('click', this.startBulk.bind(this));
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
