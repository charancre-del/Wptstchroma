<?php
/**
 * Schema Validator
 * Validates JSON-LD schema before output to ensure compliance with Schema.org and Google requirements
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Schema_Validator
{
    /**
     * Validation errors collected during validation
     */
    private static $errors = [];
    
    /**
     * Validation warnings (non-critical)
     */
    private static $warnings = [];

    /**
     * Required fields per schema type (based on Google Rich Results requirements)
     */
    private static $required_fields = [
        'LocalBusiness' => ['name', 'address'],
        'ChildCare' => ['name', 'address'],
        'Article' => ['headline', 'author', 'datePublished'],
        'BlogPosting' => ['headline', 'author', 'datePublished'],
        'Event' => ['name', 'startDate', 'location'],
        'FAQPage' => ['mainEntity'],
        'JobPosting' => ['title', 'description', 'datePosted', 'hiringOrganization'],
        'Product' => ['name'],
        'Review' => ['itemReviewed', 'author'],
        'Person' => ['name'],
        'Organization' => ['name'],
        'VideoObject' => ['name', 'description', 'thumbnailUrl', 'uploadDate'],
        'HowTo' => ['name', 'step'],
        'Service' => ['name', 'provider'],
        'Course' => ['name', 'provider'],
        'BreadcrumbList' => ['itemListElement'],
        'AggregateRating' => ['ratingValue', 'reviewCount'],
    ];

    /**
     * Recommended fields per schema type (for warnings)
     */
    private static $recommended_fields = [
        'LocalBusiness' => ['telephone', 'openingHours', 'geo', 'image', 'priceRange'],
        'ChildCare' => ['telephone', 'openingHours', 'geo', 'image', 'priceRange', 'aggregateRating'],
        'Article' => ['image', 'dateModified', 'publisher'],
        'Event' => ['endDate', 'image', 'description', 'offers'],
        'JobPosting' => ['baseSalary', 'employmentType', 'jobLocation'],
        'Product' => ['image', 'description', 'offers', 'aggregateRating'],
        'Person' => ['image', 'jobTitle', 'worksFor'],
        'Organization' => ['logo', 'contactPoint', 'sameAs'],
    ];

    /**
     * Valid Schema.org types (subset of commonly used)
     */
    private static $valid_types = [
        'Thing', 'Action', 'CreativeWork', 'Event', 'Intangible', 'Organization', 
        'Person', 'Place', 'Product', 'Article', 'BlogPosting', 'NewsArticle',
        'LocalBusiness', 'ChildCare', 'Preschool', 'EducationalOrganization',
        'Service', 'Review', 'AggregateRating', 'Rating', 'FAQPage', 'Question',
        'Answer', 'HowTo', 'HowToStep', 'JobPosting', 'VideoObject', 'ImageObject',
        'WebPage', 'WebSite', 'BreadcrumbList', 'ListItem', 'Offer', 'PostalAddress',
        'GeoCoordinates', 'OpeningHoursSpecification', 'ContactPoint', 'Course',
        'CourseInstance', 'Menu', 'MenuItem', 'Schedule', 'EducationalOccupationalCredential',
        'ItemList', 'CollectionPage', 'AboutPage', 'ContactPage', 'ImageGallery',
        'SpecialAnnouncement', 'MonetaryAmount', 'QuantitativeValue', 'PropertyValue',
    ];

    /**
     * Validate a single schema object
     *
     * @param array $schema The schema array to validate
     * @param string $context Optional context for error messages
     * @return bool True if valid, false if critical errors
     */
    public static function validate($schema, $context = '')
    {
        self::$errors = [];
        self::$warnings = [];

        // 1. Check basic structure
        if (!self::validate_structure($schema, $context)) {
            return false;
        }

        // 2. Validate @type
        $type = self::get_schema_type($schema);
        if (!self::validate_type($type, $context)) {
            return false;
        }

        // 3. Check required fields
        self::validate_required_fields($schema, $type, $context);

        // 4. Check recommended fields (warnings only)
        self::validate_recommended_fields($schema, $type, $context);

        // 5. Validate field values
        self::validate_field_values($schema, $type, $context);

        // 6. Validate nested objects
        self::validate_nested_objects($schema, $context);

        // 7. Run type-specific validation (Google Rich Results requirements)
        self::validate_type_specific($schema, $type, $context);

        return empty(self::$errors);
    }

    /**
     * Validate @graph structure (multiple schemas)
     *
     * @param array $graph_data The @graph array
     * @return bool
     */
    public static function validate_graph($graph_data)
    {
        self::$errors = [];
        self::$warnings = [];

        if (!isset($graph_data['@context'])) {
            self::$errors[] = 'Missing @context in schema graph';
        }

        if (!isset($graph_data['@graph']) || !is_array($graph_data['@graph'])) {
            self::$errors[] = 'Missing or invalid @graph array';
            return false;
        }

        $valid = true;
        foreach ($graph_data['@graph'] as $index => $schema) {
            $context = "Graph item #{$index}";
            if (!self::validate($schema, $context)) {
                $valid = false;
            }
        }

        return $valid;
    }

    /**
     * Validate JSON-LD string
     *
     * @param string $json_ld The JSON-LD string
     * @return array ['valid' => bool, 'errors' => [], 'warnings' => [], 'parsed' => []]
     */
    public static function validate_json_ld($json_ld)
    {
        self::$errors = [];
        self::$warnings = [];

        // 1. Parse JSON
        $parsed = json_decode($json_ld, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'valid' => false,
                'errors' => ['Invalid JSON: ' . json_last_error_msg()],
                'warnings' => [],
                'parsed' => null
            ];
        }

        // 2. Validate structure
        $valid = true;
        if (isset($parsed['@graph'])) {
            $valid = self::validate_graph($parsed);
        } else {
            $valid = self::validate($parsed);
        }

        return [
            'valid' => $valid && empty(self::$errors),
            'errors' => self::$errors,
            'warnings' => self::$warnings,
            'parsed' => $parsed
        ];
    }

    /**
     * Validate basic schema structure
     */
    private static function validate_structure($schema, $context)
    {
        if (!is_array($schema)) {
            self::$errors[] = "{$context}: Schema must be an array/object";
            return false;
        }

        if (empty($schema['@type']) && empty($schema['@graph'])) {
            self::$errors[] = "{$context}: Missing @type property";
            return false;
        }

        return true;
    }

    /**
     * Get the @type from schema (handles array types)
     */
    private static function get_schema_type($schema)
    {
        if (!isset($schema['@type'])) {
            return null;
        }

        // Handle multiple types [@type => ['ChildCare', 'LocalBusiness']]
        if (is_array($schema['@type'])) {
            return $schema['@type'][0]; // Use primary type
        }

        return $schema['@type'];
    }

    /**
     * Validate @type is a valid Schema.org type
     */
    private static function validate_type($type, $context)
    {
        if (!$type) {
            return false;
        }

        if (!in_array($type, self::$valid_types, true)) {
            // Not a critical error, just a warning for unknown types
            self::$warnings[] = "{$context}: Unknown type '{$type}' - may not be recognized by search engines";
        }

        return true;
    }

    /**
     * Validate required fields are present
     */
    private static function validate_required_fields($schema, $type, $context)
    {
        if (!isset(self::$required_fields[$type])) {
            return; // No required fields defined
        }

        foreach (self::$required_fields[$type] as $field) {
            if (!isset($schema[$field]) || empty($schema[$field])) {
                self::$errors[] = "{$context}: Missing required field '{$field}' for type '{$type}'";
            }
        }
    }

    /**
     * Validate recommended fields (warnings only)
     */
    private static function validate_recommended_fields($schema, $type, $context)
    {
        if (!isset(self::$recommended_fields[$type])) {
            return;
        }

        foreach (self::$recommended_fields[$type] as $field) {
            if (!isset($schema[$field]) || empty($schema[$field])) {
                self::$warnings[] = "{$context}: Missing recommended field '{$field}' for type '{$type}'";
            }
        }
    }

    /**
     * Validate field value types
     */
    private static function validate_field_values($schema, $type, $context)
    {
        // URL fields
        $url_fields = ['url', 'image', 'logo', 'sameAs', 'thumbnailUrl', 'contentUrl'];
        foreach ($url_fields as $field) {
            if (isset($schema[$field])) {
                $value = $schema[$field];
                if (is_string($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
                    self::$warnings[] = "{$context}: Field '{$field}' contains invalid URL: {$value}";
                }
            }
        }

        // Date fields (ISO 8601)
        $date_fields = ['datePublished', 'dateModified', 'datePosted', 'startDate', 'endDate', 'uploadDate'];
        foreach ($date_fields as $field) {
            if (isset($schema[$field])) {
                $value = $schema[$field];
                if (is_string($value) && !self::is_valid_date($value)) {
                    self::$warnings[] = "{$context}: Field '{$field}' should be ISO 8601 format: {$value}";
                }
            }
        }

        // Rating values (1-5 typically)
        if (isset($schema['ratingValue'])) {
            $rating = floatval($schema['ratingValue']);
            if ($rating < 0 || $rating > 5) {
                self::$warnings[] = "{$context}: ratingValue '{$rating}' should be between 0 and 5";
            }
        }

        // Telephone format
        if (isset($schema['telephone'])) {
            $phone = $schema['telephone'];
            if (!preg_match('/^[\+\d\-\(\)\s]+$/', $phone)) {
                self::$warnings[] = "{$context}: Telephone format may be invalid: {$phone}";
            }
        }
    }

    /**
     * Validate nested objects
     */
    private static function validate_nested_objects($schema, $context)
    {
        $nested_fields = ['author', 'publisher', 'provider', 'hiringOrganization', 
                          'location', 'address', 'geo', 'offers', 'review', 
                          'aggregateRating', 'mainEntity', 'itemListElement'];

        foreach ($nested_fields as $field) {
            if (isset($schema[$field])) {
                $value = $schema[$field];
                
                // Array of objects
                if (is_array($value) && isset($value[0]) && is_array($value[0])) {
                    foreach ($value as $idx => $nested) {
                        self::validate($nested, "{$context}.{$field}[{$idx}]");
                    }
                }
                // Single nested object
                elseif (is_array($value) && isset($value['@type'])) {
                    self::validate($value, "{$context}.{$field}");
                }
            }
        }
    }

    /**
     * Run type-specific validation based on schema type
     */
    private static function validate_type_specific($schema, $type, $context)
    {
        switch ($type) {
            case 'FAQPage':
                self::validate_faq_page($schema, $context);
                break;
            case 'Organization':
                self::validate_organization($schema, $context);
                break;
            case 'Person':
                self::validate_person($schema, $context);
                break;
            case 'LocalBusiness':
            case 'ChildCare':
            case 'Preschool':
                self::validate_local_business($schema, $context);
                break;
            case 'BreadcrumbList':
                self::validate_breadcrumb_list($schema, $context);
                break;
        }
    }

    /**
     * Validate FAQPage structure per Google Requirements
     */
    private static function validate_faq_page($schema, $context)
    {
        if (!isset($schema['mainEntity']) || !is_array($schema['mainEntity'])) {
            self::$errors[] = "$context: FAQPage missing mainEntity array";
            return;
        }

        foreach ($schema['mainEntity'] as $index => $entity) {
            $qContext = "$context > Question #" . ($index + 1);
            
            // Each entity must be a Question
            $type = self::get_schema_type($entity);
            if ($type !== 'Question') {
                self::$errors[] = "$qContext: mainEntity must be Question, got: $type";
                continue;
            }

            // Question must have name
            if (empty($entity['name'])) {
                self::$errors[] = "$qContext: Missing required field 'name'";
            }

            // Question must have acceptedAnswer
            if (empty($entity['acceptedAnswer'])) {
                self::$errors[] = "$qContext: Missing required field 'acceptedAnswer'";
                continue;
            }

            // Validate Answer
            $answer = $entity['acceptedAnswer'];
            $aContext = "$qContext > Answer";
            
            if (is_array($answer)) {
                $ansType = self::get_schema_type($answer);
                if ($ansType !== 'Answer') {
                    self::$errors[] = "$aContext: acceptedAnswer must be Answer type, got: $ansType";
                }
                
                if (empty($answer['text'])) {
                    self::$errors[] = "$aContext: Answer missing required field 'text'";
                }
            }
        }
    }

    /**
     * Validate Organization per Google Requirements
     */
    private static function validate_organization($schema, $context)
    {
        // name is required (already checked in required_fields)
        
        // logo should be ImageObject or valid URL
        if (isset($schema['logo'])) {
            if (!self::validate_image_field($schema['logo'], "$context > logo")) {
                self::$warnings[] = "$context: logo should be valid URL or ImageObject";
            }
        } else {
            self::$warnings[] = "$context: Missing recommended field 'logo'";
        }

        // contactPoint should be valid
        if (isset($schema['contactPoint']) && is_array($schema['contactPoint'])) {
            if (empty($schema['contactPoint']['@type']) || $schema['contactPoint']['@type'] !== 'ContactPoint') {
                self::$warnings[] = "$context > contactPoint: Should be ContactPoint type";
            }
        }

        // sameAs should be array of URLs
        if (isset($schema['sameAs'])) {
            if (!is_array($schema['sameAs'])) {
                self::$warnings[] = "$context: sameAs should be an array";
            } else {
                foreach ($schema['sameAs'] as $url) {
                    if (!self::validate_url($url)) {
                        self::$warnings[] = "$context > sameAs: Invalid URL: $url";
                    }
                }
            }
        }
    }

    /**
     * Validate Person per Google Requirements
     */
    private static function validate_person($schema, $context)
    {
        // Check jobTitle
        if (empty($schema['jobTitle'])) {
            self::$warnings[] = "$context: Missing recommended field 'jobTitle'";
        }

        // Check worksFor (should be Organization)
        if (isset($schema['worksFor']) && is_array($schema['worksFor'])) {
            $orgType = self::get_schema_type($schema['worksFor']);
            if ($orgType !== 'Organization') {
                self::$warnings[] = "$context > worksFor: Should be Organization type, got: $orgType";
            }
        }

        // Validate image
        if (isset($schema['image']) && !self::validate_image_field($schema['image'], "$context > image")) {
            self::$warnings[] = "$context: image should be valid URL or ImageObject";
        }
    }

    /**
     * Validate LocalBusiness per Google Requirements
     */
    private static function validate_local_business($schema, $context)
    {
        // Validate address (PostalAddress)
        if (isset($schema['address'])) {
            if (is_array($schema['address'])) {
                $addrType = self::get_schema_type($schema['address']);
                if ($addrType !== 'PostalAddress') {
                    self::$errors[] = "$context > address: Must be PostalAddress type, got: $addrType";
                } else {
                    // Check required address fields
                    $requiredAddr = ['streetAddress', 'addressLocality', 'addressRegion', 'postalCode'];
                    foreach ($requiredAddr as $field) {
                        if (empty($schema['address'][$field])) {
                            self::$warnings[] = "$context > address: Missing recommended field '$field'";
                        }
                    }
                }
            }
        }

        // Validate geo (GeoCoordinates)
        if (isset($schema['geo']) && is_array($schema['geo'])) {
            $geoType = self::get_schema_type($schema['geo']);
            if ($geoType !== 'GeoCoordinates') {
                self::$warnings[] = "$context > geo: Should be GeoCoordinates type, got: $geoType";
            } else {
                if (empty($schema['geo']['latitude']) || empty($schema['geo']['longitude'])) {
                    self::$warnings[] = "$context > geo: GeoCoordinates missing latitude or longitude";
                }
            }
        }
    }

    /**
     * Validate BreadcrumbList per Google Requirements
     */
    private static function validate_breadcrumb_list($schema, $context)
    {
        if (empty($schema['itemListElement']) || !is_array($schema['itemListElement'])) {
            self::$errors[] = "$context: BreadcrumbList missing itemListElement array";
            return;
        }

        foreach ($schema['itemListElement'] as $index => $item) {
            $iContext = "$context > Item #" . ($index + 1);
            
            $itemType = self::get_schema_type($item);
            if ($itemType !== 'ListItem') {
                self::$errors[] = "$iContext: Must be ListItem type, got: $itemType";
                continue;
            }

            if (!isset($item['position'])) {
                self::$errors[] = "$iContext: ListItem missing 'position'";
            }

            if (empty($item['name']) && empty($item['item'])) {
                self::$errors[] = "$iContext: ListItem must have 'name' or 'item'";
            }
        }
    }

    /**
     * Validate URL format
     */
    private static function validate_url($url)
    {
        if (!is_string($url)) {
            return false;
        }
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Validate image field (can be URL or ImageObject)
     */
    private static function validate_image_field($image, $context = '')
    {
        if (is_string($image)) {
            return self::validate_url($image);
        }
        
        if (is_array($image)) {
            $type = self::get_schema_type($image);
            if ($type === 'ImageObject') {
                if (empty($image['url'])) {
                    if ($context) {
                        self::$warnings[] = "$context: ImageObject missing 'url'";
                    }
                    return false;
                }
                return self::validate_url($image['url']);
            }
        }
        
        return false;
    }

    /**
     * Check if date string is valid ISO 8601
     */
    private static function is_valid_date($date)
    {
        // Accept various ISO 8601 formats
        $formats = [
            'Y-m-d',
            'Y-m-d\TH:i:s',
            'Y-m-d\TH:i:sP',
            'Y-m-d\TH:i:s\Z',
            \DateTime::ISO8601,
            \DateTime::ATOM
        ];

        foreach ($formats as $format) {
            $dt = \DateTime::createFromFormat($format, $date);
            if ($dt !== false) {
                return true;
            }
        }

        // Also accept timestamps that strtotime can parse
        return strtotime($date) !== false;
    }

    /**
     * Get last validation errors
     */
    public static function get_errors()
    {
        return self::$errors;
    }

    /**
     * Get last validation warnings
     */
    public static function get_warnings()
    {
        return self::$warnings;
    }

    /**
     * Get full validation report
     */
    public static function get_report()
    {
        return [
            'valid' => empty(self::$errors),
            'error_count' => count(self::$errors),
            'warning_count' => count(self::$warnings),
            'errors' => self::$errors,
            'warnings' => self::$warnings
        ];
    }

    /**
     * Clear validation state
     */
    public static function reset()
    {
        self::$errors = [];
        self::$warnings = [];
    }

    /**
     * Validate and optionally fix common issues
     *
     * @param array $schema
     * @param bool $auto_fix Whether to attempt auto-fixing
     * @return array ['schema' => fixed_schema, 'fixed' => [], 'errors' => []]
     */
    public static function validate_and_fix($schema, $auto_fix = false)
    {
        $fixed = [];
        $errors = [];

        // Ensure @context exists
        if (!isset($schema['@context']) && !isset($schema['@graph'])) {
            if ($auto_fix) {
                $schema['@context'] = 'https://schema.org';
                $fixed[] = 'Added missing @context';
            } else {
                $errors[] = 'Missing @context';
            }
        }

        // Ensure @type exists
        if (!isset($schema['@type']) && !isset($schema['@graph'])) {
            $errors[] = 'Missing @type (cannot auto-fix)';
        }

        // Fix common URL issues
        $url_fields = ['url', 'image', 'logo', 'thumbnailUrl'];
        foreach ($url_fields as $field) {
            if (isset($schema[$field]) && is_string($schema[$field])) {
                // Remove whitespace
                $cleaned = trim($schema[$field]);
                if ($cleaned !== $schema[$field]) {
                    $schema[$field] = $cleaned;
                    $fixed[] = "Trimmed whitespace from {$field}";
                }
                
                // Ensure https
                if (strpos($schema[$field], 'http://') === 0 && $auto_fix) {
                    $schema[$field] = str_replace('http://', 'https://', $schema[$field]);
                    $fixed[] = "Upgraded {$field} to HTTPS";
                }
            }
        }

        // Ensure arrays for multiple types
        if (isset($schema['sameAs']) && is_string($schema['sameAs']) && $auto_fix) {
            // If comma-separated, split into array
            if (strpos($schema['sameAs'], ',') !== false) {
                $schema['sameAs'] = array_map('trim', explode(',', $schema['sameAs']));
                $fixed[] = 'Converted sameAs to array';
            }
        }

        // Validate after fixes
        self::validate($schema);

        return [
            'schema' => $schema,
            'fixed' => $fixed,
            'errors' => array_merge($errors, self::$errors),
            'warnings' => self::$warnings
        ];
    }
}

/**
 * Register AJAX endpoint for schema validation
 */
add_action('wp_ajax_chroma_validate_schema', function() {
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Permission denied']);
    }

    $json_ld = isset($_POST['json_ld']) ? wp_unslash($_POST['json_ld']) : '';
    
    if (empty($json_ld)) {
        wp_send_json_error(['message' => 'No schema data provided']);
    }

    $result = Chroma_Schema_Validator::validate_json_ld($json_ld);
    
    if ($result['valid']) {
        wp_send_json_success([
            'message' => 'Schema is valid!',
            'warnings' => $result['warnings']
        ]);
    } else {
        wp_send_json_error([
            'message' => 'Schema validation failed',
            'errors' => $result['errors'],
            'warnings' => $result['warnings']
        ]);
    }
});

