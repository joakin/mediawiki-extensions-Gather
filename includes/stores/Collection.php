<?php

namespace Gather\stores;

use Gather\models;

/**
 * Abstraction for collection storage.
 */
abstract class Collection {
	/**
	 * @var models\Collection
	 */
	protected $collection;

	/**
	 * Get Collection model of the current collection.
	 */
	public function getCollection() {
		return $this->collection;
	}

	/**
	 * Get collection items from a list of titles
	 * @param Title[] $titles
	 *
	 * @return models\CollectionItem[]
	 */
	public function getItemsFromTitles( $titles ) {
		$extracts = ItemExtracts::loadExtracts( $titles );
		$images = ItemImages::loadImages( $titles );

		// Merge the data into models\CollectionItem
		$items = array();
		foreach ( $titles as $key=>$title ) {
			// Check, if this page has a page image
			if ( isset( $images[$title->getArticleId()] ) ) {
				$image = $images[$title->getArticleId()];
			} else {
				$image = false;
			}
			$items[] = new models\CollectionItem( $title, $image, $extracts[$key] );
		}

		return $items;
	}
}
