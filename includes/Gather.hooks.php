<?php
/**
 * Gather.hooks.php
 */

namespace Gather;

use \SpecialPage;
use Gather\stores;
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
	 * Modify mobile frontend modules to hook into the watchstar
	 * @param SkinMinerva $skin
	 * @param array $modules Resource loader modules
	 * @return boolean
	 */
	public static function onSkinMinervaDefaultModules( $skin, &$modules ) {
		if ( MobileContext::singleton()->isAlphaGroupMember() ) {
			$modules['watch'] = array( 'ext.gather.watchstar' );
		}
		return true;
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
		if ( $title->inNamespace( NS_USER ) && $isProtectedAction &&
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

	/**
	 * Load user collections
	 */
	public static function onMakeGlobalVariablesScript( &$vars, $out ) {
		$user = $out->getUser();
		if ( !$user->isAnon() ) {
			$collectionsList = stores\UserPageCollectionsList::newFromUser( $user, true );
			$gatherCollections = array();
			foreach ( $collectionsList as $collectionInfo ) {
				$id = $collectionInfo->getId();
				$collection = stores\UserPageCollection::newFromUserAndId( $user, $id );
				if ( $collection !== null ) {
					$gatherCollections[] = array(
						'id' => $id,
						'isWatchlist' => $id === 0,
						'isPublic' => $collectionInfo->isPublic(),
						'title' => $collectionInfo->getTitle(),
						'description' => $collectionInfo->getDescription(),
						'titleInCollection' => $collection->hasMember( $out->getTitle() ),
					);
				}
			}
			$vars['wgGatherCollections'] = $gatherCollections;
		}
		return true;
	}

	/**
	 * Convert the content model of json files that are actually JSON to JSON.
	 * This only affects validation and UI when saving and editing, not
	 * loading the content.
	 * @param $title Title
	 * @param &$model string
	 *
	 * @return bool
	 */
	public static function onContentHandlerDefaultModelFor( $title, &$model ) {
		$titleText = $title->getText();
		if ( $title->inNamespace( NS_USER ) &&
			preg_match( "/\/GatherCollections/", $titleText ) === 1 ) {
			$model = CONTENT_MODEL_JSON;
		}
		return true;
	}
}
