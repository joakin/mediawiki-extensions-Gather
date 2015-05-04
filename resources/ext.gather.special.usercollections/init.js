( function ( M, $ ) {
	var CollectionsList = M.require( 'ext.gather.collections.list/CollectionsList' ),
		$collectionsList = $( '.collections-list' ),
		owner = $collectionsList.data( 'owner' );

	$( function () {
		new CollectionsList( {
			el: $collectionsList,
			enhance: true,
			userName: owner
		} );
	} );

}( mw.mobileFrontend, jQuery ) );
