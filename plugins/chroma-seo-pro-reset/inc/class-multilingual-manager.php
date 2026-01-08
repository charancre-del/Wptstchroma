<?php
/**
 * Multilingual Manager
 * Handles URL routing, rewrite rules, and link filtering for Spanish sub-directory structure.
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Multilingual_Manager
{
    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * Get instance
     */
    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize hooks
     */
    public function init()
    {
        add_action('init', [$this, 'setup_rewrites']);
        add_filter('query_vars', [$this, 'add_query_vars']);
        
        // URL Filters
        add_filter('home_url', [$this, 'filter_home_url'], 10, 2);
        add_filter('page_link', [$this, 'filter_permalink'], 10, 2);
        add_filter('post_link', [$this, 'filter_permalink'], 10, 2);
        add_filter('post_type_link', [$this, 'filter_permalink'], 10, 2);
        add_filter('term_link', [$this, 'filter_term_link'], 10, 3);
        
        // Add body class for language
        add_filter('body_class', [$this, 'add_body_class']);
        
        // Language Attributes
        add_filter('language_attributes', [$this, 'filter_language_attributes']);

        // Hreflang Tags
        add_action('wp_head', [$this, 'output_hreflang_tags'], 1);
        
        // Frontend Content Swapping (Option A with fallback banner)
        add_filter('the_title', [$this, 'swap_title'], 10, 2);
        add_filter('the_content', [$this, 'swap_content'], 10);
        add_filter('the_excerpt', [$this, 'swap_excerpt'], 10);
        
        // SEO Metadata Localization
        add_filter('pre_get_document_title', [$this, 'localize_seo_title'], 30);
        add_action('wp_head', [$this, 'localize_meta_description'], 1); // Run early to potentially preempt other meta tags
        
        // Internal Link Rewriting
        add_filter('the_content', [$this, 'rewrite_content_urls'], 20);
        add_filter('nav_menu_link_attributes', [$this, 'filter_nav_menu_link'], 10, 4);
        
        // Canonical URL Correction
        add_filter('get_canonical_url', [$this, 'filter_canonical_url'], 10, 2);
        
        // Fallback Banner CSS
        add_action('wp_head', [$this, 'output_fallback_css']);
        
        // Browser Language Detection (Auto-redirect to /es/)
        // add_action('template_redirect', [$this, 'detect_browser_language']);

        // Dynamic Translation of common UI strings
        add_filter('gettext', [$this, 'dynamic_translation_filter'], 20, 3);
    }

    /**
     * Output Hreflang Tags
     */
    public function output_hreflang_tags()
    {
        if (!is_singular() && !is_home() && !is_front_page()) {
            return;
        }

        $post_id = get_the_ID();
        if (!$post_id) return;

        $alternates = self::get_alternates($post_id);
        
        if (empty($alternates['en']) || empty($alternates['es'])) {
            return;
        }

        // x-default should point to the fallback (English)
        echo '<link rel="alternate" hreflang="x-default" href="' . esc_url($alternates['en']) . '" />' . "\n";
        echo '<link rel="alternate" hreflang="en-US" href="' . esc_url($alternates['en']) . '" />' . "\n";
        echo '<link rel="alternate" hreflang="es-US" href="' . esc_url($alternates['es']) . '" />' . "\n";
    }

    /**
     * Setup rewrite rules
     */
    public function setup_rewrites()
    {
        add_rewrite_tag('%chroma_lang%', '([^&]+)');

        // Home Page: example.com/es/
        add_rewrite_rule('^es/?$', 'index.php?chroma_lang=es', 'top');

        // Custom Post Type Archives
        add_rewrite_rule('^es/locations/?$', 'index.php?post_type=location&chroma_lang=es', 'top');
        add_rewrite_rule('^es/programs/?$', 'index.php?post_type=program&chroma_lang=es', 'top');
        
        // Single Custom Post Types
        add_rewrite_rule('^es/locations/(.+?)/?$', 'index.php?location=$matches[1]&chroma_lang=es', 'top');
        add_rewrite_rule('^es/programs/(.+?)/?$', 'index.php?program=$matches[1]&chroma_lang=es', 'top');

        // Standard Pages (catch-all for hierarchical pages)
        // Note: This must come AFTER specific CPT rules to avoid conflict if slug collision, 
        // but typically 'pagename' regex handles paths.
        add_rewrite_rule('^es/(.+?)/?$', 'index.php?pagename=$matches[1]&chroma_lang=es', 'top');
    }

    /**
     * Add query vars
     */
    public function add_query_vars($vars)
    {
        $vars[] = 'chroma_lang';
        return $vars;
    }

    /**
     * Check if current request is Spanish
     */
    public static function is_spanish()
    {
        // PURE PHP IMPLEMENTATION (No WP Functions to avoid Recursion/Crash)
        
        // 1. Check Query Var (Safe if $_GET is accessible)
        if (isset($_GET['chroma_lang']) && $_GET['chroma_lang'] === 'es') {
            return true;
        }

        // 2. Check Global Constant
        if (defined('CHROMA_CURRENT_LANG') && CHROMA_CURRENT_LANG === 'es') {
            return true;
        }
        
        // 3. Robust URL Check (Pure PHP)
        if (isset($_SERVER['REQUEST_URI'])) {
            $uri = $_SERVER['REQUEST_URI'];
            
            // Check for /es/ segment or trailing /es
            // Matches: /es/, /es, /subdir/es/, /subdir/es
            if (strpos($uri, '/es/') !== false || substr($uri, -3) === '/es') {
                return true;
            }
        }

        return false;
    }

    /**
     * Get current language code
     */
    public static function get_current_language()
    {
        return self::is_spanish() ? 'es' : 'en';
    }

    /**
     * Filter Home URL
     * Appends /es/ if current language is Spanish
     */
    public function filter_home_url($url, $path)
    {
        if (self::is_spanish() && !is_admin()) {
            // Prevent infinite loop by using site_url/get_option instead of home_url()
            // And avoid double-stacking if the input $url already has /es/ (which might happen if passed from other filters)
            
            // If the RESULTING url ($url passed in) already has /es/, return strict
            if (strpos($url, '/es/') !== false || substr($url, -3) === '/es') {
                return $url;
            }

            // Construct safe base
            $home = get_option('home');
            
            // Clean paths
            $path = ltrim($path, '/');
            $home = rtrim($home, '/');
            
            // Check if path already starts with es/
            if (strpos($path, 'es/') === 0 || $path === 'es') {
                return $home . '/' . $path;
            }

            return $home . '/es/' . $path;
        }
        return $url;
    }

    /**
     * Filter Permalinks (Pages, Posts, CPTs)
     */
    public function filter_permalink($url, $post)
    {
        if (self::is_spanish() && !is_admin()) {
            $base = rtrim(get_option('home'), '/');
            // Only modify if internal link
            if (strpos($url, $base) !== false) {
                $path = substr($url, strlen($base));
                return $base . '/es' . $path;
            }
        }
        return $url;
    }

    /**
     * Filter Term Links (Categories, Tags)
     */
    public function filter_term_link($url, $term, $taxonomy)
    {
        if (self::is_spanish() && !is_admin()) {
            if (strpos($url, home_url()) !== false) {
                $base = home_url();
                $path = substr($url, strlen($base));
                return home_url('/es' . $path);
            }
        }
        return $url;
    }
    
    /**
     * Add body class
     */
    public function add_body_class($classes) {
        if (self::is_spanish()) {
            $classes[] = 'lang-es';
            $classes[] = 'translate-spanish';
        }
        return $classes;
    }
    
    /**
     * Filter language attributes (<html> tag)
     */
    public function filter_language_attributes($output) {
        if (self::is_spanish()) {
            return 'lang="es-US"';
        }
        return $output;
    }

    /**
     * Get alternate URLs (EN/ES) for a post
     * 
     * @param int|null $post_id
     * @return array ['en' => url, 'es' => url]
     */
    public static function get_alternates($post_id = null)
    {
        if (!$post_id) $post_id = get_the_ID();
        if (!$post_id) return []; // Fallback if outside loop

        // Get instance to access filters
        $instance = self::get_instance();

        // TEMPORARILY REMOVE FILTERS to get raw English URL
        remove_filter('page_link', [$instance, 'filter_permalink'], 10);
        remove_filter('post_link', [$instance, 'filter_permalink'], 10);
        remove_filter('post_type_link', [$instance, 'filter_permalink'], 10);
        remove_filter('home_url', [$instance, 'filter_home_url'], 10);

        $en_url = get_permalink($post_id);

        // RESTORE FILTERS
        add_filter('page_link', [$instance, 'filter_permalink'], 10, 2);
        add_filter('post_link', [$instance, 'filter_permalink'], 10, 2);
        add_filter('post_type_link', [$instance, 'filter_permalink'], 10, 2);
        add_filter('home_url', [$instance, 'filter_home_url'], 10, 2);

        // Check for manual English override
        $manual_en = get_post_meta($post_id, 'alternate_url_en', true);
        if ($manual_en) {
            $en_url = $manual_en;
        }

        // Check for manual Spanish override
        $manual_es = get_post_meta($post_id, 'alternate_url_es', true);

        if ($manual_es) {
            $es_url = $manual_es;
        } else {
            // Unhook home_url filter to ensure we get the raw base URL
            remove_filter('home_url', [$instance, 'filter_home_url'], 10);
            
            $raw_home = home_url();
            $path = str_replace($raw_home, '', $en_url);
            
            // Re-hook home_url filter
            add_filter('home_url', [$instance, 'filter_home_url'], 10, 2);
            
            // Construct ES URL
            // If path is empty (homepage), we just want /es/
            // If path is /locations/foo, we want /es/locations/foo
            $es_url = rtrim($raw_home, '/') . '/es' . $path;
        }
        
        return [
            'en' => $en_url, 
            'es' => $es_url
        ];
    }

    /**
     * Swap Title for Spanish
     */
    public function swap_title($title, $post_id = null)
    {
        if (!self::is_spanish() || is_admin()) return $title;
        if (!$post_id) $post_id = get_the_ID();
        if (!$post_id) return $title;
        
        $es_title = get_post_meta($post_id, '_chroma_es_title', true);
        return $es_title ?: $title;
    }

    /**
     * Swap Content for Spanish (Option A: Fallback Banner)
     */
    public function swap_content($content)
    {
        if (!self::is_spanish() || is_admin()) return $content;
        
        $post_id = get_the_ID();
        if (!$post_id) return $content;
        
        $es_content = get_post_meta($post_id, '_chroma_es_content', true);
        
        if (empty($es_content)) {
            // Option A: Show fallback banner + English content
            $banner = '<div class="chroma-lang-fallback-notice">' .
                      '<span class="dashicons dashicons-info"></span> ' .
                      esc_html__('This page is not yet available in Spanish. Showing English version.', 'chroma-excellence') .
                      '</div>';
            return $banner . $content;
        }
        
        return $es_content;
    }

    /**
     * Swap Excerpt for Spanish
     */
    public function swap_excerpt($excerpt)
    {
        if (!self::is_spanish() || is_admin()) return $excerpt;
        
        $post_id = get_the_ID();
        if (!$post_id) return $excerpt;
        
        $es_excerpt = get_post_meta($post_id, '_chroma_es_excerpt', true);
        return $es_excerpt ?: $excerpt;
    }

    /**
     * Rewrite Internal URLs in Content
     */
    public function rewrite_content_urls($content)
    {
        if (!self::is_spanish() || is_admin()) return $content;
        
        $site_url = preg_quote(home_url(), '/');
        
        // Match href="https://site.com/path" but not href="https://site.com/es/path"
        $pattern = '/href=["\'](' . $site_url . ')(?!\/es\/)([^"\']*)["\']/i';
        $replacement = 'href="$1/es$2"';
        
        return preg_replace($pattern, $replacement, $content);
    }

    /**
     * Filter Nav Menu Link Attributes (for Custom Links)
     */
    public function filter_nav_menu_link($atts, $item, $args, $depth)
    {
        if (!self::is_spanish() || is_admin()) return $atts;
        
        if (!empty($atts['href'])) {
            $href = $atts['href'];
            $site_url = home_url();
            
            // Only modify internal links that don't already have /es/
            if (strpos($href, $site_url) === 0 && strpos($href, $site_url . '/es/') !== 0) {
                $path = substr($href, strlen($site_url));
                $atts['href'] = $site_url . '/es' . $path;
            }
        }
        
        return $atts;
    }

    /**
     * Filter Canonical URL for Spanish Pages
     */
    public function filter_canonical_url($canonical_url, $post)
    {
        if (!self::is_spanish()) return $canonical_url;
        
        // Ensure canonical points to /es/ version
        $site_url = home_url();
        if (strpos($canonical_url, $site_url . '/es/') !== 0) {
            $path = substr($canonical_url, strlen($site_url));
            return $site_url . '/es' . $path;
        }
        
        return $canonical_url;
    }

    /**
     * Output Fallback Banner CSS
     */
    public function output_fallback_css()
    {
        if (!self::is_spanish()) return;
        
        echo '<style>
        .chroma-lang-fallback-notice {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeeba 100%);
            border: 1px solid #ffc107;
            border-left: 4px solid #ffc107;
            color: #856404;
            padding: 12px 16px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .chroma-lang-fallback-notice .dashicons {
            font-size: 18px;
            width: 18px;
            height: 18px;
        }
        </style>';
    }

    /**
     * Detect Browser Language and Redirect
     * Respects user preference via cookie
     */
    public function detect_browser_language()
    {
        // Skip if already on Spanish or in admin
        if (self::is_spanish() || is_admin()) return;
        
        // Skip if user has opted out
        if (isset($_COOKIE['chroma_lang_pref'])) return;
        
        // Skip bots
        if (defined('DOING_CRON') || defined('REST_REQUEST')) return;
        
        // Check Accept-Language header
        $accept_lang = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '';
        
        // Parse language preferences
        $languages = [];
        if ($accept_lang) {
            preg_match_all('/([a-z]{2})(?:-[a-zA-Z]+)?(?:;q=([0-9.]+))?/', strtolower($accept_lang), $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                $lang = $match[1];
                $quality = isset($match[2]) ? (float)$match[2] : 1.0;
                $languages[$lang] = $quality;
            }
            arsort($languages);
        }
        
        // Check if Spanish is preferred language
        $preferred = array_keys($languages);
        if (!empty($preferred) && $preferred[0] === 'es') {
            // Set cookie to remember we've redirected (24 hour expiry)
            setcookie('chroma_lang_pref', 'auto', time() + DAY_IN_SECONDS, '/');
            
            // Get Spanish URL
            $alternates = self::get_alternates(get_the_ID());
            if (!empty($alternates['es'])) {
                wp_redirect($alternates['es']);
                exit;
            }
        }
    }
    /**
     * Localize SEO Title
     */
    public function localize_seo_title($title) {
        if (!self::is_spanish()) return $title;
        
        $post_id = get_the_ID();
        if (!$post_id) return $title;
        
        $es_seo_title = get_post_meta($post_id, '_chroma_es_seo_title', true);
        if ($es_seo_title) return $es_seo_title;
        
        $es_title = get_post_meta($post_id, '_chroma_es_title', true);
        if ($es_title) return $es_title . ' | Chroma';
        
        return $title;
    }

    /**
     * Localize Meta Description
     */
    public function localize_meta_description() {
        if (!self::is_spanish()) return;
        
        $post_id = get_the_ID();
        if (!$post_id) return;
        
        $es_meta_desc = get_post_meta($post_id, '_chroma_es_meta_description', true);
        if ($es_meta_desc) {
            // Remove the default meta description if it exists
            remove_action('wp_head', 'chroma_shared_meta_description', 2);
            echo '<meta name="description" content="' . esc_attr($es_meta_desc) . '" />' . "\n";
        }
    }

    /**
     * Filter gettext to handle dynamic translation of common UI strings
     */
    public function dynamic_translation_filter($translated, $text, $domain) {
        if ($domain !== 'chroma-excellence' || !self::is_spanish()) {
            return $translated;
        }

        static $mapping = null;
        if ($mapping === null) {
            $mapping = [
                'Quick Links' => 'Enlaces Rápidos',
                'Contact' => 'Contacto',
                'Connect With Us' => 'Conéctate con Nosotros',
                'Latest Blogs' => 'Últimos Blogs',
                'No recent updates.' => 'Sin actualizaciones recientes.',
                'Privacy Policy' => 'Política de Privacidad',
                'Terms of Service' => 'Términos de Servicio',
                'Menu' => 'Menú',
                'Skip to content' => 'Saltar al contenido',
                'Toggle menu' => 'Alternar menú',
                'Close menu' => 'Cerrar menú',
                'Premium childcare & early education across Metro Atlanta.' => 'Cuidado infantil premium y educación temprana en todo Metro Atlanta.',
                'Chroma Early Learning Academy. All rights reserved.' => 'Chroma Early Learning Academy. Todos los derechos reservados.',
                'Ready to experience the Chroma difference?' => '¿Listo para experimentar la diferencia Chroma?',
                'Schedule a Tour' => 'Agenda un Recorrido',
                'Book a Tour' => 'Reserva un Recorrido',
                'Early Learning Academy' => 'Academia de Educación Temprana', // Fallback if customizer is empty
                'Early Learning' => 'Educación Temprana',
                'Academy' => 'Academia',
                'Ready to enroll in <strong>%s</strong>?' => '¿Listo para inscribirse en <strong>%s</strong>?',
                'Ready to visit our <strong>%s</strong> campus?' => '¿Listo para visitar nuestro campus de <strong>%s</strong>?',
                
                // About Page
                'Established 2015' => 'Establecido en 2015',
                'More than a school. <span class="text-chroma-yellow italic">A second home.</span>' => 'Más que una escuela. <span class="text-chroma-yellow italic">Un segundo hogar.</span>',
                'We founded Chroma on a simple belief: Early education should be a perfect blend of rigorous cognitive development and the comforting warmth of family.' => 'Fundamos Chroma con una creencia simple: la educación temprana debe ser una combinación perfecta de desarrollo cognitivo riguroso y la calidez reconfortante de la familia.',
                '"To cultivate a vibrant community of lifelong learners by blending academic rigor with the nurturing warmth of home, ensuring every child feels seen, valued, and capable."' => '"Cultivar una comunidad vibrante de aprendices de por vida combinando el rigor académico con la calidez del hogar, asegurando que cada niño se sienta visto, valorado y capaz."',
                'From one classroom to a community.' => 'De un aula a una comunidad.',
                'Over the last decade, we have grown into a network of 19+ campuses across Metro Atlanta, yet each location retains the intimacy and personal touch of that very first school. We are locally owned, operated by educators, and driven by the success of our families.' => 'Durante la última década, hemos crecido hasta convertirnos en una red de más de 19 campus en Metro Atlanta, pero cada ubicación conserva la intimidad y el toque personal de esa primera escuela. Somos propiedad local, operados por educadores e impulsados por el éxito de nuestras familias.',
                'Locations' => 'Ubicaciones',
                'Students' => 'Estudiantes',
                'Educators' => 'Educadores',
                'Licensed' => 'Licenciado',
                'The Heart of Chroma.' => 'El Corazón de Chroma.',
                'Our Educators' => 'Nuestros Educadores',
                'We don\'t just hire supervisors; we hire career educators. Our teachers are the most valuable asset in our classrooms, selected for their passion, patience, and professional credentials.' => 'No solo contratamos supervisores; contratamos educadores de carrera. Nuestros maestros son el activo más valioso en nuestras aulas, seleccionados por su pasión, paciencia y credenciales profesionales.',
                'Certified & Credentialed' => 'Certificado y Acreditado',
                'Safety First' => 'Seguridad Primero',
                'Continuous Growth' => 'Crecimiento Continuo',
                'The Chroma Standard' => 'El Estándar Chroma',
                'Unconditional Joy' => 'Alegría Incondicional',
                'Radical Safety' => 'Seguridad Radical',
                'Academic Excellence' => 'Excelencia Académica',
                'Open Partnership' => 'Colaboración Abierta',
                'Led by educators, not investors.' => 'Dirigido por educadores, no inversores.',
                'Leadership' => 'Liderazgo',
                'Fueling growing minds.' => 'Alimentando mentes en crecimiento.',
                'Giving back to our future.' => 'Devolviendo a nuestro futuro.',
                'Our Purpose' => 'Nuestro Propósito',
                'Our Story' => 'Nuestra Historia',
                'Our Mission' => 'Nuestra Misión',
                'Find a Campus' => 'Buscar un Campus',
                'Find a Location' => 'Buscar una Ubicación',
                'Find a School' => 'Buscar una Escuela',
                'Read Bio' => 'Leer Biografía',
                'Read bio for' => 'Leer biografía de',
                
                // Parents Page
                'Parent Dashboard' => 'Panel de Padres',
                'Partners in your child\'s journey.' => 'Socios en el viaje de su hijo.',

                // 404 Page
                'Ruh-roh! This page is playing hide-and-seek.' => '¡Oh, no! Esta página está jugando a las escondidas.',
                'We\'ve checked the toy bin, looked under the rugs, and even asked the goldfish, but we can\'t find this page anywhere. It must be really good at hiding!' => 'Hemos revisado la caja de juguetes, mirado debajo de las alfombras e incluso le hemos preguntado al pez dorado, pero no podemos encontrar esta página por ninguna parte. ¡Debe ser muy buena escondiéndose!',
                'Go Home' => 'Ir a Inicio',
                'Everything you need to manage your enrollment, stay connected, and engage with the Chroma community.' => 'Todo lo que necesita para administrar su inscripción, mantenerse conectado e interactuar con la comunidad Chroma.',
                'Parent Essentials' => 'Esenciales para Padres',
                'Procare Cloud' => 'Nube Procare',
                'Daily reports, photos, and attendance tracking.' => 'Informes diarios, fotos y seguimiento de asistencia.',
                'Tuition Portal' => 'Portal de Matrícula',
                'Securely view statements and make payments.' => 'Ver extractos y realizar pagos de forma segura.',
                'Parent Handbook' => 'Manual para Padres',
                'Policies, procedures, and operational details.' => 'Políticas, procedimientos y detalles operativos.',
                'Enrollment Agreement' => 'Acuerdo de Inscripción',
                'Update your annual enrollment documents.' => 'Actualice sus documentos de inscripción anuales.',
                'GA Pre-K Enrollment' => 'Inscripción GA Pre-K',
                'Lottery registration and required state forms.' => 'Registro de lotería y formularios estatales requeridos.',
                'Join Waitlist' => 'Unirse a la Lista de Espera',
                'Reserve a spot for siblings or future terms.' => 'Reserve un lugar para hermanos o términos futuros.',
                'Traditions & Celebrations' => 'Tradiciones y Celebraciones',
                'Quarterly Family Events' => 'Eventos Familiares Trimestrales',
                'Pre-K Graduation' => 'Graduación de Pre-K',
                'Parent-Teacher Conferences' => 'Conferencias Padres-Maestros',
                'Wellness' => 'Bienestar',
                'What\'s for lunch?' => '¿Qué hay para almorzar?',
                'Our in-house chefs prepare balanced, CACFP-compliant meals fresh daily. We are a nut-aware facility.' => 'Nuestros chefs internos preparan comidas equilibradas que cumplen con CACFP frescas todos los días. Somos una instalación consciente de las nueces.',
                'Monthly Menus' => 'Menús Mensuales',
                'Current Month Menu' => 'Menú del Mes Actual',
                'Standard (Ages 1-12)' => 'Estándar (Edades 1-12)',
                'Infant Puree Menu' => 'Menú de Puré para Bebés',
                'Stage 1 & 2 Solids' => 'Sólidos Etapa 1 y 2',
                'Allergy Statement' => 'Declaración de Alergias',
                'Our Nut-Free Protocols' => 'Nuestros Protocolos Libres de Nueces',
                'Fresh Fruit Daily' => 'Fruta Fresca Diaria',
                'Safe. Secure. Connected.' => 'Seguro. Protegido. Conectado.',
                '24/7 Monitored Cameras' => 'Cámaras Monitoreadas 24/7',
                'Real-Time Updates' => 'Actualizaciones en Tiempo Real',
                'Secure Access Control' => 'Control de Acceso Seguro',
                'Operational Policy FAQ' => 'Preguntas Frecuentes de Política Operativa',
                'What is the sick child policy?' => '¿Cuál es la política de niños enfermos?',
                'How do you handle inclement weather?' => '¿Cómo manejan el clima inclemente?',
                'What is the late pickup policy?' => '¿Cuál es la política de recogida tardía?',
                'Love the Chroma family?' => '¿Amas a la familia Chroma?',
                'Refer a friend and receive a <strong>$100 tuition credit</strong> when they enroll.' => 'Recomienda a un amigo y recibe un <strong>crédito de matrícula de $100</strong> cuando se inscriba.',
                'Refer a Friend' => 'Recomendar a un Amigo',
                'Life at Chroma' => 'Vida en Chroma',
                'Moments of Joy' => 'Momentos de Alegría',
                'Loading content...' => 'Cargando contenido...',
                'Open in new tab' => 'Abrir en nueva pestaña',
                
                // Contact Page
                'Get in Touch' => 'Ponte en Contacto',
                'How can we support your family today?' => '¿Cómo podemos apoyar a tu familia hoy?',
                'Whether you are looking for a new school, applying for a job, or have a media inquiry, we are here to connect you with the right team.' => 'Ya sea que busques una nueva escuela, solicites un trabajo o tengas una consulta de medios, estamos aquí para conectarte con el equipo adecuado.',
                'Send Message' => 'Enviar Mensaje',
                'Corporate Office' => 'Oficina Corporativa',
                'Looking for care?' => '¿Buscas cuidado?',
                'Find a school near you to check pricing, availability, and book a tour.' => 'Encuentra una escuela cerca de ti para consultar precios, disponibilidad y reservar un recorrido.',
                'Current Family?' => '¿Familia Actual?',
                'Access the parent portal for tuition payments, daily reports, and photos.' => 'Accede al portal para padres para pagos de matrícula, informes diarios y fotos.',
                'Parent Portal' => 'Portal para Padres',
                'Join the team?' => '¿Unirse al equipo?',
                'We are always hiring passionate educators. View open positions today.' => 'Siempre estamos contratando educadores apasionados. Vea las posiciones abiertas hoy.',
                'View Careers' => 'Ver Carreras',
                'Mailing Address' => 'Dirección de Correo',
                'Phone' => 'Teléfono',
                'Mon-Fri, 9am - 5pm EST' => 'Lun-Vie, 9am - 5pm EST',
                'Department Emails' => 'Correos del Departamento',
                'Enrollment' => 'Inscripciones',
                'Careers' => 'Carreras',
                'Press / Media' => 'Prensa / Medios',
                'Acquisitions' => 'Adquisiciones',
                'General Inquiries' => 'Consultas Generales',
                'For general questions not related to a specific campus tour.' => 'Para preguntas generales no relacionadas con un recorrido específico del campus.',
                'Frequently Asked Questions' => 'Preguntas Frecuentes',
                'How do I schedule a tour?' => '¿Cómo programo un recorrido?',
                'Are meals included in tuition?' => '¿Están incluidas las comidas en la matrícula?',
                'How do I check my position on a waitlist?' => '¿Cómo verifico mi posición en una lista de espera?',
                
                // Programs
                'A Rhythm, Not a Routine' => 'Un Ritmo, No una Rutina',
                'View Curriculum' => 'Ver Currículo',
                'View Lesson Plan' => 'Ver Plan de Lección',
                'View Locations' => 'Ver Ubicaciones',
                'All Programs' => 'Todos los Programas',
                'Prismpath™ Focus' => 'Enfoque Prismpath™',
                'Our Prismpath™ Focus' => 'Nuestro Enfoque Prismpath™',
                '%s Lesson Plan' => 'Plan de Lección de %s',
                'Download' => 'Descargar',
                'Loading lesson plan...' => 'Cargando plan de lección...',
                'Physical' => 'Físico',
                'Emotional' => 'Emocional',
                'Social' => 'Social',
                'Academic' => 'Académico',
                'Creative' => 'Creativo',
                
                // City Pages
                'Serving %s & %s County' => 'Sirviendo a %s y el Condado de %s',
                'The Best Daycare in <span class="italic text-chroma-blue">%s, %s.</span>' => 'La Mejor Guardería en <span class="italic text-chroma-blue">%s, %s.</span>',
                'Are you looking for "daycare near me"? Discover the highest-rated early learning centers in the %s area, featuring the Prismpath™ curriculum and GA Pre-K.' => '¿Está buscando "guardería cerca de mí"? Descubra los centros de aprendizaje temprano mejor calificados en el área de %s, con el plan de estudios Prismpath™ y GA Pre-K.',
                'See Locations in %s' => 'Ver Ubicaciones en %s',
                'Early Education and <br> Care in <span class="text-chroma-blue">%s, GA</span>' => 'Educación Temprana y <br> Cuidado en <span class="text-chroma-blue">%s, GA</span>',
                'Our school is more than a daycare. Through purposeful play and nurturing guidance, we help lay the foundation for a lifelong love of learning.' => 'Nuestra escuela es más que una guardería. A través del juego con propósito y la guía cariñosa, ayudamos a sentar las bases para un amor por el aprendizaje de por vida.',
                'Conveniently located near major highways and down the road from local landmarks and top-rated elementary schools, we are the convenient choice for %s working parents. Come by and see Prismpath™ in action at one of our nearby campuses.' => 'Convenientemente ubicado cerca de las principales autopistas y cerca de puntos de referencia locales y escuelas primarias de primera categoría, somos la opción conveniente para los padres trabajadores de %s. Ven a ver Prismpath™ en acción en uno de nuestros campus cercanos.',
                'Chroma Locations Serving %s' => 'Ubicaciones de Chroma que Sirven a %s',
                'Select the campus closest to your home or work.' => 'Seleccione el campus más cercano a su hogar o trabajo.',
                'Also proudly serving families in:' => 'También sirviendo orgullosamente a familias en:',
                'Programs Available in %s' => 'Programas Disponibles en %s',
                'World-class curriculum served locally.' => 'Currículo de clase mundial servido localmente.',
                'Questions about Childcare in %s' => 'Preguntas sobre el Cuidado Infantil en %s',
                'Do you offer GA Lottery Pre-K in %s?' => '¿Ofrecen GA Lottery Pre-K en %s?',
                'Yes! Our locations serving %s participate in the Georgia Lottery Pre-K program. It is tuition-free for all 4-year-olds living in Georgia.' => '¡Sí! Nuestras ubicaciones que sirven a %s participan en el programa Georgia Lottery Pre-K. Es gratuito para todos los niños de 4 años que viven en Georgia.',
                'Do you provide transportation from %s schools?' => '¿Proporcionan transporte desde las escuelas de %s?',
                'We provide safe bus transportation from most major elementary schools in the %s School District. Check the specific campus page for a full list.' => 'Proporcionamos transporte seguro en autobús desde la mayoría de las principales escuelas primarias en el Distrito Escolar de %s. Consulte la página del campus específico para obtener una lista completa.',
                'What ages do you accept at your %s centers?' => '¿Qué edades aceptan en sus centros de %s?',
                'How do I enroll my child in %s?' => '¿Cómo inscribo a mi hijo en %s?',
                'The best way to start is by scheduling a tour at your preferred location. You can book online or call us directly. We\'ll walk you through the enrollment process and answer all your questions.' => 'La mejor manera de comenzar es programando un recorrido en su ubicación preferida. Puede reservar en línea o llamarnos directamente. Lo guiaremos a través del proceso de inscripción y responderemos todas sus preguntas.',
                'Back to All Communities' => 'Volver a Todas las Comunidades',
                'Communities' => 'Comunidades',
                'Our <span class="text-chroma-blue italic">Communities</span>' => 'Nuestras <span class="text-chroma-blue italic">Comunidades</span>',
                'Discover our network of excellence across Georgia\'s most vibrant neighborhoods. Select your city to find local campuses.' => 'Descubra nuestra red de excelencia en los barrios más vibrantes de Georgia. Seleccione su ciudad para encontrar campus locales.',
                'Search for your city...' => 'Busca tu ciudad...',
                'All' => 'Todos',
                'Other' => 'Otro',
                'View Schools' => 'Ver Escuelas',
                'No communities found.' => 'No se encontraron comunidades.',
                'No cities match your search.' => 'Ninguna ciudad coincide con tu búsqueda.',
                'Don\'t see your city?' => '¿No ves tu ciudad?',
                'We are constantly expanding. Contact our enrollment team to find the nearest campus to you.' => 'Estamos en constante expansión. Contacte a nuestro equipo de inscripción para encontrar el campus más cercano a usted.',
                'No locations are currently linked to this city. Please check back soon!' => 'Actualmente no hay ubicaciones vinculadas a esta ciudad. ¡Vuelve a consultar pronto!',
                'View All Locations →' => 'Ver Todas las Ubicaciones →',
                'Contact Us' => 'Contáctanos',
                'Search by ZIP code or city name...' => 'Buscar por código postal o nombre de la ciudad...',
                
                // Program Archive
                'Ages 6 weeks to 12 years' => 'Edades de 6 semanas a 12 años',
                'Programs and Curriculum that grows <span class="text-chroma-red italic">with them.</span>' => 'Programas y Currículo que crece <span class="text-chroma-red italic">con ellos.</span>',
                'From sensory discovery in our infant suites to the project-based learning of Pre-K, every program uses our proprietary Prismpath™ model to meet children exactly where they are.' => 'Desde el descubrimiento sensorial en nuestras suites para bebés hasta el aprendizaje basado en proyectos de Pre-K, cada programa utiliza nuestro modelo patentado Prismpath™ para encontrar a los niños exactamente donde están.',
                'Schedule Tour' => 'Agendar Recorrido',
                'No programs found. Please add programs from the WordPress admin.' => 'No se encontraron programas. Por favor agregue programas desde el administrador de WordPress.',
                'The Prismpath™ Model' => 'El Modelo Prismpath™',
                'Just as a prism refracts light into a full spectrum of color, our proprietary curriculum refracts play into five key pillars of development.' => 'Así como un prisma refracta la luz en un espectro completo de colores, nuestro currículo patentado refracta el juego en cinco pilares clave del desarrollo.',
                'Physical & Sensory Health' => 'Salud Física y Sensorial',
                'Emotional Intelligence' => 'Inteligencia Emocional',
                'Social Connection' => 'Conexión Social',
                'Academic Logic' => 'Lógica Académica',
                'Creative Expression' => 'Expresión Creativa',
                'Our Methodology' => 'Nuestra Metodología',
                'More than just daycare.' => 'Más que una simple guardería.',
                'We believe that education isn\'t just about filling a bucket, but lighting a fire. Our curriculum ensures that by the time your child graduates from Chroma, they are not just "school ready"—they are life ready.' => 'Creemos que la educación no se trata solo de llenar un cubo, sino de encender un fuego. Nuestro currículo asegura que para cuando su hijo se gradúe de Chroma, no solo esté "listo para la escuela", sino listo para la vida.',
                'Cognitive Growth' => 'Crecimiento Cognitivo',
                'Critical thinking & problem solving.' => 'Pensamiento crítico y resolución de problemas.',
                'Emotional IQ' => 'Coeficiente Emocional',
                'Empathy, regulation & kindness.' => 'Empatía, regulación y amabilidad.',
                'Ready to find your fit?' => '¿Listo para encontrar su lugar?',
                'Every campus offers tours so you can meet the teachers, see the classrooms, and experience the Chroma culture firsthand.' => 'Cada campus ofrece recorridos para que pueda conocer a los maestros, ver las aulas y experimentar la cultura Chroma de primera mano.',
                'Find a Location' => 'Buscar una Ubicación',
                
                // Stories / Blog
                'The Blog' => 'El Blog',
                'Chroma Stories' => 'Historias Chroma',
                'Parenting tips, classroom spotlights, and insights from our educators.' => 'Consejos de crianza, puntos destacados del aula y conocimientos de nuestros educadores.',
                'Featured' => 'Destacado',
                'Read Story' => 'Leer Historia',
                '&larr; Previous' => '&larr; Anterior',
                'Next &rarr;' => 'Siguiente &rarr;',
                'Page %s of %s' => 'Página %s de %s',
                'No stories found. Check back soon!' => 'No se encontraron historias. ¡Vuelve a consultar pronto!',
                'Back to Stories' => 'Volver a Historias',
                'Contributor' => 'Colaborador',
                'More from Chroma' => 'Más de Chroma',
                'Book Tour' => 'Agendar Recorrido',
                'Uncategorized' => 'Sin Categoría',
                
                // Specialized Pages (Careers, Employers, Acquisitions)
                'Apply Now' => 'Aplicar Ahora',
                'No current openings. Please check back later.' => 'No hay vacantes actuales. Por favor, vuelva a consultar más tarde.',
                'Apply for Position' => 'Solicitar Posición',
                'View Current Openings' => 'Ver Vacantes Actuales',
                'Corporate Childcare Solutions:' => 'Soluciones Corporativas de Cuidado Infantil:',
                'Critical Infrastructure for Your Team' => 'Infraestructura Crítica para su Equipo',
                'Our Partnership Models' => 'Nuestros Modelos de Asociación',
                'Company Name' => 'Nombre de la Empresa',
                'HR Contact Name' => 'Nombre de Contacto de RR.HH.',
                'Work Email' => 'Correo Electrónico de Trabajo',
                'Request Info Kit' => 'Solicitar Kit de Información',
                'Why Partner With Chroma?' => '¿Por qué Asociarse con Chroma?',
                'Start the Conversation' => 'Iniciar la Conversación',
                'Fill out the form below and our acquisitions team will be in touch.' => 'Complete el formulario a continuación y nuestro equipo de adquisiciones se pondrá en contacto.',
                'Our Process' => 'Nuestro Proceso',
                'In the meantime, reach out to:' => 'Mientras tanto, comuníquese con:',
                'Search' => 'Buscar',
                'Search entire site...' => 'Buscar en todo el sitio...',
            ];
        }

        return $mapping[$text] ?? $translated;
    }
}

