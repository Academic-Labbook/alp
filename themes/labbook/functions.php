<?php
/**
 * Labbook functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Labbook
 */

// Theme version.
define( 'LABBOOK_VERSION', '1.1.0' );

// Required PHP version.
define( 'LABBOOK_MINIMUM_PHP_VERSION', '7.0.0' );

if ( ! function_exists( 'labbook_check_php_version' ) ) :
	/**
	 * Detect current PHP version and prevent theme switch if not recent enough.
	 *
	 * @param string   $old_name  Old theme name.
	 * @param WP_Theme $old_theme Old theme object.
	 */
	function labbook_check_php_version( $old_name, $old_theme ) {
		// Compare versions.
		if ( version_compare( phpversion(), LABBOOK_MINIMUM_PHP_VERSION, '<' ) ) {
			/**
			 * Notify admin that their PHP version is too low and return to the previous theme.
			 */
			function labbook_version_too_low_admin_notice() {
				echo '<div class="update-nag">';
				esc_html_e( 'Labbook cannot run on the currently installed PHP version.', 'labbook' );
				echo '<br/>';
				printf(
					/* translators: 1: current PHP version, 2: required PHP version */
					esc_html__( 'Actual version is: %1$s, required version is: %2$s.', 'labbook' ),
					esc_html( phpversion() ),
					esc_html( LABBOOK_MINIMUM_PHP_VERSION )
				);
				echo '</div>';
			}

			// Theme not activated info message.
			add_action( 'admin_notices', 'labbook_version_too_low_admin_notice' );

			// Switch back to previous theme.
			switch_theme( $old_theme->stylesheet );
		}
	}
endif;
add_action( 'after_switch_theme', 'labbook_check_php_version' );

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
			'network-menu' => esc_html__( 'Network', 'labbook' ),
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
			'height'      => 155, // Should match .site-branding CSS grid row height.
			'width'       => 400,
			'flex-width'  => true,
			'flex-height' => false,
		) );

		/**
		 * Add support for styling the post editor.
		 *
		 * @link https://wordpress.org/gutenberg/handbook/designers-developers/developers/themes/theme-support/#editor-styles
		 */
		add_theme_support( 'editor-styles' );
		add_editor_style( 'style-editor.css' );

		/**
		 * Add support for showing coauthors.
		 */
		add_theme_support( 'ssl-alp-coauthors' );

		/**
		 * Add support for showing edit summaries under posts.
		 */
		add_theme_support( 'ssl-alp-edit-summaries' );

		/**
		 * Add support for showing cross-references under posts.
		 */
		add_theme_support( 'ssl-alp-cross-references' );

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

	if ( labbook_get_option( 'show_unread_flags' ) && labbook_ssl_alp_unread_flags_enabled() ) {
		// Add support for unread flags.
		wp_enqueue_script(
			'labbook-post-read-status',
			get_template_directory_uri() . '/js/post-read-status.js',
			array(
				'jquery',
				'wp-api',
			),
			LABBOOK_VERSION,
			true
		);
	}
}
add_action( 'wp_enqueue_scripts', 'labbook_scripts' );

if ( ! function_exists( 'labbook_get_content_with_toc' ) ) :
	/**
	 * Insert table of contents into post.
	 *
	 * @param string $content The post content.
	 */
	function labbook_get_content_with_toc( $content ) {
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

		// Get contents hierarchy.
		$content = labbook_generate_post_contents( $content, $hierarchy );

		if ( is_null( $hierarchy ) || ! $hierarchy->count() ) {
			// Table of contents was not generated or has no entries.
			return $content;
		}

		?>
		<div class="entry-toc entry-toc-<?php the_ID(); ?>">
			<h3 class="entry-toc-title"><?php esc_html_e( 'Contents', 'labbook' ) ?></h3>
			<?php labbook_the_toc( $hierarchy, labbook_get_option( 'table_of_contents_max_depth' ) ); ?>
		</div>
		<?php

		return $content;
	}
endif;
add_filter( 'the_content', 'labbook_get_content_with_toc' );

/**
 * Check if coauthors provided by the ALP plugin are available and enabled.
 */
function labbook_ssl_alp_coauthors_enabled() {
	if ( ! is_plugin_active( 'ssl-alp/alp.php' ) ) {
		// Plugin is disabled.
		return false;
	} elseif ( ! get_option( 'ssl_alp_allow_multiple_authors' ) ) {
		// Coauthors are disabled.
		return false;
	}

	return true;
}

/**
 * Check if crossreferences provided by the ALP plugin are available and enabled.
 */
function labbook_ssl_alp_crossreferences_enabled() {
	if ( ! is_plugin_active( 'ssl-alp/alp.php' ) ) {
		// Plugin is disabled.
		return false;
	} elseif ( ! get_option( 'ssl_alp_enable_crossreferences' ) ) {
		// Cross-references are disabled.
		return false;
	}

	return true;
}

/**
 * Check if edit summaries provided by the ALP plugin are available and enabled.
 */
function labbook_ssl_alp_edit_summaries_enabled() {
	if ( ! is_plugin_active( 'ssl-alp/alp.php' ) ) {
		// Plugin is disabled.
		return false;
	} elseif ( ! get_option( 'ssl_alp_enable_edit_summaries' ) ) {
		// Tracking of edit summaries is disabled.
		return false;
	}

	return true;
}

/**
 * Check if unread flags provided by the ALP plugin are available and enabled.
 */
function labbook_ssl_alp_unread_flags_enabled() {
	if ( ! is_plugin_active( 'ssl-alp/alp.php' ) ) {
		// Plugin is disabled.
		return false;
	} elseif ( ! get_option( 'ssl_alp_flag_unread_posts' ) ) {
		// Unread flags are disabled.
		return false;
	}

	return true;
}

/**
 * Page table of contents generator.
 */
require get_template_directory() . '/inc/class-labbook-toc-menu-level.php';

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
