<?php

/**
 * CollectionInfo.php
 */

namespace Gather\models;

/**
 * The info of collection of items.
 *
 * Extends the base class with a count you can set.
 */
class CollectionInfo extends CollectionBase {
	/** @var array $knownMembers cache of known members in this collection */
	protected $knownMembers = array();
	/** @var int $count of items in the collection */
	protected $count;

	/**
	 * Returns items count
	 * @param int $count of items in collection
	 */
	public function setCount( $count ) {
		$this->count = $count;
	}

	/**
	 * Returns items count
	 *
	 * @return int count of items in collection
	 */
	public function getCount() {
		return $this->count;
	}

	/** @inheritdoc */
	public function toArray() {
		$data = parent::toArray();
		$data['count'] = $this->count;
		return $data;
	}

	/**
	 * @param string $title
	 * @param boolean $isMember whether this title is in the collection or not.
	 */
	public function setMember( $title, $isMember ) {
		$this->knownMembers[$title] = $isMember;
	}

	/**
	 * Returns whether the given title is a known member of the collection
	 * @param string $title
	 * @return boolean
	 */
	public function isKnownMember( $title ) {
		return $this->knownMembers[$title];
	}

}
