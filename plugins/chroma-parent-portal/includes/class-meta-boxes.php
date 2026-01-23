<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Chroma_Portal_Meta_Boxes {

	public function __construct() {
		add_action( 'add_meta_boxes', [ $this, 'add_custom_boxes' ] );
		add_action( 'save_post', [ $this, 'save_data' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
	}

	public function add_custom_boxes() {
		$screens = [ 'cp_lesson_plan', 'cp_meal_plan', 'cp_resource', 'cp_form' ];
		foreach ( $screens as $screen ) {
			add_meta_box(
				'chroma_portal_pdf_box',
				'Document File (PDF)',
				[ $this, 'render_pdf_box' ],
				$screen
			);
		}

        // PIN Box for Families
        add_meta_box(
            'chroma_portal_pin_box',
            'Family PIN',
            [ $this, 'render_pin_box' ],
            'cp_family'
        );

        // Priority for Announcements
        add_meta_box(
            'chroma_portal_priority_box',
            'Priority',
            [ $this, 'render_priority_box' ],
            'cp_announcement',
            'side'
        );

        // Event Date for Events
        add_meta_box(
            'chroma_portal_event_date_box',
            'Event Date',
            [ $this, 'render_event_date_box' ],
            'cp_event',
            'side'
        );
	}

    public function enqueue_admin_scripts($hook) {
        global $post;
        if ( ! $post || ! in_array($post->post_type, ['cp_lesson_plan', 'cp_meal_plan', 'cp_resource', 'cp_form']) ) {
            return;
        }
        wp_enqueue_media();
    }

	public function render_pdf_box( $post ) {
		$file_id = get_post_meta( $post->ID, '_cp_pdf_file_id', true );
        $file_url = $file_id ? wp_get_attachment_url($file_id) : '';
        
        wp_nonce_field( 'chroma_portal_save_meta', 'chroma_portal_meta_nonce' );
		?>
		<div style="padding: 10px;">
            <p>
                <input type="hidden" id="chroma_portal_pdf_id" name="chroma_portal_pdf_id" value="<?php echo esc_attr( $file_id ); ?>" />
                <button type="button" class="button button-secondary" id="chroma_portal_upload_pdf_btn">Select PDF</button>
                <button type="button" class="button button-link-delete" id="chroma_portal_remove_pdf_btn" style="<?php echo $file_id ? '' : 'display:none;'; ?>">Remove</button>
            </p>
            <p id="chroma_portal_pdf_preview">
                <?php if($file_url): ?>
                    <strong>Selected:</strong> <a href="<?php echo esc_url($file_url); ?>" target="_blank"><?php echo basename($file_url); ?></a>
                <?php else: ?>
                    <span style="color:#666;">No file selected.</span>
                <?php endif; ?>
            </p>

            <script>
            jQuery(document).ready(function($){
                var frame;
                $('#chroma_portal_upload_pdf_btn').on('click', function(e){
                    e.preventDefault();
                    if (frame) { frame.open(); return; }
                    frame = wp.media({
                        title: 'Select PDF Document',
                        button: { text: 'Use this PDF' },
                        library: { type: 'application/pdf' }, // Limit to PDF
                        multiple: false
                    });
                    frame.on('select', function(){
                        var attachment = frame.state().get('selection').first().toJSON();
                        $('#chroma_portal_pdf_id').val(attachment.id);
                        $('#chroma_portal_pdf_preview').html('<strong>Selected:</strong> <a href="'+attachment.url+'" target="_blank">'+attachment.filename+'</a>');
                        $('#chroma_portal_remove_pdf_btn').show();
                    });
                    frame.open();
                });
                $('#chroma_portal_remove_pdf_btn').on('click', function(e){
                    e.preventDefault();
                    $('#chroma_portal_pdf_id').val('');
                    $('#chroma_portal_pdf_preview').html('<span style="color:#666;">No file selected.</span>');
                    $(this).hide();
                });
            });
            </script>
		</div>
		<?php
	}

    public function render_pin_box( $post ) {
        // We do not show the hash, just a field to set a NEW pin
        wp_nonce_field( 'chroma_portal_save_meta', 'chroma_portal_meta_nonce' );
        ?>
        <p>
            <label for="chroma_portal_new_pin">Set New PIN (4-6 digits):</label><br>
            <input type="text" id="chroma_portal_new_pin" name="chroma_portal_new_pin" value="" placeholder="Leave empty to keep current" maxlength="6" pattern="\d{4,6}" />
        </p>
        <p class="description">Enter a number to reset the PIN for this family. If left blank, the existing PIN remains.</p>
        <?php
    }

    public function render_priority_box($post) {
        $val = get_post_meta($post->ID, '_cp_priority', true);
        ?>
         <select name="chroma_portal_priority">
             <option value="normal" <?php selected($val, 'normal'); ?>>Normal</option>
             <option value="high" <?php selected($val, 'high'); ?>>High (Alert)</option>
         </select>
        <?php
    }

    public function render_event_date_box($post) {
        $val = get_post_meta($post->ID, '_cp_event_date', true);
        wp_nonce_field( 'chroma_portal_save_meta', 'chroma_portal_meta_nonce' );
        ?>
        <input type="date" name="chroma_portal_event_date" value="<?php echo esc_attr($val); ?>" />
        <?php
    }


	public function save_data( $post_id ) {
		if ( ! isset( $_POST['chroma_portal_meta_nonce'] ) || ! wp_verify_nonce( $_POST['chroma_portal_meta_nonce'], 'chroma_portal_save_meta' ) ) {
			return;
		}

        // Save PDF
		if ( isset( $_POST['chroma_portal_pdf_id'] ) ) {
			update_post_meta( $post_id, '_cp_pdf_file_id', sanitize_text_field( $_POST['chroma_portal_pdf_id'] ) );
		}

        // Save PIN (Hash it)
        if ( ! empty( $_POST['chroma_portal_new_pin'] ) ) {
            $pin = sanitize_text_field( $_POST['chroma_portal_new_pin'] );
            // Store secure hash (future proof)
            update_post_meta( $post_id, '_cp_pin_hash', wp_hash_password( $pin ) );
            // Store simple hash for lookup performance
            update_post_meta( $post_id, '_cp_pin_simple_hash', md5( $pin ) );
        }

        // Save Priority
         if ( isset( $_POST['chroma_portal_priority'] ) ) {
			update_post_meta( $post_id, '_cp_priority', sanitize_text_field( $_POST['chroma_portal_priority'] ) );
		}

        // Save Event Date
        if ( isset( $_POST['chroma_portal_event_date'] ) ) {
			update_post_meta( $post_id, '_cp_event_date', sanitize_text_field( $_POST['chroma_portal_event_date'] ) );
		}
	}
}

new Chroma_Portal_Meta_Boxes();
