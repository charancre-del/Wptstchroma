<?php
/**
 * Template Name: Generate City Pages
 * Description: Automates the creation of SEO-optimized City pages by saving metadata for the single-city.php template.
 */

// Ensure WordPress is loaded
if (!defined('ABSPATH')) {
    require_once('../../../wp-load.php');
}

// Check permissions
if (!current_user_can('manage_options')) {
    wp_die('You do not have sufficient permissions to access this page.');
}

get_header();

// -------------------------------------------------------------------------
// 1. HARDCODED SUBDIVISIONS / NEIGHBORHOODS & COUNTIES
// -------------------------------------------------------------------------
// Structure: County Name => [ City Name => [Neighborhoods] ]
$county_data = array(
    'Cherokee' => array(
        'Canton' => ['River Green', 'Bridgemill', 'Great Sky', 'Harmony on the Lakes', 'Woodmont', 'Towne Mill', 'Laurel Canyon', 'Governors Preserve', 'Hickory Flat', 'Sixes Road'],
        'Woodstock' => ['Towne Lake', 'Eagle Watch', 'Bradshaw Farm', 'Woodlands', 'Wyngate', 'Downtown Woodstock', 'Kingsgate', 'Brookshire', 'South on Main', 'Deer Run'],
        'Ballground' => ['Woodhaven Bend', 'Estates at Sharp Mountain', 'Mountain Brooke', 'Creekside Estates', 'River Rock', 'Lantern Walk', 'Hawks Ridge', 'Amber Lake'],
        'Waleska' => ['Lake Arrowhead', 'Reinhardt Area', 'Sawyers Farm', 'Arrowhead Forest', 'Cagle Shoals', 'Brookwood'],
        'Holly Springs' => ['Harmony on the Lakes', 'Holly Springs Station', 'Edmondson Lane', 'Toonigh'],
    ),
    'Cobb' => array(
        'Marietta' => ['Indian Hills', 'Whitlock Heights', 'West Hampton', 'Windsor Oaks', 'Chimney Springs', 'Somerset', 'Oakton', 'East Cobb', 'Marietta Square'],
        'East Cobb' => ['Indian Hills', 'Atlanta Country Club', 'Chimney Springs', 'EastHampton', 'Walton Reserve', 'Paper Mill', 'Chestnut Creek', 'Highland Pointe'],
        'West Cobb' => ['Oregon Park', 'Lost Mountain', 'Mud Creek', 'Ward Creek'],
        'Kennesaw' => ['Legacy Park', 'Summerbrooke', 'Pinetree Country Club', 'Barrett Green', 'Ridenour', 'Arden Lake', 'Winchester Forest'],
        'Acworth' => ['Bentwater', 'Brookstone', 'Governors Towne Club', 'Seven Hills', 'Parkside at Mason Mill'],
        'Powder Springs' => ['Echo Mill', 'Oakleigh', 'Kyle Farm', 'Silverbrooke', 'Country Walk', 'Broadlands', 'Lost Mountain'],
        'Austell' => ['Sanders Park', 'Cureton Woods', 'Clayton Crossing', 'Sweetwater', 'Lithia Springs'],
        'Mableton' => ['Vinings Estates', 'Providence', 'Legacy at the River Line', 'Cobblestone Ridge'],
        'Smyrna' => ['Williams Park', 'Market Village', 'Vinings', 'King Springs'],
    ),
    'Fulton' => array(
        'Alpharetta' => ['Windward', 'Avalon', 'Glen Abbey', 'Crabapple', 'The Manor', 'Brookshade', 'Park Brooke', 'Kimball Bridge'],
        'Roswell' => ['Horseshoe Bend', 'Martin\'s Landing', 'Willow Springs', 'Edenwilde', 'Historic Roswell', 'Crabapple', 'Wexford'],
        'Johns Creek' => ['St Ives', 'Seven Oaks', 'Medlock Bridge', 'Sugar Mill', 'Wellington', 'Falls of Autry Mill'],
        'Milton' => ['White Columns', 'The Manor', 'Crooked Creek', 'Crabapple', 'Blue Valley', 'Alpharetta Woods'],
        'Fairburn' => ['Durham Lakes', 'Renaissance', 'Cedar Grove', 'South Fulton'],
        'Palmetto' => ['Serenbe', 'Chattahoochee Hills', 'Rico'],
        'Union City' => ['South Fulton', 'Oakley Township', 'Shannon'],
    ),
    'Gwinnett' => array(
        'Lawrenceville' => ['Sugarloaf', 'River Stone', 'Edgewater', 'Flowers Crossing', 'Chandler Pond', 'Tribble Mill', 'Brookwood'],
        'Duluth' => ['Sugarloaf Country Club', 'Sweet Bottom Plantation', 'River Plantation', 'St Marlo', 'Berkeley Lake'],
        'Norcross' => ['Peachtree Corners', 'Amberfield', 'Neely Farm', 'Spalding Corners', 'Historic Norcross'],
        'Snellville' => ['Brookwood Manor', 'Summit Chase', 'Bright Water', 'Montclair', 'Norris Lake'],
        'Lilburn' => ['Mountain Park', 'Killian Hill', 'Camp Creek', 'Parkview', 'Evermore'],
        'Suwanee' => ['Rivermoore Park', 'Edinburgh', 'MorningView', 'Old Suwanee'],
        'Buford' => ['Hamilton Mill', 'Lake Lanier', 'Buford City', 'Mall of Georgia Area'],
        'Dacula' => ['Hamilton Mill', 'Apalachee Farms', 'Reunion', 'Dacula City'],
    ),
    'Henry' => array(
        'McDonough' => ['Lake Dow', 'Eagles Landing', 'Ola', 'Union Grove', 'City Square', 'Kelleytown'],
        'Stockbridge' => ['Eagles Landing', 'Monarch Village', 'Windy Hill', 'Spivey', 'Flippen'],
        'Locust Grove' => ['Heron Bay', 'Luella', 'Locust Grove Station'],
        'Hampton' => ['Crystal Lake', 'Atlanta Motor Speedway Area', 'Lovejoy'],
    ),
    'Clayton' => array(
        'Jonesboro' => ['Lake Spivey', 'Mundy\'s Mill', 'Tara', 'Irondale'],
        'Ellenwood' => ['Panola Mountain', 'Fairview', 'Cedar Grove'],
        'Rex' => ['Rex Ridge', 'Homestead', 'Stagecoach'],
        'Morrow' => ['Lake City', 'Reynolds Road', 'Southlake'],
        'Lovejoy' => ['Lovejoy Station', 'Panhandle', 'Hastings'],
    ),
    'Fayette' => array(
        'Fayetteville' => ['Whitewater', 'Trilith', 'The Canoe Club', 'Starr\'s Mill', 'Redwine'],
        'Peachtree City' => ['Kedron', 'Braelinn', 'Glenloch', 'Wilksmoor', 'Aberdeen'],
        'Tyrone' => ['Southampton', 'River Crest', 'Windcastle', 'Berry Hill'],
    ),
    'Coweta' => array(
        'Newnan' => ['Summergrove', 'White Oak', 'Lake Redwine', 'Arbor Springs', 'Madras'],
    ),
    'Spalding' => array(
        'Griffin' => ['Sun City Peachtree', 'Cowan Road', 'Downtown Griffin'],
    ),
    'Hall' => array(
        'Gainesville' => ['Chattahoochee Country Club', 'Mundy Mill', 'Cresswind', 'Lake Lanier', 'Sardis'],
        'Murrayville' => ['Bark Camp', 'Yellow Creek', 'Wahoo'],
        'Clermont' => ['Wauka Mountain', 'Cleveland Highway', 'Mossy Creek'],
        'North Hall' => ['Mount Vernon', 'Clermont', 'Wauka'],
    ),
    'Dawson' => array(
        'Dawsonville' => ['Gold Creek', 'Chestatee', 'Dawson Forest', 'Outlet Mall Area'],
    ),
    'Lumpkin' => array(
        'Dahlonega' => ['Achasta', 'Downtown Dahlonega', 'Camp Glisson', 'Yahoola'],
    ),
    'Pickens' => array(
        'Jasper' => ['Big Canoe', 'Bent Tree', 'Grandview', 'Talking Rock'],
    ),
    'Forsyth' => array(
        'Cumming' => ['Vickery', 'Polo Fields', 'Windermere', 'Lake Lanier'],
    ),
);

