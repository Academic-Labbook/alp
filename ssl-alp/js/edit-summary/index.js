var el = wp.element.createElement;
var __ = wp.i18n.__;
var Component = wp.element.Component;
var PluginPostStatusInfo = wp.editPost.PluginPostStatusInfo;
var TextControl = wp.components.TextControl;
var registerPlugin = wp.plugins.registerPlugin;
var subscribe = wp.data.subscribe;


class EditSummaryPlugin extends Component {
    constructor() {
        super( ...arguments );

        this.state = {
            key: 'ssl_alp_edit_summary',
            // edit summary always initially empty
            value: ''
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
                    name: 'ssl_alp_edit_summary',
                    label: __( 'Edit summary', 'ssl-alp' ),
                    help: __( 'Briefly summarise your changes', 'ssl-alp' ),
                    spellcheck: true,
                    maxlength: 100,
                    value: this.state.value,
                    onChange: ( value ) => {
                        this.setState( {
                            value
                        })

                        // set message in datastore
                        wp.data.select('core/editor').ssl_alp_edit_summary = value;
                    }
                }
            )
        );
    }
}

/**
 * Set revision edit summary via REST.
 */
const setRevisionEditSummary = ( revisionId, editSummary ) => {
    var payload = {
        key: 'ssl_alp_edit_summary',
        value: editSummary
    };

    wp.apiRequest( { path: `/ssl-alp/v1/update-meta?id=${revisionId}`, method: 'POST', data: payload } ).then(
        ( data ) => {
            return data;
        },
        ( err ) => {
            return err;
        }
    );
}

/**
 * Subscribe to changes to the data store.
 *
 * https://wordpress.org/gutenberg/handbook/designers-developers/developers/packages/packages-data/#subscribe-function
 */
var lastRevisionId = {};

const unssubscribe = subscribe(() => {
    // get last revision ID
    var newRevisionId = wp.data.select('core/editor').getCurrentPostLastRevisionId();

    if (newRevisionId !== null && lastRevisionId !== null && lastRevisionId !== newRevisionId) {
        // a new revision has been created

        // get edit message
        var editSummary = wp.data.select('core/editor').ssl_alp_edit_summary;

        // set this message in the revision
        setRevisionEditSummary( newRevisionId, editSummary );
    }

    lastRevisionId = newRevisionId;
});

/**
 * Register sidebar plugin with block editor.
 */
registerPlugin( 'ssl-alp-edit-summary-plugin', {
	render: EditSummaryPlugin
} );
