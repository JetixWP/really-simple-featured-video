<?php
/**
 * Admin View: Settings
 *
 * @package RSFV
 */

namespace RSFV\Settings\Views;

use RSFV\Settings\Register;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tab_exists        = isset( $tabs[ $current_tab ] ) || has_action( 'rsfv_sections_' . $current_tab ) || has_action( 'rsfv_settings_' . $current_tab ) || has_action( 'rsfv_settings_tabs_' . $current_tab );
$current_tab_label = isset( $tabs[ $current_tab ] ) ? $tabs[ $current_tab ] : '';

if ( ! $tab_exists ) {
	wp_safe_redirect( admin_url( 'admin.php?page=rsfv-settings' ) );
	exit;
}
?>
<div class="wrap rsfv <?php echo esc_attr( $current_tab ); ?>">
	<div class="plugin-header">
		<div class="plugin-header-wrap">
			<div class="plugin-info">
				<h1 class="menu-title"><?php esc_html_e( 'Really Simple Featured Video', 'rsfv' ); ?></h1>
				<?php do_action( 'rsfv_extend_plugin_header' ); ?>
				<div class="plugin-version">
					<span>v<?php echo esc_html( RSFV_VERSION ); ?></span>
				</div>
			</div>

			<div class="brand-info">
				<a href="https://jetixwp.com?utm_campaign=settings-header&utm_source=rsfv-plugin" target="_blank"><img class="brand-logo" src="<?php echo esc_url( RSFV_PLUGIN_URL . 'assets/images/jwp-icon-dark.svg' ); ?>" alt="RSFV"></a>
			</div>
		</div>
	</div>
	<div class="rsfv-wrapper">
			<div class="nav-content">
				<nav class="nav-tab-wrapper rsfv-nav-tab-wrapper">
					<?php

					foreach ( $tabs as $slug => $label ) {
						echo '<a href="' . esc_url( admin_url( 'admin.php?page=rsfv-settings&tab=' . $slug ) ) . '" class="nav-tab nav-tab-' . esc_attr( $slug ) . ' ' . ( $current_tab === $slug ? 'nav-tab-active' : '' ) . '">' . esc_html( $label ) . '</a>';
					}

					do_action( 'rsfv_settings_tabs' );

					?>
				</nav>
			</div>
			<div class="tab-content">
				<form method="<?php echo esc_attr( apply_filters( 'rsfv_settings_form_method_tab_' . $current_tab, 'post' ) ); ?>" id="mainform" action="" enctype="multipart/form-data">
					<div class="content">
						<h1 class="screen-reader-text"><?php echo esc_html( $current_tab_label ); ?></h1>
						<?php
						do_action( 'rsfv_sections_' . $current_tab );

						self::show_messages();

						do_action( 'rsfv_settings_' . $current_tab );
						?>
						<p class="submit">
							<?php if ( empty( $GLOBALS['hide_save_button'] ) ) : ?>
								<button name="save" class="button-primary rsfv-save-button" type="submit" value="<?php esc_attr_e( 'Save changes', 'rsfv' ); ?>"><?php esc_html_e( 'Save changes', 'rsfv' ); ?></button>
							<?php endif; ?>
							<?php wp_nonce_field( 'rsfv-settings' ); ?>
						</p>
					</div>
				</form>

				<div class="sidebar">

					<?php include RSFV_PLUGIN_DIR . 'includes/Settings/Views/html-admin-sidebar-promo.php'; ?>
					<?php do_action( 'rsfv_extend_settings_sidebar' ); ?>
			</div>

	</div>
</div>
