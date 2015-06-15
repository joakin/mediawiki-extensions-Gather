( function ( M, $ ) {
	var CollectionsContentOverlay,
		SchemaGather = M.require( 'ext.gather.logging/SchemaGather' ),
		schema = new SchemaGather(),
		icons = M.require( 'icons' ),
		toast = M.require( 'toast' ),
		user = M.require( 'user' ),
		Icon = M.require( 'Icon' ),
		Button = M.require( 'Button' ),
		ToastPanel = M.require( 'ext.gather.toastpanel/ToastPanel' ),
		toastPanel = new ToastPanel().appendTo( document.body ),
		CollectionsApi = M.require( 'ext.gather.api/CollectionsApi' ),
		CollectionsContentOverlayBase = M.require( 'ext.gather.collection.base/CollectionsContentOverlayBase' ),
		ButtonWithSpinner = M.require( 'ext.gather.buttonspinner/ButtonWithSpinner' );

	/**
	 * A clickable watchstar for managing collections
	 * @class CollectionsContentOverlay
	 * @extends CollectionsContentOverlayBase
	 */
	CollectionsContentOverlay = CollectionsContentOverlayBase.extend( {
		/**
		 * FIXME: re-evaluate content overlay default classes/css.
		 * @inheritdoc
		 */
		className: 'collection-overlay content-overlay overlay position-fixed',
		/** @inheritdoc */
		templatePartials: {
			content: mw.template.get( 'ext.gather.collection.contentOverlay', 'content.hogan' )
		},
		appendTo: 'body',
		/** @inheritdoc */
		events: {
			'click .more-collections': 'onClickLoadMoreCollections',
			click: 'onClickInsideOverlay',
			'focus input': 'onFocusInput',
			'blur input': 'onBlurInput',
			'input input': 'onInput',
			'click .overlay-content li': 'onSelectCollection',
			'touchend .create-collection': 'onCreateNewCollection',
			'submit form': 'onCreateNewCollection'
		},
		/** @inheritdoc */
		hasFixedHeader: false,
		/** @inheritdoc */
		defaults: {
			page: undefined,
			/** @inheritdoc */
			fixedHeader: false,
			iconButton: new Icon( {
				name: 'tick',
				label: mw.msg( 'gather-collection-member' )
			} ).toHtmlString(),
			iconDisabledButton: new Icon( {
				name: 'tick-disabled',
				label: mw.msg( 'gather-collection-non-member' )
			} ).toHtmlString(),
			title: mw.config.get( 'wgTitle' ),
			spinner: icons.spinner().toHtmlString(),
			placeholder: mw.msg( 'gather-add-new-placeholder' ),
			subheadingNewCollection: mw.msg( 'gather-add-to-new' ),
			subheading: mw.msg( 'gather-add-to-existing' ),
			moreLinkLabel: mw.msg( 'gather-add-to-another' ),
			createButtonLabel: mw.msg( 'gather-create-new-button-label' ),
			collections: undefined
		},
		/** @inheritdoc */
		initialize: function ( options ) {
			this.api = new CollectionsApi();
			this.createButton = new ButtonWithSpinner( {
				label: this.defaults.createButtonLabel,
				additionalClassNames: 'create-collection',
				constructive: true,
				disabled: true,
				loading: false
			} );
			if ( options.collections === undefined ) {
				options.collections = [];
				CollectionsContentOverlayBase.prototype.initialize.call( this, options );
				// load the collections.
				this.showSpinner();
				this._loadCollections( user.getName(), options.page );
			} else {
				CollectionsContentOverlayBase.prototype.initialize.call( this, options );
			}
			// This should be an event on the view itself.
			M.on( 'resize', $.proxy( this, 'expandForm' ) );
		},
		/** @inheritdoc */
		show: function () {
			CollectionsContentOverlayBase.prototype.show.apply( this, arguments );
			$( 'html' ).addClass( 'gather-overlay-enabled' );
		},
		/** @inheritdoc */
		hide: function () {
			$( 'html' ).removeClass( 'gather-overlay-enabled' );
			schema.log( {
				eventName: 'hide'
			} );
			return CollectionsContentOverlayBase.prototype.hide.apply( this, arguments );
		},
		/** @inheritdoc */
		postRender: function () {
			CollectionsContentOverlayBase.prototype.postRender.apply( this );
			if ( this.$( '.create-collection' ).length === 0 ) {
				this.$( 'form' ).append( this.createButton.$el );
			}
			this.expandForm();
		},
		/**
		 * Adjust the form so that it takes up the available screen.
		 */
		expandForm: function () {
			var width = this.$( 'form' ).width() - this.createButton.$el.outerWidth() - 10;
			this.$( 'form .create-collection-input' )
				.css( 'width', width );
		},
		/**
		 * Tests if title is valid
		 * FIXME: This is duplicating code from CollectionEditOverlay.js
		 * @param {String} title Proposed collection title
		 * @returns {Boolean}
		 */
		isTitleValid: function ( title ) {
			return title.length <= 90;
		},
		/**
		 * Loads collections from the api and renders them in the view.
		 * @param {String} username
		 * @param {Page} page
		 * @param {Object} qs query string parameters to query the api with
		 * @private
		 * @returns {jQuery.promise}
		 */
		_loadCollections: function ( username, page, qs ) {
			var self = this;

			return this.api.getCurrentUsersCollections( username, page, qs ).done(
				function ( resp ) {
					var reset,
						s = self._scrollTop || 0,
						threshold = 100,
						curScrollTop = self.$( '.overlay-content' ).scrollTop();

					self.options.collections = self.options.collections.concat( resp.collections );
					if ( resp.continueArgs ) {
						self.options.moreLink = true;
						self._continue = resp.continueArgs;
					} else {
						self.options.moreLink = false;
						self._continue = false;
					}
					if ( s > curScrollTop - threshold && s < curScrollTop + threshold ) {
						reset = true;
					}
					self.render.call( self );
					if ( reset ) {
						// reset the scroll top to avoid losing the current place of the user.
						self.$( '.overlay-content' ).scrollTop( s );
						self._scrollTop = 0;
					}
				} );
		},
		/**
		 * Event handler for clicking on the load more collections link
		 * @param {jQuery.Event} ev
		 */
		onClickLoadMoreCollections: function ( ev ) {
			this._scrollTop = this.$( '.overlay-content' ).scrollTop();

			$( ev.currentTarget ).remove();
			this._loadCollections( user.getName(), this.options.page, this._continue );
		},
		/**
		 * Event handler for focusing input
		 * @param {jQuery.Event} ev
		 */
		onFocusInput: function ( ev ) {
			// switch to compact mode if space is limited.
			if ( $( window ).height() < 600 ) {
				this.$el.addClass( 'compact' );
			}
			ev.currentTarget.scrollIntoView();
		},
		/**
		 * Event handler for blurring input
		 */
		onBlurInput: function () {
			this.$el.removeClass( 'compact' );
		},
		/**
		 * Event handler for entering input.
		 * @param {jQuery.Event} ev
		 */
		onInput: function ( ev ) {
			var $input = $( ev.target ),
				val = $input.val();
			this.createButton.disabled( val === '' );
		},
		/**
		 * Event handler for setting up a new collection
		 * @param {jQuery.Event} ev
		 */
		onCreateNewCollection: function ( ev ) {
			var page = M.getCurrentPage(),
				title = this.$( '.create-collection-input' ).val();

			ev.preventDefault();
			if ( this.isTitleValid( title ) ) {
				this.addCollection( title, page );
				schema.log( {
					eventName: 'new-collection'
				} );
			} else {
				toast.show( mw.msg( 'gather-add-title-invalid-toast' ), 'toast error' );
			}
		},
		/**
		 * Event handler for all clicks inside overlay.
		 * @param {jQuery.Event} ev
		 */
		onClickInsideOverlay: function ( ev ) {
			ev.stopPropagation();
		},
		/**
		 * Event handler for selecting an existing collection.
		 * @param {jQuery.Event} ev
		 */
		onSelectCollection: function ( ev ) {
			var collection,
				$target = $( ev.currentTarget ),
				page = M.getCurrentPage();

			collection = {
				title: $target.data( 'collection-title' ),
				id:  $target.data( 'collection-id' ),
				isWatchlist: $target.data( 'collection-is-watchlist' )
			};
			this.showSpinner();
			if ( $target.data( 'collection-is-member' ) ) {
				this.removeFromCollection( collection, page );
			} else {
				this.addToCollection( collection, page );
			}
			ev.stopPropagation();
			schema.log( {
				eventName: 'select-collection'
			} );
		},
		/**
		 * Internal event for updating existing known states of users collections.
		 * @private
		 * @param {Object} collection
		 * @param {Boolean} currentPageIsMember whether the current page is in this collection
		 */
		_collectionStateChange: function ( collection, currentPageIsMember ) {
			var isNew = true;

			// update the stored state of the collection
			$.each( this.options.collections, function () {
				if ( this.id === collection.id ) {
					isNew = false;
					this.titleInCollection = currentPageIsMember;
					$.extend( this, collection );
				}
			} );
			// push the newly created collection
			if ( isNew ) {
				// by default this will be true.
				collection.titleInCollection = true;
				collection.isNew = true;
				this.options.collections.push( collection );
			}
			// refresh the ui
			this.render();
			// update UI
			this.hideSpinner();
			this.hide();
		},
		/**
		 * Emit events for the actions taken and show notifications (panel)
		 * @private
		 * @param {Object} collection
		 * @param {Boolean} currentPageIsMember whether the current page is in this collection
		 */
		_notifyChanges: function ( collection, currentPageIsMember ) {
			var msg;
			if ( currentPageIsMember ) {
				this.emit( 'collection-watch', collection, collection.isNew );
				msg = mw.msg( 'gather-add-toast', collection.title );
			} else {
				this.emit( 'collection-unwatch', collection );
				msg = mw.msg( 'gather-remove-toast', collection.title );
			}
			toastPanel.render( {
				msg: msg,
				actions: [
					new Button( {
						progressive: true,
						href: mw.util.getUrl( 'Special:Gather/id/' + collection.id ),
						label: mw.msg( 'gather-view-collection' )
					} ).options
				]
			} );
			toastPanel.show();
		},
		/**
		 * Communicate with API to remove page from collection
		 * @param {Object} collection to update
		 * @param {Page} page to remove from collection
		 */
		removeFromCollection: function ( collection, page ) {
			var self = this;
			return this.api.removePageFromCollection( collection.id, page ).done( function () {
				self._collectionStateChange( collection, false );
				self._notifyChanges( collection, false );
			} ).fail( function ( errMsg ) {
				schema.log( {
					eventName: 'remove-collection-error',
					errorText: errMsg
				} );
				self.hideSpinner();
				toast.show( mw.msg( 'gather-remove-from-collection-failed-toast' ), 'toast error' );
			} );
		},
		/**
		 * Communicate with API to add page to collection
		 * @param {Object} collection to update
		 * @param {Page} page to add to collection
		 */
		addToCollection: function ( collection, page ) {
			var self = this;
			return this.api.addPageToCollection( collection.id, page ).done( function () {
				self._collectionStateChange( collection, true );
				self._notifyChanges( collection, true );
			} ).fail( function () {
				schema.log( {
					eventName: 'add-collection-error'
				} );
				self.hideSpinner();
				toast.show( mw.msg( 'gather-add-to-collection-failed-toast' ), 'toast error' );
			} );
		},
		/**
		 * Launches editor mode for given collection
		 * @param {Number} id of collection
		 */
		loadEditor: function ( id ) {
			window.location.hash = '#/collection/edit/' + id;
		},
		/**
		 * Communicate with API to create a collection
		 * @param {String} title of collection
		 * @param {Page} page to add to collection
		 */
		addCollection: function ( title, page ) {
			var self = this,
				api = this.api;

			this.createButton.loading( true );
			this.expandForm();
			return api.addCollection( title ).done( function ( collection ) {
				api.addPageToCollection( collection.id, page ).done( function () {
					self._collectionStateChange( collection, true );
					M.once( 'collection-edit-completed', function ( newCollection ) {
						collection = $.extend( collection, newCollection );
						self._collectionStateChange( collection, true );
						self._notifyChanges( collection, true );
					} );
					self.loadEditor( collection.id );
				} ).fail( function () {
					toast.show( mw.msg( 'gather-add-failed-toast', title ), 'toast' );
					// Hide since collection was created properly and list is outdated
					self.hide();
				} );
			} ).fail( function ( errMsg ) {
				schema.log( {
					eventName: 'create-collection-error',
					errorText: errMsg
				} );
				toast.show( mw.msg( 'gather-new-collection-failed-toast', title ), 'toast error' );
			} ).always( function () {
				self.createButton.loading( false );
				self.expandForm();
			} );
		}
	} );
	M.define( 'ext.gather.watchstar/CollectionsContentOverlay', CollectionsContentOverlay );

}( mw.mobileFrontend, jQuery ) );
