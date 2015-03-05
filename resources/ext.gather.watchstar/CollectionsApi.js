/*jshint unused:vars */
( function ( M, $ ) {

	var WatchstarApi = M.require( 'modules/watchstar/WatchstarApi' ),
		CollectionsApi;

	/**
	 * API for managing collection items
	 *
	 * @class CollectionApi
	 * @extends Api
	 */
	CollectionsApi = WatchstarApi.extend( {
		/**
		 * Add page to existing collection.
		 * FIXME: This is currently smoke and mirrors. Doesn't save to server.
		 * @method
		 * @param {Number} id Identifier of collection
		 * @param {Page} page Page view object
		 * @return {jQuery.Deferred}
		 */
		addPageToCollection: function ( id, page ) {
			var d = $.Deferred();
			return d.resolve( {} );
		},
		/**
		 * Remove page from existing collection.
		 * FIXME: This is currently smoke and mirrors. Doesn't save to server.
		 * @method
		 * @param {Number} id Identifier of collection
		 * @param {Page} page Page view object
		 * @return {jQuery.Deferred}
		 */
		removePageFromCollection: function ( id, page ) {
			var d = $.Deferred();
			return d.resolve( {} );
		},
		/**
		 * Create a new collection
		 * FIXME: This is currently smoke and mirrors. Doesn't save to server.
		 * @method
		 * @param {String} title of collection
		 */
		addCollection: function ( title ) {
			var d = $.Deferred();
			return d.resolve( {
				id: 99,
				title: title
			} );
		}
	} );

	M.define( 'ext.gather.watchstar/CollectionsApi', CollectionsApi );

}( mw.mobileFrontend, jQuery ) );
