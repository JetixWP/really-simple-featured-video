<?php
/**
 * REST API Handler for Floating Videos.
 *
 * @package RSFV
 */

namespace RSFV\Featuresets\Floating_Video;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * REST_API Class for Floating Videos.
 */
class REST_API {
	/**
	 * API Namespace.
	 *
	 * @var string
	 */
	const NAMESPACE = 'rsfv/v1';

	/**
	 * Register REST API routes.
	 */
	public function register_routes() {
		// List all floating videos.
		register_rest_route(
			self::NAMESPACE,
			'/floating-videos',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => $this->get_create_update_args(),
				),
			)
		);

		// Single floating video operations.
		register_rest_route(
			self::NAMESPACE,
			'/floating-videos/(?P<id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'id' => array(
							'required'          => true,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => $this->get_create_update_args(),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'id' => array(
							'required'          => true,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);

		// Search pages/posts (all public CPTs) endpoint.
		register_rest_route(
			self::NAMESPACE,
			'/floating-videos/search-pages',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'search_pages' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'search' => array(
						'required'          => false,
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		// Search taxonomy terms endpoint.
		register_rest_route(
			self::NAMESPACE,
			'/floating-videos/search-terms',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'search_terms' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'search' => array(
						'required'          => false,
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	/**
	 * Get create/update arguments.
	 *
	 * @return array
	 */
	private function get_create_update_args() {
		return array(
			'title'             => array(
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'status'            => array(
				'required'          => false,
				'type'              => 'string',
				'default'           => 'publish',
				'sanitize_callback' => 'sanitize_key',
			),
			'video_source'      => array(
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_key',
			),
			'video_id'          => array(
				'required'          => false,
				'type'              => 'integer',
				'default'           => 0,
				'sanitize_callback' => 'absint',
			),
			'embed_url'         => array(
				'required'          => false,
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'esc_url_raw',
			),
			'display_type'      => array(
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_key',
			),
			'page_ids'          => array(
				'required' => false,
				'type'     => 'array',
				'default'  => array(),
			),
			'target_post_types' => array(
				'required' => false,
				'type'     => 'array',
				'default'  => array(),
			),
			'target_taxonomies' => array(
				'required' => false,
				'type'     => 'array',
				'default'  => array(),
			),
		);
	}

	/**
	 * Check permission.
	 *
	 * @return bool|WP_Error
	 */
	public function check_permission() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to manage floating videos.', 'rsfv' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Get all floating videos.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_items( WP_REST_Request $request ) {
		$posts = get_posts(
			array(
				'post_type'      => Init::POST_TYPE,
				'post_status'    => array( 'publish', 'draft' ),
				'posts_per_page' => 100,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		$items = array();
		foreach ( $posts as $post ) {
			$items[] = $this->prepare_item( $post );
		}

		return new WP_REST_Response( $items, 200 );
	}

	/**
	 * Get a single floating video.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_item( WP_REST_Request $request ) {
		$post = get_post( $request->get_param( 'id' ) );

		if ( ! $post || Init::POST_TYPE !== $post->post_type ) {
			return new WP_Error(
				'not_found',
				__( 'Floating video not found.', 'rsfv' ),
				array( 'status' => 404 )
			);
		}

		return new WP_REST_Response( $this->prepare_item( $post ), 200 );
	}

	/**
	 * Create a new floating video.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_item( WP_REST_Request $request ) {
		$post_id = wp_insert_post(
			array(
				'post_type'   => Init::POST_TYPE,
				'post_title'  => $request->get_param( 'title' ),
				'post_status' => $request->get_param( 'status' ) ?: 'publish',
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return new WP_Error(
				'create_failed',
				$post_id->get_error_message(),
				array( 'status' => 500 )
			);
		}

		$this->save_meta( $post_id, $request );

		$post = get_post( $post_id );

		return new WP_REST_Response( $this->prepare_item( $post ), 201 );
	}

	/**
	 * Update a floating video.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_item( WP_REST_Request $request ) {
		$post_id = $request->get_param( 'id' );
		$post    = get_post( $post_id );

		if ( ! $post || Init::POST_TYPE !== $post->post_type ) {
			return new WP_Error(
				'not_found',
				__( 'Floating video not found.', 'rsfv' ),
				array( 'status' => 404 )
			);
		}

		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_title'  => $request->get_param( 'title' ),
				'post_status' => $request->get_param( 'status' ) ?: $post->post_status,
			)
		);

		$this->save_meta( $post_id, $request );

		$post = get_post( $post_id );

		return new WP_REST_Response( $this->prepare_item( $post ), 200 );
	}

	/**
	 * Delete a floating video.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_item( WP_REST_Request $request ) {
		$post_id = $request->get_param( 'id' );
		$post    = get_post( $post_id );

		if ( ! $post || Init::POST_TYPE !== $post->post_type ) {
			return new WP_Error(
				'not_found',
				__( 'Floating video not found.', 'rsfv' ),
				array( 'status' => 404 )
			);
		}

		wp_delete_post( $post_id, true );

		return new WP_REST_Response(
			array(
				'success' => true,
				'id'      => $post_id,
			),
			200
		);
	}

	/**
	 * Search pages/posts for display condition selection.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function search_pages( WP_REST_Request $request ) {
		$search = $request->get_param( 'search' );

		// Get all public post types.
		$public_types = get_post_types( array( 'public' => true ), 'names' );
		unset( $public_types['attachment'] );

		$args = array(
			'post_type'      => array_values( $public_types ),
			'post_status'    => 'publish',
			'posts_per_page' => 20,
			'orderby'        => 'title',
			'order'          => 'ASC',
		);

		if ( ! empty( $search ) ) {
			$args['s'] = $search;
		}

		$query   = new \WP_Query( $args );
		$results = array();

		foreach ( $query->posts as $post ) {
			$post_type_obj = get_post_type_object( $post->post_type );
			$type_label    = $post_type_obj ? $post_type_obj->labels->singular_name : $post->post_type;
			$results[]     = array(
				'value' => $post->ID,
				'label' => sprintf( '%s (%s)', $post->post_title, $type_label ),
			);
		}

		return new WP_REST_Response( $results, 200 );
	}

	/**
	 * Search taxonomy terms for display condition selection.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function search_terms( WP_REST_Request $request ) {
		$search     = $request->get_param( 'search' );
		$taxonomies = get_taxonomies( array( 'public' => true ), 'names' );

		$args = array(
			'taxonomy'   => array_values( $taxonomies ),
			'hide_empty' => false,
			'number'     => 30,
			'orderby'    => 'name',
			'order'      => 'ASC',
		);

		if ( ! empty( $search ) ) {
			$args['search'] = $search;
		}

		$terms   = get_terms( $args );
		$results = array();

		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$tax_obj   = get_taxonomy( $term->taxonomy );
				$tax_label = $tax_obj ? $tax_obj->labels->singular_name : $term->taxonomy;
				$results[] = array(
					'value'    => $term->term_id,
					'label'    => sprintf( '%s (%s)', $term->name, $tax_label ),
					'taxonomy' => $term->taxonomy,
				);
			}
		}

		return new WP_REST_Response( $results, 200 );
	}

	/**
	 * Save floating video metadata.
	 *
	 * @param int             $post_id Post ID.
	 * @param WP_REST_Request $request Request object.
	 */
	private function save_meta( $post_id, WP_REST_Request $request ) {
		$video_source = sanitize_key( $request->get_param( 'video_source' ) );
		update_post_meta( $post_id, '_rsfv_fv_video_source', $video_source );

		if ( 'self' === $video_source ) {
			update_post_meta( $post_id, '_rsfv_fv_video_id', absint( $request->get_param( 'video_id' ) ) );
			delete_post_meta( $post_id, '_rsfv_fv_embed_url' );
		} elseif ( 'embed' === $video_source ) {
			update_post_meta( $post_id, '_rsfv_fv_embed_url', esc_url_raw( $request->get_param( 'embed_url' ) ) );
			delete_post_meta( $post_id, '_rsfv_fv_video_id' );
		}

		// Display conditions.
		$display_type = sanitize_key( $request->get_param( 'display_type' ) );
		update_post_meta( $post_id, '_rsfv_fv_display_type', $display_type );

		switch ( $display_type ) {
			case 'specific_pages':
				$page_ids = $request->get_param( 'page_ids' );
				if ( is_array( $page_ids ) ) {
					update_post_meta( $post_id, '_rsfv_fv_page_ids', array_map( 'absint', $page_ids ) );
				}
				break;

			case 'post_types':
				$target_post_types = $request->get_param( 'target_post_types' );
				if ( is_array( $target_post_types ) ) {
					update_post_meta( $post_id, '_rsfv_fv_target_post_types', array_map( 'sanitize_key', $target_post_types ) );
				}
				break;

			case 'taxonomies':
				$target_taxonomies = $request->get_param( 'target_taxonomies' );
				if ( is_array( $target_taxonomies ) ) {
					$sanitized = array();
					foreach ( $target_taxonomies as $tax_data ) {
						$sanitized[] = array(
							'taxonomy' => sanitize_key( $tax_data['taxonomy'] ?? '' ),
							'terms'    => array_map( 'absint', $tax_data['terms'] ?? array() ),
						);
					}
					update_post_meta( $post_id, '_rsfv_fv_target_taxonomies', $sanitized );
				}
				break;
		}
	}

	/**
	 * Prepare a floating video post for the response.
	 *
	 * @param \WP_Post $post Post object.
	 * @return array
	 */
	private function prepare_item( $post ) {
		$video_source = get_post_meta( $post->ID, '_rsfv_fv_video_source', true ) ?: 'self';
		$video_id     = get_post_meta( $post->ID, '_rsfv_fv_video_id', true );
		$embed_url    = get_post_meta( $post->ID, '_rsfv_fv_embed_url', true );
		$display_type = get_post_meta( $post->ID, '_rsfv_fv_display_type', true ) ?: 'sitewide';

		$video_url = '';
		if ( $video_id ) {
			$video_url = wp_get_attachment_url( $video_id );
		}

		return array(
			'id'                => $post->ID,
			'title'             => html_entity_decode( $post->post_title, ENT_QUOTES, 'UTF-8' ),
			'status'            => $post->post_status,
			'date'              => $post->post_date,
			'video_source'      => $video_source,
			'video_id'          => $video_id ? absint( $video_id ) : 0,
			'video_url'         => $video_url ? esc_url_raw( $video_url ) : '',
			'embed_url'         => $embed_url ? esc_url_raw( $embed_url ) : '',
			'display_type'      => $display_type,
			'page_ids'          => get_post_meta( $post->ID, '_rsfv_fv_page_ids', true ) ?: array(),
			'target_post_types' => get_post_meta( $post->ID, '_rsfv_fv_target_post_types', true ) ?: array(),
			'target_taxonomies' => get_post_meta( $post->ID, '_rsfv_fv_target_taxonomies', true ) ?: array(),
		);
	}
}
