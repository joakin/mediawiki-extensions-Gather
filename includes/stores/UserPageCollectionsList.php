<?php

/**
 * UserPageCollectionsList.php
 */

namespace Gather\stores;

use Gather\models;
use \User;
use \Title;
use \WikiPage;
use \FormatJson;

/**
 * Stores and retrieves collection lists from user pages
 */
class UserPageCollectionsList extends CollectionsList {
	const MANIFEST_FILE = 'GatherCollections.json';

	/**
	 * @inherit
	 */
	public function loadCollections() {
		$collectionsData = $this->getManifest();
		foreach ( $collectionsData as $collectionData ) {
			$this->add( $this->collectionFromJSON( $collectionData ) );
		}
	}

	/**
	 * Gets manifest json file
	 */
	private function getManifest() {
		$page = WikiPage::factory( self::getStorageTitle() );
		if ( $page->exists() ) {
			$content = $page->getContent();
			$data = FormatJson::decode( $content->getNativeData(), true );
		} else {
			$data = array();
		}
		return $data;
	}

	/**
	 * Get formatted title of the page that contains the manifest
	 */
	private function getStorageTitle() {
		$title = $this->user->getName() . '/' .UserPageCollectionsList::MANIFEST_FILE;
		return Title::makeTitleSafe( NS_USER, $title );
	}

	/**
	 * Get a models\CollectionInfo from json data in the manifest
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
