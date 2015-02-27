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


}
