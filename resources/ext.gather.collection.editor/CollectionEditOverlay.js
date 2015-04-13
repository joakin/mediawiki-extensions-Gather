( function ( M, $ ) {

	var CollectionEditOverlay,
		toast = M.require( 'toast' ),
		CollectionsApi = M.require( 'ext.gather.watchstar/CollectionsApi' ),
		CollectionSearchPanel = M.require( 'ext.gather.page.search/CollectionSearchPanel' ),
		Overlay = M.require( 'Overlay' ),
		SchemaGather = M.require( 'ext.gather.logging/SchemaGather' ),
		schema = new SchemaGather(),
		router = M.require( 'router' );

	/**
	 * Overlay for editing a collection
	 * @extends Overlay
	 * @class CollectionEditOverlay
	 * @uses CollectionSearchPanel
	 */
	CollectionEditOverlay = Overlay.extend( {
		/** @inheritdoc */
		className: 'collection-editor-overlay overlay',
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
			continueMsg: mw.msg( 'gather-overlay-continue' ),
			editMsg: mw.msg( 'gather-edit-button' ),
			deleteMsg: mw.msg( 'gather-delete-button' ),
			saveMsg: mw.msg( 'gather-edit-collection-save-label' )
		} ),
		/** @inheritdoc */
		events: $.extend( {}, Overlay.prototype.events, {
			'click .edit-action': 'onEditActionClick',
			'click .continue': 'onNextClick',
			'click .back': 'onBackClick',
			'click .save': 'onSaveClick'
		} ),
		/** @inheritdoc */
		templatePartials: $.extend( {}, Overlay.prototype.templatePartials, {
			header: mw.template.get( 'ext.gather.collection.editor', 'header.hogan' ),
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
		/** @inheritdoc */
		postRender: function ( options ) {
			var self = this,
				id = this.id;

			Overlay.prototype.postRender.apply( this, arguments );
			this.api.getCollectionMembers( id ).done( function ( pages ) {
				self.searchPanel = new CollectionSearchPanel( {
					collection: options.collection,
					pages: pages,
					el: self.$( '.panel' )
				} ).show();
			} );
		},
		/**
		 * Switch to the first pane in the overlay.
		 * @private
		 */
		_switchToFirstPane: function () {
			this.$( '.continue-header, .editor-pane' ).addClass( 'hidden' );
			this.$( '.save-header, .manage-members-pane' ).removeClass( 'hidden' );
		},
		/**
		 * Event handler when the edit button is clicked.
		 */
		onEditActionClick: function () {
			this.$( '.save-header, .manage-members-pane' ).addClass( 'hidden' );
			this.$( '.continue-header, .editor-pane' ).removeClass( 'hidden' );
		},
		/**
		 * Event handler when the back button is clicked on the title/edit description pane.
		 */
		onBackClick: function () {
			var collection = this.options.collection;
			// reset the values to their original values.
			this.$( 'input.title' ).val( collection.title );
			this.$( '.description' ).val( collection.description );
			// Note: we will need to reset checkbox when enabling private/public toggle.
			this._switchToFirstPane();
		},
		/**
		 * Event handler when the continue button is clicked in the title/edit description pane.
		 */
		onNextClick: function () {
			var newTitle = this.$( 'input.title' ).val();
			this.$( '.save-header h2 span' ).text( newTitle );
			this._switchToFirstPane();
		},
		/**
		 * Event handler when the save button is clicked.
		 */
		onSaveClick: function () {
			var title = this.$( 'input.title' ).val(),
				self = this,
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
					} ).always( function () {
						router.navigate( '/' );
						window.location.reload();
					} );
				} ).fail( function ( errMsg ) {
					toast.show( self.options.editFailedError, 'toast error' );
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