/**
 * Register AJAX endpoint for validating a post's schema
 */
add_action('wp_ajax_chroma_validate_post_schema', function() {
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Permission denied']);
    }

    $post_id = intval($_POST['post_id'] ?? 0);
    if (!$post_id) {
        wp_send_json_error(['message' => 'Invalid post ID']);
    }

    $schemas = get_post_meta($post_id, '_chroma_post_schemas', true);
    if (empty($schemas) || !is_array($schemas)) {
        wp_send_json_error(['message' => 'No schemas found for this post']);
    }

    $all_results = [];
    $has_errors = false;

    foreach ($schemas as $index => $schema_data) {
        $type = $schema_data['type'] ?? 'Unknown';
        $data = $schema_data['data'] ?? [];
        
        // Build the full schema with @context and @type
        $full_schema = array_merge(
            ['@context' => 'https://schema.org', '@type' => $type],
            $data
        );

        Chroma_Schema_Validator::validate($full_schema, "Schema #{$index} ({$type})");
        $report = Chroma_Schema_Validator::get_report();
        
        if (!$report['valid']) {
            $has_errors = true;
        }

        $all_results[] = [
            'type' => $type,
            'index' => $index,
            'valid' => $report['valid'],
            'errors' => $report['errors'],
            'warnings' => $report['warnings']
        ];
    }

    wp_send_json_success([
        'valid' => !$has_errors,
        'schemas' => $all_results
    ]);
});

/**
 * Hook into schema output to validate before rendering
 * (Development mode only - controlled by WP_DEBUG)
 */
if (defined('WP_DEBUG') && WP_DEBUG) {
    add_filter('chroma_schema_before_output', function($json_ld) {
        $result = Chroma_Schema_Validator::validate_json_ld($json_ld);
        
        if (!$result['valid']) {
            error_log('Chroma Schema Validation FAILED: ' . print_r($result['errors'], true));
        }
        
        if (!empty($result['warnings'])) {
            error_log('Chroma Schema Warnings: ' . print_r($result['warnings'], true));
        }
        
        return $json_ld;
    });
}
