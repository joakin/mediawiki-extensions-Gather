( function ( M, $ ) {

	var CollectionsContentOverlay,
		icons = M.require( 'icons' ),
		toast = M.require( 'toast' ),
		Icon = M.require( 'Icon' ),
		CollectionsApi = M.require( 'ext.gather.watchstar/CollectionsApi' ),
		ContentOverlay = M.require( 'modules/tutorials/ContentOverlay' );

	/**
	 * A clickable watchstar for managing collections
	 * @class CollectionsContentOverlay
	 * @extends ContentOverlay
	 */
	CollectionsContentOverlay = ContentOverlay.extend( {
		/**
		 * FIXME: re-evaluate content overlay default classes/css.
		 * @inheritdoc
		 */
		className: 'collection-overlay content-overlay overlay position-fixed',
		/** @inheritdoc */
		templatePartials: {
			content: mw.template.get( 'ext.gather.watchstar', 'content.hogan' )
		},
		/** @inheritdoc */
		events: {
			click: 'onClickInsideOverlay',
			'click .overlay-content li': 'onSelectCollection',
			'submit form': 'onCreateNewCollection'
		},
		/** @inheritdoc */
		hasFixedHeader: false,
		/** @inheritdoc */
		defaults: {
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
			subheadingNewCollection: mw.msg( 'gather-add-to-new' ),
			subheading: mw.msg( 'gather-add-to-existing' ),
			collections: []
		},
		/** @inheritdoc */
		initialize: function () {
			this.api = new CollectionsApi();
			ContentOverlay.prototype.initialize.apply( this, arguments );
		},
		/** @inheritdoc */
		postRender: function () {
			this.$( '.spinner' ).hide();
		},
		/**
		 * Event handler for setting up a new collection
		 * @param {jQuery.Event} ev
		 */
		onCreateNewCollection: function ( ev ) {
			var page = M.getCurrentPage(),
				title = $( ev.target ).find( 'input' ).val();

			ev.preventDefault();
			this.addCollection( title, page );
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
				$target = $( ev.target ),
				page = M.getCurrentPage();

			collection = {
				title: $target.data( 'collection-title' ),
				id:  $target.data( 'collection-id' ),
				isWatchlist: $target.data( 'collection-is-watchlist' )
			};
			this.$( '.spinner' ).show();
			if ( $target.data( 'collection-is-member' ) ) {
				this.removeFromCollection( collection, page );
			} else {
				this.addToCollection( collection, page );
			}
			ev.stopPropagation();
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
			this.$( '.spinner' ).hide();
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
			return this.api.removePageFromCollection( collection.id, page ).done(
				$.proxy( this, '_collectionStateChange', collection, false )
			);
		},
		/**
		 * Communicate with API to add page to collection
		 * @param {Object} collection to update
		 * @param {Page} page to add to collection
		 */
		addToCollection: function ( collection, page ) {
			return this.api.addPageToCollection( collection.id, page ).done(
				$.proxy( this, '_collectionStateChange', collection, true )
			);
		},
		/**
		 * Communicate with API to create a collection
		 * @param {String} title of collection
		 * @param {Page} page to add to collection
		 */
		addCollection: function ( title, page ) {
			var self = this,
				api = this.api;

			return api.addCollection( title ).done( function ( collection ) {
				api.addPageToCollection( collection.id, page ).done(
					$.proxy( self, '_collectionStateChange', collection, true )
				).fail( function () {
					toast.show( mw.msg( 'gather-add-failed-toast', title ), 'toast' );
					// Hide since collection was created properly and list is outdated
					self.hide();
				} );
			} ).fail( function () {
				toast.show( mw.msg( 'gather-new-collection-failed-toast', title ), 'toast' );
			} );
		}
	} );
	M.define( 'ext.gather.watchstar/CollectionsContentOverlay', CollectionsContentOverlay );

}( mw.mobileFrontend, jQuery ) );