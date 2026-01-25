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
    'musicUrl' => $config['music_url'] ?? ''
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
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;700;800&family=Playfair+Display:wght@600;700;800&display=swap"
        rel="stylesheet">
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
        body {
            overflow: hidden;
            cursor: none;
        }

        .line-clamp-3 {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        #dashboard-container {
            animation: slight-shift 300s infinite alternate linear;
        }

        @keyframes slight-shift {
            0% {
                transform: translate(0, 0);
            }

            25% {
                transform: translate(1px, 1px);
            }

            50% {
                transform: translate(0, 2px);
            }

            75% {
                transform: translate(-1px, 1px);
            }

            100% {
                transform: translate(0, 0);
            }
        }

        @keyframes scroll-info {
            0% {
                transform: translateX(0);
            }

            100% {
                transform: translateX(-50%);
            }
        }

        .animate-scroll-info {
            display: flex;
            animation: scroll-info 300s linear infinite;
        }

        #audio-container iframe {
            width: 100px;
            height: 100px;
            opacity: 0.01;
            pointer-events: none;
            position: fixed;
            bottom: -500px;
            left: 0;
            z-index: -1;
        }

        /* Slideshow Animation */
        .slide-layer {
            transition: opacity 2s ease-in-out;
            background-size: cover;
            background-position: center;
        }

        .ken-burns {
            animation: kenburns 20s infinite alternate linear;
        }

        @keyframes kenburns {
            0% {
                transform: scale(1);
            }

            100% {
                transform: scale(1.1);
            }
        }
    </style>
</head>

