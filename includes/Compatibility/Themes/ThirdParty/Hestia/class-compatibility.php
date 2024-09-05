<?php
/**
 * Hestia theme compatibility handler.
 *
 * @package RSFV
 */

namespace RSFV\Compatibility\Themes\ThirdParty\Hestia;

use RSFV\Compatibility\Themes\Base_Compatibility;
use RSFV\Compatibility\Plugins\WooCommerce\Compatibility as BaseWooCompatibility;
use RSFV\Options;
use RSFV\Plugin;

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

		$this->id = 'hestia';

		$this->woo_setup();

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Enqueue scripts.
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		// Register styles.
		wp_register_style( 'rsfv-hestia', $this->get_current_dir_url() . 'ThirdParty/Hestia/styles.css', array(), filemtime( $this->get_current_dir() . 'ThirdParty/Hestia/styles.css' ) );

		// Enqueue styles.
		wp_enqueue_style( 'rsfv-hestia' );

		// Add generated CSS.
		wp_add_inline_style( 'rsfv-hestia', Plugin::get_instance()->frontend_provider->generate_dynamic_css() );
	}

	/**
	 * Setup Woo compat.
	 *
	 * @return void
	 */
	public function woo_setup() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		$options                     = Options::get_instance();
		$product_archives_visibility = $options->get( 'product_archives_visibility' );

		if ( ( ! $options->has( 'product_archives_visibility' ) && ! $product_archives_visibility ) || $product_archives_visibility ) {
			$base_woo_compatibility = BaseWooCompatibility::get_instance();

			remove_action( 'woocommerce_before_shop_loop_item_title', array( $base_woo_compatibility, 'get_woo_archives_video' ) );
			add_action(
				'wp',
				function () {
					remove_action( 'woocommerce_before_shop_loop_item_title', 'hestia_woocommerce_template_loop_product_thumbnail' );
					add_action( 'woocommerce_before_shop_loop_item_title', array( $this, 'woo_template_loop_product_thumbnail' ) );
				}
			);
		}

		// Update support for single product.
		remove_filter( 'woocommerce_single_product_image_thumbnail_html', array( $base_woo_compatibility, 'woo_get_video' ), 10, 2 );
		add_filter( 'woocommerce_single_product_image_thumbnail_html', array( $this, 'woo_get_video' ), 10, 2 );
	}

	/**
	 * Update the layout of the thumbnail on single product listing
	 *
	 * @param int $post_id Product Id.
	 * @return void
	 */
	public function woo_template_loop_product_thumbnail( $post_id ) {
		$base_woo_compatibility = BaseWooCompatibility::get_instance();
		$thumbnail              = function_exists( 'woocommerce_get_product_thumbnail' ) ? woocommerce_get_product_thumbnail() : '';
		if ( empty( $thumbnail ) && function_exists( 'wc_placeholder_img' ) ) {
			$thumbnail = wc_placeholder_img();
		}
		if ( ! empty( $thumbnail ) ) {
			?>
			<div class="card-image">
				<a href="<?php echo esc_url( get_permalink() ); ?>" title="<?php the_title_attribute(); ?>">
					<?php
					$base_woo_compatibility->get_woo_archives_video( $post_id );
					do_action( 'hestia_shop_after_product_thumbnail' );
					?>
				</a>
				<?php do_action( 'hestia_shop_after_product_thumbnail_link' ); ?>
				<div class="ripple-container"></div>
			</div>
			<?php
		}
	}

	/**
	 * Filter method for getting WooCommerce video markup at products.
	 *
	 * @param string $html Thumbnail markup for products.
	 * @param int    $post_thumbnail_id Thumbnail ID.
	 * @return string
	 */
	public function woo_get_video( $html, $post_thumbnail_id ) {
		global $product;
		$base_woo_compatibility = BaseWooCompatibility::get_instance();

		$has_post_thumbnail = ! empty( get_the_post_thumbnail_url( $product->get_id() ) );

		if ( ! $has_post_thumbnail ) {
			return $base_woo_compatibility->woo_get_video( $html, $post_thumbnail_id, true );
		} else {
			return $base_woo_compatibility->woo_get_video( $html, $post_thumbnail_id );
		}
	}
}
