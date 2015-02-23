<?php

namespace Gather\stores;

use Gather\models;

use \User;
use \Title;

/**
 * Abstraction for json collection storage on user pages.
 * FIXME: This should live in core and power Special:EditWatchlist
 */
class UserPageCollection extends Collection {
	const FOLDER = 'GatherCollections';

	/**
	 * Initialise UserPageCollection from user page
	 *
	 * @param User $user owner of the collection
	 * @param int $id of the collection
	 */
	public function __construct( User $user, $id ) {
		$this->user = $user;
		$this->id = $id;
		$collectionData = JSONPage::get( $this->getStorageTitle() );
		if ( isset( $collectionData['id'] ) ) {
			// Only set the collection if it exists
			$this->collection = $this->collectionFromJSON( $collectionData );
		}
	}

	/**
	 * Get the url for the collection
	 *
	 * @return Title
	 */
	private function getStorageTitle() {
		$title = $this->user->getName() . '/' . UserPageCollection::FOLDER . '/' . $this->id . '.json';
		return Title::makeTitleSafe( NS_USER, $title );
	}

	/**
	 * Fill a collection object from json data
	 * @param array $json data to pull information from
	 *
	 * @return models\Collection
	 */
	private function collectionFromJSON( $json ) {
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
				$titles[] = Title::newFromText( $title );
			}
			$collection->batch( $this->getItemsFromTitles( $titles ) );
		}
		return $collection;
	}

}
