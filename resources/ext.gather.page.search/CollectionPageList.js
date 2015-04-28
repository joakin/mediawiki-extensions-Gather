( function ( M, $ ) {

	var PageList = M.require( 'modules/PageList' ),
		Page = M.require( 'Page' ),
		CollectionsApi = M.require( 'ext.gather.watchstar/CollectionsApi' ),
		View = M.require( 'View' ),
		Icon = M.require( 'Icon' ),
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
		/** @inheritdoc */
		templatePartials: {
			item: mw.template.get( 'ext.gather.page.search', 'item.hogan' )
		},
		/** @inheritdoc */
		initialize: function () {
			this._removals = [];
			this._additions = [];
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
		},
		/**
		 * Event handler for when a member changes status in the collection
		 * @param {jQuery.Event} ev
		 */
		onChangeMemberStatus: function ( ev ) {
			var index,
				$target = $( ev.currentTarget ),
				$listThumb = $target.find( '.list-thumb' ),
				self = this,
				title = $target.data( 'title' ),
				inCollection = $target.data( 'is-member' ),
				page = new Page( {
					title: title
				} );

			// FIXME: So hacky. Move/use methods on Page
			page.heading =  title;
			page.pageimageClass = $listThumb.attr( 'class' );
			page.listThumbStyleAttribute = $listThumb.attr( 'style' );

			if ( inCollection ) {
				this._removals.push( title );
				index = this._additions.indexOf( title );
				if ( index > -1 ) {
					this._additions.splice( index, 1 );
				}
				$target.find( '.status' ).replaceWith( self.options.iconDisabledButton );
				$target.data( 'is-member', false );
				/**
				 * @event member-removed
				 * @param {Page} page
				 * Fired when member is removed from collection
				 */
				self.emit( 'member-removed', page );
			} else {
				this._additions.push( title );
				index = this._removals.indexOf( title );
				if ( index > -1 ) {
					this._removals.splice( index, 1 );
				}
				$target.find( '.status' ).replaceWith( self.options.iconButton );
				$target.data( 'is-member', true );
				page.isMember = true;
				/**
				 * @event member-added
				 * @param {Page} page
				 * Fired when member is removed from collection
				 */
				self.emit( 'member-added', page );
			}
			return false;
		},
		/**
		 * Save any changes made to the collection.
		 */
		saveChanges: function () {
			var self = this,
				d = $.Deferred(),
				additions = this._additions,
				removals = self._removals,
				collection = this.options.collection,
				calls = [];

			if ( additions.length || removals.length ) {
				if ( additions.length ) {
					calls.push( this.api.addPagesToCollection( collection.id, additions ) );
				}
				if ( removals.length ) {
					calls.push( this.api.removePagesFromCollection( collection.id, removals ) );
				}
				return $.when.apply( $, calls );
			} else {
				return d.resolve();
			}
		}
	} );

	M.define( 'ext.gather.page.search/CollectionPageList', CollectionPageList );

}( mw.mobileFrontend, jQuery ) );
