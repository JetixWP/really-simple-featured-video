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
	 * Optional logo (PRO fills it from the brand kit).
	 *
	 * @return array
	 */
	public static function logo_var() {
		return array(
			'id'      => 'logo',
			'type'    => 'image',
			'label'   => __( 'Logo', 'rsfv' ),
			'default' => 0,
			'brand'   => 'logo',
			'group'   => 'style',
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
			array(
				'id'          => 'news-headline',
				'title'       => __( 'News headline', 'rsfv' ),
				'description' => __( 'The post photo with a slow zoom, a category tag, the headline line by line, a short summary and a byline.', 'rsfv' ),
				'version'     => '1.0.0',
				'script'      => $base . 'news-headline.js',
				'duration'    => 8,
				'category'    => 'blog',
				'vars'        => array_merge(
					array(
						array(
							'id'       => 'image',
							'type'     => 'image',
							'label'    => __( 'Photo', 'rsfv' ),
							'default'  => 0,
							'autofill' => 'featured_image',
						),
						array(
							'id'        => 'category',
							'type'      => 'string',
							'label'     => __( 'Tag', 'rsfv' ),
							'default'   => __( 'News', 'rsfv' ),
							'autofill'  => 'category',
							'maxLength' => 30,
						),
						array(
							'id'        => 'title',
							'type'      => 'string',
							'label'     => __( 'Headline', 'rsfv' ),
							'default'   => __( 'City opens its first car-free street to walkers and bikes', 'rsfv' ),
							'autofill'  => 'title',
							'maxLength' => 100,
						),
						array(
							'id'        => 'excerpt',
							'type'      => 'text',
							'label'     => __( 'Short summary', 'rsfv' ),
							'default'   => __( 'The new route links the old town with the river park, and early numbers show twice as many people on foot.', 'rsfv' ),
							'autofill'  => 'excerpt',
							'maxLength' => 200,
						),
						array(
							'id'        => 'author',
							'type'      => 'string',
							'label'     => __( 'Author', 'rsfv' ),
							'default'   => __( 'Jordan Lee', 'rsfv' ),
							'autofill'  => 'author',
							'maxLength' => 50,
						),
						array(
							'id'        => 'site_name',
							'type'      => 'string',
							'label'     => __( 'Site name', 'rsfv' ),
							'default'   => __( 'The Daily Post', 'rsfv' ),
							'autofill'  => 'site_name',
							'maxLength' => 50,
						),
					),
					self::style_vars(
						array(
							'background' => '#111111',
							'accent'     => '#e11d48',
							'text_color' => '#ffffff',
							'font'       => 'Manrope',
						)
					),
					array( self::logo_var() )
				),
			),
			array(
				'id'          => 'testimonial',
				'title'       => __( 'Testimonial', 'rsfv' ),
				'description' => __( 'Star rating, a customer review, their photo and name.', 'rsfv' ),
				'version'     => '1.0.0',
				'script'      => $base . 'testimonial.js',
				'duration'    => 9,
				'category'    => 'text',
				'vars'        => array_merge(
					array(
						array(
							'id'      => 'photo',
							'type'    => 'image',
							'label'   => __( 'Customer photo', 'rsfv' ),
							'default' => 0,
						),
						array(
							'id'      => 'rating',
							'type'    => 'enum',
							'label'   => __( 'Stars', 'rsfv' ),
							'default' => '5',
							'options' => array(
								array(
									'value' => '5',
									'label' => '5',
								),
								array(
									'value' => '4',
									'label' => '4',
								),
								array(
									'value' => '3',
									'label' => '3',
								),
								array(
									'value' => '0',
									'label' => __( 'No stars', 'rsfv' ),
								),
							),
						),
						array(
							'id'        => 'review',
							'type'      => 'text',
							'label'     => __( 'Review', 'rsfv' ),
							'default'   => __( 'Arrived fast, fits perfectly and feels great. I have already ordered a second one.', 'rsfv' ),
							'autofill'  => 'excerpt',
							'maxLength' => 280,
						),
						array(
							'id'        => 'name',
							'type'      => 'string',
							'label'     => __( 'Name', 'rsfv' ),
							'default'   => __( 'Alex Morgan', 'rsfv' ),
							'maxLength' => 50,
						),
						array(
							'id'        => 'detail',
							'type'      => 'string',
							'label'     => __( 'Detail', 'rsfv' ),
							'default'   => __( 'Verified buyer', 'rsfv' ),
							'maxLength' => 60,
						),
						array(
							'id'        => 'product',
							'type'      => 'string',
							'label'     => __( 'Product', 'rsfv' ),
							'default'   => '',
							'autofill'  => 'title',
							'maxLength' => 80,
						),
					),
					self::style_vars(
						array(
							'background' => '#fdf2f8',
							'accent'     => '#db2777',
							'text_color' => '#1f2937',
							'font'       => 'Manrope',
						)
					),
					array( self::logo_var() )
				),
			),
			array(
				'id'          => 'kinetic-type',
				'title'       => __( 'Kinetic type', 'rsfv' ),
				'description' => __( 'Three short lines that slam, zoom and type onto the screen.', 'rsfv' ),
				'version'     => '1.0.0',
				'script'      => $base . 'kinetic-type.js',
				'duration'    => 6,
				'category'    => 'text',
				'vars'        => array_merge(
					array(
						array(
							'id'        => 'line1',
							'type'      => 'string',
							'label'     => __( 'Line 1', 'rsfv' ),
							'default'   => __( 'Make it', 'rsfv' ),
							'maxLength' => 24,
						),
						array(
							'id'        => 'line2',
							'type'      => 'string',
							'label'     => __( 'Line 2', 'rsfv' ),
							'default'   => __( 'move.', 'rsfv' ),
							'maxLength' => 16,
						),
						array(
							'id'        => 'line3',
							'type'      => 'string',
							'label'     => __( 'Line 3', 'rsfv' ),
							'default'   => __( 'Make it yours.', 'rsfv' ),
							'autofill'  => 'site_name',
							'maxLength' => 40,
						),
					),
					self::style_vars(
						array(
							'background' => '#fafafa',
							'accent'     => '#ef4444',
							'text_color' => '#0a0a0a',
							'font'       => 'Manrope',
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
		 * @since 1.0.0
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
			'square-1080'    => array(
				'label'  => __( '1:1, 1080 x 1080 (square)', 'rsfv' ),
				'width'  => 1080,
				'height' => 1080,
				'fps'    => 30,
			),
		);

		/**
		 * Filter the Video Studio output sizes.
		 *
		 * Each preset: label, width, height (even numbers), fps.
		 *
		 * @since 1.0.0
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
		 * @since 1.0.0
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
		 * @since 1.0.0
		 *
		 * @param string[] $scripts Script URLs.
		 */
		return array_values( array_filter( (array) apply_filters( 'rsfv_studio_runtime_extensions', array() ) ) );
	}
}
