<?php
/**
 * The Year at Chroma page.
 *
 * The long-form weekly curriculum content is stored in the WordPress page so
 * it remains editable and crawlable. This template supplies the V2 shell and
 * interaction hooks.
 *
 * @package Chroma_Excellence
 */

get_header();
?>

<main id="primary" class="chroma-year-page">
	<?php
	while (have_posts()) :
		the_post();
		the_content();
	endwhile;
	?>
</main>

<?php
get_footer();
