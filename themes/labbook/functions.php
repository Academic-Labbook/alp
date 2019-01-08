<?php
/**
 * Labbook functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Labbook
 */

define( 'LABBOOK_VERSION', '1.0.0' );

if ( ! function_exists( 'labbook_setup' ) ) :
	/**
	 * Sets up theme defaults and registers support for various WordPress features.
	 *
	 * Note that this function is hooked into the after_setup_theme hook, which
	 * runs before the init hook. The init hook is too late for some features, such
	 * as indicating support for post thumbnails.
	 */
	function labbook_setup() {
		global $labbook_default_options;

		/*
		 * Make theme available for translation.
		 * Translations can be filed in the /languages/ directory.
		 */
		load_theme_textdomain( 'labbook', get_template_directory() . '/languages' );

		// Add default posts and comments RSS feed links to head.
		add_theme_support( 'automatic-feed-links' );

		/*
		 * Let WordPress manage the document title.
		 * By adding theme support, we declare that this theme does not use a
		 * hard-coded <title> tag in the document head, and expect WordPress to
		 * provide it for us.
		 */
		add_theme_support( 'title-tag' );

		// This theme uses wp_nav_menu() in two locations.
		register_nav_menus( array(
			'site-menu' => esc_html__( 'Primary', 'labbook' ),
			'network-menu' => esc_html__( 'Network', 'labbook' )
		) );

		/*
		 * Switch default core markup for search form, comment form, and comments
		 * to output valid HTML5.
		 */
		add_theme_support( 'html5', array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
		) );

		/*
		 * Enable support for Post Formats.
		 */
		add_theme_support( 'post-formats', array(
			'status'
		) );

		// Set up the WordPress core custom background feature.
		add_theme_support( 'custom-background', apply_filters( 'labbook_custom_background_args', array(
			'default-color' => 'ffffff',
			'default-image' => '',
		) ) );

		// Add theme support for selective refresh for widgets.
		add_theme_support( 'customize-selective-refresh-widgets' );

		/**
		 * Add support for core custom logo.
		 *
		 * @link https://codex.wordpress.org/Theme_Logo
		 */
		add_theme_support( 'custom-logo', array(
			'height'      => 155,
			'width'       => 700,
			'flex-width'  => false,
			'flex-height' => false
		) );

		// Get default theme options.
		$labbook_default_options = labbook_get_theme_option_defaults();
	}
endif;
add_action( 'after_setup_theme', 'labbook_setup' );

/**
 * Set the content width in pixels, based on the theme's design and stylesheet.
 *
 * Priority 0 to make it available to lower priority callbacks.
 *
 * @global int $content_width
 */
function labbook_content_width() {
	// This variable is intended to be overruled from themes.
	// Open WPCS issue: {@link https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards/issues/1043}.
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
	$GLOBALS['content_width'] = apply_filters( 'labbook_content_width', 640 );
}
add_action( 'after_setup_theme', 'labbook_content_width', 0 );

/**
 * Register widget area.
 *
 * @link https://developer.wordpress.org/themes/functionality/sidebars/#registering-a-sidebar
 */
function labbook_widgets_init() {
	register_sidebar( array(
		'name'          => esc_html__( 'Sidebar', 'labbook' ),
		'id'            => 'sidebar-1',
		'description'   => esc_html__( 'Add widgets here.', 'labbook' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h2 class="widget-title">',
		'after_title'   => '</h2>',
	) );
}
add_action( 'widgets_init', 'labbook_widgets_init' );

/**
 * Enqueue scripts and styles.
 */
function labbook_scripts() {
	wp_enqueue_style(
		'fontawesome',
		get_template_directory_uri() . '/vendor/font-awesome/css/font-awesome.css',
		array(),
		LABBOOK_VERSION
	);

	wp_enqueue_style(
		'labbook-style',
		get_stylesheet_uri(),
		array(),
		LABBOOK_VERSION
	);

	wp_enqueue_script(
		'labbook-navigation',
		get_template_directory_uri() . '/js/navigation.js',
		array(),
		LABBOOK_VERSION,
		true
	);

	wp_enqueue_script(
		'labbook-skip-link-focus-fix',
		get_template_directory_uri() . '/js/skip-link-focus-fix.js',
		array(),
		LABBOOK_VERSION,
		true
	);

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'labbook_scripts' );

if ( ! function_exists( 'labbook_the_content_with_toc' ) ) :
	/**
	 * Add table of contents alongside post.
	 */
	function labbook_the_content_with_toc( $content ) {
		$post = get_post();

		if ( ! labbook_get_option( 'show_page_table_of_contents' ) ) {
			return $content;
		}

		if ( is_null( $post ) ) {
			return $content;
		}

		if ( ! is_page( $post ) ) {
			return $content;
		}

		// get contents hierarchy
		$content = labbook_generate_post_contents( $content, $hierarchy );

		if ( is_null( $hierarchy ) || ! $hierarchy->count() ) {
			// table of contents was not generated or has no entries
			return $content;
		}

		?>
		<div class="entry-toc entry-toc-<?php the_ID(); ?>">
			<h3 class="entry-toc-title"><?php _e( 'Contents', 'labbook' ) ?></h3>
			<?php labbook_the_toc( $hierarchy, labbook_get_option( 'table_of_contents_max_depth' ) ); ?>
		</div>
		<?php

		return $content;
	}
endif;
add_filter( 'the_content', 'labbook_the_content_with_toc' );

/**
 * Page table of contents generator.
 */
require get_template_directory() . '/inc/toc.php';

/**
 * Functions which enhance the theme by hooking into WordPress.
 */
require get_template_directory() . '/inc/template-functions.php';

/**
 * Custom template tags for this theme.
 */
require get_template_directory() . '/inc/template-tags.php';

/**
 * Customizer additions.
 */
require get_template_directory() . '/inc/customizer.php';

/**
 * Admin functions (for is_plugin_active)
 */
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
