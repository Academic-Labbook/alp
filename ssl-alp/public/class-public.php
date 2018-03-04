<?php

/**
 * The public-facing functionality of the plugin.
 */
class SSL_ALP_Public extends SSL_ALP_Base {
	/**
	 * Whether to add the MathJax script to the page
	 */
	public $add_mathjax_script = false;

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 */
	public function enqueue_styles() {
		/**
		 * Used by SSL_APL class
		 */
		wp_enqueue_style( 'ssl-alp-public-css', plugin_dir_url( __FILE__ ) . 'css/public.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 */
	public function enqueue_scripts() {
		/**
		 * Used by SSL_APL class
		 */
		wp_enqueue_script( 'ssl-alp-public-js', plugin_dir_url( __FILE__ ) . 'js/public.js', array( 'jquery' ), $this->version, false );
	}

	/**
	 * Add MathJax shortcodes to editor
	 */
	public function add_mathjax_shortcodes() {
		add_shortcode( 'latex', array( $this, 'latex_shortcode_hook' ) );
	}

	public function latex_shortcode_hook( $atts, $content ) {
		$this->add_mathjax_script = true;

		// add optional "syntax" attribute, which defaults to "inline", but can also be "block"
		$shortcode_atts = shortcode_atts(
			array(
				'display' => 'inline',
			),
			$atts
		);

		if ( $shortcode_atts['display'] === 'inline' ) {
			return '\(' . $content . '\)';
		} elseif ( $shortcode_atts['display'] === 'block' ) {
			return '\[' . $content . '\]';
		}
	}

	public function add_doi_shortcodes() {
		add_shortcode( 'doi', array( $this, 'doi_shortcode_hook' ) );
	}

	public function doi_shortcode_hook( $atts, $content ) {
		$content = sanitize_text_field( $content );

		// DOI URL
		$url = SSL_ALP_DOI_BASE_URL . $content;

		return '<a href="' . $url . '">doi:' . $content . '</a>';
	}

	public function add_arxiv_shortcodes() {
		add_shortcode( 'arxiv', array( $this, 'arxiv_shortcode_hook' ) );
	}

	public function arxiv_shortcode_hook( $atts, $content ) {
		$content = sanitize_text_field( $content );

		// arXiv URL
		$url = SSL_ALP_ARXIV_BASE_URL . $content;

		return '<a href="' . $url . '">arXiv:' . $content . '</a>';
	}

	public function add_mathjax_script() {
		if ( !$this->add_mathjax_script ) {
			// don't load script
			return;
		}

		// MathJax URL and SRI settings
		$mathjax_url = get_option( 'ssl_alp_mathjax_url' );

		// enqueue script in footer
		wp_enqueue_script( 'ssl-alp-mathjax-script', $mathjax_url, array(), SSL_ALP_MATHJAX_VERSION, true );
	}
}
