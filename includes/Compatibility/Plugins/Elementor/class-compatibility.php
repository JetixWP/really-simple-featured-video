<?php
/**
 * Elementor's compatibility handler.
 *
 * @package RSFV
 */

namespace RSFV\Compatibility\Plugins\Elementor;

defined( 'ABSPATH' ) || exit;

use RSFV\Compatibility\Plugins\Base_Compatibility;
use RSFV\Compatibility\Plugins\Elementor\Widgets\RSFV_Video_Widget;
use RSFV\FrontEnd;
use RSFV\Options;
use function RSFV\Settings\get_post_types;

/**
 * Class Compatibility
 *
 * @package RSFV
 */
class Compatibility extends Base_Compatibility {
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
		parent::__construct();

		$this->id = 'elementor';

		$this->setup();
	}

	/**
	 * Register Settings.
	 *
	 * @param array $settings Active settings file array.
	 *
	 * @return array
	 */
	public function register_settings( $settings ) {
		// Settings.
		$settings[] = include 'class-settings.php';

		return $settings;
	}

	/**
	 * Sets up hooks and filters.
	 *
	 * @return void
	 */
	public function setup() {
		add_filter( 'rsfv_get_settings_pages', array( $this, 'register_settings' ) );

		$options = Options::get_instance();

		$disable_elementor_support = $options->get( 'disable_elementor_support' );

		if ( ! $options->has( 'disable_elementor_support' ) || ! $disable_elementor_support ) {
			add_filter( 'elementor/image_size/get_attachment_image_html', array( $this, 'update_with_video_html' ), 10, 4 );
		}

		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );
		add_filter( 'get_post_metadata', array( $this, 'prefill_widget_meta' ), 10, 4 );
		add_action( 'elementor/editor/after_enqueue_scripts', array( $this, 'enqueue_editor_scripts' ) );
	}

	/**
	 * Register Elementor widgets.
	 *
	 * @since 0.73.0
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
	 *
	 * @return void
	 */
	public function register_widgets( $widgets_manager ) {
		require_once __DIR__ . '/widgets/class-rsfv-video-widget.php';

		$widgets_manager->register( new RSFV_Video_Widget() );
	}

	/**
	 * Enqueue editor-only JS that populates the RSFV Video widget
	 * controls from existing post meta when the widget is freshly added
	 * (before it has been saved into `_elementor_data`).
	 *
	 * @since 0.73.0
	 *
	 * @return void
	 */
	public function enqueue_editor_scripts() {
		$post_id = get_the_ID();

		if ( ! $post_id ) {
			return;
		}

		// Only for RSFV-enabled post types.
		$post_type     = get_post_type( $post_id );
		$enabled_types = get_post_types();

		if ( ! $post_type || ! in_array( $post_type, $enabled_types, true ) ) {
			return;
		}

		$rsfv_meta = $this->get_rsfv_meta( $post_id );

		// Nothing to localize if no featured video exists.
		if ( empty( $rsfv_meta ) ) {
			return;
		}

		wp_enqueue_script(
			'rsfv-elementor-editor',
			RSFV_PLUGIN_URL . 'assets/js/rsfv-elementor-editor.js',
			array( 'elementor-editor' ),
			RSFV_VERSION,
			true
		);

		wp_localize_script( 'rsfv-elementor-editor', 'rsfvElementorMeta', $rsfv_meta );
	}

	/**
	 * Filter `_elementor_data` on read so that every `rsfv_video` widget
	 * whose controls are still at their defaults is pre-filled from the
	 * post's existing RSFV meta. Runs server-side before the editor JS
	 * even loads, so the controls appear populated instantly.
	 *
	 * @since 0.73.0
	 *
	 * @param mixed  $value    Current meta value (null on first call).
	 * @param int    $post_id  Post ID.
	 * @param string $meta_key Meta key being read.
	 * @param bool   $single   Whether a single value was requested.
	 *
	 * @return mixed
	 */
	public function prefill_widget_meta( $value, $post_id, $meta_key, $single ) {
		if ( '_elementor_data' !== $meta_key ) {
			return $value;
		}

		// Only run for post types that are enabled in RSFV settings.
		$post_type     = get_post_type( $post_id );
		$enabled_types = get_post_types();

		if ( ! $post_type || ! in_array( $post_type, $enabled_types, true ) ) {
			return $value;
		}

		// Prevent infinite recursion: unhook → read → re-hook.
		remove_filter( 'get_post_metadata', array( $this, 'prefill_widget_meta' ), 10 );
		$raw = get_post_meta( $post_id, '_elementor_data', true );
		add_filter( 'get_post_metadata', array( $this, 'prefill_widget_meta' ), 10, 4 );

		if ( empty( $raw ) ) {
			return $value;
		}

		$elements = is_string( $raw ) ? json_decode( $raw, true ) : $raw;

		if ( ! is_array( $elements ) ) {
			return $value;
		}

		// Gather RSFV meta once.
		$rsfv_meta = $this->get_rsfv_meta( $post_id );

		// Nothing to pre-fill if no featured video is configured.
		if ( empty( $rsfv_meta ) ) {
			return $value;
		}

		$changed  = false;
		$elements = $this->walk_elements( $elements, $rsfv_meta, $changed );

		if ( ! $changed ) {
			return $value;
		}

		// Return in the format WordPress expects from this filter.
		// When $single is true WP takes index [0] of the returned array.
		return array( wp_json_encode( $elements ) );
	}

	/**
	 * Collect the post's RSFV meta into a handy array.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return array
	 */
	private function get_rsfv_meta( $post_id ) {
		// Verify the post type is enabled in RSFV settings.
		$post_type     = get_post_type( $post_id );
		$enabled_types = get_post_types();

		if ( ! $post_type || ! in_array( $post_type, $enabled_types, true ) ) {
			return array();
		}

		$source = get_post_meta( $post_id, RSFV_SOURCE_META_KEY, true );
		$source = $source ? $source : 'self';

		$video_id  = get_post_meta( $post_id, RSFV_META_KEY, true );
		$poster_id = get_post_meta( $post_id, RSFV_POSTER_META_KEY, true );
		$embed_url = get_post_meta( $post_id, RSFV_EMBED_META_KEY, true );

		// If neither a self-hosted video nor an embed URL is set there is
		// nothing to pre-fill.
		if ( 'self' === $source && empty( $video_id ) ) {
			if ( empty( $embed_url ) ) {
				return array();
			}
		}
		if ( 'embed' === $source && empty( $embed_url ) ) {
			if ( empty( $video_id ) ) {
				return array();
			}
		}

		return array(
			'source'     => $source,
			'video_id'   => $video_id ? absint( $video_id ) : '',
			'video_url'  => $video_id ? wp_get_attachment_url( $video_id ) : '',
			'poster_id'  => $poster_id ? absint( $poster_id ) : '',
			'poster_url' => $poster_id ? wp_get_attachment_url( $poster_id ) : '',
			'embed_url'  => $embed_url ? $embed_url : '',
		);
	}

	/**
	 * Recursively walk the Elementor element tree and inject RSFV meta
	 * into any `rsfv_video` widget that has empty/default controls.
	 *
	 * @param array $elements Elementor element tree.
	 * @param array $meta     RSFV meta values.
	 * @param bool  $changed  Reference flag flipped when a widget is updated.
	 *
	 * @return array Modified elements.
	 */
	private function walk_elements( array $elements, array $meta, bool &$changed ) {
		foreach ( $elements as &$element ) {
			if (
				'widget' === ( $element['elType'] ?? '' ) &&
				'rsfv_video' === ( $element['widgetType'] ?? '' )
			) {
				$element = $this->maybe_inject_meta( $element, $meta, $changed );
			}

			if ( ! empty( $element['elements'] ) ) {
				$element['elements'] = $this->walk_elements( $element['elements'], $meta, $changed );
			}
		}
		unset( $element );

		return $elements;
	}

	/**
	 * Inject RSFV post meta into a single widget's settings when they are
	 * still at their defaults (empty).
	 *
	 * @param array $element Widget element data.
	 * @param array $meta    RSFV meta values.
	 * @param bool  $changed Reference flag.
	 *
	 * @return array
	 */
	private function maybe_inject_meta( array $element, array $meta, bool &$changed ) {
		$s = $element['settings'] ?? array();

		// Only inject when source is "current_post" (or not set = default).
		$video_source = $s['video_source'] ?? 'current_post';
		if ( 'current_post' !== $video_source ) {
			return $element;
		}

		// Already has explicit values – don't overwrite.
		$has_self_video = ! empty( $s['self_video']['url'] ) || ! empty( $s['self_video']['id'] );
		$has_embed_url  = ! empty( $s['embed_url']['url'] );

		if ( $has_self_video || $has_embed_url ) {
			return $element;
		}

		$source = $meta['source'] ?? 'self';

		$s['video_type'] = $source;

		if ( 'self' === $source && ! empty( $meta['video_id'] ) ) {
			$s['self_video'] = array(
				'id'  => $meta['video_id'],
				'url' => $meta['video_url'],
			);

			if ( ! empty( $meta['poster_id'] ) ) {
				$s['poster_image'] = array(
					'id'  => $meta['poster_id'],
					'url' => $meta['poster_url'],
				);
			}

			$changed = true;
		} elseif ( 'embed' === $source && ! empty( $meta['embed_url'] ) ) {
			$s['embed_url'] = array(
				'url' => $meta['embed_url'],
			);

			$changed = true;
		}

		$element['settings'] = $s;

		return $element;
	}

	/**
	 * Override Elementor Pro's post widget featured image html.
	 *
	 * @since 0.8.6
	 *
	 * @param string $html ex html markup.
	 * @param array  $settings Settings array of parent widget/element.
	 * @param string $image_size_key Image size key.
	 * @param string $image_key Image key.
	 *
	 * @return string
	 */
	public function update_with_video_html( $html, $settings, $image_size_key, $image_key ) {
		// Exit early if Elementor Pro isn't active.
		if ( ! class_exists( 'ElementorPro\Plugin' ) ) {
			return $html;
		}

		// Exit if the image contains site-logo.
		if ( isset( $settings['__dynamic__'] ) ) {
			$image = $settings['__dynamic__']['image'] ?? '';
			if ( str_contains( $image, 'site-logo' ) ) {
				return $html;
			}
		}

		// If the image markup is from posts/archive/featured image widgets.
		if ( is_array( $settings ) && ( isset( $settings['posts_post_type'] ) || isset( $settings['archive_classic_thumbnail'] ) || isset( $settings['__dynamic__'] ) ) ) {
			global $post;

			// Check if the $post object is not defined.
			if ( 'object' !== gettype( $post ) ) {
				return $html;
			}

			$post_id = $post->ID;

			return FrontEnd::get_featured_video_markup( $post_id, $html );
		}

		return $html;
	}
}
