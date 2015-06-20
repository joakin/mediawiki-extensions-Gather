( function ( M, $ ) {
	var PageActionOverlay = M.require( 'modules/tutorials/PageActionOverlay' ),
		SearchTutorialOverlay;

	SearchTutorialOverlay = PageActionOverlay.extend( {
		template: mw.template.get( 'ext.gather.collection.editor', 'SearchTutorialOverlay.hogan' ),
		className: 'overlay content-overlay search-tutorial-overlay slide active editing',
		events: $.extend( {}, PageActionOverlay.prototype.events, {
			'click .dismiss': 'onDismissClick'
		} ),
		appendToElement: '.collection-editor-overlay',
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
