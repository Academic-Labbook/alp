<?php
/**
 * Uninstallation tools.
 *
 * @package ssl-alp
 */

if ( ! defined( 'WPINC' ) ) {
	// Prevent direct access.
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

	/**
	 * Delete plugin options.
	 */
	private static function delete_options() {
		// Delete site options.
		delete_option( 'ssl_alp_require_login' );
		delete_option( 'ssl_alp_allow_multiple_authors' );
		delete_option( 'ssl_alp_disable_post_trackbacks' );
		delete_option( 'ssl_alp_enable_crossreferences' );
		delete_option( 'ssl_alp_enable_edit_summaries' );
		delete_option( 'ssl_alp_enable_tex' );

		// Delete network options.
		delete_site_option( 'ssl_alp_additional_media_types' );
		delete_site_option( 'ssl_alp_katex_use_custom_urls' );
		delete_site_option( 'ssl_alp_katex_js_url' );
		delete_site_option( 'ssl_alp_katex_copy_js_url' );
		delete_site_option( 'ssl_alp_katex_css_url' );
		delete_site_option( 'ssl_alp_katex_copy_css_url' );
	}

	/**
	 * Delete plugin taxonomies.
	 */
	private static function delete_taxonomies() {
		self::delete_taxonomy( 'ssl_alp_coauthor' );
		self::delete_taxonomy( 'ssl_alp_crossreference' );
	}

	/**
	 * Delete taxonomy and its terms.
	 *
	 * Based on https://wpsmith.net/2014/plugin-uninstall-delete-terms-taxonomies-wordpress-database/.
	 *
	 * @param string $taxonomy Taxonomy to delete.
	 *
	 * @global $wpdb
	 */
	private static function delete_taxonomy( $taxonomy ) {
		global $wpdb;

		// Get terms associated with this taxonomy.
		$terms = $wpdb->get_results(
			$wpdb->prepare(
				"
				SELECT t.*, tt.*
				FROM {$wpdb->terms} AS t
				INNER JOIN {$wpdb->term_taxonomy} AS tt
					ON t.term_id = tt.term_id
				WHERE tt.taxonomy = %s",
				$taxonomy
			)
		);

		// Delete terms.
		if ( $terms ) {
			foreach ( $terms as $term ) {
				// Delete term's taxonomy association.
				$wpdb->delete(
					$wpdb->term_taxonomy,
					array(
						'term_taxonomy_id' => $term->term_taxonomy_id,
					)
				);

				// Delete post relationships to term.
				$wpdb->delete(
					$wpdb->term_relationships,
					array(
						'term_taxonomy_id' => $term->term_taxonomy_id,
					)
				);

				// Delete term meta.
				$wpdb->delete(
					$wpdb->termmeta,
					array(
						'term_id' => $term->term_id,
					)
				);

				// Delete term.
				$wpdb->delete(
					$wpdb->terms,
					array(
						'term_id' => $term->term_id,
					)
				);

				// Delete taxonomy prefix.
				delete_option( 'prefix_' . $taxonomy->slug . '_option_name' );
			}
		}

		// Delete taxonomy.
		$wpdb->delete(
			$wpdb->term_taxonomy,
			array(
				'taxonomy' => $taxonomy,
			),
			array(
				'%s',
			)
		);
	}

	/**
	 * Delete post metas.
	 */
	private static function delete_post_metas() {
		self::delete_post_meta( 'ssl_alp_edit_summary' );
		self::delete_post_meta( 'ssl_alp_edit_summary_revert_id' );
	}

	/**
	 * Delete post meta.
	 *
	 * @param string $meta_key Post meta key.
	 *
	 * @global $wpdb
	 */
	private static function delete_post_meta( $meta_key ) {
		global $wpdb;

		$wpdb->delete(
			$wpdb->postmeta,
			array(
				'meta_key' => $meta_key,
			),
			array(
				'%s',
			)
		);
	}

	/**
	 * Delete user metas.
	 */
	private static function delete_user_metas() {
		// No user meta defined by plugin.
	}
}
