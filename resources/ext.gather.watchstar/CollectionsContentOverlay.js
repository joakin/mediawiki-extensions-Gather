( function ( M, $ ) {
	var CollectionsContentOverlay,
		SchemaGather = M.require( 'ext.gather.logging/SchemaGather' ),
		schema = new SchemaGather(),
		icons = M.require( 'icons' ),
		toast = M.require( 'toast' ),
		user = M.require( 'user' ),
		Icon = M.require( 'Icon' ),
		CollectionsApi = M.require( 'ext.gather.watchstar/CollectionsApi' ),
		CollectionsContentOverlayBase = M.require( 'ext.gather.collection.base/CollectionsContentOverlayBase' );

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
			content: mw.template.get( 'ext.gather.watchstar', 'content.hogan' )
		},
		appendTo: 'body',
		/** @inheritdoc */
		events: {
			click: 'onClickInsideOverlay',
			'focus input': 'onFocusInput',
			'blur input': 'onBlurInput',
			'input input': 'onInput',
			'click .overlay-content li': 'onSelectCollection',
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
			createButtonLabel: mw.msg( 'gather-create-new-button-label' ),
			placeholder: mw.msg( 'gather-add-new-placeholder' ),
			subheadingNewCollection: mw.msg( 'gather-add-to-new' ),
			subheading: mw.msg( 'gather-add-to-existing' ),
			collections: undefined
		},
		/** @inheritdoc */
		initialize: function ( options ) {
			var self = this;
			this.api = new CollectionsApi();
			if ( options.collections === undefined ) {
				options.collections = [];
				CollectionsContentOverlayBase.prototype.initialize.call( this, options );
				// load the collections.
				this.showSpinner();
				this.api.getCurrentUsersCollections( user.getName(), options.page ).done( function ( collections ) {
					self.options.collections = collections;
					self.render.call( self );
				} );
			} else {
				CollectionsContentOverlayBase.prototype.initialize.call( this, options );
			}
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
			this.hideSpinner();
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
				val = $input.val(),
				$button = $input.next( 'button' );
			$button.prop( 'disabled', val === '' );
		},
		/**
		 * Event handler for setting up a new collection
		 * @param {jQuery.Event} ev
		 */
		onCreateNewCollection: function ( ev ) {
			var page = M.getCurrentPage(),
				title = $( ev.target ).find( 'input' ).val();

			ev.preventDefault();
			if ( this.isTitleValid( title ) ) {
				this.showSpinner();
				this.addCollection( title, page );
				schema.log( {
					eventName: 'new-collection'
				} );

				this.addCollection( title, page );
			} else {
				toast.show( mw.msg( 'gather-add-failed-toast', title ), 'toast' );
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
				}
			} );
			// push the newly created collection
			if ( isNew ) {
				// by default this will be true.
				collection.titleInCollection = true;
				this.options.collections.push( collection );
			}
			// refresh the ui
			this.render();
			// update UI
			this.hideSpinner();
			this.hide();

			if ( currentPageIsMember ) {
				this.emit( 'collection-watch', collection );
				// show toast
				toast.show( mw.msg( 'gather-add-toast', collection.title ), 'toast' );
			} else {
				this.emit( 'collection-unwatch', collection );
				toast.show( mw.msg( 'gather-remove-toast', collection.title ), 'toast' );
			}
		},
		/**
		 * Communicate with API to remove page from collection
		 * @param {Object} collection to update
		 * @param {Page} page to remove from collection
		 */
		removeFromCollection: function ( collection, page ) {
			var self = this;
			return this.api.removePageFromCollection( collection.id, page ).done(
				$.proxy( this, '_collectionStateChange', collection, false )
			).fail( function ( errMsg ) {
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
			return this.api.addPageToCollection( collection.id, page ).done(
				$.proxy( this, '_collectionStateChange', collection, true )
			).fail( function () {
				schema.log( {
					eventName: 'add-collection-error'
				} );
				self.hideSpinner();
				toast.show( mw.msg( 'gather-add-to-collection-failed-toast' ), 'toast error' );
			} );
		},
		/**
		 * Communicate with API to create a collection
		 * @param {String} title of collection
		 * @param {Page} page to add to collection
		 */
		addCollection: function ( title, page ) {
			var self = this,
				api = this.api;

			this.showSpinner();
			return api.addCollection( title ).done( function ( collection ) {
				api.addPageToCollection( collection.id, page ).done(
					$.proxy( self, '_collectionStateChange', collection, true )
				).fail( function () {
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
				self.hideSpinner();
			} );
		}
	} );
	M.define( 'ext.gather.watchstar/CollectionsContentOverlay', CollectionsContentOverlay );

}( mw.mobileFrontend, jQuery ) );
