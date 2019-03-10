<?php
/**
 * Template part for displaying advanced search form.
 *
 * @package Labbook
 */

global $ssl_alp;

// Oldest post.
$oldest_posts = get_posts(
	array(
		'numberposts' => 1,
		'order'       => 'ASC',
		'orderby'     => 'date',
	)
);

$current_year = absint( date( 'Y' ) );

if ( ! empty( $oldest_posts ) ) {
	$oldest_year = absint( date( 'Y', strtotime( $oldest_posts[0]->post_date ) ) );
} else {
	// No posts. Use current year.
	$oldest_year = $current_year;
}

// Date ranges.
$year_range  = range( $oldest_year, $current_year );
$month_range = range( 1, 12 );
$day_range   = range( 1, 31 );

// Selected dates.
$selected_after_year   = get_query_var( 'ssl_alp_after_year' );
$selected_after_month  = get_query_var( 'ssl_alp_after_month' );
$selected_after_day    = get_query_var( 'ssl_alp_after_day' );
$selected_before_year  = get_query_var( 'ssl_alp_before_year' );
$selected_before_month = get_query_var( 'ssl_alp_before_month' );
$selected_before_day   = get_query_var( 'ssl_alp_before_day' );

// Get users with coauthored posts.
$authors = get_users(
    array(
        'order'   => 'ASC',
        'orderby' => 'display_name',
    )
);

// Get users with non-zero post counts. This matches the behaviour of wp_list_authors.
foreach ( (array) $authors as $id => $author ) {
    $post_count = $ssl_alp->coauthors->get_user_post_count( $author );

    if ( is_null( $post_count ) || 0 === intval( $post_count ) ) {
        // Remove user from list.
        unset( $authors[ $id ] );
    }
}

$categories = get_categories();
$tags = get_tags();

// Selected filter criteria.
$selected_coauthor_and    = get_query_var( 'ssl_alp_coauthor__and', array() );
$selected_coauthor_in     = get_query_var( 'ssl_alp_coauthor__in', array() );
$selected_coauthor_not_in = get_query_var( 'ssl_alp_coauthor__not_in', array() );
$selected_category_and    = get_query_var( 'category__and', array() );
$selected_category_in     = get_query_var( 'category__in', array() );
$selected_category_not_in = get_query_var( 'category__not_in', array() );
$selected_tag_and         = get_query_var( 'tag__and', array() );
$selected_tag_in          = get_query_var( 'tag__in', array() );
$selected_tag_not_in      = get_query_var( 'tag__not_in', array() );

?>

