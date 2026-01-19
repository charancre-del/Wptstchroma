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
    <div id="chroma-pdf-modal" class="fixed inset-0 hidden" role="dialog" aria-modal="true">
        <!-- Backdrop with brand-ink styling -->
        <div class="absolute inset-0 bg-[#0F1E26]/95 backdrop-blur-xl transition-opacity" id="chroma-pdf-backdrop"></div>

        <!-- Viewer Container -->
        <div class="absolute inset-0 md:inset-6 flex flex-col pointer-events-none p-4 md:p-10">
            
            <!-- Branded Pro Toolbar -->
            <div id="chroma-pdf-toolbar" class="bg-brand-ink text-white rounded-2xl shadow-[0_20px_50px_rgba(0,0,0,0.3)] mx-auto mb-6 px-4 md:px-8 py-4 flex items-center justify-between gap-4 md:gap-10 pointer-events-auto border border-white/10 animate-fade-in-down w-full max-w-4xl">
                <!-- Branding & Title -->
                <div class="flex items-center gap-4 border-r border-white/10 pr-6 mr-2">
                    <div class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center">
                        <i class="fa-solid fa-file-pdf text-chroma-red"></i>
                    </div>
                    <div class="hidden md:block">
                        <span class="text-[10px] uppercase tracking-[0.2em] font-bold text-white/50 block mb-0.5">Pro Viewer</span>
                        <h3 class="font-serif font-bold text-sm text-white truncate max-w-[200px]" id="chroma-pdf-title">Document</h3>
                    </div>
                </div>

                <!-- Pagination Tools -->
                <div class="flex items-center gap-2 md:gap-4 bg-white/5 rounded-full px-4 py-1.5 border border-white/5">
                    <button id="chroma-pdf-prev" class="w-8 h-8 rounded-full flex items-center justify-center transition-all disabled:opacity-10 disabled:cursor-not-allowed" title="Previous Page">
                        <i class="fa-solid fa-arrow-left"></i>
                    </button>
                    <span class="text-xs md:text-sm font-mono tracking-[0.2em] text-white/90 px-2 min-w-[60px] text-center">
                        <span id="chroma-pdf-page-num" class="font-bold text-white">1</span> <span class="mx-1 opacity-40">/</span> <span id="chroma-pdf-page-count">--</span>
                    </span>
                    <button id="chroma-pdf-next" class="w-8 h-8 rounded-full flex items-center justify-center transition-all disabled:opacity-10 disabled:cursor-not-allowed" title="Next Page">
                        <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center gap-2 border-l border-white/10 pl-6 ml-2">
                    <a href="#" id="chroma-pdf-download" download class="w-10 h-10 rounded-full hover:bg-chroma-blue flex items-center justify-center transition-all text-white/80 hover:text-white" title="Download">
                        <i class="fa-solid fa-download"></i>
                    </a>
                    <button id="chroma-pdf-close" class="w-10 h-10 rounded-full hover:bg-chroma-red flex items-center justify-center transition-all text-white/80 hover:text-white" title="Close Content">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>
            </div>

            <!-- Canvas Rendering Area -->
            <div class="flex-grow relative flex items-start justify-center overflow-hidden pointer-events-auto" id="chroma-pdf-canvas-container">
                <!-- Loader Widget -->
                <div id="chroma-pdf-loader" class="absolute inset-0 z-[10] flex flex-col items-center justify-center text-white bg-brand-ink/40 backdrop-blur-sm rounded-3xl">
                    <div class="w-14 h-14 border-4 border-white/10 border-t-chroma-red rounded-full animate-spin mb-6"></div>
                    <span class="text-xs font-bold tracking-[0.3em] uppercase opacity-80">Enhancing Document...</span>
                </div>
                
                <!-- PDF Render Paper -->
                <div class="max-w-full max-h-full overflow-auto custom-scrollbar rounded-xl shadow-[0_30px_60px_-15px_rgba(0,0,0,0.5)] bg-slate-100 p-1 md:p-4">
                    <canvas id="chroma-pdf-canvas" class="block mx-auto rounded shadow-sm bg-white"></canvas>
                </div>
            </div>

        </div>
    </div>
    <style>
        /* Force high z-index and branded aesthetics */
        #chroma-pdf-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 9999999 !important;
            background: rgba(15, 30, 38, 0.98);
        }
        
        #chroma-pdf-modal:not(.hidden) {
            display: flex !important;
            flex-direction: column;
        }

        #chroma-pdf-toolbar {
            font-family: 'Outfit', 'Inter', sans-serif;
            background: #0F1E26 !important;
            border: 1px solid rgba(255,255,255,0.1);
        }

        #chroma-pdf-toolbar h3 {
            font-family: 'Outfit', 'Playfair Display', serif;
        }

        /* Clear Arrow Navigation Styling */
        #chroma-pdf-prev, #chroma-pdf-next {
            background: rgba(255, 255, 255, 0.05);
            color: #FFFFFF !important;
            opacity: 1 !important;
            display: flex !important;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(255,255,255,0.1);
        }

        #chroma-pdf-prev i, #chroma-pdf-next i {
            color: #FFFFFF !important;
            font-size: 16px;
        }

        #chroma-pdf-prev:hover, #chroma-pdf-next:hover {
            background: #1DABCC !important; /* chroma-blue */
            border-color: #1DABCC;
            transform: scale(1.15);
        }

        #chroma-pdf-prev:disabled, #chroma-pdf-next:disabled {
            opacity: 0.1 !important;
            background: transparent !important;
            border-color: transparent !important;
            transform: none !important;
        }

        /* Ensure wrapper takes space even before render */
        #chroma-pdf-canvas-container > div:last-of-type {
            min-height: 400px;
            width: 100%;
        }

        .animate-fade-in-down { 
            animation: fadeInDown 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards; 
        }
        
        @keyframes fadeInDown { 
            from { opacity: 0; transform: translateY(-40px); } 
            to { opacity: 1; transform: translateY(0); } 
        }

        /* Responsive Canvas Fit */
        #chroma-pdf-canvas {
            max-width: 100%;
            height: auto !important;
            image-rendering: -webkit-optimize-contrast;
            display: block;
        }

        /* Luxury Scrollbar */
        .custom-scrollbar::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.02);
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            border: 2px solid transparent;
            background-clip: content-box;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.2);
            background-clip: content-box;
        }
    </style>
    <?php
}
