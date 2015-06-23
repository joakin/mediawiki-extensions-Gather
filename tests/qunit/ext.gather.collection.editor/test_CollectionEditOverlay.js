( function ( M, $ ) {
	var CollectionEditOverlay = M.require( 'ext.gather.collection.edit/CollectionEditOverlay' ),
		CollectionsApi = M.require( 'ext.gather.api/CollectionsApi' );

	QUnit.module( 'Gather: CollectionEditOverlay', {
		setup: function () {
			var collection,
				maxLengthDesc = CollectionEditOverlay.prototype.descriptionMaxLength,
				maxLength = CollectionEditOverlay.prototype.titleMaxLength;

			collection = this.collection = {
				id: 1,
				title: 'Cool title',
				description: 'Hey, I\'m a collection description.'
			};
			this.sandbox.stub( CollectionsApi.prototype, 'getCollectionMembers' )
				.returns(
					$.Deferred().resolve( {} )
				);
			this.sandbox.stub( CollectionsApi.prototype, 'getCollection' )
				.returns(
					$.Deferred().resolve( {
						query: {
							lists: [
								{
									watchlist: '',
									id: collection.id,
									description: collection.description,
									label: collection.title
								}
							]
						}
					} )
				);
			this.validTitle = getStringWithLength( maxLength );
			this.invalidTitle = getStringWithLength( maxLength + 1 );
			this.validDescription = getStringWithLength( maxLengthDesc );
			this.invalidDescription = getStringWithLength( maxLengthDesc + 1 );

		}
	} );

	function getStringWithLength( len ) {
		return Array( len + 1 ).join( 'a' );
	}

	QUnit.test( 'Collection title validation', 2, function ( assert ) {
		var overlay = new CollectionEditOverlay( {
			collection: this.collection
		} );
		assert.strictEqual( overlay.isTitleValid( this.validTitle ), true,
			'Check that a valid title is correctly evaluated' );
		assert.strictEqual( overlay.isTitleValid( this.invalidTitle ), false,
			'Check that an invalid title is correctly evaluated' );
	} );

	QUnit.test( 'Collection description validation', 2, function ( assert ) {
		var overlay = new CollectionEditOverlay( {
			collection: this.collection
		} );
		assert.strictEqual( overlay.isDescriptionValid( this.validDescription ), true,
			'Check that a valid description is correctly evaluated' );
		assert.strictEqual( overlay.isDescriptionValid( this.invalidDescription ), false,
			'Check that an invalid description is correctly evaluated' );
	} );

	QUnit.test( 'New collection', 2, function ( assert ) {
		var overlay = new CollectionEditOverlay( {} );
		assert.ok( overlay.options.collection, 'Check an empty collection is created...' );
		assert.strictEqual( overlay.$( '.collection-privacy' ).hasClass( 'private' ), false,
			'... and public by default.' );
	} );

}( mw.mobileFrontend, jQuery ) );
