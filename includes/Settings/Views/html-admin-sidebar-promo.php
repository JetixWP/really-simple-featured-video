<?php
/**
 * Admin View: sidebar with the review card and the PRO upgrade box.
 *
 * Used by Settings and Video Studio. Set $rsfv_promo_studio to true before
 * including it to list Video Studio PRO first.
 *
 * @package RSFV
 */

use RSFV\Settings\Register;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
					<?php if ( ! Register::is_review_card_dismissed() ) : ?>
						<?php
						$rsfv_dismiss_review_url = wp_nonce_url(
							add_query_arg( 'rsfv_dismiss_review', '1' ),
							'rsfv_dismiss_review_card'
						);
						?>
						<div class="notice-box is-dismissible" data-rsfv-dismiss="review-card">
							<a class="notice-dismiss rsfv-dismiss-review" href="<?php echo esc_url( $rsfv_dismiss_review_url ); ?>">
								<span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'rsfv' ); ?></span>
							</a>
							<div>
								<h3><?php esc_html_e( 'Help keep this plugin free & updated', 'rsfv' ); ?></h3>
								<p class="desc"><?php esc_html_e( 'Your review means a lot. It helps others discover that Really Simple Featured Video is free and actively maintained. If the plugin has helped you, please consider leaving your honest review/feedback on WordPress.org. Thank you!', 'rsfv' ); ?></p>
							</div>
							<div>
								<a class="button button-primary" href="https://wordpress.org/support/plugin/really-simple-featured-video/reviews/#new-post" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Leave a Review', 'rsfv' ); ?></a>
							</div>
						</div>
					<?php endif; ?>

					<?php if ( ! class_exists( '\RSFV_Pro\Plugin' ) ) : ?>
						<?php $rsfv_pro_url = 'https://jetixwp.com/plugins/really-simple-featured-video/?utm_campaign=' . ( empty( $rsfv_promo_studio ) ? 'settings-sidebar' : 'studio-sidebar' ) . '&utm_source=rsfv-plugin'; ?>
						<div class="upgrade-box">
							<div>
								<h3><span class="dashicons dashicons-tag rsfv-promo-icon" aria-hidden="true"></span><?php esc_html_e( 'Anniversary deal: RSFV PRO from $59', 'rsfv' ); ?></h3>
								<p class="desc"><?php esc_html_e( 'Auto generate more featured videos from templates with Video Studio PRO, get deeper WooCommerce control, full video analytics, wider theme support, and direct help from the developer. One payment, lifetime updates.', 'rsfv' ); ?></p>
							</div>
							<div class="rsfv-anniversary-deal">
								<p class="rsfv-anniversary-deal__label"><?php esc_html_e( 'Special Anniversary Deal', 'rsfv' ); ?> <span class="rsfv-anniversary-deal__badge"><?php esc_html_e( 'Save up to 50%', 'rsfv' ); ?></span></p>
								<ul class="rsfv-anniversary-deal__plans">
									<li>
										<span class="rsfv-anniversary-deal__plan"><?php esc_html_e( 'Single site', 'rsfv' ); ?> <em><?php esc_html_e( 'Save 34%', 'rsfv' ); ?></em></span>
										<span class="rsfv-anniversary-deal__amounts"><s>$89</s> <strong>$59</strong></span>
									</li>
									<li>
										<span class="rsfv-anniversary-deal__plan"><?php esc_html_e( 'Unlimited sites', 'rsfv' ); ?> <em><?php esc_html_e( 'Save 50%', 'rsfv' ); ?></em></span>
										<span class="rsfv-anniversary-deal__amounts"><s>$199</s> <strong>$99</strong></span>
									</li>
								</ul>
								<p class="rsfv-anniversary-deal__note"><?php esc_html_e( 'One-time payment · lifetime updates · no renewals', 'rsfv' ); ?></p>
								<a class="button button-primary" href="<?php echo esc_url( $rsfv_pro_url . '#pricing' ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Get PRO for $59', 'rsfv' ); ?></a>
								<p class="rsfv-anniversary-deal__secure"><span class="dashicons dashicons-lock rsfv-promo-icon" aria-hidden="true"></span><?php esc_html_e( 'Secure checkout · 14-day money-back guarantee', 'rsfv' ); ?></p>
							</div>
							<div>
								<p class="desc"><strong><?php esc_html_e( 'What PRO adds', 'rsfv' ); ?></strong></p>
								<ul class="rsfv-upgrade-features">
									<li><strong><?php esc_html_e( 'Video Studio PRO', 'rsfv' ); ?></strong> — <?php esc_html_e( 'auto generate videos from 20 more templates, in vertical and 4K sizes, with music, a brand kit, every Google Font, and for many entries at once', 'rsfv' ); ?></li>
									<li><strong><?php esc_html_e( 'Priority support', 'rsfv' ); ?></strong> — <?php esc_html_e( 'direct help from the developer', 'rsfv' ); ?></li>
									<li><strong><?php esc_html_e( 'Full video analytics', 'rsfv' ); ?></strong> — <?php esc_html_e( 'history beyond 14 days, watch time, completion, CSV export', 'rsfv' ); ?></li>
									<li><strong><?php esc_html_e( 'WooCommerce controls', 'rsfv' ); ?></strong> — <?php esc_html_e( 'gallery order, thumbnails, aspect ratios', 'rsfv' ); ?></li>
									<li><strong><?php esc_html_e( 'Autoplay on hover', 'rsfv' ); ?></strong> — <?php esc_html_e( 'extended controls for listings and shops', 'rsfv' ); ?></li>
									<li><strong><?php esc_html_e( 'Premium and custom themes', 'rsfv' ); ?></strong> — <?php esc_html_e( 'more supported, compatibility on request', 'rsfv' ); ?></li>
								</ul>
								<p class="rsfv-upgrade-compare"><a href="<?php echo esc_url( $rsfv_pro_url . '#compare' ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Compare free vs PRO →', 'rsfv' ); ?></a></p>
							</div>

							<div class="rsfv-upgrade-founder">
								<p><em>If you like our free plugin, you will absolutely love the PRO version. Thank you for using RSFV again, you are not just any supporter but truly the founders of our small business.</em></p>
								<p><strong>Krishna</strong>, Founder and Lead Developer</p>

								<p><strong>Have questions?</strong> Send them at <a href="mailto:krishna@jetixwp.com">krishna@jetixwp.com</a>, and I will personally get back to you at the earliest :)</p>

							</div>
						</div>
					<?php endif; ?>
