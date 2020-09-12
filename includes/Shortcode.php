<?php
namespace RSFV;
/**
 * Class Shortcode
 */
class Shortcode {
    /**
     * @var $instance
     */
    protected static $instance;

    public function __construct() {
        // Shortcode to display the video on pages, or posts.
        add_shortcode('rsfv', array( $this, 'show_video' ) );

        // Shortcode to display using post id.
        add_shortcode('rsfv_by_postid', array( $this, 'show_video_by_post_id' ) );
    }

    /**
     * Get an instance of class.
     *
     * @return Shortcode
     */
    public static function get_instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Show video on posts & pages.
     *
     * @return string|void
     */
    public function show_video(){
        global $post;

        $type = $post->post_type; // current post type
        $meta_key = 'rsfv_featured_video';
        $video_id = get_post_meta( $post->ID, $meta_key, true);
        $video_url = wp_get_attachment_url( $video_id );
        $posts_available = 'post, page'; // TODO: Set an option for this at settings
        if( ! empty( $posts_available ) ){
            $posts_available = explode(',', $posts_available);
            if( in_array( $type, $posts_available ) ){
                if( $video_url) {
                    return __('<video class="rsfv-video" id="rsfv-video_'. $post->ID .'" controls="" src="' . $video_url .'" style="max-width:100%;display:block;"></video>');
                }
            }
        }

    }

    /**
     *
     * Show video by post id.
     *
     * @param $atts array Shortcode attributes
     * @return string|void
     */
    public function show_video_by_post_id( $atts ){
        global $post;

        $meta_key = 'rsfv_featured_video';
        $video_id = get_post_meta( $atts['postid'], $meta_key, true);
        $video_url = wp_get_attachment_url( $video_id );
        $type = $post->post_type; // current post type
        $posts_available = 'post, page'; // TODO: Set an option for this at settings
        if( ! empty( $posts_available ) ){
            $posts_available = explode(",", $posts_available);
            if( in_array( $type, $posts_available ) ){
                if( $video_url ) {  // getting video here
                    return __('<video class="rsfv-video" id="rsfv_video_'.$atts['postid'].'" controls="" src="'.$video_url.'" style="max-width:100%%;display:block;"></video>');
                }
            }
        }
    }

}