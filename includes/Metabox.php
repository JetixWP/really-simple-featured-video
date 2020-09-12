<?php
namespace RSFV;

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
		$post_types = 'post, page, product'; // TODO: Set an option for this at settings.
		if ( ! empty( $post_types ) ) {
			add_meta_box( 'featured-video', __( 'Featured Video', 'rsfv' ), array( $this, 'upload_video' ), array( 'post' ), 'side', 'low' );
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

		// Get the meta value of video attachment.
		$video_id = get_post_meta( $post->ID, RSFV_META_KEY, true );

		$image     = ' button">' . __( 'Upload Video', 'rsfv' );
		$display   = 'none';
		$video_url = wp_get_attachment_url( $video_id );

		if ( $video_url ) {
			$image   = '"><video controls="" src="' . $video_url . '" style="max-width:95%;display:block;"></video>';
			$display = 'inline-block';
		}

		printf(
			'<div><a href="#" class="rsfv-upload-video-btn%1$s</a><input type="hidden" name="%2$s" id="%2$s" value="%3$s" /><a href="#" class="remove-video" style="display:%4$s">%5$s</a></div>',
			$image,
			RSFV_META_KEY,
			$video_id,
			$display,
			__( 'Remove Video', 'rsfv' )
		);
	}

	/**
	 * Saves selected video.
	 *
	 * @param $post_id string Holds post id.
	 * @return string
	 */
	public function save_video( $post_id ) {
		if ( ! current_user_can( 'edit_post' ) ) {
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

		$key_value = isset( $_POST[ RSFV_META_KEY ] ) ? sanitize_text_field( $_POST[ RSFV_META_KEY ] ) : '';

		// Save video in meta key.
		update_post_meta( $post_id, RSFV_META_KEY, $key_value );

		return $post_id;
	}
}
