( function ( M ) {

	var CollectionsList,
		View = M.require( 'View' );

	CollectionsList = View.extend( {
		defaults: {
			enhance: false,
			collections: []
		},
		template: mw.template.get( 'ext.gather.collections.list', 'CollectionsList.hogan' ),
		templatePartials: {
			item: mw.template.get( 'ext.gather.collections.list', 'CollectionsListItemCard.hogan' )
		},
		/** @inheritdoc */
		initialize: function ( options ) {
			if ( options.enhance ) {
				this.template = false;
			}
			View.prototype.initialize.apply( this, arguments );
		}
	} );

	M.define( 'ext.gather.collections.list/CollectionsList', CollectionsList );

} )( mw.mobileFrontend, jQuery );
