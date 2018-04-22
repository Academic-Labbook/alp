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
		add_theme_support(
			'custom-logo',
			array(
				'height'		=>	150,
				'width'			=>	600,
				'flex-width'	=>	true,
				'flex-height'	=>	true
			)
		);

		/*
		 * Enable support for partial refresh in Customizer widgets.
		 */
		add_theme_support( 'customize-selective-refresh-widgets' );

		register_nav_menus( array(
			'primary' => __( 'Primary Menu', 'ssl-alp' ),
			'footer'  => __( 'Footer Menu', 'ssl-alp' ),
		) );

		/*
		 * Switch default HTML5 output.
		 */
		add_theme_support( 'html5', array(
			'comment-list',
			'comment-form',
			'search-form',
			'gallery',
			'caption',
		) );

		/*
		 * Enable support for Post Formats.
		 */
		add_theme_support( 'post-formats', array(
			'status'
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

if ( ! function_exists( 'ssl_alp_update_sidebar_widgets' ) ) :
	/**
	 * Add widgets to theme sidebars on switch.
	 */
	function ssl_alp_update_sidebar_widgets() {
		// standard sidebar
		ssl_alp_set_sidebar_widgets(
			'ssl-alp-sidebar-standard',
			array(
				'search'			=> array(),
				'recent-posts'	 	=> array(),
				'recent-comments'	=> array(),
				'ssl-alp-revisions'	=> array(),
				'ssl-alp-users'		=> array(),
				'categories'		=> array(
					'count'			=>	true,
					'hierarchical'	=>	true,
					'dropdown'		=>	true
				),
				'archives'			=> array(
					'count'		=>	true,
					'dropdown'	=>	true
				),
				'meta'				=> array()
			)
		);

		// page sidebar
		ssl_alp_set_sidebar_widgets(
			'ssl-alp-sidebar-page',
			array(
				'search'			=> array(),
				'ssl-alp-contents'	=> array()
			)
		);
	}
endif;

add_action( 'after_switch_theme', 'ssl_alp_update_sidebar_widgets' );

if ( ! function_exists( 'ssl_alp_set_sidebar_widgets' ) ) :
	/**
	 * Set a sidebar's widgets.
	 *
	 * @param   string  $sidebar    	Name of the sidebar to set
	 * @param   array   $widgets        Array of widget names and args
	 */
	function ssl_alp_set_sidebar_widgets( $sidebar, $widgets = array() ) {
		// get existing sidebars and their widgets
		$sidebar_widgets = get_option( 'sidebars_widgets' );
		
		if ( ! isset( $sidebar_widgets ) ) {
			// no sidebars defined; create empty array
			$sidebar_widgets = array();
		}

		// sidebar should exist
		if ( array_key_exists( $sidebar, $sidebar_widgets ) ) {
			if ( ! empty( $sidebar_widgets[ $sidebar ] ) ) {
				// sidebar has been modified by user already, so don't touch it
				return;
			}
		}
		
		// create sidebar
		$sidebar_widgets[ $sidebar ] = array();

		// add widgets
		foreach ( $widgets as $widget => $args ) {
			// get settings for this widget, if it exists already
			$options = get_option( "widget_$widget" );

			// check if this widget has been defined already
			if ( $options ) {
				// get last key
				$keys = array_keys( $options );
				$insert_id = rsort( $keys )[0];
			} else {
				// widget not yet defined in database
				// set flag to indicate new style widget
				$options = array( '_multiwidget' => 1 );
				$insert_id = 0;
			}

			// add 1 so we can create a new row
			$insert_id++;

			// set arguments for this instance
			$options[ $insert_id ] = $args;

			// save widget settings
			update_option( "widget_$widget", $options );

			error_log("updating widget_$widget to " . print_r($options, true));

			// add widget to sidebar
			$sidebar_widgets[ $sidebar ][] = "$widget-$insert_id";
		}

		// save new widgets
		update_option( 'sidebars_widgets', $sidebar_widgets );

		error_log("updating sidebars to " . print_r($sidebar_widgets, true));
	}
endif;

if ( ! function_exists( 'ssl_alp_widgets_init' ) ) :
	/**
	 * Register widget area.
	 */
	function ssl_alp_widgets_init() {
		// standard sidebar for posts, search, etc.
		register_sidebar( array(
			'name'          => __( 'Standard Sidebar', 'ssl-alp' ),
			'id'            => 'ssl-alp-sidebar-standard',
			'description'   => __( 'This is the sidebar appearing on front page, posts, archives, search, etc.', 'ssl-alp' ),
			'before_widget' => '<aside id="%1$s" class="widget clearfix %2$s">',
			'after_widget'  => '</aside>',
			'before_title'  => '<h3 class="widgettitle">',
			'after_title'   => '</h3>',
		) );

		// sidebar for pages, intended to show contents
		register_sidebar( array(
			'name'          => __( 'Page Sidebar', 'ssl-alp' ),
			'id'            => 'ssl-alp-sidebar-page',
			'description'   => __( 'This is the sidebar appearing on pages. This is intended to hold the page contents widget.', 'ssl-alp' ),
			'before_widget' => '<aside id="%1$s" class="widget clearfix %2$s">',
			'after_widget'  => '</aside>',
			'before_title'  => '<h3 class="widgettitle">',
			'after_title'   => '</h3>',
		) );
	}
endif;

add_action( 'widgets_init', 'ssl_alp_widgets_init' );

if ( ! function_exists( 'ssl_alp_scripts' ) ) :
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
endif;

add_action( 'wp_enqueue_scripts', 'ssl_alp_scripts' );

/**
 * Include helper.
 */
require get_template_directory() . '/includes/helper.php';

/**
 * Custom template tags for this theme.
 */
require get_template_directory() . '/includes/template-tags.php';

/**
 * Custom theme functions.
 */
require get_template_directory() . '/includes/theme-functions.php';

/**
 * Custom theme custom.
 */
require get_template_directory() . '/includes/theme-custom.php';

/**
 * Custom functions that act independently of the theme templates.
 */
require get_template_directory() . '/includes/extras.php';

/**
 * Customizer additions.
 */
require get_template_directory() . '/includes/customizer.php';

/**
 * Admin functions (for is_plugin_active)
 */
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
