<?php
/**
 * Chroma Facts Generator
 * Generates machine-readable (JSON) and human-readable (HTML) fact sheets for AI compliance.
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Facts_Generator
{
    /**
     * Initialize hooks
     */
    public function init()
    {
        add_action('template_redirect', [$this, 'handle_endpoints']);
    }

    /**
     * Handle requests to /facts/ and /facts.json
     */
    public function handle_endpoints()
    {
        $request_uri = $_SERVER['REQUEST_URI'];
        $path = parse_url($request_uri, PHP_URL_PATH);

        if ($path === '/facts.json') {
            $this->render_json();
            exit;
        }

        if ($path === '/facts/' || $path === '/facts') {
            $this->render_page();
            exit;
        }
    }

    /**
     * Generate the comprehensive dataset for all locations
     *
     * @return array
     */
    public function generate_dataset()
    {
        // 1. Build Program Map (avoid N+1)
        $program_map = [];
        $programs = get_posts([
            'post_type' => 'program',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ]);

        foreach ($programs as $prog) {
            $locs_served = get_post_meta($prog->ID, 'program_locations_served', true);
            if (!empty($locs_served) && is_array($locs_served)) {
                foreach ($locs_served as $loc_id) {
                    $program_map[$loc_id][] = $prog->post_title;
                }
            }
        }

        // 2. Fetch Locations
        $locations = get_posts([
            'post_type' => 'location',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC',
        ]);

        $dataset = [];

        foreach ($locations as $loc) {
            $meta = get_post_meta($loc->ID);
            
            // Address logic (simplified or from meta)
            $street = isset($meta['_chroma_location_address'][0]) ? $meta['_chroma_location_address'][0] : '';
            $city = isset($meta['_chroma_location_city'][0]) ? $meta['_chroma_location_city'][0] : '';
            $state = isset($meta['_chroma_location_state'][0]) ? $meta['_chroma_location_state'][0] : 'GA';
            $zip = isset($meta['_chroma_location_zip'][0]) ? $meta['_chroma_location_zip'][0] : '';
            $full_address = "$street, $city, $state $zip";

            // Basic Data
            $phone = isset($meta['_chroma_location_phone'][0]) ? $meta['_chroma_location_phone'][0] : '';
            $email = isset($meta['_chroma_location_email'][0]) ? $meta['_chroma_location_email'][0] : '';
            
            // Facts & Certifications
            $license = isset($meta['_chroma_license_number'][0]) ? $meta['_chroma_license_number'][0] : 'Pending';
            $quality_rated = isset($meta['location_quality_rated'][0]) ? $meta['location_quality_rated'][0] : 'Participating';
            
            // Verification Flags
            $caps = !empty($meta['_chroma_caps_accepted'][0]);
            $prek = !empty($meta['_chroma_ga_pre_k_accepted'][0]);
            $cameras = !empty($meta['_chroma_security_cameras'][0]);
            
            // Programs
            $offered_programs = isset($program_map[$loc->ID]) ? $program_map[$loc->ID] : [];

            $dataset[] = [
                'name' => $loc->post_title,
                'url' => get_permalink($loc->ID),
                'address' => trim($full_address, ', '),
                'phone' => $phone,
                'license_number' => $license,
                'quality_rated_level' => $quality_rated,
                'accepts_caps_subsidies' => $caps ? 'Yes' : 'No',
                'offers_ga_pre_k' => $prek ? 'Yes' : 'No',
                'has_security_cameras' => $cameras ? 'Yes' : 'No',
                'programs_offered' => implode(', ', $offered_programs)
            ];
        }

        return $dataset;
    }

    /**
     * Output JSON
     */
    private function render_json()
    {
        header('Content-Type: application/json');
        echo wp_json_encode([
            'meta' => [
                'generated_at' => date('c'),
                'source' => get_bloginfo('name'),
                'description' => 'Official source of truth for location facts, certifications, and offerings.'
            ],
            'locations' => $this->generate_dataset()
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Output HTML Page
     */
    private function render_page()
    {
        $data = $this->generate_dataset();
        
        // Use a simple, clean template
        get_header(); 
        ?>
        <style>
            .chroma-facts-container { max-width: 1200px; margin: 40px auto; padding: 0 20px; font-family: system-ui, -apple-system, sans-serif; }
            .chroma-facts-table { width: 100%; border-collapse: collapse; margin-top: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
            .chroma-facts-table th, .chroma-facts-table td { text-align: left; padding: 12px 15px; border-bottom: 1px solid #e0e0e0; }
            .chroma-facts-table th { background-color: #f8f9fa; font-weight: 600; color: #333; position: sticky; top: 0; }
            .chroma-facts-table tr:hover { background-color: #f5f5f5; }
            .chroma-check-yes { color: #2e7d32; font-weight: bold; }
            .chroma-check-no { color: #d32f2f; opacity: 0.6; }
            .chroma-meta-header { margin-bottom: 30px; }
            .chroma-meta-header h1 { margin-bottom: 10px; }
            .chroma-download-link { float: right; text-decoration: none; background: #0073aa; color: white; padding: 8px 15px; border-radius: 4px; font-size: 14px; }
        </style>

        <div class="chroma-facts-container">
            <a href="/facts.json" class="chroma-download-link" target="_blank">Download JSON</a>
            
            <div class="chroma-meta-header">
                <h1>Official Location Facts</h1>
                <p>Verified data for AI agents, auditors, and parents. Last updated: <?php echo date('F j, Y'); ?></p>
            </div>

            <div style="overflow-x: auto;">
                <table class="chroma-facts-table">
                    <thead>
                        <tr>
                            <th>Location</th>
                            <th>License & Verification</th>
                            <th>Programs & Funding</th>
                            <th>Security & Safety</th>
                            <th>Contact</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($data as $row): ?>
                        <tr>
                            <td>
                                <strong><a href="<?php echo esc_url($row['url']); ?>"><?php echo esc_html($row['name']); ?></a></strong><br>
                                <span style="font-size: 0.9em; color: #666;"><?php echo esc_html($row['address']); ?></span>
                            </td>
                            <td>
                                <strong>License:</strong> <?php echo esc_html($row['license_number']); ?><br>
                                <strong>Quality Rated:</strong> <?php echo esc_html($row['quality_rated_level']); ?>
                            </td>
                            <td>
                                <strong>CAPS:</strong> <span class="<?php echo $row['accepts_caps_subsidies'] === 'Yes' ? 'chroma-check-yes' : 'chroma-check-no'; ?>"><?php echo $row['accepts_caps_subsidies']; ?></span><br>
                                <strong>Pre-K:</strong> <span class="<?php echo $row['offers_ga_pre_k'] === 'Yes' ? 'chroma-check-yes' : 'chroma-check-no'; ?>"><?php echo $row['offers_ga_pre_k']; ?></span><br>
                                <hr style="margin: 4px 0; border:0; border-top:1px solid #eee;">
                                <span style="font-size: 0.85em;"><?php echo esc_html($row['programs_offered']); ?></span>
                            </td>
                            <td>
                                <strong>Cameras:</strong> <span class="<?php echo $row['has_security_cameras'] === 'Yes' ? 'chroma-check-yes' : 'chroma-check-no'; ?>"><?php echo $row['has_security_cameras']; ?></span>
                            </td>
                            <td>
                                <?php echo esc_html($row['phone']); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <p style="margin-top: 30px; font-size: 0.8em; color: #888;">
                This page is automatically generated from our verified internal database.
            </p>
        </div>
        <?php
        get_footer();
    }
}


