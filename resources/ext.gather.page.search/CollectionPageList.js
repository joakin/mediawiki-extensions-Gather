( function ( M, $ ) {

	var PageList = M.require( 'modules/PageList' ),
		Page = M.require( 'Page' ),
		CollectionsApi = M.require( 'ext.gather.watchstar/CollectionsApi' ),
		View = M.require( 'View' ),
		Icon = M.require( 'Icon' ),
		toast = M.require( 'toast' ),
		CollectionPageList;

	/**
	 * List of items page view
	 * @class PageList
	 * @uses Page
	 * @uses WatchstarApi
	 * @uses Watchstar
	 * @extends View
	 */
	CollectionPageList = PageList.extend( {
		/**
		 * @inheritdoc
		 */
		defaults: {
			pages: undefined,
			collection: undefined,
			iconButton: new Icon( {
				name: 'tick',
				additionalClassNames: 'status',
				label: mw.msg( 'gather-collection-member' )
			} ).toHtmlString(),
			iconDisabledButton: new Icon( {
				name: 'tick-disabled',
				additionalClassNames: 'status',
				label: mw.msg( 'gather-collection-non-member' )
			} ).toHtmlString()
		},
		/** @inheritdoc */
		events: $.extend( {}, PageList.prototype.events, {
			'click li': 'onChangeMemberStatus'
		} ),
		templatePartials: {
			item: mw.template.get( 'ext.gather.page.search', 'item.hogan' )
		},
		initialize: function () {
			// FIXME: PageList in MobileFrontend should be rewritten as PageListWatchstar.
			View.prototype.initialize.apply( this, arguments );
			this.api = new CollectionsApi();
		},
		/**
		 * @inheritdoc
		 * Loads watch stars for each page.
		 */
		postRender: function () {
			// FIXME: PageList in MobileFrontend should be rewritten as PageListWatchstar.
			View.prototype.postRender.apply( this, arguments );
		},
		onChangeMemberStatus: function ( ev ) {
			var $target = $( ev.currentTarget ),
				collection = this.options.collection,
				self = this,
				title = $target.data( 'title' ),
				inCollection = $target.data( 'is-member' ),
				page = new Page( { title: title } );

			if ( inCollection ) {
				this.api.removePageFromCollection( collection.id, page ).done( function () {
					$target.find( '.status' ).replaceWith( self.options.iconDisabledButton );
					$target.data( 'is-member', false );
					toast.show( mw.msg( 'gather-remove-toast', collection.title ), 'toast' );
				} );
			} else {
				this.api.addPageToCollection( collection.id, page ).done( function () {
					$target.find( '.status' ).replaceWith( self.options.iconButton );
					$target.data( 'is-member', true );
					toast.show( mw.msg( 'gather-add-toast', collection.title ), 'toast' );
				} );
			}
			return false;
		},
	} );

	M.define( 'ext.gather.page.search/CollectionPageList', CollectionPageList );

}( mw.mobileFrontend, jQuery ) );
