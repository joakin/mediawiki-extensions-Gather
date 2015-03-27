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
			this.validTitle = getStringWithLength( overlay.titleMaxLength );
			this.invalidTitle = getStringWithLength( overlay.titleMaxLength + 1 );
			this.validDescription = getStringWithLength( overlay.descriptionMaxLength );
			this.invalidDescription = getStringWithLength( overlay.descriptionMaxLength + 1 );

		}
	} );

	/**
	 * Generate string of a certain length
	 * @param {Number} length length of desired string
	 * @returns {String} randomly generated string
	 */
	function getStringWithLength( len ) {
		var i, str = '';
		for ( i = 0; i < len; i++ ) {
			str += 'a';
		}
		return str;
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
