<?php
/**
 * TV Dashboard Template
 */

// Get current school data
$school_id = get_the_ID();
$config = get_post_meta($school_id, '_chroma_school_config', true) ?: [];
$newsletter = get_post_meta($school_id, '_chroma_school_newsletter', true) ?: [];
$eom = get_post_meta($school_id, '_chroma_school_eom', true) ?: [];
$announcements = get_post_meta($school_id, '_chroma_school_announcements', true) ?: [];
$today = get_post_meta($school_id, '_chroma_school_today', true) ?: [];
$menu = get_post_meta($school_id, '_chroma_school_menu', true) ?: '';
$qr_links = get_post_meta($school_id, '_chroma_school_qr', true) ?: [];
$slideshow = get_post_meta($school_id, '_chroma_school_slideshow', true) ?: [];
$youtube = get_post_meta($school_id, '_chroma_school_youtube', true) ?: '';
$welcome_override = get_post_meta($school_id, '_chroma_school_welcome_override', true);

$global_cares = get_option('chroma_global_cares', []);
$global_alert = get_option('chroma_global_alert', []);

// Weather
$weather = Chroma_Weather_Provider::get_weather($config['lat'] ?? null, $config['lon'] ?? null);

// Helpers
$school_name = get_the_title();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Chroma TV | <?php echo esc_html($school_name); ?></title>
    <!-- 4.3 Noindex & Sitemap exclusion -->
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
                        'slide-up': 'slideUp 10s linear infinite',
                        'pulse-slow': 'pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                    },
                    keyframes: {
                        fadeIn: { '0%': { opacity: '0' }, '100%': { opacity: '1' } },
                        slideUp: { '0%': { transform: 'translateY(100%)' }, '100%': { transform: 'translateY(-100%)' } }
                    }
                }
            }
        }
    </script>

    <style>
        /* TV Specific Overrides */
        body {
            overflow: hidden;
            cursor: none;
        }

        /* Hide cursor for TV */
        .line-clamp-3 {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .line-clamp-4 {
            display: -webkit-box;
            -webkit-line-clamp: 4;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* Subtle burn-in prevention movement */
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
    </style>
</head>

<body class="bg-brand-cream text-brand-ink antialiased h-screen w-screen flex flex-col p-6 lg:p-10 selection:bg-none">

    <div id="dashboard-container"
        class="flex-1 grid grid-cols-12 grid-rows-12 gap-8 h-full w-full max-w-[2200px] mx-auto">

        <!-- HEADER ROW (Span 12, Row 1-2) -->
        <header
            class="col-span-12 row-span-2 flex items-center justify-between bg-white rounded-[2rem] p-8 shadow-soft border border-chroma-blue/10 relative overflow-hidden">
            <!-- Decor Blob -->
            <div
                class="absolute top-0 right-0 w-64 h-64 bg-chroma-yellowLight rounded-full blur-3xl opacity-50 -translate-y-1/2 translate-x-1/2">
            </div>

            <div class="flex items-center gap-8 z-10">
                <!-- Color Dots Icon (Mockup style) -->
                <div class="flex gap-1 mr-2">
                    <div class="w-6 h-6 rounded-full bg-chroma-red"></div>
                    <div class="w-6 h-6 rounded-full bg-chroma-yellow"></div>
                    <div class="w-6 h-6 rounded-full bg-chroma-green"></div>
                    <div class="w-6 h-6 rounded-full bg-chroma-blue"></div>
                </div>

                <div>
                    <h1 class="font-serif text-3xl font-bold text-brand-ink leading-tight">
                        <?php if (!empty($welcome_override)): ?>
                            <?php echo esc_html($welcome_override); ?>
                        <?php else: ?>
                            Welcome to Chroma Early Learning
                        <?php endif; ?>
                    </h1>
                    <div class="font-serif italic text-4xl text-chroma-blue mt-1 font-semibold">
                        <!-- School Name, e.g. Marietta East -->
                        <?php echo esc_html($school_name); ?>
                    </div>
                    <p class="text-brand-ink/40 text-lg font-medium mt-2">Today is <span id="current-date"
                            class="text-brand-ink/60">...</span>
                    </p>
                </div>
            </div>

            <!-- Time & Weather Widget -->
            <div class="flex items-center gap-10 z-10">
                <?php if ($weather): ?>
                    <div class="text-right">
                        <div class="flex items-center justify-end gap-4 text-brand-ink">
                            <i
                                class="fa-solid <?php echo ($weather['code'] < 2) ? 'fa-sun' : 'fa-cloud-sun'; ?> text-5xl text-chroma-yellow"></i>
                            <span
                                class="text-6xl font-bold tracking-tighter"><?php echo esc_html($weather['temp']); ?>°</span>
                        </div>
                        <p class="text-brand-ink/50 font-medium text-lg mt-1">
                            <?php echo esc_html($weather['description']); ?>
                        </p>
                    </div>
                    <!-- Separator -->
                    <div class="h-20 w-px bg-chroma-blue/10"></div>
                <?php endif; ?>

                <div class="text-right">
                    <div id="clock"
                        class="text-7xl font-bold text-brand-ink tabular-nums tracking-tighter leading-none">00:00</div>
                    <div id="ampm"
                        class="text-2xl font-bold text-chroma-blue uppercase tracking-widest text-right mr-1 mt-1 opacity-60">
                        AM</div>
                </div>
            </div>
        </header>

        <!-- LEFT COLUMN (Span 3, Row 3-12) -->
        <aside class="col-span-3 row-span-10 flex flex-col gap-6">

            <!-- Today At A Glance -->
            <div
                class="bg-white rounded-[2rem] p-6 shadow-card border border-chroma-blue/10 flex-shrink-0 flex flex-col overflow-hidden min-h-[300px]">
                <div class="flex items-center gap-3 mb-6">
                    <div
                        class="w-12 h-12 rounded-2xl bg-brand-cream border border-brand-ink/5 flex items-center justify-center text-chroma-blue text-2xl">
                        <i class="fa-regular fa-calendar"></i>
                    </div>
                    <h2 class="font-serif text-3xl font-bold text-brand-ink">Today</h2>
                </div>
                <div class="space-y-4">
                    <?php if (!empty($today) && is_array($today)): ?>
                        <?php foreach ($today as $item): ?>
                            <div class="flex flex-col p-4 rounded-2xl bg-white border border-chroma-blue/5 shadow-sm">
                                <?php if (!empty($item['time'])): ?>
                                    <span
                                        class="text-chroma-blue font-bold text-lg mb-1"><?php echo esc_html($item['time']); ?></span>
                                <?php endif; ?>
                                <span
                                    class="font-bold text-xl text-brand-ink leading-tight"><?php echo esc_html($item['label']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- Empty State -->
                        <div class="p-6 rounded-2xl bg-brand-cream text-center opacity-60">
                            <p class="font-medium">Have a wonderful day!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Notices / Announcements -->
            <div
                class="bg-brand-cream rounded-[2rem] p-6 shadow-card flex-1 flex flex-col relative overflow-hidden border border-chroma-yellow/20">

                <div class="flex items-center gap-3 mb-6 z-10">
                    <div
                        class="w-12 h-12 rounded-2xl bg-white flex items-center justify-center text-chroma-yellow text-2xl shadow-sm">
                        <i class="fa-solid fa-bullhorn"></i>
                    </div>
                    <h2 class="font-serif text-3xl font-bold text-brand-ink">Notices</h2>
                </div>

                <div class="relative z-10 flex-1 overflow-y-auto space-y-5 pr-2">
                    <?php if (!empty($announcements) && is_array($announcements)): ?>
                        <?php foreach ($announcements as $notice): ?>
                            <div class="animate-fade-in">
                                <?php if (($notice['priority'] ?? 'normal') === 'high'): ?>
                                    <span
                                        class="inline-block px-3 py-1 rounded-lg bg-chroma-red text-white text-xs font-extra-bold uppercase tracking-wider mb-2">Important</span>
                                <?php endif; ?>
                                <h3 class="font-bold text-xl leading-tight mb-2 text-brand-ink">
                                    <?php echo esc_html($notice['title']); ?>
                                </h3>
                                <p class="text-brand-ink/70 text-base leading-relaxed"><?php echo esc_html($notice['body']); ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <!-- Decor Icon -->
                <i
                    class="fa-solid fa-bell absolute -bottom-8 -right-8 text-[10rem] text-chroma-yellow opacity-10 rotate-12"></i>
            </div>

            <!-- Weekly Menu (Black Card) -->
            <?php if (!empty($menu)): ?>
                <div
                    class="bg-brand-ink rounded-[2rem] p-6 shadow-card text-white flex gap-4 items-center h-48 relative overflow-hidden">
                    <div class="flex-1 z-10">
                        <h3 class="font-serif text-2xl font-bold mb-1">Weekly<br>Menu</h3>

                        <div
                            class="mt-4 inline-flex items-center gap-2 text-chroma-yellow text-xs font-bold uppercase tracking-widest">
                            <i class="fa-solid fa-utensils"></i> Scan to View
                        </div>
                    </div>
                    <!-- QR Code container -->
                    <div class="w-28 h-28 bg-white rounded-2xl p-2 shrink-0 z-10 relative shadow-lg">
                        <?php
                        $menu_url = filter_var($menu, FILTER_VALIDATE_URL) ? $menu : '#';
                        $qr_src = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($menu_url);
                        ?>
                        <img src="<?php echo esc_url($qr_src); ?>" class="w-full h-full object-contain" alt="Menu QR">
                        <!-- Center icon in QR -->
                        <!-- <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                            <div class="w-6 h-6 bg-white rounded-full flex items-center justify-center p-1">
                                <i class="fa-solid fa-utensils text-brand-ink text-xs"></i>
                            </div>
                        </div> -->
                    </div>

                    <!-- Decor -->
                    <i class="fa-solid fa-carrot absolute -bottom-4 -left-4 text-8xl text-white opacity-5 -rotate-12"></i>
                </div>
            <?php endif; ?>

        </aside>

        <!-- CENTER COLUMN (Span 6, Row 3-12) -->
        <main class="col-span-6 row-span-10 flex flex-col gap-8">

            <!-- Primary Visual: Slideshow or YouTube -->
            <div
                class="bg-black rounded-[2.5rem] overflow-hidden shadow-2xl relative flex-grow-[2] border-[6px] border-white ring-1 ring-black/5">

                <?php if (!empty($youtube)): ?>
                    <!-- YouTube Embed -->
                    <div class="absolute inset-0 pointer-events-none">
                        <iframe width="100%" height="100%"
                            src="https://www.youtube.com/embed/<?php echo esc_attr(basename($youtube)); ?>?autoplay=1&mute=1&controls=0&loop=1&playlist=<?php echo esc_attr(basename($youtube)); ?>"
                            frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen style="pointer-events: none;">
                        </iframe>
                    </div>
                    <div class="absolute inset-0 z-10 bg-gradient-to-t from-black/60 to-transparent pointer-events-none">
                    </div>
                <?php else: ?>
                    <!-- Slideshow -->
                    <img id="slideshow-img" src="" class="w-full h-full object-cover animate-fade-in" alt="Slideshow">

                    <div class="absolute bottom-8 left-8 right-8 z-20">
                        <span
                            class="inline-block px-3 py-1 rounded-full bg-chroma-red text-white text-xs font-bold uppercase tracking-wider mb-2 shadow-lg">Happening
                            Now</span>
                        <h2 class="font-serif text-5xl font-bold text-white drop-shadow-md">Fall Festival Highlights</h2>
                    </div>

                    <div class="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-transparent z-10"></div>

                    <?php
                    // Pass PHP array to JS
                    $js_slides = !empty($slideshow) ? json_encode($slideshow) : '[]';
                    ?>
                    <script>
                        var slideImages = <?php echo $js_slides; ?>;
                    </script>
                <?php endif; ?>

            </div>

            <!-- Star Educator -->
            <?php if (!empty($eom['name'])): ?>
                <div
                    class="bg-white rounded-[2.5rem] p-3 shadow-card border border-chroma-blue/10 flex items-center gap-8 h-48 relative overflow-hidden group">
                    <!-- Image -->
                    <div class="h-full w-48 shrink-0 rounded-[2rem] overflow-hidden relative shadow-inner">
                        <?php if (!empty($eom['photo_url'])): ?>
                            <img src="<?php echo esc_url($eom['photo_url']); ?>"
                                class="w-full h-full object-cover transform group-hover:scale-105 transition duration-1000"
                                alt="Teacher">
                        <?php else: ?>
                            <div class="w-full h-full bg-chroma-blue flex items-center justify-center text-white text-5xl"><i
                                    class="fa-solid fa-user"></i></div>
                        <?php endif; ?>
                    </div>

                    <!-- Content -->
                    <div class="flex-1 py-2 pr-6 z-10">
                        <div class="flex justify-between items-start mb-1 h-full flex-col justify-center">
                            <div>
                                <h2 class="font-serif text-4xl font-bold text-brand-ink mb-1">
                                    <?php echo esc_html($eom['name']); ?>
                                </h2>
                                <p class="text-chroma-blue font-bold uppercase tracking-widest text-xs mb-3">
                                    Leacher Teacher • Infants
                                </p>
                                <p class="text-brand-ink/60 text-lg leading-snug line-clamp-2 italic">
                                    "<?php echo esc_html($eom['blurb'] ?? 'Dedicated to nurturing every child\'s potential.'); ?>"
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Badge Icon -->
                    <div class="absolute top-6 right-6 text-chroma-yellow text-5xl opacity-40">
                        <i class="fa-solid fa-certificate"></i>
                    </div>
                </div>
            <?php endif; ?>

        </main>

        <!-- RIGHT COLUMN (Span 3, Row 3-12) -->
        <aside class="col-span-3 row-span-10 flex flex-col gap-6">

            <!-- Newsletter Card -->
            <?php if (!empty($newsletter['title'])): ?>
                <div class="bg-[#F8EEEB] rounded-[2rem] p-6 shadow-card flex-1 flex flex-col max-h-[40%]">
                    <div class="flex items-center gap-3 mb-4">
                        <div
                            class="w-10 h-10 rounded-xl bg-white/80 flex items-center justify-center text-chroma-red text-xl shadow-sm">
                            <i class="fa-regular fa-newspaper"></i>
                        </div>
                        <h2 class="font-serif text-2xl font-bold text-chroma-red">Newsletter</h2>
                    </div>

                    <div class="flex-1 flex flex-col gap-4">
                        <div>
                            <h3 class="font-bold text-xl text-brand-ink mb-1"><?php echo esc_html($newsletter['title']); ?>
                            </h3>
                            <p class="text-brand-ink/60 text-sm leading-relaxed line-clamp-3">
                                <?php echo esc_html($newsletter['body'] ?? 'Updates on upcoming events and classroom activities.'); ?>
                            </p>
                        </div>

                        <?php if (!empty($newsletter['url'])): ?>
                            <div class="bg-white rounded-2xl p-3 flex items-center gap-3 shadow-sm mt-auto">
                                <div class="bg-brand-ink p-1 rounded-lg shrink-0">
                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?php echo urlencode($newsletter['url']); ?>"
                                        class="w-12 h-12" alt="QR">
                                </div>
                                <div class="leading-tight">
                                    <p class="font-bold text-base text-brand-ink">Read Full Issue</p>
                                    <p class="text-xs text-brand-ink/50">Scan with phone</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Chroma Cares (Global/Local) -->
            <?php
            $local_cares = get_post_meta($school_id, '_chroma_school_chroma_cares', true) ?: [];
            // Prefer local override if title is set
            $display_cares = !empty($local_cares['title']) ? $local_cares : $global_cares;
            ?>
            <?php if (!empty($display_cares['title'])): ?>
                <div
                    class="bg-[#E6EFEC] rounded-[2rem] p-6 shadow-card min-h-[220px] relative overflow-hidden group flex flex-col justify-between">
                    <!-- Background Heart -->
                    <div class="absolute top-4 right-4 text-8xl text-chroma-green opacity-[0.08] rotate-12">
                        <i class="fa-solid fa-heart"></i>
                    </div>

                    <div class="relative z-10">
                        <span class="text-chroma-green font-bold uppercase tracking-widest text-xs mb-2 block">Chroma
                            Cares</span>
                        <h2 class="font-serif text-3xl font-bold text-brand-ink mb-2 leading-tight">
                            <?php echo esc_html($display_cares['title']); ?>
                        </h2>
                        <p class="text-brand-ink/60 text-sm leading-relaxed">
                            <?php echo esc_html($display_cares['body'] ?? 'Working together to support our community.'); ?>
                        </p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Helper: Celebrations -->
            <?php
            $celebrations = get_post_meta($school_id, '_chroma_school_celebrations', true) ?: [];
            ?>
            <?php if (!empty($celebrations)): ?>
                <div
                    class="bg-white rounded-[2rem] p-6 shadow-card border border-chroma-blue/10 flex-1 flex flex-col justify-center text-center relative overflow-hidden min-h-[150px]">
                    <div class="flex items-center justify-center gap-3 mb-2">
                        <i
                            class="fa-solid fa-cake-candles text-2xl text-transparent bg-clip-text bg-gradient-to-r from-purple-400 to-pink-600"></i>
                        <h3 class="font-serif text-2xl font-bold text-brand-ink">Celebrations</h3>
                    </div>

                    <div class="space-y-1 relative z-10 text-brand-ink/60 text-lg">
                        <?php foreach ($celebrations as $celeb): ?>
                            <p><?php echo esc_html($celeb); ?></p>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

        </aside>

        <!-- Global Alert Ticker (Bottom Banner) -->
        <?php if (!empty($global_alert['enabled'])): ?>
            <div
                class="absolute bottom-6 left-6 right-6 bg-chroma-blueDark text-white p-5 rounded-2xl shadow-2xl z-50 flex items-center gap-5 animate-fade-in border-l-[10px] border-chroma-yellow">
                <div
                    class="bg-chroma-yellow text-brand-ink w-10 h-10 rounded-full flex items-center justify-center font-bold shrink-0 text-xl">
                    <i class="fa-solid fa-info"></i>
                </div>
                <p class="font-bold text-2xl tracking-wide"><?php echo esc_html($global_alert['message'] ?? ''); ?></p>
            </div>
        <?php endif; ?>

    </div>

    <script>
        // Live Clock & Date
        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }).replace(' AM', '').replace(' PM', '');
            const ampmString = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }).slice(-2);
            const dateString = now.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' });

            document.getElementById('clock').textContent = timeString;
            document.getElementById('ampm').textContent = ampmString;
            document.getElementById('current-date').textContent = dateString;
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Slideshow Rotation
        if (typeof slideImages !== 'undefined' && slideImages.length > 0) {
            const imgElement = document.getElementById('slideshow-img');
            if (imgElement) {
                // Init first image
                if (!imgElement.src) imgElement.src = slideImages[0];

                let currentImg = 0;
                setInterval(() => {
                    currentImg = (currentImg + 1) % slideImages.length;
                    imgElement.style.opacity = 0;
                    setTimeout(() => {
                        imgElement.src = slideImages[currentImg];
                        imgElement.style.opacity = 1;
                    }, 500);
                }, 8000);
            }
        } else {
            // Fallback default image or hidden?
            const img = document.getElementById('slideshow-img');
            if (img && !img.src) img.src = "https://images.unsplash.com/photo-1587654780291-39c9404d746b?q=80&w=1600&auto=format&fit=crop";
        }

        // Auto-Refresh (Poll every 5 mins)
        setTimeout(() => window.location.reload(), 300000); 
    </script>
</body>

</html>