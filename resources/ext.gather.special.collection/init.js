( function ( M, $ ) {

	var CollectionFlagButton = M.require( 'ext.gather.collection.flag/CollectionFlagButton' );

	$( function () {
		var $collection = $( '.collection' );

		if ( !$collection.data( 'is-owner' ) ) {
			new CollectionFlagButton( {
				collectionId: $collection.data( 'id' )
			} ).prependTo( '.collection-moderation' );
		}

		$( '.collection-actions' ).addClass( 'visible' );
	} );
}( mw.mobileFrontend, jQuery ) );
