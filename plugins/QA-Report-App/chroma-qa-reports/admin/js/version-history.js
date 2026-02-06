/**
 * Report Version History
 * 
 * Handles loading, displaying, comparing, and restoring report versions.
 */
(function ($) {
    'use strict';

    const VersionHistory = {
        reportId: null,
        container: null,
        versions: [],

        init: function () {
            this.container = $('#cqa-version-history .cqa-version-list');

            // Get report ID from URL
            const urlParams = new URLSearchParams(window.location.search);
            this.reportId = urlParams.get('id');

            if (this.reportId && this.container.length) {
                this.loadVersions();
                this.createCompareModal();
            }
        },

        loadVersions: function () {
            const self = this;

            $.ajax({
                url: cqaAdmin.restUrl + 'cqa/v1/reports/' + this.reportId + '/versions',
                type: 'GET',
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', cqaAdmin.nonce);
                }
            }).done(function (response) {
                self.versions = response.versions || [];
                self.renderVersions(self.versions);
            }).fail(function () {
                self.container.html('<p class="cqa-error">Failed to load versions</p>');
            });
        },

        renderVersions: function (versions) {
            if (!versions.length) {
                this.container.html('<p class="cqa-empty">No version history yet</p>');
                return;
            }

            let html = '<ul class="cqa-versions">';

            versions.slice(0, 10).forEach(function (v, idx) {
                const date = new Date(v.created_at);
                const timeAgo = VersionHistory.timeAgo(date);
                const isCurrent = idx === 0;

                html += `
                    <li class="cqa-version-item ${isCurrent ? 'current' : ''}" data-version="${v.version_number}">
                        <div class="cqa-version-header">
                            <strong>v${v.version_number}</strong>
                            <span class="cqa-version-time">${timeAgo}</span>
                        </div>
                        <div class="cqa-version-meta">
                            ${v.change_summary || 'Report updated'}
                            ${v.user_name ? '<span class="cqa-version-user">by ' + v.user_name + '</span>' : ''}
                        </div>
                        <div class="cqa-version-actions">
                            ${!isCurrent ? `
                                <button type="button" class="cqa-btn-compare" data-version="${v.version_number}">Compare</button>
                                <button type="button" class="cqa-btn-restore" data-version="${v.version_number}">Restore</button>
                            ` : '<span class="cqa-current-tag">Current</span>'}
                        </div>
                    </li>
                `;
            });

            html += '</ul>';

            if (versions.length > 10) {
                html += '<p class="cqa-versions-more">' + (versions.length - 10) + ' more versions</p>';
            }

            this.container.html(html);
            this.bindEvents();
        },

        createCompareModal: function () {
            // Add modal HTML if not exists
            if (!$('#cqa-compare-modal').length) {
                const modal = `
                    <div id="cqa-compare-modal" class="cqa-modal" style="display:none;">
                        <div class="cqa-modal-content cqa-compare-modal-content">
                            <div class="cqa-modal-header">
                                <h3>Version Comparison</h3>
                                <button type="button" class="cqa-modal-close">&times;</button>
                            </div>
                            <div class="cqa-modal-body" id="cqa-compare-body">
                                <p>Loading comparison...</p>
                            </div>
                        </div>
                    </div>
                `;
                $('body').append(modal);

                // Close modal events
                $(document).on('click', '.cqa-modal-close, .cqa-modal', function (e) {
                    if (e.target === this) {
                        $('#cqa-compare-modal').fadeOut(200);
                    }
                });
            }
        },

        bindEvents: function () {
            const self = this;

            this.container.find('.cqa-btn-restore').on('click', function () {
                const version = $(this).data('version');
                if (confirm('Restore to version ' + version + '? A new version will be created with the current state first.')) {
                    self.restoreVersion(version);
                }
            });

            this.container.find('.cqa-btn-compare').on('click', function () {
                const version = $(this).data('version');
                self.showComparison(version);
            });
        },

        showComparison: function (version) {
            const self = this;
            const currentVersion = this.versions[0]?.version_number;

            if (!currentVersion) return;

            $('#cqa-compare-modal').fadeIn(200);
            $('#cqa-compare-body').html('<p class="cqa-loading">Loading comparison...</p>');

            // Fetch both versions for comparison
            Promise.all([
                this.fetchVersion(currentVersion),
                this.fetchVersion(version)
            ]).then(function ([current, old]) {
                self.renderComparison(current, old, currentVersion, version);
            }).catch(function (err) {
                $('#cqa-compare-body').html('<p class="cqa-error">Failed to load comparison</p>');
            });
        },

        fetchVersion: function (version) {
            return $.ajax({
                url: cqaAdmin.restUrl + 'cqa/v1/reports/' + this.reportId + '/versions/' + version,
                type: 'GET',
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', cqaAdmin.nonce);
                }
            });
        },

        renderComparison: function (current, old, currentVer, oldVer) {
            const currentData = current.snapshot?.data || {};
            const oldData = old.snapshot?.data || {};

            let html = `
                <div class="cqa-compare-header-bar">
                    <span class="cqa-compare-label old">v${oldVer}</span>
                    <span class="cqa-compare-arrow">→</span>
                    <span class="cqa-compare-label current">v${currentVer} (Current)</span>
                </div>
            `;

            // Compare report fields
            const reportChanges = this.compareObjects(oldData.report || {}, currentData.report || {});
            if (reportChanges.length) {
                html += '<div class="cqa-compare-section"><h4>Report Details</h4>';
                html += this.renderChanges(reportChanges);
                html += '</div>';
            }

            // Compare responses
            const responseChanges = this.compareResponses(oldData.responses || {}, currentData.responses || {});
            if (responseChanges.length) {
                html += '<div class="cqa-compare-section"><h4>Checklist Responses</h4>';
                html += this.renderResponseChanges(responseChanges);
                html += '</div>';
            }

            // Compare photos
            const photoChanges = this.comparePhotos(oldData.photos || [], currentData.photos || []);
            if (photoChanges.added.length || photoChanges.removed.length) {
                html += '<div class="cqa-compare-section"><h4>Photos</h4>';
                if (photoChanges.added.length) {
                    html += `<p class="cqa-change-added">+${photoChanges.added.length} photo(s) added</p>`;
                }
                if (photoChanges.removed.length) {
                    html += `<p class="cqa-change-removed">-${photoChanges.removed.length} photo(s) removed</p>`;
                }
                html += '</div>';
            }

            if (!reportChanges.length && !responseChanges.length && !photoChanges.added.length && !photoChanges.removed.length) {
                html += '<p class="cqa-no-changes">No significant changes detected between these versions.</p>';
            }

            $('#cqa-compare-body').html(html);
        },

        compareObjects: function (oldObj, newObj) {
            const changes = [];
            const importantFields = ['overall_rating', 'status', 'closing_notes', 'inspector_notes'];

            importantFields.forEach(function (field) {
                const oldVal = oldObj[field] || '';
                const newVal = newObj[field] || '';
                if (oldVal !== newVal) {
                    changes.push({
                        field: field.replace(/_/g, ' '),
                        old: oldVal || '(empty)',
                        new: newVal || '(empty)'
                    });
                }
            });

            return changes;
        },

        compareResponses: function (oldResp, newResp) {
            const changes = [];
            const allSections = new Set([...Object.keys(oldResp), ...Object.keys(newResp)]);

            allSections.forEach(function (section) {
                const oldItems = oldResp[section] || {};
                const newItems = newResp[section] || {};
                const allItems = new Set([...Object.keys(oldItems), ...Object.keys(newItems)]);

                allItems.forEach(function (item) {
                    const oldRating = oldItems[item]?.rating || '';
                    const newRating = newItems[item]?.rating || '';

                    if (oldRating !== newRating) {
                        changes.push({
                            section: section,
                            item: item,
                            oldRating: oldRating || 'N/A',
                            newRating: newRating || 'N/A'
                        });
                    }
                });
            });

            return changes;
        },

        comparePhotos: function (oldPhotos, newPhotos) {
            const oldIds = new Set((oldPhotos || []).map(p => p.id));
            const newIds = new Set((newPhotos || []).map(p => p.id));

            return {
                added: [...newIds].filter(id => !oldIds.has(id)),
                removed: [...oldIds].filter(id => !newIds.has(id))
            };
        },

        renderChanges: function (changes) {
            let html = '<ul class="cqa-change-list">';
            changes.forEach(function (c) {
                html += `
                    <li>
                        <strong>${c.field}:</strong>
                        <span class="cqa-old-value">${c.old}</span>
                        <span class="cqa-change-arrow">→</span>
                        <span class="cqa-new-value">${c.new}</span>
                    </li>
                `;
            });
            html += '</ul>';
            return html;
        },

        renderResponseChanges: function (changes) {
            let html = '<ul class="cqa-change-list">';
            changes.slice(0, 20).forEach(function (c) {
                html += `
                    <li>
                        <strong>${c.section} / ${c.item}:</strong>
                        <span class="cqa-old-value">${c.oldRating}</span>
                        <span class="cqa-change-arrow">→</span>
                        <span class="cqa-new-value">${c.newRating}</span>
                    </li>
                `;
            });
            if (changes.length > 20) {
                html += `<li class="cqa-more-changes">...and ${changes.length - 20} more changes</li>`;
            }
            html += '</ul>';
            return html;
        },

        restoreVersion: function (version) {
            const self = this;
            const btn = this.container.find('.cqa-btn-restore[data-version="' + version + '"]');
            const originalText = btn.text();

            btn.prop('disabled', true).text('Restoring...');

            $.ajax({
                url: cqaAdmin.restUrl + 'cqa/v1/reports/' + this.reportId + '/restore/' + version,
                type: 'POST',
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', cqaAdmin.nonce);
                }
            }).done(function (response) {
                if (response.success) {
                    alert('Report restored to version ' + version);
                    window.location.reload();
                }
            }).fail(function (xhr) {
                alert('Failed to restore: ' + (xhr.responseJSON?.message || 'Unknown error'));
                btn.prop('disabled', false).text(originalText);
            });
        },

        timeAgo: function (date) {
            const seconds = Math.floor((new Date() - date) / 1000);

            const intervals = [
                { label: 'year', seconds: 31536000 },
                { label: 'month', seconds: 2592000 },
                { label: 'day', seconds: 86400 },
                { label: 'hour', seconds: 3600 },
                { label: 'minute', seconds: 60 }
            ];

            for (const interval of intervals) {
                const count = Math.floor(seconds / interval.seconds);
                if (count >= 1) {
                    return count + ' ' + interval.label + (count !== 1 ? 's' : '') + ' ago';
                }
            }

            return 'Just now';
        }
    };

    $(document).ready(function () {
        VersionHistory.init();
    });

})(jQuery);
