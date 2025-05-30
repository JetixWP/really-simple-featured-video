<?php
/**
 * Bricks element for Really Simple Featured Video
 *
 * @package RSFV
 * @subpackage Bricks\Elements
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use RSFV\FrontEnd;

/**
 * Bricks element class for Really Simple Featured Video.
 *
 * @since 1.0.0
 */
class Bricks_Really_Simple_Featured_Video extends \Bricks\Element {

	/**
	 * Element name.
	 *
	 * @var string
	 */
	public $name = 'bricks_really_simple_featured_video';

	/**
	 * Element category.
	 *
	 * @var string
	 */
	public $category = 'media';

	/**
	 * Element icon.
	 *
	 * @var string
	 */
	public $icon = 'ti-video-clapper';

    /**
	 * CSS selector.
	 *
	 * @var string
	 */
    public $css_selector = '.bricks-really-simple-featured-video';

    /**
	 * Element label.
	 *
	 * @return string Element label
	 */
	public function get_label() {
		return esc_html__( 'Really Simple Featured Video', 'rsfv' );
	}

	/**
	 * Get element keywords.
	 *
	 * @return array Element keywords.
	 */
	public function get_keywords() {
		return array( 'video', 'featured', 'media', 'rsfv' );
	}

	/**
	 * Render the element output on the frontend.
	 *
	 * @void
	 */
	public function render() {
		$post_id = get_the_ID();
		if ( ! $post_id ) {
			if ( is_admin() ) {
				echo '<p>' . esc_html__( 'Make sure Really Simple Featured Video element is inside a Query Loop. In case you have done that, you can safely ignore this.', 'rsfv' ) . '</p>';
			}
			return;
		}

		// Set element attributes.
		$root_classes[] = 'bricks-really-simple-featured-video';

		// Add 'class' attribute to element root tag.
		$this->set_attribute( '_root', 'class', $root_classes );

		$video_markup = FrontEnd::get_featured_video_markup( $post_id );

		if ( $video_markup ) {
			echo '<div ' . $this->render_attributes( '_root' ) . '>';
			echo $video_markup;
			echo '</div>';
		} else {
			$image_url = get_the_post_thumbnail_url( $post_id );

			if ( $image_url ) {
				echo '<figure ' . $this->render_attributes( '_root' ) . '>';
				echo '<img src="' . esc_url( $image_url ) . '" alt="' . esc_attr( get_the_title( $post_id ) ) . '" class="bricks-really-simple-featured-video">';
				echo '</figure>';
			}
		}
	}
}
