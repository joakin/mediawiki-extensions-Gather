( function ( M, $ ) {

	var CollectionDeleteOverlay,
		toast = M.require( 'toast' ),
		icons = M.require( 'icons' ),
		CollectionsApi = M.require( 'ext.gather.watchstar/CollectionsApi' ),
		ContentOverlay = M.require( 'modules/tutorials/ContentOverlay' );

	/**
	 * Overlay for deleting a collection
	 * @extends ContentOverlay
	 * @class CollectionDeleteOverlay
	 */
	CollectionDeleteOverlay = ContentOverlay.extend( {
		/** @inheritdoc */
		className: 'collection-delete-overlay content-overlay position-fixed',
		/** @inheritdoc */
		hasFixedHeader: false,
		/** @inheritdoc */
		defaults: $.extend( {}, ContentOverlay.prototype.defaults, {
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
		events: $.extend( {}, ContentOverlay.prototype.events, {
			'click .delete-collection': 'onDeleteClick',
			'click .cancel-delete': 'onCancelClick'
		} ),
		/** @inheritdoc */
		templatePartials: $.extend( {}, ContentOverlay.prototype.templatePartials, {
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
				ContentOverlay.prototype.initialize.apply( this, arguments );
			}
		},
		postRender: function () {
			this.$( '.spinner' ).hide();
		},
		/**
		 * Event handler when the save button is clicked.
		 */
		onDeleteClick: function () {
			var self = this;
			this.$( '.spinner' ).show();
			// disable button and inputs
			this.$( '.delete-collection, .cancel-delete' ).prop( 'disabled', true );
			this.api.removeCollection( this.id ).done( function () {
				// Show toast
				self.$( '.spinner' ).hide();
				toast.show( self.options.deleteSuccessMsg, 'toast' );

				// Go to the collections list page as collection will no longer exist
				window.location.href = mw.util.getUrl( 'Special:Gather' );

			} ).fail( function () {
				toast.show( self.options.deleteFailedError, 'toast error' );
				// Make it possible to try again.
				self.$( '.delete-collection, .cancel-delete' ).prop( 'disabled', false );
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