( function ( blocks, element, blockEditor, components, i18n ) {
	var el = element.createElement;
	var __ = i18n.__;
	blocks.registerBlockType( 'wp-ai-disclosure/notice', {
		edit: function ( props ) {
			var blockProps = blockEditor.useBlockProps( { className: 'wp-ai-disclosure wp-ai-disclosure--inline' } );
			return el(
				'div',
				blockProps,
				el( 'span', { className: 'wp-ai-disclosure__icon', 'aria-hidden': 'true' }, 'AI' ),
				el( blockEditor.RichText, {
					tagName: 'span',
					className: 'wp-ai-disclosure__text',
					value: props.attributes.text,
					allowedFormats: [],
					placeholder: __( 'Leave empty to use the site-wide notice text', 'wp-ai-disclosure' ),
					onChange: function ( v ) { props.setAttributes( { text: v } ); }
				} )
			);
		},
		save: function () { return null; }
	} );
} )( window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components, window.wp.i18n );
