( function ( M, $ ) {

	var CollectionEditOverlay,
		toast = M.require( 'toast' ),
		CollectionsApi = M.require( 'ext.gather.watchstar/CollectionsApi' ),
		Overlay = M.require( 'Overlay' ),
		SchemaGather = M.require( 'ext.gather.logging/SchemaGather' ),
		schema = new SchemaGather(),
		router = M.require( 'router' );

	/**
	 * Overlay for editing a collection
	 * @extends Overlay
	 * @class CollectionEditOverlay
	 */
	CollectionEditOverlay = Overlay.extend( {
		/** @inheritdoc */
		className: 'collection-editor-overlay overlay position-fixed',
		titleMaxLength: 90,
		descriptionMaxLength: 280,
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
			} ]
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
			var title = this.$( '.title' ).val(),
				description = this.$( '.description' ).val();

			if ( this.isTitleValid( title ) && this.isDescriptionValid( description ) ) {
				// disable button and inputs
				this.showSpinner();
				this.$( '.mw-ui-input, .save' ).prop( 'disabled', true );
				this.api.editCollection( this.id, title, description ).done( function () {
					// Go back to the page we were and reload to avoid having to update the
					// JavaScript state.
					schema.log( {
						eventName: 'edit-collection'
					} ).done( function () {
						router.navigate( '/' );
						window.location.reload();
					} );
				} ).fail( function ( errMsg ) {
					toast.show( this.options.editFailedError, 'toast error' );
					// Make it possible to try again.
					this.$( '.mw-ui-input, .save' ).prop( 'disabled', false );
					schema.log( {
						eventName: 'edit-collection-error',
						errorMessage: errMsg
					} );
				} );
			} else {
				toast.show( this.options.editFailedError, 'toast error' );
			}

		},
		/**
		 * Tests if title is valid
		 * @param {[type]} title Proposed collection title
		 * @returns {Boolean}
		 */
		isTitleValid: function ( title ) {
			// FIXME: Need to consider other languages
			return title.length <= this.titleMaxLength;
		},
		/**
		 * Tests if description is valid
		 * @param {[type]} description Proposed collection description
		 * @returns {Boolean}
		 */
		isDescriptionValid: function ( description ) {
			// FIXME: Need to consider other languages
			return description.length <= this.descriptionMaxLength;
		}
	} );

	M.define( 'ext.gather.edit/CollectionEditOverlay', CollectionEditOverlay );

}( mw.mobileFrontend, jQuery ) );