<body class="bg-brand-cream text-brand-ink antialiased h-screen w-screen flex flex-col p-6 lg:p-10 selection:bg-none">

    <div id="dashboard-container"
        class="flex-1 grid grid-cols-12 grid-rows-12 gap-8 h-full w-full max-w-[2200px] mx-auto">

        <!-- HEADER -->
        <header
            class="col-span-12 row-span-2 flex items-center justify-between bg-white rounded-[2rem] p-8 shadow-soft border border-chroma-blue/10 relative overflow-hidden">
            <div
                class="absolute top-0 right-0 w-64 h-64 bg-chroma-yellowLight rounded-full blur-3xl opacity-50 -translate-y-1/2 translate-x-1/2">
            </div>

            <!-- Left: School Info -->
            <div class="flex items-center gap-6 z-10 w-1/4">
                <img src="<?php echo esc_url(CHROMA_SCHOOL_DB_URL); ?>assets/images/chroma_hex_logo.png" alt="Chroma"
                    class="w-14 h-14 object-contain shadow-sm" />
                <div class="min-w-0">
                    <h1 id="welcome-message" class="font-serif text-xl font-bold text-brand-ink leading-tight truncate">
                        Welcome to Chroma</h1>
                    <div class="font-serif italic text-2xl text-chroma-blue truncate font-semibold">
                        <?php echo esc_html($school_name); ?>
                    </div>
                    <p class="text-brand-ink/40 text-[10px] font-medium tracking-wider uppercase mt-1">Today is <span
                            id="current-date" class="text-brand-ink/60">...</span></p>
                </div>
            </div>

            <!-- Center: Notices (Direct in Header) -->
            <div class="flex-1 h-full flex items-center px-10 border-x border-chroma-blue/5 z-10">
                <div class="flex items-center gap-4 w-full">
                    <div
                        class="w-12 h-12 rounded-2xl bg-chroma-yellow/10 flex items-center justify-center text-chroma-yellow text-2xl shrink-0">
                        <i class="fa-solid fa-bullhorn"></i>
                    </div>
                    <div id="notices-container" class="flex-1 overflow-hidden">
                        <!-- Loaded by JS as a horizontal scroll or single line -->
                        <div class="animate-pulse flex gap-4">
                            <div class="h-6 w-48 bg-gray-50 rounded-lg"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: System Info -->
            <div class="flex items-center gap-10 z-10 pl-10">
                <div id="weather-widget" class="text-right" style="display: none;">
                    <div class="flex items-center justify-end gap-3 text-brand-ink">
                        <i id="weather-icon" class="fa-solid fa-sun text-4xl text-chroma-yellow"></i>
                        <span id="weather-temp" class="text-5xl font-bold tracking-tighter">--°</span>
                    </div>
                </div>

                <div class="h-16 w-px bg-chroma-blue/10"></div>

                <div class="text-right">
                    <div id="clock"
                        class="text-6xl font-bold text-brand-ink tabular-nums tracking-tighter leading-none">00:00</div>
                    <div id="ampm"
                        class="text-xl font-bold text-chroma-blue uppercase tracking-widest text-right mt-1 opacity-60">
                        AM</div>
                </div>
            </div>
        </header>

        <!-- LEFT COLUMN: Sched & Events -->
        <aside class="col-span-3 row-span-10 flex flex-col gap-6">
            <!-- Today -->
            <div
                class="bg-white rounded-[2rem] p-6 shadow-card border border-chroma-blue/10 flex-shrink-0 flex flex-col overflow-hidden min-h-[350px]">
                <div class="flex items-center gap-3 mb-6">
                    <div
                        class="w-10 h-10 rounded-xl bg-brand-cream border border-brand-ink/5 flex items-center justify-center text-chroma-blue text-xl">
                        <i class="fa-regular fa-calendar"></i>
                    </div>
                    <h2 class="font-serif text-2xl font-bold text-brand-ink">Today</h2>
                </div>
                <div id="today-container" class="space-y-4">
                    <!-- Loading Skeleton -->
                </div>
            </div>

            <!-- Celebrations -->
            <div
                class="bg-white rounded-[2rem] p-6 shadow-card border border-chroma-blue/10 flex-1 flex flex-col relative overflow-hidden group">
                <div class="flex items-center gap-3 mb-6 z-10">
                    <div
                        class="w-10 h-10 rounded-xl bg-rose-50 flex items-center justify-center text-rose-500 text-xl shadow-sm border border-rose-100">
                        <i class="fa-solid fa-cake-candles"></i>
                    </div>
                    <h2 class="font-serif text-2xl font-bold text-brand-ink">Celebrations</h2>
                </div>
                <div id="celebrations-container" class="relative z-10 flex-1 flex flex-col justify-center">
                    <!-- Loaded by JS -->
                </div>
                <div
                    class="absolute -bottom-6 -right-6 text-rose-500/5 text-9xl rotate-12 transition-transform group-hover:scale-110 duration-700">
                    <i class="fa-solid fa-gift"></i>
                </div>
            </div>

            <div id="menu-container" style="display: none;"></div>
        </aside>

        <!-- CENTER COLUMN: Media -->
        <main class="col-span-6 row-span-10 flex flex-col gap-8">
            <!-- Slideshow -->
            <div id="slideshow-container"
                class="bg-black rounded-[2.5rem] overflow-hidden shadow-2xl relative flex-1 border-[6px] border-white ring-1 ring-black/5">
                <div id="slide-layer-1" class="slide-layer absolute inset-0 opacity-100 ken-burns"></div>
                <div id="slide-layer-2" class="slide-layer absolute inset-0 opacity-0"></div>

                <div class="absolute bottom-10 left-10 right-10 z-20">
                    <span
                        class="inline-block px-3 py-1 rounded-full bg-chroma-red text-white text-xs font-bold uppercase tracking-wider mb-3 shadow-lg">Happening
                        Now</span>
                    <h2 id="slideshow-title"
                        class="font-serif text-6xl font-black text-white drop-shadow-xl tracking-tight">Highlights</h2>
                </div>
                <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent z-10"></div>
            </div>

            <!-- Star Educator (EOM) -->
            <div id="eom-container"
                class="bg-white rounded-[2.5rem] p-3 shadow-card border border-chroma-blue/10 flex items-center gap-8 h-[220px] shrink-0 relative overflow-hidden group"
                style="display: none;">
                <!-- Populated by JS -->
            </div>
        </main>

        <!-- RIGHT COLUMN: Newsletter -->
        <aside class="col-span-3 row-span-10 flex flex-col h-full">
            <div id="newsletter-container" class="h-full flex flex-col"></div>
            <!-- Hidden containers for logic -->
            <div id="cares-container" style="display: none;"></div>
        </aside>

        <!-- Background Audio -->
        <div id="audio-container"></div>
        <div id="audio-trigger-overlay"
            class="fixed inset-0 z-[100] bg-brand-ink/40 backdrop-blur-sm flex flex-col items-center justify-center cursor-pointer transition-opacity duration-500 overflow-hidden">
            <div class="bg-white p-12 rounded-[3rem] shadow-2xl flex flex-col items-center gap-6 animate-bounce">
                <div class="w-24 h-24 rounded-full bg-chroma-blue flex items-center justify-center text-white text-5xl">
                    <i class="fa-solid fa-play ml-2"></i>
                </div>
                <div class="text-center">
                    <p class="font-serif text-3xl font-black text-brand-ink">Tap to Start Dash</p>
                    <p class="text-brand-ink/40 font-bold uppercase tracking-widest text-xs mt-2">Enables background
                        music & animations</p>
                </div>
            </div>
        </div>

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