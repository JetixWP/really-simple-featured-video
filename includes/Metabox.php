<?php
namespace RSFV;

use function RSFV\Settings\get_post_types;

/**
 * Class Metabox
 */
class Metabox {
	/**
	 * Class instance.
	 *
	 * @var $instance
	 */
	protected static $instance;

	public function __construct() {
		// Adding meta box for featured video
		add_action( 'add_meta_boxes', array( $this, 'add_metabox' ) );

		// Loading Admin scripts here
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Saving post by updating , "featured_video_uploading" meta key
		add_action( 'save_post', array( $this, 'save_video' ), 10, 1 );
	}

	/**
	 * Get an instance of class.
	 *
	 * @return Metabox
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register Featured Video metabox.
	 *
	 * @return void
	 */
	public function add_metabox() {
		// Get enabled post types.
		$post_types = get_post_types();
		if ( ! empty( $post_types ) ) {
			add_meta_box( 'featured-video', __( 'Featured Video', 'rsfv' ), array( $this, 'upload_video' ), $post_types, 'side', 'low' );
		}
	}

	/**
	 * Enqueues scripts required for media uploader.
	 *
	 * @retun void
	 */
	public function enqueue_scripts() {
		global $pagenow;

		if ( 'post.php' === $pagenow || 'post-new.php' === $pagenow ) {
			// Enqueue all necessary WP Media APIs.
			wp_enqueue_media();
			// Enqueue plugin script.
			wp_enqueue_script( 'rsfv_custom_script', RSFV_PLUGIN_URL . 'assets/js/rsfv-media.js', array( 'jquery' ), RSFV_VERSION );

			wp_localize_script(
				'rsfv_custom_script',
				'RSFV',
				array(
					'uploader_title'    => __( 'Insert Video', 'rsfv' ),
					'uploader_btn_text' => __( 'Use this video', 'rsfv' ),
				)
			);
		}
	}


	/**
	 * Uploads the video.
	 *
	 * @param $post object Post object which holds post data.
	 * @return void
	 */
	public function upload_video( $post ) {

		// Generate nonce field.
		wp_nonce_field( 'rsfv_inner_custom_box', 'rsfv_inner_custom_box_nonce' );

		// Get the meta value of video embed url.
		$video_source = get_post_meta( $post->ID, RSFV_SOURCE_META_KEY, true );

		// Get the meta value of video attachment.
		$video_id = get_post_meta( $post->ID, RSFV_META_KEY, true );

		// Get the meta value of video embed url.
		$embed_url = get_post_meta( $post->ID, RSFV_EMBED_META_KEY, true );

		$image     = ' button">' . __( 'Upload Video', 'rsfv' );
		$display   = 'none';
		$video_url = wp_get_attachment_url( $video_id );

		// Get autoplay option.
		$is_autoplay = Options::get_instance()->get( 'video_autoplay' );
		$is_autoplay = $is_autoplay ? 'autoplay' : '';

		// Get loop option.
		$is_loop = Options::get_instance()->get( 'video_loop' );
		$is_loop = $is_loop ? 'loop' : '';

		// Get mute option.
		$is_muted = Options::get_instance()->get( 'mute_video' );
		$is_muted = $is_muted ? 'muted' : '';

		// Get Picture-In-Picture option.
		$is_pip = Options::get_instance()->get( 'picture_in_picture' );
		$is_pip = $is_pip ? 'autopictureinpicture' : '';

		// Get video controls option.
		$has_controls = Options::get_instance()->get( 'video_controls' );
		$has_controls = $has_controls ? 'controls' : '';

		if ( $video_url ) {
			$image   = '"><video src="' . esc_url( $video_url ) . '" style="max-width:95%;display:block;"' . "{$has_controls} {$is_autoplay} {$is_loop} {$is_muted} {$is_pip}" . '></video>';
			$display = 'inline-block';
		}

		$uploader_markup = sprintf(
			'<div class="rsfv-self"><a href="#" class="rsfv-upload-video-btn%1$s</a><input type="hidden" name="%2$s" id="%2$s" value="%3$s" /><a href="#" class="remove-video" style="display:%4$s">%5$s</a></div>',
			$image,
			RSFV_META_KEY,
			$video_id,
			$display,
			__( 'Remove Video', 'rsfv' )
		);

		$embed_markup = sprintf(
			'<div class="rsfv-embed"><input type="url" name="%1$s" id="%1$s" value="%2$s" placeholder="Video url goes here" /></div>',
			RSFV_EMBED_META_KEY,
			$embed_url
		);

		$self_input = sprintf(
			'<input type="radio" id="self" name="%1$s" value="self" %2$s><label for="self">%3$s</label><br>%4$s',
			RSFV_SOURCE_META_KEY,
			checked( 'self', $video_source, false ),
			__( 'Self', 'rsfv' ),
			$uploader_markup
		);

		$embed_input = sprintf(
			'<input type="radio" id="embed" name="%1$s" value="embed" %2$s><label for="embed">%3$s</label><br>%4$s',
			RSFV_SOURCE_META_KEY,
			checked( 'embed', $video_source, false ),
			__( 'Embed', 'rsfv' ),
			$embed_markup
		);

		$select_source = sprintf(
			'<div><p>%1$s</p>%2$s%3$s</div>',
			__( 'Please select video source', 'rsfv' ),
			$self_input,
			$embed_input
		);

		$styles = '<style>.rsfv-self, .rsfv-embed { padding: 10px 0; } .remove-video { margin-top: 6px; }</style>';

		printf(
			'%1$s%2$s',
			$select_source,
			$styles,
		);
	}

	/**
	 * Saves selected video.
	 *
	 * @param $post_id string Holds post id.
	 * @return string
	 */
	public function save_video( $post_id ) {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		// Check if nonce is set.
		if ( ! isset( $_POST['rsfv_inner_custom_box_nonce'] ) ) {
			return $post_id;
		}

		$nonce = $_POST['rsfv_inner_custom_box_nonce']; // phpcs:ignore

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $nonce, 'rsfv_inner_custom_box' ) ) {
			return $post_id;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		$keys = array(
			RSFV_SOURCE_META_KEY,
			RSFV_META_KEY,
			RSFV_EMBED_META_KEY,
		);

		foreach ( $keys as $key ) {
			// Get updated value.
			$key_value = isset( $_POST[ $key ] ) ? sanitize_text_field( $_POST[ $key ] ) : '';

			// Save key value in meta key.
			update_post_meta( $post_id, $key, $key_value );
		}

		return $post_id;
	}
}
