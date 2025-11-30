<?php
/**
 * Settings handler.
 *
 * @package RSFV
 */

namespace RSFV\Settings;

use RSFV\Options;

/**
 * Register Settings.
 */
class Register {
	/**
	 * Class instance
	 *
	 * @var $instance
	 */
	protected static $instance;

	/**
	 * Class constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );

		// Handle saving settings earlier than load-{page} hook to avoid race conditions in conditional menus.
		add_action( 'wp_loaded', array( $this, 'save_settings' ) );

		add_action( 'init', array( $this, 'create_options' ) );

		add_action( 'load-settings_page_rsfv-settings', array( $this, 'cleanup_plugin_settings_page' ) );
	}

	/**
	 * Get a class instance.
	 *
	 * @return Register
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Register plugin menu.
	 *
	 * @return void
	 */
	public function register_menu() {
		/**
		 * Default framework menu.
		 */
		global $admin_page_hooks;
		$primary_slug = 'jetixwp';
		$menu_icon    = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iOTUiIGhlaWdodD0iODYiIHZpZXdCb3g9IjAgMCA5NSA4NiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGcgY2xpcC1wYXRoPSJ1cmwoI2NsaXAwXzE2MjZfMTQpIj4KPHBhdGggZD0iTTY4Ljc4NjkgNTkuNzI1Nkw1OC4zMDM0IDc3LjM4MkgzNy44ODIxTDQ4LjM2NTYgNTkuNzI1Nkg2OC43ODY5Wk0zOS43MDU3IDc2LjM0MzRINTcuNzEzMUw2Ni45NjMyIDYwLjc2NDJINDguOTU1OUwzOS43MDU3IDc2LjM0MzRaIiBmaWxsPSIjMjUyNDIyIi8+CjxwYXRoIGQ9Ik0zOC43OTM5IDc2Ljg2MjdMNDguNjYwNyA2MC4yNDQ5SDY3Ljg3NUw1OC4wMDgyIDc2Ljg2MjdIMzguNzkzOVoiIGZpbGw9IndoaXRlIi8+CjxwYXRoIGQ9Ik03OC4wMjkgOS4xNjg1NkM3OC4wNTc4IDkuMTkzNTUgNzguMDc5IDkuMjIwNDQgNzguMTAwNSA5LjI0OTY1Qzc4LjExNDMgOS4yNjcwOSA3OC4xMzEyIDkuMjc4ODUgNzguMTQyNiA5LjI5ODUyTDg3Ljg3OTEgMjYuMTUyN0M4Ny44ODM0IDI2LjE2MTIgODcuODgzNSAyNi4xNzE2IDg3Ljg4ODMgMjYuMTgxNEM4Ny44OTkxIDI2LjIwMiA4Ny45MDU3IDI2LjIyMTYgODcuOTE0MSAyNi4yNDQzQzg3LjkyMjQgMjYuMjY3IDg3LjkyNzYgMjYuMjg5MyA4Ny45MzI2IDI2LjMxMzZDODcuOTM4MSAyNi4zMzcgODcuOTQwMyAyNi4zNTg3IDg3Ljk0MjEgMjYuMzgyM0M4Ny45NDI1IDI2LjM5MjEgODcuOTQ4MiAyNi40MDIgODcuOTQ4NiAyNi40MTE4Qzg3Ljk0ODkgMjYuNDIxNiA4Ny45NDMzIDI2LjQzMDggODcuOTQyMyAyNi40NDFDODcuOTQwOSAyNi40NjQgODcuOTM4MiAyNi40ODYyIDg3LjkzNCAyNi41MUM4Ny45MjkzIDI2LjUzNDggODcuOTIxOCAyNi41NTczIDg3LjkxNDMgMjYuNTgwNkM4Ny45MDggMjYuNjAxOSA4Ny44OTg4IDI2LjYyMTYgODcuODg5OCAyNi42NDE0Qzg3Ljg4NDYgMjYuNjUwNCA4Ny44ODU5IDI2LjY2MjMgODcuODgwMiAyNi42NzIyTDU4LjY4NyA3Ny4yMzYzTDU4LjY0MzYgNzcuMjg1MUM1OC42MjA1IDc3LjMxNDcgNTguNTk5MyA3Ny4zNDE5IDU4LjU3MjkgNzcuMzY0OUM1OC41NDc1IDc3LjM4NjEgNTguNTIwMyA3Ny40MDQxIDU4LjQ5MSA3Ny40MTk1QzU4LjQ2MjYgNzcuNDM1NSA1OC40MzM1IDc3LjQ1MTIgNTguNDAyMiA3Ny40NjE5QzU4LjM2OSA3Ny40NzM4IDU4LjMzNDEgNzcuNDc5NSA1OC4yOTgxIDc3LjQ4MzhDNTguMjc2NyA3Ny40ODU4IDU4LjI1ODggNzcuNDk1OSA1OC4yMzU2IDc3LjQ5NjlMMTkuMzEyNCA3Ny40OTcyTDE5LjMxMDIgNzcuNDk3QzE5LjMwMTkgNzcuNDk3IDE5LjI5MzggNzcuNDkxMSAxOS4yODQ3IDc3LjQ5MDVDMTkuMjA0OSA3Ny40ODc2IDE5LjEyNTcgNzcuNDcxMyAxOS4wNTIxIDc3LjQyODhDMTguOTc4MyA3Ny4zODYzIDE4LjkyMzYgNzcuMzI0IDE4Ljg4MTUgNzcuMjU3N0MxOC44NzcyIDc3LjI1MDggMTguODY5IDc3LjI0NyAxOC44NjQ4IDc3LjIzOTlMMTguODYyNyA3Ny4yMzc1TDkuMTMxOTUgNjAuMzc3M0M5LjEyMDY5IDYwLjM1NzYgOS4xMjA0OCA2MC4zMzcxIDkuMTExOTcgNjAuMzE2NkM5LjA5Nzc0IDYwLjI4MzMgOS4wODMzNCA2MC4yNTE1IDkuMDc3NTcgNjAuMjE1OUM5LjA2OTc0IDYwLjE4MzkgOS4wNzA1NiA2MC4xNTE3IDkuMDcwNzEgNjAuMTE4M0w5LjA3NjU1IDYwLjAyMDlDOS4wODQ2OSA1OS45ODYxIDkuMDk3MjUgNTkuOTUzMSA5LjEwNzMzIDU5LjkxNjlMOS4xMjgyMyA1OS44NTY0TDE4Ljg2MDcgNDIuOTk5M0MxOC44NjU5IDQyLjk5MDQgMTguODc2IDQyLjk4NDkgMTguODgxMSA0Mi45NzZDMTguODkzNyA0Mi45NTgzIDE4LjkwNjggNDIuOTM5NiAxOC45MjIgNDIuOTIzNUMxOC45Mzg1IDQyLjkwNTQgMTguOTU1MSA0Mi44ODgxIDE4Ljk3MzcgNDIuODcyNUMxOC45OTEyIDQyLjg1NjUgMTkuMDA4NCA0Mi44NDI1IDE5LjAyODUgNDIuODMwM0MxOS4wMzYyIDQyLjgyNTIgMTkuMDQyOCA0Mi44MTU0IDE5LjA1MTUgNDIuODEwOEMxOS4wNiA0Mi44MDY0IDE5LjA3MDMgNDIuODA2NCAxOS4wNzg4IDQyLjgwMkMxOS4xMDA0IDQyLjc5MTcgMTkuMTE5OSA0Mi43ODI5IDE5LjE0MzYgNDIuNzc1QzE5LjE2NzcgNDIuNzY2MyAxOS4xODkgNDIuNzYwNiAxOS4yMTQzIDQyLjc1NkMxOS4yMzcyIDQyLjc1MTQgMTkuMjU2NSA0Mi43NDkxIDE5LjI3ODggNDIuNzQ3N0wxOS4zMTA2IDQyLjc0MTRMMzguNDczMyA0Mi43NDU2TDU3Ljc4NSA5LjI5NjY5QzU3Ljc5MDcgOS4yODY4IDU3LjgwMDggOS4yODE1IDU3LjgwNiA5LjI3MjUxQzU3LjgxODMgOS4yNTM0IDU3LjgzMTEgOS4yMzcwMyA1Ny44NDY5IDkuMjE5OThDNTcuODYzMyA5LjIwMTkgNTcuODc5MSA5LjE4NDEyIDU3Ljg5NzcgOS4xNjg1QzU3LjkxNTMgOS4xNTI0MiA1Ny45MzMyIDkuMTM5MDggNTcuOTUzNCA5LjEyNjg1QzU3Ljk2MTEgOS4xMjE3NSA1Ny45NjY0IDkuMTEyMzIgNTcuOTc1NSA5LjEwNjgyQzU3Ljk4NDEgOS4xMDIyMiA1Ny45OTU1IDkuMTAyMjQgNTguMDA0MiA5LjA5NzY0QzU4LjAyNTUgOS4wODc0IDU4LjA0NTQgOS4wNzg0MyA1OC4wNjg1IDkuMDcxNTFDNTguMDkyNiA5LjA2MjggNTguMTE2MSA5LjA1NzIxIDU4LjE0MTQgOS4wNTI3MUM1OC4xNjMgOS4wNDg0MyA1OC4xODEzIDkuMDQ1NjMgNTguMjAxNCA5LjA0NDA0TDU4LjIzNTQgOS4wMzc5TDc3LjY5MTEgOS4wMzc4NUM3Ny43MTI0IDkuMDM4MjEgNzcuNzMxIDkuMDQ3IDc3Ljc1MjIgOS4wNDk3Qzc3Ljc5IDkuMDU0NzYgNzcuODI1MyA5LjA1OTMxIDc3Ljg2MDQgOS4wNzExOUM3Ny44OTEyIDkuMDgxODkgNzcuOTE4MiA5LjA5NzU2IDc3Ljk0NiA5LjExMzU5Qzc3Ljk3NjEgOS4xMjk3NyA3OC4wMDM1IDkuMTQ2NjYgNzguMDI5IDkuMTY4NTZaTTEwLjE3ODggNjAuMTEzOUwxOS4zMTI3IDc1Ljk0MDhMMjguNDQ1MiA2MC4xMjI5TDE5LjMxNDIgNDQuMzAxMUwxOS4zMTEzIDQ0LjI5NkwxMC4xNzg4IDYwLjExMzlaTTI5LjM0NDEgNjAuNjQzMUwyMC4yMTI3IDc2LjQ1OTNMMzguNDcyNyA3Ni40NTI5TDQ3LjYwNDcgNjAuNjM1OEwyOS4zNDQxIDYwLjY0MzFaTTQ4LjgwMzUgNjAuNjM0NkwzOS42NzEgNzYuNDUyNkw1Ny45MzYyIDc2LjQ1NzRMNjcuMDY4MiA2MC42NDAzTDQ4LjgwMzUgNjAuNjM0NlpNMjAuMjEzNyA0My43ODE3TDI5LjM0NDggNTkuNjAzNEw0Ny42MDUzIDU5LjU5NjJMMzguNDc2MiA0My43ODcyTDIwLjIxMzcgNDMuNzgxN1pNNTguNTM0MyA0My43ODI1TDQ5LjQwMzggNTkuNTk2OUw2Ny42NjkgNTkuNjAxN0w3Ni43OTk1IDQzLjc4NzNMNTguNTM0MyA0My43ODI1Wk02OC4yNjE2IDI2LjkzNDJMNTkuMTMzMiA0Mi43NDUxTDc3LjM5ODQgNDIuNzQ5OUw4Ni41MzA5IDI2LjkzMkw2OC4yNjE2IDI2LjkzNDJaTTU5LjEzNjkgMTAuMDc3Mkw2OC4yNjA4IDI1Ljg5NDlMODYuNTMwMSAyNS44OTI3TDc3LjM5MzMgMTAuMDc2OUw1OS4xMzY5IDEwLjA3NzJaTTM5LjM3MzggNDMuMjYzMUwzOS4zNzU4IDQzLjI2NzhMNDguNTA0OCA1OS4wNzY3TDY3LjM2MjEgMjYuNDE0OUw1OC4yMzY0IDEwLjU5NjFMNTguMjM1NyAxMC41OTM0TDM5LjM3MzggNDMuMjYzMVoiIGZpbGw9IiMyNTI0MjIiLz4KPHBhdGggZD0iTTkuMDc2NDEgNjAuMDIwNkM5LjA3MDcyIDYwLjA1MzMgOS4wNjkyOSA2MC4wODQ5IDkuMDcwNTYgNjAuMTE4QzkuMDcwNDEgNjAuMTUxNSA5LjA2OTg4IDYwLjE4MzYgOS4wNzc3MyA2MC4yMTU3QzkuMDgzNDkgNjAuMjUxNCA5LjA5NzM5IDYwLjI4MzQgOS4xMTE2NyA2MC4zMTY4QzkuMTIwMjEgNjAuMzM3MyA5LjEyMDM4IDYwLjM1NzggOS4xMzE3NiA2MC4zNzc2TDE4Ljg2MjEgNzcuMjM3NUwxOC44NjQzIDc3LjI0QzE4Ljg2ODUgNzcuMjQ3MiAxOC44NzcxIDc3LjI1MSAxOC44ODEzIDc3LjI1ODJDMTguOTIzNCA3Ny4zMjQ1IDE4Ljk3NzkgNzcuMzg1OSAxOS4wNTE2IDc3LjQyODVDMTkuMTI1NCA3Ny40NzExIDE5LjIwNDQgNzcuNDg3OSAxOS4yODQzIDc3LjQ5MDlDMTkuMjkzNiA3Ny40OTE0IDE5LjMwMTEgNzcuNDk3IDE5LjMwOTUgNzcuNDk3TDE5LjMxMTggNzcuNDk3MkwzOC43NzE4IDc3LjQ5MDNMNTguMjM1MyA3Ny40OTYyQzU4LjI1ODYgNzcuNDk1MyA1OC4yNzY0IDc3LjQ4NTIgNTguMjk3OSA3Ny40ODMyQzU4LjMzNCA3Ny40Nzg4IDU4LjM2OTIgNzcuNDc0IDU4LjQwMjUgNzcuNDYyQzU4LjQzMzggNzcuNDUxMyA1OC40NjIzIDc3LjQzNTQgNTguNDkwNyA3Ny40MTk0QzU4LjUyIDc3LjQwMzkgNTguNTQ3MSA3Ny4zODYgNTguNTcyNiA3Ny4zNjQ3QzU4LjU5OTEgNzcuMzQxNyA1OC42MjA3IDc3LjMxNDYgNTguNjQzOSA3Ny4yODQ4QzU4LjY1NjYgNzcuMjY2OSA1OC42NzUgNzcuMjU1OSA1OC42ODY0IDc3LjIzNjJMNjguNDE4MiA2MC4zODAyTDc4LjE0NzQgNDMuNTI4N0w4Ny44Nzk3IDI2LjY3MThDODcuODg1NCAyNi42NjE5IDg3Ljg4NDcgMjYuNjUwNyA4Ny44ODk5IDI2LjY0MTdDODcuODk5IDI2LjYyMTggODcuOTA4MSAyNi42MDE5IDg3LjkxNDUgMjYuNTgwNEM4Ny45MjIgMjYuNTU3MSA4Ny45Mjg5IDI2LjUzNDcgODcuOTMzNyAyNi41MDk5Qzg3LjkzNzkgMjYuNDg1OSA4Ny45NDExIDI2LjQ2MzggODcuOTQyNSAyNi40NDA2Qzg3Ljk0MzUgMjYuNDMwNCA4Ny45NDg3IDI2LjQyMTQgODcuOTQ4NCAyNi40MTE2Qzg3Ljk0OCAyNi40MDE4IDg3Ljk0MjYgMjYuMzkyNyA4Ny45NDIyIDI2LjM4MjlDODcuOTQwNCAyNi4zNTkgODcuOTM4NCAyNi4zMzc1IDg3LjkzMjkgMjYuMzEzOUM4Ny45Mjc4IDI2LjI4OTQgODcuOTIyNiAyNi4yNjcyIDg3LjkxNDIgMjYuMjQ0NEM4Ny45MDU4IDI2LjIyMTYgODcuODk5MSAyNi4yMDIxIDg3Ljg4ODIgMjYuMTgxNEM4Ny44ODMyIDI2LjE3MTMgODcuODgzNCAyNi4xNjA3IDg3Ljg3ODggMjYuMTUyTDc4LjE0MjUgOS4yOTgyM0M3OC4xMzEyIDkuMjc4NDcgNzguMTEzNyA5LjI2NzE4IDc4LjA5OTggOS4yNDk2Qzc4LjA3ODMgOS4yMjAzOSA3OC4wNTc2IDkuMTk0MDIgNzguMDI4OCA5LjE2OTAyQzc4LjAwMzIgOS4xNDcwMSA3Ny45NzU4IDkuMTMwMDEgNzcuOTQ1NiA5LjExMzc4Qzc3LjkxNzcgOS4wOTc2OCA3Ny44OTA3IDkuMDgyMSA3Ny44NTk3IDkuMDcxMzlDNzcuODI0NiA5LjA1OTUxIDc3Ljc5IDkuMDU1MTEgNzcuNzUyMSA5LjA1MDA1Qzc3LjczMDkgOS4wNDczOCA3Ny43MTI0IDkuMDM3ODkgNzcuNjkxIDkuMDM3NTRMNTguMjM1MiA5LjAzNzIyQzU4LjIyMyA5LjAzNzQyIDU4LjIxMjUgOS4wNDMzIDU4LjIwMTIgOS4wNDQwMkM1OC4xODExIDkuMDQ1NjEgNTguMTYyOCA5LjA0ODIzIDU4LjE0MTIgOS4wNTI1MkM1OC4xMTU4IDkuMDU3MDQgNTguMDkyMiA5LjA2MjYxIDU4LjA2NzkgOS4wNzEzOUM1OC4wNDQ3IDkuMDc4MzcgNTguMDI1MSA5LjA4NzQzIDU4LjAwMzUgOS4wOTc3N0M1Ny45OTQ5IDkuMTAyMzcgNTcuOTg0MiA5LjEwMjE5IDU3Ljk3NTUgOS4xMDY3OUM1Ny45NjYzIDkuMTEyMjkgNTcuOTYxMiA5LjEyMTI4IDU3Ljk1MzQgOS4xMjY0QzU3LjkzMzEgOS4xMzg2OCA1Ny45MTU1IDkuMTUyNTEgNTcuODk3OCA5LjE2ODY2QzU3Ljg3OTIgOS4xODQzIDU3Ljg2MzMgOS4yMDE0OSA1Ny44NDY4IDkuMjE5NThDNTcuODMwOSA5LjIzNjc3IDU3LjgxNzggOS4yNTMyIDU3LjgwNTUgOS4yNzI0N0M1Ny44MDAzIDkuMjgxNDcgNTcuNzkwMiA5LjI4NjQ1IDU3Ljc4NDUgOS4yOTYzNEw0OC4wNTIyIDI2LjE1MzJMMzguNDcyNiA0Mi43NDU3TDE5LjMxMDYgNDIuNzQxMUMxOS4yOTg1IDQyLjc0MTMgMTkuMjg4OCA0Mi43NDc3IDE5LjI3OSA0Mi43NDgxQzE5LjI1NjYgNDIuNzQ5NSAxOS4yMzc0IDQyLjc1MTYgMTkuMjE0MyA0Mi43NTYzQzE5LjE4ODkgNDIuNzYwOCAxOS4xNjc2IDQyLjc2NjUgMTkuMTQzMyA0Mi43NzUzQzE5LjExOTYgNDIuNzgzMiAxOS4xMDA1IDQyLjc5MTMgMTkuMDc4OSA0Mi44MDE3QzE5LjA3MDMgNDIuODA2MyAxOS4wNTk2IDQyLjgwNjEgMTkuMDUwOSA0Mi44MTA3QzE5LjA0MjMgNDIuODE1MyAxOS4wMzY2IDQyLjgyNTIgMTkuMDI4OCA0Mi44MzAzQzE5LjAwODUgNDIuODQyNiAxOC45OTEgNDIuODU2NCAxOC45NzMyIDQyLjg3MjZDMTguOTU0NiA0Mi44ODgyIDE4LjkzODcgNDIuOTA1NCAxOC45MjIzIDQyLjkyMzVDMTguOTA2OSA0Mi45Mzk4IDE4Ljg5MzYgNDIuOTU4NSAxOC44ODA5IDQyLjk3NjRDMTguODc1NyA0Mi45ODU0IDE4Ljg2NTcgNDIuOTkwMyAxOC44NjA1IDQyLjk5OTNMOS4xMjgxNSA1OS44NTYyQzkuMTE3MjUgNTkuODc1MSA5LjExNTg2IDU5Ljg5ODMgOS4xMDcyNyA1OS45MTczQzkuMDk3MTkgNTkuOTUzNSA5LjA4NDU1IDU5Ljk4NTcgOS4wNzY0MSA2MC4wMjA2Wk03Ny4zOTMyIDEwLjA3NjlMODYuNTI5NiAyNS44OTIzTDY4LjI2MDYgMjUuODk0OUw1OS4xMzY0IDEwLjA3NjlMNzcuMzkzMiAxMC4wNzY5Wk0yOS4zNDM3IDYwLjY0MjlMNDcuNjA0NiA2MC42MzU3TDM4LjQ3MjUgNzYuNDUyOEwyMC4yMTIyIDc2LjQ1OTFMMjkuMzQzNyA2MC42NDI5Wk02Ny42NjgzIDU5LjYwMThMNDkuNDAzNSA1OS41OTcxTDU4LjUzMzkgNDMuNzgyOEw3Ni43OTg3IDQzLjc4NzRMNjcuNjY4MyA1OS42MDE4Wk03Ny4zOTggNDIuNzQ5NEw1OS4xMzMyIDQyLjc0NDhMNjguMjYxNiAyNi45MzRMODYuNTMwNSAyNi45MzE0TDc3LjM5OCA0Mi43NDk0Wk00OC44MDMzIDYwLjYzNDZMNjcuMDY3NiA2MC42NDAyTDU3LjkzNTYgNzYuNDU3M0wzOS42NzA4IDc2LjQ1MjZMNDguODAzMyA2MC42MzQ2Wk0zOC40NzU2IDQzLjc4NzJMNDcuNjA1MSA1OS41OTYyTDI5LjM0NDIgNTkuNjAzNEwyMC4yMTM3IDQzLjc4MThMMzguNDc1NiA0My43ODcyWiIgZmlsbD0id2hpdGUiLz4KPC9nPgo8ZGVmcz4KPGNsaXBQYXRoIGlkPSJjbGlwMF8xNjI2XzE0Ij4KPHJlY3Qgd2lkdGg9Ijk1IiBoZWlnaHQ9Ijg2IiBmaWxsPSJ3aGl0ZSIvPgo8L2NsaXBQYXRoPgo8L2RlZnM+Cjwvc3ZnPgo=';

		if ( ! isset( $admin_page_hooks['jetixwp'] ) ) {
			add_menu_page(
				_x( 'JetixWP Plugins', 'Page title', 'rsfv' ),
				_x( 'JetixWP', 'Menu title', 'rsfv' ),
				'manage_options',
				$primary_slug,
				'__return_null',
				$menu_icon,
				30
			);

			add_action( 'admin_enqueue_scripts', array( $this, 'hide_freemius_submenus' ) );
		}

		add_submenu_page(
			$primary_slug,
			__( 'Really Simple Featured Video Settings', 'rsfv' ),
			__( 'Featured Video', 'rsfv' ),
			'manage_options',
			'rsfv-settings',
			array( $this, 'settings_page' )
		);

		// Remove duplicate menu hack.
		// Note: It needs to go after the above add_submenu_page call.
		remove_submenu_page( $primary_slug, $primary_slug );

		// To remove later in 1.0.0.
		add_submenu_page(
			'options-general.php',
			__( 'Really Simple Featured Video Settings', 'rsfv' ),
			__( 'Really Simple Featured Video (Old)', 'rsfv' ),
			'manage_options',
			'rsfv-settings-old',
			array( $this, 'old_settings_menu' )
		);
	}

