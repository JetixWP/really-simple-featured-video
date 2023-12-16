<?php
/**
 * Admin View: Get Pro Tab Settings
 *
 * @package RSFV
 * @since 0.8.1
 */

namespace RSFV\Settings\views;

?>

<div class="getpro-content">
	<h1 class="tab-heading"><?php esc_html_e( 'Extended Featured Video support with [Pro] Really Simple Featured Video', 'rsfv' ); ?></h1>
	<p>Loving the free version of RSFV? We just released our Pro Plugin, give it a try and optimise your Featured Video workflow with more features:</p>
	<ul>
		<li><?php esc_html_e( 'Change Video Aspect Ratio', 'rsfv' ); ?></li>
		<li><?php esc_html_e( 'Change Video Order at WooCommerce Product page', 'rsfv' ); ?></li>
		<li><?php esc_html_e( 'Priority Support', 'rsfv' ); ?></li>
		<li><?php esc_html_e( 'Requests for Theme Compatibility', 'rsfv' ); ?></li>
	</ul>
	<p><?php esc_html_e( '.. and more coming soon.', 'rsfv' ); ?></p>
	<h1 class="offer-highlight">Grab it with the early bird deal with a lifetime version for just <strong>$69.99 (For first few customers)</strong>.</h1>
	<a href="<?php echo esc_url( get_admin_url() . 'options-general.php?page=rsfv-settings-addons' ); ?>" class="rsfv-button button-primary"><?php esc_html_e( 'Explore RSFV Pro', 'rsfv' ); ?></a>
</div>
