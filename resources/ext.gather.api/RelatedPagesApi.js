( function ( M, $ ) {

	var Api = M.require( 'api' ).Api,
		RelatedPagesApi;

	/**
	 * API for managing collection items
	 *
	 * @class RelatedPagesApi
	 * @extends Api
	 */
	RelatedPagesApi = Api.extend( {
		/**
		 * @method
		 * @param {String} title Title of the page to find related pages of.
		 * @param {Number} [limit] How many related pages to return. Defaults to 3.
		 * @returns {jQuery.Deferred}
		 */
		getRelatedPages: function ( title, limit ) {
			limit = limit || 3;

			return this.get( {
				action: 'query',
				prop: 'pageimages',
				piprop: 'thumbnail',
				pilimit: limit,
				pithumbsize: mw.config.get( 'wgMFThumbnailSizes' ).tiny,
				generator: 'search',
				gsrsearch: 'morelike:' + title,
				gsrnamespace: '0',
				gsrlimit: limit
			} ).then( cleanApiResults );
		}
	} );

	/**
	 * Clean api results by extracting query.pages into an array
	 * @param {Object} results Results from the API to clean up
	 */
	function cleanApiResults( results ) {
		if ( results && results.query && results.query.pages ) {
			return $.map( results.query.pages, function ( p ) {
				return p;
			} );
		} else {
			return null;
		}
	}

	M.define( 'ext.gather.api/RelatedPagesApi', RelatedPagesApi );

}( mw.mobileFrontend, jQuery ) );
