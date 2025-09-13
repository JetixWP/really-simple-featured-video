<?php
/**
 * Rollbacker.
 *
 * @package RSFV
 */

namespace RSFV\Featuresets\Rollback;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Rollback.
 *
 * Rollback handler class is responsible for rolling back to
 * previous version.
 *
 * @since 0.50.0
 */
class Rollbacker {

	/**
	 * Package URL.
	 *
	 * Holds the package URL.
	 *
	 * @access protected
	 *
	 * @var string Package URL.
	 */
	protected $package_url;

	/**
	 * Version.
	 *
	 * Holds the version.
	 *
	 * @access protected
	 *
	 * @var string Package URL.
	 */
	protected $version;

	/**
	 * Plugin name.
	 *
	 * Holds the plugin name.
	 *
	 * @access protected
	 *
	 * @var string Plugin name.
	 */
	protected $plugin_name;

	/**
	 * Plugin slug.
	 *
	 * Holds the plugin slug.
	 *
	 * @access protected
	 *
	 * @var string Plugin slug.
	 */
	protected $plugin_slug;

	/**
	 * Rollback constructor.
	 *
	 * Initializing rollback.
	 *
	 * @access public
	 *
	 * @param array $args Optional. Rollback arguments. Default is an empty array.
	 */
	public function __construct( $args = array() ) {
		foreach ( $args as $key => $value ) {
			$this->{$key} = $value;
		}
	}

	/**
	 * Print inline style.
	 *
	 * Add an inline CSS to the rollback page.
	 *
	 * @access private
	 */
	private function print_inline_style() {
		?>
		<style>
			.wrap {
				overflow: hidden;
				max-width: 850px;
				margin: auto;
				font-family: Courier, monospace;
			}

			h1 {
				background: #252422;
				text-align: center;
				color: #fff !important;
				padding: 70px !important;
				text-transform: uppercase;
				letter-spacing: 1px;
			}

			h1 img {
				max-width: 300px;
				display: block;
				margin: auto auto 50px;
			}

			.wrap h1 {
				position: relative;
				padding-top: 140px !important;
			}

			.wrap h1:before {
				content: '';
				position: absolute;
				width: 300px;
				height: 100px;
				color: #fff;
				top: 35px;
				background-image: url("data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDIwIiBoZWlnaHQ9IjEwNCIgdmlld0JveD0iMCAwIDQyMCAxMDQiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxnIGNsaXAtcGF0aD0idXJsKCNjbGlwMF81NThfNDcpIj4KPHBhdGggZD0iTTExMy40IDU3LjY3VjU0LjM4TDEyMy45IDUyLjU2VjU3LjExQzEyMy45IDU5LjIxIDEyNC40NiA2MC44MiAxMjUuNTggNjEuOTRDMTI2Ljc0NyA2My4wMTMzIDEyOC4xOTMgNjMuNTUgMTI5LjkyIDYzLjU1QzEzMS42OTMgNjMuNTUgMTMzLjA5MyA2Mi45NjY3IDEzNC4xMiA2MS44QzEzNS4xOTMgNjAuNTg2NyAxMzUuNzMgNTkuMDQ2NyAxMzUuNzMgNTcuMThWMjMuMzdIMTQ2Ljc5VjU3LjQ2QzE0Ni43OSA2Mi4wOCAxNDUuMjAzIDY2IDE0Mi4wMyA2OS4yMkMxMzguODU3IDcyLjQ0IDEzNC44NDMgNzQuMDUgMTI5Ljk5IDc0LjA1QzEyNS4wNDMgNzQuMDUgMTIxLjAzIDcyLjU1NjcgMTE3Ljk1IDY5LjU3QzExNC45MTcgNjYuNTM2NyAxMTMuNCA2Mi41NyAxMTMuNCA1Ny42N1pNMTg1Ljg3NSA3M0gxNTQuMzc1VjIzLjM3SDE4NS44MDVWMzMuOEgxNjUuNDM1VjQzLjI1SDE4My45MTVWNTIuOThIMTY1LjQzNVY2Mi41SDE4NS44NzVWNzNaTTIzMS4yMDYgMzMuOTRIMjE1Ljg3NlY3M0gyMDQuODE2VjMzLjk0SDE4OS41NTZWMjMuMzdIMjMxLjIwNlYzMy45NFpNMjQ2LjMxMyA3M0gyMzUuMTEzVjIzLjM3SDI0Ni4zMTNWNzNaTTI5Ni4wNDYgMjMuMzdMMjgwLjA4NiA0OC4yOUwyOTYuMTg2IDczSDI4Mi43NDZMMjcyLjk0NiA1Ni45TDI2My4yMTYgNzNIMjUwLjE5NkwyNjYuMjI2IDQ4LjE1TDI1MC4xOTYgMjMuMzdIMjYzLjQ5NkwyNzMuMjk2IDM5LjQ3TDI4My4wOTYgMjMuMzdIMjk2LjA0NloiIGZpbGw9IiNmZmYiPjwvcGF0aD4KPHJlY3QgeD0iMzEyIiB5PSIyMSIgd2lkdGg9Ijk1IiBoZWlnaHQ9IjUzIiByeD0iOSIgc3Ryb2tlPSIjZmZmIiBzdHJva2Utd2lkdGg9IjIiPjwvcmVjdD4KPHBhdGggZD0iTTM1Ni43NTUgNTcuNzE1TDM2NC40IDI5LjAwNUgzNzEuMjJMMzYwLjI3NSA2OEgzNTMuNDU1TDM0My45OTUgMzguNTJMMzM0LjUzNSA2OEgzMjcuNzdMMzE2LjcxNSAyOS4wMDVIMzIzLjY0NUwzMzEuNDU1IDU3LjQ5NUwzNDAuNTg1IDI5LjAwNUgzNDcuNDZMMzU2Ljc1NSA1Ny43MTVaTTM4MC45ODggNDYuOTM1SDM4Ny44NjNDMzkyLjA0MyA0Ni45MzUgMzk0LjU3MyA0NC42MjUgMzk0LjU3MyA0MC45NEMzOTQuNTczIDM3LjIgMzkyLjA0MyAzNC44MzUgMzg3Ljg2MyAzNC44MzVIMzgwLjk4OFY0Ni45MzVaTTM4OC44NTMgNTIuNzY1SDM4MC45ODhWNjhIMzc0LjM4OFYyOS4wMDVIMzg4Ljg1M0MzOTYuMzMzIDI5LjAwNSA0MDEuMjgzIDM0LjA2NSA0MDEuMjgzIDQwLjg4NUM0MDEuMjgzIDQ3Ljc2IDM5Ni4zMzMgNTIuNzY1IDM4OC44NTMgNTIuNzY1WiIgZmlsbD0iI2ZmZiI+PC9wYXRoPgo8cGF0aCBkPSJNOTMuNzQwNyAzMC42NTA5TDMyLjI1OTUgODguMzIxM0wyLjQ3NjY5IDU2LjU3MDVMOTMuNzQwNyAzMC42NTA5WiIgZmlsbD0iIzU4ODE1NyIgc3Ryb2tlPSIjZmZmIiBzdHJva2Utd2lkdGg9IjIuNSIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiBzdHJva2UtbGluZWpvaW49InJvdW5kIj48L3BhdGg+CjxwYXRoIGQ9Ik0xMS4wNTc4IDcuNTU4NTVMMzMuMzYyNCA4OC44NTAxTDc1LjM0MzkgNzcuMzMxNEwxMS4wNTc4IDcuNTU4NTVaIiBmaWxsPSIjNTg4MTU3IiBzdHJva2U9IiNmZmYiIHN0cm9rZS13aWR0aD0iMi41IiBzdHJva2UtbGluZWNhcD0icm91bmQiIHN0cm9rZS1saW5lam9pbj0icm91bmQiPjwvcGF0aD4KPC9nPgo8ZGVmcz4KPGNsaXBQYXRoIGlkPSJjbGlwMF81NThfNDciPgo8cmVjdCB3aWR0aD0iNDIwIiBoZWlnaHQ9IjEwMy45MzkiIGZpbGw9IndoaXRlIj48L3JlY3Q+CjwvY2xpcFBhdGg+CjwvZGVmcz4KPC9zdmc+");
				background-repeat: no-repeat;
				transform: translate(50%);
			}
		</style>
		<?php
	}

