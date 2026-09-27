<?php
/**
 * Admin View: Help Tab Settings
 *
 * @package RSFV
 * @since 0.14.2
 */

namespace RSFV\Settings\views;

?>

<div class="help-content">
	<h1 class="tab-heading"><?php esc_html_e( 'Help and Support', 'rsfv' ); ?></h1>
	<p>
		<?php
		printf(
			/* translators: 1: opening anchor tag, 2: closing anchor tag */
			wp_kses(
				__( 'We try our best to help with every support request for the free plugin. You can ask questions and get assistance by posting to the %1$sWordPress.org support forum%2$s. Because we are a small team, replies there can sometimes be delayed when many people need help at once.', 'rsfv' ),
				array(
					'a' => array(
						'href'   => array(),
						'target' => array(),
						'rel'    => array(),
					),
				)
			),
			'<a href="https://wordpress.org/support/plugin/really-simple-featured-video/" target="_blank" rel="noopener noreferrer">',
			'</a>'
		);
		?>
	</p>
	<p>
		<?php
		printf(
			/* translators: 1: opening anchor tag, 2: closing anchor tag */
			wp_kses(
				__( 'If you need or want urgent support directly from the creator of this plugin, please consider upgrading to a JetixWP license. One-time payment options are available at %1$sJetixWP &gt; Really Simple Featured Video%2$s.', 'rsfv' ),
				array(
					'a' => array(
						'href'   => array(),
						'target' => array(),
						'rel'    => array(),
					),
				)
			),
			'<a href="' . esc_url( RSFV_PLUGIN_PRO_URL . '/?utm_campaign=settings-helptab&utm_source=rsfv-plugin' ) . '" target="_blank" rel="noopener noreferrer">',
			'</a>'
		);
		?>
	</p>

	<p>
		<?php
		printf(
			/* translators: 1: opening anchor tag, 2: closing anchor tag */
			wp_kses(
				__( 'If you\'ve found a bug, please submit an issue at %1$sGitHub%2$s.', 'rsfv' ),
				array(
					'a' => array(
						'href'   => array(),
						'target' => array(),
						'rel'    => array(),
					),
				)
			),
			'<a href="https://github.com/JetixWP/really-simple-featured-video/issues" target="_blank" rel="noopener noreferrer">',
			'</a>'
		);
		?>
	</p>
</div>