<?php if ( labbook_ssl_alp_advanced_search_enabled() ) : ?>

    <form role="search" method="get" id="advanced-search-form" class="search-form advanced-search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
        <div class="advanced-search">

            <h3><?php esc_html_e( 'Keywords', 'labbook' ); ?></h3>
            <div>
                <label class="screen-reader-text" for="s"><?php esc_html_x( 'Search for:', 'label', 'labbook' ); ?></label>
                <input type="text" value="<?php the_search_query(); ?>" name="s" id="s" placeholder="<?php echo esc_attr( labbook_get_option( 'search_placeholder' ) ); ?>" class="search-field" />
                <input type="submit" class="search-submit screen-reader-text" id="searchsubmit" value="<?php echo esc_attr_x( 'Search', 'submit button', 'labbook' ); ?>" />
            </div>

            <h3><?php esc_html_e( 'Publication date', 'labbook' ); ?></h3>
            <fieldset class="advanced-search-date-range">
                <?php esc_html_e( 'From', 'labbook' ); ?>
                <select name="ssl_alp_after_year">
                    <option value=""></option>
                <?php foreach ( $year_range as $year ) : ?>
                    <option value="<?php esc_attr_e( $year ); ?>"<?php echo _e( ( $year == $selected_after_year ) ? ' selected="true"' : '' ); ?>><?php esc_html_e( $year ); ?></option>
                <?php endforeach; ?>
                </select>
                <select name="ssl_alp_after_month">
                    <option value=""></option>
                <?php foreach ( $month_range as $month ) : ?>
                    <option value="<?php esc_attr_e( $month ); ?>"<?php echo _e( ( $month == $selected_after_month ) ? ' selected="true"' : '' ); ?>><?php esc_html_e( $month ); ?></option>
                <?php endforeach; ?>
                </select>
                <select name="ssl_alp_after_day">
                    <option value=""></option>
                <?php foreach ( $day_range as $day ) : ?>
                    <option value="<?php esc_attr_e( $day ); ?>"<?php echo _e( ( $day == $selected_after_day ) ? ' selected="true"' : '' ); ?>><?php esc_html_e( $day ); ?></option>
                <?php endforeach; ?>
                </select>
                <?php esc_html_e( 'to', 'labbook' ); ?>
                <select name="ssl_alp_before_year">
                    <option value=""></option>
                <?php foreach ( $year_range as $year ) : ?>
                    <option value="<?php esc_attr_e( $year ); ?>"<?php echo _e( ( $year == $selected_before_year ) ? ' selected="true"' : '' ); ?>><?php esc_html_e( $year ); ?></option>
                <?php endforeach; ?>
                </select>
                <select name="ssl_alp_before_month">
                    <option value=""></option>
                <?php foreach ( $month_range as $month ) : ?>
                    <option value="<?php esc_attr_e( $month ); ?>"<?php echo _e( ( $month == $selected_before_month ) ? ' selected="true"' : '' ); ?>><?php esc_html_e( $month ); ?></option>
                <?php endforeach; ?>
                </select>
                <select name="ssl_alp_before_day">
                    <option value=""></option>
                <?php foreach ( $day_range as $day ) : ?>
                    <option value="<?php esc_attr_e( $day ); ?>"<?php echo _e( ( $day == $selected_before_day ) ? ' selected="true"' : '' ); ?>><?php esc_html_e( $day ); ?></option>
                <?php endforeach; ?>
                </select>
                <?php esc_html_e( '.', 'labbook' ); ?>
            </fieldset>

            <h3><?php esc_html_e( 'Authors', 'labbook' ); ?></h3>
            <table class="advanced-search-criteria">
                <tr>
                    <th><?php esc_attr_e( 'Posts with all of these authors', 'labbook' ); ?></th>
                    <th><?php esc_attr_e( 'Posts with any of these authors', 'labbook' ); ?></th>
                    <th><?php esc_attr_e( 'Posts with none of these authors', 'labbook' ); ?></th>
                </tr>
                <tr>
                    <td>
                        <select name="ssl_alp_coauthor__and[]" multiple="true" size="10">
                        <?php foreach( $authors as $author ) : ?>
                        <?php
                        $coauthor_term = $ssl_alp->coauthors->get_coauthor_term( $author );
                        ?>
                            <option value="<?php esc_attr_e( $coauthor_term->term_taxonomy_id ); ?>"<?php echo _e( ( in_array( $coauthor_term->term_taxonomy_id, $selected_coauthor_and ) ) ? ' selected="true"' : '' ); ?>><?php esc_html_e( $author->display_name ); ?></option>
                        <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <select name="ssl_alp_coauthor__in[]" multiple="true" size="10">
                        <?php foreach( $authors as $author ) : ?>
                        <?php
                        $coauthor_term = $ssl_alp->coauthors->get_coauthor_term( $author );
                        ?>
                            <option value="<?php esc_attr_e( $coauthor_term->term_taxonomy_id ); ?>"<?php echo _e( ( in_array( $coauthor_term->term_taxonomy_id, $selected_coauthor_in ) ) ? ' selected="true"' : '' ); ?>><?php esc_html_e( $author->display_name ); ?></option>
                        <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <select name="ssl_alp_coauthor__not_in[]" multiple="true" size="10">
                        <?php foreach( $authors as $author ) : ?>
                        <?php
                        $coauthor_term = $ssl_alp->coauthors->get_coauthor_term( $author );
                        ?>
                            <option value="<?php esc_attr_e( $coauthor_term->term_taxonomy_id ); ?>"<?php echo _e( ( in_array( $coauthor_term->term_taxonomy_id, $selected_coauthor_not_in ) ) ? ' selected="true"' : '' ); ?>><?php esc_html_e( $author->display_name ); ?></option>
                        <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>
            <h3><?php esc_html_e( 'Categories', 'labbook' ); ?></h3>
            <table class="advanced-search-criteria">
                <tr>
                    <th><?php esc_attr_e( 'Posts with all of these categories', 'labbook' ); ?></th>
                    <th><?php esc_attr_e( 'Posts with any of these categories', 'labbook' ); ?></th>
                    <th><?php esc_attr_e( 'Posts with none of these categories', 'labbook' ); ?></th>
                </tr>
                <tr>
                    <td>
                        <select name="category__and[]" multiple="true" size="10">
                        <?php foreach( $categories as $category ) : ?>
                            <option value="<?php esc_attr_e( $category->term_id ); ?>"<?php echo _e( ( in_array( $category->term_id, $selected_category_and ) ) ? ' selected="true"' : '' ); ?>><?php esc_html_e( $category->name ); ?></option>
                        <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <select name="category__in[]" multiple="true" size="10">
                        <?php foreach( $categories as $category ) : ?>
                            <option value="<?php esc_attr_e( $category->term_id ); ?>"<?php echo _e( ( in_array( $category->term_id, $selected_category_in ) ) ? ' selected="true"' : '' ); ?>><?php esc_html_e( $category->name ); ?></option>
                        <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <select name="category__not_in[]" multiple="true" size="10">
                        <?php foreach( $categories as $category ) : ?>
                            <option value="<?php esc_attr_e( $category->term_id ); ?>"<?php echo _e( ( in_array( $category->term_id, $selected_category_not_in ) ) ? ' selected="true"' : '' ); ?>><?php esc_html_e( $category->name ); ?></option>
                        <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>
            <h3><?php esc_html_e( 'Tags', 'labbook' ); ?></h3>
            <table class="advanced-search-criteria">
                <tr>
                    <th><?php esc_attr_e( 'Posts with all of these tags', 'labbook' ); ?></th>
                    <th><?php esc_attr_e( 'Posts with any of these tags', 'labbook' ); ?></th>
                    <th><?php esc_attr_e( 'Posts with none of these tags', 'labbook' ); ?></th>
                </tr>
                <tr>
                    <td>
                        <select name="tag__and[]" multiple="true" size="10">
                        <?php foreach( $tags as $tag ) : ?>
                            <option value="<?php esc_attr_e( $tag->term_id ); ?>"<?php echo _e( ( in_array( $tag->term_id, $selected_tag_and ) ) ? ' selected="true"' : '' ); ?>><?php esc_html_e( $tag->name ); ?></option>
                        <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <select name="tag__in[]" multiple="true" size="10">
                        <?php foreach( $tags as $tag ) : ?>
                            <option value="<?php esc_attr_e( $tag->term_id ); ?>"<?php echo _e( ( in_array( $tag->term_id, $selected_tag_in ) ) ? ' selected="true"' : '' ); ?>><?php esc_html_e( $tag->name ); ?></option>
                        <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <select name="tag__not_in[]" multiple="true" size="10">
                        <?php foreach( $tags as $tag ) : ?>
                            <option value="<?php esc_attr_e( $tag->term_id ); ?>"<?php echo _e( ( in_array( $tag->term_id, $selected_tag_not_in ) ) ? ' selected="true"' : '' ); ?>><?php esc_html_e( $tag->name ); ?></option>
                        <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>
        </div>
    </form><!-- .search-form -->

<?php else :

    /* Show standard search. */
    get_search_form();

endif; ?>
