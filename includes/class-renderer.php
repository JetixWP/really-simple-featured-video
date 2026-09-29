<?php
/**
 * Shared featured video renderer.
 *
 * One place that turns a post ID into player markup, so shortcodes, the block and
 * builder integrations all follow the same rules.
 *
 * @package RSFV
 * @since   1.1.0
 */

namespace RSFV;

use RSFV\Featuresets\Hover_Autoplay\Init as Hover_Autoplay;
use function RSFV\Settings\get_post_types;

defined( 'ABSPATH' ) || exit;

/**
 * Class Renderer
 */
class Renderer {

	/**
	 * Player controls that can be overridden per instance.
	 *
	 * The keys match the saved global controls, so an override is a plain merge.
	 *
	 * @var string[]
	 */
	const CONTROL_KEYS = array( 'controls', 'autoplay', 'loop', 'mute', 'pip', 'download' );

	/**
	 * Values accepted for the hover argument.
	 *
	 * @var string[]
	 */
	const HOVER_VALUES = array( 'inherit', 'on', 'off' );

	/**
	 * Clean up render arguments.
	 *
	 * Accepts booleans, or the strings a shortcode passes, and drops anything unknown.
	 * A control that is missing or empty inherits the global setting.
	 *
	 * @param array $args Raw arguments.
	 *
	 * @return array {
	 *     @type bool[]      $controls Per control overrides, keyed by control name.
	 *     @type string      $hover    inherit, on or off.
	 *     @type string      $surface  Analytics surface, empty to keep the current one.
	 *     @type bool        $restrict Whether the viewer must be allowed to see the post.
	 * }
	 */
	public static function normalize_args( $args ) {
		$args = is_array( $args ) ? $args : array();

		$clean = array(
			'controls' => array(),
			'hover'    => 'inherit',
			'surface'  => '',
			'restrict' => ! isset( $args['restrict'] ) || (bool) $args['restrict'],
		);

		foreach ( self::CONTROL_KEYS as $key ) {
			if ( ! isset( $args[ $key ] ) ) {
				continue;
			}

			$enabled = self::parse_flag( $args[ $key ] );

			if ( null !== $enabled ) {
				$clean['controls'][ $key ] = $enabled;
			}
		}

		if ( isset( $args['hover'] ) ) {
			$hover = is_string( $args['hover'] ) ? strtolower( trim( $args['hover'] ) ) : '';

			if ( in_array( $hover, self::HOVER_VALUES, true ) ) {
				$clean['hover'] = $hover;
			}
		}

		if ( ! empty( $args['surface'] ) && is_string( $args['surface'] ) ) {
			$clean['surface'] = sanitize_key( $args['surface'] );
		}

		/**
		 * Filter the cleaned render arguments.
		 *
		 * @since 1.1.0
		 *
		 * @param array $clean Cleaned arguments.
		 * @param array $args  Raw arguments.
		 */
		return apply_filters( 'rsfv_render_args', $clean, $args );
	}

	/**
	 * Read a true or false value from a boolean, number or shortcode string.
	 *
	 * @param mixed $value Raw value.
	 *
	 * @return bool|null Null when the value is empty or not recognised, so the global setting stays.
	 */
	private static function parse_flag( $value ) {
		if ( is_bool( $value ) ) {
			return $value;
		}

		if ( ! is_string( $value ) && ! is_int( $value ) ) {
			return null;
		}

		$value = strtolower( trim( (string) $value ) );

		if ( in_array( $value, array( '1', 'true', 'yes', 'on' ), true ) ) {
			return true;
		}

		if ( in_array( $value, array( '0', 'false', 'no', 'off' ), true ) ) {
			return false;
		}

		return null;
	}

	/**
	 * Apply per instance control overrides on top of the global controls.
	 *
	 * @param array $controls Global controls.
	 * @param array $args     Cleaned arguments.
	 *
	 * @return array
	 */
	public static function apply_control_overrides( $controls, $args ) {
		$controls = is_array( $controls ) ? $controls : array();

		if ( empty( $args['controls'] ) || ! is_array( $args['controls'] ) ) {
			return $controls;
		}

		foreach ( $args['controls'] as $key => $enabled ) {
			if ( $enabled ) {
				$controls[ $key ] = true;
			} else {
				unset( $controls[ $key ] );
			}
		}

		return $controls;
	}

	/**
	 * Whether the current viewer may see a post at all.
	 *
	 * Public posts are open to everyone, other statuses need permission to read
	 * the post, and a post that still asks for its password stays hidden.
	 *
	 * @since 1.1.0
	 *
	 * @param \WP_Post|int|null $post Post object or ID.
	 *
	 * @return bool
	 */
	public static function can_view_post( $post ) {
		$post = get_post( $post );

		if ( ! $post instanceof \WP_Post ) {
			return false;
		}

		if ( ! is_post_publicly_viewable( $post ) && ! current_user_can( 'read_post', $post->ID ) ) {
			return false;
		}

		return ! post_password_required( $post );
	}

	/**
	 * Whether the current viewer may see the featured video of a post.
	 *
	 * @param \WP_Post|int|null $post Post object or ID.
	 *
	 * @return bool
	 */
	public static function is_viewable( $post ) {
		$post = get_post( $post );

		if ( ! $post instanceof \WP_Post ) {
			return false;
		}

		if ( ! in_array( $post->post_type, get_post_types(), true ) ) {
			return false;
		}

		return self::can_view_post( $post );
	}

	/**
	 * Render the featured video of a post.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $args    Optional arguments, see normalize_args().
	 *
	 * @return string Player markup, empty when there is nothing to show.
	 */
	public static function render( $post_id, $args = array() ) {
		$post = get_post( absint( $post_id ) );

		if ( ! $post instanceof \WP_Post ) {
			return '';
		}

		$args = self::normalize_args( $args );

		if ( $args['restrict'] && ! self::is_viewable( $post ) ) {
			return '';
		}

		$previous_surface = '';
		$has_stamp        = class_exists( '\RSFV\Analytics\Stamp' );

		if ( $has_stamp && '' !== $args['surface'] ) {
			$previous_surface = \RSFV\Analytics\Stamp::swap_surface( $args['surface'] );
		}

		$previous_hover = Hover_Autoplay::set_override( self::hover_override( $args['hover'] ) );

		// The override is active here, so the assets load even when hover is off globally.
		if ( 'on' === $args['hover'] ) {
			Hover_Autoplay::ensure_assets();
		}

		$markup = Shortcode::get_instance()->get_video_markup( $post->ID, $post->post_type, $args );

		if ( '' !== $markup ) {
			$source = get_post_meta( $post->ID, RSFV_SOURCE_META_KEY, true );

			/** This filter is documented in includes/class-shortcode.php */
			$markup = apply_filters(
				'rsfv_shortcode_video_output',
				$markup,
				array(
					'post_id'   => $post->ID,
					'post_type' => $post->post_type,
					'source'    => $source ? $source : 'self',
					'args'      => $args,
				)
			);
		}

		Hover_Autoplay::set_override( $previous_hover );

		if ( $has_stamp && '' !== $args['surface'] ) {
			\RSFV\Analytics\Stamp::swap_surface( $previous_surface );
		}

		return $markup;
	}

	/**
	 * Turn the hover argument into a settings override.
	 *
	 * @param string $hover inherit, on or off.
	 *
	 * @return bool|null Null to keep the global setting.
	 */
	private static function hover_override( $hover ) {
		if ( 'on' === $hover ) {
			return true;
		}

		if ( 'off' === $hover ) {
			return false;
		}

		return null;
	}
}
