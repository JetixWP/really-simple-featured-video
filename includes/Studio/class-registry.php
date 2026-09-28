<?php
/**
 * Video Studio registry: templates, presets, fonts and runtime extensions.
 *
 * @package RSFV
 */

namespace RSFV\Studio;

defined( 'ABSPATH' ) || exit;

/**
 * Class Registry
 */
class Registry {
	/**
	 * Variable types a template can declare. Matches the HyperFrames variable
	 * types, plus "text" (multi-line string) and "images" (list of images).
	 *
	 * @var string[]
	 */
	const VAR_TYPES = array( 'string', 'text', 'number', 'color', 'boolean', 'enum', 'font', 'image', 'images' );

	/**
	 * Common colour and font variables.
	 *
	 * @param array $defaults Default values keyed by variable id.
	 *
	 * @return array
	 */
	public static function style_vars( $defaults ) {
		return array(
			array(
				'id'      => 'background',
				'type'    => 'color',
				'label'   => __( 'Background', 'rsfv' ),
				'default' => $defaults['background'],
				'brand'   => 'background',
				'group'   => 'style',
			),
			array(
				'id'      => 'accent',
				'type'    => 'color',
				'label'   => __( 'Accent', 'rsfv' ),
				'default' => $defaults['accent'],
				'brand'   => 'accent',
				'group'   => 'style',
			),
			array(
				'id'      => 'text_color',
				'type'    => 'color',
				'label'   => __( 'Text', 'rsfv' ),
				'default' => $defaults['text_color'],
				'brand'   => 'text',
				'group'   => 'style',
			),
			array(
				'id'      => 'font',
				'type'    => 'font',
				'label'   => __( 'Font', 'rsfv' ),
				'default' => $defaults['font'],
				'brand'   => 'font',
				'group'   => 'style',
			),
		);
	}

