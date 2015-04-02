// jscs:disable requireCamelCaseOrUpperCaseIdentifiers
( function ( M, $ ) {

	var CollectionsWatchstar = M.require( 'ext.gather.watchstar/CollectionsWatchstar' ),
		WatchstarPageActionOverlay = M.require( 'ext.gather.watchstar/WatchstarPageActionOverlay' ),
		settings = M.require( 'settings' ),
		settingOverlayWasDismissed = 'gather-has-dismissed-tutorial',
		user = M.require( 'user' ),
		page = M.getCurrentPage();

	/**
	 * Determines if collection tutorial should be shown
	 *
	 * @method
	 * @ignore
	 * @returns {Boolean}
	 */
	function shouldShowCollectionTutorial() {
		if (
			mw.config.get( 'wgNamespaceNumber' ) === 0 &&
			// Don't show this when mobile is showing edit tutorial
			mw.util.getParamValue( 'article_action' ) !== 'signup-edit' &&
			// Don't show if the overlay is open as user could have clicked watchstar
			!$( 'html' ).hasClass( 'gather-overlay-enabled' ) &&
			// Tutorial has never been dismissed
			!settings.get( settingOverlayWasDismissed ) &&
			// Feature flag is enabled
			mw.config.get( 'wgGatherShouldShowTutorial' )
		) {
			return true;
		}
		return false;
	}

	/**
	 * Overlay was dismissed.
	 * @method
	 * @ignore
	 */
	function overlayDismissed() {
		settings.save( settingOverlayWasDismissed, true );
	}

	/**
	 * Show a pointer that points to the collection feature.
	 * @method
	 * @param {Watchstar} watchstar to react when actionable
	 * @ignore
	 */
	function showPointer( watchstar ) {
		var $star = watchstar.$el,
			actionOverlay = new WatchstarPageActionOverlay( {
				target: $star
			} );

		// Dismiss when watchstar is clicked
		$star.on( 'click', function () {
			actionOverlay.hide();
			overlayDismissed();
		} );
		// Dismiss when 'No thanks' button is clicked
		actionOverlay.on( 'cancel', overlayDismissed );
		// Toggle WatstarOverlay and dismiss
		actionOverlay.on( 'action', function ( ev ) {
			watchstar.onStatusToggle( ev );
			overlayDismissed();
		} );
		actionOverlay.show();
		// Refresh pointer otherwise it is not positioned
		// FIXME: Remove when ContentOverlay is fixed
		actionOverlay.refreshPointerArrow( $star );
	}

	/**
	 * Swap out the default watchstar for our link
	 * @method
	 * @param {Page} page
	 * @ignore
	 */
	function init( page ) {
		var $star = $( '#ca-watch' ),
			shouldShow = shouldShowCollectionTutorial(),
			watchstar = new CollectionsWatchstar( {
				page: page,
				isAnon: user.isAnon(),
				isWatched: $star.hasClass( 'watched' ),
				wasUserPrompted: shouldShow,
				isNewlyAuthenticatedUser: mw.util.getParamValue( 'article_action' ) === 'add_to_collection'
			} );

		watchstar.insertBefore( $star );
		$star.remove();
		if ( shouldShow ) {
			showPointer( watchstar );
		}
	}
	// Only init when current page is an article
	if ( !page.inNamespace( 'special' ) ) {
		init( page );
	}

}( mw.mobileFrontend, jQuery ) );
