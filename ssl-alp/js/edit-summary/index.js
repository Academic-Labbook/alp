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
    // edit summary initially empty
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

const editSummaryStore = registerStore( 'ssl-alp/edit-summary', {
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
            // create textbox value that is initially empty
            editSummary: ''
        }

        /**
         * Send the edit summary to WordPress when a new revision is created.
         */

        // previous (initial) revision ID
        let lastRevisionId = this.props.lastRevisionId;

        wp.data.subscribe(() => {
            if ( this.props.isPublished && this.props.isSaving && !this.props.isAutosaving && !this.props.isPublishing ) {
                // User is saving update to published post.

                // get latest revision ID
                let newRevisionId = this.props.lastRevisionId;

                if ( newRevisionId !== null && lastRevisionId !== null && newRevisionId !== lastRevisionId ) {
                    // a new revision has been created

                    // get edit message
                    let editSummary = this.props.getEditSummary();

                    // set this message in the revision
                    this.setRevisionEditSummary( newRevisionId, editSummary );

                    // update revision
                    lastRevisionId = newRevisionId;

                    if ( this.props.isSaving && !this.props.isAutosaving ) {
                        // clear the edit summary
                        this.props.resetEditSummary();
                    }
                }
            } else if ( this.props.isPublishing ) {
                // User is publishing a new post.

                // set last revision id
                lastRevisionId = this.props.lastRevisionId;
            }
        });

        /**
         * Set edit summary when it changes elsewhere.
         *
         * This is used to allow the edit summary textbox to be cleared when its contents is sent
         * to WordPress.
         */

        // previous (initial) edit summary value
        let lastEditSummary = '';

        wp.data.subscribe(() => {
            // latest edit summary
            let newEditSummary = this.props.getEditSummary();

            if ( newEditSummary !== null && lastEditSummary !== null && newEditSummary !== lastEditSummary ) {
                // an external change has been made to the edit summary
                this.setState( {
                    editSummary: newEditSummary
                });
            }

            lastEditSummary = newEditSummary;
        });
    }

    /**
     * Set revision edit summary via REST.
     */
    setRevisionEditSummary( revisionId, editSummary ) {
        let payload = {
            key: 'ssl_alp_edit_summary',
            value: editSummary
        };

        wp.apiRequest( {
            path: `/ssl-alp/v1/update-revision-meta?id=${revisionId}`,
            method: 'POST',
            data: payload
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
        if ( !this.props.isPublished ) {
            // don't render edit summary box on new posts
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
                    spellcheck: true,
                    maxlength: 100,
                    value: this.state.editSummary,
                    onChange: ( value ) => {
                        // update text
                        this.setState( {
                            editSummary: value
                        });

                        // set message in datastore
                        this.props.setEditSummary( value );
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
            setEditSummary: setEditSummary,
            resetEditSummary: resetEditSummary,
        };
    } )
])( EditSummaryPlugin );

/**
 * Register sidebar plugin with block editor.
 */
registerPlugin( 'ssl-alp-edit-summary-plugin', {
	render: EditSummaryPluginHOC
} );
