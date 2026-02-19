<?php
/**
 * Floating Video feature handler.
 *
 * @package RSFV
 */

namespace RSFV\Featuresets\Floating_Video;

defined( 'ABSPATH' ) || exit;

use RSFV\Options;
use function RSFV\Settings\get_video_controls;

/**
 * Class Init
 *
 * Bootstraps the Floating Video featureset.
 *
 * @package RSFV
 */
class Init {
	/**
	 * Class instance.
	 *
	 * @var Init
	 */
	protected static $instance;

	/**
	 * Custom post type slug.
	 *
	 * @var string
	 */
	const POST_TYPE = 'rsfv_floating_video';

	/**
	 * Get a class instance.
	 *
	 * @return Init
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
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );

		// Extend Tools localized data with floating video info.
		add_filter( 'rsfv_tools_localized_data', array( $this, 'extend_tools_data' ) );
	}

	/**
	 * Register the custom post type for floating videos.
	 */
	public function register_post_type() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'          => array(
					'name'          => __( 'Floating Videos', 'rsfv' ),
					'singular_name' => __( 'Floating Video', 'rsfv' ),
				),
				'public'          => false,
				'show_ui'         => false,
				'show_in_rest'    => false,
				'supports'        => array( 'title' ),
				'capability_type' => 'post',
				'map_meta_cap'    => true,
			)
		);
	}

	/**
	 * Register REST API routes.
	 */
	public function register_routes() {
		$rest_api = new REST_API();
		$rest_api->register_routes();
	}

	/**
	 * Extend the Tools localized data with pages/taxonomies for display conditions.
	 *
	 * @param array $data Existing localized data.
	 * @return array
	 */
	public function extend_tools_data( $data ) {
		$data['floatingVideoNonce'] = wp_create_nonce( 'wp_rest' );
		$data['pages']              = $this->get_pages_options();
		$data['taxonomies']         = $this->get_taxonomies_options();
		$data['postTypesAll']       = $this->get_all_public_post_types();

		return $data;
	}

	/**
	 * Get all published pages as options for display conditions.
	 *
	 * @return array
	 */
	private function get_pages_options() {
		$pages   = get_pages(
			array(
				'post_status' => 'publish',
				'number'      => 200,
			)
		);
		$options = array();

		foreach ( $pages as $page ) {
			$options[] = array(
				'value' => $page->ID,
				'label' => $page->post_title,
			);
		}

		return $options;
	}

	/**
	 * Get public taxonomies as options.
	 *
	 * @return array
	 */
	private function get_taxonomies_options() {
		$taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );
		$options    = array();

		foreach ( $taxonomies as $taxonomy ) {
			$terms = get_terms(
				array(
					'taxonomy'   => $taxonomy->name,
					'hide_empty' => false,
					'number'     => 100,
				)
			);

			if ( is_wp_error( $terms ) || empty( $terms ) ) {
				continue;
			}

			$term_options = array();
			foreach ( $terms as $term ) {
				$term_options[] = array(
					'value' => $term->term_id,
					'label' => $term->name,
				);
			}

			$options[] = array(
				'name'  => $taxonomy->name,
				'label' => $taxonomy->labels->name,
				'terms' => $term_options,
			);
		}

		return $options;
	}

	/**
	 * Get all public post types.
	 *
	 * @return array
	 */
	private function get_all_public_post_types() {
		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		$options    = array();

		foreach ( $post_types as $pt ) {
			if ( 'attachment' === $pt->name ) {
				continue;
			}

			$options[] = array(
				'value' => $pt->name,
				'label' => $pt->labels->name,
			);
		}

		return $options;
	}

	/**
	 * Enqueue frontend assets for the floating video widget.
	 */
	public function enqueue_frontend_assets() {
		// Don't load on admin.
		if ( is_admin() ) {
			return;
		}

		$matching_videos = $this->get_matching_floating_videos();

		if ( empty( $matching_videos ) ) {
			return;
		}

		// Register & enqueue CSS.
		wp_enqueue_style(
			'rsfv-floating-video',
			RSFV_PLUGIN_URL . 'assets/css/floating-video.css',
			array(),
			filemtime( RSFV_PLUGIN_DIR . 'assets/css/floating-video.css' )
		);

		// Register & enqueue JS.
		wp_enqueue_script(
			'rsfv-floating-video',
			RSFV_PLUGIN_URL . 'assets/js/floating-video.js',
			array(),
			filemtime( RSFV_PLUGIN_DIR . 'assets/js/floating-video.js' ),
			true
		);

		// Build videos array for the frontend.
		$videos = array();

		foreach ( $matching_videos as $floating_video ) {
			$meta = $this->get_floating_video_meta( $floating_video->ID );

			$video_url = '';
			if ( 'self' === $meta['video_source'] && ! empty( $meta['video_id'] ) ) {
				$video_url = wp_get_attachment_url( $meta['video_id'] );
			} elseif ( 'embed' === $meta['video_source'] && ! empty( $meta['embed_url'] ) ) {
				$video_url = $meta['embed_url'];
			}

			$videos[] = array(
				'videoSource' => $meta['video_source'],
				'videoUrl'    => $video_url ? esc_url( $video_url ) : '',
				'embedUrl'    => ! empty( $meta['embed_url'] ) ? esc_url( $meta['embed_url'] ) : '',
				'title'       => esc_attr( $floating_video->post_title ),
			);
		}

		// Get video control settings.
		$self_controls  = function_exists( '\RSFV\Settings\get_video_controls' ) ? get_video_controls( 'self' ) : array( 'controls' => true );
		$embed_controls = function_exists( '\RSFV\Settings\get_video_controls' ) ? get_video_controls( 'embed' ) : array( 'controls' => true );

		/**
		 * Filter the aspect ratio used by the floating video popup.
		 *
		 * Default is '16/9'. PRO can override this via the global aspect ratio setting.
		 *
		 * @param string $aspect_ratio Aspect ratio in 'W/H' format (e.g. '16/9', '4/3', '1/1').
		 */
		$aspect_ratio = apply_filters( 'rsfv_floating_video_aspect_ratio', '16/9' );

		wp_localize_script(
			'rsfv-floating-video',
			'RSFVFloatingVideo',
			array(
				'videos'        => $videos,
				'selfControls'  => $self_controls,
				'embedControls' => $embed_controls,
				'aspectRatio'   => $aspect_ratio,
				'layout'        => Options::get_instance()->get( 'floating_video_layout', 'standard' ),
			)
		);

		/**
		 * Fires after the floating video assets are enqueued.
		 *
		 * PRO and other extensions hook here to enqueue their own layout assets
		 * (JS / CSS) that extend the base floating video player.
		 *
		 * @param array  $videos       Videos data array passed to the frontend.
		 * @param string $aspect_ratio Aspect ratio string, e.g. '16/9'.
		 */
		do_action( 'rsfv_floating_video_after_enqueue', $videos, $aspect_ratio );
	}

	/**
	 * Get the floating video that matches current page conditions.
	 *
	 * @return \WP_Post|null
	 */
	public function get_matching_floating_videos() {
		$floating_videos = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		if ( empty( $floating_videos ) ) {
			return array();
		}

		$matches = array();

		foreach ( $floating_videos as $video ) {
			if ( $this->matches_display_conditions( $video->ID ) ) {
				$matches[] = $video;
			}
		}

		return $matches;
	}

	/**
	 * Check if a floating video matches the current page's display conditions.
	 *
	 * @param int $post_id The floating video post ID.
	 * @return bool
	 */
	public function matches_display_conditions( $post_id ) {
		$display_type = get_post_meta( $post_id, '_rsfv_fv_display_type', true );

		if ( empty( $display_type ) ) {
			return false;
		}

		switch ( $display_type ) {
			case 'sitewide':
				return true;

			case 'specific_pages':
				$page_ids = get_post_meta( $post_id, '_rsfv_fv_page_ids', true );

				if ( ! is_array( $page_ids ) || empty( $page_ids ) ) {
					return false;
				}

				// Get current queried object ID.
				$current_id = get_queried_object_id();

				return in_array( $current_id, array_map( 'intval', $page_ids ), true );

			case 'post_types':
				$target_post_types = get_post_meta( $post_id, '_rsfv_fv_target_post_types', true );

				if ( ! is_array( $target_post_types ) || empty( $target_post_types ) ) {
					return false;
				}

				if ( is_singular( $target_post_types ) ) {
					return true;
				}

				if ( is_post_type_archive( $target_post_types ) ) {
					return true;
				}

				return false;

			case 'taxonomies':
				$target_taxonomies = get_post_meta( $post_id, '_rsfv_fv_target_taxonomies', true );

				if ( ! is_array( $target_taxonomies ) || empty( $target_taxonomies ) ) {
					return false;
				}

				// Check if on a taxonomy archive matching any of the selected terms.
				foreach ( $target_taxonomies as $tax_data ) {
					$taxonomy = $tax_data['taxonomy'] ?? '';
					$term_ids = $tax_data['terms'] ?? array();

					if ( empty( $taxonomy ) || empty( $term_ids ) ) {
						continue;
					}

					// Check if we're on a term archive for this taxonomy.
					if ( is_tax( $taxonomy, array_map( 'intval', $term_ids ) ) || is_category( array_map( 'intval', $term_ids ) ) || is_tag( array_map( 'intval', $term_ids ) ) ) {
						return true;
					}

					// Check if the current single post has any of the target terms.
					if ( is_singular() ) {
						$post_terms = wp_get_object_terms( get_queried_object_id(), $taxonomy, array( 'fields' => 'ids' ) );
						if ( ! is_wp_error( $post_terms ) && array_intersect( array_map( 'intval', $term_ids ), $post_terms ) ) {
							return true;
						}
					}
				}

				return false;

			default:
				return false;
		}
	}

	/**
	 * Get floating video metadata.
	 *
	 * @param int $post_id Post ID.
	 * @return array
	 */
	public function get_floating_video_meta( $post_id ) {
		$video_source = get_post_meta( $post_id, '_rsfv_fv_video_source', true );
		$video_id     = get_post_meta( $post_id, '_rsfv_fv_video_id', true );
		$embed_url    = get_post_meta( $post_id, '_rsfv_fv_embed_url', true );

		return array(
			'video_source' => ! empty( $video_source ) ? $video_source : 'self',
			'video_id'     => ! empty( $video_id ) ? $video_id : 0,
			'embed_url'    => ! empty( $embed_url ) ? $embed_url : '',
		);
	}
}
