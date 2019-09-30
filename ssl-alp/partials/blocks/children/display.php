<?php

/**
 * Children block contents.
 */
?>

<div class="<?php esc_html_e( $ssl_alp_page_children_extra_classes ); ?>">
    <?php if ( ! empty( $ssl_alp_page_children ) ) : ?>
    <ul>
    <?php foreach ( $ssl_alp_page_children as $page ) : ?>
        <li><a href="<?php echo get_permalink( $page ); ?>"><?php echo get_the_title( $page ); ?></a></li>
    <?php endforeach; ?>
    </ul>
    <?php endif; ?>
</div>
