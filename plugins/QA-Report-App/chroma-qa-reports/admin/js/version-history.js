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
        currentVersion: null,
        activeComparisonVersion: null,

        init: function () {
            this.container = $('#cqa-version-history .cqa-version-list');

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
                url: cqaAdmin.restUrl + 'reports/' + this.reportId + '/versions',
                type: 'GET',
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', cqaAdmin.nonce);
                }
            }).done(function (response) {
                self.versions = response.versions || [];
                self.currentVersion = parseInt(response.current_version, 10) || null;
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

            versions.slice(0, 10).forEach(function (versionRow) {
                const date = new Date(versionRow.created_at);
                const timeAgo = VersionHistory.timeAgo(date);
                const versionNumber = parseInt(versionRow.version_number, 10);
                const isCurrent = VersionHistory.currentVersion === versionNumber;

                html += `
                    <li class="cqa-version-item ${isCurrent ? 'current' : ''}" data-version="${versionNumber}">
                        <div class="cqa-version-header">
                            <strong>v${versionNumber}</strong>
                            <span class="cqa-version-time">${timeAgo}</span>
                        </div>
                        <div class="cqa-version-meta">
                            ${VersionHistory.escapeHtml(versionRow.change_summary || 'Report updated')}
                            ${versionRow.user_name ? '<span class="cqa-version-user">by ' + VersionHistory.escapeHtml(versionRow.user_name) + '</span>' : ''}
                        </div>
                        <div class="cqa-version-actions">
                            ${!isCurrent ? `
                                <button type="button" class="cqa-btn-compare" data-version="${versionNumber}">Compare</button>
                                <button type="button" class="cqa-btn-restore" data-version="${versionNumber}">Restore</button>
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

                $(document).on('click', '.cqa-modal-close, .cqa-modal', function (e) {
                    if (e.target === this) {
                        $('#cqa-compare-modal').fadeOut(200);
                    }
                });

                $(document).on('click', '.cqa-btn-restore-field', function () {
                    const $button = $(this);
                    VersionHistory.restoreSelection(
                        {
                            version: $button.data('version'),
                            target_type: 'report_field',
                            field: $button.data('field')
                        },
                        $button
                    );
                });

                $(document).on('click', '.cqa-btn-restore-response', function () {
                    const $button = $(this);
                    VersionHistory.restoreSelection(
                        {
                            version: $button.data('version'),
                            target_type: 'response',
                            section_key: $button.data('section'),
                            item_key: $button.data('item')
                        },
                        $button
                    );
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
            const currentVersion = this.currentVersion || this.versions[0]?.version_number;

            if (!currentVersion) {
                return;
            }

            this.activeComparisonVersion = version;
            $('#cqa-compare-modal').fadeIn(200);
            $('#cqa-compare-body').html('<p class="cqa-loading">Loading comparison...</p>');

            Promise.all([
                this.fetchVersion(currentVersion),
                this.fetchVersion(version)
            ]).then(function ([current, old]) {
                self.renderComparison(current, old, currentVersion, version);
            }).catch(function () {
                $('#cqa-compare-body').html('<p class="cqa-error">Failed to load comparison</p>');
            });
        },

        fetchVersion: function (version) {
            return $.ajax({
                url: cqaAdmin.restUrl + 'reports/' + this.reportId + '/versions/' + version,
                type: 'GET',
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', cqaAdmin.nonce);
                }
            });
        },

        renderComparison: function (current, old, currentVer, oldVer) {
            const currentData = current.snapshot_data || {};
            const oldData = old.snapshot_data || {};

            let html = `
                <div class="cqa-compare-header-bar">
                    <span class="cqa-compare-label old">v${this.escapeHtml(String(oldVer))}</span>
                    <span class="cqa-compare-arrow">&rarr;</span>
                    <span class="cqa-compare-label current">v${this.escapeHtml(String(currentVer))} (Current)</span>
                </div>
            `;

            const reportChanges = this.compareObjects(oldData.report || {}, currentData.report || {});
            if (reportChanges.length) {
                html += '<div class="cqa-compare-section"><h4>Report Details</h4>';
                html += this.renderChanges(reportChanges, oldVer);
                html += '</div>';
            }

            const responseChanges = this.compareResponses(oldData.responses || {}, currentData.responses || {});
            if (responseChanges.length) {
                html += '<div class="cqa-compare-section"><h4>Checklist Responses</h4>';
                html += this.renderResponseChanges(responseChanges, oldVer);
                html += '</div>';
            }

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
            const fieldLabels = {
                school_id: 'School',
                report_type: 'Report Type',
                inspection_date: 'Inspection Date',
                previous_report_id: 'Compared Saved Report',
                overall_rating: 'Overall Rating',
                status: 'Status',
                closing_notes: 'Closing Notes'
            };
            const self = this;

            Object.keys(fieldLabels).forEach(function (field) {
                const oldVal = self.normalizeCompareValue(oldObj[field]);
                const newVal = self.normalizeCompareValue(newObj[field]);

                if (oldVal !== newVal) {
                    changes.push({
                        key: field,
                        field: fieldLabels[field],
                        old: oldVal,
                        new: newVal
                    });
                }
            });

            return changes;
        },

        compareResponses: function (oldResp, newResp) {
            const changes = [];
            const self = this;
            const responseFields = {
                rating: 'Rating',
                notes: 'Notes',
                evidence_type: 'Evidence',
                previous_rating: 'Previous Rating',
                previous_notes: 'Previous Notes'
            };
            const allSections = new Set([].concat(Object.keys(oldResp), Object.keys(newResp)));

            allSections.forEach(function (section) {
                const oldItems = oldResp[section] || {};
                const newItems = newResp[section] || {};
                const allItems = new Set([].concat(Object.keys(oldItems), Object.keys(newItems)));

                allItems.forEach(function (item) {
                    const oldEntry = oldItems[item] || {};
                    const newEntry = newItems[item] || {};
                    const differences = [];

                    Object.keys(responseFields).forEach(function (field) {
                        const oldValue = self.normalizeCompareValue(oldEntry[field]);
                        const newValue = self.normalizeCompareValue(newEntry[field]);

                        if (oldValue !== newValue) {
                            differences.push({
                                key: field,
                                label: responseFields[field],
                                old: oldValue,
                                new: newValue
                            });
                        }
                    });

                    if (differences.length) {
                        changes.push({
                            section: section,
                            item: item,
                            sectionLabel: self.humanizeKey(section),
                            itemLabel: self.humanizeKey(item),
                            differences: differences
                        });
                    }
                });
            });

            return changes;
        },

        comparePhotos: function (oldPhotos, newPhotos) {
            const oldIds = new Set((oldPhotos || []).map(function (photo) {
                return photo.id;
            }));
            const newIds = new Set((newPhotos || []).map(function (photo) {
                return photo.id;
            }));

            return {
                added: Array.from(newIds).filter(function (id) {
                    return !oldIds.has(id);
                }),
                removed: Array.from(oldIds).filter(function (id) {
                    return !newIds.has(id);
                })
            };
        },

        renderChanges: function (changes, version) {
            const self = this;
            let html = '<ul class="cqa-change-list">';

            changes.forEach(function (change) {
                html += `
                    <li class="cqa-change-item">
                        <div class="cqa-change-item-header">
                            <strong class="cqa-change-item-title">${self.escapeHtml(change.field)}</strong>
                            <button
                                type="button"
                                class="button button-small cqa-btn-restore-field"
                                data-version="${self.escapeAttribute(version)}"
                                data-field="${self.escapeAttribute(change.key)}"
                            >Restore saved value</button>
                        </div>
                        <div class="cqa-change-subrow">
                            <span class="cqa-old-value cqa-value-block">${self.formatCompareText(change.old)}</span>
                            <span class="cqa-change-arrow">&rarr;</span>
                            <span class="cqa-new-value cqa-value-block">${self.formatCompareText(change.new)}</span>
                        </div>
                    </li>
                `;
            });

            html += '</ul>';
            return html;
        },

        renderResponseChanges: function (changes, version) {
            const self = this;
            let html = '<ul class="cqa-change-list">';

            changes.forEach(function (change) {
                let diffHtml = '';
                change.differences.forEach(function (difference) {
                    diffHtml += `
                        <div class="cqa-change-subrow">
                            <span class="cqa-diff-label">${self.escapeHtml(difference.label)}:</span>
                            <span class="cqa-old-value cqa-value-block">${self.formatCompareText(difference.old, difference.key)}</span>
                            <span class="cqa-change-arrow">&rarr;</span>
                            <span class="cqa-new-value cqa-value-block">${self.formatCompareText(difference.new, difference.key)}</span>
                        </div>
                    `;
                });

                html += `
                    <li class="cqa-change-item">
                        <div class="cqa-change-item-header">
                            <strong class="cqa-change-item-title">${self.escapeHtml(change.sectionLabel)} / ${self.escapeHtml(change.itemLabel)}</strong>
                            <button
                                type="button"
                                class="button button-small cqa-btn-restore-response"
                                data-version="${self.escapeAttribute(version)}"
                                data-section="${self.escapeAttribute(change.section)}"
                                data-item="${self.escapeAttribute(change.item)}"
                            >Restore saved item</button>
                        </div>
                        ${diffHtml}
                    </li>
                `;
            });

            html += '</ul>';
            return html;
        },

        restoreSelection: function (payload, $button) {
            const self = this;
            const version = parseInt(payload.version, 10);
            const originalText = $button.text();

            if (!version) {
                alert('Unable to restore from that version.');
                return;
            }

            $button.prop('disabled', true).text('Restoring...');

            $.ajax({
                url: cqaAdmin.restUrl + 'reports/' + this.reportId + '/versions/' + version + '/restore-selection',
                type: 'POST',
                data: Object.assign({}, payload, {
                    version_id: this.currentVersion || ''
                }),
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', cqaAdmin.nonce);
                    if (self.currentVersion) {
                        xhr.setRequestHeader('X-CQA-Version', String(self.currentVersion));
                    }
                }
            }).done(function (response) {
                self.currentVersion = parseInt(response.version_id, 10) || self.currentVersion;
                self.loadVersions();
                self.showComparison(version);
            }).fail(function (xhr) {
                alert('Failed to restore: ' + (xhr.responseJSON?.message || 'Unknown error'));
                $button.prop('disabled', false).text(originalText);
            });
        },

        restoreVersion: function (version) {
            const self = this;
            const btn = this.container.find('.cqa-btn-restore[data-version="' + version + '"]');
            const originalText = btn.text();

            btn.prop('disabled', true).text('Restoring...');

            $.ajax({
                url: cqaAdmin.restUrl + 'reports/' + this.reportId + '/restore/' + version,
                type: 'POST',
                data: {
                    version_id: this.currentVersion || ''
                },
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', cqaAdmin.nonce);
                    if (self.currentVersion) {
                        xhr.setRequestHeader('X-CQA-Version', String(self.currentVersion));
                    }
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

        normalizeCompareValue: function (value) {
            if (value === null || value === undefined) {
                return '';
            }

            return String(value).trim();
        },

        humanizeKey: function (value) {
            return String(value || '')
                .replace(/[_-]+/g, ' ')
                .replace(/\s+/g, ' ')
                .trim()
                .replace(/\b\w/g, function (character) {
                    return character.toUpperCase();
                });
        },

        formatCompareText: function (value, field) {
            const normalized = this.normalizeCompareValue(value);
            const displayValue = normalized === ''
                ? (field && field.indexOf('rating') !== -1 ? 'N/A' : '(empty)')
                : normalized;

            return this.escapeHtml(displayValue).replace(/\n/g, '<br>');
        },

        escapeHtml: function (value) {
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        },

        escapeAttribute: function (value) {
            return this.escapeHtml(value);
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

    if (typeof window !== 'undefined') {
        window.CQAVersionHistory = VersionHistory;
    }

    $(document).ready(function () {
        VersionHistory.init();
    });

})(jQuery);
