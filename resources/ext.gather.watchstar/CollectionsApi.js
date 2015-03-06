/*jshint unused:vars */
( function ( M, $ ) {

	var CollectionsUserPageJSONApi,
		WatchstarApi = M.require( 'modules/watchstar/WatchstarApi' ),
		EditorApi = M.require( 'modules/editor/EditorApi' ),
		user = M.require( 'user' ),
		CollectionsApi;

	CollectionsUserPageJSONApi = EditorApi.extend( {
		/**
		 * Init api
		 * @param {Object} options
		 *   {Number} options.id Id of collection. If ommited it will act on
		 *   the index file
		 */
		initialize: function ( options ) {
			var suffix, base;

			options = options || {};
			suffix = options.id ? '/' + options.id + '.json' : '.json';
			base = 'User:' + user.getName() + '/GatherCollections';

			options.title = base + suffix;
			EditorApi.prototype.initialize.call( this, options );
		},
		/**
		 * Get the json content of a page based on constructor arguments
		 * @return {jQuery.Deferred}
		 */
		getJSONContent: function () {
			var d = $.Deferred();

			this.getContent().done( function ( resp ) {
				d.resolve( JSON.parse( resp ) );
			} ).fail( function () {
				d.reject();
			} );
			return d;
		},
		/**
		 * Save json content to a page based on constructor arguments
		 * @param {Object} object data to save
		 * @return {jQuery.Deferred}
		 */
		saveJSONContent: function ( object ) {
			this.setContent( JSON.stringify( object ) );
			return this.save( {
				summary: 'JSON Automagically saved by Extension:Gather CollectionsUserPageJSONApi'
			} );
		}
	} );

	/**
	 * API for managing collection items
	 *
	 * @class CollectionApi
	 * @extends Api
	 */
	CollectionsApi = WatchstarApi.extend( {
		boilerplate: {
			id: 0,
			title: 'Watchlist',
			owner: user.getName(),
			description: '',
			count: 0,
			image: '',
			// FIXME: Eek.
			public: false
		},
		/**
		 * Watch or unwatch a page. Used for interactions with the watchlist.
		 * @param {Page} page
		 * @param {bool} unwatch
		 * @returns {jQuery.Deferred}
		 */
		_watchAction: function ( page, unwatch ) {
			var data = {
				action: 'watch',
				title: page.getTitle()
			};

			if ( unwatch ) {
				data.unwatch = true;
			}
			return this.postWithToken( 'watch', data );
		},
		/**
		 * Add page to existing collection.
		 * @method
		 * @param {Number} id Identifier of collection
		 * @param {Page} page Page view object
		 * @return {jQuery.Deferred}
		 */
		addPageToCollection: function ( id, page ) {
			if ( id === 0 ) {
				return this._watchAction( page, false );
			}

			var d = $.Deferred(),
				tempApi = new CollectionsUserPageJSONApi( {
					id: id
				} );

			tempApi.getJSONContent().done( function ( collection ) {
				collection = collection || {};
				if ( !collection.items ) {
					collection.items = [];
				}
				if ( $.inArray( page.title, collection.items ) === -1 ) {
					collection.items.push( page.title );
					tempApi.saveJSONContent( collection )
						.done( $.proxy( d, 'resolve', {} ) )
						.fail( $.proxy( d, 'reject' ) );
				} else {
					// If it is already there just resolve
					d.resolve( {} );
				}
			} );
			return d;
		},
		/**
		 * Remove page from existing collection.
		 * @method
		 * @param {Number} id Identifier of collection
		 * @param {Page} page Page view object
		 * @return {jQuery.Deferred}
		 */
		removePageFromCollection: function ( id, page ) {
			if ( id === 0 ) {
				return this._watchAction( page, true );
			}
			var d = $.Deferred(),
				tempApi = new CollectionsUserPageJSONApi( {
					id: id
				} );

			tempApi.getJSONContent().done( function ( collection ) {
				if ( $.inArray( page.title, collection.items ) > -1 ) {
					// remove it.
					collection.items.splice( collection.items.indexOf( page.title ), 1 );
					tempApi.saveJSONContent( collection )
						.done( $.proxy( d, 'resolve', {} ) )
						.fail( $.proxy( d, 'reject' ) );
				} else {
					d.reject();
				}
			} );
			return d;
		},
		/**
		 * Create a new collection
		 * @method
		 * @param {String} title of collection
		 */
		addCollection: function ( title ) {
			var newCollection,
				self = this,
				d = $.Deferred(),
				indexApi = new CollectionsUserPageJSONApi();

			indexApi.getJSONContent().done( function ( collections ) {
				var newId = 0;
				// If it doesn't exist, initialize with the watchlist
				if ( !collections || !collections.length ) {
					collections = [ $.extend( {}, self.boilerplate, {
						isWatchlist: true
					} ) ];
				}
				// Compute a new id (biggest id on list +1)
				$.each( collections, function () {
					if ( this.id >= newId ) {
						newId = this.id + 1;
					}
				} );
				// Create the new collection meta and add it to the index.
				newCollection = $.extend( {}, self.boilerplate, {
					id: newId,
					title: title,
					owner: user.getName(),
					public: true
				} );
				collections.push( newCollection );
				// Update collections list with new list
				indexApi.saveJSONContent( collections ).done( function () {
					// Once the index is updated, we'll save the collection itself
					var collectionApi = new CollectionsUserPageJSONApi( {
						id: newId
					} );
					// Setup empty collection (items instead of count)
					delete newCollection.count;
					newCollection.items = [];
					collectionApi.saveJSONContent( newCollection )
						.done( $.proxy( d, 'resolve', newCollection ) )
						.fail( $.proxy( d, 'reject' ) );
				} ).fail( $.proxy( d, 'reject' ) );
			} );
			return d;
		},
		/**
		 * Edits a collection
		 * @method
		 * @param {Number} id unique identifier of collection
		 * @param {String} title of collection
		 * @param {String} description of collection
		 */
		editCollection: function ( id, title, description ) {
			var d = $.Deferred(),
				indexApi = new CollectionsUserPageJSONApi();

			indexApi.getJSONContent().done( function ( collections ) {
				var updated = null;
				// Update the index entry
				$.each( collections, function ( i, coll ) {
					if ( this.id === id ) {
						updated = coll;
						this.title = title || coll.title;
						this.description = description || coll.description;
					}
				} );
				// Save the collections list
				if ( updated ) {
					indexApi.saveJSONContent( collections ).done( function () {
						// If watchlist there is no json to save
						if ( id === 0 ) {
							d.resolve( updated );
						} else {
							var collectionApi = new CollectionsUserPageJSONApi( {
								id: id
							} );
							// we also have to update the existing collection
							collectionApi.getJSONContent().done( function ( collection ) {
								if ( collection ) {
									collection.title = title || collection.title;
									collection.description = description || collection.description;
									collectionApi.saveJSONContent( collection )
										.done( $.proxy( d, 'resolve', collection ) )
										.fail( $.proxy( d, 'reject' ) );
								} else {
									d.reject();
								}
							} ).fail( d, 'reject' );
						}
					} ).fail( d, 'reject' );
				} else {
					d.reject();
				}
			} );
			return d;
		}
	} );

	M.define( 'ext.gather.watchstar/CollectionsApi', CollectionsApi );

}( mw.mobileFrontend, jQuery ) );
