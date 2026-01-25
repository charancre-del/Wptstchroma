<?php
if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Portal_API_Routes
{

    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes()
    {
        // Namespace: chroma-portal/v1

        // Login
        register_rest_route('chroma-portal/v1', '/login', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_login'],
            'permission_callback' => '__return_true', // Public
        ]);

        // Content Fetch (Dashboard)
        register_rest_route('chroma-portal/v1', '/content/dashboard', [
            'methods' => 'GET',
            'callback' => [$this, 'get_dashboard_content'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        // Get Available Years
        register_rest_route('chroma-portal/v1', '/years', [
            'methods' => 'GET',
            'callback' => [$this, 'get_available_years'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        // Get Taxonomy Terms (months, quarters, categories)
        register_rest_route('chroma-portal/v1', '/taxonomy/(?P<taxonomy>[a-z_]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_taxonomy_terms'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        // Create Content (Admin Only)
        register_rest_route('chroma-portal/v1', '/content/create', [
            'methods' => 'POST',
            'callback' => [$this, 'create_content'],
            'permission_callback' => [$this, 'is_admin_check'],
        ]);

        // Update Content (Admin Only)
        register_rest_route('chroma-portal/v1', '/content/update/(?P<id>\d+)', [
            'methods' => 'POST',
            'callback' => [$this, 'update_content'],
            'permission_callback' => [$this, 'is_admin_check'],
        ]);

        // Delete Content (Admin Only)
        register_rest_route('chroma-portal/v1', '/content/delete/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [$this, 'delete_content'],
            'permission_callback' => [$this, 'is_admin_check'],
        ]);

        // SYSTEM CHECK (Debug)
        register_rest_route('chroma-portal/v1', '/system-check', [
            'methods' => 'GET',
            'callback' => [$this, 'debug_system_check'],
            'permission_callback' => '__return_true'
        ]);
    }

    public function debug_system_check($request)
    {
        $year = $request->get_param('year') ?: date('Y');
        $year_int = intval($year);
        $year_str = strval($year_int);

        // 1. Get All Terms
        $all_terms = get_terms(['taxonomy' => 'portal_year', 'hide_empty' => false]);

        // 2. Filter Terms (PHP)
        $matched_terms = [];
        $matched_ids = [];
        if (!is_wp_error($all_terms)) {
            foreach ($all_terms as $t) {
                if (strpos($t->name, $year_str) !== false) {
                    $matched_terms[] = $t->name;
                    $matched_ids[] = $t->term_id;
                }
            }
        }

        // 3. Query Posts (Lesson Plans)
        $posts_found = [];
        if (!empty($matched_ids)) {
            $posts = get_posts([
                'post_type' => 'cp_lesson_plan',
                'posts_per_page' => 5, // Limit 5
                'post_status' => 'any', // Check all statuses
                'tax_query' => [
                    [
                        'taxonomy' => 'portal_year',
                        'field' => 'term_id',
                        'terms' => $matched_ids,
                        'operator' => 'IN'
                    ]
                ]
            ]);
            foreach ($posts as $p) {
                $posts_found[] = [
                    'id' => $p->ID,
                    'title' => $p->post_title,
                    'status' => $p->post_status,
                    'terms' => wp_get_post_terms($p->ID, 'portal_year', ['fields' => 'names']),
                    'months' => wp_get_post_terms($p->ID, 'portal_month', ['fields' => 'names'])
                ];
            }
        }

        return [
            'status' => 'ok',
            'input_year' => $year,
            'all_terms_count' => count($all_terms),
            'matched_terms' => $matched_terms,
            'matched_ids' => $matched_ids,
            'posts_found_count' => count($posts_found),
            'sample_posts' => $posts_found
        ];
    }

    // --- Callbacks ---

    public function handle_login($request)
    {
        $pin = $request->get_param('pin');
        if (empty($pin)) {
            return new WP_Error('missing_pin', 'PIN is required', ['status' => 400]);
        }

        require_once CHROMA_PORTAL_PATH . 'includes/class-auth-handler.php';
        $result = Chroma_Portal_Auth::login($pin);

        if (is_wp_error($result)) {
            return $result;
        }

        return rest_ensure_response([
            'success' => true,
            'token' => $result['token'],
            'family' => $result['family_name']
        ]);
    }

    public function get_dashboard_content($request)
    {
        $year = $request->get_param('year') ?: date('Y');

        $is_admin = current_user_can('edit_posts');

        // Fetch all categories
        $data = [
            'is_admin' => $is_admin,
            'announcements' => $this->fetch_posts('cp_announcement', $year),
            'lesson_plans' => $this->fetch_posts('cp_lesson_plan', $year),
            'meal_plans' => $this->fetch_posts('cp_meal_plan', $year),
            'resources' => $this->fetch_posts('cp_resource', $year),
            'forms' => $this->fetch_posts('cp_form', $year),
            'events' => $this->fetch_posts('cp_event', $year),
        ];

        return rest_ensure_response($data);
    }

    public function get_available_years($request)
    {
        // Get all terms from portal_year taxonomy
        $terms = get_terms([
            'taxonomy' => 'portal_year',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'DESC'
        ]);

        $years = [];
        if (!empty($terms) && !is_wp_error($terms)) {
            foreach ($terms as $term) {
                // Extract the starting year from formats like "2026-2027" or "2026"
                if (preg_match('/^(\d{4})/', $term->name, $matches)) {
                    $years[] = [
                        'value' => $matches[1],  // e.g. "2026"
                        'label' => $term->name   // e.g. "2026-2027"
                    ];
                }
            }
        }

        return rest_ensure_response($years);
    }

    public function get_taxonomy_terms($request)
    {
        $taxonomy = $request->get_param('taxonomy');

        // Validate taxonomy
        $allowed_taxonomies = ['portal_month', 'portal_quarter', 'portal_category', 'portal_school'];
        if (!in_array($taxonomy, $allowed_taxonomies)) {
            return new WP_Error('invalid_taxonomy', 'Invalid taxonomy', ['status' => 400]);
        }

        // Get terms
        $terms = get_terms([
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ]);

        $result = [];
        if (!empty($terms) && !is_wp_error($terms)) {
            foreach ($terms as $term) {
                $result[] = $term->name;
            }
        }

        return rest_ensure_response($result);
    }

    public function create_content($request)
    {
        try {
            $title = sanitize_text_field($request->get_param('title'));
            $post_type = sanitize_text_field($request->get_param('post_type'));
            $file_id = absint($request->get_param('file_id'));
            $year = sanitize_text_field($request->get_param('year'));
            $month = sanitize_text_field($request->get_param('month'));
            $school = sanitize_text_field($request->get_param('school'));

            if (!in_array($post_type, ['cp_lesson_plan', 'cp_meal_plan', 'cp_resource', 'cp_form', 'cp_announcement', 'cp_event'])) {
                return new WP_Error('invalid_type', 'Invalid Post Type', ['status' => 400]);
            }

            if (empty($title)) {
                return new WP_Error('missing_title', 'Title is required', ['status' => 400]);
            }

            $post_id = wp_insert_post([
                'post_title' => $title,
                'post_type' => $post_type,
                'post_status' => 'publish'
            ], true);

            if (is_wp_error($post_id)) {
                return $post_id;
            }

            if ($file_id) {
                update_post_meta($post_id, '_cp_pdf_file_id', $file_id);
            }

            // Year Term Matching
            if ($year) {
                $year_str = '';
                if (preg_match('/(\d{4})/', (string) $year, $matches)) {
                    $year_str = $matches[1];
                }

                if ($year_str) {
                    $all_terms = get_terms(['taxonomy' => 'portal_year', 'hide_empty' => false]);
                    $matched_term = null;
                    if (!is_wp_error($all_terms) && !empty($all_terms)) {
                        foreach ($all_terms as $t) {
                            if (strpos($t->name, $year_str) !== false) {
                                $matched_term = $t->name;
                                break;
                            }
                        }
                    }
                    wp_set_object_terms($post_id, $matched_term ?: $year, 'portal_year');
                }
            }

            if ($school) {
                wp_set_object_terms($post_id, $school, 'portal_school');
            }

            if ($month) {
                switch ($post_type) {
                    case 'cp_lesson_plan':
                    case 'cp_announcement':
                    case 'cp_event':
                        wp_set_object_terms($post_id, $month, 'portal_month');
                        break;
                    case 'cp_meal_plan':
                        wp_set_object_terms($post_id, $month, 'portal_quarter');
                        break;
                    case 'cp_resource':
                    case 'cp_form':
                        wp_set_object_terms($post_id, $month, 'portal_category');
                        break;
                }
            }

            return rest_ensure_response(['success' => true, 'id' => $post_id]);
        } catch (Exception $e) {
            error_log('Parent Portal Content Creation Error: ' . $e->getMessage());
            return new WP_Error('create_failed', 'Failed to create content', ['status' => 500]);
        }
    }

    public function update_content($request)
    {
        try {
            $post_id = absint($request->get_param('id'));
            $title = sanitize_text_field($request->get_param('title'));
            $file_id = absint($request->get_param('file_id'));
            $year = sanitize_text_field($request->get_param('year'));
            $month = sanitize_text_field($request->get_param('month'));
            $school = sanitize_text_field($request->get_param('school'));

            if (!get_post($post_id)) {
                return new WP_Error('not_found', 'Content not found', ['status' => 404]);
            }

            $update_args = ['ID' => $post_id];
            if ($title) {
                $update_args['post_title'] = $title;
            }

            $updated = wp_update_post($update_args, true);
            if (is_wp_error($updated)) {
                return $updated;
            }

            if ($file_id) {
                update_post_meta($post_id, '_cp_pdf_file_id', $file_id);
            }

            // Year Term Matching
            if ($year) {
                $year_str = '';
                if (preg_match('/(\d{4})/', (string) $year, $matches)) {
                    $year_str = $matches[1];
                }

                if ($year_str) {
                    $all_terms = get_terms(['taxonomy' => 'portal_year', 'hide_empty' => false]);
                    $matched_term = null;
                    if (!is_wp_error($all_terms) && !empty($all_terms)) {
                        foreach ($all_terms as $t) {
                            if (strpos($t->name, $year_str) !== false) {
                                $matched_term = $t->name;
                                break;
                            }
                        }
                    }
                    wp_set_object_terms($post_id, $matched_term ?: $year, 'portal_year');
                }
            }

            $post_type = get_post_type($post_id);
            if ($school) {
                wp_set_object_terms($post_id, $school, 'portal_school');
            }
            if ($month) {
                $tax = 'portal_category';
                if ($post_type === 'cp_meal_plan') {
                    $tax = 'portal_quarter';
                } elseif (in_array($post_type, ['cp_lesson_plan', 'cp_announcement', 'cp_event'])) {
                    $tax = 'portal_month';
                }
                wp_set_object_terms($post_id, $month, $tax);
            }

            return rest_ensure_response(['success' => true]);
        } catch (Exception $e) {
            error_log('Parent Portal Content Update Error: ' . $e->getMessage());
            return new WP_Error('update_failed', 'Failed to update content', ['status' => 500]);
        }
    }

    public function delete_content($request)
    {
        try {
            $post_id = absint($request->get_param('id'));
            if (!get_post($post_id)) {
                return new WP_Error('not_found', 'Content not found', ['status' => 404]);
            }
            $result = wp_delete_post($post_id, true);
            if (!$result) {
                return new WP_Error('delete_failed', 'Failed to delete content', ['status' => 500]);
            }
            return rest_ensure_response(['success' => true]);
        } catch (Exception $e) {
            error_log('Parent Portal Content Deletion Error: ' . $e->getMessage());
            return new WP_Error('delete_failed', 'Internal Server Error', ['status' => 500]);
        }
    }

    // --- Helpers ---



    private function fetch_posts($type, $year)
    {
        try {
            // Robust Year Parsing
            $year_str = '';
            if (preg_match('/(\d{4})/', (string) $year, $matches)) {
                $year_str = $matches[1];
            } else {
                $year_str = date('Y');
            }

            $all_terms = get_terms([
                'taxonomy' => 'portal_year',
                'hide_empty' => false
            ]);

            $term_ids = [];
            if (!is_wp_error($all_terms) && !empty($all_terms)) {
                foreach ($all_terms as $t) {
                    if (strpos($t->name, $year_str) !== false) {
                        $term_ids[] = (int) $t->term_id;
                    }
                }
            }

            $posts = [];

            if (!empty($term_ids)) {
                $args = [
                    'post_type' => $type,
                    'posts_per_page' => -1,
                    'post_status' => 'publish',
                    'tax_query' => [
                        [
                            'taxonomy' => 'portal_year',
                            'field' => 'term_id',
                            'terms' => $term_ids,
                            'operator' => 'IN'
                        ]
                    ],
                    'suppress_filters' => true
                ];
                $posts = get_posts($args);
            }

            // Fallback: Manual Filter
            if (empty($posts) && !empty($term_ids)) {
                $candidates = get_posts([
                    'post_type' => $type,
                    'posts_per_page' => -1,
                    'post_status' => 'publish',
                    'suppress_filters' => true
                ]);

                foreach ($candidates as $cand) {
                    $cand_terms = wp_get_post_terms($cand->ID, 'portal_year', ['fields' => 'ids']);
                    if (!is_wp_error($cand_terms) && !empty($cand_terms)) {
                        if (count(array_intersect($cand_terms, $term_ids)) > 0) {
                            $posts[] = $cand;
                        }
                    }
                }
            }

            $results = [];

            foreach ($posts as $p) {
                $file_id = get_post_meta($p->ID, '_cp_pdf_file_id', true);
                $file_url = $file_id ? wp_get_attachment_url($file_id) : null;

                $terms = wp_get_post_terms($p->ID, ['portal_month', 'portal_quarter', 'portal_category']);
                $term_name = (!is_wp_error($terms) && !empty($terms)) ? $terms[0]->name : '';

                $school_terms = wp_get_post_terms($p->ID, 'portal_school');
                $school_name = (!is_wp_error($school_terms) && !empty($school_terms)) ? $school_terms[0]->name : '';

                $results[] = [
                    'id' => $p->ID,
                    'title' => $p->post_title,
                    'content' => $p->post_content,
                    'pdf_url' => $file_url,
                    'pdf_id' => $file_id,
                    'group' => $term_name,
                    'school' => $school_name,
                    'priority' => get_post_meta($p->ID, '_cp_priority', true),
                    'event_date' => get_post_meta($p->ID, '_cp_event_date', true),
                    'can_edit' => current_user_can('edit_post', $p->ID)
                ];
            }

            return $results;
        } catch (Exception $e) {
            error_log('Parent Portal Fetch Posts Helper Error: ' . $e->getMessage());
            return [];
        }
    }

    // --- Permissions ---

    public function check_permission($request)
    {
        // 1. Is Admin?
        if (current_user_can('edit_posts')) {
            return true;
        }

        // 2. Has Token?
        $token = $request->get_header('X-Portal-Token');
        if (!$token) {
            return new WP_Error('rest_forbidden', 'Authentication Required', ['status' => 403]);
        }

        require_once CHROMA_PORTAL_PATH . 'includes/class-auth-handler.php';
        if (!Chroma_Portal_Auth::validate_token($token)) {
            return new WP_Error('rest_forbidden', 'Invalid Token', ['status' => 403]);
        }

        return true;

        return true;
    }

    public function is_admin_check()
    {
        return current_user_can('edit_posts');
    }

}

new Chroma_Portal_API_Routes();
