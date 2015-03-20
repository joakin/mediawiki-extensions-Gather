( function ( M, $ ) {
	var CollectionsApi = M.require( 'ext.gather.watchstar/CollectionsApi' ),
		toast = M.require( 'toast' ),
		api = new CollectionsApi();

	/**
	 * Initialize the moderation buttons
	 */
	function init() {
		$( 'ul' ).on( 'click', '.hide-collection', onHideCollection );
	}

	/**
	 * Event handler for trying to hide a list
	 * @param {jQuery.Event} ev
	 */
	function onHideCollection( ev ) {
		var $btn = $( ev.currentTarget ),
			$row = $btn.closest( 'li' ),
			id = $btn.data( 'id' ),
			label = $btn.data( 'label' ),
			owner = $btn.data( 'owner' ),
			message = mw.msg( 'gather-lists-hide-collection', label, owner );

		if ( window.confirm( message ) ) {
			api.hideCollection( id ).done( function () {
				$row.fadeOut( function () {
					$row.remove();
					toast.show( mw.msg( 'gather-lists-hide-success-toast', label ), 'toast' );
				} );
			} ).fail( function () {
				toast.show( mw.msg( 'gather-lists-hide-failure-toast', label ), 'toast fail' );
			} );
		}
	}

	init();

}( mw.mobileFrontend, jQuery ) );
