( function ( M, $ ) {
	var SchemaGather,
		skinName = mw.config.get( 'skin' ),
		context = M.require( 'context' ),
		mobileMode = context.getMode(),
		Schema = M.require( 'Schema' ),
		user = M.require( 'user' );

	if ( mobileMode ) {
		skinName += '-' + mobileMode;
	}
	/**
	 * @class SchemaGather
	 * @extends Schema
	 */
	SchemaGather = Schema.extend( {
		/**
		 * @inheritdoc
		 */
		defaults: $.extend( {}, Schema.prototype.defaults, {
			skin: skinName,
			userId: mw.user.getId(),
			// FIXME: use mw.user when method available
			// Null when user is anon, set to 0
			userEditCount: user.getEditCount() || 0
		} ),
		/**
		 * @inheritdoc
		 */
		name: 'GatherClicks'
	} );

	M.define( 'ext.gather.logging/SchemaGather', SchemaGather );

}( mw.mobileFrontend, jQuery ) );
