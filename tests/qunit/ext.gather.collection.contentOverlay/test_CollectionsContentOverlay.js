// var state = mw.config.get( 'wgGatherCollections' )
// iterate through
// check id against just watched collection id
// update state
// mw.config.set( 'wgGatherCollections', state );
( function ( M, $ ) {
	var CollectionsApi = M.require( 'ext.gather.api/CollectionsApi' ),
		CollectionsContentOverlay = M.require( 'ext.gather.watchstar/CollectionsContentOverlay' );

	QUnit.module( 'Gather: Add to collection overlay', {
		setup: function () {
			var d = $.Deferred().resolve( {
					id: 2
				} ),
				d2 = $.Deferred().resolve();
			this.sandbox.stub( CollectionsApi.prototype, 'addCollection' ).returns( d );
			this.sandbox.stub( CollectionsApi.prototype, 'addPageToCollection' ).returns( d2 );
			this.sandbox.stub( CollectionsContentOverlay.prototype, 'loadEditor' );
			this.watchlist = {
				id: 0,
				title: 'Watchlist',
				titleInCollection: true
			};
			this.collection = {
				id: 1,
				title: 'Foo',
				titleInCollection: false
			};
		}
	} );

	QUnit.test( 'Internal updates to overlay', 2, function ( assert ) {
		var overlay = new CollectionsContentOverlay( {
			collections: [ this.watchlist, this.collection ]
		} );
		overlay.addToCollection( this.collection, M.getCurrentPage() ).done( function () {
			assert.strictEqual( overlay.options.collections[0].titleInCollection, true,
				'Check that the internal state does not get changed by this.' );
			assert.strictEqual( overlay.options.collections[1].titleInCollection, true,
				'Check that the internal state gets changed by this.' );
		} );
	} );

	QUnit.test( 'Internal updates to overlay when new collection', 2, function ( assert ) {
		var overlay = new CollectionsContentOverlay( {
			collections: [ this.watchlist ]
		} );
		assert.strictEqual( overlay.options.collections.length, 1,
			'Check we start with 1 collection.' );
		overlay.addCollection( 'Bar', M.getCurrentPage() ).done( function () {
			assert.strictEqual( overlay.options.collections.length, 2,
				'Check we now have 2 collections.' );
		} );
	} );

}( mw.mobileFrontend, jQuery ) );
