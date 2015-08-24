const CollectionEditOverlay = mw.mobileFrontend.require( 'ext.gather.collection.editor/CollectionEditOverlay' ),
	Button = mw.mobileFrontend.require( 'Button' ),
	Icon = mw.mobileFrontend.require( 'Icon' );

/**
	* A button used to create a collection
	* @class CreateCollectionButton
	* @extends Button
	*/
export default Button.extend( {
	/** @inheritdoc */
	defaults: {
		tagName: 'div',
		progressive: true,
		label: mw.msg( 'gather-create-collection-button-label' ),
		additionalClassNames: new Icon( {
			tagName: 'span',
			name: 'collections-icon',
			hasText: true
		} ).getClassName()
	},
	events: {
		click: 'onCreateCollectionButtonClick'
	},
	/**
		* Click handler for create collection button
		* @param {Object} ev Event Object
		*/
	onCreateCollectionButtonClick ( ev ) {
		ev.stopPropagation();
		ev.preventDefault();
		let editOverlay = new CollectionEditOverlay( {
			reloadOnSave: true
		} );
		editOverlay.show();
	}
} );
