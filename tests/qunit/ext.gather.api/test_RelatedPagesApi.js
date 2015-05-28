( function ( M, $ ) {
	var Api = M.require( 'api' ).Api,
		RelatedPagesApi = M.require( 'ext.gather.api/RelatedPagesApi' ),
		relatedPages = {
			query: {
				pages: {
					123: {
						id: 123,
						title: 'Oh noes',
						ns: 0,
						thumbnail: {
							source: 'http://placehold.it/200x100'
						}
					}
				}
			}
		},
		emptyRelatedPages = {
			query: {
				pages: {
				}
			}
		};

	QUnit.module( 'Gather - Related pages api', {
		/** @inherit */
		setup: function () {
		}
	} );

	QUnit.test( 'Returns an array with the results when api responds', 2, function ( assert ) {
		this.sandbox.stub( Api.prototype, 'get' ).returns( $.Deferred().resolve( relatedPages ) );
		var api = new RelatedPagesApi();
		api.getRelatedPages( 'Oh' ).then( function ( results ) {
			assert.ok( $.isArray( results ), 'Results must be an array' );
			assert.strictEqual( results[0].title, 'Oh noes' );
		} );
	} );

	QUnit.test( 'Empty related pages is handled fine.', 2, function ( assert ) {
		this.sandbox.stub( Api.prototype, 'get' ).returns( $.Deferred().resolve( emptyRelatedPages ) );
		var api = new RelatedPagesApi();
		api.getRelatedPages( 'Oh' ).then( function ( results ) {
			assert.ok( $.isArray( results ), 'Results must be an array' );
			assert.strictEqual( results.length, 0 );
		} );
	} );

}( mw.mobileFrontend, jQuery ) );
