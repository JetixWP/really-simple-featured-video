<?php
/**
 * Video Studio admin page: pick a post or product, then make its video.
 *
 * @package RSFV
 */

namespace RSFV\Studio;

defined( 'ABSPATH' ) || exit;

use RSFV\Tools\Admin_Tools;

/**
 * Class Admin_Page
 */
class Admin_Page {
	/**
	 * Page slug.
	 *
	 * @var string
	 */
	const SLUG = 'rsfv-studio';

	/**
	 * Admin page hook suffix.
	 *
	 * @var string
	 */
	protected $hook_suffix = '';

	/**
	 * Constructor.
	 */
	public function __construct() {
		// After Video Tools, which registers at the default priority.
		add_action( 'rsfv_register_admin_menus', array( $this, 'register_menu' ), 11 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'admin_head', array( $this, 'menu_badge_css' ) );
	}

	/**
	 * Register the submenu.
	 *
	 * @param string $primary_slug Parent menu slug.
	 *
	 * @return void
	 */
	public function register_menu( $primary_slug ) {
		/**
		 * Filter whether the Video Studio menu item shows a "New" badge.
		 *
		 * @since 0.91.0
		 *
		 * @param bool $show Show the badge.
		 */
		$badge = apply_filters( 'rsfv_studio_menu_badge', true )
			? ' <span class="rsfv-menu-badge">' . esc_html__( 'New', 'rsfv' ) . '</span>'
			: '';

		$this->hook_suffix = (string) add_submenu_page(
			$primary_slug,
			__( 'Video Studio: Automatically Generate Featured Videos', 'rsfv' ),
			__( '&nbsp;↳ Video Studio', 'rsfv' ) . $badge,
			'manage_options',
			self::SLUG,
			array( $this, 'render' ),
			RSFV_PLUGIN_DEFAULT_PRIORITY
		);
	}

	/**
	 * Badge style (the menu shows on every admin screen).
	 *
	 * @return void
	 */
	public function menu_badge_css() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		echo '<style>#adminmenu .rsfv-menu-badge{display:inline-block;margin-left:4px;padding:0 6px;border-radius:9px;background:#00a32a;color:#fff;font-size:9px;font-weight:600;line-height:17px;text-transform:uppercase;letter-spacing:.03em;vertical-align:1px}</style>';
	}

	/**
	 * Enqueue the app on this page only.
	 *
	 * @param string $hook_suffix Current admin page.
	 *
	 * @return void
	 */
	public function enqueue( $hook_suffix ) {
		if ( ! $this->hook_suffix || $hook_suffix !== $this->hook_suffix || ! Studio::enqueue_app() ) {
			return;
		}

		$data = array_merge(
			Studio::get_base_data(),
			array(
				'page'      => true,
				'postTypes' => Admin_Tools::get_enabled_post_types_options(),
				'mediaUrl'  => admin_url( 'upload.php?item=' ),
				'openPost'  => isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			)
		);

		wp_localize_script( 'rsfv-studio', 'rsfvStudio', $data );
	}

	/**
	 * Page markup.
	 *
	 * @return void
	 */
	public function render() {
		printf(
			'<div class="wrap rsfv-studio-page-wrap"><h1 class="wp-heading-inline">%1$s <span class="rsfv-studio-page-subtitle">%2$s</span></h1><hr class="wp-header-end"><div id="rsfv-studio-page"></div></div>',
			esc_html__( 'Video Studio:', 'rsfv' ),
			esc_html__( 'Automatically Generate Featured Videos', 'rsfv' )
		);
	}
}
