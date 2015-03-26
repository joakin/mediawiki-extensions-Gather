( function ( M, $ ) {

	var CollectionEditOverlay = M.require( 'ext.gather.edit/CollectionEditOverlay' ),
		CollectionDeleteOverlay = M.require( 'ext.gather.delete/CollectionDeleteOverlay' ),
		toast = M.require( 'toast' ),
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
				} else {
					toast.show( mw.msg( 'gather-no-such-action' ), 'error' );
					return $.Deferred();
				}
			} else {
				toast.show( mw.msg( 'gather-unknown-error' ), 'error' );
				return $.Deferred();
			}
		} );
	}

	$( function () {
		addOverlayManagerRoutes();
		$( '.collection-actions' ).addClass( 'visible' );
	} );
}( mw.mobileFrontend, jQuery ) );
