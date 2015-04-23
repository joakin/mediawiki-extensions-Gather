// jscs:disable requireCamelCaseOrUpperCaseIdentifiers
( function ( M, $ ) {

	var CollectionsWatchstar = M.require( 'ext.gather.watchstar/CollectionsWatchstar' ),
		PageActionOverlay = M.require( 'modules/tutorials/PageActionOverlay' ),
		WatchstarPageActionOverlay = M.require( 'ext.gather.watchstar/WatchstarPageActionOverlay' ),
		Tag = M.require( 'ext.gather.watchstar/Tag' ),
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
			// Show when not on the main page
			!page.isMainPage() &&
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
		watchstar.on( 'completed', function ( firstTimeUser ) {
			var p, tag;
			if ( firstTimeUser ) {
				// FIXME: Rename pointer overlay?
				p = new PageActionOverlay( {
					target: $( '#mw-mf-main-menu-button' ),
					summary: mw.msg( 'gather-menu-guider' ),
					cancelMsg: mw.msg( 'gather-add-to-collection-cancel' )
				} );
				p.show();
				// FIXME: Hacky.. Should use MainMenu but Bug: T93257.
				tag = new Tag( {
					label: 'new'
				} ).appendTo( '#mw-mf-page-left .collection-menu-item' );
			}
		} );
	}

	// Only init when current page is an article
	if ( !page.inNamespace( 'special' ) ) {
		init( page );
	}

}( mw.mobileFrontend, jQuery ) );
