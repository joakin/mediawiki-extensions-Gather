( function ( M, $ ) {
	var CollectionsList = M.require( 'ext.gather.collections.list/CollectionsList' );

	$( function () {
		new CollectionsList( {
			el: $( '.collections-list' ),
			enhance: true
		} );
	} );

}( mw.mobileFrontend, jQuery ) );
