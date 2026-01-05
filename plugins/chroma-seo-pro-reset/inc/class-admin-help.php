<?php
/**
 * Admin Help & Documentation
 * Adds a Help tab to the SEO Dashboard
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Admin_Help
{
    /**
     * Initialize
     */
    public function init()
    {
        add_action('chroma_seo_dashboard_tabs', [$this, 'add_tab']);
        add_action('chroma_seo_dashboard_content', [$this, 'render_content']);
    }

    /**
     * Add Help Tab
     */
    public function add_tab()
    {
        echo '<a href="?page=chroma-seo-dashboard&tab=help" class="nav-tab ' . ($this->is_active() ? 'nav-tab-active' : '') . '">' . __('Help & Guides', 'chroma-excellence') . '</a>';
    }

    /**
     * Check if tab is active
     */
    private function is_active()
    {
        return isset($_GET['tab']) && $_GET['tab'] === 'help';
    }

    /**
     * Render Content
     */
    public function render_content()
    {
        if (!$this->is_active()) {
            return;
        }
        ?>
        <div class="chroma-seo-card">
            <h2><?php _e('Advanced SEO & LLM Guide', 'chroma-excellence'); ?></h2>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">

                <!-- Quick Start -->
                <div class="chroma-doc-section">
                    <h3>🚀 Quick Start</h3>
                    <ul>
                        <li><strong>Page Inspector:</strong> Go to any page/post and look for the "SEO & LLM Inspector" meta box
                            to see what LLMs see.</li>
                        <li><strong>Universal FAQ:</strong> Add Q&A pairs to ANY page using the "FAQ Schema" box. This helps you
                            rank for specific questions.</li>
                        <li><strong>Hreflang:</strong> Link your English and Spanish pages using the "International SEO" box.
                        </li>
                    </ul>
                </div>

                <!-- City Pages -->
                <div class="chroma-doc-section">
                    <h3>🏙️ City Landing Pages</h3>
                    <p>To create a hyperlocal page (e.g., "Childcare in Canton"):</p>
                    <ol>
                        <li>Go to <strong>Cities > Add New</strong> in the admin menu.</li>
                        <li>Enter the city name as the title.</li>
                        <li>Scroll to <strong>"City Landing Configuration"</strong>.</li>
                        <li>Write a unique intro and check the locations to display.</li>
                    </ol>
                </div>

                <!-- KML & Geo -->
                <div class="chroma-doc-section">
                    <h3>🌍 Geo-Optimization</h3>
                    <p>Your KML file is automatically generated at:</p>
                    <code><?php echo home_url('/locations.kml'); ?></code>
                    <p>Submit this URL to Google Earth and other geo-directories.</p>
                </div>

                <!-- Schema Builder -->
                <div class="chroma-doc-section">
                    <h3>📊 Schema Builder</h3>
                    <p><strong>New Feature!</strong> Each page now has intelligent default schemas.</p>
                    <ul>
                        <li><strong>Locations:</strong> Automatically get ChildCare schema with address, phone, description</li>
                        <li><strong>Programs:</strong> Service schema with provider information and service type</li>
                        <li><strong>Pages:</strong> Organization schema for About page</li>
                    </ul>
                    <p>Use the "Schema Builder" tab to view, edit, or add multiple schemas to any page.</p>
                </div>

                <!-- LLM Targeting Tab -->
                <div class="chroma-doc-section">
                    <h3>🎯 LLM Targeting Tab</h3>
                    <p>Centralized control for how AI assistants recommend your pages.</p>
                    <ul>
                        <li><strong>Primary Intent:</strong> Define the main user goal (e.g., "childcare_discovery")</li>
                        <li><strong>Target Queries:</strong> Add natural language queries where LLMs should cite you</li>
                        <li><strong>Key Differentiators:</strong> Unique strengths LLMs can mention</li>
                    </ul>
                    <p>All fields show smart fallbacks when empty. Edit any post type from one dashboard!</p>
                </div>

                <!-- LLM Optimization -->
                <div class="chroma-doc-section">
                    <h3>🤖 LLM Optimization Tips</h3>
                    <ul>
                        <li><strong>Be Specific:</strong> In the "LLM Context" box on locations, define exactly *who* should go
                            there (e.g., "Parents seeking Montessori-inspired care").</li>
                        <li><strong>Use FAQs:</strong> LLMs love direct answers. Phrase your FAQs like real user queries.</li>
                        <li><strong>Citation Facts:</strong> Use the "Citation Facts" box to provide hard data (Capacity,
                            Teacher Ratio) that LLMs can cite.</li>
                    </ul>
                </div>

            </div>
        </div>

        <style>
            .chroma-doc-section {
                background: #fff;
                padding: 20px;
                border: 1px solid #ddd;
                border-radius: 5px;
            }

            .chroma-doc-section h3 {
                margin-top: 0;
                color: #2271b1;
                border-bottom: 1px solid #eee;
                padding-bottom: 10px;
            }

            .chroma-doc-section ul,
            .chroma-doc-section ol {
                margin-left: 20px;
            }

            .chroma-doc-section li {
                margin-bottom: 8px;
            }
        </style>
        <?php
    }
}
