( function ( M, $ ) {

	var CollectionsList,
		InfiniteScroll = M.require( 'InfiniteScroll' ),
		icons = M.require( 'icons' ),
		CollectionsApi = M.require( 'ext.gather.api/CollectionsApi' ),
		toast = M.require( 'toast' ),
		View = M.require( 'View' );

	CollectionsList = View.extend( {
		defaults: {
			enhance: false,
			collections: []
		},
		template: mw.template.get( 'ext.gather.collections.list', 'CollectionsList.hogan' ),
		templatePartials: {
			item: mw.template.get( 'ext.gather.collections.list', 'CollectionsListItemCard.hogan' ),
			image: mw.template.get( 'ext.gather.collections.list', 'CardImage.hogan' )
		},
		/** @inheritdoc */
		initialize: function ( options ) {
			if ( options.enhance ) {
				this.template = false;
			}
			View.prototype.initialize.apply( this, arguments );
			// After the initial render initialize the infinite scrolling.
			this.$pagination = this.$el.find( '.collections-pagination' );
			if ( this.$pagination.length ) {
				this._replacePaginationControls();
				this.api = new CollectionsApi();
				this.infiniteScroll = new InfiniteScroll();
				this.infiniteScroll.setElement( this.$el );
				this.infiniteScroll.on( 'load', $.proxy( this, '_loadCollections' ) );
			}
		},
		/**
		 * Replace html link pagination controls with components for the infinite
		 * scrolling
		 */
		_replacePaginationControls: function () {
			this.continueArgs = {
				lstcontinue: this._parseContinueUrl(
					this.$pagination.children( 'a' ).attr( 'href' )
				)
			};
			this.$pagination.html( icons.spinner().toHtmlString() );
			this.$pagination.hide();
		},
		/**
		 * Parse the pagination href to get the continue param
		 * @param {String} url to parse
		 * @return {String} continue parameter
		 */
		_parseContinueUrl: function ( url ) {
			var params = url.split( '?' )[ 1 ].split( '&' ),
				param = null;
			$.each( params, function ( i, p ) {
				if ( p.indexOf( 'lstcontinue=' ) !== -1 ) {
					param = decodeURIComponent( p.split( '=' )[ 1 ] );
				}
			} );
			return param;
		},
		/**
		 * Load more collections from the API
		 */
		_loadCollections: function () {
			var self = this;
			if ( this.continueArgs ) {
				this.$pagination.show();
				this.api.getCurrentUsersCollections( this.options.userName, null, this.continueArgs )
				.always( function () {
					self.$pagination.hide();
					self.infiniteScroll.enable();
				} )
				.done( function ( data ) {
					self.continueArgs = data.continueArgs || false;
					self.renderCollections( data.collections );
				} )
				.fail( function () {
					toast.show( mw.msg( 'gather-lists-more-failed' ), 'toast error' );
				} );
			}
		},
		/**
		 * Render collections into the view.
		 * @param {Array} collections to render
		 */
		renderCollections: function ( collections ) {
			var self = this;
			this.$pagination.before( $.map( collections, function ( coll ) {
				return self.templatePartials.item.render( $.extend( {}, coll, {
					langdir: 'ltr',
					articleCountMsg: mw.msg( 'gather-article-count', coll.count ),
					privacyMsg: self._getPrivacyMsg( coll.perm ),
					collectionUrl: self._getUrl( coll.id ),
					hasImage: Boolean( coll.image ),
					image: self.templatePartials.image.render( {
						url: coll.imageurl,
						wide: coll.imagewidth > coll.imageheight
					} )
				} ) );
			} ) );
		},
		/**
		 * Get the url for a collection
		 * @param {Number} id of the collection
		 * @return {String}
		 */
		_getUrl: function ( id ) {
			return mw.util.getUrl( [
				'Special:Gather',
				'id',
				id
			].join( '/' ) );
		},
		/**
		 * Return privacy message depending on collection perm
		 * @param {String} perm status of the collection
		 * @return {String}
		 */
		_getPrivacyMsg: function ( perm ) {
			switch ( perm ) {
				case 'public': return mw.msg( 'gather-public' );
				case 'private': return mw.msg( 'gather-private' );
				case 'hidden': return mw.msg( 'gather-hidden' );
			}
		}
	} );

	M.define( 'ext.gather.collections.list/CollectionsList', CollectionsList );

} )( mw.mobileFrontend, jQuery );
