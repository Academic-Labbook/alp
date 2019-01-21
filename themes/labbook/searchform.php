<?php
/**
 * Search form template.
 *
 * @package Labbook
 */

?>
<form role="search" method="get" id="searchform" class="search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<div>
		<label class="screen-reader-text" for="s"><?php esc_html_x( 'Search for:', 'label', 'labbook' ); ?></label>
		<input type="text" value="<?php the_search_query(); ?>" name="s" id="s" placeholder="<?php echo esc_attr( labbook_get_option( 'search_placeholder' ) ); ?>" class="search-field" />
		<input type="submit" class="search-submit screen-reader-text" id="searchsubmit" value="<?php echo esc_attr_x( 'Search', 'submit button', 'labbook' ); ?>" />
	</div>
</form><!-- .search-form -->
