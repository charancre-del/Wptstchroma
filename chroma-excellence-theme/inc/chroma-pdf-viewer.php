<?php
/**
 * Chroma PDF Shortcode & Viewer
 * 
 * Registers [chroma_pdf] and handles the markup for the viewer modal.
 * 
 * @package Chroma_Excellence
 */

// Define Shortcode
function chroma_pdf_shortcode($atts) {
    $args = shortcode_atts(array(
        'url' => '',
        'title' => 'Document',
        'button_text' => 'Read Now',
        'cover' => '', // URL to cover image
        'color' => 'chroma-blue', // Theme color for icon
    ), $atts);

    if (empty($args['url'])) return '';

    $unique_id = uniqid('pdf_');

    // Default cover if none provided
    if (empty($args['cover'])) {
        // Generate a generic document card look
        $cover_html = '<div class="w-full h-full bg-brand-cream flex items-center justify-center text-4xl text-' . esc_attr($args['color']) . '"><i class="fa-solid fa-file-pdf"></i></div>';
    } else {
        $cover_html = '<img src="' . esc_url($args['cover']) . '" alt="' . esc_attr($args['title']) . '" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">';
    }

    ob_start();
    ?>
    <div class="chroma-pdf-card group relative bg-white rounded-2xl shadow-card overflow-hidden border border-brand-ink/5 hover:-translate-y-1 transition-transform duration-300">
        <!-- Trigger Link -->
        <a href="#" 
           class="chroma-pdf-trigger absolute inset-0 z-20" 
           data-pdf-url="<?php echo esc_url($args['url']); ?>" 
           data-pdf-title="<?php echo esc_attr($args['title']); ?>"
           aria-label="<?php printf(__('View %s', 'chroma-excellence'), esc_attr($args['title'])); ?>">
        </a>

        <!-- Cover/Preview -->
        <div class="h-48 relative overflow-hidden">
            <div class="absolute inset-0 bg-brand-ink/0 group-hover:bg-brand-ink/10 transition-colors z-10"></div>
            <?php echo $cover_html; ?>
            
            <!-- Read Badge -->
            <div class="absolute bottom-3 right-3 z-10 bg-white/90 backdrop-blur px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider text-brand-ink shadow-sm opacity-0 group-hover:opacity-100 transform translate-y-2 group-hover:translate-y-0 transition-all duration-300">
                <i class="fa-solid fa-book-open mr-1 text-<?php echo esc_attr($args['color']); ?>"></i> <?php echo esc_html($args['button_text']); ?>
            </div>
        </div>

        <!-- Meta -->
        <div class="p-5">
            <h3 class="font-bold text-lg text-brand-ink leading-tight mb-1 group-hover:text-<?php echo esc_attr($args['color']); ?> transition-colors">
                <?php echo esc_html($args['title']); ?>
            </h3>
            <p class="text-xs text-brand-ink/60 uppercase tracking-wider font-bold">
                <i class="fa-solid fa-file-pdf mr-1"></i> PDF Document
            </p>
        </div>
    </div>
    <?php
    
    return ob_get_clean();
}
add_shortcode('chroma_pdf', 'chroma_pdf_shortcode');

// hooks moved outside to ensure they run even if shortcode isn't used (e.g. manual triggers)
add_action('wp_footer', 'chroma_render_pdf_modal');
add_action('wp_enqueue_scripts', 'chroma_enqueue_pdf_assets');

// Enqueue Assets
function chroma_enqueue_pdf_assets() {
    wp_register_script('chroma-pdf-viewer', get_template_directory_uri() . '/assets/js/chroma-pdf-viewer.js', array(), '1.0.0', true);
    
    // Config for JS
    $config = array(
        'pdfJsUrl' => get_template_directory_uri() . '/assets/js/pdf/pdf.min.js',
        'pdfWorkerUrl' => get_template_directory_uri() . '/assets/js/pdf/pdf.worker.min.js'
    );
    wp_localize_script('chroma-pdf-viewer', 'chromaPdfConfig', $config);
    
    wp_enqueue_script('chroma-pdf-viewer');
}

