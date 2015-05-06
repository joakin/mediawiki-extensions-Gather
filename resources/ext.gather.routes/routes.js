( function ( M, $ ) {

	var CollectionsApi = M.require( 'ext.gather.api/CollectionsApi' ),
		toast = M.require( 'toast' ),
		overlayManager = M.require( 'overlayManager' ),
		loader = M.require( 'loader' );

	overlayManager.add( /^\/collection\/(.*)\/(.*)$/, function ( action, id ) {
		var d = $.Deferred(),
			api = new CollectionsApi();

		api.getCollection( id ).done( function ( collection ) {
			if ( collection ) {
				if ( action === 'edit' ) {
					loader.loadModule( 'ext.gather.collection.editor', true ).done( function ( loadingOverlay ) {
						var CollectionEditOverlay = M.require( 'ext.gather.collection.edit/CollectionEditOverlay' ),
							isSpecialPage = mw.config.get( 'wgNamespaceNumber' ) === mw.config.get( 'wgNamespaceIds' ).special;
						loadingOverlay.hide();
						d.resolve(
							new CollectionEditOverlay( {
								collection: collection,
								reloadOnSave: isSpecialPage
							} )
						);
					} );
				} else if ( action === 'delete' ) {
					mw.loader.using( 'ext.gather.collection.delete' ).done( function () {
						var CollectionDeleteOverlay = M.require( 'ext.gather.collection.delete/CollectionDeleteOverlay' );
						d.resolve(
							new CollectionDeleteOverlay( {
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
