/**
 * Edit summary editor tools.
 *
 * This adds a textbox to the sidebar when editing an existing post which allows
 * the user to specify an edit summary.
 *
 * @package ssl-alp
 */

( function( wp ) {
    const el = wp.element.createElement;
    const __ = wp.i18n.__;
    const Component = wp.element.Component;
    const PluginPostStatusInfo = wp.editPost.PluginPostStatusInfo;
    const TextControl = wp.components.TextControl;
    const registerPlugin = wp.plugins.registerPlugin;
    const registerStore = wp.data.registerStore;
    const compose = wp.compose.compose;
    const withSelect = wp.data.withSelect;
    const withDispatch = wp.data.withDispatch;

    const DEFAULT_STATE = {
        // Edit summary initially empty.
        editSummary: ''
    };

    const actions = {
        setEditSummary( editSummary ) {
            return {
                type: 'SET_EDIT_SUMMARY',
                editSummary: editSummary
            };
        },

        resetEditSummary() {
            return {
                type: 'SET_EDIT_SUMMARY',
                editSummary: DEFAULT_STATE.editSummary
            };
        }
    };

    registerStore( 'ssl-alp/edit-summary', {
        reducer( state = DEFAULT_STATE, action ) {
            switch ( action.type ) {
                case 'SET_EDIT_SUMMARY':
                    return {
                        ...state,
                        editSummary: action.editSummary
                    };
            }

            return state;
        },

        actions,

        selectors: {
            getEditSummary( state ) {
                return state.editSummary;
            },
        },
    } );

    class EditSummaryPlugin extends Component {
        constructor() {
            super( ...arguments );

            this.state = {
                // Create textbox value that is initially empty.
                editSummary: '',
            }

            /**
             * Send the edit summary to WordPress when a new revision is created.
             */

            // Previous (initial) revision ID.
            let { lastRevisionId } = this.props;

            wp.data.subscribe( () => {
                const {
                    isPublished,
                    isPublishing,
                    isSaving,
                    isAutosaving,
                    getEditSummary,
                    resetEditSummary,
                } = this.props;

                if ( isPublished && isSaving && ! isAutosaving && ! isPublishing ) {
                    // User is saving update to published post.

                    // Get latest revision ID.
                    let newRevisionId = this.props.lastRevisionId;

                    if ( newRevisionId !== null && newRevisionId !== lastRevisionId ) {
                        // A new revision has been created.

                        // Get edit message.
                        const editSummary = getEditSummary();

                        // Set this message in the revision.
                        this.setRevisionEditSummary( newRevisionId, editSummary );

                        // Update revision.
                        lastRevisionId = newRevisionId;

                        if ( isSaving && ! isAutosaving ) {
                            // Clear the edit summary.
                            resetEditSummary();
                        }
                    }
                } else if ( isPublishing ) {
                    // User is publishing a new post.

                    // Set last revision id.
                    lastRevisionId = this.props.lastRevisionId;
                }
            } );

            /**
             * Set edit summary when it changes elsewhere.
             *
             * This is used to allow the edit summary textbox to be cleared when its contents is sent
             * to WordPress.
             */

            // Previous (initial) edit summary value.
            let lastEditSummary = '';

            wp.data.subscribe( () => {
                const { getEditSummary } = this.props;

                // Latest edit summary.
                const newEditSummary = getEditSummary();

                if ( newEditSummary !== null && lastEditSummary !== null && newEditSummary !== lastEditSummary ) {
                    // An external change has been made to the edit summary.
                    this.setState( {
                        editSummary: newEditSummary,
                    } );
                }

                lastEditSummary = newEditSummary;
            });
        }

        /**
         * Set revision edit summary via REST.
         */
        setRevisionEditSummary( revisionId, editSummary ) {
            const payload = {
                post_id: revisionId,
                key: 'ssl_alp_edit_summary',
                value: editSummary,
            };

            wp.apiRequest( {
                path: '/ssl-alp/v1/update-revision-meta',
                method: 'POST',
                data: payload,
            } ).then(
                ( data ) => {
                    return data;
                },
                ( err ) => {
                    return err;
                }
            );
        }

        render() {
            const { isPublished, setEditSummary } = this.props;

            if ( ! isPublished ) {
                // Don't render edit summary box on new posts.
                return null;
            }

            return el(
                PluginPostStatusInfo,
                {
                    className: 'ssl-alp-edit-summary-panel'
                },
                el(
                    TextControl,
                    {
                        name: 'ssl_alp_edit_summary',
                        label: __( 'Edit summary', 'ssl-alp' ),
                        help: __( 'Briefly summarise your changes', 'ssl-alp' ),
                        spellCheck: true,
                        maxLength: 100,
                        value: this.state.editSummary,
                        onChange: ( value ) => {
                            // Update text.
                            this.setState( {
                                editSummary: value
                            });

                            // Set message in datastore.
                            setEditSummary( value );
                        }
                    }
                )
            );
        }
    }

    /**
     * Wrap a higher-order component around plugin to catch post update events.
     */
    const EditSummaryPluginHOC = compose( [
        withSelect( ( select, { forceIsSaving } ) => {
            const {
                isCurrentPostPublished,
                isPublishingPost,
                isSavingPost,
                isAutosavingPost,
                getCurrentPostLastRevisionId,
            } = select( 'core/editor' );

            const {
                getEditSummary
            } = select( 'ssl-alp/edit-summary' );

            return {
                isPublished: isCurrentPostPublished(),
                isPublishing: isPublishingPost(),
                isSaving: forceIsSaving || isSavingPost(),
                isAutosaving: isAutosavingPost(),
                lastRevisionId: getCurrentPostLastRevisionId(),
                getEditSummary: getEditSummary,
            };
        } ),
        withDispatch( ( dispatch ) => {
            const {
                setEditSummary,
                resetEditSummary,
            } = dispatch( 'ssl-alp/edit-summary' );

            return {
                setEditSummary,
                resetEditSummary,
            };
        } )
    ])( EditSummaryPlugin );

    /**
     * Register sidebar plugin with block editor.
     */
    registerPlugin( 'ssl-alp-edit-summary-plugin', {
        render: EditSummaryPluginHOC
    } );
} )(
	window.wp
);
