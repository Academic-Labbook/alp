<?php

if ( ! defined( 'WPINC' ) ) {
    // prevent direct access
    exit;
}

/**
 * Fired during plugin uninstallation.
 */
class SSL_ALP_Uninstaller {
	/**
	 * Uninstall plugin.
     *
     * This should remove all traces of the plugin. See e.g.
     * https://wordpress.stackexchange.com/a/716/138112.
     *
     * One exception to this is the user roles, which are permanently changed
     * by ALP.
	 */
	public static function uninstall() {
        self::delete_options();
        self::delete_taxonomies();
        self::delete_post_metas();
        self::delete_user_metas();
    }

    private static function delete_options() {
        // delete site options
        delete_option( 'ssl_alp_require_login' );
        delete_option( 'ssl_alp_allow_multiple_authors' );
        delete_option( 'ssl_alp_disable_post_trackbacks' );
        delete_option( 'ssl_alp_enable_crossreferences' );
        delete_option( 'ssl_alp_enable_edit_summaries' );
        delete_option( 'ssl_alp_enable_tex' );

        // delete network options
        delete_site_option( 'ssl_alp_additional_media_types' );
        delete_site_option( 'ssl_alp_tex_use_custom_urls' );
        delete_site_option( 'ssl_alp_katex_js_url' );
        delete_site_option( 'ssl_alp_katex_copy_js_url' );
        delete_site_option( 'ssl_alp_katex_css_url' );
        delete_site_option( 'ssl_alp_katex_copy_css_url' );
    }

    private static function delete_taxonomies() {
        self::delete_taxonomy( 'ssl_alp_coauthor' );
        self::delete_taxonomy( 'ssl_alp_crossreference' );
    }

    /**
     * Delete taxonomy and its terms.
     *
     * Based on https://wpsmith.net/2014/plugin-uninstall-delete-terms-taxonomies-wordpress-database/.
     */
    private static function delete_taxonomy( $taxonomy ) {
        global $wpdb;

        // get terms associated with this taxonomy
        $terms = $wpdb->get_results(
            $wpdb->prepare("
                SELECT t.*, tt.*
                FROM $wpdb->terms AS t
                INNER JOIN $wpdb->term_taxonomy AS tt
                    ON t.term_id = tt.term_id
                WHERE tt.taxonomy = '%s'",
                $taxonomy
            )
        );

        // delete terms
        if ( $terms ) {
            foreach ( $terms as $term ) {
                // delete term's taxonomy association
                $wpdb->delete(
                    $wpdb->term_taxonomy,
                    array(
                        'term_taxonomy_id' => $term->term_taxonomy_id
                    )
                );

                // delete post relationships to term
                $wpdb->delete(
                    $wpdb->term_relationships,
                    array(
                        'term_taxonomy_id' => $term->term_taxonomy_id
                    )
                );

                // delete term meta
                $wpdb->delete(
                    $wpdb->term_meta,
                    array(
                        'term_id' => $term->term_id
                    )
                );

                // delete term
                $wpdb->delete(
                    $wpdb->terms,
                    array(
                        'term_id' => $term->term_id
                    )
                );

                // delete taxonomy prefix
                delete_option( 'prefix_' . $taxonomy->slug . '_option_name' );
            }
        }

        // delete taxonomy
        $wpdb->delete(
            $wpdb->term_taxonomy,
            array(
                'taxonomy' => $taxonomy
            ),
            array(
                '%s'
            )
        );
    }

    private static function delete_post_metas() {
        self::delete_post_meta( 'ssl_alp_edit_summary' );
        self::delete_post_meta( 'ssl_alp_edit_summary_revert_id' );
    }

    private static function delete_post_meta( $meta_key ) {
        global $wpdb;

        $wpdb->delete(
            $wpdb->post_meta,
            array(
                'meta_key' => $meta_key
            ),
            array(
                '%s'
            )
        );
    }

    private static function delete_user_metas() {
        // no user meta defined by plugin
    }
}
