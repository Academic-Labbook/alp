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
	 * @param WP_Site|int $blog Blog object or ID.
	 */
	public static function uninstall_multisite_blog( $blog ) {
		$blog = get_site( $blog );

		if ( is_null( $blog ) ) {
			return;
		}

		// Delete blog data.
		self::delete_data_from_blog( $blog );
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
			$blog = get_site( $blog_id );

			if ( is_null( $blog ) ) {
				return;
			}

			// Delete data from this blog.
			self::delete_data_from_blog( $blog );
		}
	}

	/**
	 * Delete plugin data from the specified blog.
	 *
	 * @param WP_Site|int $blog Blog object or ID.
	 */
	private static function delete_data_from_blog( $blog ) {
		if ( ! is_multisite() ) {
			return;
		}

		$blog = get_site( $blog );

		if ( is_null( $blog ) ) {
			return;
		}

		// Switch to the blog.
		switch_to_blog( $blog->blog_id );

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
		self::delete_custom_post_types();
	}

	/**
	 * Delete plugin options.
	 */
	private static function delete_options() {
		// Delete site options.
		delete_option( 'ssl_alp_require_login' );
		delete_option( 'ssl_alp_enable_applications' );
		delete_option( 'ssl_alp_disallow_public_advanced_search' );
		delete_option( 'ssl_alp_enable_inventory' );
		delete_option( 'ssl_alp_allow_multiple_authors' );
		delete_option( 'ssl_alp_disable_post_trackbacks' );
		delete_option( 'ssl_alp_disable_social_media_blocks' );
		delete_option( 'ssl_alp_enable_crossreferences' );
		delete_option( 'ssl_alp_enable_edit_summaries' );
		delete_option( 'ssl_alp_flag_unread_posts' );
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
		self::delete_taxonomy( 'ssl-alp-coauthor' );
		self::delete_taxonomy( 'ssl-alp-crossreference' );
		self::delete_taxonomy( 'ssl-alp-unread-flag' );
		self::delete_taxonomy( 'ssl-alp-inventory-item' );
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
		// Revisions.
		self::delete_user_meta( 'ssl_alp_revisions_per_page' );

		// Application passwords.
		self::delete_user_meta( 'ssl_alp_applications' );
		self::delete_user_meta( 'ssl_alp_applications_per_page' );

		// Screen column settings.
		self::delete_user_meta( 'manageposts_page_ssl-alp-admin-post-revisionscolumnshidden' );
		self::delete_user_meta( 'managepages_page_ssl-alp-admin-page-revisionscolumnshidden' );
		self::delete_user_meta( 'managessl-alp-inventory_page_ssl-alp-admin-inventory-revisionscolumnshidden' );
		self::delete_user_meta( 'manageusers_page_ssl-alp-admin-applicationscolumnshidden' );
	}

	/**
	 * Delete user meta.
	 *
	 * @param string $meta_key User meta key.
	 *
	 * @global $wpdb
	 */
	private static function delete_user_meta( $meta_key ) {
		global $wpdb;

		$wpdb->delete(
			$wpdb->usermeta,
			array(
				'meta_key' => $meta_key,
			),
			array(
				'%s',
			)
		);
	}

	/**
	 * Delete custom post types.
	 */
	private static function delete_custom_post_types() {
		// Change inventory posts to pages.
		self::delete_custom_post_type_posts( 'ssl-alp-inventory', 'page' );
	}

	/**
	 * Delete or reassign custom post type posts and their revisions.
	 *
	 * @param string $post_type     The post type to remove.
	 * @param string $new_post_type The replacement post type. If specified, the post type is
	 *                              changed to this, and revisions are left untouched. If null, the
	 *                              posts are deleted.
	 *
	 * @global $wpdb
	 */
	private static function delete_custom_post_type_posts( $post_type, $new_post_type = null ) {
		global $wpdb;

		if ( is_null( $new_post_type ) ) {
			$post_ids = $wpdb->get_col(
				$wpdb->prepare(
					"
					SELECT post_id
					FROM $wpdb->posts
					WHERE post_type = %s
					"
				),
				$post_type
			);

			foreach ( $post_ids as $post_id ) {
				// Delete children.
				$wpdb->delete(
					$wpdb->posts,
					array(
						'post_parent' => $post_id,
					),
					array(
						'%d',
					)
				);

				// Delete post.
				$wpdb->delete(
					$wpdb->posts,
					array(
						'ID' => $post_id,
					),
					array(
						'%d',
					)
				);
			}
		} else {
			// Reassign to new post type.
			$wpdb->update(
				$wpdb->posts,
				array(
					'post_type' => $new_post_type,
				),
				array(
					'post_type' => $post_type,
				),
				array(
					'%s',
				),
				array(
					'%s',
				)
			);
		}
	}
}
