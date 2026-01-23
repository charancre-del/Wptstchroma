<?php
/**
 * Credential Badges
 * Display trust badges for DECAL, NAEYC, etc.
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Credential_Badges
{
    private static $badges = [
        'ga_decal' => [
            'name' => 'GA DECAL Licensed',
            'description' => 'Georgia Department of Early Care and Learning Licensed Facility',
            'icon' => '🏛️',
            'color' => '#1a5c8a'
        ],
        'naeyc' => [
            'name' => 'NAEYC Accredited',
            'description' => 'National Association for the Education of Young Children',
            'icon' => '⭐',
            'color' => '#2e7d32'
        ],
        'quality_rated' => [
            'name' => 'Quality Rated',
            'description' => 'Georgia Quality Rated Program',
            'icon' => '✓',
            'color' => '#ff9800'
        ],
        'cpr_certified' => [
            'name' => 'CPR & First Aid',
            'description' => 'All staff CPR and First Aid certified',
            'icon' => '❤️',
            'color' => '#c62828'
        ],
        'security_cameras' => [
            'name' => 'Security Cameras',
            'description' => 'Parent-accessible security camera system',
            'icon' => '📹',
            'color' => '#5c6bc0'
        ]
    ];
    
    public function __construct() {
        add_shortcode('credential_badges', [$this, 'shortcode']);
        add_filter('the_content', [$this, 'auto_append_badges'], 25);
        // add_action('wp_head', [$this, 'output_credentials_schema']);
    }
    
    /**
     * Get credentials for a location
     */
    public static function get_location_credentials($post_id) {
        // Try from post meta
        $credentials = get_post_meta($post_id, 'location_credentials', true);
        
        if (is_array($credentials) && !empty($credentials)) {
            return $credentials;
        }
        
        // Try from hasCredential schema
        $schema_data = get_post_meta($post_id, '_chroma_schema_data', true);
        if ($schema_data && isset($schema_data[0]['data']['hasCredential'])) {
            return $schema_data[0]['data']['hasCredential'];
        }
        
        // Default: assume all locations have these
        return ['ga_decal', 'quality_rated'];
    }
    
    /**
     * Render badges HTML
     */
    public static function render_badges($credentials, $style = 'default') {
        if (empty($credentials)) {
            return '';
        }
        
        ob_start();
        ?>
        <div class="chroma-credential-badges style-<?php echo esc_attr($style); ?>">
            <?php foreach ($credentials as $cred): 
                $badge = self::$badges[$cred] ?? null;
                if (!$badge && is_array($cred)) {
                    // Schema.org format
                    $badge = [
                        'name' => $cred['credentialCategory'] ?? $cred['name'] ?? 'Certified',
                        'description' => $cred['name'] ?? '',
                        'icon' => '✓',
                        'color' => '#333'
                    ];
                }
                if (!$badge) continue;
            ?>
                <div class="badge" style="--badge-color: <?php echo esc_attr($badge['color']); ?>">
                    <span class="badge-icon"><?php echo $badge['icon']; ?></span>
                    <span class="badge-name"><?php echo esc_html($badge['name']); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <style>
            .chroma-credential-badges {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
                margin: 20px 0;
            }
            .chroma-credential-badges .badge {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 8px 14px;
                background: color-mix(in srgb, var(--badge-color) 10%, white);
                border: 2px solid var(--badge-color);
                border-radius: 20px;
                font-size: 13px;
                font-weight: 600;
                color: var(--badge-color);
            }
            .chroma-credential-badges .badge-icon {
                font-size: 16px;
            }
            .chroma-credential-badges.style-compact .badge {
                padding: 5px 10px;
                font-size: 11px;
            }
            .chroma-credential-badges.style-inline {
                display: inline-flex;
            }
        </style>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Shortcode: [credential_badges]
     */
    public function shortcode($atts) {
        $atts = shortcode_atts([
            'post_id' => get_the_ID(),
            'style' => 'default'
        ], $atts);
        
        $credentials = self::get_location_credentials($atts['post_id']);
        
        return self::render_badges($credentials, $atts['style']);
    }
    
    /**
     * Auto-append badges to location pages
     */
    public function auto_append_badges($content) {
        if (!is_singular('location') || doing_filter('get_the_excerpt')) {
            return $content;
        }
        
        if (!get_option('chroma_seo_show_credential_badges', true)) {
            return $content;
        }
        
        $credentials = self::get_location_credentials(get_the_ID());
        $badges_html = self::render_badges($credentials);
        
        // Insert after first paragraph
        $pos = strpos($content, '</p>');
        if ($pos !== false) {
            $content = substr_replace($content, '</p>' . $badges_html, $pos, 4);
        } else {
            $content = $badges_html . $content;
        }
        
        return $content;
    }
    
    /**
     * Output credentials schema
     */
    public function output_credentials_schema() {
        if (!is_singular('location')) {
            return;
        }
        
        $credentials = self::get_location_credentials(get_the_ID());
        
        if (empty($credentials)) {
            return;
        }
        
        $schema_credentials = [];
        
        foreach ($credentials as $cred) {
            if (is_array($cred)) {
                $schema_credentials[] = $cred;
            } elseif (isset(self::$badges[$cred])) {
                $badge = self::$badges[$cred];
                $schema_credentials[] = [
                    '@type' => 'EducationalOccupationalCredential',
                    'name' => $badge['name'],
                    'credentialCategory' => $badge['description']
                ];
            }
        }
        
        if (empty($schema_credentials)) {
            return;
        }
        
        echo '<script type="application/ld+json">';
        echo json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            '@id' => get_permalink() . '#business',
            'hasCredential' => $schema_credentials
        ], JSON_UNESCAPED_SLASHES);
        echo '</script>' . "\n";
    }
}

new Chroma_Credential_Badges();


