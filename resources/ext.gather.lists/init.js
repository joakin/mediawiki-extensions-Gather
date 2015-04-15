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
		var $button = $( ev.currentTarget ),
			data = $button.data(),
			key = 'gather-lists-' + data.action + '-collection';

		if ( window.confirm( mw.msg( key, data.label, data.owner ) ) ) {
			api.setVisible( data.id, data.action === 'show' ).always( function () {
				$button.closest( 'li' ).remove();
				key = 'gather-lists-' + data.action + '-success-toast';
				toast.show( mw.msg( key, data.label ), 'toast' );
			}, function () {
				key = 'gather-lists-' + data.action + '-failure-toast';
				toast.show( mw.msg( key, data.label ), 'toast fail' );
			} );
		}
	}

	init();

}( mw.mobileFrontend, jQuery ) );
