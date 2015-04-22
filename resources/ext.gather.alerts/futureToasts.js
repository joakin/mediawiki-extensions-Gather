( function ( M, $ ) {

	var settings = M.require( 'settings' ),
		toast = M.require( 'toast' ),
		key = 'future-toasts';

	/**
	 * Get the future toasts to show
	 * @return {Array}
	 */
	function getToasts() {
		try {
			return JSON.parse( settings.get( key ) ) || [];
		} catch ( e ) {
			return [];
		}
	}

	/**
	 * Schedule an alert for the future.
	 * @param {String} msg to show
	 * @param {String} className class to add to element
	 */
	function addFutureToast( msg, className ) {
		var toasts = getToasts();
		toasts.push( [ msg, className ] );
		settings.save( key, JSON.stringify( toasts ) );
	}

	/**
	 * Show all pending toasts and clear the queue
	 */
	function showFutureToasts() {
		$.each( getToasts(), function ( i, t ) {
			toast.show( t[0], t[1] );
		} );
		settings.remove( key );
	}

	M.define( 'ext.gather.alerts/futureToasts', {
		add: addFutureToast,
		show: showFutureToasts
	} );

}( mw.mobileFrontend, jQuery ) );