	/**
	 * Hide Freemius submenus if they exist.
	 *
	 * @return void
	 */
	public function hide_freemius_submenus() {
		// Enqueue a core admin style as a handle for inline CSS.
		wp_enqueue_style( 'wp-admin' );

		$custom_css = '.toplevel_page_jetixwp .wp-submenu li a[href*="-addons"] { display: none !important; }';
		wp_add_inline_style( 'wp-admin', $custom_css );
	}

	/**
	 * Redirect old settings menu to new one.
	 *
	 * To remove later in 1.0.0.
	 *
	 * @return void
	 */
	public function old_settings_menu() {
		echo "<p>Hello! This page has been moved to the <a href='" . esc_url( admin_url( 'admin.php?page=jetixwp' ) ) . "'>JetixWP menu</a>. You will be redirected there in a second...</p>";
		?>
			<script type="text/javascript">
				setTimeout(function() {
					window.location.href = "<?php echo esc_url( admin_url( 'admin.php?page=rsfv-settings' ) ); ?>";
				}, 1000);
			</script>
		<?php
	}

	/**
	 * Add settings page.
	 *
	 * @return void
	 */
	public function settings_page() {
		Admin_Settings::output();
	}

	/**
	 * Default options.
	 *
	 * Sets up the default options used on the settings page.
	 */
	public function create_options() {
		if ( ! is_admin() ) {
			return false;
		}

		// Include settings so that we can run through defaults.
		include RSFV_PLUGIN_DIR . 'includes/Settings/class-admin-settings.php';

		$settings = Admin_Settings::get_settings_pages();

		foreach ( $settings as $section ) {
			if ( 'object' !== gettype( $section ) || ! method_exists( $section, 'get_settings' ) ) {
				continue;
			}
			$subsections = array_unique( array_merge( array( '' ), array_keys( $section->get_sections() ) ) );

			foreach ( $subsections as $subsection ) {
				foreach ( $section->get_settings( $subsection ) as $value ) {
					if ( isset( $value['default'], $value['id'] ) ) {
						$autoload = isset( $value['autoload'] ) ? (bool) $value['autoload'] : true;
						add_option( $value['id'], $value['default'], '', ( $autoload ? 'yes' : 'no' ) );
					}
				}
			}
		}
	}

