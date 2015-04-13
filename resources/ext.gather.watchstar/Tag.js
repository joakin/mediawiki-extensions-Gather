// jscs:disable requireCamelCaseOrUpperCaseIdentifiers
( function ( M ) {

	var Tag,
		View = M.require( 'View' );

	/**
	 * A tag with a label
	 * @class Tag
	 * @extends View
	 */
	Tag = View.extend( {
		className: 'gather-tag',
		template: mw.template.get( 'ext.gather.watchstar', 'Tag.hogan' )
	} );
	M.define( 'ext.gather.watchstar/Tag', Tag );

}( mw.mobileFrontend ) );
