var el = wp.element.createElement;
var TextControl = wp.components.TextControl;
var Fragment = wp.element.Fragment;
var __ = wp.i18n.__;
var registerPlugin = wp.plugins.registerPlugin;
var PluginPostStatusInfo = wp.editPost.PluginPostStatusInfo;

function MyTextBox({}) {
	return (
        <Fragment>
            <PluginPostStatusInfo>
                <TextControl
                    label={ __( 'Hi!' ) }
                    value={ 'Hi!' }
                />
            </PluginPostStatusInfo>
        </Fragment>
	)
}

const MyPostStatusInfoPlugin = compose()(MyTextBox);

registerPlugin( 'my-post-status-info-plugin', {
	render: MyPostStatusInfoPlugin
} );