	/**
	 * Handle saving of settings.
	 *
	 * @return void
	 */
	public function save_settings() {
		global $current_tab, $current_section;

		// We should only save on the settings page.
		if ( ! is_admin() || ! isset( $_GET['page'] ) || 'rsfv-settings' !== $_GET['page'] ) {
			return;
		}

		// Include settings pages.
		Admin_Settings::get_settings_pages();

		// Get current tab/section.
		$current_tab     = empty( $_GET['tab'] ) ? 'general' : sanitize_title( wp_unslash( $_GET['tab'] ) );
		$current_section = empty( $_REQUEST['section'] ) ? '' : sanitize_title( wp_unslash( $_REQUEST['section'] ) );
		$nonce           = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';

		// Save settings if data has been posted.
		if ( wp_verify_nonce( $nonce, 'rsfv-settings' ) ) {
			if ( '' !== $current_section && apply_filters( "rsfv_save_settings_{$current_tab}_{$current_section}", ! empty( $_POST['save'] ) ) ) {
				Admin_Settings::save();
			} elseif ( '' === $current_section && apply_filters( "rsfv_save_settings_{$current_tab}", ! empty( $_POST['save'] ) || isset( $_POST['rsfv-license_activate'] ) ) ) {
				Admin_Settings::save();
			}
		}
	}

