var el = wp.element.createElement;
var __ = wp.i18n.__;
var Component = wp.element.Component;
var PluginPostStatusInfo = wp.editPost.PluginPostStatusInfo;
var TextControl = wp.components.TextControl;
var registerPlugin = wp.plugins.registerPlugin;
var subscribe = wp.data.subscribe;
var registerStore = wp.data.registerStore;

// initial value
wp.data.select('core/editor').ssl_alp_edit_summary = '';

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
        let lastRevisionId;

        subscribe(() => {
            // get latest revision ID
            let newRevisionId = wp.data.select('core/editor').getCurrentPostLastRevisionId();

            if (newRevisionId !== null && lastRevisionId !== null && newRevisionId !== lastRevisionId) {
                // a new revision has been created

                // get edit message
                let editSummary = wp.data.select('core/editor').ssl_alp_edit_summary;

                // set this message in the revision
                this.setRevisionEditSummary( newRevisionId, editSummary );

                // clear the edit summary
                wp.data.select('core/editor').ssl_alp_edit_summary = '';
            }

            lastRevisionId = newRevisionId;
        });

        /**
         * Set edit summary when it changes elsewhere.
         *
         * This is used to allow the edit summary textbox to be cleared when its contents is sent
         * to WordPress.
         */

        // previous (initial) edit summary value
        let lastEditSummary = '';

        subscribe(() => {
            // latest edit summary
            let newEditSummary = wp.data.select('core/editor').ssl_alp_edit_summary;

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

        wp.apiRequest( { path: `/ssl-alp/v1/update-revision-meta?id=${revisionId}`, method: 'POST', data: payload } ).then(
            ( data ) => {
                return data;
            },
            ( err ) => {
                return err;
            }
        );
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
                        wp.data.select('core/editor').ssl_alp_edit_summary = value;
                    }
                }
            )
        );
    }
}

/**
 * Register sidebar plugin with block editor.
 */
registerPlugin( 'ssl-alp-edit-summary-plugin', {
	render: EditSummaryPlugin
} );
