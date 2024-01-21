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
	<h1 class="menu-title"><?php esc_html_e( 'Really Simple Featured Video Settings', 'rsfv' ); ?></h1>
	<div class="rsfv-wrapper">
		<form method="<?php echo esc_attr( apply_filters( 'rsfv_settings_form_method_tab_' . $current_tab, 'post' ) ); ?>" id="mainform" action="" enctype="multipart/form-data">
			<nav class="nav-tab-wrapper rsfv-nav-tab-wrapper">
				<?php

				foreach ( $tabs as $slug => $label ) {
					echo '<a href="' . esc_html( admin_url( 'admin.php?page=rsfv-settings&tab=' . esc_attr( $slug ) ) ) . '" class="nav-tab ' . ( $current_tab === $slug ? 'nav-tab-active' : '' ) . '">' . esc_html( $label ) . '</a>';
				}

				do_action( 'rsfv_settings_tabs' );

				?>
			</nav>
			<div class="tab-content">
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
			<?php if ( ! class_exists( '\RSFV_Pro\Plugin' ) ) : ?>
			<div class="upgrade-box">
				<div>
					<h3>🚀 &nbsp;Grab the Pro version with our Early Bird Lifetime Deal</h3>
					<p class="desc">We recently released RSFV Pro plugin to support additional features, where we can continue to keep them maintained and updated.</p>
				</div>
				<div>
					<p class="desc"><strong>Our Pro plugin includes these features -</strong></p>
					<ul>
						<li>✅ <strong>Change Video Aspect Ratio</strong></li>
						<li>✅ <strong>Change Video Order at Woo Product CPT</strong></li>
						<li>✅ <strong>Requests for Theme Compatibility</strong></li>
						<li>✅ <strong>Priority Support</strong></li>
					</ul>
				</div>
				<div>
					<p class="desc">More features to come in the future, to name a few -</p>
					<ul>
						<li>🏗️ Elementor support</li>
						<li>🏗️ Divi Support</li>
						<li>🏗️ Featured Video Blocks for Full Site Editing </li>
						<li>🏗️ And more Woo customizations</li>
					</ul>
				</div>

				<div>
				<a class="button button-primary" href="https://smalltowndev.com/really-simple-featured-video?utm_campaign=settings-sidebar&utm_source=rsfv-plugin" target="_blank">Grab this deal</a>
				</div>

				<div>
					<p><em>Lifetime deal is available for first few customers only, thank you for supporting our launch, you are not just any supporters but truly the founders of our small business.</em></p>
					<p><strong>Krishna</strong>, Creator of Really Simple Featured Video</p>

					<p><strong>Have questions?</strong> Please mail us at <a href="mailto:support@smalltowndev.com">support@smalltowndev.com</a>, and we will get back to you at the earliest :)</p>

				</div>
			</div>
			<?php endif; ?>
		</div>
	</div>
</div>
