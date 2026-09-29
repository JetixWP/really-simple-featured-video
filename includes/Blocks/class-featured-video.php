<?php
/**
 * Featured Video block.
 *
 * @package RSFV
 * @since   1.1.0
 */

namespace RSFV\Blocks;

use RSFV\Renderer;
use RSFV\Featuresets\Hover_Autoplay\Init as Hover_Autoplay;
use function RSFV\Settings\get_video_controls;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the rsfv/featured-video block and renders it on the server.
 */
class Featured_Video {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	const BLOCK_NAME = 'rsfv/featured-video';

	/**
	 * Class instance.
	 *
	 * @var Featured_Video
	 */
	protected static $instance;

	/**
	 * Get a class instance.
	 *
	 * @return Featured_Video
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_block' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'add_editor_data' ) );
	}

	/**
	 * Path of the built block, empty when the build is missing.
	 *
	 * @return string
	 */
	private function get_block_dir() {
		$dir = RSFV_PLUGIN_DIR . 'assets/blocks/featured-video';

		return file_exists( $dir . '/block.json' ) ? $dir : '';
	}

	/**
	 * Register the block type.
	 *
	 * @return void
	 */
	public function register_block() {
		$dir = $this->get_block_dir();

		if ( '' === $dir ) {
			return;
		}

		register_block_type(
			$dir,
			array(
				'render_callback' => array( $this, 'render' ),
			)
		);

		$handle = generate_block_asset_handle( self::BLOCK_NAME, 'editorScript' );

		wp_set_script_translations( $handle, 'rsfv', RSFV_PLUGIN_DIR . 'languages' );
	}

	/**
	 * Give the editor script what it needs to label settings and link to admin screens.
	 *
	 * @return void
	 */
	public function add_editor_data() {
		if ( '' === $this->get_block_dir() ) {
			return;
		}

		$self_controls  = get_video_controls( 'self' );
		$embed_controls = get_video_controls( 'embed' );
		$controls       = array();

		foreach ( Renderer::CONTROL_KEYS as $key ) {
			$controls[ $key ] = array(
				'self'  => ! empty( $self_controls[ $key ] ),
				'embed' => ! empty( $embed_controls[ $key ] ),
			);
		}

		$data = array(
			'controls'    => $controls,
			'hover'       => (bool) Hover_Autoplay::get_settings()['enable_hover_autoplay'],
			'settingsUrl' => current_user_can( 'manage_options' ) ? admin_url( 'admin.php?page=rsfv-settings' ) : '',
		);

		wp_add_inline_script(
			generate_block_asset_handle( self::BLOCK_NAME, 'editorScript' ),
			'window.rsfvBlock = ' . wp_json_encode( $data ) . ';',
			'before'
		);
	}

	/**
	 * Turn block attributes into renderer arguments.
	 *
	 * Each setting is inherit, on or off. Inherit leaves the global setting alone.
	 *
	 * @param array $attributes Block attributes.
	 *
	 * @return array
	 */
	public function get_render_args( $attributes ) {
		$args = array(
			'surface'  => 'block',
			'restrict' => true,
		);

		foreach ( Renderer::CONTROL_KEYS as $key ) {
			$value = isset( $attributes[ $key ] ) ? $attributes[ $key ] : 'inherit';

			if ( 'on' === $value ) {
				$args[ $key ] = true;
			} elseif ( 'off' === $value ) {
				$args[ $key ] = false;
			}
		}

		$args['hover'] = isset( $attributes['hover'] ) ? $attributes['hover'] : 'inherit';

		/**
		 * Filter the renderer arguments of a Featured Video block.
		 *
		 * @since 1.1.0
		 *
		 * @param array $args       Renderer arguments.
		 * @param array $attributes Block attributes.
		 */
		return apply_filters( 'rsfv_block_args', $args, $attributes );
	}

	/**
	 * Work out which post the block shows.
	 *
	 * @param array          $attributes Block attributes.
	 * @param \WP_Block|null $block      Block instance.
	 *
	 * @return int Zero when there is no post to show.
	 */
	public function get_post_id( $attributes, $block ) {
		if ( isset( $attributes['source'] ) && 'post' === $attributes['source'] ) {
			return isset( $attributes['postId'] ) ? absint( $attributes['postId'] ) : 0;
		}

		if ( $block instanceof \WP_Block && ! empty( $block->context['postId'] ) ) {
			return absint( $block->context['postId'] );
		}

		$post_id = get_the_ID();

		if ( ! $post_id && is_singular() ) {
			$post_id = get_queried_object_id();
		}

		return absint( $post_id );
	}

	/**
	 * Render the block.
	 *
	 * @param array          $attributes Block attributes.
	 * @param string         $content    Inner content, always empty.
	 * @param \WP_Block|null $block      Block instance.
	 *
	 * @return string
	 */
	public function render( $attributes, $content = '', $block = null ) {
		$attributes = is_array( $attributes ) ? $attributes : array();
		$post_id    = $this->get_post_id( $attributes, $block );

		if ( ! $post_id ) {
			return '';
		}

		$markup = Renderer::render( $post_id, $this->get_render_args( $attributes ) );

		if ( '' === $markup && isset( $attributes['fallback'] ) && 'image' === $attributes['fallback'] ) {
			$markup = $this->get_fallback_image( $post_id );
		}

		if ( '' === $markup ) {
			return '';
		}

		$wrapper = get_block_wrapper_attributes( array( 'class' => 'rsfv-has-video' ) );

		return sprintf( '<div %s>%s</div>', $wrapper, $markup );
	}

	/**
	 * Featured image shown when the post has no video.
	 *
	 * The image is read straight from the attachment so the post thumbnail filter,
	 * which swaps images for videos, is not involved.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return string
	 */
	private function get_fallback_image( $post_id ) {
		if ( ! Renderer::can_view_post( $post_id ) ) {
			return '';
		}

		$thumbnail_id = get_post_thumbnail_id( $post_id );

		if ( ! $thumbnail_id ) {
			return '';
		}

		return wp_get_attachment_image( $thumbnail_id, 'large', false, array( 'class' => 'rsfv-block-fallback-image' ) );
	}
}
