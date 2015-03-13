( function ( M, $ ) {

	var CollectionEditOverlay,
		toast = M.require( 'toast' ),
		CollectionsApi = M.require( 'ext.gather.watchstar/CollectionsApi' ),
		Overlay = M.require( 'Overlay' ),
		router = M.require( 'router' );

	/**
	 * Overlay for editing a collection
	 * @extends Overlay
	 * @class CollectionEditOverlay
	 */
	CollectionEditOverlay = Overlay.extend( {
		/** @inheritdoc */
		className: 'collection-editor-overlay overlay position-fixed',
		/** @inheritdoc */
		defaults: $.extend( {}, Overlay.prototype.defaults, {
			editFailedError: mw.msg( 'gather-edit-collection-failed-error' ),
			unknownCollectionError: mw.msg( 'gather-error-unknown-collection' ),
			collection: null,
			heading: mw.msg( 'gather-edit-collection-heading' ),
			nameLabel: mw.msg( 'gather-edit-collection-label-name' ),
			descriptionLabel: mw.msg( 'gather-edit-collection-label-description' ),
			privateLabel: mw.msg( 'gather-edit-collection-label-privacy' ),
			headerButtonsListClassName: 'overlay-action',
			headerButtons: [ {
				className: 'save submit',
				msg: mw.msg( 'gather-edit-collection-save-label' )
			} ],
		} ),
		/** @inheritdoc */
		events: $.extend( {}, Overlay.prototype.events, {
			'click .save': 'onSaveClick'
		} ),
		/** @inheritdoc */
		templatePartials: $.extend( {}, Overlay.prototype.templatePartials, {
			content: mw.template.get( 'ext.gather.collection.editor', 'content.hogan' )
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
				Overlay.prototype.initialize.apply( this, arguments );
			}
		},
		/**
		 * Event handler when the save button is clicked.
		 */
		onSaveClick: function () {
			// disable button and inputs
			this.showSpinner();
			this.$( '.mw-ui-input, .save' ).prop( 'disabled', true );
			this.api.editCollection(
				this.id, this.$( '.title' ).val(), this.$( '.description' ).val()
			).done( function () {
				// Go back to the page we were and reload to avoid having to update the
				// JavaScript state.
				router.navigate( '/' );
				window.location.reload();
			} ).fail( function () {
				toast.show( this.options.editFailedError, 'toast error' );
				// Make it possible to try again.
				this.$( '.mw-ui-input, .save' ).prop( 'disabled', false );
			} );
		}
	} );

	M.define( 'ext.gather.edit/CollectionEditOverlay', CollectionEditOverlay );

}( mw.mobileFrontend, jQuery ) );
