( function ( M, $ ) {

	var CollectionEditOverlay = M.require( 'ext.gather.edit/CollectionEditOverlay' ),
		CollectionDeleteOverlay = M.require( 'ext.gather.delete/CollectionDeleteOverlay' ),
		overlayManager = M.require( 'overlayManager' );

	/** Add routes for editing and deleting to the overlay manager */
	function addOverlayManagerRoutes() {
		overlayManager.add( /^\/collection\/(.*)\/(.*)$/, function ( action, id ) {
			id = parseInt( id, 10 );
			var collection;
			$.each( mw.config.get( 'wgGatherCollections' ), function () {
				if ( this.id === id && this.isWatchlist === false ) {
					collection = this;
				}
			} );
			if ( collection ) {
				if ( action === 'edit' ) {
					return new CollectionEditOverlay( {
						collection: collection
					} );
				} else if ( action === 'delete' ) {
					return new CollectionDeleteOverlay( {
						collection: collection
					} );
				}
			} else {
				return null;
			}
		} );
	}

	$( function () {
		addOverlayManagerRoutes();
		$( '.collection-actions' ).show();
	} );
}( mw.mobileFrontend, jQuery ) );
