/**
 * Revision editor tools.
 *
 * This adds a checkbox to the sidebar when composing or editing a post which
 * when toggled avoids revision count and list from appearing on the post page.
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

    class RevisionDisplayPlugin extends Component {
        render() {
            const { hideRevisions, setHideRevisions } = this.props;

            return el(
                PluginPostStatusInfo,
                {
                    className: 'ssl-alp-hide-revisions-panel'
                },
                el(
                    CheckboxControl,
                    {
                        name: 'ssl_alp_hide_revisions',
                        label: __( 'Hide revisions', 'ssl-alp' ),
                        help: __( 'Do not display revisions on the post page', 'ssl-alp' ),
                        checked: hideRevisions,
                        onChange: ( value ) => {
                            setHideRevisions( value );
                        }
                    }
                )
            );
        }
    }

    const RevisionDisplayPluginHOC = compose( [
        withSelect( ( select ) => {
            const { getEditedPostAttribute } = select( 'core/editor' );

            const editedPostAttributes = getEditedPostAttribute( 'meta' );
            const hideRevisions = editedPostAttributes[ 'ssl_alp_hide_revisions' ];

            return { hideRevisions };
        } ),
        withDispatch( ( dispatch ) => {
            const { editPost } = dispatch( 'core/editor' );

            return {
                setHideRevisions: function( value ) {
                    editPost( {
                        meta: { ssl_alp_hide_revisions: value },
                    } );
                }
            }
        } )
    ])( RevisionDisplayPlugin );

    /**
     * Register sidebar plugin with block editor.
     */
    registerPlugin( 'ssl-alp-hide-revisions-plugin', {
        render: RevisionDisplayPluginHOC
    } );
} )(
	window.wp
);
