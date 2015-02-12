<?php

/**
 * CollectionsList.php
 */

namespace Gather\stores;

use Gather\models;
use \User;

/**
 * Abstract class for a store that loads the collections of a user.
 * Extend it and implement loadCollections.
 */
abstract class CollectionsList {

	/**
	 * @var User Owner of the collections
	 */
	protected $user;

	/**
	 * @var models\Collection[] Internal list of collections.
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
	 * @param models\Collection $collection
	 */
	public function add( models\Collection $collection ) {
		if ( $this->includePrivate ||
			( !$this->includePrivate && $collection->isPublic() ) ) {
			$this->lists[] = $collection;
		}
	}

	/**
	 * Returns the list of collections
	 *
	 * @return models\Collection[]
	 */
	public function getLists() {
		return $this->lists;
	}
}

