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
        if (!is_singular() && !is_home() && !is_front_page() && !get_query_var('chroma_combo')) {
            return;
        }

        $post_id = is_singular() ? get_the_ID() : null;
        // If front page, we might not have a post ID in global context or we want to be explicit
        if (!$post_id && (is_front_page() || is_home())) {
            $post_id = get_option('page_on_front');
        }

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
            
            // Clean paths
            $path = ltrim($path, '/');
            
            // Check if URL or Path already contains /es/
            if (strpos($url, '/es/') !== false || strpos($path, 'es/') === 0 || $path === 'es') {
                return $url;
            }

            // Construct safe base
            $home = rtrim(get_option('home'), '/');
            
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
            // Avoid double stacking
            if (strpos($url, '/es/') !== false) {
                return $url;
            }

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
            $base = rtrim(get_option('home'), '/');
            if (strpos($url, $base) !== false) {
                $path = substr($url, strlen($base));
                return $base . '/es' . $path;
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
     * Get alternate URLs (EN/ES) for a post or current page
     * 
     * @param int|null $post_id
     * @return array ['en' => url, 'es' => url]
     */
    public static function get_alternates($post_id = null)
    {
        global $wp;

        // 1. Determine the base English URL based on the current context
        if ($post_id) {
            $en_url = get_permalink($post_id);
        } elseif (is_singular()) {
            $en_url = get_permalink();
        } elseif (is_front_page() || is_home()) {
            $en_url = rtrim(get_option('home'), '/');
        } elseif (is_post_type_archive()) {
            $en_url = get_post_type_archive_link(get_query_var('post_type'));
        } elseif (is_category() || is_tag() || is_tax()) {
            $en_url = get_term_link(get_queried_object());
        } elseif (is_404()) {
            $en_url = rtrim(get_option('home'), '/'); // 404s fallback to home
        } else {
            // Check for COMBO pages or other custom requests using query vars or request uri
            $en_url = home_url($wp->request);
        }

        // Safety: If get_permalink or others failed
        if (is_wp_error($en_url) || !$en_url) {
            $en_url = rtrim(get_option('home'), '/');
        }

        // 2. Clear out any /es/ segments to get "Pure English" base
        // This is critical to avoid /es/es/ or looping
        $base_home = rtrim(get_option('home'), '/');
        $en_url = str_replace($base_home . '/es/', $base_home . '/', $en_url);
        if (substr($en_url, -3) === '/es') {
             $en_url = substr($en_url, 0, -3);
        }

        // 3. Handle Manual Overrides for singular posts
        if ($post_id || is_singular()) {
            $id = $post_id ?: get_the_ID();
            $manual_en = get_post_meta($id, 'alternate_url_en', true);
            if ($manual_en) $en_url = $manual_en;
            
            $manual_es = get_post_meta($id, 'alternate_url_es', true);
            if ($manual_es) {
                return ['en' => $en_url, 'es' => $manual_es];
            }
        }

        // 4. Construct Spanish URL
        $path = str_replace($base_home, '', $en_url);
        $path = ltrim($path, '/');
        
        $es_url = $base_home . '/es/' . $path;

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
            // Option A: Just show English content (Silent Fallback)
            return $content;
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
        
        $site_url = preg_quote(rtrim(get_option('home'), '/'), '/');
        
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
            $site_url = rtrim(get_option('home'), '/');
            
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
        $site_url = rtrim(get_option('home'), '/');
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
                
                // Location Page Extras
                'Now Enrolling: Pre-K & Toddlers' => 'Inscripciones Abiertas: Pre-K y Niños Pequeños',
                "%s's home for brilliant beginnings." => 'El hogar de %s para comienzos brillantes.',
                'Schedule Visit' => 'Programar Visita',
                'Ages Served' => 'Edades Atendidas',
                'Google Rating' => 'Calificación de Google',
                'Mon - Fri' => 'Lun - Vie',
                'Campus Features' => 'Características del Campus',
                'Designed for discovery.' => 'Diseñado para el descubrimiento.',
                'Every corner of our %s campus is intentional—from the soft lighting in our infant suites to the collaborative stations in our Pre-K classrooms.' => 'Cada rincón de nuestro campus de %s es intencional, desde la iluminación suave en nuestras suites para bebés hasta las estaciones de colaboración en nuestras aulas de Pre-K.',
                'Secure Access' => 'Acceso Seguro',
                'Keypad entry, 24/7 video monitoring, and a staffed front desk ensure your child is always safe.' => 'La entrada con teclado, el monitoreo por video las 24 horas, los 7 días de la semana y un mostrador con personal aseguran que su hijo esté siempre seguro.',
                'Nature Playground' => 'Patio de Naturaleza',
                'Our oversized, shaded outdoor space features gardening beds, trike paths, and natural sensory zones.' => 'Nuestro espacio al aire libre sombreado y de gran tamaño cuenta con camas de jardinería, senderos para triciclos y zonas sensoriales naturales.',
                'STEM Atelier' => 'Atelier STEM',
                'A dedicated studio for science experiments, light table exploration, and early engineering projects.' => 'Un estudio dedicado a experimentos científicos, exploración de mesas de luz y proyectos de ingeniería temprana.',
                'GA Lottery Pre-K' => 'Pre-K de la Lotería de GA',
                'We are a proud partner of the Georgia Pre-K Program, offering tuition-free education for 4-year-olds.' => 'Somos un socio orgulloso del Programa Pre-K de Georgia, que ofrece educación gratuita para niños de 4 años.',
                'Welcome to Chroma %s.' => 'Bienvenido a Chroma %s.',
                'Campus Director' => 'Director del Campus',
                'Explore Our Campus' => 'Explore Nuestro Campus',
                'Take a Virtual Tour' => 'Realice un Recorrido Virtual',
                'Walk through our %s campus from the comfort of your home. Explore our classrooms, outdoor play areas, and learning spaces.' => 'Recorra nuestro campus de %s desde la comodidad de su hogar. Explore nuestras aulas, áreas de juego al aire libre y espacios de aprendizaje.',
                'Programs at this location' => 'Programas en esta ubicación',
                'Curriculum tailored to the specific developmental window of your child.' => 'Currículo adaptado a la ventana de desarrollo específica de su hijo.',
                'View Curriculum Details' => 'Ver Detalles del Currículo',
                'Learn More' => 'Más Información',
                'Family Stories' => 'Historias Familiares',
                'Why Families Love Us' => 'Por Qué las Familias nos Aman',
                'Parent Review' => 'Reseña de Padres',
                'Do you offer tours?' => '¿Ofrecen recorridos?',
                'Yes! We encourage all families to book a tour to see our classrooms, meet our directors, and experience the Chroma difference firsthand.' => '¡Sí! Alentamos a todas las familias a reservar un recorrido para ver nuestras aulas, conocer a nuestros directores y experimentar la diferencia de Chroma de primera mano.',
                'What ages do you serve?' => '¿Qué edades atienden?',
                'We typically serve children from 6 weeks (Infants) up to 12 years old (After School), though specific programs may vary by campus.' => 'Normalmente atendemos a niños desde las 6 semanas (bebés) hasta los 12 años (después de la escuela), aunque los programas específicos pueden variar según el campus.',
                'Is food included?' => '¿Está incluida la comida?',
                'Yes, we provide nutritious, child-friendly meals and snacks prepared fresh daily.' => 'Sí, proporcionamos comidas y refrigerios nutritivos y aptos para niños preparados frescos todos los días.',
                'Visit Us' => 'Visítenos',
                'Come see the magic in person.' => 'Venga a ver la magia en persona.',
                'Tours are the best way to feel the Chroma difference.' => 'Los recorridos son la mejor manera de sentir la diferencia de Chroma.',
                'We are available for tours Monday through Friday' => 'Estamos disponibles para recorridos de lunes a viernes',
                ' between %s and %s' => ' entre las %s y las %s',
                '. We welcome little ones to accompany on a tour!' => '. ¡Damos la bienvenida a los más pequeños para que nos acompañen en un recorrido!',
                'Address' => 'Dirección',
                'Get Directions' => 'Obtener Direcciones',
                'Hours of Operation' => 'Horas de Operación',
                'Monday - Friday:' => 'Lunes - Viernes:',
                'Weekends: Closed' => 'Fines de semana: Cerrado',
                'School Pickups' => 'Recogidas Escolares',
                'We provide pickup service to:' => 'Brindamos servicio de recogida a:',
                'Request a Tour' => 'Solicitar un Recorrido',
                "Fill out the form below and we'll contact you to confirm a time." => 'Complete el formulario a continuación y nos pondremos en contacto con usted para confirmar una hora.',
                'or' => 'o',
                'Book a Tour Now' => 'Reserve un Recorrido Ahora',
                'Serving %s Families' => 'Sirviendo a Familias de %s',
                'View Campus' => 'Ver Campus',
                'Ages %s' => 'Edades %s',
                'View Program' => 'Ver Programa',
                
                // Header & Footer
                'Early Learning' => 'Aprendizaje Temprano',
                'Academy' => 'Academia',
                'Book a Tour' => 'Reservar Recorrido',
                'Menu' => 'Menú',
                'Toggle menu' => 'Alternar menú',
                'Close menu' => 'Cerrar menú',
                'Ready to experience the Chroma difference?' => '¿Listo para experimentar la diferencia de Chroma?',
                'Schedule a Tour' => 'Programar un Recorrido',
                'Ready to enroll in <strong>%s</strong>?' => '¿Listo para inscribirse en <strong>%s</strong>?',
                'Ready to visit our <strong>%s</strong> campus?' => '¿Listo para visitar nuestro campus de <strong>%s</strong>?',
                'Skip to content' => 'Saltar al contenido',
                'Search' => 'Buscar',
                'Search entire site...' => 'Buscar en todo el sitio...',
                
                // Location Archive & Page
                'Campuses' => 'Campuses',
                'Find your Chroma Community - Our Locations' => 'Encuentra tu Comunidad Chroma - Nuestras Ubicaciones',
                'Find your Chroma <span class="text-chroma-green italic">community.</span>' => 'Encuentra tu <span class="text-chroma-green italic">comunidad</span> Chroma.',
                'Search by ZIP code or city name...' => 'Buscar por código postal o nombre de la ciudad...',
                'Search by city, zip, or campus name...' => 'Buscar por ciudad, código postal o nombre del campus...',
                'All Locations' => 'Todas las Ubicaciones',
                'Now Enrolling' => 'Inscripciones Abiertas',
                'New Campus' => 'Nuevo Campus',
                'Metro Atlanta' => 'Metro Atlanta',
                'Open Now' => 'Abierto Ahora',
                'Book Tour' => 'Reservar Recorrido',
                'Contact Us' => 'Contáctenos',
                'View Campus' => 'Ver Campus',
                '%s+ Locations in Metro Atlanta' => '%s+ Ubicaciones en Metro Atlanta',
                'Not sure which campus is right for you?' => '¿No estás seguro de qué campus es el adecuado para ti?',
                "Our enrollment specialists can help you find the nearest location with openings for your child's age group." => 'Nuestros especialistas en inscripciones pueden ayudarle a encontrar la ubicación más cercana con vacantes para el grupo de edad de su hijo.',
                'Contact Support' => 'Contactar a Soporte',
                'Back to Home' => 'Volver al Inicio',
                'Schedule Your Visit' => 'Programe su Visita',
                'Open in new tab' => 'Abrir en una nueva pestaña',
                'No locations found' => 'No se encontraron ubicaciones',
                'Try adjusting your search terms or selecting "%s".' => 'Intente ajustar sus términos de búsqueda o seleccione "%s".',
                'View' => 'Ver',

                // Careers
                'Apply Now' => 'Aplicar Ahora',
                'No current openings. Please check back later.' => 'No hay vacantes actuales. Por favor, vuelva a consultar más tarde.',
                'Apply for Position' => 'Aplicar para el Puesto',
                'Join Our Team' => 'Únete a Nuestro Equipo',
                'View Current Openings' => 'Ver Vacantes Actuales',
                'Competitive Pay & 401k' => 'Pago Competitivo y 401k',
                'Paid Tuition & CDA' => 'Matrícula Pagada y CDA',
                'Health & Wellness' => 'Salud y Bienestar',
                'Current Opportunities' => 'Oportunidades Actuales',
                "Don't see your role?" => '¿No ves tu puesto?',

                // Curriculum / Prismpath
                'The Chroma Difference' => 'La Diferencia Chroma',
                'Scientific rigor. <br><span class="italic text-chroma-green">Joyful delivery.</span>' => 'Rigor científico. <br><span class="italic text-chroma-green">Entrega alegre.</span>',
                'The Prismpath™ Framework' => 'El Marco Prismpath™',
                'Physical' => 'Físico',
                'Emotional' => 'Emocional',
                'Social' => 'Social',
                'Academic' => 'Académico',
                'Creative' => 'Creativo',
                'Learning Journey' => 'Viaje de Aprendizaje',
                'How learning evolves.' => 'Cómo evoluciona el aprendizaje.',
                'Environment' => 'Ambiente',
                'The classroom is the "Third Teacher."' => 'El aula es el "Tercer Maestro."',
                'Construction Zone' => 'Zona de Construcción',
                'Atelier (Art Studio)' => 'Atelier (Estudio de Arte)',
                'Literacy Nook' => 'Rincón de Alfabetización',
                'Measuring Milestones' => 'Midiendo Hitos',
                'Daily Progress Tracking' => 'Seguimiento del Progreso Diario',
                'Developmental Screenings' => 'Evaluaciones del Desarrollo',
                'Formal Assessments' => 'Evaluaciones Formales',
                'See the curriculum in action.' => 'Vea el currículo en acción.',
                'Find a Location' => 'Encontrar una Ubicación',
                'Schedule a Tour' => 'Programar un Recorrido',

                // Single Location
                'Designed for discovery.' => 'Diseñado para el descubrimiento.',
                'Secure Access' => 'Acceso Seguro',
                'Nature Playground' => 'Patio de Juegos Natural',
                'STEM Atelier' => 'Atelier de STEM',
                'GA Lottery Pre-K' => 'Pre-K de la Lotería de GA',
                'Meet the Director' => 'Conozca a la Directora',
                'Take a Virtual Tour' => 'Realice un Recorrido Virtual',
                'Explore Our Campus' => 'Explore Nuestro Campus',
                'Programs at this location' => 'Programas en esta ubicación',
                'Curriculum tailored to the specific developmental window of your child.' => 'Currículo adaptado a la ventana de desarrollo específica de su hijo.',
                'View Curriculum Details' => 'Ver Detalles del Currículo',
                'Family Stories' => 'Historias de Familias',
                'Why Families Love Us' => 'Por Qué las Familias Nos Aman',
                'Happy Parent' => 'Padre Feliz',
                'Frequently Asked Questions' => 'Preguntas Frecuentes',
                'Do you offer tours?' => '¿Ofrecen recorridos?',
                'Yes! We encourage all families to book a tour to see our classrooms, meet our directors, and experience the Chroma difference firsthand.' => '¡Sí! Animamos a todas las familias a reservar un recorrido para ver nuestras aulas, conocer a nuestros directores y experimentar la diferencia de Chroma de primera mano.',
                'What ages do you serve?' => '¿A qué edades atienden?',
                'We typically serve children from 6 weeks (Infants) up to 12 years old (After School), though specific programs may vary by campus.' => 'Normalmente atendemos a niños desde las 6 semanas (infantes) hasta los 12 años (después de la escuela), aunque los programas específicos pueden variar según el campus.',
                'Is food included?' => '¿Está incluida la comida?',
                'Yes, we provide nutritious, child-friendly meals and snacks prepared fresh daily.' => 'Sí, proporcionamos comidas y meriendas nutritivas y adecuadas para niños, preparadas frescas a diario.',
                'Visit Us' => 'Visítenos',
                'Come see the magic in person.' => 'Venga a ver la magia en persona.',
                'Tours are the best way to feel the Chroma difference.' => 'Los recorridos son la mejor manera de sentir la diferencia de Chroma.',
                'We are available for tours Monday through Friday' => 'Estamos disponibles para recorridos de lunes a viernes',
                ' between %s and %s' => ' entre las %s y las %s',
                'We welcome little ones to accompany on a tour!' => '¡Damos la bienvenida a los más pequeños para que nos acompañen en el recorrido!',
                'Address' => 'Dirección',
                'Get Directions' => 'Obtener Direcciones',
                'Contact' => 'Contacto',
                'Phone:' => 'Teléfono:',
                'Email:' => 'Correo electrónico:',
                'Hours of Operation' => 'Horario de Operación',
                'Monday - Friday:' => 'Lunes - Viernes:',
                'Weekends: Closed' => 'Fines de Semana: Cerrado',
                'School Pickups' => 'Recogidas Escolares',
                'We provide pickup service to:' => 'Ofrecemos servicio de recogida en:',
                'Request a Tour' => 'Solicitar un Recorrido',
                "Fill out the form below and we'll contact you to confirm a time." => 'Complete el siguiente formulario y nos pondremos en contacto con usted para confirmar una hora.',
                'or' => 'o',
                'Book a Tour Now' => 'Reserve un Recorrido Ahora',
                'Schedule Visit' => 'Programar Visita',
                'Ages Served' => 'Edades Atendidas',
                'Google Rating' => 'Calificación de Google',
                'Mon - Fri' => 'Lun - Vie',
                'Campus Features' => 'Características del Campus',
                'Every corner of our %s campus is intentional—from the soft lighting in our infant suites to the collaborative stations in our Pre-K classrooms.' => 'Cada rincón de nuestro campus de %s es intencional, desde la suave iluminación en nuestras suites para infantes hasta las estaciones de colaboración en nuestras aulas de Pre-K.',
                'Welcome to Chroma %s.' => 'Bienvenido a Chroma %s.',
                'Campus Director' => 'Directora del Campus',
                'Walk through our %s campus from the comfort of your home. Explore our classrooms, outdoor play areas, and learning spaces.' => 'Recorra nuestro campus de %s desde la comodidad de su hogar. Explore nuestras aulas, áreas de juego al aire libre y espacios de aprendizaje.',

                // Curriculum Details additions
                'Foundation (0-18 Months)' => 'Fundación (0-18 Meses)',
                'Discovery (18 Months - 3 Years)' => 'Descubrimiento (18 Meses - 3 Años)',
                'Readiness (3 Years - 5 Years)' => 'Preparación (3 Años - 5 Años)',

                // Homepage UI Strings
                '19+ Metro Atlanta Locations' => 'Más de 19 Ubicaciones en Metro Atlanta',
                '4.8 Average Parent Rating' => '4.8 de Calificación Promedio de Padres',
                'Licensed • Quality Rated • GA Pre-K Partner' => 'Licenciados • Calificación de Calidad • Socio de GA Pre-K',
                'Hero Image Coming Soon' => 'Imagen de Héroe Próximamente',
                'Kindergarten Ready' => 'Preparado para el Jardín de Infantes',
                'Comprehensive Prep' => 'Preparación Integral',
                'Find the right program in 10 seconds' => 'Encuentre el programa adecuado en 10 segundos',
                "Choose your child's age and we'll suggest the Chroma program designed for their development stage and your family's needs." => 'Elija la edad de su hijo y le sugeriremos el programa de Chroma diseñado para su etapa de desarrollo y las necesidades de su familia.',
                'Speak to an enrollment specialist' => 'Hable con un especialista en inscripciones',
                'Program Preview' => 'Vista Previa del Programa',
                'Start Over' => 'Empezar de Nuevo',
                'What Parents Say' => 'Lo que dicen los padres',
                'Trusted by thousands of Atlanta families' => 'Con la confianza de miles de familias de Atlanta',
                "Don't just take our word for it. Here's what parents have to say about their experience with Chroma Early Learning." => 'No se quede solo con nuestra palabra. Esto es lo que los padres tienen que decir sobre su experiencia con Chroma Early Learning.',
                'Go to review %d' => 'Ir a la reseña %d',
                'Schedule a private tour' => 'Programar un recorrido privado',
                'Why families choose Chroma' => 'Por qué las familias eligen Chroma',
                'Warm, consistent teachers' => 'Maestros cálidos y consistentes',
                'Daily parent communication' => 'Comunicación diaria con los padres',
                'Healthy meals included' => 'Comidas saludables incluidas',
                'Age-appropriate security' => 'Seguridad adecuada para la edad',
                'GA Lottery Pre-K available' => 'Pre-K de la Lotería de GA disponible',
                'Tour: 20–30 min' => 'Recorrido: 20–30 min',
                'Please activate the "Chroma Tour Form" plugin.' => 'Por favor active el plugin "Chroma Tour Form".',
                'The Chroma Standard' => 'El Estándar Chroma',
                'Designed for discovery.' => 'Diseñado para el descubrimiento.',
                'GA Lottery Pre-K' => 'Pre-K de la Lotería de GA',
                
                // Combo Pages (Dynamic City+Program pages)
                'Premier %s in %s, %s.' => '%s de Primera Categoría en %s, %s.',
                'Now Enrolling: %s' => 'Inscripciones Abiertas: %s',
                'Why %s Parents Choose Our %s' => 'Por Qué los Padres de %s Eligen Nuestro %s',
                'We understand that choosing care in %s is a big decision. Here is what sets our %s apart.' => 'Entendemos que elegir cuidado en %s es una gran decisión. Esto es lo que distingue nuestro %s.',
                'Low Ratios' => 'Bajas Proporciones',
                'Our %s campus maintains strict teacher-to-student ratios, ensuring your child gets the individual attention they need.' => 'Nuestro campus de %s mantiene estrictas proporciones de maestro a estudiante, asegurando que su hijo reciba la atención individual que necesita.',
                'Prismpath™ Curriculum' => 'Currículo Prismpath™',
                'Specifically designed for %s, our curriculum balances play-based learning with school readiness.' => 'Diseñado específicamente para %s, nuestro currículo equilibra el aprendizaje basado en el juego con la preparación escolar.',
                'Real-Time Updates' => 'Actualizaciones en Tiempo Real',
                'Parents in %s love our app. Get photos and updates throughout the workday straight to your phone.' => 'Los padres en %s aman nuestra aplicación. Reciba fotos y actualizaciones durante la jornada laboral directamente en su teléfono.',
                'Serving Families in %s' => 'Sirviendo a Familias en %s',
                'Located conveniently off %s, our' => 'Ubicado convenientemente cerca de %s, nuestro',
                'Our %s campus is the preferred choice for families living in' => 'Nuestro campus de %s es la opción preferida para familias que viven en',
                'Whether you work at %s or commute via %s, our drop-off and pick-up hours (6:30 AM – 6:30 PM) are designed for working parents in %s County.' => 'Ya sea que trabaje en %s o viaje por %s, nuestros horarios de entrada y salida (6:30 AM – 6:30 PM) están diseñados para padres trabajadores en el Condado de %s.',
                'Our convenient hours (6:30 AM – 6:30 PM) are designed for working parents in %s County.' => 'Nuestros horarios convenientes (6:30 AM – 6:30 PM) están diseñados para padres trabajadores en el Condado de %s.',
                'Chroma Locations Serving %s' => 'Ubicaciones de Chroma que Sirven a %s',
                'Select the campus closest to your home or work.' => 'Seleccione el campus más cercano a su hogar o trabajo.',
                'Visit Our %s Classroom' => 'Visite Nuestra Aula de %s',
                'See the %s environment in person. Meet our Director and teachers.' => 'Vea el ambiente de %s en persona. Conozca a nuestra Directora y maestros.',
                'More Childcare Options in %s' => 'Más Opciones de Cuidado Infantil en %s',
                'Other locations in %s' => 'Otras ubicaciones en %s',
                
                // Homepage Hero
                'The art of <span class="italic text-chroma-red">growing up.</span>' => 'El arte de <span class="italic text-chroma-red">crecer.</span>',
                'Where accredited excellence meets the warmth of home. A modern sanctuary powered by our proprietary Prismpath™ learning model for children 6 weeks to 12 years.' => 'Donde la excelencia acreditada se encuentra con la calidez del hogar. Un santuario moderno impulsado por nuestro modelo de aprendizaje patentado Prismpath™ para niños de 6 semanas a 12 años.',
                'View Programs' => 'Ver Programas',
                '19+ Metro Atlanta Locations' => 'Más de 19 Ubicaciones en Metro Atlanta',
                
                // Stats Strip
                'Metro campuses' => 'Campuses metro',
                'Children enrolled' => 'Niños inscritos',
                'Avg parent rating' => 'Calificación promedio de padres',
                'Age range' => 'Rango de edad',
                
                // Prismpath Panels
                'Grounded in Expertise. Wrapped in Love.' => 'Basado en la Experiencia. Envuelto en Amor.',
                'Meet the Team' => 'Conozca al Equipo',
                'Expert Care, Extended Family.' => 'Cuidado Experto, Familia Extendida.',
                'Our educators are state-certified professionals who understand that the most important credential is kindness.' => 'Nuestros educadores son profesionales certificados por el estado que entienden que la credencial más importante es la amabilidad.',
                'Wholesome Fuel' => 'Combustible Saludable',
                'Organic, balanced meals served family-style to fuel growing minds.' => 'Comidas orgánicas y equilibradas servidas al estilo familiar para alimentar mentes en crecimiento.',
                'Uncompromised Safety' => 'Seguridad sin Concesiones',
                'Secure, monitored facilities with open-door transparency for parents.' => 'Instalaciones seguras y monitoreadas con transparencia de puertas abiertas para los padres.',
                'Kindergarten Readiness' => 'Preparación para el Jardín de Infantes',
                'Our graduates enter school confident, socially capable, and academically prepared.' => 'Nuestros graduados ingresan a la escuela confiados, socialmente capaces y académicamente preparados.',
                
                // Program Wizard Defaults
                "Infant\n(6 weeks–12m)" => "Bebés\n(6 semanas–12m)",
                "Toddler\n(1 year)" => "Niños Pequeños\n(1 año)",
                "Preschool\n(2 years)" => "Preescolar\n(2 años)",
                "Pre-K Prep\n(3 years)" => "Preparación Pre-K\n(3 años)",
                "GA Pre-K\n(4 years)" => "GA Pre-K\n(4 años)",
                "After School\n(5–12 years)" => "Después de la Escuela\n(5–12 años)",
                
                // FAQ
                'Common questions from parents' => 'Preguntas comunes de los padres',
                'We’ve answered a few of the questions parents ask most when choosing childcare and early learning.' => 'Hemos respondido algunas de las preguntas que los padres hacen con más frecuencia al elegir guardería y aprendizaje temprano.',
                
                // Reviews Defaults
                'Marietta Campus' => 'Campus de Marietta',
                'Johns Creek Campus' => 'Campus de Johns Creek',
                'Austell Campus' => 'Campus de Austell',
                'Our daughter has flourished at Chroma. The teachers genuinely care, and the Prismpath curriculum has her excited to learn every day. We couldn\'t ask for a better early learning experience.' => 'Nuestra hija ha florecido en Chroma. Los maestros realmente se preocupan, y el currículo Prismpath la tiene emocionada por aprender todos los días. No podríamos pedir una mejor experiencia de aprendizaje temprano.',
                'After touring several centers, Chroma stood out immediately. The transparency, the warmth, and the expert care made our decision easy. Our son has been there for two years and we\'ve never looked back.' => 'Después de recorrer varios centros, Chroma se destacó de inmediato. La transparencia, la calidez y el cuidado experto facilitaron nuestra decisión. Nuestro hijo ha estado allí durante dos años y nunca hemos mirado atrás.',
                'The family-style meals, the daily communication, the beautiful facilities — everything exceeds expectations. Chroma feels like an extension of our family, and our twins are thriving.' => 'Las comidas al estilo familiar, la comunicación diaria, las hermosas instalaciones; todo supera las expectativas. Chroma se siente como una extensión de nuestra familia, y nuestros gemelos están prosperando.',
                
                // Featured Stories
                'Inside the Prismpath™ Classroom' => 'Dentro del Aula Prismpath™',
                'Take a peek at how our educators weave play and academics together each day.' => 'Eche un vistazo a cómo nuestros educadores entrelazan el juego y lo académico cada día.',
                'Family-Style Dining at Chroma' => 'Cenas al Estilo Familiar en Chroma',
                'Why shared meals matter for social-emotional growth and independence.' => 'Por qué las comidas compartidas son importantes para el crecimiento socioemocional y la independencia.',
                'Partnering with Parents' => 'Asociación con los Padres',
                'See how we communicate daily to keep families connected to the classroom.' => 'Vea cómo nos comunicamos diariamente para mantener a las familias conectadas con el aula.',
                
                // Regions
                'North Atlanta' => 'Norte de Atlanta',
                'South Atlanta' => 'Sur de Atlanta',
                'East Atlanta' => 'Este de Atlanta',
                'West Atlanta' => 'Oeste de Atlanta',
                
                // Curriculum Labels (Radar Chart)
                'Physical' => 'Físico',
                'Emotional' => 'Emocional',
                'Social' => 'Social',
                'Academic' => 'Académico',
                'Creative' => 'Creativo',
                'Prismpath™ Focus' => 'Enfoque Prismpath™',
                
                // Footer & Sticky CTA
                'Premium childcare & early education across Metro Atlanta.' => 'Cuidado infantil de primera y educación temprana en todo Metro Atlanta.',
                'Quick Links' => 'Enlaces Rápidos',
                'Connect With Us' => 'Conéctate con Nosotros',
                'Latest Blogs' => 'Últimos Blogs',
                'Chroma Early Learning Academy. All rights reserved.' => 'Chroma Early Learning Academy. Todos los derechos reservados.',
                'Privacy Policy' => 'Política de Privacidad',
                'Terms of Service' => 'Términos de Servicio',
                'Ready to experience the Chroma difference?' => '¿Listo para experimentar la diferencia de Chroma?',
                'Ready to enroll in <strong>%s</strong>?' => '¿Listo para inscribirse en <strong>%s</strong>?',
                'Ready to visit our <strong>%s</strong> campus?' => '¿Listo para visitar nuestro campus de <strong>%s</strong>?',
                
                // Program Wizard & Enhancements
                'Find the right program in 10 seconds' => 'Encuentre el programa adecuado en 10 segundos',
                'Choose your child\'s age and we\'ll suggest the Chroma program designed for their development stage and your family\'s needs.' => 'Elija la edad de su hijo y le sugeriremos el programa Chroma diseñado para su etapa de desarrollo y las necesidades de su familia.',
                'Speak to an enrollment specialist' => 'Hable con un especialista en inscripciones',
                'Start Over' => 'Empezar de nuevo',
                'Program Preview' => 'Vista previa del programa',
                'View Lesson Plan' => 'Ver plan de lecciones',
                '%s Lesson Plan' => 'Plan de lecciones de %s',
                'Loading lesson plan...' => 'Cargando plan de lecciones...',
                'Open in new tab' => 'Abrir en pestaña nueva',
                'Download' => 'Descargar',
                'View Curriculum' => 'Ver currículo',
                
                // Common Program Enhancements
                'Age Calculator' => 'Calculadora de edad',
                'Frequently Asked Questions' => 'Preguntas frecuentes',
                'Photo Gallery' => 'Galería de fotos',
                'Parent Testimonials' => 'Testimonios de padres',
                
                // Curriculum Page
                'See the curriculum in action.' => 'Vea el currículo en acción.',
                'Schedule a tour to see our "Third Teacher" classrooms and meet the educators bringing Prismpath™ to life.' => 'Programe un recorrido para ver nuestras aulas del "Tercer Maestro" y conocer a los educadores que dan vida a Prismpath™.',
                'Find a Location' => 'Encuentra una ubicación',
                'The Chroma Difference' => 'La Diferencia Chroma',
                'Scientific rigor. <br><span class="italic text-chroma-green">Joyful delivery.</span>' => 'Rigor científico. <br><span class="italic text-chroma-green">Entrega alegre.</span>',
                'Our proprietary Prismpath™ curriculum isn\'t just about ABCs. It\'s a comprehensive framework designed to build the critical thinking, emotional intelligence, and social skills needed for the 21st century.' => 'Nuestro currículo patentado Prismpath™ no se trata solo del ABC. Es un marco integral diseñado para desarrollar el pensamiento crítico, la inteligencia emocional y las habilidades sociales necesarias para el siglo XXI.',
                'The Prismpath™ Framework' => 'El Marco Prismpath™',
                'How learning evolves.' => 'Cómo evoluciona el aprendizaje.',
                'Environment' => 'Ambiente',
                'The classroom is the "Third Teacher."' => 'El aula es el "Tercer Maestro."',
                'Measuring Milestones' => 'Midiendo Hitos',
                
                // Curriculum Section
                'The Prismpath™ Curriculum' => 'El Currículo Prismpath™',
                'A curriculum that shifts as your child grows' => 'Un currículo que cambia a medida que su hijo crece',
                'Our Prismpath™ framework balances five pillars – physical, emotional, social, academic, and creative development. The mix changes at each age so your child gets exactly what they need, when they need it.' => 'Nuestro marco Prismpath™ equilibra cinco pilares: desarrollo físico, emocional, social, académico y creativo. La mezcla cambia en cada edad para que su hijo reciba exactamente lo que necesita, cuando lo necesita.',
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


