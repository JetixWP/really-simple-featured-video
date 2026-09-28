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
		echo '<style>#adminmenu .rsfv-menu-badge{display:inline-block;margin-left:2px;padding:0 5px;border-radius:8px;background:#008710;color:#fff;font-size:8px;font-weight:700;line-height:15px;text-transform:uppercase;vertical-align:1px;white-space:nowrap}</style>';
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

		// Sidebar cards styled like Settings.
		wp_enqueue_style( 'rsfv_settings', RSFV_PLUGIN_URL . 'assets/css/admin-settings.css', array(), filemtime( RSFV_PLUGIN_DIR . 'assets/css/admin-settings.css' ) );

		// Same JetixWP header and tabs as Video Tools.
		$tools_css = RSFV_PLUGIN_DIR . 'assets/js/tools/style-index.css';
		if ( file_exists( $tools_css ) ) {
			wp_enqueue_style( 'rsfv-tools', RSFV_PLUGIN_URL . 'assets/js/tools/style-index.css', array( 'wp-components' ), filemtime( $tools_css ) );
			wp_add_inline_style( 'rsfv-tools', 'body.jetixwp_page_rsfv-studio #wpcontent{padding-left:0}' );
		}
	}

	/**
	 * Page markup.
	 *
	 * @return void
	 */
	public function render() {
		?>
		<div class="wrap rsfv rsfv-tools rsfv-studio-page-wrap">
			<div class="plugin-header">
				<div class="plugin-header-wrap">
					<div class="plugin-info">
						<h1 class="menu-title"><?php esc_html_e( 'Really Simple Featured Video → Video Studio', 'rsfv' ); ?></h1>
						<span class="rsfv-studio-page-subtitle"><?php esc_html_e( 'Automatically Generate Featured Videos', 'rsfv' ); ?></span>
						<?php do_action( 'rsfv_extend_plugin_header' ); ?>
					</div>

					<div class="brand-info">
						<a href="https://jetixwp.com?utm_campaign=settings-header&utm_source=rsfv-plugin" target="_blank"><img class="brand-logo" src="<?php echo esc_url( RSFV_PLUGIN_URL . 'assets/images/jwp-icon-dark.svg' ); ?>" alt="RSFV"></a>
					</div>
				</div>
			</div>
			<div class="rsfv-studio-layout">
				<div id="rsfv-studio-page" class="rsfv-tools-app"></div>
				<?php
				ob_start();
				$rsfv_promo_studio = true;
				include RSFV_PLUGIN_DIR . 'includes/Settings/Views/html-admin-sidebar-promo.php';
				$rsfv_sidebar = trim( ob_get_clean() );
				?>
				<?php if ( '' !== $rsfv_sidebar ) : ?>
					<div class="sidebar rsfv-studio-sidebar">
						<?php echo $rsfv_sidebar; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in the view. ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}
