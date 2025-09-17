<?php
/**
 * Metabox handler.
 *
 * @package RSFV
 */

namespace RSFV;

use function RSFV\Settings\get_post_types;
use function RSFV\Settings\get_video_controls;

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

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Adding meta box for featured video.
		add_action( 'add_meta_boxes', array( $this, 'add_metabox' ) );

		// Loading Admin scripts here.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Saving post by updating "featured_video_uploading" meta key.
		add_action( 'save_post', array( $this, 'save_video' ), 10, 1 );

		// Allows display property at style attribute for wp_kses.
		add_filter(
			'safe_style_css',
			function ( $styles ) {
				$styles[] = 'display';
				return $styles;
			}
		);
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
			wp_enqueue_script( 'rsfv_custom_script', RSFV_PLUGIN_URL . 'assets/js/rsfv-media.js', array( 'jquery' ), RSFV_VERSION, true );

			wp_localize_script(
				'rsfv_custom_script',
				'RSFV',
				array(
					'uploader_title'    => __( 'Insert Video', 'rsfv' ),
					'uploader_btn_text' => __( 'Use this video', 'rsfv' ),
					'meta_poster_key'   => RSFV_POSTER_META_KEY,
				)
			);
		}
	}


	/**
	 * Uploads the video.
	 *
	 * @param object $post Post object which holds post data.
	 * @return void
	 */
	public function upload_video( $post ) {

		// Generate nonce field.
		wp_nonce_field( 'rsfv_inner_custom_box', 'rsfv_inner_custom_box_nonce' );

		// Get the meta value of video source.
		$video_source = get_post_meta( $post->ID, RSFV_SOURCE_META_KEY, true );
		$video_source = $video_source ? $video_source : 'self';

		// Get the meta value of video attachment.
		$video_id = get_post_meta( $post->ID, RSFV_META_KEY, true );

		// Get the meta value of video embed url.
		$embed_url = get_post_meta( $post->ID, RSFV_EMBED_META_KEY, true );

		$image     = ' button">' . __( 'Upload Video', 'rsfv' );
		$display   = 'none';
		$video_url = wp_get_attachment_url( $video_id );

		$video_controls = get_video_controls();

		// Get autoplay option.
		$is_autoplay = ( is_array( $video_controls ) && isset( $video_controls['autoplay'] ) ) && $video_controls['autoplay'];
		$is_autoplay = $is_autoplay ? 'autoplay' : '';

		// Get loop option.
		$is_loop = ( is_array( $video_controls ) && isset( $video_controls['loop'] ) ) && $video_controls['loop'];
		$is_loop = $is_loop ? 'loop' : '';

		// Get mute option.
		$is_muted = ( is_array( $video_controls ) && isset( $video_controls['mute'] ) ) && $video_controls['mute'];
		$is_muted = $is_muted ? 'muted' : '';

		// Get PictureInPicture option.
		$is_pip = ( is_array( $video_controls ) && isset( $video_controls['pip'] ) ) && $video_controls['pip'];
		$is_pip = $is_pip ? 'autopictureinpicture' : '';

		// Get video controls option.
		$has_controls = ( is_array( $video_controls ) && isset( $video_controls['controls'] ) ) && $video_controls['controls'];
		$has_controls = $has_controls ? 'controls' : '';

		if ( $video_url ) {
			$image   = '"><video src="' . esc_url( $video_url ) . '" style="max-width:95%;display:block;" ' . esc_attr( $has_controls ) . ' ' . esc_attr( $is_autoplay ) . ' ' . esc_attr( $is_loop ) . ' ' . esc_attr( $is_muted ) . ' ' . esc_attr( $is_pip ) . '></video>';
			$display = 'inline-block';
		}

		$poster_html = '';
		if ( 'self' === $video_source ) {
			$poster_id  = get_post_meta( $post->ID, RSFV_POSTER_META_KEY, true );
			$poster_url = $poster_id ? wp_get_attachment_image_url( $poster_id, 'medium' ) : '';
			$style_img  = $poster_url ? 'display:block;' : 'display:none;';
			$style_btn  = $poster_url ? 'display:inline-block;' : 'display:none;';

			$poster_html  = '<div class="rsfv-poster" style="padding:10px 0;">';
			$poster_html .= sprintf(
				'<img id="rsfv-poster-preview" src="%1$s" style="max-width:95%%;%2$s" />',
				esc_url( $poster_url ),
				esc_attr( $style_img )
			);
			$poster_html .= sprintf(
				'<input type="hidden" name="%1$s" id="%1$s" value="%2$s" />',
				RSFV_POSTER_META_KEY,
				esc_attr( $poster_id )
			);
			$poster_html .= sprintf(
				'<a href="#" class="button rsfv-set-poster">%s</a> ',
				__( 'Set Poster Image', 'rsfv' )
			);
			$poster_html .= sprintf(
				'<a href="#" class="button rsfv-remove-poster" style="%s">%s</a>',
				esc_attr( $style_btn ),
				__( 'Remove Poster', 'rsfv' )
			);
			$poster_html .= '</div>';
		}

		$uploader_markup = sprintf(
			'<div class="rsfv-self"><a href="#" class="rsfv-upload-video-btn%1$s</a><input type="hidden" name="%2$s" id="%2$s" value="%3$s" /><a href="#" class="remove-video" style="display:%4$s;">%5$s</a>%6$s</div>',
			$image,
			RSFV_META_KEY,
			$video_id,
			$display,
			__( 'Remove Video', 'rsfv' ),
			$poster_html
		);

		$embed_markup = sprintf(
			'<div class="rsfv-embed"><input type="url" name="%1$s" id="%1$s" value="%2$s" placeholder="%3$s" /><span><br><br>%4$s</span></div>',
			RSFV_EMBED_META_KEY,
			$embed_url,
			__( 'Video url goes here', 'rsfv' ),
			__( 'Directly copy &amp; paste video urls from Youtube, Vimeo &amp; Dailymotion.', 'rsfv' )
		);

		$self_input = sprintf(
			'<div class="rsfv-radio-wrap"><input type="radio" id="self" name="%1$s" value="self" %2$s><label for="self">%3$s</label></div>%4$s',
			RSFV_SOURCE_META_KEY,
			checked( 'self', $video_source, false ),
			__( 'Self', 'rsfv' ),
			$uploader_markup
		);

		$embed_input = sprintf(
			'<div class="rsfv-radio-wrap"><input type="radio" id="embed" name="%1$s" value="embed" %2$s><label for="embed">%3$s</label></div>%4$s',
			RSFV_SOURCE_META_KEY,
			checked( 'embed', $video_source, false ),
			__( 'Embed', 'rsfv' ),
			$embed_markup
		);

		$select_source = sprintf(
			'<div><p>%1$s</p>%2$s%3$s</div>',
			__( 'Please select a video source', 'rsfv' ),
			$self_input,
			$embed_input
		);

		$styles = '<style>.rsfv-self, .rsfv-embed { padding: 10px 0; } .remove-video { margin-top: 6px; } .rsfv-poster { margin: 8px 0 !important; } .rsfv-set-poster { margin: 4px 0 !important; }</style>';

		echo wp_kses( $select_source, $this->get_allowed_html() );
		echo wp_kses( $styles, $this->get_allowed_html() );
	}

	/**
	 * Saves selected video.
	 *
	 * @param string $post_id Holds post id.
	 * @return string
	 */
	public function save_video( $post_id ) {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		$nonce = isset( $_POST['rsfv_inner_custom_box_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['rsfv_inner_custom_box_nonce'] ) ) : '';

		// Check if nonce is set.
		if ( empty( $nonce ) ) {
			return $post_id;
		}

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
			RSFV_POSTER_META_KEY,
		);

		foreach ( $keys as $key ) {
			// Get updated value.
			$key_value = isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';

			// Save key value in meta key.
			update_post_meta( $post_id, $key, $key_value );
		}

		return $post_id;
	}

	/**
	 * Get a list of allowed html elements.
	 *
	 * @return array Allowed html elements.
	 */
	public function get_allowed_html() {
		return array(
			'video' => array(
				'src'                  => array(),
				'style'                => array(),
				'loop'                 => array(),
				'muted'                => array(),
				'autopictureinpicture' => array(),
				'autoplay'             => array(),
				'controls'             => array(),
			),
			'input' => array(
				'type'        => array(),
				'id'          => array(),
				'name'        => array(),
				'value'       => array(),
				'placeholder' => array(),
				'checked'     => array(),
			),
			'label' => array(
				'for' => array(),
			),
			'div'   => array(
				'class' => array(),
			),
			'a'     => array(
				'href'  => array(),
				'class' => array(),
				'style' => array(),
			),
			'p'     => array(),
			'span'  => array(),
			'br'    => array(),
			'i'     => array(),
			'style' => array(),
			'img'   => array(
				'id'    => array(),
				'src'   => array(),
				'style' => array(),
			),
		);
	}
}
