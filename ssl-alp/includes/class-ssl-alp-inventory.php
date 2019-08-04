<?php
/**
 * Inventory tools.
 *
 * @package ssl-alp
 */

if ( ! defined( 'WPINC' ) ) {
	// Prevent direct access.
	exit;
}

/**
 * Inventory functionality.
 */
class SSL_ALP_Inventory extends SSL_ALP_Module {
	/**
	 * Post types with inventory term support.
	 *
	 * @var array
	 */
	protected $supported_post_types = array(
        'post',
    );

	/**
	 * Register hooks.
	 */
	public function register_hooks() {
		$loader = $this->get_loader();

		/**
		 * Inventory post type.
		 */

        // Register inventory post type.
        $loader->add_action( 'init', $this, 'register_post_type' );

        // Remove month dropdown filter on admin page list.
        $loader->add_action( 'months_dropdown_results', $this, 'disable_months_dropdown_results', 10, 2 );

        // Remove date column from admin post list.
        $loader->add_filter( 'manage_edit-ssl_alp_inventory_columns', $this, 'manage_edit_columns' );

        // Sort inventory posts alphabetically by default.
        $loader->add_filter( 'manage_edit-ssl_alp_inventory_sortable_columns', $this, 'manage_edit_sortable_columns' );

        // Create/delete corresponding inventory item terms whenever posts are created/deleted.
        $loader->add_action( 'save_post', $this, 'associate_inventory_post_with_term', 10, 2 );
		$loader->add_action( 'deleted_post', $this, 'delete_associated_inventory_post_term' );

        /**
         * Inventory taxonomy.
         */

		// Register inventory item taxonomy.
		$loader->add_action( 'init', $this, 'register_taxonomy' );

		// Disallow creation of new terms directly (this is temporarily disabled
		// by `associate_inventory_post_with_term`).
		// NOTE: if this line is changed, the enable_disallow_insert_term_filter
		// and disable_disallow_insert_term_filter functions must also be
		// updated.
		$loader->add_filter( 'pre_insert_term', $this, 'disallow_insert_term', 10, 2 );

		// Delete any invalid inventory items when post terms are set.
		$loader->add_action( 'added_term_relationship', $this, 'reject_invalid_inventory_terms', 10, 3 );

		// Filter to stop users from editing or deleting inventory terms directly.
		$loader->add_filter( 'user_has_cap', $this, 'filter_user_has_cap', 10, 4 );

		// Stop super admins deleting inventory terms.
		$loader->add_filter( 'map_meta_cap', $this, 'filter_capabilities', 10, 4 );
	}

	/**
	 * Register settings.
	 */
	public function register_settings() {
		register_setting(
			SSL_ALP_SITE_SETTINGS_PAGE,
			'ssl_alp_enable_inventory',
			array(
				'type' => 'boolean',
			)
		);
	}

	/**
	 * Register settings fields.
	 */
	public function register_settings_fields() {
		/**
		 * Post multiple author settings field.
		 */
		add_settings_field(
			'ssl_alp_inventory_settings',
			__( 'Inventory', 'ssl-alp' ),
			array( $this, 'inventory_settings_callback' ),
			SSL_ALP_SITE_SETTINGS_PAGE,
			'ssl_alp_site_settings_section'
		);
	}

	/**
	 * Inventory settings partial.
	 */
	public function inventory_settings_callback() {
		require_once SSL_ALP_BASE_DIR . 'partials/admin/settings/site/inventory-settings-display.php';
	}

	private function enable_disallow_insert_term_filter() {
		add_filter( 'pre_insert_term', array( $this, 'disallow_insert_term', 10, 2 ) );
	}

	private function disable_disallow_insert_term_filter() {
		remove_filter( 'pre_insert_term', array( $this, 'disallow_insert_term', 10, 2 ) );
	}

