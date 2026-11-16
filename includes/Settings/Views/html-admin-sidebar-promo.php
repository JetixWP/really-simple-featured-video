<?php
/**
 * Admin View: sidebar with the PRO upgrade box.
 *
 * Used by Settings and Video Studio. Set $rsfv_promo_studio to true before
 * including it to list Video Studio PRO first.
 *
 * @package RSFV
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rsfv_has_pro = class_exists( '\RSFV_Pro\Plugin' );

?>
					<?php if ( ! $rsfv_has_pro ) : ?>
						<?php
						$rsfv_pro_url            = 'https://jetixwp.com/plugins/really-simple-featured-video/?utm_campaign=' . ( empty( $rsfv_promo_studio ) ? 'settings-sidebar' : 'studio-sidebar' ) . '&utm_source=rsfv-plugin';
						$rsfv_checkout_url       = 'https://checkout.freemius.com/plugin/8537/plan/37071/licenses/%s/?billing_cycle=lifetime&coupon=BLACKFRIDAY26&hide_coupon=true';
						$rsfv_checkout_single    = sprintf( $rsfv_checkout_url, '1' );
						$rsfv_checkout_unlimited = sprintf( $rsfv_checkout_url, 'unlimited' );
						?>
						<div class="upgrade-box rsfv-bf">
							<div class="rsfv-bf__deal">
								<p class="rsfv-bf__top">
									<span class="rsfv-bf__eyebrow"><?php esc_html_e( 'Black Friday', 'rsfv' ); ?></span>
									<span class="rsfv-bf__badge"><?php esc_html_e( 'Up to 50% off', 'rsfv' ); ?></span>
								</p>
								<h3 class="rsfv-bf__title"><?php esc_html_e( 'RSFV PRO for life, from $59', 'rsfv' ); ?></h3>
								<p class="rsfv-bf__ends"><span class="dashicons dashicons-clock" aria-hidden="true"></span><?php esc_html_e( 'Ends December 2', 'rsfv' ); ?></p>
								<ul class="rsfv-bf__plans">
									<li>
										<a class="rsfv-bf__plan is-best" href="<?php echo esc_url( $rsfv_checkout_unlimited ); ?>" target="_blank" rel="noopener noreferrer">
											<span class="rsfv-bf__plan-info">
												<span class="rsfv-bf__plan-name"><?php esc_html_e( 'Unlimited sites', 'rsfv' ); ?></span>
												<span class="rsfv-bf__plan-tag"><?php esc_html_e( 'Best value · save 50%', 'rsfv' ); ?></span>
											</span>
											<span class="rsfv-bf__amounts"><s><span class="screen-reader-text"><?php esc_html_e( 'Regular price', 'rsfv' ); ?></span>$199</s> <strong><span class="screen-reader-text"><?php esc_html_e( 'Black Friday price', 'rsfv' ); ?></span>$99</strong></span>
										</a>
									</li>
									<li>
										<a class="rsfv-bf__plan" href="<?php echo esc_url( $rsfv_checkout_single ); ?>" target="_blank" rel="noopener noreferrer">
											<span class="rsfv-bf__plan-info">
												<span class="rsfv-bf__plan-name"><?php esc_html_e( 'Single site', 'rsfv' ); ?></span>
												<span class="rsfv-bf__plan-tag"><?php esc_html_e( 'Save 34%', 'rsfv' ); ?></span>
											</span>
											<span class="rsfv-bf__amounts"><s><span class="screen-reader-text"><?php esc_html_e( 'Regular price', 'rsfv' ); ?></span>$89</s> <strong><span class="screen-reader-text"><?php esc_html_e( 'Black Friday price', 'rsfv' ); ?></span>$59</strong></span>
										</a>
									</li>
								</ul>
								<a class="button rsfv-bf__cta" href="<?php echo esc_url( $rsfv_checkout_unlimited ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Claim 50% off', 'rsfv' ); ?></a>
								<p class="rsfv-bf__fine"><?php esc_html_e( 'One-time payment · lifetime updates · no renewals', 'rsfv' ); ?></p>
								<p class="rsfv-bf__fine"><span class="dashicons dashicons-lock" aria-hidden="true"></span><?php esc_html_e( 'Secure checkout · 14-day money-back guarantee', 'rsfv' ); ?></p>
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

						<?php if ( ! empty( $rsfv_promo_studio ) ) : ?>
							<?php // The Studio list runs long, this keeps the deal in view once the box above scrolls away. ?>
							<div class="rsfv-bf-mini">
								<p>
									<strong><?php esc_html_e( 'Black Friday: RSFV PRO from $59', 'rsfv' ); ?></strong>
									<span><?php esc_html_e( 'Up to 50% off · ends December 2', 'rsfv' ); ?></span>
								</p>
								<a class="button rsfv-bf__cta" href="<?php echo esc_url( $rsfv_checkout_unlimited ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Claim 50% off', 'rsfv' ); ?></a>
							</div>
						<?php endif; ?>
					<?php endif; ?>
