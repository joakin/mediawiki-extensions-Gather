import extend from 'xtend';

const ConfirmationOverlay = mw.mobileFrontend.require( 'ext.gather.collection.confirm/ConfirmationOverlay' ),
	SchemaGatherFlags = mw.mobileFrontend.require( 'ext.gather.logging/SchemaGatherFlags' ),
	toast = mw.mobileFrontend.require( 'toast' );

let schema = new SchemaGatherFlags();

/**
	* Overlay for deleting a collection
	* @extends ConfirmationOverlay
	* @class CollectionFlagOverlay
	*/
export default ConfirmationOverlay.extend( {
	/** @inheritdoc */
	defaults: extend( ConfirmationOverlay.prototype.defaults, {
		flagSuccessMsg: mw.msg( 'gather-flag-collection-success' ),
		subheading: mw.msg( 'gather-flag-collection-heading' ),
		confirmMessage: mw.msg( 'gather-flag-collection-confirm' ),
		confirmButtonClass: 'mw-ui-destructive',
		confirmButtonLabel: mw.msg( 'gather-flag-collection-flag-label' )
	} ),
	/** @inheritdoc */
	events: extend( ConfirmationOverlay.prototype.events, {
		'click .confirm': 'onFlagClick'
	} ),
	/**
		* Event handler when the delete button is clicked.
		*/
	onFlagClick () {
		this.showSpinner();
		// disable buttons
		this.$( '.confirm, .cancel' ).prop( 'disabled', true );
		schema.log( {
			collectionId: this.id
		} ).always( () => {
			toast.show( this.options.flagSuccessMsg, 'toast' );
			this.emit( 'collection-flagged' );
			this.hide();
		} );
	},
	/**
	 * Override Overlay:onExit function as this overlay is not controlled by OverlayManager
	 */
	onExit () {}
} );
