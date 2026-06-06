<?php
/**
 * Shared SEO profile helpers for sitemap, singular, and virtual routes.
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('chroma_seo_normalize_language')) {
    function chroma_seo_normalize_language($language = '')
    {
        return strtolower((string) $language) === 'es' ? 'es' : 'en';
    }
}

if (!function_exists('chroma_seo_is_spanish_request')) {
    function chroma_seo_is_spanish_request()
    {
        if (function_exists('chroma_detect_current_language')) {
            return chroma_detect_current_language() === 'es';
        }

        if (class_exists('Chroma_Multilingual_Manager') && method_exists('Chroma_Multilingual_Manager', 'is_spanish')) {
            return (bool) Chroma_Multilingual_Manager::is_spanish();
        }

        $path = wp_parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
        return is_string($path) && strpos($path, '/es/') === 0;
    }
}

if (!function_exists('chroma_seo_get_request_language')) {
    function chroma_seo_get_request_language($language = '')
    {
        if ($language !== '') {
            return chroma_seo_normalize_language($language);
        }

        return chroma_seo_is_spanish_request() ? 'es' : 'en';
    }
}

if (!function_exists('chroma_seo_get_request_path')) {
    function chroma_seo_get_request_path($path = '')
    {
        if ($path === '') {
            $path = (string) wp_parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
        }

        if ($path === '') {
            return '/';
        }

        return user_trailingslashit('/' . ltrim($path, '/'));
    }
}

if (!function_exists('chroma_seo_get_request_segments')) {
    function chroma_seo_get_request_segments($path = '')
    {
        $path = trim(chroma_seo_get_request_path($path), '/');
        if ($path === '') {
            return [];
        }

        return array_values(array_filter(explode('/', $path), 'strlen'));
    }
}

if (!function_exists('chroma_seo_get_route_segments')) {
    function chroma_seo_get_route_segments($path = '')
    {
        $segments = chroma_seo_get_request_segments($path);
        if (!empty($segments) && $segments[0] === 'es') {
            array_shift($segments);
        }

        return $segments;
    }
}

if (!function_exists('chroma_seo_build_url_from_path')) {
    function chroma_seo_build_url_from_path($path)
    {
        return home_url(user_trailingslashit('/' . ltrim((string) $path, '/')));
    }
}

if (!function_exists('chroma_seo_get_current_absolute_url')) {
    function chroma_seo_get_current_absolute_url()
    {
        return chroma_seo_build_url_from_path(chroma_seo_get_request_path());
    }
}

if (!function_exists('chroma_seo_clean_text')) {
    function chroma_seo_clean_text($text)
    {
        $text = html_entity_decode((string) $text, ENT_QUOTES, get_bloginfo('charset') ?: 'UTF-8');
        $text = wp_strip_all_tags($text);
        $text = preg_replace('/\s+/u', ' ', $text);
        return trim((string) $text);
    }
}

if (!function_exists('chroma_seo_trim_text')) {
    function chroma_seo_trim_text($text, $limit = 160)
    {
        $text = chroma_seo_clean_text($text);
        if ($text === '') {
            return '';
        }

        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($text) <= $limit) {
                return $text;
            }

            return rtrim(mb_substr($text, 0, max(0, $limit - 1))) . '…';
        }

        if (strlen($text) <= $limit) {
            return $text;
        }

        return rtrim(substr($text, 0, max(0, $limit - 1))) . '…';
    }
}

if (!function_exists('chroma_seo_trim_title')) {
    function chroma_seo_trim_title($title)
    {
        return chroma_seo_trim_text($title, 65);
    }
}

if (!function_exists('chroma_seo_trim_meta_description')) {
    function chroma_seo_trim_meta_description($description)
    {
        return chroma_seo_trim_text($description, 160);
    }
}

if (!function_exists('chroma_seo_get_static_route_defaults')) {
    function chroma_seo_get_static_route_defaults()
    {
        return [
            'home' => [
                'en' => [
                    'title' => 'Chroma Academy | Top Daycare & Preschool in Metro Atlanta',
                    'meta_description' => 'Discover Chroma Early Learning Academy for daycare, preschool, GA Pre-K, and family-focused early education across Metro Atlanta.',
                ],
                'es' => [
                    'title' => 'Chroma Academy | Guardería y preescolar en Metro Atlanta',
                    'meta_description' => 'Descubre Chroma Early Learning Academy para guardería, preescolar, GA Pre-K y educación temprana para familias en Metro Atlanta.',
                ],
            ],
            'schedule-a-tour' => [
                'en' => [
                    'title' => 'Schedule a Tour | Chroma Early Learning Academy',
                    'meta_description' => 'Schedule a tour at Chroma Early Learning Academy to explore our classrooms, curriculum, and enrollment options for your family.',
                ],
                'es' => [
                    'title' => 'Programa un recorrido | Chroma Early Learning Academy',
                    'meta_description' => 'Programa un recorrido en Chroma Early Learning Academy para conocer nuestros salones, currículo y opciones de inscripción para tu familia.',
                ],
            ],
            'terms-of-service' => [
                'en' => [
                    'title' => 'Terms of Service | Chroma Early Learning Academy',
                    'meta_description' => 'Review Chroma Early Learning Academy terms of service, including enrollment, billing, attendance, and family policy information.',
                ],
                'es' => [
                    'title' => 'Términos de servicio | Chroma Early Learning Academy',
                    'meta_description' => 'Consulta los términos de servicio de Chroma Early Learning Academy, incluyendo inscripción, facturación, asistencia y políticas familiares.',
                ],
            ],
            'privacy-policy' => [
                'en' => [
                    'title' => 'Privacy Policy | Chroma Early Learning Academy',
                    'meta_description' => 'Read the Chroma Early Learning Academy privacy policy to understand how we collect, use, and protect family information.',
                ],
                'es' => [
                    'title' => 'Política de privacidad | Chroma Early Learning Academy',
                    'meta_description' => 'Lee la política de privacidad de Chroma Early Learning Academy para entender cómo recopilamos, usamos y protegemos la información familiar.',
                ],
            ],
            'chroma-early-learning' => [
                'en' => [
                    'title' => 'Chroma Early Learning | Early Childhood Education',
                    'meta_description' => 'Explore Chroma Early Learning and learn how our early childhood programs help children and families thrive.',
                ],
                'es' => [
                    'title' => 'Chroma Early Learning | Educación infantil',
                    'meta_description' => 'Explora Chroma Early Learning y conoce cómo nuestros programas de educación infantil ayudan a niños y familias a prosperar.',
                ],
            ],
            'chroma-early-start' => [
                'en' => [
                    'title' => 'Chroma Early Learning | Early Childhood Education',
                    'meta_description' => 'Explore Chroma Early Learning and learn how our early childhood programs help children and families thrive.',
                ],
                'es' => [
                    'title' => 'Chroma Early Learning | Educación infantil',
                    'meta_description' => 'Explora Chroma Early Learning y conoce cómo nuestros programas de educación infantil ayudan a niños y familias a prosperar.',
                ],
            ],
            'parents' => [
                'en' => [
                    'title' => 'Parents | Chroma Early Learning Academy',
                    'meta_description' => 'Find parent resources, enrollment guidance, tuition details, and family support information from Chroma Early Learning Academy.',
                ],
                'es' => [
                    'title' => 'Padres | Chroma Early Learning Academy',
                    'meta_description' => 'Encuentra recursos para padres, información de inscripción, detalles de matrícula y apoyo familiar de Chroma Early Learning Academy.',
                ],
            ],
            'programs' => [
                'en' => [
                    'title' => 'Programs | Chroma Early Learning Academy',
                    'meta_description' => 'Explore Chroma early learning programs for every age, from infant care and toddlers to preschool, GA Pre-K, Kindergarten, and school-age care.',
                ],
                'es' => [
                    'title' => 'Programas | Chroma Early Learning Academy',
                    'meta_description' => 'Explora los programas de aprendizaje temprano de Chroma para cada edad, desde bebes y ninos pequenos hasta preescolar, GA Pre-K, Kindergarten y cuidado para escolares.',
                ],
            ],
            'contact-us' => [
                'en' => [
                    'title' => 'Contact Us | Chroma Early Learning Academy',
                    'meta_description' => 'Contact Chroma Early Learning Academy to ask about enrollment, locations, tuition, or scheduling a tour.',
                ],
                'es' => [
                    'title' => 'Contáctanos | Chroma Early Learning Academy',
                    'meta_description' => 'Contacta a Chroma Early Learning Academy para preguntas sobre inscripción, ubicaciones, matrícula o recorridos.',
                ],
            ],
            'stories' => [
                'en' => [
                    'title' => 'Stories & Parenting Tips | Chroma Early Learning Academy',
                    'meta_description' => 'Read parenting tips, child development guidance, and early learning stories from the Chroma Early Learning Academy team.',
                ],
                'es' => [
                    'title' => 'Historias y consejos para familias | Chroma Early Learning Academy',
                    'meta_description' => 'Lee consejos para familias, guía sobre desarrollo infantil e historias de aprendizaje temprano del equipo de Chroma Early Learning Academy.',
                ],
            ],
            'curriculum' => [
                'en' => [
                    'title' => 'PrismPath Curriculum | Chroma Early Learning Academy',
                    'meta_description' => 'Explore the PrismPath curriculum at Chroma Early Learning Academy and see how we support every stage of early development.',
                ],
                'es' => [
                    'title' => 'Currículo PrismPath | Chroma Early Learning Academy',
                    'meta_description' => 'Explora el currículo PrismPath de Chroma Early Learning Academy y descubre cómo apoyamos cada etapa del desarrollo temprano.',
                ],
            ],
            'summer-camp-discover-go' => [
                'en' => [
                    'title' => 'Summer Camp Discover, Go! | Chroma Early Learning Academy',
                    'meta_description' => 'Learn about Chroma summer camp programs with seasonal themes, hands-on activities, and fun experiences for school-age children.',
                ],
                'es' => [
                    'title' => 'Campamento de verano Discover, Go! | Chroma Early Learning Academy',
                    'meta_description' => 'Conoce los campamentos de verano de Chroma con temas de temporada, actividades prácticas y experiencias divertidas para niños mayores.',
                ],
            ],
            'employers' => [
                'en' => [
                    'title' => 'Employer Childcare Solutions | Chroma Early Learning Academy',
                    'meta_description' => 'Explore employer childcare solutions from Chroma, including family support programs and early education access for working parents.',
                ],
                'es' => [
                    'title' => 'Soluciones de cuidado infantil para empleadores | Chroma Early Learning Academy',
                    'meta_description' => 'Explora las soluciones de cuidado infantil para empleadores de Chroma, con apoyo familiar y acceso a educación temprana para padres trabajadores.',
                ],
            ],
            'careers' => [
                'en' => [
                    'title' => 'Careers | Chroma Early Learning Academy',
                    'meta_description' => 'Explore career opportunities at Chroma Early Learning Academy and join our team of educators, leaders, and support staff.',
                ],
                'es' => [
                    'title' => 'Carreras | Chroma Early Learning Academy',
                    'meta_description' => 'Explora oportunidades profesionales en Chroma Early Learning Academy y únete a nuestro equipo de educadores, líderes y personal de apoyo.',
                ],
            ],
            'blog' => [
                'en' => [
                    'title' => 'Blog | Chroma Early Learning Academy',
                    'meta_description' => 'Browse blog articles from Chroma Early Learning Academy covering parenting, child development, and early education topics.',
                ],
                'es' => [
                    'title' => 'Blog | Chroma Early Learning Academy',
                    'meta_description' => 'Explora artículos del blog de Chroma Early Learning Academy sobre crianza, desarrollo infantil y educación temprana.',
                ],
            ],
            'newsroom' => [
                'en' => [
                    'title' => 'Newsroom | Chroma Early Learning Academy',
                    'meta_description' => 'Read the latest Chroma Early Learning Academy news, announcements, campus updates, and company stories.',
                ],
                'es' => [
                    'title' => 'Sala de prensa | Chroma Early Learning Academy',
                    'meta_description' => 'Lee las últimas noticias, anuncios, novedades de campus e historias de Chroma Early Learning Academy.',
                ],
            ],
            'parent-portal' => [
                'en' => [
                    'title' => 'Parent Portal | Chroma',
                    'meta_description' => 'Secure family portal for tuition, daily reports, classroom updates, and school resources.',
                ],
                'es' => [
                    'title' => 'Portal para familias | Chroma',
                    'meta_description' => 'Portal seguro para familias con matrícula, reportes diarios, novedades del aula y recursos escolares.',
                ],
            ],
        ];
    }
}

if (!function_exists('chroma_seo_get_static_route_key')) {
    function chroma_seo_get_static_route_key($path = '')
    {
        $segments = chroma_seo_get_route_segments($path);
        if (empty($segments)) {
            return 'home';
        }

        if (count($segments) === 1) {
            $slug = $segments[0];
            $defaults = chroma_seo_get_static_route_defaults();
            if (isset($defaults[$slug])) {
                return $slug;
            }
        }

        return '';
    }
}

if (!function_exists('chroma_seo_get_static_profile')) {
    function chroma_seo_get_static_profile($route_key, $language = '')
    {
        $language = chroma_seo_get_request_language($language);
        $defaults = chroma_seo_get_static_route_defaults();
        if (!isset($defaults[$route_key][$language])) {
            return [];
        }

        return [
            'title' => chroma_seo_trim_title($defaults[$route_key][$language]['title']),
            'meta_description' => chroma_seo_trim_meta_description($defaults[$route_key][$language]['meta_description']),
        ];
    }
}

if (!function_exists('chroma_seo_get_archive_profile')) {
    function chroma_seo_get_archive_profile($post_type, $language = '')
    {
        $language = chroma_seo_get_request_language($language);

        $defaults = [
            'location' => [
                'en' => [
                    'title' => 'Locations | Chroma Early Learning Academy',
                    'meta_description' => 'Explore Chroma locations across Metro Atlanta and find the campus, programs, and tour options that fit your family.',
                ],
                'es' => [
                    'title' => 'Ubicaciones | Chroma Early Learning Academy',
                    'meta_description' => 'Explora las ubicaciones de Chroma en Metro Atlanta y encuentra el campus, los programas y los recorridos ideales para tu familia.',
                ],
            ],
            'program' => [
                'en' => [
                    'title' => 'Programs | Chroma Early Learning Academy',
                    'meta_description' => 'Explore Chroma programs for infants through school-age children, including preschool, GA Pre-K, after-school, and seasonal camps.',
                ],
                'es' => [
                    'title' => 'Programas | Chroma Early Learning Academy',
                    'meta_description' => 'Explora los programas de Chroma para bebés, niños pequeños y escolares, incluyendo preescolar, GA Pre-K, after-school y campamentos.',
                ],
            ],
            'city' => [
                'en' => [
                    'title' => 'Communities | Chroma Early Learning Academy',
                    'meta_description' => 'Explore Chroma communities across Georgia and find nearby campuses, programs, and tour information for your family.',
                ],
                'es' => [
                    'title' => 'Comunidades | Chroma Early Learning Academy',
                    'meta_description' => 'Explora las comunidades de Chroma en Georgia y encuentra campus cercanos, programas e información para recorridos.',
                ],
            ],
        ];

        if (!isset($defaults[$post_type][$language])) {
            return [];
        }

        return [
            'title' => chroma_seo_trim_title($defaults[$post_type][$language]['title']),
            'meta_description' => chroma_seo_trim_meta_description($defaults[$post_type][$language]['meta_description']),
        ];
    }
}

if (!function_exists('chroma_seo_get_program_label')) {
    function chroma_seo_get_program_label($program, $language = '')
    {
        $language = chroma_seo_get_request_language($language);
        if (!$program instanceof WP_Post) {
            return '';
        }

        if ($language === 'es') {
            $translated_title = trim((string) get_post_meta($program->ID, '_chroma_es_title', true));
            if ($translated_title !== '') {
                return $translated_title;
            }
        }

        $labels = [
            'infant-care' => ['en' => 'Infant Care', 'es' => 'Cuidado para bebés'],
            'toddler-care' => ['en' => 'Toddler Care', 'es' => 'Cuidado para niños pequeños'],
            'preschool' => ['en' => 'Preschool', 'es' => 'Preescolar'],
            'pre-k-prep' => ['en' => 'Pre-K Prep', 'es' => 'Preparación para Pre-K'],
            'ga-pre-k' => ['en' => 'GA Pre-K', 'es' => 'GA Pre-K'],
            'after-school' => ['en' => 'After School', 'es' => 'After-School'],
            'camp-summer-winter-fall' => ['en' => 'Seasonal Camps', 'es' => 'Campamentos estacionales'],
            'parents-day-out' => ['en' => "Parent's Day Out", 'es' => 'Día libre para padres'],
            'kindergarten' => ['en' => 'Kindergarten', 'es' => 'Kindergarten'],
            'kindergarten-1' => ['en' => 'Kindergarten', 'es' => 'Kindergarten'],
            'rising-pre-k' => ['en' => 'Rising Pre-K', 'es' => 'Pre-K en ascenso'],
            'rising-kindergarten' => ['en' => 'Rising Kindergarten', 'es' => 'Kindergarten en ascenso'],
        ];

        $slug = $program->post_name;
        if (isset($labels[$slug][$language])) {
            return $labels[$slug][$language];
        }

        return get_the_title($program);
    }
}

if (!function_exists('chroma_seo_get_virtual_city_records')) {
    function chroma_seo_get_virtual_city_records()
    {
        static $records = null;

        if ($records !== null) {
            return $records;
        }

        $records = [];

        $add_record = static function ($record) use (&$records) {
            $state = strtoupper((string) ($record['state'] ?? 'GA'));
            $city_name = trim((string) ($record['city_name'] ?? ''));
            if ($city_name === '') {
                return;
            }

            $canonical_slug = sanitize_title((string) ($record['canonical_slug'] ?? $city_name));
            if ($canonical_slug === '') {
                return;
            }

            $key = $state . '|' . $canonical_slug;
            if (!isset($records[$key])) {
                $records[$key] = [
                    'city_name' => $city_name,
                    'city_name_es' => trim((string) ($record['city_name_es'] ?? '')),
                    'canonical_slug' => $canonical_slug,
                    'state' => $state,
                    'city_page_id' => (int) ($record['city_page_id'] ?? 0),
                    'location_id' => (int) ($record['location_id'] ?? 0),
                    'aliases' => [],
                ];
            }

            if (!empty($record['city_page_id']) && empty($records[$key]['city_page_id'])) {
                $records[$key]['city_page_id'] = (int) $record['city_page_id'];
            }

            if (!empty($record['location_id']) && empty($records[$key]['location_id'])) {
                $records[$key]['location_id'] = (int) $record['location_id'];
            }

            $candidate_es = trim((string) ($record['city_name_es'] ?? ''));
            if ($candidate_es !== '' && $records[$key]['city_name_es'] === '') {
                $records[$key]['city_name_es'] = $candidate_es;
            }

            $aliases = array_filter((array) ($record['aliases'] ?? []));
            $aliases[] = $canonical_slug;
            $records[$key]['aliases'] = array_values(array_unique(array_map('sanitize_title', array_filter($aliases))));
        };

        $city_posts = get_posts([
            'post_type' => 'city',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ]);

        foreach ($city_posts as $city_post) {
            $city_name = (string) get_post_meta($city_post->ID, 'city_name', true);
            if ($city_name === '') {
                $city_name = $city_post->post_title;
            }

            $state = (string) get_post_meta($city_post->ID, 'city_state', true);
            if ($state === '') {
                $state = 'GA';
            }

            $city_name_es = (string) get_post_meta($city_post->ID, '_chroma_es_title', true);

            $add_record([
                'city_name' => $city_name,
                'city_name_es' => $city_name_es,
                'canonical_slug' => sanitize_title($city_name),
                'state' => $state,
                'city_page_id' => $city_post->ID,
                'aliases' => [
                    sanitize_title($city_post->post_name),
                    sanitize_title($city_name),
                    sanitize_title($city_name_es),
                ],
            ]);
        }

        $manual_cities = get_option('chroma_seo_manual_cities', []);
        foreach ((array) $manual_cities as $manual_city) {
            $city_name = trim((string) ($manual_city['city'] ?? ''));
            $state = trim((string) ($manual_city['state'] ?? 'GA'));
            if ($city_name === '') {
                continue;
            }

            $add_record([
                'city_name' => $city_name,
                'canonical_slug' => sanitize_title($city_name),
                'state' => $state,
                'aliases' => [sanitize_title($city_name)],
            ]);
        }

        $locations = get_posts([
            'post_type' => 'location',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ]);

        foreach ($locations as $location) {
            $city_name = trim((string) get_post_meta($location->ID, 'location_city', true));
            $state = trim((string) get_post_meta($location->ID, 'location_state', true));
            if ($city_name === '' || $state === '') {
                continue;
            }

            $add_record([
                'city_name' => $city_name,
                'canonical_slug' => sanitize_title($city_name),
                'state' => $state,
                'location_id' => $location->ID,
                'aliases' => [sanitize_title($city_name)],
            ]);
        }

        $records = array_values($records);
        return $records;
    }
}

if (!function_exists('chroma_seo_resolve_virtual_city_context')) {
    function chroma_seo_resolve_virtual_city_context($city_slug, $state = '')
    {
        $city_slug = sanitize_title((string) $city_slug);
        $state = strtoupper((string) $state);

        if ($city_slug === '') {
            return null;
        }

        foreach (chroma_seo_get_virtual_city_records() as $record) {
            if ($state !== '' && strtoupper((string) $record['state']) !== $state) {
                continue;
            }

            if (in_array($city_slug, (array) $record['aliases'], true)) {
                return $record;
            }
        }

        return null;
    }
}

if (!function_exists('chroma_seo_get_city_display_name')) {
    function chroma_seo_get_city_display_name($city_context, $language = '')
    {
        $language = chroma_seo_get_request_language($language);
        if (!is_array($city_context)) {
            return '';
        }

        if ($language === 'es' && !empty($city_context['city_name_es'])) {
            return (string) $city_context['city_name_es'];
        }

        return (string) ($city_context['city_name'] ?? '');
    }
}

if (!function_exists('chroma_seo_get_post_translation_value')) {
    function chroma_seo_get_post_translation_value($post_id, array $meta_keys)
    {
        foreach ($meta_keys as $meta_key) {
            $value = trim((string) get_post_meta($post_id, $meta_key, true));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }
}

if (!function_exists('chroma_seo_build_singular_profile')) {
    function chroma_seo_build_singular_profile($post, $language = '')
    {
        $language = chroma_seo_get_request_language($language);
        if (!$post instanceof WP_Post) {
            return [];
        }

        if ($language !== 'es') {
            return [];
        }

        $post_id = $post->ID;
        $post_type = $post->post_type;

        $title = chroma_seo_get_post_translation_value($post_id, ['_chroma_es_seo_title']);
        $translated_title = chroma_seo_get_post_translation_value($post_id, ['_chroma_es_title']);
        $translated_excerpt = chroma_seo_get_post_translation_value($post_id, ['_chroma_es_meta_description', '_chroma_es_excerpt']);
        $translated_content = chroma_seo_get_post_translation_value($post_id, ['_chroma_es_content']);

        if ($title === '') {
            switch ($post_type) {
                case 'post':
                    $base_title = $translated_title !== '' ? $translated_title : get_the_title($post);
                    $title = 'Artículo de Chroma: ' . $base_title;
                    break;
                case 'program':
                    $base_title = $translated_title !== '' ? $translated_title : chroma_seo_get_program_label($post, 'es');
                    $title = $base_title . ' | Programa de Chroma';
                    break;
                case 'location':
                    $base_title = $translated_title !== '' ? $translated_title : get_the_title($post);
                    $title = $base_title . ' | Campus de Chroma';
                    break;
                case 'city':
                    $base_title = $translated_title !== '' ? $translated_title : ((string) get_post_meta($post_id, 'city_name', true) ?: get_the_title($post));
                    $title = 'Cuidado infantil en ' . $base_title . ' | Chroma';
                    break;
                case 'page':
                    $base_title = $translated_title !== '' ? $translated_title : get_the_title($post);
                    $title = $base_title . ' | Chroma Early Learning Academy';
                    break;
                default:
                    if ($translated_title !== '') {
                        $title = $translated_title . ' | Chroma';
                    }
                    break;
            }
        }

        $meta_description = $translated_excerpt;
        if ($meta_description === '' && $translated_content !== '') {
            $meta_description = wp_trim_words(chroma_seo_clean_text($translated_content), 28, '…');
        }

        if ($meta_description === '') {
            switch ($post_type) {
                case 'post':
                    $base_title = $translated_title !== '' ? $translated_title : get_the_title($post);
                    $meta_description = 'Lee este artículo de Chroma sobre ' . chroma_seo_clean_text($base_title) . ' y descubre consejos útiles para familias y aprendizaje temprano.';
                    break;
                case 'program':
                    $program_label = $translated_title !== '' ? $translated_title : chroma_seo_get_program_label($post, 'es');
                    $age_range = trim((string) get_post_meta($post_id, 'program_age_range', true));
                    $meta_description = 'Descubre ' . $program_label . ' en Chroma Early Learning Academy.';
                    if ($age_range !== '') {
                        $meta_description .= ' Ideal para edades ' . $age_range . '.';
                    }
                    $meta_description .= ' Conoce nuestro enfoque educativo y agenda una visita.';
                    break;
                case 'location':
                    $location_name = $translated_title !== '' ? $translated_title : get_the_title($post);
                    $city_name = chroma_seo_get_post_translation_value($post_id, ['_chroma_es_location_city']);
                    if ($city_name === '') {
                        $city_name = trim((string) get_post_meta($post_id, 'location_city', true));
                    }
                    $meta_description = 'Conoce ' . $location_name;
                    if ($city_name !== '') {
                        $meta_description .= ' en ' . $city_name;
                    }
                    $meta_description .= ' y descubre programas de cuidado infantil y preescolar de Chroma para tu familia.';
                    break;
                case 'city':
                    $city_name = $translated_title !== '' ? $translated_title : ((string) get_post_meta($post_id, 'city_name', true) ?: get_the_title($post));
                    $meta_description = 'Explora las opciones de cuidado infantil y preescolar de Chroma en ' . $city_name . ', con programas, campus cercanos y recorridos para familias.';
                    break;
                case 'page':
                    $page_title = $translated_title !== '' ? $translated_title : get_the_title($post);
                    $meta_description = 'Descubre ' . chroma_seo_clean_text($page_title) . ' en Chroma Early Learning Academy y encuentra informacion util para tu familia.';
                    break;
                default:
                    if ($translated_title !== '') {
                        $meta_description = 'Descubre ' . $translated_title . ' en Chroma Early Learning Academy.';
                    }
                    break;
            }
        }

        if ($post_type === 'program') {
            $program_overrides = [
                'rising-pre-k' => [
                    'title' => 'Pre-K en ascenso | Programa de Chroma',
                    'meta_description' => 'Prepara a tu hijo de 4 a 5 años para Pre-K con un programa alegre y práctico de Chroma Early Learning Academy. Conoce el programa y agenda una visita.',
                ],
                'rising-kindergarten' => [
                    'title' => 'Kindergarten en ascenso | Programa de Chroma',
                    'meta_description' => 'Ayuda a tu hijo a prepararse para kindergarten con aprendizaje basado en el juego, rutinas escolares y apoyo de maestros de Chroma Early Learning Academy.',
                ],
                'kindergarten-1' => [
                    'title' => 'Programa de kindergarten | Chroma Early Learning Academy',
                    'meta_description' => 'Descubre el programa de kindergarten de Chroma Early Learning Academy, diseñado para fortalecer la confianza, la curiosidad y la preparación para primer grado.',
                ],
            ];

            if (isset($program_overrides[$post->post_name])) {
                $title = $program_overrides[$post->post_name]['title'];
                $meta_description = $program_overrides[$post->post_name]['meta_description'];
            }
        }

        return [
            'title' => chroma_seo_trim_title($title),
            'meta_description' => chroma_seo_trim_meta_description($meta_description),
        ];
    }
}

if (!function_exists('chroma_seo_get_program_combo_slug')) {
    function chroma_seo_get_program_combo_slug($program)
    {
        if (!$program instanceof WP_Post) {
            return '';
        }

        $slug = sanitize_title((string) $program->post_name);
        if (
            $slug === 'kindergarten-1'
            && function_exists('chroma_get_kindergarten_program_alias_post')
        ) {
            $alias_program = chroma_get_kindergarten_program_alias_post();
            if ($alias_program instanceof WP_Post && (int) $alias_program->ID === (int) $program->ID) {
                return 'kindergarten';
            }
        }

        return $slug;
    }
}

if (!function_exists('chroma_seo_resolve_program_for_combo_slug')) {
    function chroma_seo_resolve_program_for_combo_slug($program_slug)
    {
        $program_slug = sanitize_title((string) $program_slug);
        if ($program_slug === '') {
            return null;
        }

        if (
            $program_slug === 'kindergarten'
            && function_exists('chroma_get_kindergarten_program_alias_post')
        ) {
            $alias_program = chroma_get_kindergarten_program_alias_post();
            if ($alias_program instanceof WP_Post) {
                return $alias_program;
            }
        }

        $program = get_page_by_path($program_slug, OBJECT, 'program');
        if ($program instanceof WP_Post) {
            return $program;
        }

        $matches = get_posts([
            'post_type' => 'program',
            'name' => $program_slug,
            'posts_per_page' => 1,
            'post_status' => 'publish',
        ]);

        return $matches[0] ?? null;
    }
}

if (!function_exists('chroma_seo_get_combo_storage_slug')) {
    function chroma_seo_get_combo_storage_slug($program_slug)
    {
        $program_slug = sanitize_title((string) $program_slug);
        if ($program_slug === '') {
            return '';
        }

        $program = function_exists('chroma_seo_resolve_program_for_combo_slug')
            ? chroma_seo_resolve_program_for_combo_slug($program_slug)
            : null;

        return $program instanceof WP_Post ? sanitize_title((string) $program->post_name) : $program_slug;
    }
}

if (!function_exists('chroma_seo_build_combo_profile')) {
    function chroma_seo_build_combo_profile($program, $city_context, $language = '')
    {
        $language = chroma_seo_get_request_language($language);
        if (!$program instanceof WP_Post || !is_array($city_context)) {
            return [];
        }

        $city_name = chroma_seo_get_city_display_name($city_context, $language);
        $state = strtoupper((string) ($city_context['state'] ?? 'GA'));
        $age_range = trim((string) get_post_meta($program->ID, 'program_age_range', true));
        $program_label = chroma_seo_get_program_label($program, $language);
        $program_slug = chroma_seo_get_program_combo_slug($program);
        $canonical_path = ($language === 'es' ? '/es/' : '/') . $program_slug . '-in-' . $city_context['canonical_slug'] . '-' . strtolower($state) . '/';

        $meta_map = [
            'infant-care' => [
                'en' => "Trusted Infant Care in {$city_name}, {$state}" . ($age_range !== '' ? " for babies {$age_range}." : '.') . ' Safe routines, caring teachers, and early learning at Chroma.',
                'es' => "Cuidado para bebés en {$city_name}, {$state}" . ($age_range !== '' ? " para edades {$age_range}." : '.') . ' Rutinas seguras, atención cariñosa y aprendizaje temprano en Chroma.',
            ],
            'toddler-care' => [
                'en' => "Toddler Care in {$city_name}, {$state}" . ($age_range !== '' ? " for ages {$age_range}." : '.') . ' Guided play, language-rich learning, and caring teachers at Chroma.',
                'es' => "Cuidado para niños pequeños en {$city_name}, {$state}" . ($age_range !== '' ? " para edades {$age_range}." : '.') . ' Juego guiado, lenguaje y atención cariñosa en Chroma.',
            ],
            'preschool' => [
                'en' => "Preschool in {$city_name}, {$state}" . ($age_range !== '' ? " for ages {$age_range}." : '.') . ' Hands-on learning, small classes, and trusted teachers at Chroma.',
                'es' => "Preescolar en {$city_name}, {$state}" . ($age_range !== '' ? " para edades {$age_range}." : '.') . ' Aprendizaje práctico, grupos pequeños y maestros de confianza en Chroma.',
            ],
            'pre-k-prep' => [
                'en' => "Pre-K Prep in {$city_name}, {$state}" . ($age_range !== '' ? " for ages {$age_range}." : '.') . ' School-readiness skills, structure, and play-based learning at Chroma.',
                'es' => "Preparación para Pre-K en {$city_name}, {$state}" . ($age_range !== '' ? " para edades {$age_range}." : '.') . ' Habilidades de preparación escolar, estructura y aprendizaje a través del juego en Chroma.',
            ],
            'ga-pre-k' => [
                'en' => "GA Pre-K in {$city_name}, {$state} for local families. Explore Chroma classrooms, readiness skills, and enrollment information today.",
                'es' => "GA Pre-K en {$city_name}, {$state} para familias locales. Explora los salones de Chroma, habilidades de preparación escolar e inscripción.",
            ],
            'after-school' => [
                'en' => "After School in {$city_name}, {$state}" . ($age_range !== '' ? " for ages {$age_range}." : '.') . ' Homework help, enrichment, and dependable care at Chroma.',
                'es' => "Programa after-school en {$city_name}, {$state}" . ($age_range !== '' ? " para edades {$age_range}." : '.') . ' Apoyo con tareas, actividades de enriquecimiento y cuidado confiable en Chroma.',
            ],
            'camp-summer-winter-fall' => [
                'en' => "Seasonal Camps in {$city_name}, {$state}" . ($age_range !== '' ? " for ages {$age_range}." : '.') . ' Discover field trips, themed weeks, and active learning at Chroma.',
                'es' => "Campamentos estacionales en {$city_name}, {$state}" . ($age_range !== '' ? " para edades {$age_range}." : '.') . ' Descubre excursiones, semanas temáticas y aprendizaje activo en Chroma.',
            ],
            'parents-day-out' => [
                'en' => "Parent's Day Out in {$city_name}, {$state}" . ($age_range !== '' ? " for ages {$age_range}." : '.') . ' Flexible care, engaging activities, and trusted support at Chroma.',
                'es' => "Día libre para padres en {$city_name}, {$state}" . ($age_range !== '' ? " para edades {$age_range}." : '.') . ' Cuidado flexible, actividades atractivas y apoyo confiable en Chroma.',
            ],
            'kindergarten' => [
                'en' => "Kindergarten in {$city_name}, {$state}. Explore Chroma classrooms, readiness skills, and private early elementary options for local families.",
                'es' => "Kindergarten en {$city_name}, {$state}. Explora los salones de Chroma, las habilidades de preparación escolar y las opciones para familias locales.",
            ],
            'rising-pre-k' => [
                'en' => "Rising Pre-K in {$city_name}, {$state}. Discover Chroma support for children preparing to enter Pre-K with confidence.",
                'es' => "Pre-K en ascenso en {$city_name}, {$state}. Descubre cómo Chroma apoya a niños que se preparan para entrar a Pre-K con confianza.",
            ],
            'rising-kindergarten' => [
                'en' => "Rising Kindergarten in {$city_name}, {$state}. Explore summer readiness support for children preparing for elementary school at Chroma.",
                'es' => "Kindergarten en ascenso en {$city_name}, {$state}. Explora el apoyo de preparación de verano para niños que se preparan para la primaria en Chroma.",
            ],
        ];

        $meta_description = $meta_map[$program_slug][$language] ?? '';
        if ($meta_description === '') {
            $meta_description = $language === 'es'
                ? "{$program_label} en {$city_name}, {$state}. Descubre el enfoque educativo de Chroma y agenda un recorrido hoy."
                : "{$program_label} in {$city_name}, {$state}. Discover the Chroma learning approach and schedule a tour today.";
        }

        return [
            'title' => chroma_seo_trim_title("{$program_label} " . ($language === 'es' ? 'en' : 'in') . " {$city_name}, {$state} | Chroma"),
            'meta_description' => chroma_seo_trim_meta_description($meta_description),
            'canonical' => chroma_seo_build_url_from_path($canonical_path),
            'indexable' => true,
            'sitemap_include' => true,
            'city_context' => $city_context,
        ];
    }
}

if (!function_exists('chroma_seo_build_near_me_profile')) {
    function chroma_seo_build_near_me_profile($keyword, $city_context = null, $language = '')
    {
        $language = chroma_seo_get_request_language($language);
        $keyword = sanitize_title((string) $keyword);

        $labels = [
            'daycare' => ['en' => 'Daycare', 'es' => 'Guardería'],
            'preschool' => ['en' => 'Preschool', 'es' => 'Preescolar'],
            'childcare' => ['en' => 'Childcare', 'es' => 'Cuidado infantil'],
            'pre-k' => ['en' => 'Pre-K', 'es' => 'Pre-K'],
            'infant-care' => ['en' => 'Infant Care', 'es' => 'Cuidado para bebés'],
        ];

        $label = $labels[$keyword][$language] ?? ucwords(str_replace('-', ' ', $keyword));

        if (is_array($city_context)) {
            $city_name = chroma_seo_get_city_display_name($city_context, $language);
            $state = strtoupper((string) ($city_context['state'] ?? 'GA'));
            $connector = $language === 'es' ? 'cerca de' : 'Near';
            $meta_description = $language === 'es'
                ? "Compara opciones confiables de {$label} cerca de {$city_name}, {$state}. Explora campus, currículo y recorridos de Chroma para familias locales."
                : "Compare trusted {$label} options near {$city_name}, {$state}. Explore nearby Chroma campuses, curriculum, and tour information for local families.";

            return [
                'title' => chroma_seo_trim_title("{$label} {$connector} {$city_name}, {$state} | Chroma"),
                'meta_description' => chroma_seo_trim_meta_description($meta_description),
                'canonical' => chroma_seo_build_url_from_path(($language === 'es' ? '/es/' : '/') . "{$keyword}-near-{$city_context['canonical_slug']}-" . strtolower($state) . '/'),
                'indexable' => true,
                'sitemap_include' => true,
                'city_context' => $city_context,
            ];
        }

        $meta_description = $language === 'es'
            ? "Compara opciones confiables de {$label} cerca de ti. Explora campus, currículo y recorridos de Chroma para familias en Georgia."
            : "Compare trusted {$label} options near you. Explore nearby Chroma campuses, curriculum, and tour information for Georgia families.";

        return [
            'title' => chroma_seo_trim_title("{$label} " . ($language === 'es' ? 'cerca de mí' : 'Near Me') . ' | Chroma'),
            'meta_description' => chroma_seo_trim_meta_description($meta_description),
            'canonical' => chroma_seo_build_url_from_path(($language === 'es' ? '/es/' : '/') . "{$keyword}-near-me/"),
            'indexable' => true,
            'sitemap_include' => true,
        ];
    }
}

if (!function_exists('chroma_resolve_current_seo_profile')) {
    function chroma_resolve_current_seo_profile()
    {
        $language = chroma_seo_get_request_language();
        $path = chroma_seo_get_request_path();

        $profile = [
            'title' => '',
            'meta_description' => '',
            'canonical' => chroma_seo_get_current_absolute_url(),
            'indexable' => !(is_404() || is_search() || is_feed() || is_robots()),
            'sitemap_include' => false,
            'lang' => $language,
            'route_type' => '',
        ];

        $route_key = chroma_seo_get_static_route_key($path);
        if ($route_key !== '') {
            $profile = array_merge($profile, chroma_seo_get_static_profile($route_key, $language));
            $profile['route_type'] = 'static';
            $profile['sitemap_include'] = $route_key === 'home';
            return apply_filters('chroma_seo_profile', $profile, ['route_key' => $route_key, 'path' => $path]);
        }

        if (is_post_type_archive()) {
            $post_type = (string) get_query_var('post_type');
            if ($post_type === '') {
                $post_type = (string) get_post_type();
            }
            if (in_array($post_type, ['location', 'program', 'city'], true)) {
                $profile = array_merge($profile, chroma_seo_get_archive_profile($post_type, $language));
                $profile['route_type'] = 'archive';
                return apply_filters('chroma_seo_profile', $profile, ['route_key' => $post_type . '_archive', 'path' => $path]);
            }
        }

        if ((string) get_query_var('chroma_combo') !== '') {
            $program_slug = sanitize_title((string) get_query_var('combo_program'));
            $city_slug = sanitize_title((string) get_query_var('combo_city'));
            $state = strtoupper((string) get_query_var('combo_state'));
            $program = function_exists('chroma_seo_resolve_program_for_combo_slug')
                ? chroma_seo_resolve_program_for_combo_slug($program_slug)
                : null;
            $city_context = $city_slug !== '' ? chroma_seo_resolve_virtual_city_context($city_slug, $state) : null;

            if ($program instanceof WP_Post && is_array($city_context)) {
                $profile = array_merge($profile, chroma_seo_build_combo_profile($program, $city_context, $language));
                $profile['route_type'] = 'combo';
                return apply_filters('chroma_seo_profile', $profile, [
                    'route_key' => 'combo',
                    'path' => $path,
                    'post' => $program,
                    'city_context' => $city_context,
                ]);
            }
        }

        $near_keyword = sanitize_title((string) get_query_var('chroma_near_me'));
        if ($near_keyword !== '') {
            $city_slug = sanitize_title((string) get_query_var('near_city'));
            $state = strtoupper((string) get_query_var('near_state'));
            $city_context = null;

            if ($city_slug !== '') {
                $city_context = chroma_seo_resolve_virtual_city_context($city_slug, $state);
            }

            $profile = array_merge($profile, chroma_seo_build_near_me_profile($near_keyword, $city_context, $language));
            $profile['route_type'] = 'near_me';

            return apply_filters('chroma_seo_profile', $profile, [
                'route_key' => 'near_me',
                'path' => $path,
                'keyword' => $near_keyword,
                'city_context' => $city_context,
            ]);
        }

        if (is_singular()) {
            $post = get_post();
            if ($post instanceof WP_Post) {
                $profile = array_merge($profile, chroma_seo_build_singular_profile($post, $language));
                $profile['route_type'] = 'singular';
                return apply_filters('chroma_seo_profile', $profile, ['post' => $post, 'path' => $path]);
            }
        }

        return apply_filters('chroma_seo_profile', $profile, ['path' => $path]);
    }
}
