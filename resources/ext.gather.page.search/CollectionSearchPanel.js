( function ( M, $ ) {

	var Panel = M.require( 'Panel' ),
		Icon = M.require( 'Icon' ),
		SearchApi = M.require( 'modules/search/SearchApi' ),
		SEARCH_DELAY = 200,
		CollectionPageList = M.require( 'ext.gather.page.search/CollectionPageList' ),
		CollectionSearchPanel;

	/**
	 * Class for a showing page search results in a panel
	 * @class CollectionPageSearchPanel
	 * @extends Panel
	 * @uses CollectionPageList
	 */
	CollectionSearchPanel = Panel.extend( {
		/** @inheritdoc */
		template: mw.template.get( 'ext.gather.page.search', 'CollectionSearchPanel.hogan' ),
		/** @inheritdoc */
		events: $.extend( {}, Panel.prototype.events, {
			'input .search input': 'onSearchInput',
			'click .empty-results a': 'onSearchLinkClick'
		} ),
		/**
		 * @inheritdoc
		 * @cfg {Array} defaults.pages a list of pages in the collection
		 * @cfg {Object} defaults.collection the collection being worked on
		 */
		className: 'panel visible collection-search-panel',
		defaults: {
			pages: [],
			collection: undefined,
			searchIcon: new Icon( {
				name: 'search',
				// FIXME:
				label: mw.msg( 'search' ),
				additionalClassNames: 'indicator'
			} ).toHtmlString(),
			emptyInputMsg: mw.msg( 'gather-overlay-search-empty' ),
			newSearchMsg: mw.msg( 'gather-overlay-search-new' )
		},
		/** @inheritdoc */
		initialize: function ( options ) {
			var self = this;
			this.query = '';
			// FIXME: In future we'll want to use CollectionApi for this
			this.api = new SearchApi();
			Panel.prototype.initialize.call( this, options );
			this._members = {};
			$.each( this.options.pages, function ( i, page ) {
				self._members[page.title] = true;
			} );
		},
		/** @inheritdoc */
		postRender: function () {
			Panel.prototype.postRender.apply( this );
			this.renderResults();
		},
		/**
		 * Updates the members of the collection associated with the panel
		 * @param {Page} page
		 * @param {Boolean} isRemoved whether page has been removed from this collection
		 */
		updateCollectionMembers: function ( page, isRemoved ) {
			var newPages = [],
				options = this.options;

			if ( isRemoved ) {
				delete this._members[page.title];
			} else {
				this._members[page.title] = true;
			}
			if ( isRemoved ) {
				$.each( options.pages, function ( i, curPage ) {
					if ( curPage.title !== page.title ) {
						newPages.push( curPage );
					}
				} );
				this.options.pages = newPages;
			} else {
				this.options.pages.push( page );
			}
			this._hasChanged = true;
			/**
			 * @event change
			 * @param {Page} page
			 * @param {Boolean} isRemoved
			 * Fired when the pages change from the collection
			 */
			this.emit( 'change', page, isRemoved );
		},
		/**
		 * Updates the rendering of the internal CollectionPageList
		 * @private
		 * @param {Page[]} [pages] Pages to render. If missing will use internal pages.
		 */
		renderResults: function ( pages ) {
			pages = pages || this.options.pages;
			var self = this,
				emptyResultsMsg;
			if ( this.pageList ) {
				this.pageList.options.pages = pages;
				this.pageList.render();
			} else {
				this.pageList = new CollectionPageList( {
					pages: pages,
					collection: this.options.collection,
					el: this.$( '.results' )
				} );
				this.pageList.on( 'member-removed', function ( page ) {
					self.updateCollectionMembers( page, true );
				} );
				this.pageList.on( 'member-added', function ( page ) {
					self.updateCollectionMembers( page );
				} );
			}
			this.pageList.renderPageImages();
			if ( !pages.length ) {
				if ( this.query ) {
					emptyResultsMsg = mw.msg( 'gather-overlay-search-results-empty', this.query );
				} else {
					emptyResultsMsg = '';
				}
				this.$( '.empty-results h2' ).text( emptyResultsMsg );
				this.$( '.empty-results' ).show();
			} else {
				this.$( '.empty-results' ).hide();
			}
		},
		/**
		 * Check whether a member is a known member of the current collection.
		 * @param {String} title
		 * @returns {Boolean}
		 */
		hasMember: function ( title ) {
			return this._members[title] !== undefined;
		},
		/**
		 * Event handler for when try another search link is clicked
		 */
		onSearchLinkClick: function () {
			$( '.search input' ).val( '' ).focus().trigger( 'input' );
			return false;
		},
		/**
		 * Event handler for when search input changes
		 */
		onSearchInput: function () {
			this.search( this.$( 'input' ).val() );
		},
		/**
		 * Trigger search
		 * @param {String} query
		 */
		search: function ( query ) {
			var self = this;

			this.query = query;

			if ( query !== this.lastQuery ) {
				this.api.abort();
				clearTimeout( this.timer );

				if ( query.length ) {
					this.$( '.spinner' ).show();

					this.timer = setTimeout( function () {
						self.api.search( query ).done( function ( data ) {
							var results;

							// check if we're getting the rights response in case of out of
							// order responses (need to get the current value of the input)
							if ( data.query === query ) {
								results = $.map( data.results, function ( page ) {
									page.isMember = self.hasMember( page.title );
									return page;
								} );
								self.$( '.spinner' ).hide();
								self.renderResults( results );
							}
						} );
					}, SEARCH_DELAY );
				} else {
					// re-render the members of the collection
					this.renderResults();
				}

				// keep track of last query to take into account backspace usage
				this.lastQuery = query;
			}
		},
		/**
		 * Check if collection has changed.
		 * @return {Boolean}
		 */
		hasChanges: function () {
			return this._hasChanged;
		},
		/**
		 * Save any changes made to the collection.
		 * @return {jQuery.Deferred}
		 */
		saveChanges: function () {
			this._hasChanged = false;
			return this.pageList.saveChanges();
		},
		/**
		 * Add a new item to the collection items and reflect the change on the ui
		 * @param {Object} page The page to add.
		 * @param {Boolean} removed If it is a removal or an addition.
		 */
		toggleNewMember: function ( page, removed ) {
			this.pageList.toggleMember( page.title, removed );
			this.updateCollectionMembers( page, removed );
			this.renderResults();
		}
	} );

	M.define( 'ext.gather.page.search/CollectionSearchPanel', CollectionSearchPanel );

}( mw.mobileFrontend, jQuery ) );
