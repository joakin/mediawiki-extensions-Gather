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
class UserPageCollectionsList extends CollectionsList {
	const MANIFEST_FILE = 'GatherCollections.json';

	/**
	 * @inherit
	 */
	public function loadCollections() {
		$collectionsData = JSONPage::get( $this->getStorageTitle() );
		foreach ( $collectionsData as $collectionData ) {
			$this->add( $this->collectionFromJSON( $collectionData ) );
		}
	}

	/**
	 * Get formatted title of the page that contains the manifest
	 *
	 * @return Title
	 */
	private function getStorageTitle() {
		$title = $this->user->getName() . '/' .UserPageCollectionsList::MANIFEST_FILE;
		return Title::makeTitleSafe( NS_USER, $title );
	}

	/**
	 * Get a basic collection object with the metadata from json data in the manifest
	 * @param array $json data to pull information from
	 *
	 * @return models\CollectionInfo
	 */
	private function collectionFromJSON( $json ) {
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