// Render Global Modal (Once)
function chroma_render_pdf_modal() {
    // Only render once
    if (defined('CHROMA_PDF_MODAL_RENDERED')) return;
    define('CHROMA_PDF_MODAL_RENDERED', true);
    ?>
    <div id="chroma-pdf-modal" class="fixed inset-0 z-[200] hidden" role="dialog" aria-modal="true">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-brand-ink/90 backdrop-blur-md transition-opacity" id="chroma-pdf-backdrop"></div>

        <!-- Viewer Container -->
        <div class="absolute inset-2 md:inset-10 bg-transparent flex flex-col pointer-events-none">
            
            <!-- Toolbar -->
            <div class="bg-white rounded-full shadow-2xl mx-auto mb-4 px-6 py-3 flex items-center gap-6 pointer-events-auto animate-fade-in-down">
                <!-- Title -->
                <div class="hidden md:block border-r border-gray-200 pr-6 mr-2">
                    <h3 class="font-serif font-bold text-brand-ink" id="chroma-pdf-title">Document Viewer</h3>
                </div>

                <!-- Pagination -->
                <div class="flex items-center gap-4">
                    <button id="chroma-pdf-prev" class="w-10 h-10 rounded-full hover:bg-gray-100 flex items-center justify-center transition-colors disabled:opacity-30 disabled:cursor-not-allowed text-brand-ink">
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>
                    <span class="text-sm font-mono text-brand-ink/70">
                        <span id="chroma-pdf-page-num" class="font-bold text-brand-ink">1</span> / <span id="chroma-pdf-page-count">--</span>
                    </span>
                    <button id="chroma-pdf-next" class="w-10 h-10 rounded-full hover:bg-gray-100 flex items-center justify-center transition-colors disabled:opacity-30 disabled:cursor-not-allowed text-brand-ink">
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-2 border-l border-gray-200 pl-6 ml-2">
                    <a href="#" id="chroma-pdf-download" download class="w-10 h-10 rounded-full hover:bg-chroma-blue hover:text-white flex items-center justify-center transition-colors text-brand-ink" title="Download">
                        <i class="fa-solid fa-download"></i>
                    </a>
                    <button id="chroma-pdf-close" class="w-10 h-10 rounded-full hover:bg-chroma-red hover:text-white flex items-center justify-center transition-colors text-brand-ink" title="Close">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>
            </div>

            <!-- Canvas Area -->
            <div class="flex-grow relative flex items-center justify-center pointer-events-auto" id="chroma-pdf-canvas-container">
                <!-- Loader -->
                <div id="chroma-pdf-loader" class="absolute z-10 flex flex-col items-center text-white">
                    <div class="w-12 h-12 border-4 border-white/30 border-t-white rounded-full animate-spin mb-4"></div>
                    <span class="text-sm font-bold tracking-widest uppercase">Loading Document...</span>
                </div>
                
                <!-- PDF Render Wrapper (Scrollable if zoomed, but we fit to page mostly) -->
                <div class="max-w-full max-h-full overflow-auto custom-scrollbar rounded shadow-2xl">
                    <canvas id="chroma-pdf-canvas" class="block bg-white"></canvas>
                </div>
            </div>

        </div>
    </div>
    <style>
        /* Robust CSS Fallbacks for PDF Viewer */
        #chroma-pdf-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 999999 !important;
            background: rgba(18, 38, 48, 0.95); /* brand-ink equivalent */
            backdrop-filter: blur(10px);
        }
        
        #chroma-pdf-modal:not(.hidden) {
            display: flex !important;
            flex-direction: column;
        }

        #chroma-pdf-modal .animate-fade-in-down { 
            animation: fadeInDown 0.3s ease-out forwards; 
        }
        
        @keyframes fadeInDown { 
            from { opacity: 0; transform: translateY(-20px); } 
            to { opacity: 1; transform: translateY(0); } 
        }

        /* Ensure mobile readability and canvas fit */
        #chroma-pdf-canvas {
            max-width: 100%;
            height: auto !important;
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 8px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 4px;
        }
    </style>
    <?php
}
