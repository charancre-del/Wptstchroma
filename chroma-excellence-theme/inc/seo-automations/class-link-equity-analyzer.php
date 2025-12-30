<?php
/**
 * Link Equity Analyzer
 * Analyzes internal link structure and provides recommendations
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Link_Equity_Analyzer
{
    public function __construct() {
        add_action('admin_menu', [$this, 'add_dashboard_page']);
    }
    
    /**
     * Analyze all pages
     */
    public static function analyze() {
        $posts = get_posts([
            'post_type' => ['post', 'page', 'location', 'program'],
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ]);
        
        $results = [];
        $site_url = home_url();
        
        foreach ($posts as $post) {
            $url = get_permalink($post);
            $content = $post->post_content;
            
            // Count outgoing internal links
            preg_match_all('/<a[^>]+href=["\'](' . preg_quote($site_url, '/') . '[^"\']*)["\'][^>]*>/i', $content, $matches);
            $outgoing = count($matches[1] ?? []);
            
            $results[$post->ID] = [
                'id' => $post->ID,
                'title' => $post->post_title,
                'url' => $url,
                'type' => $post->post_type,
                'outgoing' => $outgoing,
                'incoming' => 0 // Will be calculated
            ];
        }
        
        // Count incoming links
        foreach ($posts as $post) {
            $url = get_permalink($post);
            
            foreach ($posts as $other_post) {
                if ($other_post->ID === $post->ID) continue;
                
                if (strpos($other_post->post_content, $url) !== false) {
                    $results[$post->ID]['incoming']++;
                }
            }
        }
        
        // Calculate score (simple PageRank-like)
        foreach ($results as &$r) {
            $r['score'] = ($r['incoming'] * 2) + ($r['outgoing'] * 0.5);
        }
        
        // Sort by score
        uasort($results, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        return $results;
    }
    
    /**
     * Get orphan pages (0 incoming links)
     */
    public static function get_orphans() {
        $analysis = self::analyze();
        
        return array_filter($analysis, function($r) {
            return $r['incoming'] === 0;
        });
    }
    
    /**
     * Get recommendations
     */
    public static function get_recommendations() {
        $analysis = self::analyze();
        $recommendations = [];
        
        // Find orphans
        $orphans = array_filter($analysis, fn($r) => $r['incoming'] === 0);
        foreach ($orphans as $orphan) {
            $recommendations[] = [
                'type' => 'orphan',
                'post_id' => $orphan['id'],
                'title' => $orphan['title'],
                'message' => 'This page has no internal links pointing to it. Add links from related content.'
            ];
        }
        
        // Find pages with too many outgoing links
        $heavy = array_filter($analysis, fn($r) => $r['outgoing'] > 20);
        foreach ($heavy as $h) {
            $recommendations[] = [
                'type' => 'too_many_links',
                'post_id' => $h['id'],
                'title' => $h['title'],
                'message' => 'This page has ' . $h['outgoing'] . ' outgoing links. Consider consolidating.'
            ];
        }
        
        // Find low-score important pages (locations/programs with few incoming)
        $important_low = array_filter($analysis, function($r) {
            return in_array($r['type'], ['location', 'program']) && $r['incoming'] < 3;
        });
        foreach ($important_low as $il) {
            $recommendations[] = [
                'type' => 'needs_links',
                'post_id' => $il['id'],
                'title' => $il['title'],
                'message' => 'This ' . $il['type'] . ' page only has ' . $il['incoming'] . ' incoming links. Add more from blog posts.'
            ];
        }
        
        return $recommendations;
    }
    
    /**
     * Add dashboard page
     */
    public function add_dashboard_page() {
        add_submenu_page(
            'chroma-llm-settings',
            'Link Equity',
            'Link Equity',
            'manage_options',
            'chroma-link-equity',
            [$this, 'render_dashboard']
        );
    }
    
    /**
     * Render dashboard
     */
    public function render_dashboard() {
        $analysis = self::analyze();
        $recommendations = self::get_recommendations();
        $orphans = self::get_orphans();
        ?>
        <div class="wrap">
            <h1>Link Equity Analysis</h1>
            
            <div class="equity-stats">
                <div class="stat-box">
                    <h3><?php echo count($analysis); ?></h3>
                    <p>Total Pages</p>
                </div>
                <div class="stat-box warning">
                    <h3><?php echo count($orphans); ?></h3>
                    <p>Orphan Pages</p>
                </div>
                <div class="stat-box">
                    <h3><?php echo count($recommendations); ?></h3>
                    <p>Recommendations</p>
                </div>
            </div>
            
            <?php if (!empty($recommendations)): ?>
            <h2>Recommendations</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Page</th>
                        <th>Issue</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($recommendations, 0, 20) as $rec): ?>
                    <tr>
                        <td>
                            <a href="<?php echo get_edit_post_link($rec['post_id']); ?>" target="_blank">
                                <?php echo esc_html($rec['title']); ?>
                            </a>
                        </td>
                        <td><?php echo esc_html($rec['message']); ?></td>
                        <td>
                            <a href="<?php echo get_edit_post_link($rec['post_id']); ?>" class="button">Edit</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
            
            <h2>All Pages by Link Score</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Page</th>
                        <th>Type</th>
                        <th>Incoming</th>
                        <th>Outgoing</th>
                        <th>Score</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($analysis, 0, 50) as $page): ?>
                    <tr class="<?php echo $page['incoming'] === 0 ? 'orphan-row' : ''; ?>">
                        <td>
                            <a href="<?php echo esc_url($page['url']); ?>" target="_blank">
                                <?php echo esc_html($page['title']); ?>
                            </a>
                        </td>
                        <td><?php echo esc_html($page['type']); ?></td>
                        <td><?php echo $page['incoming']; ?></td>
                        <td><?php echo $page['outgoing']; ?></td>
                        <td><strong><?php echo round($page['score'], 1); ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <style>
            .equity-stats { display: flex; gap: 20px; margin: 20px 0; }
            .stat-box { background: #fff; border: 1px solid #ccc; padding: 20px; border-radius: 8px; text-align: center; min-width: 150px; }
            .stat-box h3 { font-size: 36px; margin: 0; color: #0073aa; }
            .stat-box.warning h3 { color: #d63638; }
            .stat-box p { margin: 10px 0 0; color: #666; }
            .orphan-row { background: #fff8e5 !important; }
        </style>
        <?php
    }
}

new Chroma_Link_Equity_Analyzer();
