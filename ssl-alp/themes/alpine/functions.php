<?php
/**
 * Theme functions and definitions.
 *
 * @package ssl-alp
 */

if ( ! function_exists( 'ssl_alp_setup' ) ) :
	/**
	 * Sets up theme defaults and registers support for various WordPress features.
	 *
	 * Note that this function is hooked into the after_setup_theme hook, which
	 * runs before the init hook. The init hook is too late for some features, such
	 * as indicating support for post thumbnails.
	 */
	function ssl_alp_setup() {
		global $content_width;
		global $ssl_alp_default_options;

		/**
		 * Set the content width based on the theme's design and stylesheet.
		 */
		if ( ! isset( $content_width ) ) {
			$content_width = 800;
		}

		/*
		 * Make theme available for translation.
		 */
		load_theme_textdomain( 'ssl-alp' );

		// Add default posts and comments RSS feed links to head.
		add_theme_support( 'automatic-feed-links' );

		/*
		* Enable support for Title Tag.
		*/
		add_theme_support( 'title-tag' );

		/*
		 * Enable support for custom logo.
		 */
		add_theme_support( 'custom-logo' );

		/*
		 * Enable support for partial refresh in Customizer widgets.
		 */
		add_theme_support( 'customize-selective-refresh-widgets' );

		/*
		 * Enable support for Post Thumbnails on posts and pages.
		 */
		add_theme_support( 'post-thumbnails' );

		register_nav_menus( array(
			'primary' => __( 'Primary Menu', 'ssl-alp' ),
			'footer'  => __( 'Footer Menu', 'ssl-alp' ),
		) );

		/*
		 * Switch default core markup for search form, comment form, and comments
		 * to output valid HTML5.
		 */
		add_theme_support( 'html5', array(
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
		) );

		/*
		 * Enable support for Post Formats.
		 */
		add_theme_support( 'post-formats', array(
			'aside',
			'image',
			'video',
			'audio',
			'quote',
			'status',
			'link',
			'chat',
			'gallery',
		) );

		// Setup the WordPress core custom background feature.
		add_theme_support( 'custom-background', apply_filters(
			'ssl_alp_custom_background_args', array(
				'default-color' => 'f0f3f5',
				'default-image' => '',
			) ) );

		$ssl_alp_default_options = ssl_alp_get_theme_option_defaults();
	}
endif;

add_action( 'after_setup_theme', 'ssl_alp_setup' );

/**
 * Register widget area.
 */
function ssl_alp_widgets_init() {
	register_sidebar( array(
		'name'          => __( 'Sidebar', 'ssl-alp' ),
		'id'            => 'sidebar-1',
		'description'   => __( 'Add widgets here to appear in your sidebar.', 'ssl-alp' ),
		'before_widget' => '<aside id="%1$s" class="widget clearfix %2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h3 class="widget-title">',
		'after_title'   => '</h3>',
	) );
}

add_action( 'widgets_init', 'ssl_alp_widgets_init' );

/**
 * Enqueue scripts and styles.
 */
function ssl_alp_scripts() {
	wp_enqueue_style( 'fontawesome', get_template_directory_uri().'/third-party/font-awesome/css/font-awesome.css', false, '4.7.0' );
	wp_enqueue_style( 'ssl-alp-style', get_stylesheet_uri(), array(), '2.3' );

	wp_enqueue_script( 'ssl-alp-navigation', get_template_directory_uri() . '/js/navigation.js', array(), '20120206', true );
	wp_enqueue_script( 'ssl-alp-custom', get_template_directory_uri() . '/js/custom.js', array( 'jquery' ), '1.8', true );

	wp_localize_script(
		'ssl-alp-custom',
		'SSL_ALP_Screen_Reader_Text',
		array(
			'expand'   => __( 'expand menu', 'ssl-alp' ),
			'collapse' => __( 'collapse menu', 'ssl-alp' ),
		)
	);

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}

add_action( 'wp_enqueue_scripts', 'ssl_alp_scripts' );

/**
 * Include helper.
 */
require get_template_directory() . '/inc/helper.php';

/**
 * Implement the Custom Header feature.
 */
require get_template_directory() . '/inc/custom-header.php';

/**
 * Custom template tags for this theme.
 */
require get_template_directory() . '/inc/template-tags.php';

/**
 * Custom theme functions.
 */
require get_template_directory() . '/inc/theme-functions.php';

/**
 * Custom theme custom.
 */
require get_template_directory() . '/inc/theme-custom.php';

/**
 * Custom functions that act independently of the theme templates.
 */
require get_template_directory() . '/inc/extras.php';

/**
 * Customizer additions.
 */
require get_template_directory() . '/inc/customizer.php';
