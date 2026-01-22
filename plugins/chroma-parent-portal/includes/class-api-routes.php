<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Chroma_Portal_API_Routes {

	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function register_routes() {
        // Namespace: chroma-portal/v1
        
        // Login
		register_rest_route( 'chroma-portal/v1', '/login', [
			'methods'  => 'POST',
			'callback' => [ $this, 'handle_login' ],
            'permission_callback' => '__return_true', // Public
		] );
        
        // Content Fetch (Dashboard)
        register_rest_route( 'chroma-portal/v1', '/content/dashboard', [
			'methods'  => 'GET',
			'callback' => [ $this, 'get_dashboard_content' ],
            'permission_callback' => [ $this, 'check_permission' ],
		] );

        // Get Available Years
        register_rest_route( 'chroma-portal/v1', '/years', [
			'methods'  => 'GET',
			'callback' => [ $this, 'get_available_years' ],
            'permission_callback' => [ $this, 'check_permission' ],
		] );

        // Create Content (Admin Only)
        register_rest_route( 'chroma-portal/v1', '/content/create', [
			'methods'  => 'POST',
			'callback' => [ $this, 'create_content' ],
            'permission_callback' => [ $this, 'is_admin_check' ],
		] );

        // Update Content (Admin Only)
        register_rest_route( 'chroma-portal/v1', '/content/update/(?P<id>\d+)', [
			'methods'  => 'POST',
			'callback' => [ $this, 'update_content' ],
            'permission_callback' => [ $this, 'is_admin_check' ],
		] );

        // Delete Content (Admin Only)
        register_rest_route( 'chroma-portal/v1', '/content/delete/(?P<id>\d+)', [
			'methods'  => 'DELETE',
			'callback' => [ $this, 'delete_content' ],
            'permission_callback' => [ $this, 'is_admin_check' ],
		] );
	}
    
    // --- Callbacks ---
    
    public function handle_login( $request ) {
        $pin = $request->get_param( 'pin' );
        if ( empty( $pin ) ) {
            return new WP_Error( 'missing_pin', 'PIN is required', [ 'status' => 400 ] );
        }
        
        require_once CHROMA_PORTAL_PATH . 'includes/class-auth-handler.php';
        $result = Chroma_Portal_Auth::login( $pin );
        
        if ( is_wp_error( $result ) ) {
            return $result;
        }
        
        return rest_ensure_response([
            'success' => true,
            'token' => $result['token'],
            'family' => $result['family_name']
        ]);
    }
    
    public function get_dashboard_content( $request ) {
        $year = $request->get_param( 'year' ) ?: date('Y');

        $is_admin = current_user_can( 'edit_posts' );

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

        return rest_ensure_response( $data );
    }

    public function get_available_years( $request ) {
        // Get all terms from portal_year taxonomy
        $terms = get_terms([
            'taxonomy' => 'portal_year',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'DESC'
        ]);

        $years = [];
        if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
            foreach ( $terms as $term ) {
                // Extract the starting year from formats like "2026-2027" or "2026"
                if ( preg_match('/^(\d{4})/', $term->name, $matches) ) {
                    $years[] = [
                        'value' => $matches[1],  // e.g. "2026"
                        'label' => $term->name   // e.g. "2026-2027"
                    ];
                }
            }
        }

        return rest_ensure_response( $years );
    }
    
    public function create_content( $request ) {
        // Admin Upload Logic
        $title = $request->get_param('title');
        $post_type = $request->get_param('post_type'); // e.g. cp_lesson_plan
        $file_id = $request->get_param('file_id');
        $year = $request->get_param('year');
        $month = $request->get_param('month'); // ID or name? assuming name implies term lookup or create
        
        // Basic Validation
        if ( ! in_array($post_type, ['cp_lesson_plan', 'cp_meal_plan', 'cp_resource', 'cp_form', 'cp_announcement', 'cp_event']) ) {
             return new WP_Error( 'invalid_type', 'Invalid Post Type', [ 'status' => 400 ] );
        }
        
        $post_id = wp_insert_post([
            'post_title' => $title,
            'post_type' => $post_type,
            'post_status' => 'publish'
        ]);
        
        if ( is_wp_error( $post_id ) ) {
            return $post_id;
        }
        
        // Meta
        if ( $file_id ) {
            update_post_meta( $post_id, '_cp_pdf_file_id', $file_id );
        }
        
        // Terms (Year)
        if ( $year ) {
            wp_set_object_terms( $post_id, $year, 'portal_year' );
        }
        
        // Terms (Month / Quarter / Category)
        // Simplified mapping for now
        if ( $month && $post_type === 'cp_lesson_plan' ) {
             wp_set_object_terms( $post_id, $month, 'portal_month' );
        }
        
        return rest_ensure_response([
            'success' => true,
            'id' => $post_id,
            'message' => 'Content Created'
        ]);
    }

    public function update_content( $request ) {
        $post_id = $request->get_param('id');
        $title = $request->get_param('title');
        $file_id = $request->get_param('file_id');
        $year = $request->get_param('year');
        $month = $request->get_param('month');

        if ( ! get_post( $post_id ) ) {
            return new WP_Error( 'not_found', 'Content not found', [ 'status' => 404 ] );
        }

        $update_args = ['ID' => $post_id];
        if ($title) $update_args['post_title'] = $title;
        wp_update_post($update_args);

        if ($file_id) update_post_meta( $post_id, '_cp_pdf_file_id', $file_id );
        if ($year) wp_set_object_terms( $post_id, $year, 'portal_year' );
        
        $post_type = get_post_type($post_id);
        if ($month) {
            $tax = ($post_type === 'cp_meal_plan') ? 'portal_quarter' : (($post_type === 'cp_lesson_plan') ? 'portal_month' : 'portal_category');
            wp_set_object_terms( $post_id, $month, $tax );
        }

        return rest_ensure_response(['success' => true]);
    }

    public function delete_content( $request ) {
        $post_id = $request->get_param('id');
        if ( ! get_post( $post_id ) ) {
            return new WP_Error( 'not_found', 'Content not found', [ 'status' => 404 ] );
        }
        wp_delete_post( $post_id, true );
        return rest_ensure_response(['success' => true]);
    }
    
    // --- Helpers ---
    
    private function fetch_posts( $type, $year ) {
        // Try multiple year formats: "2026", "2026-2027", "2026-27"
        $year_int = intval($year);
        $next_year = $year_int + 1;

        $year_variations = [
            strval($year_int),                              // "2026"
            $year_int . '-' . $next_year,                   // "2026-2027"
            $year_int . '-' . substr(strval($next_year), -2)  // "2026-27"
        ];

        $args = [
            'post_type' => $type,
            'posts_per_page' => -1,
            'tax_query' => [
                [
                    'taxonomy' => 'portal_year',
                    'field' => 'name',
                    'terms' => $year_variations,
                    'operator' => 'IN'
                ]
            ]
        ];

        // Debug logging
        error_log("Chroma Portal: Fetching $type for year variations: " . implode(', ', $year_variations));

        $posts = get_posts( $args );
        error_log("Chroma Portal: Found " . count($posts) . " posts for $type");

        $results = [];
        
        foreach ( $posts as $p ) {
            $file_id = get_post_meta( $p->ID, '_cp_pdf_file_id', true );
            $file_url = $file_id ? wp_get_attachment_url( $file_id ) : null;
            
            // Get Month/Quarter/Cat
            $terms = wp_get_post_terms( $p->ID, ['portal_month', 'portal_quarter', 'portal_category'] );
            $term_name = !empty($terms) ? $terms[0]->name : ''; // First term
            
            $results[] = [
                'id' => $p->ID,
                'title' => $p->post_title,
                'content' => $p->post_content, // For Announcements/Events
                'pdf_url' => $file_url,
                'pdf_id' => $file_id, // For Admin Edit
                'group' => $term_name, // "January" or "Q1" or "Policy"
                'priority' => get_post_meta( $p->ID, '_cp_priority', true ),
                'event_date' => get_post_meta( $p->ID, '_cp_event_date', true ),
                'can_edit' => current_user_can( 'edit_post', $p->ID )
            ];
        }
        return $results;
    }
    
    // --- Permissions ---
    
    public function check_permission( $request ) {
        // 1. Is Admin?
        if ( current_user_can( 'edit_posts' ) ) {
            return true;
        }
        
        // 2. Has Token?
        $token = $request->get_header( 'X-Portal-Token' );
        if ( ! $token ) {
             return new WP_Error( 'rest_forbidden', 'Authentication Required', [ 'status' => 403 ] );
        }
        
        require_once CHROMA_PORTAL_PATH . 'includes/class-auth-handler.php';
        if ( ! Chroma_Portal_Auth::validate_token( $token ) ) {
             return new WP_Error( 'rest_forbidden', 'Invalid Token', [ 'status' => 403 ] );
        }
        
        return true;
    }
    
    public function is_admin_check() {
        return current_user_can( 'edit_posts' );
    }

}

new Chroma_Portal_API_Routes();
