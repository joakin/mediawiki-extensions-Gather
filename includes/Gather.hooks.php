<?php
/**
 * Gather.hooks.php
 */

namespace Gather;

use \SpecialPage;
use Gather\views\helpers\CSS;
use \MobileContext;

/**
 * Hook handlers for Gather extension
 *
 * Hook handler method names should be in the form of:
 *	on<HookName>()
 * For intance, the hook handler for the 'RequestContextCreateSkin' would be called:
 *	onRequestContextCreateSkin()
 */
class Hooks {
	public static function onExtensionSetup() {
		// FIXME: This doesn't do anything as if mobilefrontend is not present
		// The reported error is "This requires Gather."
		if ( !defined( 'MOBILEFRONTEND' ) ) {
			echo "Gather extension requires MobileFrontend.\n";
			die( -1 );
		}
	}
	/**
	 * Add collections link in personal tools menu
	 * @param array &$items Items array to be added to menu
	 */
	public static function onMobilePersonalTools( &$items ) {
		if ( MobileContext::singleton()->isAlphaGroupMember() ) {
			// Add collections link below watchlist
			$itemArray = array_slice( $items, 0, 1, true ) +
				array(
					'collections' => array(
						'links' => array(
							array(
								'text' => wfMessage( 'gather-lists-title' )->escaped(),
								'href' => SpecialPage::getTitleFor( 'Gather' )->getLocalURL(),
								// FIXME: Temporarily watchlist icon
								'class' => CSS::iconClass( 'watchlist', 'before' ),
							),
						),
					),
				) +
				array_slice( $items, 1, count( $items ) - 1, true );
			$items = $itemArray;
		}
	}

	/**
	 * UnitTestsList hook handler
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UnitTestsList
	 *
	 * @param array $files
	 * @return bool
	 */
	public static function onUnitTestsList( &$files ) {
		$files[] = __DIR__ . '/../tests/phpunit';

		return true;
	}

	/**
	 * Disallow moving or editing gather page json files
	 */
	public static function onGetUserPermissionsErrors( $title, $user, $action, &$result ) {
		$manifest = "/GatherCollections.json";
		$isProtectedAction = $action === 'edit' || $action === 'move';
		$titleText = $title->getText();
		if ( $title->getNamespace() === NS_USER && $isProtectedAction &&
				preg_match( "/\/GatherCollections\.json$/", $titleText ) === 1
		) {
			// we have a collection definition so check the user matches the title.
			if ( preg_match( "/^" . $user->getName() . "\/GatherCollections.json$/", $titleText ) === 1 ) {
				return true;
			} else {
				$result = false;
				return false;
			}
		} else {
			return true;
		}
	}
}
