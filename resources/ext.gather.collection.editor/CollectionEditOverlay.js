( function ( M, $ ) {

	var CollectionEditOverlay,
		toast = M.require( 'toast' ),
		CollectionsApi = M.require( 'ext.gather.watchstar/CollectionsApi' ),
		CollectionSearchPanel = M.require( 'ext.gather.page.search/CollectionSearchPanel' ),
		Overlay = M.require( 'Overlay' ),
		Icon = M.require( 'Icon' ),
		SchemaGather = M.require( 'ext.gather.logging/SchemaGather' ),
		schema = new SchemaGather(),
		router = M.require( 'router' ),
		CollectionDeleteOverlay = M.require( 'ext.gather.delete/CollectionDeleteOverlay' );

	/**
	 * Overlay for editing a collection
	 * @extends Overlay
	 * @class CollectionEditOverlay
	 * @uses CollectionSearchPanel
	 */
	CollectionEditOverlay = Overlay.extend( {
		_selectors: {
			edit: '.continue-header, .editor-pane',
			manage: '.save-header, .manage-members-pane .content, .manage-members-pane .results, .manage-members-pane .search',
			search: '.search-header, .manage-members-pane .results'
		},
		/** @inheritdoc */
		className: 'collection-editor-overlay overlay',
		titleMaxLength: 90,
		descriptionMaxLength: 280,
		/** @inheritdoc */
		defaults: $.extend( {}, Overlay.prototype.defaults, {
			clearIcon: new Icon( {
				name: 'clear',
				label: mw.msg( 'gather-edit-collection-clear-label' ),
				additionalClassNames: 'clear hidden'
			} ).options,
			// FIXME: Should live in MobileFrontend
			cancelArrowIcon: new Icon( {
				tagName: 'button',
				name: 'back',
				additionalClassNames: 'cancel',
				label: mw.msg( 'mobile-frontend-overlay-close' )
			} ).options,
			editSuccessMsg: mw.msg( 'gather-update-collection-success' ),
			editFailedError: mw.msg( 'gather-edit-collection-failed-error' ),
			unknownCollectionError: mw.msg( 'gather-error-unknown-collection' ),
			collection: null,
			heading: mw.msg( 'gather-edit-collection-heading' ),
			nameLabel: mw.msg( 'gather-edit-collection-label-name' ),
			descriptionLabel: mw.msg( 'gather-edit-collection-label-description' ),
			publicLabel: mw.msg( 'gather-edit-collection-label-public' ),
			headerButtonsListClassName: 'overlay-action',
			editMsg: mw.msg( 'gather-overlay-edit-button' ),
			deleteMsg: mw.msg( 'gather-delete-button' ),
			saveMsg: mw.msg( 'gather-edit-collection-save-label' )
		} ),
		/** @inheritdoc */
		events: $.extend( {}, Overlay.prototype.events, {
			'click .edit-action': 'onEditActionClick',
			'click .delete-action': 'onDeleteActionClick',
			'click .clear': 'onClearSearch',
			'focus .manage-members-pane input': 'onFocusSearch',
			'input .search-header input': 'onRunSearch',
			'click .search-header .back': 'onExitSearch',
			'click .save-description': 'onSaveDescriptionClick',
			'click .back': 'onBackClick',
			'click .save': 'onFirstPaneSaveClick'
		} ),
		/** @inheritdoc */
		templatePartials: $.extend( {}, Overlay.prototype.templatePartials, {
			icon: Icon.prototype.template,
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
			this.$clear = this.$( '.search-header .clear' );
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
				} );
				self.searchPanel.show();
			} );
		},
		/**
		 * Switch to the first pane in the overlay.
		 * @private
		 */
		_switchToFirstPane: function () {
			this.$( this._selectors.edit )
				.add( this._selectors.search )
				.addClass( 'hidden' );
			this.$( this._selectors.manage ).removeClass( 'hidden' );
		},
		/**
		 * Switch to search pane.
		 * @private
		 */
		_switchToSearchPane: function () {
			this.$( this._selectors.edit )
				.add( this._selectors.manage )
				.addClass( 'hidden' );
			this.$( this._selectors.search ).removeClass( 'hidden' );
			this.$( '.search-header input' ).focus();
		},
		/**
		 * Switch to edit pane.
		 * @private
		 */
		_switchToEditPane: function () {
			this.$( this._selectors.manage )
				.add( this._selectors.search )
				.addClass( 'hidden' );
			this.$( this._selectors.edit ).removeClass( 'hidden' );
		},
		/**
		 * Event handler when the search input is cleared
		 */
		onClearSearch: function () {
			this.$( '.search-header input' ).val( '' );
			this.searchPanel.search( '' );
			this.$clear.addClass( 'hidden' );
			this.$( '.search-header input' ).focus();
		},
		/**
		 * Event handler when the search input is focused
		 */
		onFocusSearch: function () {
			this.searchPanel.search( this.$( '.search-header input' ).val() );
			this._switchToSearchPane();
		},
		/**
		 * Event handler when the edit button is clicked.
		 * @param {jQuery.Event} ev
		 */
		onRunSearch: function ( ev ) {
			var val = $( ev.currentTarget ).val(),
				$clear = this.$clear;

			if ( val ) {
				$clear.removeClass( 'hidden' );
			} else {
				$clear.addClass( 'hidden' );
			}
			this.searchPanel.search( val );
		},
		/**
		 * Event handler when the exit search button is clicked.
		 */
		onExitSearch: function () {
			this.searchPanel.search( '' );
			this._switchToFirstPane();
		},
		/**
		 * Event handler when the edit button is clicked.
		 */
		onEditActionClick: function () {
			this._switchToEditPane();
		},
		/**
		 * Event handler when the delete button is clicked.
		 */
		onDeleteActionClick: function () {
			this.$el.append( new CollectionDeleteOverlay( {
				collection: this.options.collection
			} ).$el );
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
		onFirstPaneSaveClick: function () {
			this.hide();
			// Go back to the page we were and reload to avoid having to update the
			// JavaScript state.
			if ( this._stateChanged ) {
				window.setTimeout( function () {
					router.navigate( '/' );
					window.location.reload();
				}, 100 );
			}
		},
		/**
		 * Event handler when the save button is clicked.
		 */
		onSaveDescriptionClick: function () {
			var title = this.$( 'input.title' ).val(),
				self = this,
				isPrivate = !this.$( '.privacy' ).is( ':checked' ),
				description = this.$( '.description' ).val();

			if ( this.isTitleValid( title ) && this.isDescriptionValid( description ) ) {
				this.$( '.save-header h2 span' ).text( title );
				// disable button and inputs
				this.showSpinner();
				this.$( '.mw-ui-input, .save-description' ).prop( 'disabled', true );
				this.api.editCollection( this.id, title, description, isPrivate ).done( function () {
					schema.log( {
						eventName: 'edit-collection'
					} ).always( function () {
						self._switchToFirstPane();
						// Make sure when the user leaves the overlay the page gets refreshed
						self._stateChanged = true;
					} );
					self.$( '.mw-ui-input, .save-description' ).prop( 'disabled', false );
					toast.show( self.options.editSuccessMsg, 'toast' );
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
