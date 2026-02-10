<?php
/**
 * Elementor widget for Really Simple Featured Video.
 *
 * @package RSFV
 * @subpackage Elementor\Widgets
 */

namespace RSFV\Compatibility\Plugins\Elementor\Widgets;

defined( 'ABSPATH' ) || exit;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use function RSFV\Settings\get_post_types;

/**
 * RSFV Video Widget.
 *
 * Displays a featured video using the same rendering pipeline as the RSFV
 * metabox / shortcodes. Users can choose between showing the current post's
 * featured video or specifying a post by ID, and when using the current post
 * they can pick Self-Hosted (media library) or Embed as the video source.
 *
 * The widget reads existing RSFV post meta to auto-populate its controls
 * and writes changes back to post meta on save so the featured video
 * stays in sync with the CPT edit-screen metabox.
 *
 * @since 0.73.0
 */
class RSFV_Video_Widget extends Widget_Base {

	/**
	 * Get widget name.
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'rsfv_video';
	}

	/**
	 * Get widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return esc_html__( 'Really Simple Featured Video', 'rsfv' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'eicon-video-camera';
	}

	/**
	 * Get widget categories.
	 *
	 * @return array Widget categories.
	 */
	public function get_categories() {
		return array( 'general' );
	}

	/**
	 * Get widget keywords.
	 *
	 * @return array Widget keywords.
	 */
	public function get_keywords() {
		return array( 'video', 'featured', 'rsfv', 'media', 'really simple featured video', 'featured video' );
	}

