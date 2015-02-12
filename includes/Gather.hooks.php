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
	/**
	 * Add collections link in personal tools menu
	 * @param array &$items Items array to be added to menu
	 */
	public static function addPersonalTools( &$items ) {
		if ( MobileContext::singleton()->isAlphaGroupMember() ) {
			// Add collections link below watchlist
			$itemArray = array_slice( $items, 0, 1, true ) +
				array(
					'collections' => array(
						'links' => array(
							array(
								'text' => wfMessage( 'gather-lists-title' )->escaped(),
								'href' => SpecialPage::getTitleFor( 'Gather' ),
								// FIXME: Temporarily watchlist icon
								'class' => CSS::iconClass( 'watchlist', 'before' ),
							),
						),
					),
				) +
				array_slice( $items, 1, count( $items ) - 1, true ) ;
			$items = $itemArray;
		}
	}
}
