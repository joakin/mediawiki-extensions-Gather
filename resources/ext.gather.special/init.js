( function ( M, $ ) {

	var CollectionEditOverlay = M.require( 'ext.gather.edit/CollectionEditOverlay' ),
		overlayManager = M.require( 'overlayManager' );

	/** Add routes to the overlay manager */
	function addOverlayManagerEditing() {
		overlayManager.add( /^\/collection\/edit\/(.*)$/, function ( id ) {
			id = parseInt( id, 10 );
			var collection;
			$.each( mw.config.get( 'wgGatherCollections' ), function () {
				if ( this.id === id && this.isWatchlist === false ) {
					collection = this;
				}
			} );
			if ( collection ) {
				return new CollectionEditOverlay( {
					collection: collection
				} );
			} else {
				return null;
			}
		} );
	}

	$( function () {
		addOverlayManagerEditing();
		$( '.collection-actions' ).show();
	} );
}( mw.mobileFrontend, jQuery ) );
