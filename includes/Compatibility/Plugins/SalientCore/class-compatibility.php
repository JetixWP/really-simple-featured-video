<?php
/**
 * SalientCore compatibility handler.
 *
 * @package RSFV
 */

namespace RSFV\Compatibility\Plugins\SalientCore;

defined( 'ABSPATH' ) || exit;

use RSFV\FrontEnd;
use RSFV\Compatibility\Plugins\Base_Compatibility;

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

		$this->id = 'salient-core';

		$this->setup();
	}

	/**
	 * Sets up hooks and filters.
	 *
	 * @return void
	 */
	public function setup() {
		add_filter( 'nectar_post_grid_item_image', array( $this, 'post_grid_video_markup' ) );
		add_action( 'nectar_blog_post_grid_item_start', array( $this, 'post_grid_item_start' ), 10, 2 );
	}

	/**
	 * Post Loop builder element video markup.
	 *
	 * @param string $image_markup Featured image markup.
	 * @return string
	 */
	public function post_grid_video_markup( $image_markup ) {
		global $post;

		if ( 'object' !== gettype( $post ) ) {
			return $image_markup;
		}

		$post_id = $post->ID;

		return FrontEnd::get_featured_video_markup( $post_id, $image_markup );
	}

	/**
	 * Add custom hack for removing wrapped anchor link.
	 *
	 * @param array  $atts Element attributes.
	 * @param object $query Custom query in display.
	 * @return void
	 */
	public function post_grid_item_start( $atts, $query ) {
		global $post;

		if ( 'object' !== gettype( $post ) ) {
			return;
		}

		$post_id            = $post->ID;
		$has_featured_video = FrontEnd::has_featured_video( $post_id );

		if ( $has_featured_video ) {
			echo '<span class="rsfv-video-next" style="display: none;"><style>.rsfv-video-next + .nectar-post-grid-item .bg-wrap-link { display: none !important; } #ajax-content-wrap .nectar-post-grid[data-columns="1"] > .rsfv-video-next:nth-child(1) + .nectar-post-grid-item { margin-top: 0; } </style></span>';
		}
	}
}
