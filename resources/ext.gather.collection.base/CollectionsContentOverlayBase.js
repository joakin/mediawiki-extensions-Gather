( function ( M ) {

	var CollectionsContentOverlayBase,
		ContentOverlay = M.require( 'ContentOverlay' );

	/**
	 * A clickable watchstar for managing collections
	 * @class CollectionsContentOverlayBase
	 * @extends ContentOverlay
	 */
	CollectionsContentOverlayBase = ContentOverlay.extend( {
		/**
		 * FIXME: re-evaluate content overlay default classes/css.
		 * @inheritdoc
		 */
		appendToElement: 'body',
		/** @inheritdoc */
		hasFixedHeader: false,
		/** @inheritdoc */
		postRender: function () {
			this.hideSpinner();
		},
		/**
		 * Reveal all interface elements and cancel the spinner.
		 */
		hideSpinner: function () {
			this.$( '.overlay-content' ).children().show();
			this.$( '.spinner' ).hide();
			// For position absolute to work the parent must have a specified height
			this.$el.parent().css( 'height', '100%' );
		},
		/**
		 * Hide all interface elements and show spinner.
		 */
		showSpinner: function () {
			this.$( '.overlay-content' ).children().hide();
			this.$( '.spinner' ).show();
		}
	} );
	M.define( 'ext.gather.collection.base/CollectionsContentOverlayBase', CollectionsContentOverlayBase );

}( mw.mobileFrontend ) );
