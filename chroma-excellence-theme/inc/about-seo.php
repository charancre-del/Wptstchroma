<?php
/**
 * About page SEO helpers and meta fields
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
        exit;
}

/**
 * Determine if the current context is the About page template
 */
function chroma_is_about_template( $post_id = null ) {
        if ( null === $post_id ) {
                return is_page_template( 'page-about.php' );
        }

        return 'page-about.php' === get_page_template_slug( $post_id );
}

/**
 * Default SEO copy for the About page
 */
function chroma_get_about_seo_defaults( $post_id = null ) {
        $site_name = get_bloginfo( 'name' );
        $about_url = $post_id ? get_permalink( $post_id ) : home_url( '/about/' );

        $schema = array(
                '@context'    => 'https://schema.org',
                '@type'       => 'AboutPage',
                'name'        => sprintf( 'About %s', $site_name ),
                'description' => 'Mission-led educators, family partnerships, and values that shape joyful early learning.',
                'url'         => $about_url,
                'publisher'   => array(
                        '@type' => 'Organization',
                        'name'  => $site_name,
                        'url'   => home_url( '/' ),
                ),
        );

        return array(
                'title'           => sprintf( 'About %s | Our Story, Mission & Team', $site_name ),
                'description'     => 'Discover how Chroma nurtures whole-child growth through inspired educators, intentional classrooms, and a values-driven culture.',
                'structured_data' => wp_json_encode( $schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ),
        );
}

/**
 * Retrieve About page SEO fields with defaults
 */
function chroma_get_about_seo_fields( $post_id = null ) {
        $post_id  = $post_id ?: get_the_ID();
        $defaults = chroma_get_about_seo_defaults( $post_id );

        return array(
                'title'           => chroma_get_meta_value( $post_id, 'about_meta_title', $defaults['title'] ),
                'description'     => chroma_get_meta_value( $post_id, 'about_meta_description', $defaults['description'] ),
                'structured_data' => chroma_get_meta_value( $post_id, 'about_structured_data', $defaults['structured_data'] ),
        );
}

/**
 * Register About page SEO meta fields
 */
function chroma_register_about_meta_fields() {
        $meta_args = array(
                'type'         => 'string',
                'single'       => true,
                'show_in_rest' => true,
                'auth_callback' => function() {
                        return current_user_can( 'edit_pages' );
                },
        );

        register_post_meta(
                'page',
                'about_meta_title',
                array_merge(
                        $meta_args,
                        array(
                                'sanitize_callback' => 'sanitize_text_field',
                        )
                )
        );

        register_post_meta(
                'page',
                'about_meta_description',
                array_merge(
                        $meta_args,
                        array(
                                'sanitize_callback' => 'sanitize_textarea_field',
                        )
                )
        );

        register_post_meta(
                'page',
                'about_structured_data',
                array_merge(
                        $meta_args,
                        array(
                                'sanitize_callback' => 'sanitize_textarea_field',
                        )
                )
        );
}
add_action( 'init', 'chroma_register_about_meta_fields' );

/**
 * Add meta box for About page SEO fields
 */
function chroma_about_meta_box( $post_type, $post ) {
        if ( 'page' !== $post_type || ! chroma_is_about_template( $post->ID ) ) {
                return;
        }

        add_meta_box(
                'chroma-about-seo',
                __( 'About Page SEO', 'chroma-excellence' ),
                'chroma_render_about_meta_box',
                'page',
                'side',
                'default'
        );
}
add_action( 'add_meta_boxes', 'chroma_about_meta_box', 10, 2 );

/**
 * Render About page SEO meta box
 */
