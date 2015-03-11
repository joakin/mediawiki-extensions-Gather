<?php

namespace Gather\stores;

use Gather\models;

use \User;
use \Title;

/**
 * Abstraction for json collection storage on user pages.
 * FIXME: This should live in core and power Special:EditWatchlist
 */
class UserPageCollection extends Collection implements CollectionStorage {
	const FOLDER = 'GatherCollections';

	/**
	 * Get Collection model with an id of a user
	 * @param User $owner of the collection
	 * @param int $id of the collection
	 * @return models\Collection
	 */
	public static function newFromUserAndId( $owner, $id ) {
		if ( $id !== 0 ) {
			$collectionData = JSONPage::get( self::getStorageTitle( $owner, $id ) );
			return self::collectionFromJSON( $collectionData );
		} else {
			// id 0 is the watchlist. Which loads differently
			return WatchlistCollection::newFromUser( $owner );
		}
	}

	/**
	 * Get the url for the collection
	 * @param User $owner of the collection
	 * @param int $id of the collection
	 *
	 * @return Title
	 */
	public static function getStorageTitle( $owner, $id ) {
		$title = $owner->getName() . '/' . self::FOLDER . '/' . $id . '.json';
		return Title::makeTitleSafe( NS_USER, $title );
	}

	/**
	 * Fill a collection object from json data
	 * Returns null if there is not enough information to fill it up.
	 * @param array $json data to pull information from
	 *
	 * @return models\Collection|null
	 */
	public static function collectionFromJSON( $json ) {
		try {
			if ( !isset($json['id']) ||
				!isset($json['owner']) ||
				!isset($json['title']) ) {
				return null;
			}

			$collection = new models\Collection(
				$json['id'],
				User::newFromName( $json['owner'] ),
				$json['title'],
				$json['description'],
				$json['public'],
				wfFindFile( $json['image'] )
			);
			if ( isset( $json['items'] ) ) {
				// Make titles
				$titles = array();
				foreach ( $json['items'] as $title ) {
					if ( is_string( $title ) && isset( $title ) ) {
						$titles[] = Title::newFromText( $title );
					}
				}
				$collection->batch( self::getItemsFromTitles( $titles ) );
			}
			return $collection;
		} catch (Exception $e) {
			// Invalid json
			return null;
		}
	}

}
