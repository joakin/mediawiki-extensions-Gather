import $ from '../jquery';
import mobileFrontend from '../mobilefrontend';

const CollectionFlagButton = mobileFrontend.require( 'ext.gather.collection.flag/CollectionFlagButton' );

$( function () {
	let $collection = $( '.collection' );

	if ( !$collection.data( 'is-owner' ) ) {
		new CollectionFlagButton( {
			collectionId: $collection.data( 'id' )
		} ).prependTo( '.collection-moderation' );
	}

	$( '.collection-actions' ).addClass( 'visible' );
} );