function chroma_render_about_meta_box( $post ) {
        wp_nonce_field( 'chroma_about_meta_nonce', 'chroma_about_meta_nonce_field' );

        $defaults        = chroma_get_about_seo_defaults( $post->ID );
        $meta_title      = get_post_meta( $post->ID, 'about_meta_title', true );
        $meta_description = get_post_meta( $post->ID, 'about_meta_description', true );
        $structured_data = get_post_meta( $post->ID, 'about_structured_data', true );

        if ( '' === $meta_title ) {
                $meta_title = $defaults['title'];
        }

        if ( '' === $meta_description ) {
                $meta_description = $defaults['description'];
        }

        if ( '' === $structured_data ) {
                $structured_data = $defaults['structured_data'];
        }
        ?>
        <p>
                <label for="about_meta_title" class="screen-reader-text"><?php esc_html_e( 'SEO Title', 'chroma-excellence' ); ?></label>
                <input type="text" id="about_meta_title" name="about_meta_title" value="<?php echo esc_attr( $meta_title ); ?>" class="widefat" />
                <small><?php esc_html_e( 'Custom title tag shown in search results and browser tabs.', 'chroma-excellence' ); ?></small>
        </p>
        <p>
                <label for="about_meta_description" class="screen-reader-text"><?php esc_html_e( 'Meta Description', 'chroma-excellence' ); ?></label>
                <textarea id="about_meta_description" name="about_meta_description" class="widefat" rows="3"><?php echo esc_textarea( $meta_description ); ?></textarea>
                <small><?php esc_html_e( '1–2 sentence summary for search snippets.', 'chroma-excellence' ); ?></small>
        </p>
        <p>
                <label for="about_structured_data" class="screen-reader-text"><?php esc_html_e( 'Structured Data JSON-LD', 'chroma-excellence' ); ?></label>
                <textarea id="about_structured_data" name="about_structured_data" class="widefat" rows="6"><?php echo esc_textarea( $structured_data ); ?></textarea>
                <small><?php esc_html_e( 'Optional JSON-LD tailored to the About page.', 'chroma-excellence' ); ?></small>
        </p>
        <?php
}

/**
 * Save About page SEO meta fields
 */
function chroma_save_about_meta_box( $post_id ) {
        if ( ! isset( $_POST['chroma_about_meta_nonce_field'] ) || ! wp_verify_nonce( wp_unslash( $_POST['chroma_about_meta_nonce_field'] ), 'chroma_about_meta_nonce' ) ) {
                return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
                return;
        }

        if ( isset( $_POST['post_type'] ) && 'page' === $_POST['post_type'] ) {
                if ( ! current_user_can( 'edit_page', $post_id ) ) {
                        return;
                }
        }

        if ( ! chroma_is_about_template( $post_id ) ) {
                return;
        }

        $meta_title       = isset( $_POST['about_meta_title'] ) ? sanitize_text_field( wp_unslash( $_POST['about_meta_title'] ) ) : '';
        $meta_description = isset( $_POST['about_meta_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['about_meta_description'] ) ) : '';
        $structured_data  = isset( $_POST['about_structured_data'] ) ? sanitize_textarea_field( wp_unslash( $_POST['about_structured_data'] ) ) : '';

        update_post_meta( $post_id, 'about_meta_title', $meta_title );
        update_post_meta( $post_id, 'about_meta_description', $meta_description );
        update_post_meta( $post_id, 'about_structured_data', $structured_data );
}
add_action( 'save_post_page', 'chroma_save_about_meta_box' );

/**
 * Surface About page SEO data in the document head
 */
function chroma_about_meta_output() {
        if ( ! chroma_is_about_template() ) {
                return;
        }

        $seo_fields = chroma_get_about_seo_fields();

        if ( $seo_fields['description'] ) {
                echo '<meta name="description" content="' . esc_attr( wp_strip_all_tags( $seo_fields['description'] ) ) . '" />' . "\n";
        }

        if ( $seo_fields['structured_data'] ) {
                echo '<script type="application/ld+json">' . wp_kses( $seo_fields['structured_data'], array() ) . '</script>' . "\n";
        }
}
add_action( 'wp_head', 'chroma_about_meta_output', 0 );

/**
 * Filter the document title for the About page
 */
function chroma_about_document_title( $title ) {
        if ( chroma_is_about_template() ) {
                $seo_fields = chroma_get_about_seo_fields();

                if ( $seo_fields['title'] ) {
                        return $seo_fields['title'];
                }
        }

        return $title;
}
add_filter( 'pre_get_document_title', 'chroma_about_document_title' );
add_filter( 'wpseo_title', 'chroma_about_document_title' );

/**
 * Filter meta description for SEO plugins
 */
function chroma_about_meta_description_filter( $description ) {
        if ( chroma_is_about_template() ) {
                $seo_fields = chroma_get_about_seo_fields();

                if ( $seo_fields['description'] ) {
                        return $seo_fields['description'];
                }
        }

        return $description;
}
add_filter( 'wpseo_metadesc', 'chroma_about_meta_description_filter' );
