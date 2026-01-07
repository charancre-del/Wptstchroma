<?php
/**
 * Custom Search Form TEMPLATE
 *
 * @package Chroma_Excellence
 */
?>
<form role="search" method="get" class="search-form relative" action="<?php echo esc_url(home_url('/')); ?>">
    <label class="w-full block">
        <span class="screen-reader-text"><?php _e('Search for:', 'chroma-excellence'); ?></span>
        <input type="search" class="search-field w-full p-4 pr-12 rounded-xl border border-brand-ink/10 focus:outline-none focus:border-chroma-blue transition-colors"
            placeholder="<?php esc_attr_e('Search entire site...', 'chroma-excellence'); ?>"
            value="<?php echo get_search_query(); ?>" name="s" />
    </label>
    <button type="submit" class="search-submit absolute right-4 top-1/2 -translate-y-1/2 text-chroma-blue hover:text-chroma-red transition-colors bg-transparent border-0 cursor-pointer p-0">
        <span class="screen-reader-text"><?php _e('Search', 'chroma-excellence'); ?></span>
        <i class="fa-solid fa-search text-xl"></i>
    </button>
</form>
