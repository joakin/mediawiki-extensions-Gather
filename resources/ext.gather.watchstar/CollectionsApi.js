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
				var data = resp ? JSON.parse( resp ) : resp;
				d.resolve( data );
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
			var self = this;
			return this.postWithToken( 'watch', {
				action: 'editlist',
				label: title
			} ).then( function ( data ) {
				data = data.editlist;
				return $.extend( {}, self.boilerplate, {
					id: data.id,
					title: title,
					// FIXME: this value should come from UI
					owner: user.getName(),
					items: data.pages,
					// FIXME: this value should come from UI
					public: true
				} );
			} );
		},
		/**
		 * Edits a collection
		 * @method
		 * @param {Number} id unique identifier of collection
		 * @param {String} title of collection
		 * @param {String} description of collection
		 */
		editCollection: function ( id, title, description ) {
			return this.postWithToken( 'watch', {
				action: 'editlist',
				id: id,
				label: title,
				description: description
			} );
		}
	} );

	M.define( 'ext.gather.watchstar/CollectionsApi', CollectionsApi );

}( mw.mobileFrontend, jQuery ) );