	/**
	 * Apply package.
	 *
	 * Change the plugin data when WordPress checks for updates. This method
	 * modifies package data to update the plugin from a specific URL containing
	 * the version package.
	 *
	 * @access protected
	 */
	protected function apply_package() {
		$update_plugins = get_site_transient( 'update_plugins' );
		if ( ! is_object( $update_plugins ) ) {
			$update_plugins = new \stdClass();
		}

		$plugin_info = new \stdClass();
		$plugin_info->new_version = $this->version;
		$plugin_info->slug = $this->plugin_slug;
		$plugin_info->package = $this->package_url;
		$plugin_info->url = 'https://jetixwp.com/';

		$update_plugins->response[ $this->plugin_name ] = $plugin_info;

		set_site_transient( 'update_plugins', $update_plugins );
	}

	/**
	 * Upgrade.
	 *
	 * Run WordPress upgrade to rollback plugin to previous version.
	 *
	 * @access protected
	 */
	protected function upgrade() {
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

		$upgrader_args = array(
			'url' => 'update.php?action=upgrade-plugin&plugin=' . rawurlencode( $this->plugin_name ),
			'plugin' => $this->plugin_name,
			'nonce' => 'upgrade-plugin_' . $this->plugin_name,
			'title' => esc_html__( 'Rollback to Previous Version', 'rsfv' ),
		);

		$this->print_inline_style();

		$upgrader = new \Plugin_Upgrader( new \Plugin_Upgrader_Skin( $upgrader_args ) );
		$upgrader->upgrade( $this->plugin_name );
	}

	/**
	 * Run.
	 *
	 * Rollback plugin to previous versions.
	 *
	 * @access public
	 */
	public function run() {
		$this->apply_package();
		$this->upgrade();
	}
}
