( function ( M, $ ) {

	var CollectionEditOverlay,
		toast = M.require( 'toast' ),
		futureToast = M.require( 'ext.gather.alerts/futureToasts' ),
		CollectionsApi = M.require( 'ext.gather.api/CollectionsApi' ),
		CollectionSearchPanel = M.require( 'ext.gather.page.search/CollectionSearchPanel' ),
		Overlay = M.require( 'Overlay' ),
		Icon = M.require( 'Icon' ),
		SchemaGather = M.require( 'ext.gather.logging/SchemaGather' ),
		schema = new SchemaGather(),
		router = M.require( 'router' ),
		CollectionDeleteOverlay = M.require( 'ext.gather.collection.delete/CollectionDeleteOverlay' );

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
			collection: null,
			reloadOnSave: false,
			confirmExitMessage: mw.msg( 'gather-edit-collection-confirm' ),
			editSuccessMsg: mw.msg( 'gather-update-collection-success' ),
			editFailedError: mw.msg( 'gather-edit-collection-failed-error' ),
			unknownCollectionError: mw.msg( 'gather-error-unknown-collection' ),
			heading: mw.msg( 'gather-edit-collection-heading' ),
			nameLabel: mw.msg( 'gather-edit-collection-label-name' ),
			descriptionLabel: mw.msg( 'gather-edit-collection-label-description' ),
			publicLabel: mw.msg( 'gather-edit-collection-label-public' ),
			headerButtonsListClassName: 'overlay-action',
			editMsg: mw.msg( 'gather-overlay-edit-button' ),
			deleteMsg: mw.msg( 'gather-delete-button' ),
			saveMsg: mw.msg( 'gather-edit-collection-save-label' ),
			emptyInputMsg: mw.msg( 'gather-overlay-search-empty' )
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
			if ( options && options.collection ) {
				this.id = options.collection.id;
			}
			this.api = new CollectionsApi();
			Overlay.prototype.initialize.apply( this, arguments );
			this.$clear = this.$( '.search-header .clear' );
		},
		/** @inheritdoc */
		postRender: function () {
			var id = this.id;
			Overlay.prototype.postRender.apply( this, arguments );

			if ( id ) {
				this._populateCollectionMembers();
			} else {
				this.id = null;
				// New collection privacy default to public
				this.$( '.privacy' ).prop( 'checked', true );
				this._switchToEditPane();
			}

		},
		/**
		 * Set up collection search panel with existing members
		 * @private
		 */
		_populateCollectionMembers: function () {
			var self = this;
			this.$( '.manage-members-pane' ).removeClass( 'hidden' );
			this.api.getCollectionMembers( this.id ).done( function ( pages ) {
				self.searchPanel = new CollectionSearchPanel( {
					collection: self.options.collection,
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
			var deleteOverlay = new CollectionDeleteOverlay( {
				collection: this.options.collection
			} );
			deleteOverlay.show();
		},
		/**
		 * Event handler when the back button is clicked on the title/edit description pane.
		 */
		onBackClick: function () {
			if ( this.id ) {
				// reset the values to their original values.
				this.$( 'input.title' ).val( this.options.collection.title );
				this.$( '.description' ).val( this.options.collection.description );
				// Note: we will need to reset checkbox when enabling private/public toggle.
				this._switchToFirstPane();
			} else {
				Overlay.prototype.hide.apply( this, arguments );
			}
		},
		/**
		 * Refresh the page
		 * @private
		 */
		_reloadCollection: function () {
			var self = this;
			window.setTimeout( function () {
				router.navigate( '/' );
				if ( self.options.reloadOnSave ) {
					window.location.reload();
				}
			}, 100 );
		},
		/**
		 * Event handler when the continue button is clicked in the title/edit description pane.
		 */
		onFirstPaneSaveClick: function () {
			var self = this;

			if ( this.searchPanel.hasChanges() ) {
				this.$( '.save' ).prop( 'disabled', true );
				this.searchPanel.saveChanges().done( function () {
					if ( self.options.reloadOnSave ) {
						futureToast.add( self.options.editSuccessMsg, 'toast' );
					} else {
						toast.show( self.options.editSuccessMsg, 'toast' );
					}
					self._reloadCollection();
				} );
			} else if ( this._stateChanged ) {
				this.hide();
				this._reloadCollection();
			} else {
				// nothing to do.
				self.hide();
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
				this.api.editCollection( this.id, title, description, isPrivate ).done( function ( data ) {
					var eventParams = {
						eventName: 'edit-collection'
					};
					self.$( '.mw-ui-input, .save-description' ).prop( 'disabled', false );
					toast.show( self.options.editSuccessMsg, 'toast' );
					if ( self.id === null ) {
						// Set the overlay id to the newly created collection id
						self.id = data.editlist.id;
						self.options.collection = {
							id: data.editlist.id,
							title: title,
							description: description
						};
						self._populateCollectionMembers();
						eventParams.eventName = 'new-collection';
						eventParams.source = 'special-gather';
					}
					schema.log( eventParams ).always( function () {
						self._switchToFirstPane();
						// Make sure when the user leaves the overlay the page gets refreshed
						self._stateChanged = true;
					} );
				} ).fail( function ( errMsg ) {
					toast.show( self.options.editFailedError, 'toast error' );
					// Make it possible to try again.
					self.$( '.mw-ui-input, .save-description' ).prop( 'disabled', false );
					schema.log( {
						eventName: 'edit-collection-error',
						errorMessage: errMsg
					} );
				} );
			} else {
				toast.show( this.options.editFailedError, 'toast error' );
			}

		},
		/** @inheritdoc */
		hide: function () {
			if ( this.searchPanel.hasChanges() ) {
				if ( !window.confirm( this.options.confirmExitMessage ) ) {
					return;
				}
			}
			return Overlay.prototype.hide.apply( this, arguments );
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

	M.define( 'ext.gather.collection.edit/CollectionEditOverlay', CollectionEditOverlay );

}( mw.mobileFrontend, jQuery ) );
