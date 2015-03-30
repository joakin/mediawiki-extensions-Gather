( function ( M, $ ) {

	var CollectionDeleteOverlay,
		SchemaGather = M.require( 'ext.gather.logging/SchemaGather' ),
		schema = new SchemaGather(),
		toast = M.require( 'toast' ),
		icons = M.require( 'icons' ),
		CollectionsApi = M.require( 'ext.gather.watchstar/CollectionsApi' ),
		CollectionsContentOverlayBase = M.require( 'ext.gather.collection.base/CollectionsContentOverlayBase' );

	/**
	 * Overlay for deleting a collection
	 * @extends CollectionsContentOverlayBase
	 * @class CollectionDeleteOverlay
	 */
	CollectionDeleteOverlay = CollectionsContentOverlayBase.extend( {
		/** @inheritdoc */
		className: 'collection-delete-overlay content-overlay position-fixed',
		/** @inheritdoc */
		defaults: $.extend( {}, CollectionsContentOverlayBase.prototype.defaults, {
			fixedHeader: false,
			collection: null,
			spinner: icons.spinner().toHtmlString(),
			deleteSuccessMsg: mw.msg( 'gather-delete-collection-success' ),
			deleteFailedError: mw.msg( 'gather-delete-collection-failed-error' ),
			unknownCollectionError: mw.msg( 'gather-error-unknown-collection' ),
			subheadingDeleteCollection: mw.msg( 'gather-delete-collection-heading' ),
			confirmMessage: mw.msg( 'gather-delete-collection-confirm' ),
			deleteButtonLabel: mw.msg( 'gather-delete-collection-delete-label' ),
			cancelButtonLabel: mw.msg( 'gather-delete-collection-cancel-label' )
		} ),
		/** @inheritdoc */
		events: $.extend( {}, CollectionsContentOverlayBase.prototype.events, {
			'click .delete-collection': 'onDeleteClick',
			'click .cancel-delete': 'onCancelClick'
		} ),
		/** @inheritdoc */
		templatePartials: $.extend( {}, CollectionsContentOverlayBase.prototype.templatePartials, {
			content: mw.template.get( 'ext.gather.collection.delete', 'content.hogan' )
		} ),
		/** @inheritdoc */
		initialize: function ( options ) {
			var collection = options.collection;
			if ( !collection ) {
				// use toast
				toast.show( options.unknownCollectionError, 'toast error' );
			} else {
				this.id = collection.id;
				this.api = new CollectionsApi();
				CollectionsContentOverlayBase.prototype.initialize.apply( this, arguments );
			}
		},
		/**
		 * Event handler when the save button is clicked.
		 */
		onDeleteClick: function () {
			var self = this;
			this.showSpinner();
			// disable button and inputs
			this.$( '.delete-collection, .cancel-delete' ).prop( 'disabled', true );
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
				self.$( '.delete-collection, .cancel-delete' ).prop( 'disabled', false );
				schema.log( {
					eventName: 'delete-collection-error',
					errorText: errMsg
				} );
			} );
		},
		/**
		 * Event handler when the cancel button is clicked.
		 */
		onCancelClick: function () {
			this.hide();
		}
	} );

	M.define( 'ext.gather.delete/CollectionDeleteOverlay', CollectionDeleteOverlay );

}( mw.mobileFrontend, jQuery ) );
