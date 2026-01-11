<?php
/**
 * Admin View: Bulk Actions
 *
 * @package RSFV
 */

namespace RSFV\Tools\Views;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="wrap rsfv rsfv-tools rsfv-tools-page">
	<div class="plugin-header">
		<div class="plugin-header-wrap">
			<div class="plugin-info">
				<h1 class="menu-title"><?php esc_html_e( 'Really Simple Featured Video → Tools', 'rsfv' ); ?></h1>
				<?php do_action( 'rsfv_extend_plugin_header' ); ?>
			</div>

			<div class="brand-info">
				<a href="https://jetixwp.com?utm_campaign=settings-header&utm_source=rsfv-plugin" target="_blank"><img class="brand-logo" src="<?php echo esc_url( RSFV_PLUGIN_URL . 'assets/images/jwp-icon-dark.svg' ); ?>" alt="RSFV"></a>
			</div>
		</div>
	</div>
	<div id="rsfv-tools-app"></div>
</div>
