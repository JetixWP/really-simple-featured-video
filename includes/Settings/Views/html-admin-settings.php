<?php
/**
 * Admin View: Settings
 *
 * @package RSFV
 */

namespace RSFV\Settings\Views;

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
				<a href="https://jetixwp.com?utm_campaign=settings-header&utm_source=rsfv-plugin" target="_blank"><img class="brand-logo" src="<?php echo esc_url( RSFV_PLUGIN_URL . 'assets/images/icon-dark.svg' ); ?>" alt="RSFV"></a>
			</div>
		</div>
	</div>
	<div class="rsfv-wrapper">
		<form method="<?php echo esc_attr( apply_filters( 'rsfv_settings_form_method_tab_' . $current_tab, 'post' ) ); ?>" id="mainform" action="" enctype="multipart/form-data">

			<div class="nav-content">
				<nav class="nav-tab-wrapper rsfv-nav-tab-wrapper">
					<?php

					foreach ( $tabs as $slug => $label ) {
						echo '<a href="' . esc_html( admin_url( 'admin.php?page=rsfv-settings&tab=' . esc_attr( $slug ) ) ) . '" class="nav-tab ' . ( $current_tab === $slug ? 'nav-tab-active' : '' ) . '">' . esc_html( $label ) . '</a>';
					}

					do_action( 'rsfv_settings_tabs' );

					?>
				</nav>
			</div>
			<div class="tab-content">
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

				<div class="sidebar">
					<?php if ( ! class_exists( '\RSFV_Pro\Plugin' ) ) : ?>
						<div class="upgrade-box">
							<div>
								<h3>🚀 &nbsp;Grab the PRO version with a Lifetime Deal</h3>
								<p class="desc">Our RSFV Pro plugin is now available to support additional features, where we continue to keep them maintained and updated. We are also continuously working on adding more and more theme support.</p>
							</div>
							<div>
								<p class="desc"><strong>Our Pro plugin includes these features -</strong></p>
								<ul>
									<li>✅ <strong>Priority Support</strong></li>
									<li>✅ <strong>Extended Autoplay on Hover [Currently in BETA]</strong></li>
									<li>✅ <strong>Change Video Aspect Ratio</strong></li>
									<li>✅ <strong>Change Video Order at Woo Product CPT</strong></li>
									<li>✅ <strong>Support for more Premium/Custom Themes</strong></li>
									<li>✅ <strong>Requests for Theme Compatibility</strong></li>
								</ul>
							</div>
							<div>
								<p class="desc">New features in every update, to name a few upcoming changes -</p>
								<ul>
									<li>🏗️ Featured Video Blocks for Full Site Editing </li>
									<li>🏗️ And more Video related customizations</li>
								</ul>
							</div>

							<div>
								<a class="button button-primary" href="https://jetixwp.com/plugins/really-simple-featured-video?utm_campaign=settings-sidebar&utm_source=rsfv-plugin" target="_blank">✨ Unlock Extra features with PRO</a>
							</div>

							<div>
								<p><em>If you like our free plugin, you will absolutely love the PRO version. Thank you for using RSFV again, you are not just any supporter but truly the founders of our small business.</em></p>
								<p><strong>Krishna</strong>, Founder and Lead Developer</p>

								<p><strong>Have questions?</strong> Please mail us at <a href="mailto:hello@jetixwp.com">hello@jetixwp.com</a>, and we will get back to you at the earliest :)</p>

							</div>
						</div>
					<?php endif; ?>
					<?php do_action( 'rsfv_extend_settings_sidebar' ); ?>
				</div>
			</div>
		</form>
	</div>
</div>
