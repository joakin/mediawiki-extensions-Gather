import $ from '../jquery.js';
import CollectionsList from '../ext.gather.collections.list/CollectionsList.js';

let $collectionsList = $( '.collections-list' ),
	owner = $collectionsList.data( 'owner' ),
	mode = $collectionsList.data( 'mode' );

$( function () {
	new CollectionsList( {
		el: $collectionsList,
		enhance: true,
		owner,
		mode
	} );
} );
