<?php

/**
 * CollectionsList.php
 */

namespace Gather;

/**
 * A list of collections, which are represented by the Collection class.
 */
class CollectionsList implements IteratorAggregate {
	/**
	 * @var Gather\Collection[] Internal list of collections.
	 */
	protected $lists = array();

	/**
	 * @var bool if the list can show private collections or not
	 */
	protected $includePrivate;

	/**
	 * Creates a list of collection cards
	 *
	 * @param User $user collection list owner
	 * @param boolean $includePrivate if the list can show private collections or not
	 */
	public function __construct( User $user, $includePrivate = false ) {
		$this->includePrivate = $includePrivate;

		// Get watchlist collection (private)
		// Directly avoid adding if not owner
		if ( $includePrivate ) {
			$watchlist = new Gather\Collection(
				$user,
				wfMessage( 'gather-watchlist-title' ),
				wfMessage( 'gather-watchlist-description' ),
				false
			);
			$watchlist->load( new WatchlistCollectionStore( $user ) );

			$this->add( $watchlist );
		}

		// FIXME: Add from UserCollectionStore
	}

	/**
	 * Adds a page to the collection.
	 * If the collection to add is private, and this collection list does not include
	 * private items, the collection won't be added
	 *
	 * @param Gather\Collection $collection
	 */
	public function add( Gather\Collection $collection ) {
		if ( $this->includePrivate ||
			( !$this->includePrivate && $collection->isPublic() ) ) {
			$this->lists[] = $collection;
		}
	}

	/**
	 * Gets the iterator for the internal array
	 *
	 * @return ArrayIterator
	 */
	public function getIterator() {
		return new ArrayIterator( $this->lists );
	}
}