// Flatten for easy lookup
$city_neighborhoods_flat = array();
$city_county_map = array();

foreach ($county_data as $county => $cities) {
    foreach ($cities as $city => $hoods) {
        $city_neighborhoods_flat[$city] = $hoods;
        $city_county_map[$city] = $county;
    }
}

// -------------------------------------------------------------------------
// 2. MAIN EXECUTION LOGIC
// -------------------------------------------------------------------------

echo '<div class="wrap">';
echo '<h1>Generating City Pages (Template-Based Architecture)</h1>';

// Parse locations.md
$locations_file = get_template_directory() . '/locations.md';
if (!file_exists($locations_file)) {
    wp_die('Error: locations.md not found.');
}

$lines = file($locations_file);
$city_map = array(); // City Name => [School Name, School Address, School ID]

// Find all published locations first to map names to IDs
$all_locations = get_posts(array('post_type' => 'location', 'posts_per_page' => -1));
$location_lookup = array();
foreach ($all_locations as $loc) {
    $location_lookup[trim($loc->post_title)] = $loc->ID;
}

foreach ($lines as $line) {
    if (strpos($line, '|') === false || strpos($line, 'School Name') !== false)
        continue;

    $parts = array_map('trim', explode('|', $line));
    if (count($parts) < 4)
        continue; // Need at least empty parts

    $school_name = $parts[1];
    $address = $parts[2];
    $target_cities_str = $parts[3];

    if (empty($target_cities_str))
        continue;

    $target_cities = array_map('trim', explode(',', $target_cities_str));
    $school_id = isset($location_lookup[$school_name]) ? $location_lookup[$school_name] : 0;

    foreach ($target_cities as $city) {
        if (empty($city))
            continue;
        if (!isset($city_map[$city])) {
            $city_map[$city] = array();
        }
        $city_map[$city][] = array(
            'school_name' => $school_name,
            'address' => $address,
            'id' => $school_id
        );
    }
}

