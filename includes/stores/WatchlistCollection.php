<?php

namespace Gather\stores;

use Gather\models;

use \User;
use \Title;
use \GenderCache;

/**
 * Abstraction for watchlist storage.
 * FIXME: This should live in core and power Special:EditWatchlist
 */
class WatchlistCollection extends Collection {

	/**
	 * Get a watchlist by user
	 * @param User $user to lookup watchlist for
	 * @return models\Collection
	 */
	public static function newFromUser( User $user ) {
		$titles = self::loadTitles( $user );
		$items = self::getItemsFromTitles( $titles );

		// Grab first image available for the collection
		$firstImage = null;
		foreach ( $items as $item ) {
			if ( $item->hasImage() ) {
				$firstImage = $item->getFile();
				break;
			}
		}

		// Construct the models\Collection
		$collection = new models\Collection(
			0, // Watchlist has a hardcoded id of 0
			$user,
			wfMessage( 'gather-watchlist-title' )->text(),
			wfMessage( 'gather-watchlist-description' )->text(),
			false, // Watchlist is private
			$firstImage
		);
		$collection->batch( $items );

		return $collection;
	}

	/**
	 * Load titles of the watchlist
	 * @param User $user
	 * @return Title[]
	 */
	private static function loadTitles( $user ) {
		$dbr = wfGetDB( DB_SLAVE );

		$res = $dbr->select(
			'watchlist',
			array( 'wl_namespace', 'wl_title'),
			array(
				'wl_user' => $user->getId(),
				'wl_namespace' => 0,
			),
			__METHOD__,
			array( 'LIMIT' => 50,)
		);

		$titles = array();
		if ( $res->numRows() > 0 ) {
			foreach ( $res as $row ) {
				$title = Title::makeTitle( $row->wl_namespace, $row->wl_title );
				$titles[] = $title;
			}
			$res->free();
		}
		GenderCache::singleton()->doTitlesArray( $titles );

		return $titles;
	}

}
