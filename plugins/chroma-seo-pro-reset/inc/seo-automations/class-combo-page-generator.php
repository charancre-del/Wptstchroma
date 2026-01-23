<?php
/**
 * Program + City Combo Page Generator
 * Auto-generates landing pages like /pre-k-in-cumming-ga/
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Combo_Page_Generator
{
    const REWRITE_TAG = 'chroma_combo';
    
    public function __construct() {
        add_action('init', [$this, 'add_rewrite_rules']);
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_action('template_redirect', [$this, 'handle_combo_page']);

        add_action('admin_menu', [$this, 'add_admin_page'], 20);
        
        add_action('template_redirect', [$this, 'handle_sitemap']); // Manual Sitemap Handler
        add_filter('wpseo_sitemap_index', [$this, 'add_to_sitemap']); // Yoast index integration
        
        // Register Native Sitemap Providers (EN and ES)
        if (did_action('init')) {
            $this->register_sitemap_providers();
        } else {
            add_action('init', [$this, 'register_sitemap_providers']);
        }
        
        // Auto-flush rules if needed (Temporary for update)
        if (!get_option('chroma_combo_sitemap_flush_Check')) {
            add_action('init', function() {
                flush_rewrite_rules();
                update_option('chroma_combo_sitemap_flush_Check', true);
            }, 99);
        }
    }

    
    public function add_rewrite_rules() {
        // Pattern: /program-in-city-state/
        add_rewrite_rule(
            '^([a-z0-9-]+)-in-([a-z-]+)-([a-z]{2})/?$',
            'index.php?' . self::REWRITE_TAG . '=1&combo_program=$matches[1]&combo_city=$matches[2]&combo_state=$matches[3]',
            'top'
        );

        // Custom Sitemap Rules
        add_rewrite_rule('^sitemap-combos\.xml$', 'index.php?chroma_combo_sitemap=en', 'top');
        add_rewrite_rule('^sitemap-combos-es\.xml$', 'index.php?chroma_combo_sitemap=es', 'top');
    }
    
    public function add_query_vars($vars) {
        $vars[] = self::REWRITE_TAG;
        $vars[] = 'combo_program';
        $vars[] = 'combo_city';
        $vars[] = 'combo_state';
        $vars[] = 'chroma_combo_sitemap'; 
        return $vars;
    }
    
    /**
     * Register WP Native Sitemap Providers
     */
    public function register_sitemap_providers() {
        wp_register_sitemap_provider('combos', new Chroma_Combo_Sitemap_Provider('en'));
        wp_register_sitemap_provider('combos-es', new Chroma_Combo_Sitemap_Provider('es'));
    }
    
    /**
     * Handle combo page request
     */
    public function handle_combo_page() {
        if (!get_query_var(self::REWRITE_TAG)) {
            return;
        }
        
        $program_slug = sanitize_title(get_query_var('combo_program'));
        $city_slug = sanitize_title(get_query_var('combo_city'));
        $state = strtoupper(sanitize_text_field(get_query_var('combo_state')));
        
        // Find program
        $program = get_page_by_path($program_slug, OBJECT, 'program');
        if (!$program) {
            $programs = get_posts([
                'post_type' => 'program',
                'name' => $program_slug,
                'posts_per_page' => 1
            ]);
            $program = $programs[0] ?? null;
        }
        
        if (!$program) {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            return;
        }
        
        // Find nearest location in this city/state
        $location = $this->find_location_in_city($city_slug, $state);
        
        // Setup global post for template compatibility
        global $post, $wp_query;
        $post = $program;
        setup_postdata($post);
        
        // Configure main query to look like a singular program
        $wp_query->is_page = false;
        $wp_query->is_single = true;
        $wp_query->is_singular = true;
        $wp_query->is_home = false;
        $wp_query->is_archive = false;
        $wp_query->is_404 = false;
        $wp_query->queried_object = $program;
        $wp_query->queried_object_id = $program->ID;
        $wp_query->post = $program;
        
        // Initializing variables for header checks
        $wp_query->posts = [$program];
        $wp_query->post_count = 1;
        $wp_query->found_posts = 1;

        // Prepare data for Schema (must happen before get_header)
        $city_name = ucwords(str_replace('-', ' ', $city_slug));
        $loc_address = $location ? get_post_meta($location->ID, 'location_address', true) : '';
        $loc_zip = $location ? get_post_meta($location->ID, 'location_zip', true) : '';
        $age_range = get_post_meta($program->ID, 'program_age_range', true);

        // Add Schema
        $this->output_schema($program, $city_name, $state, $location, $loc_address, $loc_zip, $age_range);

        // Add Body Class
        add_filter('body_class', function($classes) {
            $classes[] = 'combo-page';
            return $classes;
        });

        // Force Canonical (Closure method to ensure context)
        $combo_canonical = home_url("/{$program_slug}-in-{$city_slug}-{$state}/");
        
        // High priority filter for Yoast Canonical AND OpenGraph URL
        foreach (['wpseo_canonical', 'wpseo_opengraph_url'] as $filter) {
            add_filter($filter, function($current) use ($combo_canonical) {
                return $combo_canonical;
            }, PHP_INT_MAX);
        }
        
        // Dynamic SEO Title (30-60 chars target)
        $program_title = get_the_title($program);
        $seo_title = "{$program_title} in {$city_name}, {$state} | Chroma";
        
        // Truncate if too long (max 60 chars)
        if (strlen($seo_title) > 60) {
            $seo_title = "{$program_title} in {$city_name} | Chroma";
        }
        if (strlen($seo_title) > 60) {
            $seo_title = substr($seo_title, 0, 57) . '...';
        }
        
        add_filter('wpseo_title', function($current) use ($seo_title) {
            return $seo_title;
        }, PHP_INT_MAX);
        
        add_filter('pre_get_document_title', function($current) use ($seo_title) {
            return $seo_title;
        }, PHP_INT_MAX);
        
        // Dynamic Meta Description (60-160 chars target)
        $meta_desc = $this->generate_combo_meta_description($program, $city_name, $state, $age_range);
        
        add_filter('wpseo_metadesc', function($current) use ($meta_desc) {
            return $meta_desc;
        }, PHP_INT_MAX);
        
        add_filter('wpseo_opengraph_desc', function($current) use ($meta_desc) {
            return $meta_desc;
        }, PHP_INT_MAX);
        
        // Also output meta description tag directly (in case Yoast isn't outputting it)
        add_action('wp_head', function() use ($meta_desc) {
            echo '<meta name="description" content="' . esc_attr($meta_desc) . '" />' . "\n";
        }, 1);
        
        // Render the page
        status_header(200);
        get_header();
        echo $this->get_combo_page_html($program, $city_slug, $state, $location);
        get_footer();
        exit;
    }
    
    /**
     * Find location in or near a city
     */
    public function find_location_in_city($city_slug, $state) {
        $city_normalized = str_replace('-', ' ', $city_slug);
        
        $locations = get_posts([
            'post_type' => 'location',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ]);
        
        // Exact match first
        foreach ($locations as $loc) {
            $loc_city = get_post_meta($loc->ID, 'location_city', true);
            $loc_state = get_post_meta($loc->ID, 'location_state', true);
            
            if (strtolower($loc_city) === $city_normalized && strtoupper($loc_state) === $state) {
                return $loc;
            }
        }
        
        // Return first location in state
        foreach ($locations as $loc) {
            $loc_state = get_post_meta($loc->ID, 'location_state', true);
            if (strtoupper($loc_state) === $state) {
                return $loc;
            }
        }
        
        // Return any location
        return $locations[0] ?? null;
    }
    
    /**
     * Generate dynamic meta description for combo page (60-160 chars)
     */
    public function generate_combo_meta_description($program, $city_name, $state, $age_range) {
        $program_title = get_the_title($program);
        $program_slug = $program->post_name;
        
        // Define templates based on program type
        $templates = [
            'infant-care' => "Trusted Infant Care in {$city_name}, {$state} for babies {$age_range}. Safe sleep, sensory play & nurturing caregivers. Tour today!",
            'toddler-care' => "Quality Toddler Care in {$city_name}, {$state} for ages {$age_range}. Language-rich learning, guided play & caring teachers. Enroll now!",
            'preschool' => "Explore Preschool in {$city_name}, {$state} for ages {$age_range}. Hands-on learning, small classes & dedicated teachers. Schedule a tour!",
            'pre-k-prep' => "Pre-K Prep in {$city_name}, {$state} for ages {$age_range}. Kindergarten readiness, structured learning & social growth. Enroll today!",
            'ga-pre-k' => "Free GA Pre-K in {$city_name} for 4-year-olds. State-funded, kindergarten-ready curriculum at Chroma. Limited spots—enroll now!",
            'after-school' => "After School program in {$city_name}, {$state} for ages {$age_range}. Homework help, enrichment activities & safe transportation. Join us!",
            'camp-summer-winter-fall' => "Summer & Holiday Camps in {$city_name}, {$state}. Fun activities, field trips & friendships for kids {$age_range}. Register today!",
            'parents-day-out' => "Parents Day Out in {$city_name}, {$state} for ages {$age_range}. Flexible care, engaging activities & peace of mind. Book your spot!",
        ];
        
        // Get template or generate a generic one
        if (isset($templates[$program_slug])) {
            $description = $templates[$program_slug];
        } else {
            // Generic fallback
            $description = "{$program_title} in {$city_name}, {$state}";
            if ($age_range) {
                $description .= " for ages {$age_range}";
            }
            $description .= ". Quality early learning with caring teachers at Chroma Early Learning. Schedule a tour today!";
        }
        
        // Handle empty age range
        $description = str_replace(' for ages .', '.', $description);
        $description = str_replace(' for babies .', '.', $description);
        $description = str_replace(' for kids .', '.', $description);
        
        // Ensure within 160 chars
        if (strlen($description) > 160) {
            $description = substr($description, 0, 157) . '...';
        }
        
        // Ensure minimum 60 chars
        if (strlen($description) < 60) {
            $description .= " Trusted childcare serving families in Metro Atlanta.";
        }
        
        return $description;
    }
    
    /**
     * Get combo page HTML content
     */
    public function get_combo_page_html($program, $city_slug, $state, $location) {
        // Defensive check
        if (!$program || !is_a($program, 'WP_Post')) {
            return '<div class="py-20 text-center">System validation error: Invalid Program Object.</div>';
        }

        $city_name = ucwords(str_replace('-', ' ', $city_slug));
        
        // Get program details
        $age_range = get_post_meta($program->ID, 'program_age_range', true);
        $description = get_the_excerpt($program) ?: wp_trim_words($program->post_content, 50);
        $program_image = get_the_post_thumbnail_url($program, 'large') ?: '';
        
        // Try to get city page for enhanced local SEO data
        $city_page = $this->find_city_page($city_slug, $state);
        
        // Location details (physical address, phone) - from location post
        $loc_name = '';
        $loc_address = '';
        $loc_phone = '';
        $loc_url = '';
        $loc_zip = '';
        
        // Always get location details from the location post itself
        if ($location) {
            $loc_name = $location->post_title;
            $loc_address = get_post_meta($location->ID, 'location_address', true);
            $loc_phone = get_post_meta($location->ID, 'location_phone', true);
            $loc_url = get_permalink($location);
            $loc_zip = get_post_meta($location->ID, 'location_zip', true);
        }
        
        // Local SEO data (neighborhoods, roads, employers) - from city page
        $neighborhoods = [];
        $major_road = '';
        $local_employers = '';
        $county = 'Forsyth'; // Default
        
        if ($city_page) {
            $raw_neighborhoods = get_post_meta($city_page->ID, 'city_neighborhoods', true);
            if (is_array($raw_neighborhoods)) {
                $neighborhoods = $raw_neighborhoods;
            } else {
                $neighborhoods = array_filter(array_map('trim', explode(',', (string)$raw_neighborhoods)));
            }
            $major_road = get_post_meta($city_page->ID, 'city_major_road', true);
            $local_employers = get_post_meta($city_page->ID, 'city_employers', true);
            $county = get_post_meta($city_page->ID, 'city_county', true) ?: 'Forsyth';
        } elseif ($location) {
            // Fallback: try to get county from location
            $county = get_post_meta($location->ID, 'location_county', true) ?: 'Forsyth';
        }
        
        // OVERRIDE: Check for specific combo page data (AI Generated or Manual Edit)
        // This is stored in options table via Chroma_Combo_Page_Data class
        $combo_custom_data = Chroma_Combo_Page_Data::get($program->post_name, $city_slug, $state);
        
        if (!empty($combo_custom_data)) {
            if (!empty($combo_custom_data['neighborhoods'])) {
                $neighborhoods = $combo_custom_data['neighborhoods'];
            }
            if (!empty($combo_custom_data['major_road'])) {
                $major_road = $combo_custom_data['major_road'];
            }
            if (!empty($combo_custom_data['local_employers'])) {
                $local_employers = $combo_custom_data['local_employers'];
            }
            if (!empty($combo_custom_data['county'])) {
                $county = $combo_custom_data['county'];
            }
        }
        
        // Spanish Override
        $lang = function_exists('chroma_detect_current_language') ? chroma_detect_current_language() : 'en';
        
        if ($lang === 'es' && !empty($combo_custom_data)) {
            if (!empty($combo_custom_data['neighborhoods_es'])) {
                $neighborhoods = $combo_custom_data['neighborhoods_es'];
            }
            if (!empty($combo_custom_data['major_road_es'])) {
                $major_road = $combo_custom_data['major_road_es'];
            }
            if (!empty($combo_custom_data['local_employers_es'])) {
                $local_employers = $combo_custom_data['local_employers_es'];
            }
            if (!empty($combo_custom_data['custom_intro_es'])) {
                $combo_custom_data['custom_intro'] = $combo_custom_data['custom_intro_es'];
            }
        }

        // Fallback neighborhoods if none defined
        if (empty($neighborhoods)) {
            $neighborhoods = [$city_name . ' Downtown', $city_name . ' North', $city_name . ' South'];
        }
        
        // Define Intro Text
        $intro_text = '';
        if (!empty($combo_custom_data['custom_intro'])) {
            $intro_text = $combo_custom_data['custom_intro'];
        } else {
            // Default fallback logic
            $intro_text = sprintf(
                __('Searching for the best %s near %s? At Chroma %s, we combine the safety you need with the enriching curriculum your child deserves.', 'chroma-excellence'),
                strtolower($program->post_title),
                esc_html($neighborhoods[0] ?? $city_name),
                esc_html($city_name)
            );
        }
        
        // Output schema in head (still needs to run directly as it hooks to wp_head)
        // Note: handle_combo_page will call this separately, or we can leave the hook here if we are careful.
        // Better to move the hook to handle_combo_page to separate logic.
        
        ob_start();

        // Safe Translation Preparation
        // Cast to string to prevent object errors
        $t_prog_title = (string) $program->post_title;
        $t_city_name = (string) $city_name;
        $t_age_range = (string) $age_range;
        
        // Define translated strings - strictly using variables
        $str_now_enrolling = sprintf(__('Now Enrolling: %s', 'chroma-excellence'), $t_age_range);
        $str_premier_title = sprintf(__('Premier %s in', 'chroma-excellence'), $t_prog_title);
        $str_schedule_visit = __('Schedule Visit', 'chroma-excellence');
        $str_serving_families = sprintf(__('Serving %s Families', 'chroma-excellence'), $t_city_name);
        $str_why_choose = sprintf(__('Why %s Parents Choose Our %s', 'chroma-excellence'), $t_city_name, $t_prog_title);
        $str_understanding = sprintf(__('We understand that choosing care in %s is a big decision. Here is what sets our %s apart.', 'chroma-excellence'), $t_city_name, $t_prog_title);
        $str_low_ratios = __('Low Ratios', 'chroma-excellence');
        $str_ratios_desc = sprintf(__('Our %s campus maintains strict teacher-to-student ratios, ensuring your child gets the individual attention they need.', 'chroma-excellence'), $t_city_name);
        $str_curriculum_title = __('Prismpath™ Curriculum', 'chroma-excellence');
        
        // Handle ternary for age label safely
        $t_early_learners = __('early learners', 'chroma-excellence');
        $str_curriculum_desc = sprintf(__('Specifically designed for %s, our curriculum balances play-based learning with school readiness.', 'chroma-excellence'), $t_age_range ?: $t_early_learners);
        
        $str_updates_title = __('Real-Time Updates', 'chroma-excellence');
        $str_updates_desc = sprintf(__('Parents in %s love our app. Get photos and updates throughout the workday straight to your phone.', 'chroma-excellence'), $t_city_name);
        
        // Neighborhood logic
        $t_neigh_0 = $neighborhoods[0] ?? $t_city_name;
        $str_serving_header = sprintf(__('Serving Families in %s', 'chroma-excellence'), $t_neigh_0);
        
        $str_locations_serving = sprintf(__('Chroma Locations Serving %s', 'chroma-excellence'), $t_city_name);
        $str_select_campus = __('Select the campus closest to your home or work.', 'chroma-excellence');
        $str_view_campus = __('View Campus', 'chroma-excellence');
        $str_visit_classroom = sprintf(__('Visit Our %s Classroom', 'chroma-excellence'), $t_city_name);
        $str_see_environment = sprintf(__('See the %s environment in person. Meet our Director and teachers.', 'chroma-excellence'), $t_prog_title);
        $str_more_options = sprintf(__('More Childcare Options in %s', 'chroma-excellence'), $t_city_name);
        
        // Location Text Logic
        $t_major_road = $major_road;
        $t_county = $county;
        
        if ($t_major_road) {
            $str_location_intro = sprintf(__('Located conveniently off %s, our', 'chroma-excellence'), '<strong>' . esc_html($t_major_road) . '</strong>');
        } else {
            $str_location_intro = __('Our', 'chroma-excellence');
        }

        $t_neighborhoods_html = '<strong>' . implode('</strong>, <strong>', array_map('esc_html', array_slice($neighborhoods, 0, 3))) . '</strong>';
        $str_location_main = sprintf(__('%s campus is the preferred choice for families living in %s.', 'chroma-excellence'), esc_html($t_city_name), $t_neighborhoods_html);

        if ($local_employers) {
            $str_commute = sprintf(__('Whether you work at %s or commute via %s, our drop-off and pick-up hours (6:30 AM – 6:30 PM) are designed for working parents in %s County.', 'chroma-excellence'), 
                '<strong>' . esc_html($local_employers) . '</strong>', 
                esc_html($t_major_road ?: 'GA-400'), 
                esc_html($t_county)
            );
        } else {
            $str_commute = sprintf(__('Our convenient hours (6:30 AM – 6:30 PM) are designed for working parents in %s County.', 'chroma-excellence'), esc_html($t_county));
        }
        ?>
        <main class="combo-page bg-brand-cream">
            
            <!-- Hero Section -->
            <section class="relative pt-20 pb-24 bg-white overflow-hidden">
                <div class="absolute top-0 right-0 w-2/3 h-full bg-gradient-to-l from-chroma-blue/5 to-transparent -z-10"></div>
                <div class="max-w-7xl mx-auto px-4 lg:px-6 grid lg:grid-cols-2 gap-12 items-center">
                    <div>
                        <?php if ($age_range): ?>
                        <div class="inline-flex items-center gap-2 bg-chroma-blue/10 text-chroma-blue px-4 py-1.5 rounded-full text-[11px] font-bold uppercase tracking-widest mb-6">
                            <?php echo esc_html($str_now_enrolling); ?>
                        </div>
                        <?php endif; ?>
                        
                        <h1 class="font-serif text-4xl md:text-5xl lg:text-6xl text-brand-ink mb-6 leading-tight">
                            <?php echo esc_html($str_premier_title); ?> 
                            <span class="italic text-chroma-blue"><?php echo esc_html($city_name); ?>, <?php echo esc_html($state); ?>.</span>
                        </h1>
                        
                        <p class="text-lg text-brand-ink/70 mb-8 leading-relaxed">
                            <?php echo wp_kses_post($intro_text); ?>
                        </p>
                        
                        <div class="flex flex-wrap gap-4">
                            <a href="#tour" class="inline-flex items-center justify-center px-8 py-4 rounded-full bg-chroma-blue text-white text-xs font-bold uppercase tracking-[0.2em] shadow-soft hover:bg-brand-ink transition-all">
                                <?php echo esc_html($str_schedule_visit); ?>
                            </a>
                            <?php 
                            // Only show address if the location is actually IN this city
                            $show_loc_details = false;
                            if ($location) {
                                $real_city = get_post_meta($location->ID, 'location_city', true);
                                if (strcasecmp(trim($real_city), trim($city_slug)) === 0 || strcasecmp(trim($real_city), trim($city_name)) === 0) {
                                    $show_loc_details = true;
                                }
                            }
                            ?>

                            <?php if ($show_loc_details && $loc_address): ?>
                            <span class="flex items-center gap-2 text-sm font-bold text-brand-ink/60 px-4 py-4">
                                <svg class="w-4 h-4 text-chroma-red" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
                                <?php echo esc_html($loc_address); ?>
                            </span>
                            <?php else: ?>
                            <span class="flex items-center gap-2 text-sm font-bold text-brand-ink/60 px-4 py-4">
                                <svg class="w-4 h-4 text-chroma-blue" fill="currentColor" viewBox="0 0 20 20"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/></svg>
                                <?php echo esc_html($str_serving_families); ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="relative h-[400px] lg:h-[450px] rounded-[2rem] lg:rounded-[3rem] overflow-hidden shadow-2xl border-4 border-white">
                        <?php if ($program_image): ?>
                            <img src="<?php echo esc_url($program_image); ?>" 
                                 class="w-full h-full object-cover" 
                                 alt="<?php echo esc_attr($program->post_title); ?> students in <?php echo esc_attr($city_name); ?>" 
                                 loading="eager">
                        <?php else: ?>
                            <div class="w-full h-full bg-gradient-to-br from-chroma-blue/20 to-chroma-red/20 flex items-center justify-center">
                                <span class="text-6xl">🎨</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
            
            <!-- Why Choose Section -->
            <section class="py-20 bg-brand-cream">
                <div class="max-w-7xl mx-auto px-4 lg:px-6">
                    <div class="text-center mb-16 max-w-3xl mx-auto">
                        <h2 class="text-3xl md:text-4xl font-serif font-bold text-brand-ink mb-4">
                            <?php echo esc_html($str_why_choose); ?>
                        </h2>
                        <p class="text-brand-ink/60">
                            <?php echo esc_html($str_understanding); ?>
                        </p>
                    </div>
                    
                    <div class="grid md:grid-cols-3 gap-8">
                        <!-- Benefit 1: Low Ratios -->
                        <div class="bg-white p-8 rounded-3xl shadow-sm border border-brand-ink/5">
                            <div class="w-12 h-12 bg-chroma-red/10 text-chroma-red rounded-xl flex items-center justify-center text-xl mb-4">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"/></svg>
                            </div>
                            <h3 class="font-bold text-xl mb-2"><?php echo esc_html($str_low_ratios); ?></h3>
                            <p class="text-sm text-brand-ink/70"><?php echo esc_html($str_ratios_desc); ?></p>
                        </div>
                        
                        <!-- Benefit 2: Prismpath -->
                        <div class="bg-white p-8 rounded-3xl shadow-sm border border-brand-ink/5">
                            <div class="w-12 h-12 bg-chroma-yellow/10 text-chroma-yellow rounded-xl flex items-center justify-center text-xl mb-4">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            </div>
                            <h3 class="font-bold text-xl mb-2"><?php echo esc_html($str_curriculum_title); ?></h3>
                            <p class="text-sm text-brand-ink/70"><?php echo esc_html($str_curriculum_desc); ?></p>
                        </div>
                        
                        <!-- Benefit 3: Real-Time Updates -->
                        <div class="bg-white p-8 rounded-3xl shadow-sm border border-brand-ink/5">
                            <div class="w-12 h-12 bg-chroma-green/10 text-chroma-green rounded-xl flex items-center justify-center text-xl mb-4">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/></svg>
                            </div>
                            <h3 class="font-bold text-xl mb-2"><?php echo esc_html($str_updates_title); ?></h3>
                            <p class="text-sm text-brand-ink/70"><?php echo esc_html($str_updates_desc); ?></p>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Hyper-Local SEO Section -->
            <section class="py-20 bg-white border-y border-brand-ink/5">
                <div class="max-w-4xl mx-auto px-4 text-center">
                    <h2 class="font-serif text-3xl font-bold mb-6">
                        <?php echo esc_html($str_serving_header); ?> 
                        <?php if (count($neighborhoods) > 1): ?> & <?php echo esc_html($neighborhoods[1]); ?><?php endif; ?>
                    </h2>
                    <p class="text-lg text-brand-ink/70 leading-relaxed">
                        <?php echo $str_location_intro; ?> 
                        <?php echo $str_location_main; ?>
                        <br><br>
                        <?php echo $str_commute; ?>
                    </p>
                </div>
            </section>
            
            <!-- Tour/Location Selection Section -->
            <section id="tour" class="py-24 bg-chroma-blueDark text-white">
                <div class="max-w-7xl mx-auto px-4 text-center">
                    <?php 
                    // Check for nearby locations from City Page
                    $nearby_ids = [];
                    if ($city_page) {
                        $nearby_ids = get_post_meta($city_page->ID, 'city_nearby_locations', true);
                    }
                    
                    // If no manually selected locations, but we found a STRICT match location for this city, show it!
                    if (empty($nearby_ids) && $location) {
                        $real_city = get_post_meta($location->ID, 'location_city', true);
                        if (strcasecmp(trim($real_city), trim($city_slug)) === 0 || strcasecmp(trim($real_city), trim($city_name)) === 0) {
                            $nearby_ids = [$location->ID];
                        }
                    }

                    // Fallback to searching if no explicit links (optional, but requested to use checked ones)
                    // If we have locations, show grid. Else show form.
                    if (!empty($nearby_ids) && is_array($nearby_ids)): 
                        $grid_query = new WP_Query([
                            'post_type' => 'location',
                            'post__in' => $nearby_ids,
                            'orderby' => 'post__in',
                            'posts_per_page' => -1
                        ]);
                    ?>
                        <h2 class="font-serif text-3xl md:text-4xl font-bold mb-6"><?php echo esc_html($str_locations_serving); ?></h2>
                        <p class="text-white/60 mb-12"><?php echo esc_html($str_select_campus); ?></p>
                        
                        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8 text-left">
                            <?php 
                            while ($grid_query->have_posts()): $grid_query->the_post(); 
                                $grid_id = get_the_ID();
                                $grid_address = get_post_meta($grid_id, 'location_address', true);
                                $grid_city = get_post_meta($grid_id, 'location_city', true);
                                if ($grid_city && !$grid_address) $grid_address = $grid_city;
                                $grid_rating = get_post_meta($grid_id, 'location_google_rating', true) ?: '4.9';
                                $grid_image = get_the_post_thumbnail_url($grid_id, 'medium_large') ?: 'https://images.unsplash.com/photo-1587654780291-39c9404d746b?q=80&w=600&auto=format&fit=crop';
                            ?>
                            <div class="group p-6 rounded-3xl bg-white text-brand-ink shadow-2xl transition-all hover:-translate-y-1">
                                <div class="h-48 rounded-2xl bg-brand-cream mb-6 overflow-hidden relative">
                                    <img src="<?php echo esc_url($grid_image); ?>" class="w-full h-full object-cover" alt="<?php the_title_attribute(); ?>">
                                    <div class="absolute top-4 right-4 bg-white px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide shadow-sm text-brand-ink">
                                        <?php echo esc_html($grid_rating); ?> ★
                                    </div>
                                </div>
                                <h3 class="font-serif text-xl font-bold mb-2"><?php the_title(); ?></h3>
                                <?php if ($grid_address): ?>
                                <p class="text-sm opacity-60 mb-1"><?php echo esc_html($grid_address); ?></p>
                                <?php endif; ?>
                                    <p class="text-xs font-bold uppercase tracking-widest mb-6 opacity-80"><?php echo esc_html($str_serving_families); ?></p>
                                <div class="mt-auto">
                                    <a href="<?php the_permalink(); ?>" class="block w-full py-3 bg-chroma-blue text-white text-center rounded-xl text-xs font-bold uppercase tracking-widest hover:bg-brand-ink transition-colors">
                                        <?php echo esc_html($str_view_campus); ?>
                                    </a>
                                </div>
                            </div>
                            <?php endwhile; wp_reset_postdata(); ?>
                        </div>

                    <?php else: ?>
                        <!-- Default Form Logic (Fallback) -->
                        <div class="max-w-3xl mx-auto">
                            <h2 class="font-serif text-3xl md:text-4xl font-bold mb-6"><?php echo esc_html($str_visit_classroom); ?></h2>
                            <p class="text-white/60 mb-10"><?php echo esc_html($str_see_environment); ?></p>
                            
                            <div class="bg-white p-8 rounded-[2rem] text-left shadow-2xl">
                                <?php echo do_shortcode('[chroma_tour_form]'); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
            
            <!-- Interlinking Section: Other Programs in City -->
            <section class="py-20 bg-brand-cream border-t border-brand-ink/5">
                <div class="max-w-7xl mx-auto px-4 lg:px-6">
                    <h2 class="font-serif text-2xl md:text-3xl font-bold text-center text-brand-ink mb-10">
                        <?php echo esc_html($str_more_options); ?>
                    </h2>
                    
                    <div class="flex flex-wrap justify-center gap-4">
                        <?php 
                        $other_programs = get_posts(['post_type' => 'program', 'posts_per_page' => -1, 'exclude' => [$program->ID], 'orderby' => 'title', 'order' => 'ASC']);
                        foreach ($other_programs as $op): 
                            $link = home_url('/' . $op->post_name . '-in-' . $city_slug . '-' . strtolower($state) . '/');
                        ?>
                        <a href="<?php echo esc_url($link); ?>" class="px-6 py-3 bg-white rounded-full text-sm font-bold text-brand-ink hover:bg-chroma-blue hover:text-white transition-all shadow-sm border border-brand-ink/5">
                            <?php echo esc_html($op->post_title); ?> in <?php echo esc_html($city_name); ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <!-- FAQ Section -->
            <section class="py-24 bg-white border-t border-brand-ink/5">
                <div class="max-w-4xl mx-auto px-4">
                    <h2 class="font-serif text-3xl md:text-4xl font-bold text-center text-brand-ink mb-12">Common Questions</h2>
                    
                    <div class="space-y-4">
                        <details class="group bg-brand-cream rounded-2xl p-6 shadow-sm border border-brand-ink/5 cursor-pointer">
                            <summary class="flex items-center justify-between font-bold text-brand-ink list-none">
                                <span>What are the tuition rates for <?php echo esc_html($program->post_title); ?> in <?php echo esc_html($city_name); ?>?</span>
                                <span class="text-chroma-blue group-open:rotate-180 transition-transform">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </span>
                            </summary>
                            <p class="mt-3 text-sm text-brand-ink/70 leading-relaxed">
                                Tuition varies based on the specific program and schedule (full-time vs. part-time). Please schedule a tour to receive a detailed tuition sheet for our <?php echo esc_html($city_name); ?> campus.
                            </p>
                        </details>
                        
                        <details class="group bg-brand-cream rounded-2xl p-6 shadow-sm border border-brand-ink/5 cursor-pointer">
                            <summary class="flex items-center justify-between font-bold text-brand-ink list-none">
                                <span>Is food included in the program?</span>
                                <span class="text-chroma-blue group-open:rotate-180 transition-transform">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </span>
                            </summary>
                            <p class="mt-3 text-sm text-brand-ink/70 leading-relaxed">
                                Yes! We provide a nutritious breakfast, hot lunch, and afternoon snack prepared fresh daily. Our menus are CACFP compliant and we accommodate most dietary restrictions.
                            </p>
                        </details>
                        
                        <details class="group bg-brand-cream rounded-2xl p-6 shadow-sm border border-brand-ink/5 cursor-pointer">
                            <summary class="flex items-center justify-between font-bold text-brand-ink list-none">
                                <span>Are the teachers at <?php echo esc_html($city_name); ?> certified?</span>
                                <span class="text-chroma-blue group-open:rotate-180 transition-transform">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </span>
                            </summary>
                            <p class="mt-3 text-sm text-brand-ink/70 leading-relaxed">
                                Absolutely. All lead teachers hold a CDA, TCC, or higher degree in Early Childhood Education. Every staff member is also CPR/First Aid certified and undergoes rigorous background checks.
                            </p>
                        </details>
                        
                        <?php if ($age_range): ?>
                        <details class="group bg-brand-cream rounded-2xl p-6 shadow-sm border border-brand-ink/5 cursor-pointer">
                            <summary class="flex items-center justify-between font-bold text-brand-ink list-none">
                                <span>What ages does the <?php echo esc_html($program->post_title); ?> program serve?</span>
                                <span class="text-chroma-blue group-open:rotate-180 transition-transform">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </span>
                            </summary>
                            <p class="mt-3 text-sm text-brand-ink/70 leading-relaxed">
                                Our <?php echo esc_html($program->post_title); ?> program serves children ages <?php echo esc_html($age_range); ?>.
                            </p>
                        </details>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
            
        </main>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Find city CPT page
     */
    public function find_city_page($city_slug, $state) {
        $city_normalized = str_replace('-', ' ', $city_slug);
        
        $cities = get_posts([
            'post_type' => 'city',
            'posts_per_page' => 1,
            'post_status' => 'publish',
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key' => 'city_name',
                    'value' => $city_normalized,
                    'compare' => 'LIKE'
                ]
            ]
        ]);
        
        if (!empty($cities)) {
            return $cities[0];
        }
        
        // Fallback: try by post title
        $city = get_page_by_path($city_slug, OBJECT, 'city');
        return $city ?: null;
    }
    
    /**
     * Output schema markup
     */
    public function output_schema($program, $city_name, $state, $location, $loc_address = '', $loc_zip = '', $age_range = '') {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Service',
            'serviceType' => $program->post_title,
            'provider' => [
                '@type' => 'Preschool',
                'name' => 'Chroma Early Learning Academy - ' . $city_name,
                'address' => [
                    '@type' => 'PostalAddress',
                    'addressLocality' => $city_name,
                    'addressRegion' => $state
                ]
            ],
            'areaServed' => [
                '@type' => 'City',
                'name' => $city_name
            ]
        ];
        
        if ($loc_zip) {
            $schema['provider']['address']['postalCode'] = $loc_zip;
        }

        if ($age_range) {
            $schema['audience'] = [
                '@type' => 'EducationalAudience',
                'educationalRole' => 'student',
                'audienceType' => 'Children ' . $age_range
            ];
        }
        
        // Register to Central Registry
        if (class_exists('Chroma_Schema_Registry')) {
            Chroma_Schema_Registry::register($schema, ['source' => 'combo-page-generator']);
        }
    }
    
    /**
     * Get all combo pages for sitemap
     */
    public static function get_all_combos() {
        $combos = [];
        
        $programs = get_posts(['post_type' => 'program', 'posts_per_page' => -1]);
        $cities = self::get_all_cities();
        
        foreach ($programs as $prog) {
            foreach ($cities as $city) {
                $combos[] = [
                    'program' => $prog,
                    'city' => $city['city'],
                    'state' => $city['state'],
                    'url' => home_url('/' . $prog->post_name . '-in-' . sanitize_title($city['city']) . '-' . strtolower($city['state']) . '/'),
                    'city_page_id' => $city['city_page_id'] ?? null,
                    'location_id' => $city['location_id'] ?? null
                ];
            }
        }
        
        return $combos;
    }
    
    /**
     * Get all cities from hybrid sources
     * Priority: City CPT → Manual additions → Locations (only for linking)
     */
    public static function get_all_cities() {
        $cities = [];
        
        // Source 1: City CPT (PRIMARY SOURCE)
        $city_posts = get_posts(['post_type' => 'city', 'posts_per_page' => -1, 'post_status' => 'publish']);
        foreach ($city_posts as $city_post) {
            $city_name = get_post_meta($city_post->ID, 'city_name', true) ?: $city_post->post_title;
            $state = get_post_meta($city_post->ID, 'city_state', true) ?: 'GA';
            if ($city_name && $state) {
                $key = sanitize_title($city_name . '-' . $state);
                $cities[$key] = [
                    'city' => $city_name, 
                    'state' => $state, 
                    'location_id' => null,
                    'city_page_id' => $city_post->ID
                ];
            }
        }
        
        // Source 2: Manual additions
        $manual = get_option('chroma_seo_manual_cities', []);
        foreach ($manual as $m) {
            if (!empty($m['city']) && !empty($m['state'])) {
                $key = sanitize_title($m['city'] . '-' . $m['state']);
                if (!isset($cities[$key])) {
                    $cities[$key] = [
                        'city' => $m['city'], 
                        'state' => $m['state'], 
                        'location_id' => null,
                        'city_page_id' => null
                    ];
                }
            }
        }
        
        // Source 3: Locations
        $locations = get_posts(['post_type' => 'location', 'posts_per_page' => -1]);
        $use_locations_as_source = empty($cities); // FALLBACK: use locations if no cities from CPT or manual
        
        foreach ($locations as $loc) {
            $city = get_post_meta($loc->ID, 'location_city', true);
            $state = get_post_meta($loc->ID, 'location_state', true);
            if ($city && $state) {
                $key = sanitize_title($city . '-' . $state);
                if (isset($cities[$key])) {
                    // Link location_id if city already exists
                    $cities[$key]['location_id'] = $loc->ID;
                } elseif ($use_locations_as_source) {
                    // Create city from location as fallback
                    $cities[$key] = [
                        'city' => $city, 
                        'state' => $state, 
                        'location_id' => $loc->ID,
                        'city_page_id' => null
                    ];
                }
            }
        }
        
        return array_values($cities);
    }

    /**
     * Handle Manual Sitemap Request
     */
    public function handle_sitemap() {
        $lang = get_query_var('chroma_combo_sitemap');
        if (!$lang) return;

        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        $combos = self::get_all_combos();
        foreach ($combos as $combo) {
            $saved_data = Chroma_Combo_Page_Data::get($combo['program']->post_name, sanitize_title($combo['city']), $combo['state']);
            $status = $saved_data['status'] ?? 'auto';
            
            if ($status === 'published' || $status === 'publish') {
                $url = $combo['url'];
                if ($lang === 'es') {
                    $url = str_replace(home_url('/'), home_url('/es/'), $url);
                }
                
                echo '<url>' . "\n";
                echo '  <loc>' . esc_url($url) . '</loc>' . "\n";
                echo '  <lastmod>' . date('c') . '</lastmod>' . "\n";
                echo '  <changefreq>weekly</changefreq>' . "\n";
                echo '  <priority>0.8</priority>' . "\n";
                echo '</url>' . "\n";
            }
        }
        
        echo '</urlset>';
        exit;
    }

    /**
     * Add to Yoast Sitemap Index
     */
    public function add_to_sitemap($sitemap_index) {
        $last_mod = date('c');
        $sitemap_index .= '<sitemap><loc>' . home_url('/sitemap-combos.xml') . '</loc><lastmod>' . $last_mod . '</lastmod></sitemap>';
        $sitemap_index .= '<sitemap><loc>' . home_url('/sitemap-combos-es.xml') . '</loc><lastmod>' . $last_mod . '</lastmod></sitemap>';
        return $sitemap_index;
    }

    
    /**
     * Add admin page
     */
    public function add_admin_page() {
        add_submenu_page(
            'chroma-seo-dashboard',
            'Auto Pages',
            'Auto Pages',
            'manage_options',
            'chroma-auto-pages',
            [$this, 'render_admin_page']
        );
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        $combos = self::get_all_combos();
        $total = count($combos);
        
        // Add combo data to each (if AI features enabled)
        foreach ($combos as &$combo) {
            if (class_exists('Chroma_Combo_Page_Data')) {
                $combo['data'] = Chroma_Combo_Page_Data::get(
                    $combo['program']->post_name,
                    sanitize_title($combo['city']),
                    $combo['state']
                );
            } else {
                $combo['data'] = ['status' => 'auto', 'ai_generated' => false, 'last_updated' => null];
            }
        }
        unset($combo);
        
        // Pagination
        $per_page_options = [25, 50, 100, 0];
        $per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 100;
        if (!in_array($per_page, $per_page_options)) $per_page = 100;
        
        $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $total_pages = $per_page > 0 ? ceil($total / $per_page) : 1;
        
        $offset = $per_page > 0 ? ($paged - 1) * $per_page : 0;
        $display_combos = $per_page > 0 ? array_slice($combos, $offset, $per_page) : $combos;
        $showing_count = count($display_combos);
        
        $base_url = admin_url('admin.php?page=chroma-auto-pages');
        $auto_publish = get_option('chroma_combo_auto_publish', false);
        $nonce = wp_create_nonce('chroma_combo_ai');
        ?>
        <div class="wrap">
            <h1>🚀 Auto-Generated Pages</h1>
            
            <!-- Stats -->
            <div class="combo-stats" style="display: flex; gap: 20px; margin: 20px 0;">
                <div class="stat-box" style="background: #fff; border: 1px solid #ccc; padding: 15px 25px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 28px; font-weight: bold; color: #0073aa;"><?php echo $total; ?></div>
                    <div style="color: #666;">Total Combos</div>
                </div>
                <div class="stat-box" style="background: #fff; border: 1px solid #ccc; padding: 15px 25px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 28px; font-weight: bold; color: #00a32a;">
                        <?php echo count(array_filter($combos, fn($c) => $c['data']['status'] === 'published')); ?>
                    </div>
                    <div style="color: #666;">Published</div>
                </div>
                <div class="stat-box" style="background: #fff; border: 1px solid #ccc; padding: 15px 25px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 28px; font-weight: bold; color: #dba617;">
                        <?php echo count(array_filter($combos, fn($c) => $c['data']['ai_generated'])); ?>
                    </div>
                    <div style="color: #666;">AI Enhanced</div>
                </div>
            </div>
            
            <h2>Program + City Combo Pages (<?php echo $total; ?>)</h2>
            
            <!-- Bulk Actions Toolbar -->
            <div class="tablenav top" style="margin-bottom: 15px;">
                <div class="alignleft actions bulkactions">
                    <select id="bulk-action-selector">
                        <option value="">Bulk Actions</option>
                        <option value="ai_generate">🤖 AI Generate Content</option>
                        <option value="ai_translate">🌐 Translate to Spanish</option>
                        <option value="set_published">📤 Set as Published</option>
                        <option value="set_draft">📝 Set as Draft</option>
                    </select>
                    <button type="button" id="do-bulk-action" class="button">Apply</button>
                    <span id="bulk-status" style="margin-left: 10px;"></span>
                </div>
                
                <div class="alignright" style="display: flex; gap: 10px; align-items: center;">
                    <form method="get" style="display: inline;">
                        <input type="hidden" name="page" value="chroma-auto-pages">
                        <label>Show: 
                            <select name="per_page" onchange="this.form.submit()">
                                <option value="25" <?php selected($per_page, 25); ?>>25</option>
                                <option value="50" <?php selected($per_page, 50); ?>>50</option>
                                <option value="100" <?php selected($per_page, 100); ?>>100</option>
                                <option value="0" <?php selected($per_page, 0); ?>>All</option>
                            </select>
                        </label>
                    </form>
                    <span>Showing <?php echo $offset + 1; ?>–<?php echo $offset + $showing_count; ?> of <?php echo $total; ?></span>
                </div>
            </div>
            
            <table class="wp-list-table widefat fixed striped" id="combo-table">
                <thead>
                    <tr>
                        <th style="width: 30px;"><input type="checkbox" id="select-all"></th>
                        <th>Page</th>
                        <th style="width: 100px;">Status</th>
                        <th style="width: 80px;">City Page</th>
                        <th style="width: 100px;">Last Updated</th>
                        <th style="width: 200px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($display_combos as $i => $combo): 
                        $program_slug = $combo['program']->post_name;
                        $city_slug = sanitize_title($combo['city']);
                        $status = $combo['data']['status'];
                        $status_color = $status === 'published' ? '#00a32a' : ($status === 'draft' ? '#dba617' : '#999');
                    ?>
                    <tr data-program="<?php echo esc_attr($program_slug); ?>" 
                        data-city="<?php echo esc_attr($city_slug); ?>" 
                        data-state="<?php echo esc_attr($combo['state']); ?>">
                        <td><input type="checkbox" class="combo-checkbox"></td>
                        <td>
                            <strong><?php echo esc_html($combo['program']->post_title . ' in ' . $combo['city'] . ', ' . $combo['state']); ?></strong>
                            <?php if ($combo['data']['ai_generated']): ?>
                                <span style="background: #e7f3ff; color: #0073aa; padding: 2px 6px; border-radius: 3px; font-size: 10px; margin-left: 5px;">AI</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span style="color: <?php echo $status_color; ?>; font-weight: 500;">
                                <?php 
                                if (class_exists('Chroma_Combo_Page_Data')) {
                                    echo esc_html(Chroma_Combo_Page_Data::get_status_label($status));
                                } else {
                                    echo esc_html(ucfirst($status));
                                }
                                ?>
                            </span>
                        </td>
                        <td>
                            <?php if (!empty($combo['city_page_id'])): ?>
                                <span style="color: #00a32a;">✓</span>
                            <?php else: ?>
                                <span style="color: #999;">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size: 11px; color: #666;">
                            <?php echo $combo['data']['last_updated'] ? date('M j, Y', $combo['data']['last_updated']) : '—'; ?>
                        </td>
                        <td>
                            <a href="<?php echo esc_url($combo['url']); ?>" target="_blank" class="button button-small">Preview</a>
                            <button type="button" class="button button-small edit-combo-btn" title="Edit">✏️ Edit</button>
                            <button type="button" class="button button-small ai-generate-btn" title="AI Generate">🤖</button>
                            <button type="button" class="button button-small ai-translate-btn" title="Translate to Spanish">🌐</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- Pagination -->
            <?php if ($per_page > 0 && $total_pages > 1): ?>
            <div class="tablenav bottom" style="margin-top: 15px;">
                <div class="tablenav-pages">
                    <span class="pagination-links">
                        <?php if ($paged > 1): ?>
                            <a class="first-page button" href="<?php echo esc_url($base_url . '&paged=1&per_page=' . $per_page); ?>">«</a>
                            <a class="prev-page button" href="<?php echo esc_url($base_url . '&paged=' . ($paged - 1) . '&per_page=' . $per_page); ?>">‹</a>
                        <?php else: ?>
                            <span class="button disabled">«</span>
                            <span class="button disabled">‹</span>
                        <?php endif; ?>
                        
                        <span class="paging-input"><?php echo $paged; ?> of <?php echo $total_pages; ?></span>
                        
                        <?php if ($paged < $total_pages): ?>
                            <a class="next-page button" href="<?php echo esc_url($base_url . '&paged=' . ($paged + 1) . '&per_page=' . $per_page); ?>">›</a>
                            <a class="last-page button" href="<?php echo esc_url($base_url . '&paged=' . $total_pages . '&per_page=' . $per_page); ?>">»</a>
                        <?php else: ?>
                            <span class="button disabled">›</span>
                            <span class="button disabled">»</span>
                        <?php endif; ?>
                    </span>
                </div>
            </div>
            <?php endif; ?>
            
            <hr style="margin: 30px 0;">
            
            <h2>📍 Near Me Pages</h2>
            <?php if (class_exists('Chroma_Near_Me_Pages') && method_exists('Chroma_Near_Me_Pages', 'get_all_pages')): ?>
                <div style="max-height: 400px; overflow-y: auto; border: 1px solid #e5e5e5; background: #fff;">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Page</th>
                                <th style="width: 150px;">Type</th>
                                <th style="width: 150px;">Location Context</th>
                                <th style="width: 100px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $near_pages = Chroma_Near_Me_Pages::get_all_pages();
                            foreach ($near_pages as $np):
                            ?>
                            <tr>
                                <td><strong><?php echo esc_html($np['title']); ?></strong></td>
                                <td><span class="badge" style="background: <?php echo $np['type'] === 'Generic' ? '#e5e5e5' : '#e7f3ff; color: #0073aa'; ?>; padding: 2px 6px; border-radius: 3px; font-size: 11px;"><?php echo esc_html($np['type']); ?></span></td>
                                <td><?php echo esc_html($np['city'] !== '—' ? $np['city'] . ', ' . $np['state'] : 'Global'); ?></td>
                                <td>
                                    <a href="<?php echo esc_url($np['url']); ?>" target="_blank" class="button button-small">Preview</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <p class="description" style="margin-top: 5px;">These pages are virtual and generated instantly for users. They are automatically included in the sitemap.</p>
            <?php endif; ?>

            <hr style="margin: 30px 0;">
            
            <h2>Settings</h2>
            <form method="post" action="options.php">
                <?php settings_fields('chroma_auto_pages'); ?>
                <table class="form-table">
                    <tr>
                        <th>Auto-Publish AI Content</th>
                        <td>
                            <label>
                                <input type="checkbox" name="chroma_combo_auto_publish" value="1" <?php checked($auto_publish); ?>>
                                Automatically publish AI-generated content (otherwise saves as draft)
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th>Manual Cities</th>
                        <td>
                            <textarea name="chroma_seo_manual_cities_raw" rows="5" class="large-text"><?php 
                                $manual = get_option('chroma_seo_manual_cities', []);
                                foreach ($manual as $m) {
                                    echo esc_html($m['city'] . ', ' . $m['state']) . "\n";
                                }
                            ?></textarea>
                            <p class="description">One city per line: City, ST</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Save Settings'); ?>
            </form>
        </div>
        
        <!-- Edit Modal -->
        <div id="combo-edit-modal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 100000;">
            <div style="background: #fff; max-width: 600px; margin: 50px auto; padding: 20px; border-radius: 8px; max-height: 80vh; overflow-y: auto;">
                <h2 id="modal-title">Edit Combo Page</h2>
                <input type="hidden" id="edit-program-slug">
                <input type="hidden" id="edit-city-slug">
                <input type="hidden" id="edit-state">
                
                <table class="form-table">
                    <tr>
                        <th>Neighborhoods</th>
                        <td><input type="text" id="edit-neighborhoods" class="regular-text" style="width: 100%;" placeholder="Downtown, North Side, South Side"></td>
                    </tr>
                    <tr>
                        <th>Major Road</th>
                        <td><input type="text" id="edit-major-road" class="regular-text" placeholder="GA-400"></td>
                    </tr>
                    <tr>
                        <th>Local Employers</th>
                        <td><input type="text" id="edit-employers" class="regular-text" style="width: 100%;" placeholder="Northside Hospital, Delta"></td>
                    </tr>
                    <tr>
                        <th>County</th>
                        <td><input type="text" id="edit-county" class="regular-text" placeholder="Forsyth"></td>
                    </tr>
                    <tr>
                        <th>Custom Intro</th>
                        <td><textarea id="edit-custom-intro" rows="3" style="width: 100%;"></textarea></td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            <select id="edit-status">
                                <option value="auto">Auto</option>
                                <option value="draft">Draft</option>
                                <option value="published">Published</option>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="button" id="save-combo-btn" class="button button-primary">Save</button>
                    <button type="button" id="ai-generate-modal-btn" class="button">🤖 AI Generate</button>
                    <button type="button" id="close-modal-btn" class="button">Cancel</button>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(function($) {
            var nonce = '<?php echo $nonce; ?>';
            
            // Select all checkbox
            $('#select-all').on('change', function() {
                $('.combo-checkbox').prop('checked', $(this).prop('checked'));
            });
            
            // Edit button
            $('.edit-combo-btn').on('click', function() {
                var $row = $(this).closest('tr');
                var program = $row.data('program');
                var city = $row.data('city');
                var state = $row.data('state');
                
                $('#edit-program-slug').val(program);
                $('#edit-city-slug').val(city);
                $('#edit-state').val(state);
                $('#modal-title').text('Edit: ' + $row.find('td:eq(1) strong').text());
                
                // Load existing data via AJAX
                $('#combo-edit-modal').fadeIn(200);
                
                // Show loading state
                $('#edit-custom-intro').val('Loading...');
                
                $.post(ajaxurl, {
                    action: 'chroma_combo_get_data',
                    nonce: nonce,
                    program_slug: program,
                    city_slug: city,
                    state: state
                }, function(response) {
                    if (response.success) {
                        var data = response.data;
                        $('#edit-custom-intro').val(data.custom_intro || '');
                        $('#edit-neighborhoods').val((data.neighborhoods || []).join(', '));
                        $('#edit-major-road').val(data.major_road || '');
                        $('#edit-employers').val(data.local_employers || '');
                        $('#edit-county').val(data.county || '');
                        $('#edit-status').val(data.status || 'auto');
                    } else {
                        $('#edit-custom-intro').val('');
                        alert('Failed to load data.');
                    }
                });
            });
            
            // AI Translate
            $('.ai-translate-btn').on('click', function() {
                var $row = $(this).closest('tr');
                var program = $row.data('program');
                var city = $row.data('city');
                var state = $row.data('state');
                var $btn = $(this);
                
                if (!confirm('Translate this page\'s content to Spanish using AI?')) return;
                
                $btn.prop('disabled', true).text('⏳');
                
                $.post(ajaxurl, {
                    action: 'chroma_combo_ai_translate',
                    nonce: nonce,
                    program_slug: program,
                    city_slug: city,
                    state: state
                }, function(response) {
                    $btn.prop('disabled', false).text('🌐');
                    if (response.success) {
                        alert('Translation complete!');
                    } else {
                        alert('Error: ' + (response.data || 'Unknown error'));
                    }
                });
            });
            
            // Close modal (only when clicking the background or cancel button)
            $('#close-modal-btn').on('click', function() {
                $('#combo-edit-modal').fadeOut(200);
            });
            $('#combo-edit-modal').on('click', function(e) {
                if (e.target === this) $(this).fadeOut(200);
            });
            // Prevent clicks inside modal content from bubbling to background
            $('#combo-edit-modal > div').on('click', function(e) {
                e.stopPropagation();
            });
            
            // Save combo data
            $('#save-combo-btn').on('click', function() {
                var $btn = $(this).prop('disabled', true).text('Saving...');
                
                $.post(ajaxurl, {
                    action: 'chroma_combo_save_data',
                    nonce: nonce,
                    program_slug: $('#edit-program-slug').val(),
                    city_slug: $('#edit-city-slug').val(),
                    state: $('#edit-state').val(),
                    neighborhoods: $('#edit-neighborhoods').val().split(',').map(s => s.trim()),
                    major_road: $('#edit-major-road').val(),
                    local_employers: $('#edit-employers').val(),
                    county: $('#edit-county').val(),
                    custom_intro: $('#edit-custom-intro').val(),
                    status: $('#edit-status').val()
                }, function(response) {
                    $btn.prop('disabled', false).text('Save');
                    if (response.success) {
                        alert('Saved!');
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                });
            });
            
            // Single AI Generate
            $('.ai-generate-btn, #ai-generate-modal-btn').on('click', function() {
                var $row = $(this).closest('tr');
                if (!$row.length) $row = null; // Modal button
                
                var program = $row ? $row.data('program') : $('#edit-program-slug').val();
                var city = $row ? $row.data('city') : $('#edit-city-slug').val();
                var state = $row ? $row.data('state') : $('#edit-state').val();
                
                var $btn = $(this).prop('disabled', true);
                var originalText = $btn.text();
                $btn.text('⏳');
                
                $.post(ajaxurl, {
                    action: 'chroma_combo_ai_generate',
                    nonce: nonce,
                    program_slug: program,
                    city_slug: city,
                    state: state
                }, function(response) {
                    $btn.prop('disabled', false).text(originalText);
                    if (response.success) {
                        alert('AI content generated!');
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                });
            });
            
            // Bulk action
            $('#do-bulk-action').on('click', function() {
                var action = $('#bulk-action-selector').val();
                if (!action) return alert('Select a bulk action');
                
                var $checked = $('.combo-checkbox:checked');
                if (!$checked.length) return alert('Select at least one item');
                
                var combos = [];
                $checked.each(function() {
                    var $row = $(this).closest('tr');
                    combos.push({
                        program_slug: $row.data('program'),
                        city_slug: $row.data('city'),
                        state: $row.data('state')
                    });
                });
                
                var $btn = $(this).prop('disabled', true).text('Processing...');
                $('#bulk-status').text('Processing ' + combos.length + ' items...');
                
                if (action === 'ai_generate' || action === 'ai_translate') {
                    var ajax_action = action === 'ai_translate' ? 'chroma_combo_ai_bulk_translate' : 'chroma_combo_ai_bulk_generate';
                    
                    $.post(ajaxurl, {
                        action: ajax_action,
                        nonce: nonce,
                        combos: combos
                    }, function(response) {
                        $btn.prop('disabled', false).text('Apply');
                        if (response.success) {
                            $('#bulk-status').text('Done: ' + response.data.success_count + ' success, ' + response.data.error_count + ' errors');
                            setTimeout(() => location.reload(), 2000);
                        } else {
                            $('#bulk-status').text('Error: ' + response.data);
                        }
                    });
                } else {
                    // Status change
                    var status = action.replace('set_', '');
                    $.post(ajaxurl, {
                        action: 'chroma_combo_bulk_status',
                        nonce: nonce,
                        combos: combos,
                        status: status
                    }, function(response) {
                        $btn.prop('disabled', false).text('Apply');
                        if (response.success) {
                            $('#bulk-status').text(response.data.message);
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            $('#bulk-status').text('Error: ' + response.data);
                        }
                    });
                }
            });
        });
        </script>
        <?php
    }
}

// Register settings
add_action('admin_init', function() {
    // Auto-publish toggle
    register_setting('chroma_auto_pages', 'chroma_combo_auto_publish');
    
    // Manual cities
    register_setting('chroma_auto_pages', 'chroma_seo_manual_cities_raw', function($raw) {
        $cities = [];
        $lines = explode("\n", $raw);
        foreach ($lines as $line) {
            $parts = array_map('trim', explode(',', $line));
            if (count($parts) >= 2) {
                $cities[] = ['city' => $parts[0], 'state' => strtoupper($parts[1])];
            }
        }
        update_option('chroma_seo_manual_cities', $cities);
        return $raw;
    });
});

new Chroma_Combo_Page_Generator();

/**
 * Custom Sitemap Provider for Combo Pages
 */
class Chroma_Combo_Sitemap_Provider extends WP_Sitemaps_Provider {
    private $lang;

    public function __construct($lang = 'en') {
        $this->lang = $lang;
        $this->name = $lang === 'es' ? 'combos-es' : 'combos';
        $this->object_type = 'custom'; 
    }

    public function get_url_list($page_num, $object_subtype = '') {
        $urls = [];
        $combos = Chroma_Combo_Page_Generator::get_all_combos();
        
        $published = [];
        foreach ($combos as $combo) {
            $saved_data = Chroma_Combo_Page_Data::get($combo['program']->post_name, sanitize_title($combo['city']), $combo['state']);
            $status = $saved_data['status'] ?? 'auto';
            if ($status === 'published' || $status === 'publish') {
                $url = $combo['url'];
                if ($this->lang === 'es') {
                    $url = str_replace(home_url('/'), home_url('/es/'), $url);
                }
                $published[] = [
                    'loc' => $url,
                    'lastmod' => date('c'),
                    'changefreq' => 'weekly',
                    'priority' => 0.8,
                ];
            }
        }
        
        $per_page = 2000;
        $offset = ($page_num - 1) * $per_page;
        return array_slice($published, $offset, $per_page);
    }

    public function get_max_num_pages($object_subtype = '') {
        $combos = Chroma_Combo_Page_Generator::get_all_combos();
        $count = 0;
        foreach ($combos as $combo) {
            $saved_data = Chroma_Combo_Page_Data::get($combo['program']->post_name, sanitize_title($combo['city']), $combo['state']);
            $status = $saved_data['status'] ?? 'auto';
            if ($status === 'published' || $status === 'publish') $count++;
        }
        return ceil($count / 2000);
    }
}

new Chroma_Combo_Page_Generator();



