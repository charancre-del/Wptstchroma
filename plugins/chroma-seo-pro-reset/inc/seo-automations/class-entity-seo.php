<?php
/**
 * Entity SEO
 * Knowledge Graph optimization, topic clustering
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Entity_SEO
{
    public function __construct() {
        // DISABLED - Moved to Chroma SEO Pro Plugin
        // add_action('wp_head', [$this, 'output_organization_schema']);
        add_filter('the_content', [$this, 'add_semantic_markup'], 5);
    }
    
    /**
     * Output Organization schema with sameAs
     */
    public function output_organization_schema() {
        if (!is_front_page()) {
            return;
        }
        
        $same_as = get_option('chroma_seo_same_as_urls', [
            'https://www.facebook.com/chromaearlylearning',
            'https://www.instagram.com/chromaearlylearning',
            'https://www.linkedin.com/company/chroma-early-learning'
        ]);
        
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            '@id' => home_url('/#organization'),
            'name' => get_bloginfo('name'),
            'url' => home_url('/'),
            'logo' => get_theme_mod('custom_logo') ? wp_get_attachment_url(get_theme_mod('custom_logo')) : '',
            'description' => get_bloginfo('description'),
            'sameAs' => array_filter($same_as)
        ];
        
        // Add founder if available
        $founder = get_option('chroma_seo_founder_name');
        if ($founder) {
            $schema['founder'] = [
                '@type' => 'Person',
                'name' => $founder
            ];
        }
        
        // Add founding date
        $founded = get_option('chroma_seo_founded_date');
        if ($founded) {
            $schema['foundingDate'] = $founded;
        }
        
        echo '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>' . "\n";
    }
    
    /**
     * Add semantic markup to content
     */
    public function add_semantic_markup($content) {
        // Wrap numbers that look like phone numbers
        $content = preg_replace(
            '/(\(?(\d{3})\)?[\s.-]?(\d{3})[\s.-]?(\d{4}))/',
            '<span itemscope itemtype="https://schema.org/telephone">$1</span>',
            $content
        );
        
        return $content;
    }
    
    /**
     * Get topic clusters
     */
    public static function get_topic_clusters() {
        // Define topic clusters
        $clusters = [
            'infant-care' => [
                'name' => 'Infant Care',
                'keywords' => ['infant', 'baby', 'newborn', '0-12 months'],
                'hub_page' => null
            ],
            'preschool' => [
                'name' => 'Preschool',
                'keywords' => ['preschool', 'pre-k', '3-4 years', 'kindergarten prep'],
                'hub_page' => null
            ],
            'child-development' => [
                'name' => 'Child Development',
                'keywords' => ['development', 'milestones', 'learning', 'growth'],
                'hub_page' => null
            ]
        ];
        
        // Find hub pages
        foreach ($clusters as $slug => &$cluster) {
            $page = get_page_by_path($slug);
            if ($page) {
                $cluster['hub_page'] = $page;
            }
        }
        
        return $clusters;
    }
}

new Chroma_Entity_SEO();


