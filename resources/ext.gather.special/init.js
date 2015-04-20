( function ( M, $ ) {

	var CollectionsApi = M.require( 'ext.gather.watchstar/CollectionsApi' ),
		CollectionFlagOverlay = M.require( 'ext.gather.flag/CollectionFlagOverlay' ),
		Icon = M.require( 'Icon' ),
		api = new CollectionsApi();

	$( function () {
		var flagIcon, $flag,
			$collection = $( '.collection' );

		if ( !$collection.data( 'is-owner' ) && mw.config.get( 'skin' ) === 'minerva' ) {
			flagIcon = new Icon( {
				name: 'collection-flag',
				title: mw.msg( 'gather-flag-collection-flag-label' )
			} );
			// FIXME: See T97077
			$flag = $( flagIcon.toHtmlString() );
			$flag.on( 'click', function ( ev ) {
				var flagOverlay;
				ev.stopPropagation();
				ev.preventDefault();
				if ( !$flag.hasClass( 'disabled' ) ) {
					api.getCollection( $collection.data( 'id' ) ).done( function ( collection ) {
						flagOverlay = new CollectionFlagOverlay( {
							collection: collection
						} );
						flagOverlay.show();
						flagOverlay.on( 'collection-flagged', function () {
							// After flagging, prevent click from opening flag confirmation again
							$flag.addClass( 'disabled' );
						} );
					} );
				}
			} );
			$flag.prependTo( '.collection-moderation' );
		}

		$( '.collection-actions' ).addClass( 'visible' );
	} );
}( mw.mobileFrontend, jQuery ) );
