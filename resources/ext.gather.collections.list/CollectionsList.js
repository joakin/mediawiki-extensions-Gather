( function ( M, $ ) {

	var CollectionsList,
		InfiniteScroll = M.require( 'InfiniteScroll' ),
		icons = M.require( 'icons' ),
		CollectionsApi = M.require( 'ext.gather.api/CollectionsApi' ),
		toast = M.require( 'toast' ),
		View = M.require( 'View' ),
		Icon = M.require( 'Icon' ),
		CreateCollectionButton = M.require( 'ext.gather.collections.list/CreateCollectionButton' );

	CollectionsList = View.extend( {
		/** @inheritdoc */
		defaults: {
			collections: [],
			// FIXME: Use the icon partials in server and client when supported in server templates.
			userIconClass: new Icon( {
				name: 'profile',
				hasText: true
			} ).getClassName()
		},
		template: mw.template.get( 'ext.gather.collections.list', 'CollectionsList.hogan' ),
		templatePartials: {
			item: mw.template.get( 'ext.gather.collections.list', 'CollectionsListItemCard.hogan' ),
			image: mw.template.get( 'ext.gather.collections.list', 'CardImage.hogan' )
		},
		/** @inheritdoc */
		initialize: function () {
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
		/** @inheritdoc */
		postRender: function () {
			// Look for rendered list in the dom
			var $collectionsList = $( '.collections-list' );
			// Add a create button at the bottom if the list owner is viewing in minerva skin
			if ( $collectionsList.data( 'is-owner' ) && mw.config.get( 'skin' ) === 'minerva' ) {
				new CreateCollectionButton( {} )
					.appendTo( $collectionsList.find( '.collection-actions' ) );
			}
			View.prototype.postRender.apply( this, arguments );
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
				this._apiCallByMode()
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
		 * Call the api depending on the collectionslist mode
		 * @return {jQuery.Deferred} Contains a list of collections
		 */
		_apiCallByMode: function () {
			if ( this.options.mode === 'recent' ) {
				return this.api.getCollections( null, $.extend( this.continueArgs, {
						lstminitems: 4
					} ) );
			} else {
				return this.api.getCurrentUsersCollections( this.options.owner, null, this.continueArgs );
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
					// If the collection has an owner, don't show it in the cards.
					owner: Boolean( self.options.owner ) ? null : {
						label: coll.owner,
						link: self._getOwnerUrl( coll.owner ),
						className: self.options.userIconClass
					},
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
		 * Get the owner url
		 * @param {String} name of the owner
		 * @return {String}
		 */
		_getOwnerUrl: function ( name ) {
			return mw.util.getUrl( [ 'Special:Gather', 'by', name ].join( '/' ) );
		},
		/**
		 * Get the url for a collection
		 * @param {Number} id of the collection
		 * @return {String}
		 */
		_getUrl: function ( id ) {
			return mw.util.getUrl( [ 'Special:Gather', 'id', id ].join( '/' ) );
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
