/**
 * Chroma QA Reports - Admin Scripts
 *
 * @package ChromaQAReports
 */

(function ($) {
    'use strict';

    // Global namespace
    window.CQA = window.CQA || {};

    /**
     * API Helper
     */
    CQA.api = {
        baseUrl: cqaAdmin.restUrl,
        nonce: cqaAdmin.nonce,

        request: function (endpoint, method, data, options) {
            options = options || {};
            return $.ajax({
                url: this.baseUrl + endpoint,
                method: method || 'GET',
                data: data,
                headers: $.extend({
                    'X-WP-Nonce': this.nonce
                }, options.headers || {}),
                contentType: 'application/json',
                dataType: 'json'
            });
        },

        get: function (endpoint, data, options) {
            return this.request(endpoint, 'GET', data, options);
        },

        post: function (endpoint, data, options) {
            options = options || {};
            return $.ajax({
                url: this.baseUrl + endpoint,
                method: 'POST',
                data: JSON.stringify(data),
                headers: $.extend({
                    'X-WP-Nonce': this.nonce,
                    'Content-Type': 'application/json'
                }, options.headers || {}),
                dataType: 'json'
            });
        },

        put: function (endpoint, data, options) {
            options = options || {};
            return $.ajax({
                url: this.baseUrl + endpoint,
                method: 'PUT',
                data: JSON.stringify(data),
                headers: $.extend({
                    'X-WP-Nonce': this.nonce,
                    'Content-Type': 'application/json'
                }, options.headers || {}),
                dataType: 'json'
            });
        },

        delete: function (endpoint, options) {
            return this.request(endpoint, 'DELETE', undefined, options);
        }
    };

    /**
     * Notifications
     */
    CQA.notify = {
        show: function (message, type) {
            type = type || 'info';

            var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');

            $('.cqa-wrap').prepend($notice);

            // Auto-dismiss after 5 seconds
            setTimeout(function () {
                $notice.fadeOut(function () {
                    $(this).remove();
                });
            }, 5000);
        },

        success: function (message) {
            this.show(message, 'success');
        },

        error: function (message) {
            this.show(message, 'error');
        },

        warning: function (message) {
            this.show(message, 'warning');
        }
    };

    /**
     * Confirm dialog
     */
    CQA.confirm = function (message, callback) {
        if (confirm(message)) {
            callback();
        }
    };

    /**
     * Loading state helpers
     */
    CQA.loading = {
        show: function ($element) {
            $element.addClass('cqa-loading').prop('disabled', true);
            $element.data('original-text', $element.text());
            $element.text(cqaAdmin.strings.saving);
        },

        hide: function ($element) {
            $element.removeClass('cqa-loading').prop('disabled', false);
            $element.text($element.data('original-text'));
        }
    };

    /**
     * Initialize on document ready
     */
    $(document).ready(function () {
        // Delete confirmation
        $('.cqa-delete-btn').on('click', function (e) {
            if (!confirm(cqaAdmin.strings.confirm_delete)) {
                e.preventDefault();
            }
        });

        // Auto-dismiss notices
        $('.notice.is-dismissible').each(function () {
            var $notice = $(this);
            setTimeout(function () {
                $notice.fadeOut();
            }, 5000);
        });

        // Toggle password visibility
        $('input[type="password"]').each(function () {
            var $input = $(this);
            var $toggle = $('<button type="button" class="button button-secondary" style="margin-left: 8px;">Show</button>');

            $toggle.on('click', function () {
                if ($input.attr('type') === 'password') {
                    $input.attr('type', 'text');
                    $(this).text('Hide');
                } else {
                    $input.attr('type', 'password');
                    $(this).text('Show');
                }
            });

            $input.after($toggle);
        });

        // Form validation
        $('form').on('submit', function () {
            var valid = true;

            $(this).find('[required]').each(function () {
                if (!$(this).val()) {
                    $(this).addClass('cqa-error');
                    valid = false;
                } else {
                    $(this).removeClass('cqa-error');
                }
            });

            return valid;
        });

        // Status badge colors
        $('.cqa-badge').each(function () {
            var $badge = $(this);
            var text = $badge.text().toLowerCase().trim();

            if (text.includes('exceeds') || text.includes('approved') || text.includes('active')) {
                $badge.addClass('cqa-badge-success');
            } else if (text.includes('needs') || text.includes('improvement')) {
                $badge.addClass('cqa-badge-danger');
            } else if (text.includes('draft') || text.includes('pending')) {
                $badge.addClass('cqa-badge-warning');
            }
        });

        // Initialize Settings Page
        if ($('.cqa-settings-form').length > 0) {
            CQA.settings.init();
        }
    });

    /**
     * Settings Page Logic
     */
    CQA.settings = {
        init: function () {
            this.cacheDOM();
            this.bindEvents();

            if (cqaAdmin.googleClientId && cqaAdmin.developerKey) {
                this.loadGooglePicker();
            }
        },

        cacheDOM: function () {
            this.$driveBtn = $('#cqa-drive-picker-btn');
            this.$driveInput = $('#cqa_drive_root_folder');
        },

        bindEvents: function () {
            this.$driveBtn.on('click', this.handleDriveClick.bind(this));

            // Feature Flag Audience Toggle
            $('select[name^="cqa_flag_"][name$="_audience"]').on('change', function () {
                var $select = $(this);
                var $canaryInput = $select.closest('td').find('.cqa-canary-input');

                if ($select.val() === 'canary') {
                    $canaryInput.slideDown();
                } else {
                    $canaryInput.slideUp();
                }
            });

            // Fetch AI Models
            $('#cqa-fetch-models-btn').on('click', this.handleFetchModels.bind(this));
        },

        handleFetchModels: function (e) {
            e.preventDefault();
            var $btn = $(e.currentTarget);
            var $select = $('#cqa_gemini_model');
            var originalValue = $select.val();

            CQA.loading.show($btn);
            $btn.text('Fetching...');

            var apiKey = $('#cqa_gemini_api_key').val();
            // If it's the masked value, don't send it (backend will use DB)
            var query = {};
            if (apiKey && !apiKey.includes('****') && !apiKey.includes('••••')) {
                query.api_key = apiKey;
            }

            CQA.api.get('settings/available-models', query)
                .done(function (models) {
                    $select.empty();

                    if (models && models.length > 0) {
                        $.each(models, function (i, model) {
                            var $option = $('<option></option>')
                                .val(model.name)
                                .text(model.displayName + ' (' + model.name + ')');

                            if (model.name === originalValue) {
                                $option.prop('selected', true);
                            }

                            $select.append($option);
                        });
                        CQA.notify.success('Models fetched successfully.');
                    } else {
                        CQA.notify.warning('No compatible models found.');
                    }
                })
                .fail(function (xhr) {
                    var message = 'Failed to fetch models.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message += ' ' + xhr.responseJSON.message;
                    }
                    CQA.notify.error(message);
                })
                .always(function () {
                    CQA.loading.hide($btn);
                    $btn.html('<span class="dashicons dashicons-update"></span> Fetch Models');
                });
        },

        loadGooglePicker: function () {
            // Load both GIS and GAPI
            $.getScript('https://accounts.google.com/gsi/client', function () {
                $.getScript('https://apis.google.com/js/api.js', function () {
                    gapi.load('picker', {
                        'callback': function () {
                            // Picker API loaded
                        }
                    });
                });
            });
        },

        handleDriveClick: function (e) {
            e.preventDefault();

            // Modern GIS Client
            const client = google.accounts.oauth2.initTokenClient({
                client_id: cqaAdmin.googleClientId,
                scope: 'https://www.googleapis.com/auth/drive.file',
                callback: (response) => {
                    if (response.access_token) {
                        this.createPicker(response.access_token);
                    }
                },
            });

            // Request token
            client.requestAccessToken();
        },

        createPicker: function (oauthToken) {
            if (oauthToken) {
                const picker = new google.picker.PickerBuilder()
                    .addView(google.picker.ViewId.FOLDERS)
                    .setOAuthToken(oauthToken)
                    .setDeveloperKey(cqaAdmin.developerKey)
                    .setCallback(this.pickerCallback.bind(this))
                    .build();
                picker.setVisible(true);
            }
        },

        pickerCallback: function (data) {
            if (data[google.picker.Response.ACTION] == google.picker.Action.PICKED) {
                const doc = data[google.picker.Response.DOCUMENTS][0];
                const folderId = doc[google.picker.Document.ID];
                this.$driveInput.val(folderId);

                // Trigger change to ensure any listeners pick it up
                this.$driveInput.trigger('change');
            }
        }
    };

})(jQuery);