if (!function_exists('chroma_get_alternates')) {
    /**
     * Global helper for theme usage
     */
    function chroma_get_alternates($post_id = null) {
        if (class_exists('Chroma_Multilingual_Manager')) {
            return Chroma_Multilingual_Manager::get_alternates($post_id);
        }
        return [];
    }
}

if (!function_exists('chroma_get_translated_meta')) {
    /**
     * Get translated meta field with fallback
     */
    function chroma_get_translated_meta($post_id, $key, $single = true) {
        if (class_exists('Chroma_Multilingual_Manager') && method_exists('Chroma_Multilingual_Manager', 'is_spanish') && Chroma_Multilingual_Manager::is_spanish()) {
            $es_val = get_post_meta($post_id, '_chroma_es_' . $key, $single);
            if ($es_val) return $es_val;
        }
        return get_post_meta($post_id, $key, $single);
    }
}

if (!function_exists('chroma_get_theme_mod')) {
    /**
     * Localized theme mod helper
     */
    function chroma_get_theme_mod($name, $default = false) {
        $val = get_theme_mod($name, $default);
        if (class_exists('Chroma_Multilingual_Manager') && Chroma_Multilingual_Manager::is_spanish()) {
            $es_val = get_theme_mod($name . '_es');
            if ($es_val) return $es_val;
        }
        return $val;
    }
}
