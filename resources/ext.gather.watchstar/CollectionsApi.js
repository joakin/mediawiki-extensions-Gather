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
				remove: true,
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