	/**
	 * Register widget controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->register_content_controls();
	}

	/**
	 * Register content tab controls.
	 *
	 * @return void
	 */
	private function register_content_controls() {
		$this->start_controls_section(
			'content_section',
			array(
				'label' => esc_html__( 'RSFV Video', 'rsfv' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		// ── Source selector.
		$this->add_control(
			'video_source',
			array(
				'label'   => esc_html__( 'Video Source', 'rsfv' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'current_post',
				'options' => array(
					'current_post' => esc_html__( 'Current Post', 'rsfv' ),
					'by_post_id'   => esc_html__( 'By Post ID', 'rsfv' ),
				),
			)
		);

		// ── By Post ID controls.
		$this->add_control(
			'post_id',
			array(
				'label'       => esc_html__( 'Post ID', 'rsfv' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => esc_html__( 'Enter the post ID', 'rsfv' ),
				'description' => esc_html__( 'Enter the Post ID whose featured video you want to display.', 'rsfv' ),
				'label_block' => true,
				'condition'   => array(
					'video_source' => 'by_post_id',
				),
			)
		);

		// ── Current Post – video type selector.
		$this->add_control(
			'video_type',
			array(
				'label'     => esc_html__( 'Video Type', 'rsfv' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'self',
				'options'   => array(
					'self'  => esc_html__( 'Self-Hosted', 'rsfv' ),
					'embed' => esc_html__( 'Embed', 'rsfv' ),
				),
				'condition' => array(
					'video_source' => 'current_post',
				),
			)
		);

		// ── Self-Hosted: video file from media library.
		$this->add_control(
			'self_video',
			array(
				'label'       => esc_html__( 'Choose Video', 'rsfv' ),
				'type'        => Controls_Manager::MEDIA,
				'media_types' => array( 'video' ),
				'default'     => array(
					'url' => '',
				),
				'condition'   => array(
					'video_source' => 'current_post',
					'video_type'   => 'self',
				),
			)
		);

		// ── Self-Hosted: poster image (shown only when a video is set).
		$this->add_control(
			'poster_image',
			array(
				'label'     => esc_html__( 'Poster Image', 'rsfv' ),
				'type'      => Controls_Manager::MEDIA,
				'default'   => array(
					'url' => '',
				),
				'condition' => array(
					'video_source'     => 'current_post',
					'video_type'       => 'self',
					'self_video[url]!' => '',
				),
			)
		);

		// ── Embed: video URL.
		$this->add_control(
			'embed_url',
			array(
				'label'       => esc_html__( 'Video URL', 'rsfv' ),
				'type'        => Controls_Manager::URL,
				'placeholder' => esc_html__( 'https://www.youtube.com/watch?v=...', 'rsfv' ),
				'description' => esc_html__( 'Paste a video URL from YouTube, Vimeo, or Dailymotion.', 'rsfv' ),
				'label_block' => true,
				'options'     => false,
				'condition'   => array(
					'video_source' => 'current_post',
					'video_type'   => 'embed',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Persist widget settings to the RSFV post meta keys so they stay in
	 * sync with the metabox on the CPT edit screen.
	 *
	 * Called by Elementor for every widget instance when the document is saved.
	 *
	 * @since 0.73.0
	 *
	 * @param array $settings The sanitised widget settings.
	 *
	 * @return array Settings (unchanged).
	 */
	public function on_save( array $settings ) {
		// Only sync meta when the source is "current_post".
		$video_source = $settings['video_source'] ?? 'current_post';

		if ( 'current_post' !== $video_source ) {
			return $settings;
		}

		$document = \Elementor\Plugin::$instance->documents->get_current();

		if ( ! $document ) {
			return $settings;
		}

		$post_id = $document->get_main_id();

		if ( ! $post_id ) {
			return $settings;
		}

		// Only sync meta for post types enabled in RSFV settings.
		$post_type     = get_post_type( $post_id );
		$enabled_types = get_post_types();

		if ( ! $post_type || ! in_array( $post_type, $enabled_types, true ) ) {
			return $settings;
		}

		$video_type = $settings['video_type'] ?? 'self';

		// ── Build what we would write.
		$new_source   = sanitize_text_field( $video_type );
		$new_video_id = '';
		$new_poster   = '';
		$new_embed    = '';

		if ( 'self' === $video_type ) {
			$new_video_id = sanitize_text_field( $settings['self_video']['id'] ?? '' );
			$new_poster   = sanitize_text_field( $settings['poster_image']['id'] ?? '' );
		} else {
			$new_embed = esc_url_raw( $settings['embed_url']['url'] ?? '' );
		}

		// ── Read current meta.
		$cur_source   = (string) get_post_meta( $post_id, RSFV_SOURCE_META_KEY, true );
		$cur_video_id = (string) get_post_meta( $post_id, RSFV_META_KEY, true );
		$cur_poster   = (string) get_post_meta( $post_id, RSFV_POSTER_META_KEY, true );
		$cur_embed    = (string) get_post_meta( $post_id, RSFV_EMBED_META_KEY, true );

		// Nothing changed – skip the write.  This prevents two widgets
		// on the same page from fighting: the one the user actually
		// edited will have new values while the untouched one will
		// still match the current meta and be silently skipped.
		if (
			$new_source === $cur_source &&
			(string) $new_video_id === $cur_video_id &&
			(string) $new_poster === $cur_poster &&
			$new_embed === $cur_embed
		) {
			return $settings;
		}

		// ── Write only when values differ.
		update_post_meta( $post_id, RSFV_SOURCE_META_KEY, $new_source );
		update_post_meta( $post_id, RSFV_META_KEY, $new_video_id );
		update_post_meta( $post_id, RSFV_POSTER_META_KEY, $new_poster );
		update_post_meta( $post_id, RSFV_EMBED_META_KEY, $new_embed );

		return $settings;
	}

	/**
	 * Render widget output on the frontend.
	 *
	 * Delegates to the existing RSFV shortcodes so the full rendering
	 * pipeline runs – including hover-autoplay, container data-attributes,
	 * and every filter other parts of the plugin hook into.
	 *
	 * @return void
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		$video_source = $settings['video_source'] ?? 'current_post';

		// ── By Post ID.
		if ( 'by_post_id' === $video_source ) {
			$post_id = trim( $settings['post_id'] ?? '' );

			if ( empty( $post_id ) ) {
				$this->render_placeholder( esc_html__( 'Please enter a Post ID.', 'rsfv' ) );
				return;
			}

			$output = do_shortcode( '[rsfv_by_postid post_id="' . esc_attr( $post_id ) . '"]' );
			if ( ! empty( $output ) ) {
				echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is generated by RSFV shortcodes and properly escaped there.
			} else {
				$this->render_placeholder( esc_html__( 'No featured video found for this Post ID.', 'rsfv' ) );
			}
			return;
		}

		// ── Current Post.
		// The [rsfv] shortcode reads directly from the post's RSFV meta
		// (synced by on_save / prefill) and runs through the full pipeline
		// including hover-autoplay support.
		$output = do_shortcode( '[rsfv]' );

		if ( ! empty( $output ) ) {
			echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is generated by RSFV shortcodes and properly escaped there.
		} else {
			$this->render_placeholder( esc_html__( 'No featured video configured for this post.', 'rsfv' ) );
		}
	}

	/**
	 * Get the post ID of the current document being edited / rendered.
	 *
	 * @return int|false
	 */
	private function get_current_post_id() {
		$document = \Elementor\Plugin::$instance->documents->get_current();

		return $document ? $document->get_main_id() : get_the_ID();
	}

	/**
	 * Render a placeholder message (visible only in the Elementor editor).
	 *
	 * @param string $message Placeholder text.
	 *
	 * @return void
	 */
	private function render_placeholder( $message ) {
		if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			printf(
				'<div class="rsfv-widget-placeholder" style="padding:20px;background:#f0f0f0;text-align:center;color:#666;border:1px dashed #ccc;">%s</div>',
				esc_html( $message )
			);
		}
	}
}
