<div id="secondary" <?php echo ssl_alp_sidebar_class( 'widget-area container clearfix' ); ?> role="complementary">
<?php
if ( is_page() && ssl_alp_get_option( 'page_specific_sidebar' ) ) {
	get_sidebar( 'page' );
} else {
	get_sidebar( 'standard' );
}
?>
</div>