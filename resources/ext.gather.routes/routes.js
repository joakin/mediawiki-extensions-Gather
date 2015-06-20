( function ( M, $ ) {

	var CollectionsApi = M.require( 'ext.gather.api/CollectionsApi' ),
		toast = M.require( 'toast' ),
		overlayManager = M.require( 'overlayManager' ),
		loader = M.require( 'loader' );

	/**
	 * Render the collection edit overlay
	 * @method
	 * @param {Number} id Collection id
	 * @param {Boolean} [showTutorial] set to true to show tutorial
	 */
	function renderCollectionEditOverlay( id, showTutorial ) {
		var d = $.Deferred(),
			api = new CollectionsApi();

		showTutorial = showTutorial || false;
		api.getCollection( id ).done( function ( collection ) {
			if ( collection ) {
				loader.loadModule( 'ext.gather.collection.editor', true ).done( function ( loadingOverlay ) {
					var CollectionEditOverlay = M.require( 'ext.gather.collection.edit/CollectionEditOverlay' ),
						isSpecialPage = mw.config.get( 'wgNamespaceNumber' ) === mw.config.get( 'wgNamespaceIds' ).special;
					loadingOverlay.hide();
					d.resolve(
						new CollectionEditOverlay( {
							collection: collection,
							reloadOnSave: isSpecialPage,
							showTutorial: showTutorial
						} )
					);
				} );
			} else {
				toast.show( mw.msg( 'gather-unknown-error' ), 'error' );
			}
		} );
		return d;
	}

	overlayManager.add( /^\/edit-collection\/(.*)$/, function ( id ) {
		return renderCollectionEditOverlay( id );
	} );

	overlayManager.add( /^\/edit-collection-tutorial\/(.*)$/, function ( id ) {
		return renderCollectionEditOverlay( id, true );
	} );

}( mw.mobileFrontend, jQuery ) );
