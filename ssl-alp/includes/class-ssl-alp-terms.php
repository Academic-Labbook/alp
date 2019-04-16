<?php
/**
 * Term tools.
 *
 * Based on 'Term Management Tools' by scribu: https://github.com/scribu/wp-term-management-tools.
 *
 * @package ssl-alp
 */

if ( ! defined( 'WPINC' ) ) {
	// Prevent direct access.
	exit;
}

/**
 * Term functionality.
 */
class SSL_ALP_Terms extends SSL_ALP_Module {
    /**
     * Enqueue scripts.
     */
    public function enqueue_admin_scripts() {
        global $taxonomy;

        if ( ! $this->user_can_manage_terms() ) {
            return;
        }

        wp_enqueue_script(
            'ssl-alp-term-management-tools',
            SSL_ALP_BASE_URL . 'js/admin-term-management.js',
            array( 'jquery' ),
            $this->get_version(),
            true
        );

		wp_localize_script(
            'ssl-alp-term-management-tools',
            'ssl_alp_term_management_actions',
            $this->get_actions( $taxonomy )
        );
    }

	/**
	 * Register hooks.
	 */
	public function register_hooks() {
		$loader = $this->get_loader();

		// Add bulk term tools to term edit screen.
        $loader->add_action( 'load-edit-tags.php', $this, 'add_term_management_tools' );

        // Handle admin notices.
        $loader->add_action( 'admin_notices', $this, 'print_admin_notices' );

        // Print hidden inputs for term management list.
        $loader->add_action( 'admin_footer', $this, 'print_inputs' );
    }

    /**
     * Check if the current user can manage terms.
     */
    private function user_can_manage_terms() {
        if ( in_array( 'taxonomy', $_REQUEST ) ) {
            $taxonomy = $_REQUEST['taxonomy'];
        } else {
            $taxonomy = 'post_tag';
        }

        $taxonomy = get_taxonomy( $taxonomy );

        if ( ! $taxonomy ) {
            return false;
        }

        return current_user_can( $taxonomy->cap->manage_terms );
    }

    /**
     * Get action descriptions.
     *
     * @param string $taxonomy The taxonomy being edited.
     * @return array Array of actions and their descriptions.
     */
	private function get_actions( $taxonomy ) {
		$actions = array(
			'merge' => __( 'Merge', 'ssl-alp' ),
        );

		if ( is_taxonomy_hierarchical( $taxonomy ) ) {
            // Add set parent action.
			$actions = array_merge(
                array(
				    'set_parent' => __( 'Set Parent', 'ssl-alp' ),
                ),
                $actions
            );
        }

        return $actions;
    }

    /**
     * Add term management tools to term edit screen.
     */
    public function add_term_management_tools() {
        if ( ! $this->user_can_manage_terms() ) {
            return;
        }

		$args = array(
			'taxonomy'    => 'post_tag',
			'delete_tags' => false,
			'action'      => false,
			'action2'     => false
        );

		$data     = shortcode_atts( $args, $_REQUEST );
        $taxonomy = get_taxonomy( $data['taxonomy'] );

		if ( ! $taxonomy ) {
            return;
        }

        $action = false;

		foreach ( array( 'action', 'action2' ) as $key ) {
			if ( $data[ $key ] && '-1' !== $data[ $key ] ) {
				$action = $data[ $key ];
			}
        }

		if ( ! $action ) {
            return;
        }

        // Handle submitted actions.
		$this->delegate_handling( $action, $data['taxonomy'], $data['delete_tags'] );
    }

    /**
     * Handle submitted term management request by passing to appropriate function.
     *
     * @param string $action   Action string.
     * @param string $taxonomy Taxonomy being edited.
     * @param array  $term_ids Term IDs being managed.
     */
	protected function delegate_handling( $action, $taxonomy, $term_ids ) {
		if ( empty( $term_ids ) ) {
            return;
        }

        $success = false;

        // Determine action.
		foreach ( array_keys( $this->get_actions( $taxonomy ) ) as $key ) {
			if ( 'bulk_' . $key === $action ) {
                check_admin_referer( 'bulk-tags' );

                // Call action handler.
                $success = call_user_func( array( $this, "handle_{$key}" ), $term_ids, $taxonomy );

				break;
			}
        }

		if ( ! $success ) {
            return;
        }

        $referer = wp_get_referer();

        // Send user back to where they came from.
		if ( $referer && false !== strpos( $referer, 'edit-tags.php' ) ) {
			$location = $referer;
		} else {
			$location = add_query_arg( 'taxonomy', $taxonomy, 'edit-tags.php' );
        }

		if ( isset( $_REQUEST['post_type'] ) && 'post' !== $_REQUEST['post_type'] ) {
			$location = add_query_arg( 'post_type', $_REQUEST['post_type'], $location );
        }

        wp_redirect(
            add_query_arg(
                'message',
                $success ? 'ssl_alp_term_updated' : 'ssl_alp_term_error',
                $location
            )
        );

		exit;
    }