	/**
	 * Built-in templates.
	 *
	 * @return array
	 */
	protected static function core_templates() {
		$base = RSFV_PLUGIN_URL . 'assets/studio/templates/';

		return array(
			array(
				'id'          => 'text-reveal',
				'title'       => __( 'Text reveal', 'rsfv' ),
				'description' => __( 'A big headline that rises in word by word, with a short line under it.', 'rsfv' ),
				'version'     => '1.0.0',
				'script'      => $base . 'text-reveal.js',
				'duration'    => 7,
				'category'    => 'text',
				'vars'        => array_merge(
					array(
						array(
							'id'        => 'headline',
							'type'      => 'string',
							'label'     => __( 'Headline', 'rsfv' ),
							'default'   => __( 'Your headline here', 'rsfv' ),
							'autofill'  => 'title',
							'maxLength' => 120,
						),
						array(
							'id'        => 'subline',
							'type'      => 'string',
							'label'     => __( 'Line under it', 'rsfv' ),
							'default'   => '',
							'autofill'  => 'site_name',
							'maxLength' => 140,
						),
					),
					self::style_vars(
						array(
							'background' => '#101828',
							'accent'     => '#f79009',
							'text_color' => '#ffffff',
							'font'       => 'Manrope',
						)
					)
				),
			),
			array(
				'id'          => 'image-slideshow',
				'title'       => __( 'Image slideshow', 'rsfv' ),
				'description' => __( 'Photos with a slow zoom and pan, moving into each other, with a title on top.', 'rsfv' ),
				'version'     => '1.0.0',
				'script'      => $base . 'image-slideshow.js',
				'duration'    => 9,
				'category'    => 'images',
				'vars'        => array_merge(
					array(
						array(
							'id'       => 'images',
							'type'     => 'images',
							'label'    => __( 'Images', 'rsfv' ),
							'default'  => array(),
							'autofill' => 'gallery',
							'max'      => 8,
						),
						array(
							'id'        => 'title',
							'type'      => 'string',
							'label'     => __( 'Title', 'rsfv' ),
							'default'   => '',
							'autofill'  => 'title',
							'maxLength' => 100,
						),
						array(
							'id'        => 'caption',
							'type'      => 'string',
							'label'     => __( 'Caption', 'rsfv' ),
							'default'   => '',
							'autofill'  => 'price',
							'maxLength' => 120,
						),
						array(
							'id'      => 'slide_duration',
							'type'    => 'number',
							'label'   => __( 'Seconds per image', 'rsfv' ),
							'default' => 3,
							'min'     => 2,
							'max'     => 8,
							'step'    => 0.5,
						),
						array(
							'id'      => 'fit',
							'type'    => 'enum',
							'label'   => __( 'Photo fit', 'rsfv' ),
							'default' => 'cover',
							'options' => array(
								array(
									'value' => 'cover',
									'label' => __( 'Fill the frame', 'rsfv' ),
								),
								array(
									'value' => 'contain',
									'label' => __( 'Show the whole photo', 'rsfv' ),
								),
							),
						),
						array(
							'id'      => 'transition',
							'type'    => 'enum',
							'label'   => __( 'Transition', 'rsfv' ),
							'default' => 'fade',
							'options' => array(
								array(
									'value' => 'fade',
									'label' => __( 'Crossfade', 'rsfv' ),
								),
								array(
									'value' => 'slide',
									'label' => __( 'Slide', 'rsfv' ),
								),
								array(
									'value' => 'zoom',
									'label' => __( 'Zoom', 'rsfv' ),
								),
							),
						),
						array(
							'id'      => 'overlay',
							'type'    => 'number',
							'label'   => __( 'Shade behind text (%)', 'rsfv' ),
							'default' => 55,
							'min'     => 0,
							'max'     => 90,
							'step'    => 5,
							'group'   => 'style',
						),
					),
					array_values(
						array_filter(
							self::style_vars(
								array(
									'background' => '#000000',
									'accent'     => '#f79009',
									'text_color' => '#ffffff',
									'font'       => 'Manrope',
								)
							),
							function ( $v ) {
								return 'background' !== $v['id'];
							}
						)
					)
				),
			),
			array(
				'id'          => 'product-card',
				'title'       => __( 'Product card', 'rsfv' ),
				'description' => __( 'Product photo, name, price and a call to action. Fills itself from WooCommerce.', 'rsfv' ),
				'version'     => '1.0.0',
				'script'      => $base . 'product-card.js',
				'duration'    => 8,
				'category'    => 'product',
				'vars'        => array_merge(
					array(
						array(
							'id'       => 'image',
							'type'     => 'image',
							'label'    => __( 'Product image', 'rsfv' ),
							'default'  => 0,
							'autofill' => 'featured_image',
						),
						array(
							'id'        => 'kicker',
							'type'      => 'string',
							'label'     => __( 'Small label', 'rsfv' ),
							'default'   => __( 'New arrival', 'rsfv' ),
							'autofill'  => 'category',
							'maxLength' => 40,
						),
						array(
							'id'        => 'title',
							'type'      => 'string',
							'label'     => __( 'Name', 'rsfv' ),
							'default'   => __( 'Product name', 'rsfv' ),
							'autofill'  => 'title',
							'maxLength' => 80,
						),
						array(
							'id'        => 'price',
							'type'      => 'string',
							'label'     => __( 'Price', 'rsfv' ),
							'default'   => '',
							'autofill'  => 'price',
							'maxLength' => 30,
						),
						array(
							'id'        => 'old_price',
							'type'      => 'string',
							'label'     => __( 'Old price (crossed out)', 'rsfv' ),
							'default'   => '',
							'autofill'  => 'regular_price',
							'maxLength' => 30,
						),
						array(
							'id'        => 'badge',
							'type'      => 'string',
							'label'     => __( 'Badge', 'rsfv' ),
							'default'   => '',
							'autofill'  => 'sale_badge',
							'maxLength' => 12,
						),
						array(
							'id'        => 'cta',
							'type'      => 'string',
							'label'     => __( 'Button text', 'rsfv' ),
							'default'   => __( 'Shop now', 'rsfv' ),
							'maxLength' => 30,
						),
					),
					self::style_vars(
						array(
							'background' => '#f4ede4',
							'accent'     => '#e04f39',
							'text_color' => '#1d1a17',
							'font'       => 'Manrope',
						)
					)
				),
			),
			array(
				'id'          => 'quote',
				'title'       => __( 'Quote', 'rsfv' ),
				'description' => __( 'A quote that fades in word by word, then the author and their role.', 'rsfv' ),
				'version'     => '1.0.0',
				'script'      => $base . 'quote.js',
				'duration'    => 9,
				'category'    => 'text',
				'vars'        => array_merge(
					array(
						array(
							'id'        => 'quote',
							'type'      => 'text',
							'label'     => __( 'Quote', 'rsfv' ),
							'default'   => __( 'Simple things, done really well.', 'rsfv' ),
							'autofill'  => 'excerpt',
							'maxLength' => 280,
						),
						array(
							'id'        => 'author',
							'type'      => 'string',
							'label'     => __( 'Author', 'rsfv' ),
							'default'   => '',
							'autofill'  => 'author',
							'maxLength' => 60,
						),
						array(
							'id'        => 'role',
							'type'      => 'string',
							'label'     => __( 'Role or company', 'rsfv' ),
							'default'   => '',
							'autofill'  => 'site_name',
							'maxLength' => 60,
						),
					),
					self::style_vars(
						array(
							'background' => '#1f1b2e',
							'accent'     => '#a78bfa',
							'text_color' => '#ffffff',
							'font'       => 'Vollkorn',
						)
					)
				),
			),
		);
	}

