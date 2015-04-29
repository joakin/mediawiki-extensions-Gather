( function ( $ ) {
	if ( mw.config.get( 'wgNamespaceNumber' ) > -1 ) {
		$( function () {
			mw.loader.load( 'ext.gather.init' );
		} );
	}

	// set up link in personal tab
	$( function () {
		var $li = $( '<li>' ).insertBefore( '#p-personal ul li:last-child' );
		$( '<a>' ).attr( 'href', mw.util.getUrl( 'Special:Gather' ) )
			// FIXME: i18n
			.text( mw.msg( 'gather-lists-title' ) ).appendTo( $li );
	} );
}( jQuery ) );
