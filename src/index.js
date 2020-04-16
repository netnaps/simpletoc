const { __, setLocaleData } = wp.i18n;
const { registerBlockType } = wp.blocks;
const listulicon  = wp.element.createElement('svg',
	{
		width: 20,
		height: 20
	},
	wp.element.createElement( 'path',
		{
			d: "M5.5 7C4.67 7 4 6.33 4 5.5 4 4.68 4.67 4 5.5 4 6.32 4 7 4.68 7 5.5 7 6.33 6.32 7 5.5 7zM8 5h9v1H8V5zm-2.5 7c-.83 0-1.5-.67-1.5-1.5C4 9.68 4.67 9 5.5 9c.82 0 1.5.68 1.5 1.5 0 .83-.68 1.5-1.5 1.5zM8 10h9v1H8v-1zm-2.5 7c-.83 0-1.5-.67-1.5-1.5 0-.82.67-1.5 1.5-1.5.82 0 1.5.68 1.5 1.5 0 .83-.68 1.5-1.5 1.5zM8 15h9v1H8v-1z"
		}
	)
);

registerBlockType( 'simpletoc/toc', {
	title: __( 'SimpleTOC', 'simpletoc' ),
	icon: listulicon,
	category: 'layout',
	edit: function ( props ) {
		return buildTOC( props );
    },
	save: function ( props ) {
		return buildTOC( props );
    },
} );

function buildTOC(props){

	const data = wp.data.select( 'core/block-editor' );
	const blocks = data.getBlocks();
	const headings = blocks.map( ( item, index ) => {
		if ( item.name === 'core/heading' ) {
			var slug = '';
			var hashslug = '';
			var blockId = ( item.clientId );

			slug = item.attributes.content.toSlug();
			wp.data.dispatch( 'core/editor' ).updateBlockAttributes( blockId, { anchor: slug } );
			hashslug = '#' + slug;

			return <li><a href={hashslug}>{item.attributes.content}</a></li>;
		}
	} );

	return <p className={ props.className }>
			<h2 class="simpletoc-title">{ __( 'Table of Contents', 'simpletoc' ) }</h2>
			<ul class="simpletoc">
					{headings}
			</ul>
			</p>;
}

String.prototype.toSlug = function ()
{
	var str = this;
	str = str.replace(/^\s+|\s+$/g, ''); // trim
	str = str.toLowerCase();

	// remove accents, swap ñ for n, etc
	var from = "àáäâèéëêìíïîòóöôùúüûñçěščřžýúůďťň·/_,:;";
	var to   = "aaaaeeeeiiiioooouuuuncescrzyuudtn------";

	for (var i=0, l=from.length ; i<l ; i++)
	{
		str = str.replace(new RegExp(from.charAt(i), 'g'), to.charAt(i));
	}

	str = str.replace('.', '-') // replace a dot by a dash
		.replace(/[^a-z0-9 -]/g, '') // remove invalid chars
		.replace(/\s+/g, '-') // collapse whitespace and replace by a dash
		.replace(/-+/g, '-') // collapse dashes
		.replace( /\//g, '' ); // collapse all forward-slashes

	return str;
}
