<?php
/**
 * Video Studio: make featured videos from templates in the browser.
 *
 * @package RSFV
 */

namespace RSFV\Studio;

defined( 'ABSPATH' ) || exit;

use function RSFV\Settings\get_post_types;

/**
 * Class Studio
 */
class Studio {
	/**
	 * Class instance.
	 *
	 * @var Studio
	 */
	protected static $instance;

	/**
	 * Get an instance of class.
	 *
	 * @return Studio
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
		new REST_API();
		new Admin_Page();

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'rsfv_metabox_after_source', array( $this, 'render_launcher' ) );
	}

	/**
	 * Current post on an edit screen, when featured videos are enabled for it.
	 *
	 * @return \WP_Post|null
	 */
	protected function current_post() {
		global $pagenow, $post;

		if ( ! in_array( $pagenow, array( 'post.php', 'post-new.php' ), true ) || ! $post instanceof \WP_Post ) {
			return null;
		}
		if ( ! in_array( $post->post_type, (array) get_post_types(), true ) ) {
			return null;
		}
		if ( ! current_user_can( 'upload_files' ) ) {
			return null;
		}

		return $post;
	}

	/**
	 * Data every Video Studio screen needs (editor and add-on screens).
	 *
	 * @return array
	 */
	public static function get_base_data() {
		return array(
			'restUrl'       => esc_url_raw( rest_url( REST_API::NAMESPACE . '/studio/' ) ),
			'nonce'         => wp_create_nonce( 'wp_rest' ),
			'runtimeUrl'    => self::asset_url( 'studio-runtime.js' ),
			'templates'     => array_values( Registry::get_templates() ),
			'presets'       => Registry::get_presets(),
			'defaultPreset' => 'landscape-1080',
			'fonts'         => Registry::get_fonts(),
			'extensions'    => Registry::get_runtime_extensions(),
			'maxUploadSize' => wp_max_upload_size(),
			'isPro'         => defined( 'RSFV_PRO_VERSION' ),
			// PRO older than 1.40.0 has no Video Studio extras yet.
			'proNeedsUpdate' => defined( 'RSFV_PRO_VERSION' ) && version_compare( RSFV_PRO_VERSION, '1.40.0', '<' ),
			'pluginsUrl'    => admin_url( 'plugins.php' ),
			'upgradeUrl'    => RSFV_PLUGIN_PRO_URL . '/#pricing',
		);
	}

	/**
	 * Data the editor needs.
	 *
	 * @param \WP_Post $post Post.
	 *
	 * @return array
	 */
	public static function get_editor_data( $post ) {
		$data = array_merge(
			self::get_base_data(),
			array(
				'postId'        => $post->ID,
				'postType'      => $post->post_type,
				'fields'        => Post_Fields::get( $post ),
				'composition'   => Composition::get( $post->ID ),
				'isCurrent'     => Composition::is_current( $post->ID ),
				'changed'       => Composition::changed_sources( $post->ID ),
				'supportsThumb' => post_type_supports( $post->post_type, 'thumbnail' ),
			)
		);

		/**
		 * Filter the data passed to the Video Studio editor.
		 *
		 * @since 1.0.0
		 *
		 * @param array    $data Data.
		 * @param \WP_Post $post Post.
		 */
		return apply_filters( 'rsfv_studio_editor_data', $data, $post );
	}

	/**
	 * URL of a built Video Studio file, versioned by its modified time.
	 *
	 * @param string $file File name in assets/js/studio.
	 *
	 * @return string
	 */
	public static function asset_url( $file ) {
		$path = RSFV_PLUGIN_DIR . 'assets/js/studio/' . $file;
		$ver  = file_exists( $path ) ? filemtime( $path ) : RSFV_VERSION;

		return add_query_arg( 'ver', $ver, RSFV_PLUGIN_URL . 'assets/js/studio/' . $file );
	}

	/**
	 * Register and enqueue the editor app.
	 *
	 * @return bool Whether the app was enqueued.
	 */
	public static function enqueue_app() {
		$asset_file = RSFV_PLUGIN_DIR . 'assets/js/studio/studio.asset.php';
		if ( ! file_exists( $asset_file ) ) {
			return false;
		}
		$asset = require $asset_file;

		wp_enqueue_media();

		wp_enqueue_script(
			'rsfv-studio',
			RSFV_PLUGIN_URL . 'assets/js/studio/studio.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);
		wp_set_script_translations( 'rsfv-studio', 'rsfv', RSFV_PLUGIN_DIR . 'languages' );

		if ( file_exists( RSFV_PLUGIN_DIR . 'assets/js/studio/style-studio.css' ) ) {
			wp_enqueue_style(
				'rsfv-studio',
				RSFV_PLUGIN_URL . 'assets/js/studio/style-studio.css',
				array( 'wp-components' ),
				$asset['version']
			);
		}

		/**
		 * Fires after the Video Studio app is enqueued, so add-ons can load
		 * their scripts after it.
		 *
		 * @since 1.0.0
		 */
		do_action( 'rsfv_studio_enqueued' );

		return true;
	}

	/**
	 * Pass the shared config to the app once per page (add-ons may call
	 * this too).
	 *
	 * @return void
	 */
	public static function localize_base() {
		$existing = wp_scripts()->get_data( 'rsfv-studio', 'data' );
		if ( is_string( $existing ) && false !== strpos( $existing, 'var rsfvStudio ' ) ) {
			return;
		}
		wp_localize_script( 'rsfv-studio', 'rsfvStudio', self::get_base_data() );
	}

	/**
	 * Enqueue on post edit screens.
	 *
	 * @return void
	 */
	public function enqueue() {
		$post = $this->current_post();
		if ( ! $post || ! self::enqueue_app() ) {
			return;
		}

		wp_localize_script( 'rsfv-studio', 'rsfvStudio', self::get_editor_data( $post ) );
	}

	/**
	 * Launcher inside the Featured Video box.
	 *
	 * @param \WP_Post $post Post.
	 *
	 * @return void
	 */
	public function render_launcher( $post ) {
		if ( ! $post instanceof \WP_Post || ! current_user_can( 'upload_files' ) ) {
			return;
		}
		printf(
			'<div class="rsfv-studio-launcher" id="rsfv-studio-launcher" data-post-id="%d"></div>',
			absint( $post->ID )
		);
	}
}
