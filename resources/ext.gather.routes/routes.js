( function ( M, $ ) {

	var CollectionsApi = M.require( 'ext.gather.api/CollectionsApi' ),
		toast = M.require( 'toast' ),
		overlayManager = M.require( 'overlayManager' );

	overlayManager.add( /^\/collection\/(.*)\/(.*)$/, function ( action, id ) {
		var d = $.Deferred(),
			api = new CollectionsApi();

		api.getCollection( id ).done( function ( collection ) {
			if ( collection ) {
				if ( action === 'edit' ) {
					mw.loader.using( 'ext.gather.collection.editor' ).done( function () {
						var CollectionEditOverlay = M.require( 'ext.gather.collection.edit/CollectionEditOverlay' );
						d.resolve(
							new CollectionEditOverlay( {
								collection: collection
							} )
						);
					} );
				} else {
					toast.show( mw.msg( 'gather-no-such-action' ), 'error' );
				}
			} else {
				toast.show( mw.msg( 'gather-unknown-error' ), 'error' );
			}
		} );
		return d;
	} );

}( mw.mobileFrontend, jQuery ) );
