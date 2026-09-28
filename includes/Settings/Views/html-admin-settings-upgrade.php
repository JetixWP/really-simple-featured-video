<?php
/**
 * Admin View: Upgrade Tab Settings
 *
 * @package RSFV
 * @since 0.56.2
 */

namespace RSFV\Settings\views;

defined( 'ABSPATH' ) || exit;

$rsfv_pro_url     = RSFV_PLUGIN_PRO_URL . '/?utm_campaign=settings-protab&utm_source=rsfv-plugin';
$rsfv_pricing_url = $rsfv_pro_url . '#pricing';
$rsfv_compare_url = $rsfv_pro_url . '#compare';

$rsfv_pro_features = array(
	array(
		'icon'  => 'dashicons-chart-area',
		'title' => __( 'Full video analytics', 'rsfv' ),
		'desc'  => __( 'See plays, watch time and completion for every video, keep history beyond 14 days, and export to CSV.', 'rsfv' ),
	),
	array(
		'icon'  => 'dashicons-cart',
		'title' => __( 'WooCommerce gallery control', 'rsfv' ),
		'desc'  => __( 'Pick where the video sits in the product gallery, use your own gallery thumbnail, and set one aspect ratio for every video.', 'rsfv' ),
	),
	array(
		'icon'  => 'dashicons-controls-play',
		'title' => __( 'Extended autoplay on hover', 'rsfv' ),
		'desc'  => __( 'Preview videos on hover in shop, archive and listing pages, with finer control over how they play.', 'rsfv' ),
	),
	array(
		'icon'  => 'dashicons-admin-appearance',
		'title' => __( 'Premium and custom theme support', 'rsfv' ),
		'desc'  => __( 'Works with more premium themes out of the box. Using something unusual? Ask and we will add support.', 'rsfv' ),
	),
	array(
		'icon'  => 'dashicons-sos',
		'title' => __( 'Priority support from the developer', 'rsfv' ),
		'desc'  => __( 'Skip the forum queue. Your questions go straight to the person who builds the plugin.', 'rsfv' ),
	),
	array(
		'icon'  => 'dashicons-lightbulb',
		'title' => __( 'Shape the roadmap', 'rsfv' ),
		'desc'  => __( 'Feature and compatibility requests from PRO users go to the front of the line.', 'rsfv' ),
	),
);

$rsfv_pro_faqs = array(
	array(
		'q' => __( 'Is this a subscription?', 'rsfv' ),
		'a' => __( 'No. You pay once and receive updates for life. There are no renewals and nothing to cancel.', 'rsfv' ),
	),
	array(
		'q' => __( 'Will my current settings and videos carry over?', 'rsfv' ),
		'a' => __( 'Yes. PRO runs alongside the free plugin and adds to it. Nothing is reset or removed when you upgrade.', 'rsfv' ),
	),
	array(
		'q' => __( 'What if PRO does not work for me?', 'rsfv' ),
		'a' => __( 'Ask for a refund within 14 days of purchase and you get your money back, no questions asked.', 'rsfv' ),
	),
);
?>

<div class="upgrade-content">

	<section class="rsfv-pro-hero">
		<p class="rsfv-pro-eyebrow">🎉 <?php esc_html_e( 'Anniversary deal · up to 50% off for a limited time', 'rsfv' ); ?></p>
		<h1 class="tab-heading"><?php esc_html_e( 'Get more out of every featured video with RSFV PRO', 'rsfv' ); ?></h1>
		<p class="rsfv-pro-lead"><?php esc_html_e( 'Know which videos people watch, control exactly how they show up in your WooCommerce store, and get help directly from the developer when you need it.', 'rsfv' ); ?></p>
		<div class="rsfv-pro-actions">
			<a href="<?php echo esc_url( $rsfv_pricing_url ); ?>" target="_blank" rel="noopener noreferrer" class="rsfv-button button-primary"><?php esc_html_e( 'Get PRO from $59', 'rsfv' ); ?></a>
			<a href="<?php echo esc_url( $rsfv_compare_url ); ?>" target="_blank" rel="noopener noreferrer" class="rsfv-button button-secondary"><?php esc_html_e( 'Compare free vs PRO', 'rsfv' ); ?></a>
		</div>
		<p class="rsfv-pro-trust">
			<span>✔ <?php esc_html_e( 'One-time payment', 'rsfv' ); ?></span>
			<span>✔ <?php esc_html_e( 'Lifetime updates', 'rsfv' ); ?></span>
			<span>✔ <?php esc_html_e( '14-day money-back guarantee', 'rsfv' ); ?></span>
		</p>
	</section>

	<section class="rsfv-pro-section">
		<h2 class="rsfv-pro-section-title"><?php esc_html_e( 'What you unlock', 'rsfv' ); ?></h2>
		<ul class="rsfv-pro-features">
			<?php foreach ( $rsfv_pro_features as $rsfv_feature ) : ?>
				<li class="rsfv-pro-feature">
					<span class="rsfv-pro-feature__icon dashicons <?php echo esc_attr( $rsfv_feature['icon'] ); ?>" aria-hidden="true"></span>
					<div>
						<h3><?php echo esc_html( $rsfv_feature['title'] ); ?></h3>
						<p><?php echo esc_html( $rsfv_feature['desc'] ); ?></p>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>
	</section>

	<section class="rsfv-pro-section rsfv-pro-faq">
		<h2 class="rsfv-pro-section-title"><?php esc_html_e( 'Common questions', 'rsfv' ); ?></h2>
		<?php foreach ( $rsfv_pro_faqs as $rsfv_faq ) : ?>
			<details>
				<summary><?php echo esc_html( $rsfv_faq['q'] ); ?></summary>
				<p><?php echo esc_html( $rsfv_faq['a'] ); ?></p>
			</details>
		<?php endforeach; ?>
		<p class="rsfv-pro-contact">
			<?php
			printf(
				/* translators: 1: opening anchor tag, 2: closing anchor tag */
				wp_kses(
					__( 'Still unsure? Email %1$skrishna@jetixwp.com%2$s and I will personally get back to you.', 'rsfv' ),
					array( 'a' => array( 'href' => array() ) )
				),
				'<a href="mailto:krishna@jetixwp.com">',
				'</a>'
			);
			?>
		</p>
	</section>

</div>
