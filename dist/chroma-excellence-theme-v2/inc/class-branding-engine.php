<?php
/**
 * Chroma Branding Engine
 * 
 * Drives theme aesthetics via JSON configuration and CSS variables.
 * Enables zero-code white-labeling.
 *
 * @package ChromaExcellence
 */

if (!defined('ABSPATH')) exit;

class Chroma_Branding_Engine {

    private static $instance = null;
    private $settings = [];

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    protected function __construct() {
        $this->load_settings();
        add_action('wp_head', [$this, 'inject_css_variables'], 1);
    }

    private function load_settings() {
        $json_path = CHROMA_THEME_DIR . '/theme-settings.json';
        if (file_exists($json_path)) {
            $content = file_get_contents($json_path);
            $this->settings = json_decode($content, true);
        }
    }

    public function get_setting($group, $key = null) {
        if (!isset($this->settings[$group])) return null;
        if (null === $key) return $this->settings[$group];
        return $this->settings[$group][$key] ?? null;
    }

    public function inject_css_variables() {
        $brand = $this->get_setting('brand');
        $fonts = $this->get_setting('fonts');

        if (empty($brand)) return;

        ?>
        <style id="chroma-branding-vars">
            :root {
                --cqa-primary: <?php echo esc_html($brand['primary']); ?>;
                --cqa-secondary: <?php echo esc_html($brand['secondary']); ?>;
                --cqa-accent: <?php echo esc_html($brand['accent']); ?>;
                --cqa-ink: <?php echo esc_html($brand['ink']); ?>;
                --cqa-bg: <?php echo esc_html($brand['background']); ?>;
                --cqa-radius: <?php echo esc_html($brand['radius']); ?>;
                
                --cqa-font-heading: <?php echo esc_html($fonts['heading']); ?>;
                --cqa-font-body: <?php echo esc_html($fonts['body']); ?>;

                /* Utility derivations */
                --cqa-primary-rgb: <?php echo $this->hex2rgb($brand['primary']); ?>;
            }
        </style>
        <?php
    }

    private function hex2rgb($hex) {
        $hex = str_replace("#", "", $hex);
        if(strlen($hex) == 3) {
            $r = hexdec(substr($hex,0,1).substr($hex,0,1));
            $g = hexdec(substr($hex,1,1).substr($hex,1,1));
            $b = hexdec(substr($hex,2,1).substr($hex,2,1));
        } else {
            $r = hexdec(substr($hex,0,2));
            $g = hexdec(substr($hex,2,2));
            $b = hexdec(substr($hex,4,2));
        }
        return "$r, $g, $b";
    }
}

// Initialise
Chroma_Branding_Engine::get_instance();
