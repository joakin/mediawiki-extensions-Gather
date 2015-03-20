( function ( M, $ ) {
	var SchemaGather,
		Schema = M.require( 'Schema' ),
		user = M.require( 'user' );

	/**
	 * @class SchemaGather
	 * @extends Schema
	 */
	SchemaGather = Schema.extend( {
		/**
		 * @inheritdoc
		 */
		defaults: $.extend( {}, Schema.prototype.defaults, {
			userId: mw.user.getId(),
			// FIXME:  use mw.user when method available
			userEditCount: user.getEditCount()
		} ),
		/**
		 * @inheritdoc
		 */
		name: 'SchemaGatherClicks'
	} );

	M.define( 'ext.gather.logging/SchemaGather', SchemaGather );

}( mw.mobileFrontend, jQuery ) );
