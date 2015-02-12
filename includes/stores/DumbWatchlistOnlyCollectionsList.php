<?php

/**
 * DumbOnlyWatchlistCollectionsList.php
 */

namespace Gather\stores;

use Gather\models;

/**
 * Dumb collections list store that only knows to return the watchlist.
 *
 * FIXME: This class will be substituted when we actually load collections list from
 * somewhere else.
 */
class DumbWatchlistOnlyCollectionsList extends CollectionsList {
	/**
	 * @inherit
	 */
	public function loadCollections() {
		$collections = array();
		// Dumb collections list getter, only returns the watchlist.
		// Get watchlist collection (private)
		// Directly avoid adding if no privates
		if ( $this->includePrivate ) {
			$watchlistStore = new WatchlistCollection( $this->user );
			$collections[] = $watchlistStore->getCollection();
		}
		return $collections;
	}
}

