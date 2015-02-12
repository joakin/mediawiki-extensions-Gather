<?php

/**
 * CollectionsListStore.php
 */

namespace Gather\stores;

use Gather\models\Collection;
use \User;

/**
 * Abstract class for a store that loads the collections of a user.
 * Extend it and implement loadCollections.
 */
abstract class CollectionsListStore {

	/**
	 * @var User Owner of the collections
	 */
	protected $user;

	/**
	 * @var Collection[] Internal list of collections.
	 */
	protected $lists = array();

	/**
	 * @var bool if the list can show private collections or not
	 */
	protected $includePrivate;

	/**
	 * Creates a list of collections
	 *
	 * @param User $user collection list owner
	 * @param boolean $includePrivate if the list can show private collections or not
	 */
	public function __construct( User $user, $includePrivate = false ) {
		$this->user = $user;
		$this->includePrivate = $includePrivate;
		$collections = $this->loadCollections();
		foreach ( $collections as $collection ) {
			$this->add( $collection );
		}
	}

	/**
	 * Load collections of the user
	 *
	 * @return CollectionItem[] titles
	 */
	abstract public function loadCollections();

	/**
	 * Adds a page to the collection.
	 * If the collection to add is private, and this collection list does not include
	 * private items, the collection won't be added
	 *
	 * @param Collection $collection
	 */
	public function add( Collection $collection ) {
		if ( $this->includePrivate ||
			( !$this->includePrivate && $collection->isPublic() ) ) {
			$this->lists[] = $collection;
		}
	}

	/**
	 * Returns the list of collections
	 *
	 * @return Collection[]
	 */
	public function getLists() {
		return $this->lists;
	}
}

