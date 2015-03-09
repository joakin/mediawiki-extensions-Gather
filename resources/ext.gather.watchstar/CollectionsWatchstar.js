// jscs:disable requireCamelCaseOrUpperCaseIdentifiers
( function ( M, $ ) {

	var CollectionsWatchstar,
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
		Watchstar = M.require( 'modules/watchstar/Watchstar' );

	/**
	 * A clickable watchstar for managing collections
	 * @class CollectionsWatchstar
	 * @extends Watchstar
	 */
	CollectionsWatchstar = Watchstar.extend( {
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
		 * @cfg {Object} defaults.collections definitions of the users existing collections
		 */
		defaults: $.extend( {}, Watchstar.defaults, {
			page: M.getCurrentPage(),
			inCollections: 0,
			collections: []
		} ),
		/** @inheritdoc */
		postRender: function ( options ) {
			var $el = this.$el,
				unwatchedClass = watchIcon.getGlyphClassName(),
				watchedClass = watchedIcon.getGlyphClassName();

			// For newly authenticated users via CTA force dialog to open.
			if ( options.isNewlyAuthenticatedUser ) {
				setTimeout( function () {
					$el.trigger( 'click' );
				}, 500 );
				delete options.isNewlyAuthenticatedUser;
			}
			if ( options.isWatched ) {
				$el.addClass( watchedClass ).removeClass( unwatchedClass );
			} else {
				$el.addClass( unwatchedClass ).removeClass( watchedClass );
			}
			$el.removeClass( 'hidden' );
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
			}

			overlay.on( 'collection-watch', function ( collection ) {
				if ( collection.isWatchlist ) {
					self.newStatus( true );
				}
			} );
			overlay.on( 'collection-unwatch', function ( collection ) {
				if ( collection.isWatchlist ) {
					self.newStatus( true );
				}
			} );
			overlay.show();
			ev.stopPropagation();
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

}( mw.mobileFrontend, jQuery ) );
