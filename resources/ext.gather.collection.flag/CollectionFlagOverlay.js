import mobileFrontend from '../mobilefrontend';
import msg from '../messages';
import extend from 'xtend';

const ConfirmationOverlay = mobileFrontend.require( 'ext.gather.collection.confirm/ConfirmationOverlay' ),
	SchemaGatherFlags = mobileFrontend.require( 'ext.gather.logging/SchemaGatherFlags' ),
	toast = mobileFrontend.require( 'toast' );

let schema = new SchemaGatherFlags();

/**
	* Overlay for deleting a collection
	* @extends ConfirmationOverlay
	* @class CollectionFlagOverlay
	*/
export default ConfirmationOverlay.extend( {
	/** @inheritdoc */
	defaults: extend( ConfirmationOverlay.prototype.defaults, {
		flagSuccessMsg: msg( 'gather-flag-collection-success' ),
		subheading: msg( 'gather-flag-collection-heading' ),
		confirmMessage: msg( 'gather-flag-collection-confirm' ),
		confirmButtonClass: 'mw-ui-destructive',
		confirmButtonLabel: msg( 'gather-flag-collection-flag-label' )
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