	/**
	 * Remove all notices from settings page for a clean and minimal look.
	 *
	 * @return void
	 */
	public function cleanup_plugin_settings_page() {
		remove_all_actions( 'admin_notices' );
	}
}

/**
 * Clean variables using sanitize_text_field. Arrays are cleaned recursively.
 * Non-scalar values are ignored.
 *
 * @param string|array $var Data to sanitize.
 * @return string|array
 */
function rsfv_clean( $var ) {
	if ( is_array( $var ) ) {
		return array_map( __NAMESPACE__ . '\rsfv_clean', $var );
	}

	return is_scalar( $var ) ? sanitize_text_field( $var ) : $var;
}

/**
 * Output admin fields.
 *
 * Loops though the RSFV options array and outputs each field.
 *
 * @param array $options Opens array to output.
 */
function rsfv_admin_fields( $options ) {

	if ( ! class_exists( 'Admin_Settings', false ) ) {
		include __DIR__ . '/class-admin-settings.php';
	}

	Admin_Settings::output_fields( $options );
}

/**
 * Update all settings which are passed.
 *
 * @param array $options Option fields to save.
 * @param array $data Passed data.
 */
function rsfv_update_options( $options, $data = null ) {

	if ( ! class_exists( 'Admin_Settings', false ) ) {
		include __DIR__ . '/class-admin-settings.php';
	}

	Admin_Settings::save_fields( $options, $data );
}

