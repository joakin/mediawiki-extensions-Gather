( function ( M, $ ) {

	var CollectionDeleteOverlay,
		SchemaGather = M.require( 'ext.gather.logging/SchemaGather' ),
		schema = new SchemaGather(),
		toast = M.require( 'toast' ),
		CollectionsApi = M.require( 'ext.gather.watchstar/CollectionsApi' ),
		ConfirmationOverlay = M.require( 'ext.gather.confirm/ConfirmationOverlay' );

	/**
	 * Overlay for deleting a collection
	 * @extends ConfirmationOverlay
	 * @class CollectionDeleteOverlay
	 */
	CollectionDeleteOverlay = ConfirmationOverlay.extend( {
		/** @inheritdoc */
		defaults: $.extend( {}, ConfirmationOverlay.prototype.defaults, {
			deleteSuccessMsg: mw.msg( 'gather-delete-collection-success' ),
			deleteFailedError: mw.msg( 'gather-delete-collection-failed-error' ),
			subheading: mw.msg( 'gather-delete-collection-heading' ),
			confirmMessage: mw.msg( 'gather-delete-collection-confirm' ),
			confirmButtonClass: 'mw-ui-destructive',
			confirmButtonLabel: mw.msg( 'gather-delete-collection-delete-label' )
		} ),
		/** @inheritdoc */
		events: $.extend( {}, ConfirmationOverlay.prototype.events, {
			'click .confirm': 'onDeleteClick'
		} ),
		/** @inheritdoc */
		initialize: function () {
			this.api = new CollectionsApi();
			ConfirmationOverlay.prototype.initialize.apply( this, arguments );
		},
		/**
		 * Event handler when the delete button is clicked.
		 */
		onDeleteClick: function () {
			var self = this;
			this.showSpinner();
			// disable button and inputs
			this.$( '.confirm, .cancel' ).prop( 'disabled', true );
			this.api.removeCollection( this.id ).done( function () {
				// Show toast
				self.$( '.spinner' ).hide();
				toast.show( self.options.deleteSuccessMsg, 'toast' );

				schema.log( {
					eventName: 'delete-collection'
				} ).always( function () {
					// Go to the collections list page as collection will no longer exist
					window.location.href = mw.util.getUrl( 'Special:Gather' );
				} );

			} ).fail( function ( errMsg ) {
				toast.show( self.options.deleteFailedError, 'toast error' );
				self.hide();
				// Make it possible to try again.
				self.$( '.confirm, .cancel' ).prop( 'disabled', false );
				schema.log( {
					eventName: 'delete-collection-error',
					errorText: errMsg
				} );
			} );
		}
	} );

	M.define( 'ext.gather.delete/CollectionDeleteOverlay', CollectionDeleteOverlay );

}( mw.mobileFrontend, jQuery ) );
