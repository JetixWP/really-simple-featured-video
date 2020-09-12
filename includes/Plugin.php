<?php
namespace RSFV;
/**
 * Class RSFV_featured_video
 */
final class Plugin {
    protected static $instance;
    protected $counter;

    public function __construct() {
        $this->includes();
        $this->register();
    }

    public static function get_instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }

        do_action( 'RSFV_loaded' );
        return self::$instance;
    }

    public function register() {
        // Register Plugin classes.
        Metabox::get_instance();
        Shortcode::get_instance();
        FrontEnd::get_instance();
    }

    public function includes() {
        require_once RSFV_PLUGIN_DIR . 'includes/Metabox.php';
        require_once RSFV_PLUGIN_DIR . 'includes/Shortcode.php';
        require_once RSFV_PLUGIN_DIR . 'includes/FrontEnd.php';
    }
}