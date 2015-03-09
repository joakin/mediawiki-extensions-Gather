<?php

/**
 * UserPageCollectionsList.php
 */

namespace Gather\stores;

use Gather\models;
use \User;
use \Title;

/**
 * Stores and retrieves collection lists from user pages
 */
class UserPageCollectionsList implements CollectionsListStorage {
	const MANIFEST_FILE = 'GatherCollections.json';

	/**
	 * Get list of collections by user
	 * @param User $user collection list owner
	 * @param boolean $includePrivate if the list should show private collections or not
	 * @return models\CollectionsList List of collections.
	 */
	public static function newFromUser( User $user, $includePrivate = false ) {
		$collectionsList = new models\CollectionsList( $includePrivate );
		// Add watchlist
		$watchlist = WatchlistCollection::newFromUser( $user );
		$watchlistInfo = new models\CollectionInfo(
			$watchlist->getId(),
			$watchlist->getOwner(),
			$watchlist->getTitle(),
			$watchlist->getDescription(),
			$watchlist->isPublic(),
			$watchlist->getFile()
		);
		$watchlistInfo->setCount( $watchlist->getCount() );
		$collectionsList->add( $watchlistInfo );
		// Add collections
		$collectionsData = JSONPage::get( self::getStorageTitle( $user ) );
		foreach ( $collectionsData as $collectionData ) {
			$collectionsList->add( self::collectionFromJSON( $collectionData ) );
		}
		return $collectionsList;
	}

	/**
	 * Get formatted title of the page that contains the manifest
	 * @param User $user
	 * @return Title
	 */
	public static function getStorageTitle( User $user ) {
		$title = $user->getName() . '/' . self::MANIFEST_FILE;
		return Title::makeTitleSafe( NS_USER, $title );
	}

	/**
	 * Get a basic collection object with the metadata from json data in the manifest
	 * @param array $json data to pull information from
	 *
	 * @return models\CollectionInfo
	 */
	public static function collectionFromJSON( $json ) {
		$collection = new models\CollectionInfo(
			$json['id'],
			User::newFromName( $json['owner'] ),
			$json['title'],
			$json['description'],
			$json['public'],
			wfFindFile( $json['image'] )
		);
		$collection->setCount( $json['count'] );
		return $collection;
	}

}