// Process Each City
foreach ($city_map as $city_name => $schools) {
    echo "<hr><h3>Processing: $city_name</h3>";

    // 1. Get Neighborhoods & County
    $neighborhoods = isset($city_neighborhoods_flat[$city_name]) ? $city_neighborhoods_flat[$city_name] : array();
    $county = isset($city_county_map[$city_name]) ? $city_county_map[$city_name] : 'Local';

    // Fallback: Use School Names as neighborhoods if none hardcoded
    if (empty($neighborhoods)) {
        foreach ($schools as $s) {
            $neighborhoods[] = str_replace(['Campus', 'Academy by Chroma Early Learning'], '', $s['school_name']);
        }
    }

    // 2. Get Hero Image (From First School)
    $hero_image_url = '';
    if (!empty($schools)) {
        $primary_school_id = $schools[0]['id'];
        if ($primary_school_id) {
            // Try Hero Gallery First
            $gallery_raw = get_post_meta($primary_school_id, 'location_hero_gallery', true);
            if ($gallery_raw) {
                $gallery_lines = explode("\n", $gallery_raw);
                if (!empty($gallery_lines)) {
                    $hero_image_url = trim($gallery_lines[0]);
                }
            }
            // Fallback to Featured Image
            if (empty($hero_image_url)) {
                $hero_image_url = get_the_post_thumbnail_url($primary_school_id, 'full');
            }
        }
    }

    // 3. Create/Update Post
    // We do NOT generate HTML content anymore. The template handles it.
    $page_title = "Best Daycare & Preschool in $city_name, GA | Chroma Early Learning";

    $existing_page = get_page_by_title($city_name, OBJECT, 'city');

    $post_data = array(
        'post_title' => $city_name, // Slug will be city name
        'post_content' => '', // Empty content, template does the work
        'post_status' => 'publish',
        'post_type' => 'city',
        'post_author' => get_current_user_id(),
    );

    if ($existing_page) {
        $post_data['ID'] = $existing_page->ID;
        $post_id = wp_update_post($post_data);
        echo "Updated page for $city_name (ID: $post_id)<br>";
    } else {
        $post_id = wp_insert_post($post_data);
        echo "Created page for $city_name (ID: $post_id)<br>";
    }

    if (is_wp_error($post_id)) {
        echo "Error creating page: " . $post_id->get_error_message() . "<br>";
        continue;
    }

    // 4. Update Meta Data (The "Model" for our View)
    update_post_meta($post_id, 'city_county', $county);
    update_post_meta($post_id, 'city_neighborhoods', $neighborhoods);

    $school_ids = array_column($schools, 'id');
    update_post_meta($post_id, 'related_location_ids', $school_ids);

    if ($hero_image_url) {
        update_post_meta($post_id, 'city_hero_image', $hero_image_url);
    }

    // SEO Meta
    update_post_meta($post_id, '_yoast_wpseo_title', $page_title);
    update_post_meta($post_id, '_yoast_wpseo_metadesc', "Looking for 5-star rated daycare in $city_name? Chroma offers accredited infant care, toddler programs, and Free GA Pre-K at convenient locations near you.");

    // Keywords
    $keywords = array("daycare $city_name", "preschool $city_name", "child care $city_name", "early learning $city_name");
    foreach ($schools as $s) {
        $keywords[] = $s['school_name'];
    }
    update_post_meta($post_id, 'meta_keywords', implode(', ', $keywords));
}

echo '<p>All operations completed. City pages are now using the new template architecture.</p>';
echo '</div>';

get_footer();
