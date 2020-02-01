/**
 * Children block.
 *
 * This block uses server side rendering to display page children so that
 * changes are reflected without having to saving the page again.
 *
 * @package ssl-alp
 */

( function( wp ) {
	/**
	 * New block registration function.
	 */
    var registerBlockType = wp.blocks.registerBlockType;

    /**
     * Data selector.
     */
    var withSelect = wp.data.withSelect;

	/**
	 * Elements.
	 */
    var el = wp.element.createElement;
    var Component = wp.element.Component;

    /**
     * Components.
     */
    var Placeholder = wp.components.Placeholder;
    var Spinner = wp.components.Spinner;
    var Disabled = wp.components.Disabled;

    /**
     * Editor tools.
     */
    var ServerSideRender = wp.editor.ServerSideRender;

	/**
	 * Retrieves the translation of text.
	 *
	 * @see https://github.com/WordPress/gutenberg/tree/master/i18n#api
	 */
    var __ = wp.i18n.__;

	const blockIcon = el(
		'svg',
		{
			width: 24,
			height: 24,
		},
		el(
			'path',
			{
				d: "M17.882768 15.114406C18.862527 15.114406 19.65678 15.908659 19.65678 16.888418C19.65678 16.888418 19.65678 18.662429 19.65678 18.662429C19.65678 19.642188 18.862527 20.436441 17.882768 20.436441C17.882768 20.436441 16.108756 20.436441 16.108756 20.436441C15.128998 20.436441 14.334745 19.642188 14.334745 18.662429C14.334745 18.662429 14.334745 16.888418 14.334745 16.888418C14.334745 15.908659 15.128998 15.114406 16.108756 15.114406C16.108756 15.114406 16.108756 13.340394 16.108756 13.340394C16.108756 13.340394 7.2387006 13.340394 7.2387006 13.340394C7.2387006 13.340394 7.2387006 15.114406 7.2387006 15.114406C8.2184598 15.114406 9.0127117 15.908659 9.0127117 16.888418C9.0127117 16.888418 9.0127117 18.662429 9.0127117 18.662429C9.0127117 19.642188 8.2184598 20.436441 7.2387006 20.436441C7.2387006 20.436441 5.4646893 20.436441 5.4646893 20.436441C4.48493 20.436441 3.6906782 19.642188 3.6906782 18.662429C3.6906782 18.662429 3.6906782 16.888418 3.6906782 16.888418C3.6906782 15.908659 4.48493 15.114406 5.4646893 15.114406C5.4646893 15.114406 5.4646893 13.340394 5.4646893 13.340394C5.4646893 12.360636 6.2589412 11.566383 7.2387006 11.566383C7.2387006 11.566383 10.786723 11.566383 10.786723 11.566383C10.786723 11.566383 10.786723 9.7923727 10.786723 9.7923727C9.8069639 9.7923727 9.0127117 8.9981209 9.0127117 8.0183616C9.0127117 8.0183616 9.0127117 6.2443504 9.0127117 6.2443504C9.0127117 5.2645911 9.8069639 4.4703393 10.786723 4.4703393C10.786723 4.4703393 12.560734 4.4703393 12.560734 4.4703393C13.540494 4.4703393 14.334745 5.2645911 14.334745 6.2443504C14.334745 6.2443504 14.334745 8.0183616 14.334745 8.0183616C14.334745 8.9981209 13.540494 9.7923727 12.560734 9.7923727C12.560734 9.7923727 12.560734 11.566384 12.560734 11.566384C12.560734 11.566384 16.108756 11.566384 16.108756 11.566384C17.088516 11.566384 17.882768 12.360636 17.882768 13.340394C17.882768 13.340394 17.882768 15.114406 17.882768 15.114406M5.4646893 16.888418C5.4646893 16.888418 5.4646893 18.662429 5.4646893 18.662429C5.4646893 18.662429 7.2387006 18.662429 7.2387006 18.662429C7.2387006 18.662429 7.2387006 16.888418 7.2387006 16.888418C7.2387006 16.888418 5.4646893 16.888418 5.4646893 16.888418M16.108756 16.888418C16.108756 16.888418 16.108756 18.662429 16.108756 18.662429C16.108756 18.662429 17.882768 18.662429 17.882768 18.662429C17.882768 18.662429 17.882768 16.888418 17.882768 16.888418C17.882768 16.888418 16.108756 16.888418 16.108756 16.888418M10.786723 6.2443504C10.786723 6.2443504 10.786723 8.0183616 10.786723 8.0183616C10.786723 8.0183616 12.560734 8.0183616 12.560734 8.0183616C12.560734 8.0183616 12.560734 6.2443504 12.560734 6.2443504C12.560734 6.2443504 10.786723 6.2443504 10.786723 6.2443504",
            }
		),
    );

    function getEditComponent( blockName, blockTitle ) {
        return class extends Component {
            render() {
                const { className, attributes, children } = this.props;
                const hasChildren = Array.isArray( children ) && children.length;

                if ( ! hasChildren ) {
                    return (
                        el(
                            Placeholder,
                            {
                                icon: blockIcon,
                                label: blockTitle,
                            },
                            // Display spinner until children can be read.
                            ! Array.isArray( children ) ? Spinner() : __( 'No children found.', 'ssl-alp' ),
                        )
                    );
                }

                return (
                    el(
                        ServerSideRender,
                        {
                            className,
                            block: blockName,
                            attributes
                        }
                    )
                );
            }
        };
    };

    const name = 'ssl-alp/page-children';
    const title = __( 'Page Children', 'ssl-alp' );
    const edit = getEditComponent( name, title );

	registerBlockType( name, {
		title: title,

		description: __( 'Displays a list of this page\'s children.', 'ssl-alp' ),

		keywords: [
            __( 'Parent', 'ssl-alp' ),
            __( 'Child', 'ssl-alp' ),
            __( 'Subpage', 'ssl-alp' ),
		],

		icon: blockIcon,

		category: 'widgets',

		supports: {
			// Removes support for editing in HTML mode.
			html: false,
		},

		edit: withSelect( ( select, props ) => {
            const { attributes } = props;
            const { getEntityRecords, getPostType } = select( 'core' );
            const { getCurrentPostId, getCurrentPostType } = select( 'core/editor' );
            let selectedPostType = getPostType( getCurrentPostType() ) || {};

            const childrenQuery = {
                per_page: -1,
                parent: getCurrentPostId(),
                orderby: 'menu_order',
                order: 'asc',
            };

			return {
                children: getEntityRecords( 'postType', selectedPostType.slug, childrenQuery ) || [],
                selectedPostType,
			};
		} )( edit ),

		save() {
            // Force server side rendering callback to be used.
            return null;
        }
	} );
} )(
	window.wp
);
