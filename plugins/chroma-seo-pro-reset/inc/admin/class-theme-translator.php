<?php
/**
 * Theme String Translator
 * Scans theme files for strings and manages AI translations.
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Theme_Translator
{
    private $text_domain = 'chroma-excellence';
    private $option_key = 'chroma_theme_translations_es';

    public function init()
    {
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('wp_ajax_chroma_scan_theme_strings', [$this, 'ajax_scan_strings']);
        add_action('wp_ajax_chroma_save_string_translations', [$this, 'ajax_save_translations']);
        add_action('wp_ajax_chroma_bulk_translate_strings', [$this, 'ajax_bulk_translate_strings']);
        add_action('wp_ajax_chroma_export_po', [$this, 'ajax_export_po']);
        
        // TEMP DEBUG
        add_action('wp_ajax_chroma_debug_meta', [$this, 'ajax_debug_meta']);
        add_action('wp_ajax_nopriv_chroma_debug_meta', [$this, 'ajax_debug_meta']);

        // Runtime Translation Hook
        add_filter('gettext', [$this, 'filter_gettext'], 10, 3);
    }

    public function register_menu()
    {
        add_submenu_page(
            'chroma-seo-dashboard',
            'Theme Translator',
            'Theme Translator',
            'manage_options',
            'chroma-theme-translator',
            [$this, 'render_page']
        );
    }

    public function render_page()
    {
        $existing_translations = get_option($this->option_key, []);
        ?>
        <div class="wrap chroma-seo-dashboard">
            <h1>🎨 Theme String Translator</h1>
            <p>Scan your theme for translatable strings and use AI to generate Spanish translations.</p>
            
            <div class="card" style="padding: 20px; max-width: 1200px;">
                <div class="actions" style="margin-bottom: 20px;">
                     <button id="chroma-scan-btn" class="button button-primary button-large">
                        <span class="dashicons dashicons-search"></span> Scan Theme Files
                     </button>
                     <button id="chroma-bulk-translate-btn" class="button button-secondary button-large" disabled>
                        <span class="dashicons dashicons-translation"></span> AI Translate Missing
                     </button>
                     <button id="chroma-save-translations-btn" class="button button-secondary button-large" disabled>
                        <span class="dashicons dashicons-saved"></span> Save Changes
                     </button>
                     <button id="chroma-export-po-btn" class="button button-secondary button-large">
                        <span class="dashicons dashicons-download"></span> Export .PO File
                     </button>
                     <span id="chroma-status" style="margin-left: 10px; font-weight: bold;"></span>
                </div>

                <div id="chroma-scan-results" style="display: none;">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th style="width: 40%;">Original String (English)</th>
                                <th style="width: 60%;">Spanish Translation</th>
                            </tr>
                        </thead>
                        <tbody id="chroma-strings-body">
                            <!-- Populated via JS -->
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Hidden Store for existing translations -->
            <script>
                window.chromaExistingTranslations = <?php echo json_encode($existing_translations); ?>;
            </script>
            
            <style>
                textarea.translation-input { width: 100%; height: 40px; }
            </style>
            
            <script>
            jQuery(document).ready(function($) {
                var scannedStrings = [];
                var existingTranslations = window.chromaExistingTranslations || {};

                // SCAN
                $('#chroma-scan-btn').click(function() {
                    var btn = $(this);
                    btn.prop('disabled', true).text('Scanning...');
                    $('#chroma-status').text('Scanning theme files...');
                    
                    $.post(ajaxurl, {
                        action: 'chroma_scan_theme_strings',
                        nonce: '<?php echo wp_create_nonce('chroma_seo_nonce'); ?>'
                    }, function(response) {
                        btn.prop('disabled', false).text('Scan Theme Files');
                        
                        if(response.success) {
                            scannedStrings = response.data;
                            renderTable();
                            $('#chroma-scan-results').show();
                            $('#chroma-bulk-translate-btn').prop('disabled', false);
                            $('#chroma-save-translations-btn').prop('disabled', false);
                            $('#chroma-status').text('Found ' + scannedStrings.length + ' strings.');
                        } else {
                            $('#chroma-status').text('Error: ' + response.data.message);
                        }
                    });
                });

                function renderTable() {
                    var html = '';
                    scannedStrings.forEach(function(str) {
                        var val = existingTranslations[str] || '';
                        html += '<tr>';
                        html += '<td>' + escapeHtml(str) + '</td>';
                        html += '<td><textarea class="translation-input" data-original="' + escapeHtml(str) + '">' + escapeHtml(val) + '</textarea></td>';
                        html += '</tr>';
                    });
                    $('#chroma-strings-body').html(html);
                }
                
                function escapeHtml(text) {
                    if(!text) return '';
                    return text
                        .replace(/&/g, "&amp;")
                        .replace(/</g, "&lt;")
                        .replace(/>/g, "&gt;")
                        .replace(/"/g, "&quot;")
                        .replace(/'/g, "&#039;");
                }

                // BULK TRANSLATE
                $('#chroma-bulk-translate-btn').click(function() {
                    if(!confirm('This will use AI tokens to translate empty fields. Continue?')) return;
                    
                    var btn = $(this);
                    btn.prop('disabled', true).text('Translating...');
                    $('#chroma-status').text('AI is working... this may take a moment.');
                    
                    // Collect missing strings
                    var missing = {};
                    $('.translation-input').each(function() {
                        var original = $(this).data('original');
                        var current = $(this).val();
                        if(!current) {
                            missing[original] = original; // Send original as value to hint context if needed
                        }
                    });
                    
                    if(Object.keys(missing).length === 0) {
                        alert('No missing translations found.');
                        btn.prop('disabled', false).text('AI Translate Missing');
                        return;
                    }
                    
                    
                    $.post(ajaxurl, {
                        action: 'chroma_bulk_translate_strings', // We need to register this
                        strings: missing,
                        nonce: '<?php echo wp_create_nonce('chroma_seo_nonce'); ?>'
                    }, function(response) {
                       btn.prop('disabled', false).text('AI Translate Missing');
                       if(response.success) {
                           // Update inputs
                           var results = response.data;
                           $('.translation-input').each(function() {
                               var original = $(this).data('original');
                               if(results[original]) {
                                   $(this).val(results[original]);
                               }
                           });
                           $('#chroma-status').text('Translation complete! Review and Save.');
                       } else {
                           $('#chroma-status').text('Error: ' + (response.data.message || 'Unknown'));
                       }
                    });
                });

                // SAVE
                $('#chroma-save-translations-btn').click(function() {
                    var btn = $(this);
                    btn.prop('disabled', true).text('Saving...');
                    
                    var data = {};
                    $('.translation-input').each(function() {
                        var original = $(this).data('original');
                        var val = $(this).val();
                        if(val) data[original] = val;
                    });
                    
                    $.post(ajaxurl, {
                        action: 'chroma_save_string_translations',
                        translations: data,
                        nonce: '<?php echo wp_create_nonce('chroma_seo_nonce'); ?>'
                    }, function(response) {
                         btn.prop('disabled', false).text('Save Changes');
                         if(response.success) {
                             $('#chroma-status').text('Saved successfully.').css('color', 'green');
                             existingTranslations = data;
                         } else {
                             $('#chroma-status').text('Save failed.').css('color', 'red');
                         }
                    });
                });

                // EXPORT PO
                $('#chroma-export-po-btn').click(function() {
                    $.post(ajaxurl, {
                        action: 'chroma_export_po',
                        nonce: '<?php echo wp_create_nonce('chroma_seo_nonce'); ?>'
                    }, function(response) {
                        if(response.success) {
                            // Create download
                            var blob = new Blob([response.data.content], {type: 'text/plain'});
                            var url = window.URL.createObjectURL(blob);
                            var a = document.createElement('a');
                            a.href = url;
                            a.download = response.data.filename;
                            document.body.appendChild(a);
                            a.click();
                            document.body.removeChild(a);
                            window.URL.revokeObjectURL(url);
                            $('#chroma-status').text('Exported ' + response.data.count + ' strings.').css('color', 'green');
                        } else {
                            $('#chroma-status').text('Export failed.').css('color', 'red');
                        }
                    });
                });
            });
            </script>
        </div>
        <?php
    }

    /**
     * AJAX: Scan Theme Files
     */
    public function ajax_scan_strings()
    {
        check_ajax_referer('chroma_seo_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Denied']);

        try {
            set_time_limit(120);

            $theme_dir = get_template_directory();
            
            if (!is_dir($theme_dir)) {
                throw new Exception("Theme directory not found: $theme_dir");
            }

            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($theme_dir, FilesystemIterator::SKIP_DOTS)
            );
            
            $strings = [];
            $files_scanned = 0;

            foreach ($files as $file) {
                if ($file->isDir()) continue;
                if ($file->getExtension() !== 'php') continue;

                $files_scanned++;
                $content = file_get_contents($file->getPathname());
                
                // Relaxed Regex: Matches function call and first argument (string).
                // Ignores text domain presence to catch all potential strings.
                // Improved Regex: Catches string content even if followed by arguments
                // Matches: _e('String', 'domain') OR _e('String')
                preg_match_all("/(?:_e|__|esc_attr_e|esc_html_e|esc_attr__|esc_html__|_x|esc_html_x|esc_attr_x)\s*\(\s*(['\"])(.*?)\1/s", $content, $matches);
                
                if (!empty($matches[2])) {
                    foreach ($matches[2] as $match) {
                        // Skip if it looks like a variable (starts with $) - though usually regex won't match $
                        $strings[] = $match;
                    }
                }
            }
            
            $unique_strings = array_unique($strings);
            sort($unique_strings);

            if (empty($unique_strings)) {
                wp_send_json_error(['message' => "Scanned $files_scanned files but found no strings. Check text domain usage or file permissions."]);
            } else {
                wp_send_json_success($unique_strings);
            }

        } catch (Throwable $e) {
            wp_send_json_error(['message' => 'Scan Error: ' . $e->getMessage()]);
        }
    }
    
    /**
     * AJAX: Bulk Translate Strings (Helper)
     */
    public function ajax_bulk_translate_strings() {
        check_ajax_referer('chroma_seo_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Permission denied']);

        // Delegate to engine
        if(class_exists('Chroma_Translation_Engine')) {
            $strings = isset($_POST['strings']) ? $_POST['strings'] : [];
            $translated = Chroma_Translation_Engine::translate_bulk($strings, 'es', 'Translate UI strings for a childcare website.');
            wp_send_json_success($translated);
        }
        wp_send_json_error(['message' => 'Engine not found']);
    }

    /**
     * AJAX: Save Translations
     */
    public function ajax_save_translations() {
        check_ajax_referer('chroma_seo_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Denied']);
        
        $data = isset($_POST['translations']) ? $_POST['translations'] : [];
        // Sanitize? Keys are original English strings, Values are translations.
        // We trust admin input here mostly, but recursive sanitize is good.
        // update_option handles serialization.
        
        // Sanitize values
        $sanitized = array_map('sanitize_text_field', $data);
        
        update_option($this->option_key, $sanitized);
        wp_send_json_success();
    }

    /**
     * Static cache for translations
     */
    private static $translations_cache = null;

    /**
     * Filter Gettext for Runtime Translation
     */
    public function filter_gettext($translation, $text, $domain)
    {
        if ($domain !== $this->text_domain) {
            return $translation;
        }

        // Only apply if current language is Spanish
        $is_spanish = false;
        if (class_exists('Chroma_Multilingual_Manager')) {
            $is_spanish = Chroma_Multilingual_Manager::is_spanish();
        } else {
            $is_spanish = (strpos($_SERVER['REQUEST_URI'], '/es/') !== false);
        }
        
        if (!$is_spanish) {
            return $translation;
        }

        // Use static cache to avoid repeated get_option calls
        if (self::$translations_cache === null) {
            self::$translations_cache = get_option($this->option_key, []);
        }
        
        if (isset(self::$translations_cache[$text]) && !empty(self::$translations_cache[$text])) {
            return self::$translations_cache[$text];
        }

        return $translation;
    }

    /**
     * AJAX: Export translations as .PO file
     */
    public function ajax_export_po()
    {
        check_ajax_referer('chroma_seo_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Permission denied']);

        $translations = get_option($this->option_key, []);
        
        // Generate PO file content
        $po_content = "# Spanish translations for Chroma Excellence theme.\n";
        $po_content .= "# Generated by Chroma SEO Pro\n";
        $po_content .= "# " . date('Y-m-d H:i:s') . "\n";
        $po_content .= "msgid \"\"\n";
        $po_content .= "msgstr \"\"\n";
        $po_content .= "\"Project-Id-Version: chroma-excellence\\n\"\n";
        $po_content .= "\"Report-Msgid-Bugs-To: \\n\"\n";
        $po_content .= "\"POT-Creation-Date: " . date('Y-m-d H:i:sO') . "\\n\"\n";
        $po_content .= "\"PO-Revision-Date: " . date('Y-m-d H:i:sO') . "\\n\"\n";
        $po_content .= "\"Language: es_ES\\n\"\n";
        $po_content .= "\"MIME-Version: 1.0\\n\"\n";
        $po_content .= "\"Content-Type: text/plain; charset=UTF-8\\n\"\n";
        $po_content .= "\"Content-Transfer-Encoding: 8bit\\n\"\n\n";

        foreach ($translations as $original => $translated) {
            if (empty($translated)) continue;
            
            // Escape quotes
            $original = str_replace('"', '\\"', $original);
            $translated = str_replace('"', '\\"', $translated);
            
            $po_content .= "msgid \"" . $original . "\"\n";
            $po_content .= "msgstr \"" . $translated . "\"\n\n";
        }

        wp_send_json_success([
            'content' => $po_content,
            'filename' => 'chroma-excellence-es_ES.po',
            'count' => count($translations)
        ]);
    }

    public function ajax_debug_meta() {
        $page = get_page_by_path('about');
        if (!$page) $page = get_page_by_path('about-us');
        
        if ($page) {
            $meta = get_post_meta($page->ID);
            wp_send_json_success([
                'id' => $page->ID,
                'title' => $page->post_title,
                'es_content' => $meta['_chroma_es_content'][0] ?? 'MISSING',
                'es_title' => $meta['_chroma_es_title'][0] ?? 'MISSING',
                'all_meta' => $meta
            ]);
        }
        wp_send_json_error('Page not found');
    }
}


