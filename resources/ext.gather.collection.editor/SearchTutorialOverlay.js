( function ( M, $ ) {
	var PointerOverlay = M.require( 'mobile.contentOverlays/PointerOverlay' ),
		SearchTutorialOverlay;

	SearchTutorialOverlay = PointerOverlay.extend( {
		template: mw.template.get( 'ext.gather.collection.editor', 'SearchTutorialOverlay.hogan' ),
		className: 'overlay pointer-overlay search-tutorial-overlay',
		events: $.extend( {}, PointerOverlay.prototype.events, {
			'click .dismiss': 'onDismissClick'
		} ),
		defaults: {
			tutorialHeading: mw.msg( 'gather-overlay-search-tutorial-heading' ),
			tutorialText: mw.msg( 'gather-overlay-search-tutorial-text' ),
			dismissButtonLabel: mw.msg( 'gather-tutorial-dismiss-button-label' )
		},
		/**
		 * Event handler for dismissing the overlay
		 */
		onDismissClick: function () {
			this.hide();
		}
	} );

	M.define( 'ext.gather.collection.edit/SearchTutorialOverlay', SearchTutorialOverlay );
}( mw.mobileFrontend, jQuery ) );
