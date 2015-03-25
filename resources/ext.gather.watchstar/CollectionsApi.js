/*jshint unused:vars */
( function ( M, $ ) {

	var WatchstarApi = M.require( 'modules/watchstar/WatchstarApi' ),
		user = M.require( 'user' ),
		CollectionsApi;

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
		removeCollection: function( id ) {
			return this.postWithToken( 'watch', {
				action: 'editlist',
				mode: 'deletelist',
				id: id
			} );
		},
		/**
		 * Gets an object representing all the current users collections
		 * @method
		 * @param {String} owner of the collections
		 * @param {Page} page the current page.
		 */
		getCurrentUsersCollections: function ( owner, page ) {
			return this.get( {
				action: 'query',
				list: 'lists',
				lsttitle: page.getTitle(),
				lstprop: 'label|description|public|image|count',
				lstowner: owner
			} ).then( function ( resp ) {
				if ( resp.query && resp.query.lists ) {
					return $.map( resp.query.lists, function ( list ) {
						// FIXME: API should handle all these inconsistencies.
						list.isWatchlist = list.id === 0;
						list.titleInCollection = list.title;
						list.title = list.label;
						list.owner = owner;
						delete list.label;
						return list;
					} );
				} else {
					return [];
				}
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
		setPrivacy: function ( id, isPrivate ) {
			return this.postWithToken( 'watch', {
				action: 'editlist',
				id: id,
				perm: isPrivate ? 'private' : 'public'
			} );
		},
		/**
		 * Hide list (moderation purposes)
		 * @method
		 * @param {Number} id unique identifier of collection
		 * @return {jQuery.Deferred}
		 */
		hideCollection: function ( id ) {
			return this.postWithToken( 'watch', {
				action: 'editlist',
				id: id,
				mode: 'hidelist'
			} );
		},
		/**
		 * Show list (moderation purposes)
		 * @method
		 * @param {Number} id unique identifier of collection
		 * @return {jQuery.Deferred}
		 */
		showCollection: function ( id ) {
			return this.postWithToken( 'watch', {
				action: 'editlist',
				id: id,
				mode: 'showlist'
			} );
		}
	} );

	M.define( 'ext.gather.watchstar/CollectionsApi', CollectionsApi );

}( mw.mobileFrontend, jQuery ) );
