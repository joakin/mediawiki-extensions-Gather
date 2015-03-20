// jscs:disable requireCamelCaseOrUpperCaseIdentifiers
( function ( M, $ ) {

	var actionOverlay,
		$target = $( '#ca-watch' ),
		CollectionsWatchstar = M.require( 'ext.gather.watchstar/CollectionsWatchstar' ),
		PageActionOverlay = M.require( 'modules/tutorials/PageActionOverlay' ),
		settings = M.require( 'settings' ),
		settingName = 'gather-has-dismissed-tutorial',
		util = M.require( 'util' ),
		user = M.require( 'user' );

	/**
	 * Determines if collection tutorial should be shown
	 *
	 * @method
	 * @ignore
	 * @returns {Boolean}
	 */
	function shouldShowCollectionTutorial() {
		var collections = mw.config.get( 'wgGatherCollections' ),
			// Show to anonymous or a user with only watchstar as a collection
			// note ES5 method usage
			showToUser = user.isAnon() || Object.keys( collections ).length === 1;

		if (
			// User only has a watchlist, meaning they have not created a collection
			showToUser &&
			// Tutorial has never been dismissed
			!settings.get( settingName ) &&
			// Feature flag is enabled
			mw.config.get( 'wgGatherShouldShowTutorial' )
		) {
			return true;
		}
		return false;
	}

	/**
	 * Disable and hide the tutorial
	 * @method
	 * @ignore
	 */
	function dismissTutorial() {
		actionOverlay.hide();
		settings.save( settingName, true );
	}

	/**
	 * Show a pointer that points to the collection feature.
	 * @method
	 * @param {Watchstar} watchstar
	 * @ignore
	 */
	function showPointer( watchstar ) {
		// FIXME: Should be it's own View (WatchstarPageActionOverlay)
		actionOverlay = new PageActionOverlay( {
			target: $target,
			className: 'slide active editing',
			summary: mw.msg( 'gather-add-to-collection-summary', mw.config.get( 'wgTitle' ) ),
			confirmMsg: mw.msg( 'gather-add-to-collection-confirm' ),
			cancelMsg: mw.msg( 'gather-add-to-collection-cancel' )
		} );
		actionOverlay.show();
		// Refresh pointer otherwise it is not positioned
		// FIXME: Remove when ContentOverlay is fixed
		actionOverlay.refreshPointerArrow( $target );
		// Dismiss when watchstar is clicked
		$target.on( 'click', dismissTutorial );
		// Dismiss when 'No thanks' button is clicked
		actionOverlay.$( '.cancel' ).on( 'click', dismissTutorial );
		// Toggle WatstarOverlay and dismiss
		actionOverlay.$( '.actionable' ).on( 'click', function ( ev ) {
			// Hide the tutorial
			watchstar.onStatusToggle.call( watchstar, ev );
			dismissTutorial();
		} );
	}

	/**
	 * Toggle the watch status of a known page
	 * @method
	 * @param {Page} page
	 * @ignore
	 */
	function init( page ) {
		var watchstar = new CollectionsWatchstar( {
			el: $target,
			page: page,
			isAnon: user.isAnon(),
			collections: mw.config.get( 'wgGatherCollections' ),
			isNewlyAuthenticatedUser: util.query.article_action === 'add_to_collection'
		} );
		if ( !page.inNamespace( 'special' ) ) {
			// Determine if we should show the collection tutorial
			if ( $target.length > 0 && shouldShowCollectionTutorial() ) {
				// FIXME: Timeout shouldn't be necessary but T91047 exists.
				setTimeout( function () {
					showPointer( watchstar );
				}, 2000 );
			}
		}
	}

	init( M.getCurrentPage() );

}( mw.mobileFrontend, jQuery ) );
