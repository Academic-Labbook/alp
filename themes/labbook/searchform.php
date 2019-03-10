<?php
/**
 * Search form template.
 *
 * @package Labbook
 */

?>
<form role="search" method="get" id="searchform" class="search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<div class="search-flex">
		<label class="screen-reader-text" for="s"><?php esc_html_x( 'Search for:', 'label', 'labbook' ); ?></label>
		<input type="text" value="<?php the_search_query(); ?>" name="s" id="s" placeholder="<?php echo esc_attr( labbook_get_option( 'search_placeholder' ) ); ?>" class="search-field" />
		<input type="submit" class="search-submit" id="searchsubmit" value="<?php echo esc_attr_x( 'Go', 'submit button', 'labbook' ); ?>" />
	<?php if ( labbook_ssl_alp_advanced_search_enabled() ) : ?>
		<span class="search-advanced-link">
			<a href="<?php echo esc_url( home_url( '/?labbook_advanced_search=1' ) ); ?>"><?php esc_html_e( 'Advanced', 'labbook' ); ?></a>
		</span>
	<?php endif; ?>
	</div>
</form><!-- .search-form -->
