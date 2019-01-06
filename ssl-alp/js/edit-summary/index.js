var el = wp.element.createElement;
var __ = wp.i18n.__;
var Component = wp.element.Component;
var PluginPostStatusInfo = wp.editPost.PluginPostStatusInfo;
var TextControl = wp.components.TextControl;
var registerPlugin = wp.plugins.registerPlugin;
var compose = wp.compose.compose;
var withSelect = wp.data.withSelect;
var withDispatch = wp.data.withDispatch;

function EditSummary({ summary, onUpdate }) {
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
                spellcheck: 'true',
                maxlength: 100,
                value: summary,
                onChange: (value) => {
                    onUpdate( value );
                }
            }
        )
    );
}

// group together two actions
const ssl_alp_edit_summary_plugin = compose([
    withSelect((select) => {
        return {
            summary: select('core/editor').getEditedPostAttribute('meta').ssl_alp_edit_summary,
        };
    }),
    withDispatch((dispatch) => ({
        onUpdate(value) {
            const currentMeta = wp.data.select( 'core/editor' ).getEditedPostAttribute( 'meta' );
            const newMeta = {
                ...currentMeta,
                ssl_alp_edit_summary: value
            };
            dispatch('core/editor').editPost({ meta: newMeta });
        },
    })),
  ])( EditSummary );

registerPlugin( 'ssl-alp-edit-summary-plugin', {
	render: ssl_alp_edit_summary_plugin
} );
