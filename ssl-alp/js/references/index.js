/**
 * Cross-reference editor tools.
 *
 * This adds a checkbox to the sidebar when composing or editing a post which
 * avoids cross-references from appearing under the post.
 *
 * @package ssl-alp
 */

( function( wp ) {
    const el = wp.element.createElement;
    const __ = wp.i18n.__;
    const Component = wp.element.Component;
    const PluginPostStatusInfo = wp.editPost.PluginPostStatusInfo;
    const CheckboxControl = wp.components.CheckboxControl;
    const registerPlugin = wp.plugins.registerPlugin;
    const compose = wp.compose.compose;
    const withSelect = wp.data.withSelect;
    const withDispatch = wp.data.withDispatch;

    class CrossReferencePlugin extends Component {
        render() {
            const { hideCrossReferences, setHideCrossReferences } = this.props;

            return el(
                PluginPostStatusInfo,
                {
                    className: 'ssl-alp-hide-crossreferences-panel'
                },
                el(
                    CheckboxControl,
                    {
                        name: 'ssl_alp_hide_crossreferences',
                        label: __( 'Hide cross-references', 'ssl-alp' ),
                        help: __( 'Do not display posts linked to/from this one on the post page', 'ssl-alp' ),
                        checked: hideCrossReferences,
                        onChange: ( value ) => {
                            setHideCrossReferences( value );
                        }
                    }
                )
            );
        }
    }

    const CrossReferencePluginHOC = compose( [
        withSelect( ( select ) => {
            const { getEditedPostAttribute } = select( 'core/editor' );

            const editedPostAttributes = getEditedPostAttribute( 'meta' );
            const hideCrossReferences = editedPostAttributes[ 'ssl_alp_hide_crossreferences_to' ];

            return { hideCrossReferences };
        } ),
        withDispatch( ( dispatch ) => {
            const { editPost } = dispatch( 'core/editor' );

            return {
                setHideCrossReferences: function( value ) {
                    editPost( {
                        meta: { ssl_alp_hide_crossreferences_to: value },
                    } );
                }
            }
        } )
    ])( CrossReferencePlugin );

    /**
     * Register sidebar plugin with block editor.
     */
    registerPlugin( 'ssl-alp-hide-crossreferences-plugin', {
        render: CrossReferencePluginHOC
    } );
} )(
	window.wp
);
