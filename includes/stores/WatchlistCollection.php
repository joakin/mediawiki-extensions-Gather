<?php

namespace Gather\stores;

use Gather\models;

use \User;
use \Title;
use \GenderCache;

/**
 * Abstraction for watchlist storage.
 * FIXME: This should live in core and power Special:EditWatchlist
 */
class WatchlistCollection implements Collection {
	/**
	 * @var models\Collection
	 */
	protected $collection;

	/**
	 * @inheritdoc
	 */
	public function getCollection() {
		return $this->collection;
	}

	/**
	 * @inheritdoc
	 */
	public function getId() {
		// Watchlist has hardcoded id of 0
		return 0;
	}

	/**
	 * Initialise WatchlistCollection from database
	 *
	 * @param User $user to lookup watchlist members for
	 */
	public function __construct( User $user ) {
		// Load the different data we need
		$titles = $this->loadTitles( $user );
		$extracts = ItemExtracts::loadExtracts( $titles );
		$images = ItemImages::loadImages( $titles );

		// Merge the data into models\CollectionItem
		$items = array();
		foreach ( $titles as $key=>$title ) {
			$items[] = new models\CollectionItem( $title, $images[$key], $extracts[$key] );
		}

		// Grab first image available for the collection
		$firstImage = null;
		foreach ( $images as $image ) {
			if ( $image ) {
				$firstImage = $image;
				break;
			}
		}

		// Construct the internal models\Collection
		$this->collection = new models\Collection(
			$this->getId(),
			$user,
			wfMessage( 'gather-watchlist-title' ),
			wfMessage( 'gather-watchlist-description' ),
			false,
			$firstImage
		);
		$this->collection->batch( $items );
	}

	/**
	 * Load titles of the watchlist
	 *
	 * @return Title[]
	 */
	private function loadTitles( $user ) {
		$list = array();
		$dbr = wfGetDB( DB_SLAVE );

		$res = $dbr->select(
			'watchlist',
			array( 'wl_namespace', 'wl_title'),
			array(
				'wl_user' => $user->getId(),
				'wl_namespace' => 0,
			),
			__METHOD__,
			array( 'LIMIT' => 50,)
		);

		$titles = array();
		if ( $res->numRows() > 0 ) {
			foreach ( $res as $row ) {
				$title = Title::makeTitle( $row->wl_namespace, $row->wl_title );
				$titles[] = $title;
			}
			$res->free();
		}
		GenderCache::singleton()->doTitlesArray( $titles );

		return $titles;
	}

}