    /**
     * Register the inventory post type.
     */
    public function register_post_type() {
		if ( ! get_option( 'ssl_alp_enable_inventory' ) ) {
			// Inventory disabled.
			return;
        }

        $labels = array(
            'name'                     => __( 'Inventory', 'ssl-alp' ),
            'singular_name'            => __( 'Inventory', 'ssl-alp' ),
            'add_new_item'             => __( 'Add New Item', 'ssl-alp' ),
            'edit_item'                => __( 'Edit Item', 'ssl-alp' ),
            'new_item'                 => __( 'New Item', 'ssl-alp' ),
            'view_item'                => __( 'View Item', 'ssl-alp' ),
            'view_items'               => __( 'View Items', 'ssl-alp' ),
            'search_items'             => __( 'Search Items', 'ssl-alp' ),
            'not_found'                => __( 'No items found.', 'ssl-alp' ),
            'not_found_in_trash'       => __( 'No items found in Trash.', 'ssl-alp' ),
            'all_items'                => __( 'All Items', 'ssl-alp' ),
            'attributes'               => __( 'Item Attributes', 'ssl-alp' ),
            'insert_into_item'         => __( 'Insert into item', 'ssl-alp' ),
            'uploaded_to_this_item'    => __( 'Uploaded to this item', 'ssl-alp' ),
            'featured_image'           => __( 'Item Image', 'ssl-alp' ),
            'set_featured_image'       => __( 'Set item image', 'ssl-alp' ),
            'remove_featured_image'    => __( 'Remove item image', 'ssl-alp' ),
            'use_featured_image'       => __( 'Use as item image', 'ssl-alp' ),
            'filter_items_list'        => __( 'Filter items list', 'ssl-alp' ),
            'items_list_navigation'    => __( 'Items list navigation', 'ssl-alp' ),
            'items_list'               => __( 'Items list', 'ssl-alp' ),
            'item_published'           => __( 'Item created.', 'ssl-alp' ),
            'item_published_privately' => __( 'Item created privately.', 'ssl-alp' ),
            'item_reverted_to_draft'   => __( 'Item reverted to draft.', 'ssl-alp' ),
            'item_updated'             => __( 'Item updated.', 'ssl-alp' ),
        );

        // Register new post type to represent inventory items.
        $args = array(
            'labels'          => $labels,
            'description'     => __( 'Inventory items.', 'ssl-alp' ),
            'public'          => true,
            'hierarchical'    => false,
			'show_in_rest'    => true,
			'template'        => array(
				array(
					'core/heading',
					array(
						'content'     => 'Location',
					)
				),
				array(
					'core/paragraph',
					array(
						'placeholder' => 'Location...',
					)
				),
			),
			'menu_icon'       => 'dashicons-tag',
            'supports'        => array(
                'title',
                'editor',
                'revisions',
                'page-attributes',
                'thumbnail',
            ),
            'rewrite'         => array(
                'slug' => 'inventory',
            ),
        );

        register_post_type( 'ssl_alp_inventory', $args );
    }

	/**
	 * Disable months dropdown box in admin inventory posts list.
	 *
	 * @param array  $months    Months.
	 * @param string $post_type Post type being shown.
	 * @return array Empty array if post type is page, otherwise $months.
	 */
	public function disable_months_dropdown_results( $months, $post_type ) {
		if ( 'ssl_alp_inventory' === $post_type ) {
			// Return empty array to force it to hide (see months_dropdown() in class-wp-list-table.php).
			return array();
		}

		return $months;
	}

	/**
	 * Filter columns shown on list of inventory posts in admin panel.
	 *
	 * @param array $columns Columns to show by default.
	 * @return array Columns with date column removed.
	 */
	public function manage_edit_columns( $columns ) {
		if ( array_key_exists( 'date', $columns ) ) {
			// Remove date column.
			unset( $columns['date'] );
		}

		return $columns;
	}

	/**
	 * Remove date column and sort columns alphabetically by name on list of
     * inventory posts in admin panel.
	 *
	 * @param array $columns Sortable columns.
	 * @return array Columns with title column set as default sort.
	 */
	public function manage_edit_sortable_columns( $columns ) {
		if ( array_key_exists( 'date', $columns ) ) {
			// Remove date column.
			unset( $columns['date'] );
		}

		// Make title the default sort.
		$columns['title'] = array( $columns['title'], true );

		return $columns;
    }

	/**
	 * Add or update inventory item taxonomy term using the specified inventory
	 * custom post type post.
	 *
	 * @param WP_Post $post The inventory post.
	 */
	private function update_inventory_item_term( $post ) {
		$post = get_post( $post );

        if ( is_null( $post ) ) {
			// Invalid post.
			return;
		}

		// Get inventory item term, if present.
		$term = $this->get_inventory_term( $post );

		// Temporarily disable the filter that blocks creation of terms in the
		// ssl_alp_inventory_item taxonomy.
		$this->disable_disallow_insert_term_filter();

		if ( ! $term ) {
			// Term doesn't yet exist.
			$args = array(
				'slug' => $this->get_inventory_term_slug( $post ),
			);

			wp_insert_term( $post->post_title, 'ssl_alp_inventory_item', $args );
		} else {
			// Update term.
            $args = array(
                'name' => $post->post_title,
                'slug' => $this->get_inventory_term_slug( $post ),
            );

            wp_update_term( $term->term_id, 'ssl_alp_inventory_item', $args );
		}

		// Re-enable the filter.
		$this->enable_disallow_insert_term_filter();
	}

