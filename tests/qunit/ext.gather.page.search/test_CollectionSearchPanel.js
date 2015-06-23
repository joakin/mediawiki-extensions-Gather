( function ( M ) {
	var Page = M.require( 'Page' ),
		Icon = M.require( 'Icon' ),
		CollectionSearchPanel = M.require( 'ext.gather.page.search/CollectionSearchPanel' );

	QUnit.module( 'Gather: CollectionSearchPanel', {
		setup: function () {
			this.pages = [
				new Page( {
					displayTitle: 'Foo',
					title: 'Foo'
				} ),
				new Page( {
					displayTitle: 'Bar',
					title: 'Bar'
				} )
			];
			this.glyphClass = new Icon( {
				name: 'tick'
			} ).getGlyphClassName();
			this.untickedGlyphClass = new Icon( {
				name: 'tick-disabled'
			} ).getGlyphClassName();
		}
	} );

	QUnit.test( 'Check icons render as expected.', 2, function ( assert ) {
		var panel = new CollectionSearchPanel( {
			collection: {
				id: 100
			},
			pages: this.pages
		} );
		assert.strictEqual( panel.$( '.results h3' ).length, 2, 'Two titles rendered' );
		assert.strictEqual( panel.$( '.results .' + this.glyphClass ).length, 2, 'Two in collection icons rendered' );
	} );

	QUnit.test( 'Check icons in search results render as expected.', 4, function ( assert ) {
		var panel = new CollectionSearchPanel( {
			collection: {
				id: 100
			},
			pages: this.pages
		} );
		panel.renderResults( [
			new Page( {
				displayTitle: 'Bar',
				title: 'Bar'
			} ),
			new Page( {
				displayTitle: 'WZ',
				title: 'WZ'
			} ),
			new Page( {
				displayTitle: 'ZZZz',
				title: 'ZZZz'
			} )
		] );
		assert.strictEqual( panel.$( '.results h3' ).length, 3, 'Three titles rendered' );
		assert.strictEqual( panel.$( '.results .' + this.untickedGlyphClass ).length, 2, 'Unticked icons rendered' );
		assert.strictEqual( panel.$( '.results .' + this.glyphClass ).length, 1, 'Ticked icon rendered for Foo.' );
		assert.strictEqual( panel.$( '.results .' + this.glyphClass ).siblings( 'h3' ).text(),
			'Bar', 'Check the ticked item is Bar.' );
	} );

}( mw.mobileFrontend ) );
