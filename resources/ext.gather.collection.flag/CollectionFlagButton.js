import mobileFrontend from '../mobilefrontend';
import msg from '../messages';
import CollectionFlagOverlay from './CollectionFlagOverlay';

const CollectionsApi = mobileFrontend.require( 'ext.gather.api/CollectionsApi' ),
	Button = mobileFrontend.require( 'Button' ),
	Icon = mobileFrontend.require( 'Icon' );

let api = new CollectionsApi();

/**
 * A button used to flag a collection
 * @class CollectionFlagButton
 * @extends Button
 */
export default Button.extend( {
	/** @inheritdoc */
	defaults: {
		tagName: 'div',
		additionalClassNames: new Icon( {
			name: 'collection-flag',
			additionalClassNames: 'mw-ui-quiet'
		} ).getClassName(),
		title: msg( 'gather-flag-collection-flag-label' )
	},
	events: {
		click: 'onCollectionFlagButtonClick'
	},
	/** @inheritdoc */
	postRender () {
		Button.prototype.postRender.apply( this, arguments );
		this.$el.attr( 'title', this.options.title );
	},
	/**
	 * Click handler for collection flag button
	 * @param {Object} ev Event Object
	 */
	onCollectionFlagButtonClick ( ev ) {
		ev.stopPropagation();
		ev.preventDefault();

		if ( this.$el.hasClass( 'disabled' ) ) {
			return;
		}

		// Prevent multiple clicks
		this.$el.addClass( 'disabled' );

		api.getCollection( this.options.collectionId ).done( ( collection ) => {
			let flagOverlay = new CollectionFlagOverlay( {
				collection
			} );
			flagOverlay.show();

			// After flagging, remove flag icon.
			flagOverlay.on( 'collection-flagged', () => this.$el.detach() );

			flagOverlay.on( 'hide', () => this.$el.removeClass( 'disabled' ) );
		} );
	}
} );
