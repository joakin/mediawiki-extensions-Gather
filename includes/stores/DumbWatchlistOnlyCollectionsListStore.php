<?php

/**
 * DumbOnlyWatchlistCollectionsListStore.php
 */

namespace Gather\stores;

use Gather\models\Collection;

/**
 * Dumb collections list store that only knows to return the watchlist.
 *
 * FIXME: This class will be substituted when we actually load collections list from
 * somewhere else.
 */
class DumbWatchlistOnlyCollectionsListStore extends CollectionsListStore {
	/**
	 * @inherit
	 */
	public function loadCollections() {
		$collections = array();
		// Dumb collections list getter, only returns the watchlist.
		// Get watchlist collection (private)
		// Directly avoid adding if no privates
		if ( $this->includePrivate ) {
			$watchlist = new Collection(
				$this->user,
				wfMessage( 'gather-watchlist-title' ),
				wfMessage( 'gather-watchlist-description' ),
				false
			);
			$watchlist->load( new WatchlistCollectionStore( $this->user ) );
			$collections[] = $watchlist;
		}
		return $collections;
	}
}