    /**
     * Handle merging multiple terms into one.
     *
     * @param array  $term_ids Terms to merge.
     * @param string $taxonomy Taxonomy.
     */
	protected function handle_merge( $term_ids, $taxonomy ) {
        if ( ! isset( $_REQUEST['merge'] ) ) {
            return false;
        }

        $merge_id   = $_REQUEST['merge'];
        $merge_term = get_term( $merge_id, $taxonomy );

		if ( is_null( $merge_term ) || is_wp_error( $merge_term ) ) {
            // Specified term is invalid.
            return false;
        }

		foreach ( $term_ids as $term_id ) {
			if ( absint( $term_id ) === absint( $merge_term->term_id ) ) {
                // This term is the one the others are being merged into.
                continue;
            }

            // Merge term's objects into the target then delete the term.
            wp_delete_term(
                $term_id,
                $taxonomy,
                array(
                    'default'       => $merge_term->term_id,
                    'force_default' => true
                )
            );
        }

		return true;
    }

    /**
     * Handle setting a parent for a hierarchical term.
     *
     * @param array  $term_ids Terms to set the parent of.
     * @param string $taxonomy Taxonomy.
     */
	protected function handle_set_parent( $term_ids, $taxonomy ) {
        if ( ! isset( $_REQUEST['parent'] ) ) {
            return false;
        }

        $parent_id = $_REQUEST['parent'];

		foreach ( $term_ids as $term_id ) {
			if ( absint( $term_id ) === absint( $parent_id ) ) {
                // This term is the parent.
                continue;
            }

            // Set the term's parent.
            $ret = wp_update_term(
                $term_id,
                $taxonomy,
                array(
                    'parent' => $parent_id
                )
            );

			if ( is_wp_error( $ret ) ) {
                return false;
            }
        }

		return true;
	}

    /**
     * Print admin notices.
     */
	public function print_admin_notices() {
        if ( ! $this->user_can_manage_terms() ) {
            return;
        }

		if ( ! isset( $_GET['message'] ) ) {
			return;
		}

		switch ( $_GET['message'] ) {
			case  'ssl_alp_term_updated':
				echo '<div class="notice notice-success is-dismissible">';
				echo '<p>' . esc_html__( 'Terms updated.', 'ssl-alp' ) . '</p>';
				echo '</div>';
                break;
            case  'ssl_alp_term_error':
				echo '<div class="notice notice-error is-dismissible">';
				echo '<p>' . esc_html__( 'Terms not updated.', 'ssl-alp' ) . '</p>';
				echo '</div>';
				break;
		}
    }

    /**
     * Output the form inputs shown in the term management screen.
     */
	public function print_inputs() {
        global $taxonomy;

        if ( ! $this->user_can_manage_terms() ) {
            return;
        }

		foreach ( array_keys( $this->get_actions( $taxonomy ) ) as $key ) {
            echo "<div id='ssl-alp-term-management-action-$key' style='display:none'>\n";

            call_user_func( array( $this, "input_{$key}" ), $taxonomy );

			echo "</div>\n";
		}
    }

    /**
     * Output dropdown box with categories available as targets for merge.
     *
     * @param string $taxonomy Taxonomy.
     */
	public function input_merge( $taxonomy ) {
        echo '<label for="ssl-alp-term-management-merge">';
        esc_html_e( 'into: ', 'ssl-alp' );
        echo '</label>';

		wp_dropdown_categories(
            array(
                'hide_empty'       => 0,
                'hide_if_empty'    => false,
                'name'             => 'merge',
                'id'               => 'ssl-alp-term-management-merge',
                'orderby'          => 'name',
                'taxonomy'         => $taxonomy,
                'hierarchical'     => true,
                'show_option_none' => __( 'None', 'ssl-alp' ),
            )
        );
    }

    /**
     * Output dropdown box with categories available as targets for setting parent.
     *
     * @param string $taxonomy Taxonomy.
     */
	public function input_set_parent( $taxonomy ) {
        echo '<label for="ssl-alp-term-management-set-parent">';
        esc_html_e( 'to: ', 'ssl-alp' );
        echo '</label>';

		wp_dropdown_categories(
            array(
                'hide_empty'       => 0,
                'hide_if_empty'    => false,
                'name'             => 'parent',
                'id'               => 'ssl-alp-term-management-set-parent',
                'orderby'          => 'name',
                'taxonomy'         => $taxonomy,
                'hierarchical'     => true,
                'show_option_none' => __( 'None', 'ssl-alp' ),
            )
        );
	}
}
