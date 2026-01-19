<?php
/**
 * Global Booking Iframe Modal
 * 
 * Provides a reusable modal for third-party booking systems (e.g. Procare, Calendly, etc.)
 */

function chroma_render_booking_modal() {
    ?>
    <!-- Tour Booking Modal -->
    <div id="chroma-booking-modal" class="fixed inset-0 z-[1000] hidden" role="dialog" aria-modal="true">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-brand-ink/80 backdrop-blur-sm transition-opacity" id="chroma-booking-backdrop"></div>

        <!-- Modal Container -->
        <div class="absolute inset-4 md:inset-10 bg-white rounded-3xl shadow-2xl overflow-hidden flex flex-col animate-fade-in-up">
            <!-- Header -->
            <div class="bg-brand-cream border-b border-brand-ink/5 px-6 py-4 flex items-center justify-between flex-shrink-0">
                <h3 class="font-serif text-xl font-bold text-brand-ink"><?php _e('Schedule Your Visit', 'chroma-excellence'); ?></h3>
                <div class="flex items-center gap-4">
                    <a href="#" id="chroma-booking-external" target="_blank"
                        class="text-xs font-bold uppercase tracking-wider text-brand-ink/70 hover:text-chroma-blue transition-colors hidden md:block">
                        <?php _e('Open in new tab', 'chroma-excellence'); ?> <i class="fa-solid fa-external-link-alt ml-1"></i>
                    </a>
                    <button id="chroma-booking-close"
                        class="w-10 h-10 rounded-full bg-white border border-brand-ink/10 flex items-center justify-center text-brand-ink hover:bg-chroma-red hover:text-white hover:border-chroma-red transition-all">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>
            </div>

            <!-- Iframe Container -->
            <div class="flex-grow relative bg-white">
                <div id="chroma-booking-loader" class="absolute inset-0 flex items-center justify-center bg-white z-10">
                    <div class="w-12 h-12 border-4 border-chroma-blue/20 border-t-chroma-blue rounded-full animate-spin"></div>
                </div>
                <iframe id="chroma-booking-frame" src="" class="w-full h-full border-0"
                    allow="camera; microphone; autoplay; encrypted-media;"></iframe>
            </div>
        </div>
    </div>

    <style>
        #chroma-booking-modal:not(.hidden) { display: flex !important; }
        .animate-fade-in-up { 
            animation: bookingFadeUp 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards; 
        }
        @keyframes bookingFadeUp { 
            from { opacity: 0; transform: translateY(40px); } 
            to { opacity: 1; transform: translateY(0); } 
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('chroma-booking-modal');
            const backdrop = document.getElementById('chroma-booking-backdrop');
            const closeBtn = document.getElementById('chroma-booking-close');
            const iframe = document.getElementById('chroma-booking-frame');
            const externalLink = document.getElementById('chroma-booking-external');
            const loader = document.getElementById('chroma-booking-loader');

            function openBooking(url) {
                if (!modal) return;
                modal.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
                loader.classList.remove('hidden');
                iframe.src = url;
                if (externalLink) externalLink.href = url;
                iframe.onload = function () {
                    loader.classList.add('hidden');
                };
            }

            function closeBooking() {
                if (!modal) return;
                modal.classList.add('hidden');
                document.body.style.overflow = '';
                iframe.src = '';
            }

            // Delegation for any .booking-btn
            document.addEventListener('click', function(e) {
                const btn = e.target.closest('.booking-btn');
                if (btn) {
                    const url = btn.getAttribute('href');
                    if (url && (url.startsWith('http') || url.includes('/'))) {
                        // If it's an internal anchor or page, let it be. 
                        // But if it's a booking link, intercept.
                        if (url.includes('procare') || url.includes('calendly') || btn.classList.contains('force-iframe')) {
                             e.preventDefault();
                             openBooking(url);
                        }
                    }
                }
            });

            if (closeBtn) closeBtn.addEventListener('click', closeBooking);
            if (backdrop) backdrop.addEventListener('click', closeBooking);
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
                    closeBooking();
                }
            });

            // Expose globally
            window.chromaOpenBooking = openBooking;
        });
    </script>
    <?php
}
add_action('wp_footer', 'chroma_render_booking_modal');