	public function get_inventory_term( $post ) {
		$post = get_post( $post );

        if ( is_null( $post ) ) {
			// Invalid post.
			return;
		}

		$slug = $this->get_inventory_term_slug( $post );
		return get_term_by( 'slug', $slug, 'ssl_alp_inventory_item' );
	}

	/**
	 * Get unique slug for the inventory term. This uses the associated
	 * inventory post's ID since this doesn't change even if e.g. its slug does.
	 *
	 * @param WP_Post $post    The inventory post.
	 */
    private function get_inventory_term_slug( $post ) {
        $post = get_post( $post );

        if ( is_null( $post ) ) {
			// Invalid post.
			return;
        }

        return $post->ID;
    }

	/**
	 * Get post from inventory term.
	 *
	 * @param WP_Term $term The inventory term.
	 *
	 * @return WP_Post|null The inventory post, or null if the term is invalid.
	 */
	private function get_post_from_inventory_term( $term ) {
		// The term's slug is the post ID.
		return get_post( $term->slug );
	}

    /**
     * Associate a post in the ssl_alp_inventory custom post type with a corresponding
     * ssl_alp_inventory_item term when created or saved.
     *
     * @param int     $post_id The post ID.
     * @param WP_Post $post    The post object.
     */
    public function associate_inventory_post_with_term( $post_id, $post ) {
		if ( ! get_option( 'ssl_alp_enable_inventory' ) ) {
			// Inventory disabled.
			return;
        }

        if ( 'ssl_alp_inventory' !== $post->post_type ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            // Don't create term for autosaves.
            return;
        }

        if ( 'publish' !== $post->post_status ) {
            // Don't create a term unless the post is being published.
            return;
		}

		// Add or update the associated term.
		$this->update_inventory_item_term( $post );
    }

    /**
     * Delete associated inventory post term before an inventory post is deleted.
     *
     * @param int $post_id The post ID.
     */
    public function delete_associated_inventory_post_term( $post_id ) {
		if ( ! get_option( 'ssl_alp_enable_inventory' ) ) {
			// Inventory disabled.
			return;
		}

        $post = get_post( $post_id );

        if ( is_null( $post ) ) {
			// Invalid post.
			return;
		}

        if ( 'ssl_alp_inventory' !== $post->post_type ) {
            return;
		}

        $term = $this->get_inventory_term( $post );

        if ( ! $term ) {
            // No term to delete.
            return;
		}

		wp_delete_term( $term->term_id, 'ssl_alp_inventory_item' );
		clean_term_cache( array( $term->term_id ), 'ssl_alp_inventory_item' );
    }

	/**
	 * Register the inventory taxonomy and add post type support.
	 */
	public function register_taxonomy() {
		if ( ! get_option( 'ssl_alp_enable_inventory' ) ) {
			// Inventory disabled.
			return;
		}

		// Register new taxonomy so that we can store inventory item and post relationships.
		$args = array(
			'hierarchical'          => false,
			'labels'                => array(
                'name'                       => __( 'Inventory', 'ssl-alp' ),
                'singular_name'              => __( 'Item', 'ssl-alp' ),
                'search_items'               => __( 'Search Items', 'ssl-alp' ),
                'popular_items'              => __( 'Popular Items', 'ssl-alp' ),
                'all_items'                  => __( 'All Items', 'ssl-alp' ),
                'edit_item'                  => __( 'Edit Item', 'ssl-alp' ),
                'update_item'                => __( 'Update Item', 'ssl-alp' ),
                'add_new_item'               => __( 'Add New Item', 'ssl-alp' ),
                'new_item_name'              => __( 'New Item Name', 'ssl-alp' ),
                'separate_items_with_commas' => __( 'Separate items with commas', 'ssl-alp' ),
                'add_or_remove_items'        => __( 'Add or remove items', 'ssl-alp' ),
                'choose_from_most_used'      => __( 'Choose from the most used items', 'ssl-alp' ),
                'not_found'                  => __( 'No items found.', 'ssl-alp' ),
                'no_terms'                   => __( 'No items', 'ssl-alp' ),
            ),
			'public'                => true,
            'show_in_menu'          => false, // Disable term edit page.
			'show_in_rest'          => true,  // Needed for block editor support.
            'show_admin_column'     => true,  // Show associated terms in admin edit screen.
		);

		// Create inventory taxonomy.
		register_taxonomy( 'ssl_alp_inventory_item', $this->supported_post_types, $args );
	}

