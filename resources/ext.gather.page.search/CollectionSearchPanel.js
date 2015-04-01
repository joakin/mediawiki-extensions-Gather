( function ( M ) {

	var Panel = M.require( 'Panel' ),
		CollectionPageList = M.require( 'ext.gather.page.search/CollectionPageList' ),
		CollectionSearchPanel;

	/**
	 * Class for a showing page search results in a panel
	 * @class CollectionPageSearchPanel
	 * @extends Panel
	 */
	CollectionSearchPanel = Panel.extend( {
		template: mw.template.get( 'ext.gather.page.search', 'CollectionSearchPanel.hogan' ),
		/**
		 * @inheritdoc
		 * @cfg {Array} defaults.pages a list of pages in the collection
		 * @cfg {Object} defaults.collection the collection being worked on
		 */
		className: 'panel visible collection-search-panel',
		defaults: {
			pages: [],
			collection: undefined
		},
		postRender: function( options ) {
			Panel.prototype.postRender.apply( this, arguments );
			this._renderResults( options.pages );
		},
		_renderResults: function ( pages ) {
			var collectionPageList = new CollectionPageList( {
				pages: pages,
				collection: this.options.collection,
				el: this.$( '.results' )
			} );
			collectionPageList.renderPageImages();
		}
	} );

	M.define( 'ext.gather.page.search/CollectionSearchPanel', CollectionSearchPanel );

}( mw.mobileFrontend ) );
