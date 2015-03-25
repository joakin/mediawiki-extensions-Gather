( function ( M, $ ) {
	var
		SchemaGather = M.require( 'ext.gather.logging/SchemaGather' ),
		schema = new SchemaGather(),
		PageActionOverlay = M.require( 'modules/tutorials/PageActionOverlay' ),
		WatchstarPageActionOverlay;

	WatchstarPageActionOverlay = PageActionOverlay.extend( {
		className: PageActionOverlay.prototype.className + ' slide active editing',
		events: $.extend( {}, PageActionOverlay.prototype.events, {
			'click .cancel': 'onCancelClick',
			'click .actionable': 'onActionClick'
		} ),
		defaults: {
			summary: mw.msg( 'gather-add-to-collection-summary', mw.config.get( 'wgTitle' ) ),
			confirmMsg: mw.msg( 'gather-add-to-collection-confirm' ),
			cancelMsg: mw.msg( 'gather-add-to-collection-cancel' )
		},
		/**
		 * Event handler for cancelling the overlay
		 * @emits cancel
		 */
		onCancelClick: function () {
			this.hide();
			/**
			 * @event cancel
			 */
			this.emit( 'cancel' );
			schema.log( {
				eventName: 'dismiss-onboarding'
			} );
		},
		/**
		 * Event handler for cancelling the overlay
		 * @emits action
		 */
		onActionClick: function ( ev ) {
			this.hide();
			/**
			 * @event action
			 */
			this.emit( 'action', ev );
		}
	} );

	M.define( 'ext.gather.watchstar/WatchstarPageActionOverlay', WatchstarPageActionOverlay );
}( mw.mobileFrontend, jQuery ) );