/**
 * Get a setting from the settings API.
 *
 * @param mixed $option_name Option name to save.
 * @param mixed $default Default value to save.
 * @return string
 */
function rsfv_settings_get_option( $option_name, $default = '' ) {

	if ( ! class_exists( 'Admin_Settings', false ) ) {
		include __DIR__ . '/class-admin-settings.php';
	}

	return Admin_Settings::get_option( $option_name, $default );
}

/**
 * Get enabled post types.
 *
 * @return array
 */
function get_post_types() {
	$post_types = Options::get_instance()->get( 'post_types' );
	$post_types = is_array( $post_types ) ? array_keys( $post_types ) : '';

	if ( ! is_array( $post_types ) && empty( $post_types ) ) {
		$post_types = array( 'post' );
	}

	return apply_filters( 'rsfv_get_enabled_post_types', $post_types );
}

/**
 * Get default video controls.
 *
 * @return array
 */
function get_default_video_controls() {
	return array(
		'controls' => true,
	);
}

/**
 * Get enabled video controls.
 *
 * @param string $type Type of video.
 *
 * @return array
 */
function get_video_controls( $type = 'self' ) {
	$defaults = get_default_video_controls();

	if ( 'self' === $type ) {
		$controls = Options::get_instance()->get( 'self_video_controls' );
	} else {
		$controls = Options::get_instance()->get( 'embed_video_controls' );
	}

	return is_array( $controls ) && ! empty( $controls ) ? $controls : $defaults;
}
