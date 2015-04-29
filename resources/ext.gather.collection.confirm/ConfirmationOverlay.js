( function ( M, $ ) {

	var ConfirmationOverlay,
		toast = M.require( 'toast' ),
		icons = M.require( 'icons' ),
		CollectionsContentOverlayBase = M.require( 'ext.gather.collection.base/CollectionsContentOverlayBase' );

	/**
	 * Action confirmation overlay base class
	 * @extends CollectionsContentOverlayBase
	 * @class ConfirmationOverlay
	 */
	ConfirmationOverlay = CollectionsContentOverlayBase.extend( {
		/** @inheritdoc */
		className: 'overlay collection-confirmation-overlay content-overlay position-fixed',
		/** @inheritdoc */
		defaults: $.extend( {}, CollectionsContentOverlayBase.prototype.defaults, {
			fixedHeader: false,
			collection: null,
			spinner: icons.spinner().toHtmlString(),
			unknownCollectionError: mw.msg( 'gather-error-unknown-collection' ),
			cancelButtonClass: 'mw-ui-progressive',
			cancelButtonLabel: mw.msg( 'gather-confirmation-cancel-button-label' )
		} ),
		/** @inheritdoc */
		events: $.extend( {}, CollectionsContentOverlayBase.prototype.events, {
			'click .cancel': 'onCancelClick'
		} ),
		/** @inheritdoc */
		templatePartials: $.extend( {}, CollectionsContentOverlayBase.prototype.templatePartials, {
			content: mw.template.get( 'ext.gather.collection.confirm', 'confirmationOverlay.hogan' )
		} ),
		/** @inheritdoc */
		initialize: function ( options ) {
			var collection = options.collection;
			if ( !collection ) {
				// use toast
				toast.show( options.unknownCollectionError, 'toast error' );
			} else {
				this.id = collection.id;
				CollectionsContentOverlayBase.prototype.initialize.apply( this, arguments );
			}
		},
		/**
		 * Event handler when the cancel button is clicked.
		 */
		onCancelClick: function () {
			this.hide();
		}
	} );

	M.define( 'ext.gather.collection.confirm/ConfirmationOverlay', ConfirmationOverlay );

}( mw.mobileFrontend, jQuery ) );
