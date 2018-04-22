<div id="secondary" <?php echo ssl_alp_sidebar_class( 'widget-area container clearfix' ); ?> role="complementary">
<?php
if ( is_page() ) {
	get_sidebar( 'page' );
} else {
	get_sidebar( 'standard' );
}
?>
</div>