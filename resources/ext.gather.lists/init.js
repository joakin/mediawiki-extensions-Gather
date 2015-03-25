( function ( M, $ ) {
	var CollectionsApi = M.require( 'ext.gather.watchstar/CollectionsApi' ),
		toast = M.require( 'toast' ),
		api = new CollectionsApi();

	/**
	 * Initialize the moderation buttons
	 */
	function init() {
		$( 'ul' ).on( 'click', '.moderate-collection', onModerateCollection );
	}

	/**
	 * Event handler for trying to hide a list
	 * @param {jQuery.Event} ev
	 */
	function onModerateCollection( ev ) {
		var $btn = $( ev.currentTarget ),
			$row = $btn.closest( 'li' ),
			id = $btn.data( 'id' ),
			label = $btn.data( 'label' ),
			owner = $btn.data( 'owner' ),
			action = $btn.data( 'action' ),
			msgKey = action === 'hide' ? 'gather-lists-hide-collection' : 'gather-lists-show-collection',
			message = mw.msg( msgKey, label, owner );

		if ( action === 'hide' && window.confirm( message ) ) {
			api.hideCollection( id ).done( function () {
				$row.fadeOut( function () {
					$row.remove();
					toast.show( mw.msg( 'gather-lists-hide-success-toast', label ), 'toast' );
				} );
			} ).fail( function () {
				toast.show( mw.msg( 'gather-lists-hide-failure-toast', label ), 'toast fail' );
			} );
		}

		if ( action === 'show' && window.confirm( message ) ) {
			api.showCollection( id ).done( function () {
				$row.fadeOut( function () {
					$row.remove();
					toast.show( mw.msg( 'gather-lists-show-success-toast', label ), 'toast' );
				} );
			} ).fail( function () {
				toast.show( mw.msg( 'gather-lists-show-failure-toast', label ), 'toast fail' );
			} );
		}
	}

	init();

}( mw.mobileFrontend, jQuery ) );
