<?php
/**
 * Admin View: sidebar card promoting Delta (deltatheme.com).
 *
 * Included by html-admin-sidebar-promo.php, which sets $rsfv_has_pro and, on the
 * Video Studio screen, $rsfv_promo_studio. Only PRO users can dismiss the card.
 *
 * @package RSFV
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rsfv_delta_surface = empty( $rsfv_promo_studio ) ? 'sidebar' : 'studio-sidebar';
$rsfv_delta_args    = array(
	'utm_source'   => 'rsfv-plugin',
	'utm_medium'   => 'plugin',
	'utm_campaign' => 'delta-launch',
);
$rsfv_delta_url     = add_query_arg( array_merge( $rsfv_delta_args, array( 'utm_content' => $rsfv_delta_surface ) ), 'https://deltatheme.com/' );
$rsfv_delta_cta_url = add_query_arg( array_merge( $rsfv_delta_args, array( 'utm_content' => $rsfv_delta_surface . '-cta' ) ), 'https://deltatheme.com/' );
?>
					<div class="rsfv-delta-promo is-dismissible">
						<?php if ( ! empty( $rsfv_has_pro ) ) : ?>
							<a class="notice-dismiss rsfv-dismiss-delta" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'rsfv_dismiss_delta', '1' ), 'rsfv_dismiss_delta_promo' ) ); ?>">
								<span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'rsfv' ); ?></span>
							</a>
						<?php endif; ?>
						<span class="rsfv-delta-promo__tag"><?php esc_html_e( 'New', 'rsfv' ); ?></span>
						<p class="rsfv-delta-promo__eyebrow"><?php esc_html_e( 'Our latest and greatest', 'rsfv' ); ?></p>
						<div class="rsfv-delta-promo__header">
							<img width="48" height="48" src="<?php echo esc_url( RSFV_PLUGIN_URL . 'assets/images/delta-logo.svg' ); ?>" alt="<?php esc_attr_e( 'Delta logo', 'rsfv' ); ?>" />
							<h3><a href="<?php echo esc_url( $rsfv_delta_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Meet Delta: build WordPress sites with AI', 'rsfv' ); ?></a></h3>
						</div>

						<p class="desc"><?php esc_html_e( 'From the JetixWP team, seven months in the making and now out. Describe the site you want and your AI agent builds it in clean HTML and CSS, without the headache and learning curve of page builders.', 'rsfv' ); ?></p>

						<ul class="rsfv-upgrade-features">
							<li><strong><?php esc_html_e( 'Works with Claude, ChatGPT, Cursor and any MCP client', 'rsfv' ); ?></strong></li>
							<li><strong><?php esc_html_e( 'Clean HTML and CSS output, no builder markup', 'rsfv' ); ?></strong></li>
							<li><strong><?php esc_html_e( 'Templates for every part of the site', 'rsfv' ); ?></strong></li>
							<li><strong><?php esc_html_e( 'Sketchbook boards to try ideas safely', 'rsfv' ); ?></strong></li>
							<li><strong><?php esc_html_e( 'Bring your own AI, no AI fee from Delta', 'rsfv' ); ?></strong></li>
						</ul>

						<p class="rsfv-delta-promo__pricing">
							<?php
							echo wp_kses(
								sprintf(
									/* translators: 1: single site price, 2: unlimited sites price. */
									esc_html__( 'Founder’s Lifetime License: %1$s single site · %2$s unlimited sites. One payment, lifetime updates, 30-day refund guarantee.', 'rsfv' ),
									'<strong>$49</strong>',
									'<strong>$99</strong>'
								),
								array( 'strong' => array() )
							);
							?>
						</p>

						<p class="rsfv-delta-promo__support"><?php esc_html_e( 'Founder’s pricing is half the regular price. Lock it in now and get your sites built and managed by an AI agent you already use.', 'rsfv' ); ?></p>

						<a class="button button-primary" href="<?php echo esc_url( $rsfv_delta_cta_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( '✨ Explore Delta', 'rsfv' ); ?></a>
					</div>
