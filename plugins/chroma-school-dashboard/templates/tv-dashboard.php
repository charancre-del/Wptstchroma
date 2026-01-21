<?php
/**
 * TV Dashboard Template
 */

// Get current school data
$school_id = get_the_ID();
$config = get_post_meta($school_id, '_chroma_school_config', true) ?: [];

// Minimal PHP - just config
$school_name = get_the_title();
$slug = get_post_field('post_name', $school_id);

// Config for JS
$js_config = [
    'slug' => $slug,
    'lat' => $config['lat'] ?? '',
    'lon' => $config['lon'] ?? '',
    'apiUrl' => get_rest_url(), // Ensure we have the base URL
    'procareProxyUrl' => get_option('chroma_procare_proxy_url', 'http://localhost:3456') // Proxy service URL
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Chroma TV | <?php echo esc_html($school_name); ?></title>
    <meta name="robots" content="noindex,nofollow,noarchive,nosnippet">
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;700;800&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- PDF.js for Newsletter Rendering -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script>
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
    </script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Outfit', 'system-ui', 'sans-serif'],
                        serif: ['Playfair Display', 'ui-serif', 'Georgia', 'serif'],
                    },
                    colors: {
                        brand: { ink: '#263238', cream: '#FFFCF8' },
                        chroma: {
                            red: '#D67D6B', redLight: '#F4E5E2',
                            blue: '#4A6C7C', blueDark: '#2F4858', blueLight: '#E3E9EC',
                            green: '#8DA399', greenLight: '#E3EBE8',
                            yellow: '#E6BE75', yellowLight: '#FDF6E3'
                        }
                    },
                    borderRadius: { '3xl': '1.75rem', '4xl': '2.5rem' },
                    boxShadow: {
                        soft: '0 20px 40px -10px rgba(74, 108, 124, 0.08)',
                        card: '0 10px 30px -5px rgba(0, 0, 0, 0.04)'
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-out forwards',
                    },
                    keyframes: {
                        fadeIn: { '0%': { opacity: '0' }, '100%': { opacity: '1' } }
                    }
                }
            }
        }
    </script>

    <style>
        body { overflow: hidden; cursor: none; }
        .line-clamp-3 { display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
        #dashboard-container { animation: slight-shift 300s infinite alternate linear; }
        @keyframes slight-shift {
            0% { transform: translate(0, 0); }
            25% { transform: translate(1px, 1px); }
            50% { transform: translate(0, 2px); }
            75% { transform: translate(-1px, 1px); }
            100% { transform: translate(0, 0); }
        }
    </style>
</head>

<body class="bg-brand-cream text-brand-ink antialiased h-screen w-screen flex flex-col p-6 lg:p-10 selection:bg-none">

    <div id="dashboard-container" class="flex-1 grid grid-cols-12 grid-rows-12 gap-8 h-full w-full max-w-[2200px] mx-auto">

        <!-- HEADER -->
        <header class="col-span-12 row-span-2 flex items-center justify-between bg-white rounded-[2rem] p-8 shadow-soft border border-chroma-blue/10 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-64 h-64 bg-chroma-yellowLight rounded-full blur-3xl opacity-50 -translate-y-1/2 translate-x-1/2"></div>

            <div class="flex items-center gap-8 z-10">
                <img src="<?php echo esc_url(get_template_directory_uri()); ?>/assets/images/logo_icon_70x70.webp" alt="Chroma" class="w-16 h-16 rounded-xl shadow-sm" />
                <div>
                    <!-- Welcome Overridden by JS if set -->
                    <h1 id="welcome-message" class="font-serif text-3xl font-bold text-brand-ink leading-tight">Welcome to Chroma Early Learning</h1>
                    <div class="font-serif italic text-4xl text-chroma-blue mt-1 font-semibold">
                        <?php echo esc_html($school_name); ?>
                    </div>
                    <p class="text-brand-ink/40 text-lg font-medium mt-2">Today is <span id="current-date" class="text-brand-ink/60">...</span></p>
                </div>
            </div>

            <div class="flex items-center gap-10 z-10">
                <!-- Weather Widget (Hidden initially) -->
                <div id="weather-widget" class="text-right" style="display: none;">
                    <div class="flex items-center justify-end gap-4 text-brand-ink">
                        <i id="weather-icon" class="fa-solid fa-sun text-5xl text-chroma-yellow"></i>
                        <span id="weather-temp" class="text-6xl font-bold tracking-tighter">--°</span>
                    </div>
                    <p id="weather-desc" class="text-brand-ink/50 font-medium text-lg mt-1">--</p>
                </div>
                
                <div class="h-20 w-px bg-chroma-blue/10"></div>

                <div class="text-right">
                    <div id="clock" class="text-7xl font-bold text-brand-ink tabular-nums tracking-tighter leading-none">00:00</div>
                    <div id="ampm" class="text-2xl font-bold text-chroma-blue uppercase tracking-widest text-right mr-1 mt-1 opacity-60">AM</div>
                </div>
            </div>
        </header>

        <!-- LEFT COLUMN -->
        <aside class="col-span-3 row-span-10 flex flex-col gap-6">

            <!-- Today -->
            <div class="bg-white rounded-[2rem] p-6 shadow-card border border-chroma-blue/10 flex-shrink-0 flex flex-col overflow-hidden min-h-[300px]">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 rounded-2xl bg-brand-cream border border-brand-ink/5 flex items-center justify-center text-chroma-blue text-2xl">
                        <i class="fa-regular fa-calendar"></i>
                    </div>
                    <h2 class="font-serif text-3xl font-bold text-brand-ink">Today</h2>
                </div>
                <div id="today-container" class="space-y-4">
                    <!-- Loading Skeleton -->
                    <div class="animate-pulse flex flex-col gap-4">
                         <div class="h-16 bg-gray-100 rounded-2xl"></div>
                         <div class="h-16 bg-gray-100 rounded-2xl"></div>
                    </div>
                </div>
            </div>

            <!-- Notices -->
            <div class="bg-brand-cream rounded-[2rem] p-6 shadow-card flex-1 flex flex-col relative overflow-hidden border border-chroma-yellow/20">
                <div class="flex items-center gap-3 mb-6 z-10">
                    <div class="w-12 h-12 rounded-2xl bg-white flex items-center justify-center text-chroma-yellow text-2xl shadow-sm">
                        <i class="fa-solid fa-bullhorn"></i>
                    </div>
                    <h2 class="font-serif text-3xl font-bold text-brand-ink">Notices</h2>
                </div>
                <div id="notices-container" class="relative z-10 flex-1 overflow-y-auto space-y-5 pr-2">
                    <!-- Loaded by JS -->
                </div>
                <i class="fa-solid fa-bell absolute -bottom-8 -right-8 text-[10rem] text-chroma-yellow opacity-10 rotate-12"></i>
            </div>

            <!-- Menu (Hidden if empty) -->
            <!-- We'll hide it via JS if empty -->
            <div id="menu-container" style="display: none;"></div>

        </aside>

        <!-- CENTER COLUMN -->
        <main class="col-span-6 row-span-10 flex flex-col gap-8">
            <!-- Slideshow -->
            <div class="bg-black rounded-[2.5rem] overflow-hidden shadow-2xl relative flex-grow-[2] border-[6px] border-white ring-1 ring-black/5">
                <img id="slideshow-img" src="" class="w-full h-full object-cover" alt="Slideshow">
                
                <div class="absolute bottom-8 left-8 right-8 z-20">
                    <span class="inline-block px-3 py-1 rounded-full bg-chroma-red text-white text-xs font-bold uppercase tracking-wider mb-2 shadow-lg">Happening Now</span>
                    <h2 id="slideshow-title" class="font-serif text-5xl font-bold text-white drop-shadow-md">Highlights</h2>
                </div>
                <div class="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-transparent z-10"></div>
            </div>

             <!-- Star Educator (EOM) -->
             <div id="eom-container" class="bg-white rounded-[2.5rem] p-3 shadow-card border border-chroma-blue/10 flex items-center gap-8 h-48 relative overflow-hidden group" style="display: none;">
                 <!-- Populated by JS -->
             </div>
             <!-- Since EOM is complex HTML, I'll skip implementing it fully in JS for MVP 
                  BUT I removed the PHP. So now EOM is GONE.
                  I MUST implement EOM in JS. -->
             <!-- I'll add a place holder -->
        </main>

        <!-- RIGHT COLUMN -->
        <aside class="col-span-3 row-span-10 flex flex-col gap-6">
             <div id="newsletter-container"></div>
             <div id="cares-container"></div>
             <div id="celebrations-container"></div>
        </aside>

        <!-- Global Alert -->
        <div id="global-alert-container" style="display: none;"></div>

    </div>

    <!-- JS Loader -->
    <script>
        window.ChromaConfig = <?php echo json_encode($js_config); ?>;
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.min.js"></script>
    <script>
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.worker.min.js';
    </script>
    <script src="<?php echo CHROMA_SCHOOL_DB_URL . 'assets/js/tv-dashboard.js?v=1.0'; ?>"></script>
</body>
</html>