// jscs:disable requireCamelCaseOrUpperCaseIdentifiers
( function ( M, $ ) {

	var CollectionsWatchstar = M.require( 'ext.gather.watchstar/CollectionsWatchstar' ),
		util = M.require( 'util' ),
		user = M.require( 'user' );

	/**
	 * Toggle the watch status of a known page
	 * @method
	 * @param {Page} page
	 * @ignore
	 */
	function init( page ) {
		var $container = $( '#ca-watch' );
		if ( !page.inNamespace( 'special' ) ) {
			new CollectionsWatchstar( {
				el: $container,
				collections: [
					{
						id: 0,
						title: mw.msg( 'gather-watchlist-title' ),
						titleInCollection: page.isWatched()
					}
				],
				page: page,
				isAnon: user.isAnon(),
				isNewlyAuthenticatedUser: util.query.article_action === 'add_to_collection'
			} );
		}
	}
	init( M.getCurrentPage() );

}( mw.mobileFrontend, jQuery ) );
