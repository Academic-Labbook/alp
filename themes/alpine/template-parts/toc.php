<?php
/**
 * Template part for post table of contents
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Alpine
 */

?>

<div class="entry-toc entry-toc-<?php the_ID(); ?>">
    <h3 class="entry-toc-title"><?php _e( 'Contents', 'ssl-alpine' ) ?></h3>
    <?php ssl_alpine_the_toc_display(); ?>
</div>
