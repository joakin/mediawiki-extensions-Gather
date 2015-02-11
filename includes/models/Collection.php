<?php

/**
 * Collection.php
 */

namespace Gather;

use \User;
use \Title;
use \IteratorAggregate;
use \ArrayIterator;
use \SpecialPage;

/**
 * A collection of items, which are represented by the CollectionItem class.
 */
class Collection implements IteratorAggregate {

	/**
	 * The internal collection of items.
	 *
	 * @var CollectionItem[]
	 */
	protected $items = array();

	/**
	 * Owner of collection
	 * @var User
	 */
	protected $owner;

	/**
	 * @var string
	 */
	protected $title;

	/**
	 * @var string
	 */
	protected $description;

	/**
	 * Whether collection is public or private
	 * Collection by default is true
	 *
	 * @var bool
	 */
	protected $public;

	/**
	 * @param User $user User that owns the collection
	 * @param string $title Title of the collection
	 * @param string $description Description of the collection
	 */
	public function __construct( User $user, $title = '', $description = '', $public = true ) {
		$this->owner = $user;
		$this->title = $title;
		$this->description = $description;
		$this->public = $public;
	}

	/**
	 * The internal id of a collection
	 *
	 * @var int id
	 */
	protected $id;

	/**
	 * Adds a item to the collection.
	 *
	 * @param CollectionItem $item
	 */
	public function add( CollectionItem $item ) {
		$this->items[] = $item;
	}

	/**
	 * Gets the iterator for the internal array
	 *
	 * @return ArrayIterator
	 */
	public function getIterator() {
		return new ArrayIterator( $this->items );
	}

	/**
	 * @return User
	 */
	public function getOwner() {
		return $this->owner;
	}

	/**
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * @return string
	 */
	public function getDescription() {
		return $this->description;
	}

	/**
	 * Returns if the list is public
	 *
	 * @return boolean
	 */
	public function isPublic() {
		return $this->public;
	}

	/**
	 * Set if the list is public
	 *
	 * @param boolean $public
	 */
	public function setPublic( $public ) {
		$this->public = $public;
	}

	/**
	 * @return int id The internal id of a collection
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * Returns items count
	 *
	 * @return int count of items in collection
	 */
	public function getCount() {
		return count( $this->items );
	}

	/**
	 * Return local url for collection
	 * Example: /wiki/Special:Gather/user/id
	 *
	 * @return string localized url for collection
	 */
	public function getUrl() {
		return SpecialPage::getTitleFor( 'Gather' )
			->getSubpage( $this->getOwner() )
			->getSubpage( $this->getId() )
			->getLocalURL();
	}

	/**
	 * @return array list of items
	 */
	public function getItems() {
		return $this->items;
	}

	/**
	 * Adds an array of titles to the collection
	 *
	 * @param CollectionStore $store
	 */
	public function load( CollectionStore $store ) {
		$this->id = $store->getId();
		$items = $store->getItems();
		foreach ( $items as $item ) {
			$this->add( $item );
		}
	}

}
