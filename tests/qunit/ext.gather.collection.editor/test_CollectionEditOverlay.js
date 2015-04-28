( function ( M ) {
	var CollectionEditOverlay = M.require( 'ext.gather.collection.edit/CollectionEditOverlay' ),
		collection,
		overlay;

	QUnit.module( 'Gather', {
		setup: function () {
			collection = {
				id: 1,
				title: 'Cool title',
				description: 'Hey, I\'m a collection description.'
			};
			overlay = new CollectionEditOverlay( {
				collection: collection
			} );
			this.validTitle = getStringWithLength( overlay.titleMaxLength );
			this.invalidTitle = getStringWithLength( overlay.titleMaxLength + 1 );
			this.validDescription = getStringWithLength( overlay.descriptionMaxLength );
			this.invalidDescription = getStringWithLength( overlay.descriptionMaxLength + 1 );

		}
	} );

	function getStringWithLength( len ) {
		return Array( len + 1 ).join( 'a' );
	}

	QUnit.test( 'Collection title validation', 2, function ( assert ) {
		assert.strictEqual( overlay.isTitleValid( this.validTitle ), true,
			'Check that a valid title is correctly evaluated' );
		assert.strictEqual( overlay.isTitleValid( this.invalidTitle ), false,
			'Check that an invalid title is correctly evaluated' );
	} );

	QUnit.test( 'Collection description validation', 2, function ( assert ) {
		assert.strictEqual( overlay.isDescriptionValid( this.validDescription ), true,
			'Check that a valid description is correctly evaluated' );
		assert.strictEqual( overlay.isDescriptionValid( this.invalidDescription ), false,
			'Check that an invalid description is correctly evaluated' );
	} );

}( mw.mobileFrontend ) );
