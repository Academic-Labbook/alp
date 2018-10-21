var el = wp.element.createElement;
var __ = wp.i18n.__;
var Component = wp.element.Component;
var PluginPostStatusInfo = wp.editPost.PluginPostStatusInfo;
var TextControl = wp.components.TextControl;
var registerPlugin = wp.plugins.registerPlugin;
var withSelect = wp.data.withSelect;

class SSL_ALP_Edit_Summary_Plugin extends Component {
    constructor() {
        super( ...arguments );

        this.state = {
            key: 'ssl_alp_edit_summary',
            // edit summary always initially empty
            value: ''
        }
    }

    /**
     * Update post meta using REST API.
     */
    static getDerivedStateFromProps( nextProps, state ) {
        if ( nextProps.isSaving && !nextProps.isAutoSaving && !nextProps.isPublishing ) {
            if ( nextProps.revisionId ) {
                wp.apiRequest( { path: `/ssl-alp/v1/update-meta?id=${nextProps.revisionId}`, method: 'POST', data: state } ).then(
                    ( data ) => {
                        return data;
                    },
                    ( err ) => {
                        return err;
                    }
                );
            }
        }
    }

    render() {
        return el(
            PluginPostStatusInfo,
            {
                className: 'ssl-alp-edit-summary-panel'
            },
            el(
                TextControl,
                {
                    name: 'ssl_alp_revision_post_edit_summary',
                    label: __( 'Edit summary', 'ssl-alp' ),
                    help: __( 'Briefly summarise your changes', 'ssl-alp' ),
                    spellcheck: 'true',
                    maxlength: 100,
                    value: this.state.value,
                    onChange: ( value ) => {
                        this.setState( {
                            value
                        })
                    }
                }
            )
        );
    }
}

/**
 * Give current post IDs and status flags to SslAlpEditSummaryPlugin object.
 */
const HOC = withSelect( ( select, { forceIsSaving } ) => {
    const {
        getCurrentPostId,
        getCurrentPostLastRevisionId,
        isSavingPost,
        isPublishingPost,
        isAutosavingPost
    } = select( 'core/editor' );
    
	return {
        postId: getCurrentPostId(),
        revisionId: getCurrentPostLastRevisionId(),
        isSaving: forceIsSaving || isSavingPost(),
        isAutoSaving: isAutosavingPost(),
        isPublishing: isPublishingPost()
	};
} )( SSL_ALP_Edit_Summary_Plugin );

registerPlugin( 'ssl-alp-edit-summary-plugin', {
	render: HOC
} );