	/**
	 * Disallow the creation of new terms under normal circumstances.
	 *
	 * This is to avoid users being able to create terms in the inventory
	 * taxonomy directly; terms should only be created when a new inventory post
	 * is created.
	 *
	 * @param string $term     The term.
	 * @param string $taxonomy The taxonomy.
	 *
	 * @return string|WP_Error $term The term, or error.
	 */
	public function disallow_insert_term( $term, $taxonomy ) {
		if ( 'ssl_alp_inventory_item' !== $taxonomy ) {
			return $term;
		}

		// Return an error in all circumstances.
		return new WP_Error(
			'disallow_insert_term',
			__( 'Your role does not have permission to add terms to this taxonomy', 'ssl-alp' )
		);
	}

	/**
	 * Delete invalid inventory when a post is saved.
	 *
	 * Unfortunately there is no way to filter terms before they are set on a post, so this function
	 * deletes them afterwards instead.
	 *
	 * @param int    $object_id Object ID.
	 * @param int    $tt_id     Term taxonomy ID.
	 * @param string $taxonomy  Taxonomy slug.
	 */
	public function reject_invalid_inventory_terms( $object_id, $tt_id, $taxonomy ) {
		if ( 'ssl_alp_inventory_item' !== $taxonomy ) {
			return;
		}

		if ( ! get_option( 'ssl_alp_enable_inventory' ) ) {
			// Inventory disabled.
			return;
		}

		$term = get_term_by( 'term_taxonomy_id', $tt_id, 'ssl_alp_inventory_item' );

		if ( ! $term ) {
			// Nothing to do here.
			return;
		}

		// Check term is valid.
		$inventory_post = $this->get_post_from_inventory_term( $term );

		if ( ! $inventory_post ) {
			// This is not a valid inventory term - delete it.
			wp_delete_term( $term->term_id, 'ssl_alp_inventory_item' );
		}
	}

	/**
	 * Stop users editing or deleting inventory terms.
	 *
	 * @param array   $all_capabilities All user capabilities.
	 * @param mixed   $unused           Unused.
	 * @param array   $args             Capability arguments.
	 * @param WP_User $user             User object.
	 */
	public function filter_user_has_cap( $all_capabilities, $unused, $args, $user ) {
		if ( ! get_option( 'ssl_alp_enable_inventory' ) ) {
			// Inventory disabled.
			return $all_capabilities;
		}

		$requested_capability = $args[0];

		if ( in_array( $requested_capability, array( 'edit_term', 'delete_term' ), true ) ) {
			// Disallow in all circumstances.
			$all_capabilities['edit_term']   = false;
			$all_capabilities['delete_term'] = false;
        }

        return $all_capabilities;
	}

	/**
	 * Filter capabilities of super admins to stop them editing or deleting inventory terms.
	 *
	 * Inventory terms are essential to the correct operation of the inventory system and are
     * managed only by the inventory custom post type.
	 *
	 * @param array  $caps    All capabilities.
	 * @param string $cap     Capability being checked.
	 * @param int    $user_id User ID.
	 * @param array  $args    Capability arguments.
	 */
	public function filter_capabilities( $caps, $cap, $user_id, $args ) {
		// Construct list of capabilities based on post type.
		$filtered_caps = array(
			// Terms.
			'edit_term',
			'delete_term',
		);

		if ( ! in_array( $cap, $filtered_caps, true ) ) {
			// this is not a capability we need to filter.
			return $caps;
		}

		// Get term.
		$term = get_term( $args[0] );

		if ( is_null( $term ) ) {
			return $caps;
		}

		$taxonomy = get_taxonomy( $term->taxonomy );

		if ( 'ssl_alp_inventory_item' === $taxonomy->name ) {
			// Disallow.
			$caps[] = 'do_not_allow';
		}

		return $caps;
	}
}
