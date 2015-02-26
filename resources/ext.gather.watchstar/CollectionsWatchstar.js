// jscs:disable requireCamelCaseOrUpperCaseIdentifiers
( function ( M, $ ) {

	var CollectionsWatchstar,
		CollectionsContentOverlay = M.require( 'ext.gather.watchstar/CollectionsContentOverlay' ),
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
		 * @cfg {Object} defaults.collections definitions of the users existing collections
		 */
		defaults: $.extend( {}, Watchstar.prototype.defaults, {
			collections: []
		} ),
		/** @inheritdoc */
		postRender: function ( options ) {
			var $el = this.$el;
			Watchstar.prototype.postRender.apply( this, arguments );
			// For newly authenticated users via CTA force dialog to open.
			if ( options.isNewlyAuthenticatedUser ) {
				setTimeout( function () {
					$el.trigger( 'click' );
				}, 500 );
				delete options.isNewlyAuthenticatedUser;
			}
		},
		/** @inheritdoc */
		onStatusToggle: function ( ev ) {
			// Open the collections content overlay to deal with this.
			var self = this,
				overlay = new CollectionsContentOverlay( {
					collections: this.options.collections
				} );
			overlay.on( 'watch', function () {
				self.newStatus( true );
			} );
			overlay.on( 'unwatch', function () {
				self.newStatus( false );
			} );
			overlay.show();
			ev.stopPropagation();
		},
		/**
		 * Sets a new status on the watchstar
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
