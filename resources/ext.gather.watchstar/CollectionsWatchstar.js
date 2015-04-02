// jscs:disable requireCamelCaseOrUpperCaseIdentifiers
( function ( M ) {

	var CollectionsWatchstar,
		SchemaGather = M.require( 'ext.gather.logging/SchemaGather' ),
		schema = new SchemaGather(),
		CtaDrawer = M.require( 'CtaDrawer' ),
		CollectionsContentOverlay = M.require( 'ext.gather.watchstar/CollectionsContentOverlay' ),
		Icon = M.require( 'Icon' ),
		// FIXME: MobileFrontend code duplication
		watchIcon = new Icon( {
			name: 'watch',
			additionalClassNames: 'icon-32px watch-this-article'
		} ),
		watchedIcon = new Icon( {
			name: 'watched',
			additionalClassNames: 'icon-32px watch-this-article'
		} ),
		user = M.require( 'user' ),
		View = M.require( 'View' );

	/**
	 * A clickable watchstar for managing collections
	 * @class CollectionsWatchstar
	 * @extends View
	 */
	CollectionsWatchstar = View.extend( {
		/**
		 * @inheritdoc
		 */
		events: {
			// Disable clicks on original link
			'click a': 'onLinksClick',
			click: 'onStatusToggle'
		},
		tagName: 'li',
		className: 'collection-star-container',
		template: mw.template.get( 'ext.gather.watchstar', 'star.hogan' ),
		/** @inheritdoc */
		ctaDrawerOptions: {
			content: mw.msg( 'gather-anon-cta' ),
			queryParams: {
				campaign: 'gather',
				returntoquery: 'article_action=add_to_collection'
			}
		},
		/**
		 * @inheritdoc
		 * @cfg {Object} defaults Default options hash.
		 * @cfg {Number} defaults.inCollections number of collections the current page appears in
		 * @cfg {Array} defaults.collections definitions of the users existing collections
		 * @cfg {Boolean} defaults.wasUserPrompted a flag which identifies if the user was prompted
		 *  e.g. by WatchstarPageActionOverlay
		 */
		defaults: {
			page: M.getCurrentPage(),
			inCollections: 0,
			label: mw.msg( 'gather-watchstar-button-label' ),
			wasUserPrompted: false,
			collections: undefined
		},
		/** @inheritdoc */
		preRender: function ( options ) {
			options.watchIconClass = options.isWatched ? watchedIcon.getClassName() :
				watchIcon.getClassName();
		},
		/** @inheritdoc */
		postRender: function ( options ) {
			var $el = this.$el;

			// For newly authenticated users via CTA force dialog to open.
			if ( options.isNewlyAuthenticatedUser ) {
				setTimeout( function () {
					$el.trigger( 'click' );
				}, 500 );
				delete options.isNewlyAuthenticatedUser;
			}
			$el.removeClass( 'hidden' );
		},
		/**
		 * Prevent default on incoming events
		 * @param {jQuery.Event} ev
		 */
		onLinksClick: function ( ev ) {
			ev.preventDefault();
		},
		/**
		 * Triggered when a user anonymously clicks on the watchstar.
		 * @method
		 */
		onStatusToggleAnon: function () {
			if ( !this.drawer ) {
				this.drawer = new CtaDrawer( this.ctaDrawerOptions );

			}
			this.drawer.show();
		},
		/** @inheritdoc */
		onStatusToggleUser: function ( ev ) {
			// Open the collections content overlay to deal with this.
			var overlay = this.overlay,
				self = this;

			if ( !overlay ) {
				// cache it so state changes internally for this session
				this.overlay = overlay = new CollectionsContentOverlay( {
					page: this.options.page,
					// FIXME: Should be retrievable from Page
					description: mw.config.get( 'wgMFDescription' ),
					// FIXME: Should be retrievable from Page
					pageImageUrl: mw.config.get( 'wgGatherPageImageThumbnail' ),
					collections: this.options.collections
				} );
				overlay.on( 'collection-watch', function ( collection ) {
					if ( collection.isWatchlist ) {
						self.newStatus( true );
					}
				} );
				overlay.on( 'collection-unwatch', function ( collection ) {
					if ( collection.isWatchlist ) {
						self.newStatus( false );
					}
				} );
			}
			overlay.show();
			ev.stopPropagation();
		},
		/** @inheritdoc */
		onStatusToggle: function () {
			if ( user.isAnon() ) {
				this.onStatusToggleAnon.apply( this, arguments );
			} else {
				this.onStatusToggleUser.apply( this, arguments );
			}
			schema.log( {
				eventName: 'click',
				source: this.options.wasUserPrompted ? 'onboarding' : 'unknown'
			} );
		},
		/**
		 * Sets a new status on the watchstar.
		 * Only executed for the special Watchlist collection.
		 * @param {bool} newStatus
		 */
		newStatus: function ( newStatus ) {
			if ( newStatus ) {
				this.options.isWatched = true;
				this.render();
				/**
				 * @event watch
				 * Fired when the watch star is changed to watched status
				 */
				this.emit( 'watch' );
			} else {
				this.options.isWatched = false;
				this.render();
				/**
				 * @event unwatch
				 * Fired when the watch star is changed to unwatched status
				 */
				this.emit( 'unwatch' );
			}
		}
	} );
	M.define( 'ext.gather.watchstar/CollectionsWatchstar', CollectionsWatchstar );

}( mw.mobileFrontend ) );
