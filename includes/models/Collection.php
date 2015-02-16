<?php

/**
 * Collection.php
 */

namespace Gather\models;

use \User;
use \Title;
use \IteratorAggregate;
use \ArrayIterator;
use \SpecialPage;

/**
 * A collection of items, which are represented by the CollectionItem class.
 */
class Collection implements IteratorAggregate, WithImage {

	/**
	 * The internal collection of items.
	 *
	 * @var CollectionItem[]
	 */
	protected $items = array();

	/**
	 * The internal id of a collection
	 *
	 * @var int id
	 */
	protected $id;

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
	 * Image that represents the collection.
	 *
	 * @var File
	 */
	protected $image;

	/**
	 * @param int $id id of the collection. Null if not persisted yet
	 * @param User $user User that owns the collection
	 * @param string $title Title of the collection
	 * @param string $description Description of the collection
	 * @param File $image Main image of the collection
	 * @param boolean $public Whether the collection is public or private
	 */
	public function __construct( $id = null, User $user, $title = '', $description = '',
		$image = null, $public = true ) {

		$this->id = $id;
		$this->owner = $user;
		$this->title = $title;
		$this->description = $description;
		$this->image = $image;
		$this->public = $public;
	}

	/**
	 * Adds a item to the collection.
	 *
	 * @param CollectionItem $item
	 */
	public function add( CollectionItem $item ) {
		$this->items[] = $item;
	}

	/**
	 * Adds an array of items to the collection
	 *
	 * @param CollectionItem[] $items list of items to add
	 */
	public function batch( $items ) {
		foreach ( $items as $item ) {
			$this->add( $item );
		}
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
	 * @inheritdoc
	 */
	public function hasImage() {
		return $this->image ? true : false;
	}

	/**
	 * @inheritdoc
	 */
	public function getFile() {
		return $this->image;
	}

}
