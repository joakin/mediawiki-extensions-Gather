( function ( M, $ ) {
	var CollectionsApi = M.require( 'ext.gather.api/CollectionsApi' ),
		toast = M.require( 'toast' ),
		Icon = M.require( 'Icon' ),
		api = new CollectionsApi();

	/**
	 * Initialize the moderation buttons
	 */
	function init() {
		var label, owner,
			$collection = $( '.collection' );

		if ( $collection.length && $collection.data( 'is-admin' ) && $collection.data( 'is-public' ) ) {
			label = $collection.data( 'label' );
			owner = $collection.data( 'owner' );
			new Icon( {
				name: 'collection-hide',
				title: mw.msg( 'gather-lists-hide-collection', label, owner ),
				additionalClassNames: 'moderate-collection'
			} )
			.appendTo( '.collection-moderation' );
			// FIXME: Should be possible to apply data directly
			$( '.moderate-collection' )
				.data( 'label', label )
				.data( 'owner', owner )
				.data( 'id', $collection.data( 'id' ) )
				.data( 'action', 'hide' );
		}
		// For Special:GatherEditFeed
		$( '.moderate-collection' ).on( 'click', onModerateCollection );
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
			api.setVisible( data.id, data.action === 'show' ).done( function () {
				$button.closest( 'li' ).remove();
				key = 'gather-lists-' + data.action + '-success-toast';
				toast.show( mw.msg( key, data.label ), 'toast' );
			} ).fail( function () {
				key = 'gather-lists-' + data.action + '-failure-toast';
				toast.show( mw.msg( key, data.label ), 'toast fail' );
			} );
		}
	}

	init();

}( mw.mobileFrontend, jQuery ) );
