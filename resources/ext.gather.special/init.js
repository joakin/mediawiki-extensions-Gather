( function ( M, $ ) {

	var CollectionsApi = M.require( 'ext.gather.watchstar/CollectionsApi' ),
		CollectionFlagOverlay = M.require( 'ext.gather.flag/CollectionFlagOverlay' ),
		Icon = M.require( 'Icon' ),
		user = M.require( 'user' ),
		api = new CollectionsApi(),
		id = window.location.pathname.split( '/' ).pop();

	$( function () {

		api.getCollection( id ).done( function ( collection ) {
			var flagIcon, $flag;
			if (
				// No flagging on watchlist
				collection.id === 0 ||
				// Don't show icon if user is the collection owner
				collection.owner === user.getName() ||
				// Only show flag icon in minerva as mobile overlays require it
				mw.config.get( 'skin' ) !== 'minerva'
			) {
				return;
			}

			flagIcon = new Icon( {
				name: 'collection-flag',
				tagName: 'a',
				title: mw.msg( 'gather-flag-collection-flag-label' )
			} );
			$flag = $( flagIcon.toHtmlString() );
			$flag.on( 'click', function ( ev ) {
				var flagOverlay;
				ev.stopPropagation();
				ev.preventDefault();
				if ( !$flag.hasClass( 'disabled' ) ) {
					flagOverlay = new CollectionFlagOverlay( { collection: collection } );
					flagOverlay.show();
					flagOverlay.on( 'collection-flagged', function () {
						// After flagging, prevent click from opening flag confirmation again
						$flag.addClass( 'disabled' );
					} );
				}
			} );
			$flag.appendTo( '.collection-moderation' );
		} );

		$( '.collection-actions' ).addClass( 'visible' );
	} );
}( mw.mobileFrontend, jQuery ) );
