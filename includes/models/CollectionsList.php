<?php

/**
 * models\CollectionsList.php
 */

namespace Gather\models;

use \User;
use Gather\models;
use \ArrayIterator;
use \ApiMain;
use \FauxRequest;

class CollectionsList implements \IteratorAggregate, ArraySerializable {

	/**
	 * @var CollectionInfo[] list of collection items
	 */
	protected $collections = array();

	/**
	 * @var bool if the list can show private collections or not
	 */
	protected $includePrivate;

	public function __construct( $includePrivate = false ) {
		$this->includePrivate = $includePrivate;
	}

	/**
	 * Adds a item to the collection.
	 * If the collection to add is private, and this collection list does not include
	 * private items, the collection won't be added
	 * @param CollectionInfo $collection
	 */
	public function add( CollectionInfo $collection ) {
		if ( $this->includePrivate ||
			( !$this->includePrivate && $collection->isPublic() ) ) {
			$this->collections[] = $collection;
		}
	}

	/**
	 * Adds an array of items to the collection
	 *
	 * @param CollectionInfo[] $items list of items to add
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
		return new ArrayIterator( $this->collections );
	}

	/**
	 * Gets the amount of collections in list
	 * @returns int
	 */
	public function getCount() {
		return count( $this->collections );
	}

	/** @inheritdoc */
	public function toArray() {
		$arr = array();
		foreach ( $this->collections as $collection ) {
			$arr[] = $collection->toArray();
		}
		return $arr;
	}

	/**
	 * Generate UserPageCollectionsList from api result
	 * FIXME: $user parameter currently ignored
	 * @param User $user collection list owner (currently ignored)
	 * @param boolean $includePrivate if the list should show private collections or not
	 * @return models\CollectionsList List of collections.
	 */
	public static function newFromApi( User $user, $includePrivate = false ) {
		$collectionsList = new CollectionsList( $includePrivate );
		$api = new ApiMain( new FauxRequest( array(
			'action' => 'query',
			'list' => 'lists',
			'lstprop' => 'label|description|public|count',
			'continue' => '',
		) ) );
		$api->execute();
		$data = $api->getResultData();
		if ( isset( $data['query']['lists'] ) ) {
			$lists = $data['query']['lists'];
			foreach ( $lists as $list ) {
				if ( $list['public'] || $includePrivate ) {
					$info = new models\CollectionInfo( $list['id'], $user,
						$list['label'], $list['description'], $list['public'] );
					$info->setCount( $list['count'] );
					$collectionsList->add( $info );
				}
			}
		}
		return $collectionsList;
	}
}

