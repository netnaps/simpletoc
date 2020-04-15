const { __, setLocaleData } = wp.i18n;
const { registerBlockType } = wp.blocks;

const listul  = wp.element.createElement('svg',
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
import ServerSideRender from '@wordpress/server-side-render';

registerBlockType( 'simpletoc/toc', {
	title: __( 'SimpleTOC', 'simpletoc' ),
	icon: listul,
	category: 'layout',
	edit: function( props ) {
			const data = wp.data.select( 'core/block-editor' );
			const blocks = data.getBlocks();
			//wp.data.dispatch( 'core/editor' ).editPost( { title: 'My New Title' } );
			setHeadingAnchors(blocks);
        return (
					<p className={ props.className }>
            <ServerSideRender
                block="simpletoc/toc"
                attributes={ props.attributes }
            />
					</p>
        );

    },
	save: props => {
		return null;
	},
} );

function setHeadingAnchors(blocks){
	var headings = blocks.forEach(function (item,index){
			var blockId = '';
			var slug = '';
			if(item['name'] === 'core/heading'){
				blockId = (item['clientId']);
				console.info(blockId);
				/* generate the slug for the anchor id */
				slug = item.attributes.content.toSlug();
				/* onyl set anchor if it isn't already defined */
				if(item.attributes.anchor === undefined){
					wp.data.dispatch( 'core/editor' ).updateBlockAttributes( blockId, { anchor: slug } );
				}
			}
	});
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
