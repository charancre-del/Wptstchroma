<?php
/**
 * Program Page Enhancements
 * FAQ, Testimonials, Gallery, Age Calculator, Sticky CTA
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Program_Enhancements
{
    public function __construct()
    {
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post_program', [$this, 'save_meta']);
        // DISABLED: Schema now handled by Chroma SEO Pro plugin
        // add_action('wp_head', [$this, 'output_faq_schema']);

    }

    /**
     * Add meta boxes for program enhancements
     */
    public function add_meta_boxes()
    {
        add_meta_box(
            'program_faq',
            '❓ FAQ Section',
            [$this, 'render_faq_metabox'],
            'program',
            'normal',
            'default'
        );

        add_meta_box(
            'program_gallery',
            '📷 Photo Gallery',
            [$this, 'render_gallery_metabox'],
            'program',
            'normal',
            'default'
        );

        add_meta_box(
            'program_testimonials',
            '💬 Testimonials',
            [$this, 'render_testimonials_metabox'],
            'program',
            'normal',
            'default'
        );
    }

    /**
     * Render FAQ metabox
     */
    public function render_faq_metabox($post)
    {
        $faqs = get_post_meta($post->ID, 'program_faqs', true) ?: [];
        wp_nonce_field('program_enhancements', 'program_enhancements_nonce');
        ?>
        <p>Add FAQ items that will appear on the program page with FAQPage schema for rich results.</p>
        <div id="faq-repeater">
            <?php foreach ($faqs as $i => $faq): ?>
                <div class="faq-item" style="background:#f9f9f9;padding:15px;margin:10px 0;border-radius:8px;">
                    <p><strong>Question:</strong><br>
                        <input type="text" name="program_faqs[<?php echo $i; ?>][question]"
                            value="<?php echo esc_attr($faq['question'] ?? ''); ?>" class="widefat">
                    </p>
                    <p><strong>Answer:</strong><br>
                        <textarea name="program_faqs[<?php echo $i; ?>][answer]" rows="3"
                            class="widefat"><?php echo esc_textarea($faq['answer'] ?? ''); ?></textarea>
                    </p>
                    <button type="button" class="button remove-faq">Remove</button>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="button button-primary" id="add-faq">+ Add FAQ</button>
        <script>
            jQuery(function ($) {
                var i = <?php echo count($faqs); ?>;
                $('#add-faq').on('click', function () {
                    $('#faq-repeater').append(
                        '<div class="faq-item" style="background:#f9f9f9;padding:15px;margin:10px 0;border-radius:8px;">' +
                        '<p><strong>Question:</strong><br><input type="text" name="program_faqs[' + i + '][question]" class="widefat"></p>' +
                        '<p><strong>Answer:</strong><br><textarea name="program_faqs[' + i + '][answer]" rows="3" class="widefat"></textarea></p>' +
                        '<button type="button" class="button remove-faq">Remove</button></div>'
                    );
                    i++;
                });
                $(document).on('click', '.remove-faq', function () {
                    $(this).closest('.faq-item').remove();
                });
            });
        </script>
        <?php
    }

    /**
     * Render gallery metabox
     */
    public function render_gallery_metabox($post)
    {
        $gallery_ids = get_post_meta($post->ID, 'program_gallery', true) ?: [];
        ?>
        <p>Select images for the program photo gallery.</p>
        <div id="gallery-preview" style="display:flex;flex-wrap:wrap;gap:10px;margin:10px 0;">
            <?php foreach ($gallery_ids as $id): ?>
                <div class="gallery-thumb" data-id="<?php echo $id; ?>">
                    <?php echo wp_get_attachment_image($id, 'thumbnail'); ?>
                    <span class="remove-image" style="cursor:pointer;color:red;">×</span>
                </div>
            <?php endforeach; ?>
        </div>
        <input type="hidden" name="program_gallery" id="program_gallery" value="<?php echo implode(',', $gallery_ids); ?>">
        <button type="button" class="button" id="add-gallery-images">Add Images</button>
        <script>
            jQuery(function ($) {
                var frame;
                $('#add-gallery-images').on('click', function (e) {
                    e.preventDefault();
                    if (frame) { frame.open(); return; }
                    frame = wp.media({
                        title: 'Select Gallery Images',
                        multiple: true,
                        library: { type: 'image' }
                    });
                    frame.on('select', function () {
                        var selection = frame.state().get('selection');
                        var ids = $('#program_gallery').val() ? $('#program_gallery').val().split(',') : [];
                        selection.each(function (attachment) {
                            ids.push(attachment.id);
                            $('#gallery-preview').append(
                                '<div class="gallery-thumb" data-id="' + attachment.id + '">' +
                                '<img src="' + attachment.attributes.sizes.thumbnail.url + '">' +
                                '<span class="remove-image" style="cursor:pointer;color:red;">×</span></div>'
                            );
                        });
                        $('#program_gallery').val(ids.join(','));
                    });
                    frame.open();
                });
                $(document).on('click', '.remove-image', function () {
                    var id = $(this).parent().data('id');
                    var ids = $('#program_gallery').val().split(',').filter(x => x != id);
                    $('#program_gallery').val(ids.join(','));
                    $(this).parent().remove();
                });
            });
        </script>
        <?php
    }

    /**
     * Render testimonials metabox
     */
    public function render_testimonials_metabox($post)
    {
        $testimonials = get_post_meta($post->ID, 'program_testimonials', true) ?: [];
        ?>
        <p>Add parent testimonials for this program.</p>
        <div id="testimonials-repeater">
            <?php foreach ($testimonials as $i => $t): ?>
                <div class="testimonial-item" style="background:#f0f7ff;padding:15px;margin:10px 0;border-radius:8px;">
                    <p><strong>Quote:</strong><br>
                        <textarea name="program_testimonials[<?php echo $i; ?>][quote]" rows="2"
                            class="widefat"><?php echo esc_textarea($t['quote'] ?? ''); ?></textarea>
                    </p>
                    <p><strong>Parent Name:</strong><br>
                        <input type="text" name="program_testimonials[<?php echo $i; ?>][name]"
                            value="<?php echo esc_attr($t['name'] ?? ''); ?>" class="regular-text">
                    </p>
                    <p><strong>Child's Age/Program:</strong><br>
                        <input type="text" name="program_testimonials[<?php echo $i; ?>][child]"
                            value="<?php echo esc_attr($t['child'] ?? ''); ?>" class="regular-text"
                            placeholder="e.g., Parent of a 4-year-old">
                    </p>
                    <button type="button" class="button remove-testimonial">Remove</button>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="button button-primary" id="add-testimonial">+ Add Testimonial</button>
        <script>
            jQuery(function ($) {
                var ti = <?php echo count($testimonials); ?>;
                $('#add-testimonial').on('click', function () {
                    $('#testimonials-repeater').append(
                        '<div class="testimonial-item" style="background:#f0f7ff;padding:15px;margin:10px 0;border-radius:8px;">' +
                        '<p><strong>Quote:</strong><br><textarea name="program_testimonials[' + ti + '][quote]" rows="2" class="widefat"></textarea></p>' +
                        '<p><strong>Parent Name:</strong><br><input type="text" name="program_testimonials[' + ti + '][name]" class="regular-text"></p>' +
                        '<p><strong>Child\'s Age/Program:</strong><br><input type="text" name="program_testimonials[' + ti + '][child]" class="regular-text" placeholder="e.g., Parent of a 4-year-old"></p>' +
                        '<button type="button" class="button remove-testimonial">Remove</button></div>'
                    );
                    ti++;
                });
                $(document).on('click', '.remove-testimonial', function () {
                    $(this).closest('.testimonial-item').remove();
                });
            });
        </script>
        <?php
    }

    /**
     * Save meta
     */
    public function save_meta($post_id)
    {
        if (
            !isset($_POST['program_enhancements_nonce']) ||
            !wp_verify_nonce($_POST['program_enhancements_nonce'], 'program_enhancements')
        ) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            return;
        if (!current_user_can('edit_post', $post_id))
            return;

        // Save FAQs
        if (isset($_POST['program_faqs'])) {
            $faqs = array_filter($_POST['program_faqs'], function ($f) {
                return !empty($f['question']) && !empty($f['answer']);
            });
            update_post_meta($post_id, 'program_faqs', array_values($faqs));
        } else {
            delete_post_meta($post_id, 'program_faqs');
        }

        // Save Gallery
        if (isset($_POST['program_gallery']) && !empty($_POST['program_gallery'])) {
            $ids = array_filter(explode(',', $_POST['program_gallery']));
            update_post_meta($post_id, 'program_gallery', $ids);
        } else {
            delete_post_meta($post_id, 'program_gallery');
        }

        // Save Testimonials
        if (isset($_POST['program_testimonials'])) {
            $testimonials = array_filter($_POST['program_testimonials'], function ($t) {
                return !empty($t['quote']) && !empty($t['name']);
            });
            update_post_meta($post_id, 'program_testimonials', array_values($testimonials));
        } else {
            delete_post_meta($post_id, 'program_testimonials');
        }
    }

    /**
     * Output FAQ schema
     */
    public function output_faq_schema()
    {
        if (!is_singular('program'))
            return;

        // Check for manual override
        $override = get_post_meta(get_the_ID(), '_chroma_schema_override', true);
        if ($override) {
            return;
        }

        // Internal Duplicate Suppression
        global $chroma_faq_output_done;
        if (!empty($chroma_faq_output_done)) {
            return;
        }

        $faqs = get_post_meta(get_the_ID(), 'program_faqs', true);
        if (empty($faqs))
            return;

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => []
        ];

        foreach ($faqs as $faq) {
            $schema['mainEntity'][] = [
                '@type' => 'Question',
                'name' => $faq['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $faq['answer']
                ]
            ];
        }

        echo '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
        $chroma_faq_output_done = true;
    }

    /**
     * Render sticky CTA bar
     */
    public function render_sticky_cta()
    {
        if (!is_singular('program'))
            return;

        $program_title = get_the_title();
        $tour_url = home_url('/schedule-a-tour/');
        ?>
        <div id="sticky-cta" class="sticky-cta-bar">
            <div class="sticky-cta-content">
                <span class="sticky-cta-text">Ready to enroll in
                    <strong><?php echo esc_html($program_title); ?></strong>?</span>
                <a href="<?php echo esc_url($tour_url); ?>" class="sticky-cta-button">Schedule a Tour</a>
            </div>
        </div>
        <style>
            <style>.sticky-cta-bar {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                background: linear-gradient(135deg, #263238 0%, #37474f 100%);
                padding: 15px 20px;
                z-index: 9999;
                box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.2);
                transform: translateY(100%);
                will-change: transform;
                transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            }

            .sticky-cta-bar.visible {
                transform: translateY(0);
            }

            .sticky-cta-content {
                max-width: 1200px;
                margin: 0 auto;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 20px;
                flex-wrap: wrap;
            }

            .sticky-cta-text {
                color: #fff;
                font-size: 16px;
            }

            .sticky-cta-button {
                background: #D67D6B;
                color: #fff;
                padding: 12px 30px;
                border-radius: 50px;
                font-weight: 700;
                text-decoration: none;
                text-transform: uppercase;
                font-size: 12px;
                letter-spacing: 0.1em;
                transition: transform 0.2s, background 0.2s;
            }

            .sticky-cta-button:hover {
                background: #c26a5a;
                transform: scale(1.05);
            }

            @media (max-width: 600px) {
                .sticky-cta-content {
                    flex-direction: column;
                    text-align: center;
                }
            }
        </style>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var cta = document.getElementById('sticky-cta');
                var shown = false;
                window.addEventListener('scroll', function () {
                    if (window.scrollY > 500 && !shown) {
                        cta.classList.add('visible');
                        shown = true;
                    }
                });
            });
        </script>
        <?php
    }

    /**
     * Render FAQ section (call from template)
     */
    public static function render_faq_section($post_id = null)
    {
        $post_id = $post_id ?: get_the_ID();
        $faqs = get_post_meta($post_id, 'program_faqs', true);

        if (empty($faqs))
            return;
        ?>
        <section class="program-faq py-16 bg-white">
            <div class="max-w-4xl mx-auto px-4">
                <h2 class="text-3xl font-serif font-bold text-center mb-10 text-brand-ink">
                    Frequently Asked Questions
                </h2>
                <div class="faq-accordion space-y-4">
                    <?php foreach ($faqs as $i => $faq): ?>
                        <div class="faq-item border border-brand-ink/10 rounded-xl overflow-hidden">
                            <button
                                class="faq-trigger w-full text-left p-5 flex justify-between items-center bg-brand-cream/50 hover:bg-brand-cream transition-colors"
                                aria-expanded="false" aria-controls="faq-<?php echo $i; ?>">
                                <span class="font-bold text-brand-ink"><?php echo esc_html($faq['question']); ?></span>
                                <svg class="faq-icon w-5 h-5 text-brand-ink/50 transition-transform" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div class="faq-content hidden p-5 bg-white" id="faq-<?php echo $i; ?>">
                                <p class="text-brand-ink/80"><?php echo wp_kses_post($faq['answer']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <script>
            document.querySelectorAll('.faq-trigger').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var content = this.nextElementSibling;
                    var icon = this.querySelector('.faq-icon');
                    var expanded = this.getAttribute('aria-expanded') === 'true';

                    this.setAttribute('aria-expanded', !expanded);
                    content.classList.toggle('hidden');
                    icon.style.transform = expanded ? '' : 'rotate(180deg)';
                });
            });
        </script>
        <?php
    }

    /**
     * Render testimonials carousel (call from template)
     */
    public static function render_testimonials_section($post_id = null)
    {
        $post_id = $post_id ?: get_the_ID();
        $testimonials = get_post_meta($post_id, 'program_testimonials', true);

        if (empty($testimonials))
            return;
        ?>
        <section class="program-testimonials py-16 bg-brand-cream">
            <div class="max-w-5xl mx-auto px-4">
                <h2 class="text-3xl font-serif font-bold text-center mb-10 text-brand-ink">
                    What Parents Say
                </h2>
                <div class="testimonials-carousel relative">
                    <div class="testimonials-track flex transition-transform duration-500" style="transform: translateX(0);">
                        <?php foreach ($testimonials as $i => $t): ?>
                            <div class="testimonial-slide flex-shrink-0 w-full md:w-1/2 lg:w-1/3 p-4">
                                <div class="bg-white rounded-2xl p-6 shadow-soft h-full">
                                    <div class="text-4xl text-chroma-yellow mb-4">"</div>
                                    <p class="text-brand-ink/80 mb-4 italic"><?php echo esc_html($t['quote']); ?></p>
                                    <div class="mt-auto">
                                        <p class="font-bold text-brand-ink"><?php echo esc_html($t['name']); ?></p>
                                        <?php if (!empty($t['child'])): ?>
                                            <p class="text-sm text-brand-ink/60"><?php echo esc_html($t['child']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($testimonials) > 3): ?>
                        <button
                            class="carousel-prev absolute left-0 top-1/2 -translate-y-1/2 w-10 h-10 bg-white rounded-full shadow-lg flex items-center justify-center">←</button>
                        <button
                            class="carousel-next absolute right-0 top-1/2 -translate-y-1/2 w-10 h-10 bg-white rounded-full shadow-lg flex items-center justify-center">→</button>
                    <?php endif; ?>
                </div>
            </div>
        </section>
        <script>
            (function () {
                var track = document.querySelector('.testimonials-track');
                var slides = document.querySelectorAll('.testimonial-slide');
                var prev = document.querySelector('.carousel-prev');
                var next = document.querySelector('.carousel-next');
                var current = 0;
                var total = slides.length;
                var perView = window.innerWidth >= 1024 ? 3 : window.innerWidth >= 768 ? 2 : 1;

                function update() {
                    track.style.transform = 'translateX(-' + (current * (100 / perView)) + '%)';
                }

                if (prev) prev.addEventListener('click', function () {
                    current = Math.max(0, current - 1);
                    update();
                });

                if (next) next.addEventListener('click', function () {
                    current = Math.min(total - perView, current + 1);
                    update();
                });
            })();
        </script>
        <?php
    }

    /**
     * Render gallery section (call from template)
     */
    public static function render_gallery_section($post_id = null)
    {
        $post_id = $post_id ?: get_the_ID();
        $gallery_ids = get_post_meta($post_id, 'program_gallery', true);

        if (empty($gallery_ids))
            return;
        ?>
        <section class="program-gallery py-16 bg-white">
            <div class="max-w-6xl mx-auto px-4">
                <h2 class="text-3xl font-serif font-bold text-center mb-10 text-brand-ink">
                    A Day in Our Classroom
                </h2>
                <div class="gallery-grid grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <?php foreach ($gallery_ids as $id): ?>
                        <a href="<?php echo esc_url(wp_get_attachment_url($id)); ?>"
                            class="gallery-item block rounded-xl overflow-hidden aspect-square group"
                            data-lightbox="program-gallery">
                            <?php echo wp_get_attachment_image($id, 'medium', false, [
                                'class' => 'w-full h-full object-cover transition-transform duration-300 group-hover:scale-110'
                            ]); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php
    }

    /**
     * Render age calculator (call from template)
     */
    public static function render_age_calculator($post_id = null)
    {
        $post_id = $post_id ?: get_the_ID();
        $age_range = get_post_meta($post_id, 'program_age_range', true);
        $program_title = get_the_title($post_id);

        // Parse age range (e.g., "3-4 years" or "12-24 months")
        preg_match('/(\d+)\s*-?\s*(\d+)?\s*(months?|years?)/i', $age_range, $matches);
        $min_age = isset($matches[1]) ? intval($matches[1]) : 0;
        $max_age = isset($matches[2]) ? intval($matches[2]) : $min_age + 1;
        $unit = isset($matches[3]) ? strtolower($matches[3]) : 'years';

        // Convert to months
        if (strpos($unit, 'year') !== false) {
            $min_months = $min_age * 12;
            $max_months = $max_age * 12;
        } else {
            $min_months = $min_age;
            $max_months = $max_age;
        }
        ?>
        <div class="age-calculator bg-gradient-to-br from-chroma-blue/10 to-chroma-green/10 rounded-2xl p-6 my-8">
            <h3 class="text-xl font-bold text-brand-ink mb-4">🎂 Is Your Child Ready for
                <?php echo esc_html($program_title); ?>?</h3>
            <p class="text-brand-ink/70 mb-4 text-sm">Enter your child's birthday to check eligibility.</p>

            <div class="flex flex-wrap gap-4 items-end mb-4">
                <div>
                    <label class="block text-xs font-bold text-brand-ink/60 mb-1">Birthday</label>
                    <input type="date" id="child-birthday"
                        class="px-4 py-2 border border-brand-ink/20 rounded-lg focus:border-chroma-blue focus:outline-none">
                </div>
                <button type="button" id="check-age"
                    class="px-6 py-2 bg-chroma-blue text-white rounded-lg font-bold hover:bg-chroma-blue/90 transition-colors">
                    Check
                </button>
            </div>

            <div id="age-result" class="hidden p-4 rounded-lg">
                <p id="age-message" class="font-medium"></p>
                <a id="age-cta" href="<?php echo esc_url(home_url('/schedule-a-tour/')); ?>"
                    class="inline-block mt-3 px-6 py-2 bg-chroma-red text-white rounded-full text-sm font-bold hidden">
                    Schedule a Tour →
                </a>
                <a id="age-alt" href="" class="inline-block mt-3 text-chroma-blue font-medium text-sm hidden">
                    Check another program →
                </a>
            </div>
        </div>

        <script>
            document.getElementById('check-age').addEventListener('click', function () {
                var birthday = document.getElementById('child-birthday').value;
                if (!birthday) { alert('Please enter a birthday'); return; }

                var dob = new Date(birthday);
                var today = new Date();
                var ageMonths = (today.getFullYear() - dob.getFullYear()) * 12 + (today.getMonth() - dob.getMonth());

                var result = document.getElementById('age-result');
                var message = document.getElementById('age-message');
                var cta = document.getElementById('age-cta');
                var alt = document.getElementById('age-alt');

                var minMonths = <?php echo $min_months; ?>;
                var maxMonths = <?php echo $max_months; ?>;

                result.classList.remove('hidden', 'bg-green-100', 'bg-yellow-100', 'bg-red-100');
                cta.classList.add('hidden');
                alt.classList.add('hidden');

                var years = Math.floor(ageMonths / 12);
                var months = ageMonths % 12;
                var ageText = years > 0 ? years + ' year' + (years > 1 ? 's' : '') + (months > 0 ? ', ' + months + ' month' + (months > 1 ? 's' : '') : '') : months + ' months';

                if (ageMonths >= minMonths && ageMonths <= maxMonths) {
                    result.classList.add('bg-green-100');
                    message.innerHTML = '✅ <strong>Perfect!</strong> Your child is ' + ageText + ' old — ideal for our <?php echo esc_js($program_title); ?> program!';
                    cta.classList.remove('hidden');
                } else if (ageMonths < minMonths) {
                    result.classList.add('bg-yellow-100');
                    var monthsUntil = minMonths - ageMonths;
                    message.innerHTML = '🕐 Your child (' + ageText + ') will be eligible in ' + monthsUntil + ' month' + (monthsUntil > 1 ? 's' : '') + '.';
                    alt.href = '<?php echo esc_url(home_url('/programs/')); ?>';
                    alt.textContent = 'View programs for younger children →';
                    alt.classList.remove('hidden');
                } else {
                    result.classList.add('bg-yellow-100');
                    message.innerHTML = '📈 Your child (' + ageText + ') may be ready for an older age group.';
                    alt.href = '<?php echo esc_url(home_url('/programs/')); ?>';
                    alt.textContent = 'View all programs →';
                    alt.classList.remove('hidden');
                }
            });
        </script>
        <?php
    }
}

new Chroma_Program_Enhancements();
