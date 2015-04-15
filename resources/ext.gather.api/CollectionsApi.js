/*jshint unused:vars */
( function ( M, $ ) {

	var Api = M.require( 'api' ).Api,
		user = M.require( 'user' ),
		CollectionsApi;

	/**
	 * API for managing collection items
	 *
	 * @class CollectionApi
	 * @extends Api
	 */
	CollectionsApi = Api.extend( {
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
		 * Add page to existing collection.
		 * @method
		 * @param {Number} id Identifier of collection
		 * @param {Page} page Page view object
		 * @return {jQuery.Deferred}
		 */
		addPageToCollection: function ( id, page ) {
			return this.postWithToken( 'watch', {
				action: 'editlist',
				id: id,
				titles: [ page.getTitle() ]
			} );
		},
		/**
		 * Remove page from existing collection.
		 * @method
		 * @param {Number} id Identifier of collection
		 * @param {Page} page Page view object
		 * @return {jQuery.Deferred}
		 */
		removePageFromCollection: function ( id, page ) {
			return this.postWithToken( 'watch', {
				action: 'editlist',
				id: id,
				mode: 'remove',
				titles: [ page.getTitle() ]
			} );
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
				perm: 'public',
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
		 * Removes a collection
		 * @method
		 * @param {Number} id unique identifier of collection
		 */
		removeCollection: function ( id ) {
			return this.postWithToken( 'watch', {
				action: 'editlist',
				mode: 'deletelist',
				id: id
			} );
		},
		/**
		 * Obtain all the members of a given collection
		 * @method
		 * @param {Number} id unique identifier of collection
		 */
		getCollectionMembers: function ( id ) {
			var params = {
				action: 'query',
				prop: 'pageimages',
				piprop: 'thumbnail',
				pithumbsize: 120,
				pilimit: 50,
				generator: 'listpages',
				glspid: id,
				glsplimit: 50
			};

			return this.get( params ).then( function ( resp ) {
				// Workaround for https://phabricator.wikimedia.org/T95741
				if ( !resp.query ) {
					return [];
				}
				return $.map( resp.query.pages, function ( page ) {
					page.heading = page.title;
					page.isMember = true;
					return page;
				} );
			} );
		},
		/**
		 * Gets an object representing all the current users collections
		 * @method
		 * @param {String} owner of the collections
		 * @param {Page} page the current page.
		 * @param {Object} [queryArgs] parameters to send to api
		 */
		getCurrentUsersCollections: function ( owner, page, queryArgs ) {
			var args = $.extend( {}, queryArgs || {}, {
				action: 'query',
				list: 'lists',
				lstlimit: 50,
				lsttitle: page.getTitle(),
				lstprop: 'label|description|public|image|count',
				lstowner: owner
			} );
			return this.get( args ).then( function ( resp ) {
				var result = {};
				if ( resp['query-continue'] ) {
					result.continueArgs = resp['query-continue'].lists;
				}
				if ( resp.query && resp.query.lists ) {
					result.collections = $.map( resp.query.lists, function ( list ) {
						// FIXME: API should handle all these inconsistencies.
						list.isWatchlist = list.id === 0;
						list.titleInCollection = list.title;
						list.title = list.label;
						list.owner = owner;
						delete list.label;
						return list;
					} );
				} else {
					result.collections = [];
				}
				return result;
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
		},
		/**
		 * Set collection privacy
		 * @method
		 * @param {Number} id unique identifier of collection
		 * @param {Boolean} isPrivate private or not
		 * @return {jQuery.Deferred}
		 */
		setPrivate: function ( id, isPrivate ) {
			return this.postWithToken( 'watch', {
				action: 'editlist',
				id: id,
				perm: isPrivate ? 'private' : 'public'
			} );
		},
		/**
		 * Show or hide a list (for moderation purposes)
		 * @method
		 * @param {Number} id unique identifier of collection
		 * @param {Boolean} isVisible
		 * @return {jQuery.Deferred}
		 */
		setVisible: function ( id, isVisible ) {
			return this.postWithToken( 'watch', {
				action: 'editlist',
				id: id,
				mode: isVisible ? 'showlist' : 'hidelist'
			} );
		}
	} );

	M.define( 'ext.gather.watchstar/CollectionsApi', CollectionsApi );

}( mw.mobileFrontend, jQuery ) );
