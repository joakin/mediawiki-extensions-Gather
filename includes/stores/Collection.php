<?php

namespace Gather\stores;

use Gather\models;

use \User;
use \Title;

/**
 * Abstraction for collection storage.
 */
abstract class Collection {

	/**
	 * Get collection items from a list of titles
	 * @param Title[] $titles
	 *
	 * @return models\CollectionItem[]
	 */
	public static function getItemsFromTitles( $titles ) {
		if ( count( $titles ) > 0 ) {
			$extracts = ItemExtracts::loadExtracts( $titles );
			$images = ItemImages::loadImages( $titles );
		}

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