	/**
	 * All templates, including ones added by other plugins.
	 *
	 * @return array Templates keyed by id.
	 */
	public static function get_templates() {
		/**
		 * Filter the Video Studio templates.
		 *
		 * Each template: id, title, description, version, script (URL of a
		 * script that calls RSFVStudio.registerTemplate()), duration, category,
		 * vars (list of variables, see Registry::VAR_TYPES).
		 *
		 * @since 0.91.0
		 *
		 * @param array $templates Templates.
		 */
		$templates = apply_filters( 'rsfv_studio_templates', self::core_templates() );

		$valid = array();
		foreach ( (array) $templates as $template ) {
			if ( empty( $template['id'] ) || empty( $template['script'] ) ) {
				continue;
			}
			$template['id']      = sanitize_key( $template['id'] );
			$template['version'] = isset( $template['version'] ) ? (string) $template['version'] : '1.0.0';
			$template['vars']    = isset( $template['vars'] ) ? array_values( (array) $template['vars'] ) : array();

			$valid[ $template['id'] ] = $template;
		}

		return $valid;
	}

	/**
	 * Output sizes.
	 *
	 * @return array Presets keyed by id.
	 */
	public static function get_presets() {
		$presets = array(
			'landscape-1080' => array(
				'label'  => __( '16:9, 1080p (Full HD)', 'rsfv' ),
				'width'  => 1920,
				'height' => 1080,
				'fps'    => 30,
			),
			'landscape-720'  => array(
				'label'  => __( '16:9, 720p (HD)', 'rsfv' ),
				'width'  => 1280,
				'height' => 720,
				'fps'    => 30,
			),
		);

		/**
		 * Filter the Video Studio output sizes.
		 *
		 * Each preset: label, width, height (even numbers), fps.
		 *
		 * @since 0.91.0
		 *
		 * @param array $presets Presets keyed by id.
		 */
		$presets = apply_filters( 'rsfv_studio_presets', $presets );

		$valid = array();
		foreach ( (array) $presets as $id => $preset ) {
			$width  = isset( $preset['width'] ) ? absint( $preset['width'] ) : 0;
			$height = isset( $preset['height'] ) ? absint( $preset['height'] ) : 0;
			if ( $width < 16 || $height < 16 || $width > 4096 || $height > 4096 ) {
				continue;
			}
			$valid[ sanitize_key( $id ) ] = array(
				'label'  => isset( $preset['label'] ) ? (string) $preset['label'] : $width . 'x' . $height,
				'width'  => $width - ( $width % 2 ),
				'height' => $height - ( $height % 2 ),
				'fps'    => isset( $preset['fps'] ) ? min( 60, max( 1, absint( $preset['fps'] ) ) ) : 30,
			);
		}

		return $valid;
	}

	/**
	 * Fonts templates can use.
	 *
	 * @return array Fonts keyed by family.
	 */
	public static function get_fonts() {
		$base  = RSFV_PLUGIN_URL . 'assets/studio/fonts/';
		$fonts = array(
			'Manrope'        => array(
				'label'  => 'Manrope',
				'url'    => $base . 'manrope.woff2',
				'weight' => '200 800',
			),
			'Ysabeau Office' => array(
				'label'  => 'Ysabeau Office',
				'url'    => $base . 'ysabeau-office.woff2',
				'weight' => '1 1000',
			),
			'Roboto Slab'    => array(
				'label'  => 'Roboto Slab',
				'url'    => $base . 'roboto-slab.woff2',
				'weight' => '100 900',
			),
			'Vollkorn'       => array(
				'label'  => 'Vollkorn',
				'url'    => $base . 'vollkorn.woff2',
				'weight' => '400 900',
			),
		);

		/**
		 * Filter the Video Studio fonts (WOFF2 files on this site).
		 *
		 * @since 0.91.0
		 *
		 * @param array $fonts Fonts keyed by family: label, url, weight, style.
		 */
		return apply_filters( 'rsfv_studio_fonts', $fonts );
	}

	/**
	 * Extra scripts loaded into the render sandbox (for example an audio
	 * track). Each script calls RSFVStudio.registerExtension().
	 *
	 * @return string[] Script URLs.
	 */
	public static function get_runtime_extensions() {
		/**
		 * Filter the Video Studio runtime extensions.
		 *
		 * @since 0.91.0
		 *
		 * @param string[] $scripts Script URLs.
		 */
		return array_values( array_filter( (array) apply_filters( 'rsfv_studio_runtime_extensions', array() ) ) );
	}
}
