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

        // Determine if it's a single schema or a @graph
        if (isset($parsed['@graph']) && is_array($parsed['@graph'])) {
            $graph = $parsed['@graph'];
            $context = 'Graph';

            // 2. Validate @context for the graph
            if (!isset($parsed['@context'])) {
                self::$errors[] = 'Missing @context in schema graph';
            }

            // 3. Validate each node in the graph
            foreach ($graph as $i => $node) {
                $nodeContext = count($graph) > 1 ? "$context > Node $i" : $context;
                self::validate($node, $nodeContext); // Use the main validate function for each node
            }

            // 4. Validate Graph Integrity (Linkages)
            self::validate_graph_integrity($graph);

        } else {
            // Single schema object
            self::validate($parsed);
        }

        return [
            'valid' => empty(self::$errors),
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
            case 'Corporation':
            case 'EducationalOrganization':
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
            case 'Article':
            case 'BlogPosting':
            case 'NewsArticle':
            case 'TechArticle':
                self::validate_article($schema, $context);
                break;
            case 'Event':
                self::validate_event($schema, $context);
                break;
            case 'HowTo':
                self::validate_howto($schema, $context);
                break;
            case 'VideoObject':
                self::validate_video_object($schema, $context);
                break;
            case 'AggregateRating':
                self::validate_aggregate_rating($schema, $context);
                break;
            case 'WebPage':
                self::validate_webpage($schema, $context);
                break;
            case 'Service':
                self::validate_service($schema, $context);
                break;
            case 'CollectionPage':
            case 'AboutPage':
            case 'ContactPage':
            case 'SearchResultsPage':
                self::validate_collection_page($schema, $context);
                break;
            case 'ItemList':
                self::validate_item_list($schema, $context);
                break;
        }
    }

    /**
     * Validate Article/BlogPosting per Google Requirements
     */
    private static function validate_article($schema, $context)
    {
        // Recommended fields
        $recommended = ['headline', 'image', 'datePublished', 'dateModified', 'author', 'publisher'];
        foreach ($recommended as $field) {
            if (empty($schema[$field])) {
                self::$warnings[] = "$context: Missing recommended field '$field'";
            }
        }

        // Validate Image
        if (isset($schema['image']) && !self::validate_image_field($schema['image'], "$context > image")) {
            self::$warnings[] = "$context: image should be valid URL or ImageObject";
        }

        // Validate Author
        if (isset($schema['author'])) {
            $authType = self::get_schema_type($schema['author']);
            if (!in_array($authType, ['Person', 'Organization'])) {
                self::$warnings[] = "$context > author: Should be Person or Organization, got: $authType";
            }
        }

        // Validate Publisher
        if (isset($schema['publisher'])) {
            $pubType = self::get_schema_type($schema['publisher']);
            if ($pubType !== 'Organization') {
                self::$warnings[] = "$context > publisher: Should be Organization, got: $pubType";
            }
        }
    }

    /**
     * Validate Event per Google Requirements
     */
    private static function validate_event($schema, $context)
    {
        // Required fields
        $required = ['startDate', 'location', 'eventAttendanceMode', 'eventStatus'];
        foreach ($required as $field) {
            if (empty($schema[$field])) {
                self::$errors[] = "$context: Missing required field '$field'";
            }
        }

        // Validate Dates
        if (isset($schema['startDate']) && !self::is_valid_date($schema['startDate'])) {
             self::$errors[] = "$context > startDate: Invalid date format";
        }
        if (isset($schema['endDate']) && !self::is_valid_date($schema['endDate'])) {
             self::$errors[] = "$context > endDate: Invalid date format";
        }

        // Validate Location
        if (isset($schema['location'])) {
            $locType = self::get_schema_type($schema['location']);
            if (!in_array($locType, ['Place', 'VirtualLocation', 'PostalAddress'])) {
                self::$warnings[] = "$context > location: Should be Place or VirtualLocation, got: $locType";
            }
            // Check address if Place
            if ($locType === 'Place' && isset($schema['location']['address'])) {
                $addrType = self::get_schema_type($schema['location']['address']);
                if ($addrType !== 'PostalAddress') {
                    self::$warnings[] = "$context > location > address: Should be PostalAddress";
                }
            }
        }

        // Validate Offers
        if (isset($schema['offers'])) {
             $offerType = self::get_schema_type($schema['offers']);
             if ($offerType !== 'Offer') {
                  self::$warnings[] = "$context > offers: Should be Offer type";
             }
        }
    }

    /**
     * Validate HowTo per Google Requirements
     */
    private static function validate_howto($schema, $context)
    {
        // Required: name, step
        if (empty($schema['step'])) { 
             self::$errors[] = "$context: Missing required field 'step'";
             return;
        }

        // Validate Steps
        $steps = isset($schema['step']) ? $schema['step'] : [];
        if (!is_array($steps)) $steps = [$steps];

        foreach ($steps as $i => $step) {
            $sContext = "$context > step[$i]";
            $stepType = self::get_schema_type($step);
            
            if (!in_array($stepType, ['HowToStep', 'HowToSection'])) {
                self::$warnings[] = "$sContext: Should be HowToStep or HowToSection, got: $stepType";
                continue;
            }

            // HowToStep validation
            if ($stepType === 'HowToStep') {
                // Must have text, image, video, OR itemListElement
                $hasContent = !empty($step['text']) || !empty($step['image']) || !empty($step['video']) || !empty($step['itemListElement']) || !empty($step['url']);
                if (!$hasContent) {
                    self::$warnings[] = "$sContext: HowToStep missing content (text, image, video, url, or itemListElement)";
                }
            }
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

        // Check telephone
        if (isset($schema['telephone'])) {
            self::validate_telephone($schema['telephone'], "$context > telephone");
        }

        // contactPoint should be valid
        if (isset($schema['contactPoint']) && is_array($schema['contactPoint'])) {
            if (empty($schema['contactPoint']['@type']) || $schema['contactPoint']['@type'] !== 'ContactPoint') {
                self::$warnings[] = "$context > contactPoint: Should be ContactPoint type";
            }
            if (isset($schema['contactPoint']['telephone'])) {
                self::validate_telephone($schema['contactPoint']['telephone'], "$context > contactPoint > telephone");
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
     * Helper: Validate Telephone
     * Google recommends international format, but we at least check for meaningful content.
     */
    private static function validate_telephone($tel, $context)
    {
        if (empty($tel)) {
            self::$warnings[] = "$context: Empty telephone number";
            return false;
        }
        
        // Remove common separators to check for digits
        $clean = preg_replace('/[\s\-\+\(\)\.]/', '', $tel);
        if (strlen($clean) < 5) {
            self::$errors[] = "$context: Invalid telephone number '$tel'. Must contain at least 5 digits.";
            return false;
        }
        
        return true;
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

        // Check worksFor (should be Organization or subtype)
        if (isset($schema['worksFor']) && is_array($schema['worksFor'])) {
            $orgType = self::get_schema_type($schema['worksFor']);
            $validOrgTypes = ['Organization', 'Corporation', 'LocalBusiness', 'ChildCare', 'Preschool', 'School', 'EducationalOrganization', 'NGO', 'GovernmentOrganization'];
            
            if (!in_array($orgType, $validOrgTypes)) {
                self::$warnings[] = "$context > worksFor: Should be Organization type (or subtype), got: $orgType";
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
        // Inherit Organization validation (logo, contactPoint, telephone, etc.)
        self::validate_organization($schema, $context);

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

        $positions = [];

        foreach ($schema['itemListElement'] as $index => $item) {
            $iContext = "$context > Item #" . ($index + 1);
            
            $itemType = self::get_schema_type($item);
            if ($itemType !== 'ListItem') {
                self::$errors[] = "$iContext: Must be ListItem type, got: $itemType";
                continue;
            }

            // Check position
            if (!isset($item['position'])) {
                self::$errors[] = "$iContext: ListItem missing 'position'";
            } else {
                $positions[] = (int) $item['position'];
            }

            // Check name or item required
            if (empty($item['name']) && empty($item['item'])) {
                self::$errors[] = "$iContext: ListItem must have 'name' or 'item'";
            }
            
            // NEW: Check for HTML in name field
            if (isset($item['name']) && preg_match('/<[^>]+>/', $item['name'])) {
                self::$errors[] = "$iContext: 'name' contains HTML tags - must be plain text";
            }
            
            // NEW: Check for empty string item (common bug)
            if (isset($item['item']) && $item['item'] === '') {
                self::$errors[] = "$iContext: 'item' is empty string - provide valid URL or omit field";
            }
            
            // NEW: Validate item URL format if provided
            if (isset($item['item']) && !empty($item['item']) && !filter_var($item['item'], FILTER_VALIDATE_URL)) {
                self::$warnings[] = "$iContext: 'item' may not be a valid URL: {$item['item']}";
            }
        }
        
        // NEW: Check position sequence
        if (!empty($positions)) {
            sort($positions);
            $expected = range(1, count($positions));
            if ($positions !== $expected) {
                self::$warnings[] = "$context: Position values should be sequential starting from 1";
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
                
                // Check dimensions (Google Recommended)
                if (!isset($image['width']) || !isset($image['height'])) {
                     self::$warnings[] = "$context: ImageObject should have explicit 'width' and 'height'";
                }

                return self::validate_url($image['url']);
            }
        }
        
        return false;
    }

    /**
     * Validate VideoObject
     */
    private static function validate_video_object($schema, $context)
    {
        $required = ['name', 'description', 'uploadDate', 'thumbnailUrl'];
        foreach ($required as $field) {
            if (empty($schema[$field])) {
                self::$errors[] = "$context: VideoObject missing required field '$field'";
            }
        }

        if (isset($schema['uploadDate']) && !self::is_valid_date($schema['uploadDate'])) {
            self::$errors[] = "$context > uploadDate: Invalid date format";
        }

        if (isset($schema['thumbnailUrl'])) {
            $thumbs = is_array($schema['thumbnailUrl']) ? $schema['thumbnailUrl'] : [$schema['thumbnailUrl']];
            foreach ($thumbs as $url) {
                if (!self::validate_url($url)) {
                     self::$warnings[] = "$context > thumbnailUrl: Invalid URL '$url'";
                }
            }
        }
    }

    /**
     * Validate AggregateRating
     */
    private static function validate_aggregate_rating($schema, $context)
    {
        if (!isset($schema['ratingValue'])) {
            self::$errors[] = "$context: Missing 'ratingValue'";
            return;
        }

        $val = $schema['ratingValue'];
        $min = isset($schema['worstRating']) ? (float)$schema['worstRating'] : 1;
        $max = isset($schema['bestRating']) ? (float)$schema['bestRating'] : 5;

        if (!is_numeric($val) || $val < $min || $val > $max) {
             self::$errors[] = "$context > ratingValue: Must be between $min and $max, got '$val'";
        }
    }

    /**
     * Validate WebPage
     */
    private static function validate_webpage($schema, $context)
    {
        if (empty($schema['name']) && empty($schema['headline'])) {
             self::$warnings[] = "$context: WebPage should have 'name' or 'headline'";
        }

        if (isset($schema['breadcrumb'])) {
             $bcType = self::get_schema_type($schema['breadcrumb']);
             if ($bcType !== 'BreadcrumbList') {
                 self::$warnings[] = "$context > breadcrumb: Should be BreadcrumbList, got $bcType";
             }
        }
    }    

    /**
     * Validate Service schema
     */
    private static function validate_service($schema, $context)
    {
        // Recommended fields (not strictly required per Google)
        if (empty($schema['name'])) {
            self::$warnings[] = "$context: Service should have 'name'";
        }
        if (empty($schema['provider'])) {
            self::$warnings[] = "$context: Service should have 'provider'";
        }
        if (empty($schema['serviceType'])) {
            self::$warnings[] = "$context: Service should have 'serviceType'";
        }
        
        // Validate provider if present
        if (isset($schema['provider']) && is_array($schema['provider'])) {
            $provType = self::get_schema_type($schema['provider']);
            $validProviderTypes = ['Organization', 'Person', 'LocalBusiness', 'ChildCare', 'Preschool', 'EducationalOrganization'];
            if (!in_array($provType, $validProviderTypes)) {
                self::$warnings[] = "$context > provider: Should be Organization or Person, got: $provType";
            }
        }
        
        // Validate areaServed if present
        if (isset($schema['areaServed']) && is_array($schema['areaServed'])) {
            $areaType = self::get_schema_type($schema['areaServed']);
            $validAreaTypes = ['City', 'State', 'Country', 'Place', 'GeoShape', 'AdministrativeArea'];
            if (!in_array($areaType, $validAreaTypes)) {
                self::$warnings[] = "$context > areaServed: Should be City, State, or Place type, got: $areaType";
            }
        }
    }

    /**
     * Validate CollectionPage / AboutPage / ContactPage
     */
    private static function validate_collection_page($schema, $context)
    {
        // These page types are valid with minimal fields
        if (empty($schema['name']) && empty($schema['headline'])) {
            self::$warnings[] = "$context: Page should have 'name' or 'headline'";
        }
        
        // Validate mainEntity if present (often an ItemList)
        if (isset($schema['mainEntity'])) {
            $type = self::get_schema_type($schema['mainEntity']);
            if ($type === 'ItemList') {
                self::validate_item_list($schema['mainEntity'], "$context > mainEntity");
            }
        }
    }

    /**
     * Validate ItemList schema
     */
    private static function validate_item_list($schema, $context)
    {
        if (empty($schema['itemListElement'])) {
            self::$errors[] = "$context: ItemList missing 'itemListElement'";
            return;
        }
        
        if (!is_array($schema['itemListElement'])) {
            self::$errors[] = "$context: itemListElement must be an array";
            return;
        }
        
        foreach ($schema['itemListElement'] as $i => $item) {
            $iContext = "$context > Item #" . ($i + 1);
            
            if (!is_array($item)) {
                self::$errors[] = "$iContext: Must be an object";
                continue;
            }
            
            $type = self::get_schema_type($item);
            if ($type !== 'ListItem' && !empty($type)) {
                self::$warnings[] = "$iContext: Expected ListItem type, got: $type";
            }
            
            if (!isset($item['position'])) {
                self::$warnings[] = "$iContext: Missing 'position'";
            }
            
            // ListItem should have name, url, or item
            if (empty($item['name']) && empty($item['url']) && empty($item['item'])) {
                self::$warnings[] = "$iContext: Should have 'name', 'url', or 'item'";
            }
        }
    }

    /**
     * Check if date string is valid ISO 8601
     */
    private static function is_valid_date($date)
    {
        // Simple ISO 8601 check (simplified)
        // Accepts: 2024-01-01, 2024-01-01T12:00:00, 2024-01-01T12:00:00+00:00
        if (!is_string($date)) return false;
        
        // Use DateTime to verify
        // Try multiple formats or just constructor
        try {
            new DateTime($date);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Validate Graph Integrity (Broken Links)
     */
    private static function validate_graph_integrity($graph)
    {
        $ids = [];
        $refs = [];

        // 1. Collect all IDs and References recursively
        self::scan_graph_for_ids_and_refs($graph, $ids, $refs);

        // 2. meaningful refs (only those that look like local IDs or start with #)
        foreach ($refs as $ref) {
            // We only care if it points to a local ID (e.g. #id or matching URL)
            // If it's an external URL, we can't validate it easily here.
            
            // If it starts with #, strictly check validity
            if (strpos($ref, '#') === 0) {
                 if (!in_array($ref, $ids) && !in_array(ltrim($ref, '#'), $ids)) {
                     // Try loose matching (sometimes ID is full URL ending in #hash)
                     $found = false;
                     foreach ($ids as $id) {
                         if (strpos($id, $ref) !== false) $found = true;
                     }
                     if (!$found) {
                         self::$warnings[] = "Graph Integrity: Broken reference '$ref' (target node not found)";
                     }
                 }
            }
        }
    }

    /**
     * Recursive scan for @id definitions and usages
     */
    private static function scan_graph_for_ids_and_refs($data, &$ids, &$refs)
    {
        if (is_array($data)) {
            // Check if this object defines an ID
            if (isset($data['@id'])) {
                $ids[] = $data['@id'];
            }
            
            // Check if this object IS ONLY a reference (e.g. {"@id": "..."})
            // and has no other keys (roughly). Or usually property: {"@id": "..."}
            if (isset($data['@id']) && count($data) === 1) {
                $refs[] = $data['@id'];
            }

            foreach ($data as $key => $value) {
                self::scan_graph_for_ids_and_refs($value, $ids, $refs);
            }
        }
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
            chroma_debug_log(' Schema Validation FAILED: ' . print_r($result['errors'], true));
        }
        
        if (!empty($result['warnings'])) {
            chroma_debug_log(' Schema Warnings: ' . print_r($result['warnings'], true));
        }
        
        return $json_ld;
    });
}


