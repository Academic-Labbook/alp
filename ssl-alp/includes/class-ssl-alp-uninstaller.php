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
	 * This function fires when the plugin is uninstalled, either on an individual blog or a
	 * network, but not when a blog is deleted on a network. That condition is handled by
	 * `uninstall_multisite_blog`.
	 *
	 * This should remove all traces of the plugin. See e.g.
	 * https://wordpress.stackexchange.com/a/716/138112.
	 *
	 * One exception to this is the user roles, which are permanently changed
	 * by ALP.
	 */
	public static function uninstall() {
		if ( is_multisite() ) {
			// This is a network and the plugin is being uninstalled on all blogs. Remove data from
			// each blog.
			self::delete_data_from_blogs();
		} else {
			// Delete data on single site.
			self::delete_data();
		}
	}

	/**
	 * Action to run when a blog is deleted on a network, to delete plugin options and terms.
	 *
	 * @param int $blog_id Blog ID.
	 */
	public static function uninstall_multisite_blog( $blog_id ) {
		// Add blog options using blog ID specified in call.
		self::delete_data_from_blog( $blog_id );
	}

	/**
	 * Delete plugin data from each blog on the network.
	 *
	 * @global wpdb $wpdb
	 */
	private static function delete_data_from_blogs() {
		global $wpdb;

		if ( ! is_multisite() ) {
			return;
		}

		// Loop over all blogs on the network.
		foreach ( $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs}" ) as $blog_id ) {
			// Delete data from this blog.
			self::delete_data_from_blog( $blog_id );
		}
	}

	/**
	 * Delete plugin data from the specified blog.
	 *
	 * @param int $blog_id Blog ID.
	 */
	private static function delete_data_from_blog( $blog_id ) {
		if ( ! is_multisite() ) {
			return;
		}

		// Switch to the blog.
		switch_to_blog( $blog_id );

		// Delete data from this blog.
		self::delete_data();

		// Switch back to previous blog.
		restore_current_blog();
	}

	/**
	 * Delete plugin data from a single site.
	 */
	public static function delete_data() {
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

				if ( is_object( $taxonomy ) && property_exists( $taxonomy, 'slug' ) && ! is_null( $taxonomy->slug ) ) {
					// Delete taxonomy prefix.
					delete_option( 'prefix_' . $taxonomy->slug . '_option_name' );
				}
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
