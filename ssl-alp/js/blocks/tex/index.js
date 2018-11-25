( function() {
	var __                = wp.i18n.__; // The __() function for internationalization.
	var createElement     = wp.element.createElement; // The wp.element.createElement() function to create elements.
	var registerBlockType = wp.blocks.registerBlockType; // The registerBlockType() function to register blocks.
	var PlainText          = wp.editor.PlainText; // For creating editable elements.

	registerBlockType(
		'ssl-alp/tex-block',
		{
			title: __( 'TeX', 'ssl-alp' ),
			icon: 'unlock',
            category: 'formatting',
            
			attributes: {
				content: {
                    type: 'string',
                    source: 'text',
                    selector: 'code'
				},
            },

            supports: {
                html: false
            },

			// Defines the block within the editor.
			edit: function( props ) {
				var content = props.attributes.content;
				var focus = props.focus;

				function onChangeContent( updatedContent ) {
					props.setAttributes( { content: updatedContent } );
				}

				return createElement(
					PlainText,
					{
                        value: content,
						style: {
                            "font-family": "monospace",
                            "padding": "0.8em 1em",
                            "border": "1px solid",
                            "border-radius": "4px"
                        },
						onChange: onChangeContent,
						focus: focus,
             			onFocus: props.setFocus
					},
				);
			},

			// Defines the saved block.
			save: function( props ) {
				var content = props.attributes.content;

				return createElement(
					'p',
					{
						className: props.className,
					},
					content
				);
			},
		}
	);
})();
