( function ( M ) {
	var CollectionEditOverlay = M.require( 'ext.gather.edit/CollectionEditOverlay' ),
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
			this.validTitle = randomString( overlay.titleMaxLength );
			this.invalidTitle = randomString( overlay.titleMaxLength + 1 );
			this.validDescription = randomString( overlay.descriptionMaxLength );
			this.invalidDescription = randomString( overlay.descriptionMaxLength + 1 );

		}
	} );

	/**
	 * Use base 36 method to generate a random string with specified length
	 * @param {Number} length length of desired string
	 * @returns {String} randomly generated string
	 */
	function randomString( length ) {
		return Math.round(
			( Math.pow( 36, length + 1 ) - Math.random() * Math.pow( 36, length ) )
		).toString( 36 ).slice( 1 );
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
