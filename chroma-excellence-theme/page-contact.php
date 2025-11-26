<?php
/**
 * Template Name: Contact Page
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$page_id = get_the_ID();

// Hero Section
$hero_badge       = get_post_meta( $page_id, 'contact_hero_badge', true ) ?: 'Start Your Journey';
$hero_title       = get_post_meta( $page_id, 'contact_hero_title', true ) ?: 'We\'d love to meet you.';
$hero_description = get_post_meta( $page_id, 'contact_hero_description', true ) ?: 'Ready to experience the Chroma difference? Schedule a tour or ask us a question below to get started.';

// Form Settings
$form_submit_text = get_post_meta( $page_id, 'contact_form_submit_text', true ) ?: 'Submit Request';

// Corporate Office
$corporate_title   = get_post_meta( $page_id, 'contact_corporate_title', true ) ?: 'Corporate Office';
$corporate_name    = get_post_meta( $page_id, 'contact_corporate_name', true ) ?: 'Chroma Early Learning HQ';
$corporate_address = get_post_meta( $page_id, 'contact_corporate_address', true ) ?: "123 Education Way, Suite 400\nAtlanta, GA 30309";
$corporate_phone   = get_post_meta( $page_id, 'contact_corporate_phone', true ) ?: '(404) 555-0199';

// Careers Section
$careers_title       = get_post_meta( $page_id, 'contact_careers_title', true ) ?: 'Careers';
$careers_description = get_post_meta( $page_id, 'contact_careers_description', true ) ?: 'Passionate about early childhood education? We are always looking for dedicated teachers and directors.';
$careers_link_text   = get_post_meta( $page_id, 'contact_careers_link_text', true ) ?: 'View Open Positions';
$careers_link_url    = get_post_meta( $page_id, 'contact_careers_link_url', true ) ?: '/careers';

// Press Section
$press_title       = get_post_meta( $page_id, 'contact_press_title', true ) ?: 'Press Inquiries';
$press_description = get_post_meta( $page_id, 'contact_press_description', true ) ?: 'For media kits and interview requests with our leadership team.';
$press_link_text   = get_post_meta( $page_id, 'contact_press_link_text', true ) ?: 'Visit Newsroom';
$press_link_url    = get_post_meta( $page_id, 'contact_press_link_url', true ) ?: '/newsroom';
?>

<main id="primary" class="site-main" role="main">
	<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

		<!-- Hero -->
		<section class="py-20 bg-white text-center">
			<div class="max-w-4xl mx-auto px-4">
				<span class="text-chroma-blue font-bold tracking-[0.2em] text-xs uppercase mb-3 block">
					<?php echo esc_html( $hero_badge ); ?>
				</span>
				<h1 class="font-serif text-5xl md:text-6xl text-brand-ink mb-6">
					<?php echo esc_html( $hero_title ); ?>
				</h1>
				<p class="text-lg text-brand-ink/60">
					<?php echo esc_html( $hero_description ); ?>
				</p>
			</div>
		</section>

		<!-- Contact Grid -->
		<section class="pb-24 bg-white">
			<div class="max-w-7xl mx-auto px-4 lg:px-6 grid lg:grid-cols-2 gap-16">

				<!-- Form -->
				<div class="bg-brand-cream p-10 rounded-[3rem] border border-brand-ink/5 shadow-soft">
					<form class="space-y-6" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="chroma_contact_form">
						<?php wp_nonce_field( 'chroma_contact_form_action', 'chroma_contact_form_nonce' ); ?>

						<div class="grid md:grid-cols-2 gap-6">
							<div>
								<label class="block text-xs font-bold uppercase tracking-wider text-brand-ink/50 mb-2">First Name *</label>
								<input type="text" name="first_name" class="w-full p-4 rounded-xl border border-brand-ink/10 focus:border-chroma-blue focus:outline-none bg-white" placeholder="Jane" required>
							</div>
							<div>
								<label class="block text-xs font-bold uppercase tracking-wider text-brand-ink/50 mb-2">Last Name *</label>
								<input type="text" name="last_name" class="w-full p-4 rounded-xl border border-brand-ink/10 focus:border-chroma-blue focus:outline-none bg-white" placeholder="Doe" required>
							</div>
						</div>

						<div class="grid md:grid-cols-2 gap-6">
							<div>
								<label class="block text-xs font-bold uppercase tracking-wider text-brand-ink/50 mb-2">Email Address *</label>
								<input type="email" name="email" class="w-full p-4 rounded-xl border border-brand-ink/10 focus:border-chroma-blue focus:outline-none bg-white" placeholder="you@example.com" required>
							</div>
							<div>
								<label class="block text-xs font-bold uppercase tracking-wider text-brand-ink/50 mb-2">Phone Number *</label>
								<input type="tel" name="phone" class="w-full p-4 rounded-xl border border-brand-ink/10 focus:border-chroma-blue focus:outline-none bg-white" placeholder="(555) 123-4567" required>
							</div>
						</div>

						<div class="grid md:grid-cols-2 gap-6">
							<div>
								<label class="block text-xs font-bold uppercase tracking-wider text-brand-ink/50 mb-2">Preferred Campus</label>
								<select name="preferred_campus" class="w-full p-4 rounded-xl border border-brand-ink/10 focus:border-chroma-blue focus:outline-none bg-white">
									<option value="">Select a location...</option>
									<?php
									// Query location posts
									$locations = new WP_Query( array(
										'post_type'      => 'location',
										'posts_per_page' => -1,
										'orderby'        => 'title',
										'order'          => 'ASC',
									) );

									if ( $locations->have_posts() ) {
										while ( $locations->have_posts() ) {
											$locations->the_post();
											echo '<option value="' . esc_attr( get_the_title() ) . '">' . esc_html( get_the_title() ) . '</option>';
										}
										wp_reset_postdata();
									} else {
										// Fallback options if no locations exist
										?>
										<option>Lawrenceville</option>
										<option>Marietta</option>
										<option>Alpharetta</option>
										<option>Duluth</option>
										<option>Newnan</option>
										<?php
									}
									?>
									<option>I'm not sure yet</option>
								</select>
							</div>
							<div>
								<label class="block text-xs font-bold uppercase tracking-wider text-brand-ink/50 mb-2">Child's Age</label>
								<input type="text" name="child_age" class="w-full p-4 rounded-xl border border-brand-ink/10 focus:border-chroma-blue focus:outline-none bg-white" placeholder="e.g. 2 years">
							</div>
						</div>

						<div>
							<label class="block text-xs font-bold uppercase tracking-wider text-brand-ink/50 mb-2">Topic</label>
							<select name="topic" class="w-full p-4 rounded-xl border border-brand-ink/10 focus:border-chroma-blue focus:outline-none bg-white">
								<option>Schedule a Tour</option>
								<option>Tuition Inquiry</option>
								<option>Careers / HR</option>
								<option>Partnerships</option>
								<option>Other Inquiry</option>
							</select>
						</div>

						<div>
							<label class="block text-xs font-bold uppercase tracking-wider text-brand-ink/50 mb-2">Message</label>
							<textarea name="message" rows="4" class="w-full p-4 rounded-xl border border-brand-ink/10 focus:border-chroma-blue focus:outline-none bg-white" placeholder="Tell us about your family's needs..."></textarea>
						</div>

						<button type="submit" class="w-full py-4 bg-chroma-blue text-white font-bold rounded-full uppercase tracking-[0.2em] text-xs hover:bg-chroma-blueDark transition-colors shadow-lg">
							<?php echo esc_html( $form_submit_text ); ?>
						</button>
					</form>
				</div>

				<!-- Info -->
				<div class="flex flex-col justify-center gap-10">
					<!-- Corporate Office -->
					<div>
						<h3 class="font-serif text-2xl font-bold text-brand-ink mb-4">
							<?php echo esc_html( $corporate_title ); ?>
						</h3>
						<p class="text-brand-ink/70 leading-relaxed">
							<?php
							if ( ! empty( $corporate_name ) ) {
								echo esc_html( $corporate_name ) . '<br>';
							}
							echo nl2br( esc_html( $corporate_address ) );
							?>
						</p>
						<?php if ( ! empty( $corporate_phone ) ) : ?>
						<p class="mt-4 text-chroma-blue font-bold">
							<?php echo esc_html( $corporate_phone ); ?>
						</p>
						<?php endif; ?>
					</div>

					<div class="h-px bg-brand-ink/10 w-full"></div>

					<!-- Careers -->
					<div>
						<h3 class="font-serif text-2xl font-bold text-brand-ink mb-4">
							<?php echo esc_html( $careers_title ); ?>
						</h3>
						<p class="text-brand-ink/70 mb-4">
							<?php echo esc_html( $careers_description ); ?>
						</p>
						<a href="<?php echo esc_url( $careers_link_url ); ?>"
						   class="text-chroma-red font-bold text-sm uppercase tracking-wider hover:text-chroma-blue flex items-center gap-2">
							<?php echo esc_html( $careers_link_text ); ?>
							<i class="fa-solid fa-arrow-right"></i>
						</a>
					</div>

					<div class="h-px bg-brand-ink/10 w-full"></div>

					<!-- Press Inquiries -->
					<div>
						<h3 class="font-serif text-2xl font-bold text-brand-ink mb-4">
							<?php echo esc_html( $press_title ); ?>
						</h3>
						<p class="text-brand-ink/70 mb-4">
							<?php echo esc_html( $press_description ); ?>
						</p>
						<a href="<?php echo esc_url( $press_link_url ); ?>"
						   class="text-chroma-green font-bold text-sm uppercase tracking-wider hover:text-chroma-blue flex items-center gap-2">
							<?php echo esc_html( $press_link_text ); ?>
							<i class="fa-solid fa-arrow-right"></i>
						</a>
					</div>
				</div>

			</div>
		</section>

	</article>
</main>

<?php
get_footer();
