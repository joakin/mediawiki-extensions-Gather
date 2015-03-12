<?php

/**
 * Collection.php
 */

namespace Gather\models;

use \IteratorAggregate;
use \ArrayIterator;
use \User;
use \ApiMain;
use \FauxRequest;
use \Gather\stores\ItemExtracts;
use \Title;

/**
 * A collection with a list of items, which are represented by the CollectionItem class.
 */
class Collection extends CollectionBase implements IteratorAggregate {
	/**
	 * The internal collection of items.
	 *
	 * @var CollectionItem[]
	 */
	protected $items = array();

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
	 * @return array list of items
	 */
	public function getItems() {
		return $this->items;
	}

	/**
	 * Whether collection has a given title as a member
	 *
	 * @param Title $title
	 *
	 * @return boolean [description]
	 */
	public function hasMember( $title ) {
		foreach ( $this->items as $item ) {
			if ( $item->getTitle()->getFullText() === $title->getFullText() ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Returns items count
	 *
	 * @return int count of items in collection
	 */
	public function getCount() {
		return count( $this->items );
	}

	/** @inheritdoc */
	public function toArray() {
		$data = parent::toArray();
		$data['items'] = array();
		foreach ( $this->this as $item ) {
			$data['items'][] = $item->toArray();
		}
		return $data;
	}

	/**
	 * Generate UserPageCollectionsList from api result
	 * @param Integer $id the id of the collection
	 * @param User $user collection list owner (currently unused)
	 * @return models\Collections a collection
	 */
	public static function newFromApi( $id, User $user ) {
		// Work out meta data for this collection
		$cl = CollectionsList::newFromApi( $user );
		$collection = null;
		foreach ( $cl as $c ) {
			if ( $c->getId() === $id ) {
				$collection = self::newFromCollectionInfo( $c );
			}
		}
		if ( $collection ) {
			$api = new ApiMain( new FauxRequest( array(
				'action' => 'query',
				'prop' => 'pageimages|extracts',
				'generator' => 'listpages',
				'glspid' => $id,
				'explaintext' => true,
				'exintro' => true,
				'exchars' => ItemExtracts::CHAR_LIMIT,
				'exlimit' => 50,
			) ) );
			try {
				$api->execute();
				$data = $api->getResultData();
				if ( isset( $data['query']['pages'] ) ) {
					$pages = $data['query']['pages'];
					foreach ( $pages as $page ) {
						$title = Title::newFromText( $page['title'], $page['ns'] );
						// FIXME: Make use of the page image
						$pi = false;
						$extract = isset( $page['extract'][0] ) ? $page['extract'][0] : '';
						$collection->add( new CollectionItem( $title, $pi, $extract ) );
					}
				}
			} catch ( Exception $e ) {
				// just return collection
			}
		}

		return $collection;
	}

	/**
	 * @param CollectionInfo $info
	 * @return models\Collection
	 */
	public static function newFromCollectionInfo( $info ) {
		return new Collection( $info->getId(), $info->getOwner(),
			$info->getTitle(), $info->getDescription(), $info->isPublic(), $info->getFile());
	}
}
