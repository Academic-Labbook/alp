( function( wp ) {
    const { createElement, Fragment } = wp.element;
    const { registerFormatType, toggleFormat } = wp.richText;
    const { RichTextToolbarButton, RichTextShortcut } = wp.editor;

	/**
	 * Retrieves the translation of text.
	 * @see https://github.com/WordPress/gutenberg/tree/master/i18n#api
	 */
    var __ = wp.i18n.__;

    const name = "tex";
    const title = __( "TeX", "ssl-alp" );
    const character = "m";
    const type = `ssl-alp/${ name }`;

    const renderSpans = () => {
        const texspans = document.querySelectorAll(".ssl-alp-tex-inline");

        for (let i = 0; i < texspans.length; i++) {
            const texspan = texspans[i];
            const rendered = document.createElement("span");

            try {
                katex.render(
                    texspan.textContent,
                    rendered,
                    {
                        displayMode: false,
                        throwOnError: false
                    }
                );
            } catch(e) {
                rendered.style.color = "red";
                rendered.textContent = e.message;
            }

            texspan.parentNode.replaceChild(rendered, texspan);
        }
    }

    registerFormatType( type, {
        type,
        title: title,
        tagName: "tex",
        className: "ssl-alp-tex-inline",

        edit( { isActive, value, onChange } ) {
            const onToggle = () => {
                onChange( toggleFormat( value, { type: type } ) );
                renderSpans();
            }

            return (
                createElement( Fragment, null,
                    createElement( RichTextShortcut, {
                        type: 'primary',
                        character,
                        onUse: onToggle
                    } ),
                    createElement( RichTextToolbarButton, {
                        title,
                        icon: "arrow-up",
                        onClick: onToggle,
                        isActive: isActive,
                        shortcutType: 'primary',
                        shortcutCharacter: character,
                        className: `toolbar-button-with-text toolbar-button__advanced-${ name }`
                    } ) )
            );
        }
    } );
} )(
	window.wp
);
