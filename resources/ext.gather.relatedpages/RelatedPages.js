( function ( M, $ ) {

	var RelatedPagesApi = M.require( 'ext.gather.api/RelatedPagesApi' ),
		View = M.require( 'View' ),
		Page = M.require( 'Page' ),
		CollectionPageList = M.require( 'ext.gather.page.search/CollectionPageList' ),
		icons = M.require( 'icons' ),
		RelatedPages;

	/**
	 * @class RelatedPages
	 * @extends View
	 * @uses CollectionPageList
	 */
	RelatedPages = View.extend( {
		className: 'related-pages',
		defaults: {
			spinner: icons.spinner().toHtmlString(),
			heading: mw.msg( 'gather-edit-collection-related-pages' )
		},
		template: mw.template.get( 'ext.gather.relatedpages', 'relatedpages.hogan' ),
		/** @inheritdoc */
		initialize: function () {
			this.api = new RelatedPagesApi();
			this.relatedPages = [];
			this.pageList = null;
			this._loading = true;
			View.prototype.initialize.apply( this, arguments );
			this.search().then( $.proxy( this, 'postRender' ) );
		},
		/** @inheritdoc */
		postRender: function () {
			var self = this,
				pages = this.relatedPages;
			if ( !this.pageList ) {
				this.pageList = new CollectionPageList( {
					pages: pages,
					collection: this.options.collection,
					el: this.$( '.results' )
				} );
				this.pageList.on( 'member-removed', function ( page ) {
					self.change( page, true );
				} );
				this.pageList.on( 'member-added', function ( page ) {
					self.change( page );
				} );
			} else {
				this.pageList.options.pages = pages;
				this.pageList.render();
			}
			if ( !this._loading && this.relatedPages.length === 0 ) {
				this.$el.detach();
			} else {
				this.$el.removeClass( 'hidden' );
			}
		},
		/**
		 * Search for related pages
		 * @returns {$.Deferred} Resolves when results have updated
		 */
		search: function () {
			var self = this,
				title = this.options.title;
			if ( title ) {
				this.loading( true );
				return this.api.getRelatedPages( title ).always( function () {
					self.loading( false );
				} ).then( function ( relatedPages ) {
					if ( relatedPages ) {
						self.relatedPages = $.map( relatedPages, function ( pageOptions ) {
							pageOptions.isMember = false;
							return new Page( pageOptions );
						} );
					}
				} );
			} else {
				return $.Deferred().reject( new Error( 'Invalid title' ) );
			}
		},
		/**
		 * Respond to change on the list of members
		 * @param {Page} page
		 * @param {Boolean} isRemoved
		 */
		change: function ( page, isRemoved ) {
			var index = -1;

			/**
			 * @event change
			 * @param {Page} page
			 * @param {Boolean} isRemoved
			 * Fired when the pages change from the collection
			 */
			this.emit( 'change', page, isRemoved );

			if ( !isRemoved ) {
				// When an item is checked, remove it from the related results
				$.each( this.relatedPages, function ( i, p ) {
					if ( p.title === page.title ) {
						index = i;
					}
				} );
				if ( index > -1 ) {
					this.relatedPages.splice( index, 1 );
				}
				this.postRender();
			}
		},
		/**
		 * Set loading state. When loading, set spinner, when not, clear up
		 * @param {Boolean} isLoading
		 * internal one
		 */
		loading: function ( isLoading ) {
			this._loading = isLoading;
			if ( isLoading ) {
				this.$( '.spinner' ).removeClass( 'hidden' );
			} else {
				this.$( '.spinner' ).addClass( 'hidden' );
			}
		}
	} );

	M.define( 'ext.gather.relatedpages/RelatedPages', RelatedPages );

}( mw.mobileFrontend, jQuery ) );
