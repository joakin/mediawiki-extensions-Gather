<?php

/**
 * models\CollectionsList.php
 */

namespace Gather\models;

use User;
use Gather\models;
use ArrayIterator;
use IteratorAggregate;
use ApiMain;
use FauxRequest;
use SpecialPage;

class CollectionsList implements IteratorAggregate, ArraySerializable, WithImage {

	/**
	 * @var CollectionInfo[] list of collection items
	 */
	protected $collections = array();
	/**
	 * @var array query string parameters
	 */
	protected $continue = array();
	/**
	 * @var bool if the list can show private collections or not
	 */
	protected $includePrivate;

	public function __construct( $user = false, $includePrivate = false ) {
		$this->user = $user;
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
	 * Return user who owns this collection.
	 * @return User
	 */
	public function getOwner() {
		return $this->user;
	}

	/**
	 * Returns if the user is the owner of the list of collections
	 * @param User $user user to check if it is the owner
	 * @return boolean
	 */
	public function isOwner( User $user ) {
		return $this->user->equals( $user );
	}

	/**
	 * Return local url for list of collections
	 * Example: /wiki/Special:Gather/by/user
	 *
	 * @param array $query string parameters for url
	 * @return string localized url for collection
	 */
	public function getUrl( $query = array() ) {
		if ( $this->getOwner() ) {
			return SpecialPage::getTitleFor( 'Gather' )
				->getSubpage( 'by' )
				->getSubpage( $this->getOwner() )
				->getLocalURL( $query );
		} else {
			return SpecialPage::getTitleFor( 'Gather' )
				->getSubpage( 'all' )
				->getSubpage( $this->listMode )
				->getLocalURL( $query );
		}
	}

	/**
	 * Return a URL that allows you to retreive the rest of the list of collections
	 * @return string|null
	 */
	public function getContinueUrl() {
		return $this->continue ? $this->getUrl( $this->continue, $this->listMode ) : false;
	}

	/**
	 * @param array $continue information to obtain further lists
	 */
	public function setContinueQueryString( $continue, $mode ) {
		$this->continue = $continue;
		$this->listMode = $mode;
	}

	/**
	 * Generate UserPageCollectionsList from api result
	 * @param User $user collection list owner (currently ignored)
	 * @param boolean [$includePrivate] if the list should show private collections or not
	 * @param string|boolean [$memberTitle] title of member to check for
	 * @param array [$continue] generate collection list from continue parameter
	 * @param string [$mode] to run
	 * @param integer [$limit] of number of collections to show
	 * @return models\CollectionsList List of collections.
	 */
	public static function newFromApi( $user = null, $includePrivate = false,
		$memberTitle = false, $continue = array(), $mode = null, $limit = 50 ) {
		$collectionsList = new CollectionsList( $user, $includePrivate );
		$query = array_merge( $continue, array(
			'action' => 'query',
			'list' => 'lists',
			'lstprop' => 'label|description|public|image|count|updated|owner',
			'lstlimit' => $limit,
			'continue' => '',
			) );
		if ( $memberTitle ) {
			$query['lsttitle'] = $memberTitle;
		}
		if ( $user ) {
			$query['lstowner'] = $user->getName();
		}
		if ( $mode ) {
			$query['lstmode'] = "all$mode";
		} else {
			$mode = '';
		}
		$api = new ApiMain( new FauxRequest( $query ) );
		$api->execute();
		$data = $api->getResult()->getResultData( null, array( 'Strip' => 'all' ) );
		if ( isset( $data['query']['lists'] ) ) {
			$lists = $data['query']['lists'];
			foreach ( $lists as $list ) {
				$public = $list['perm'] === 'public';

				if ( $public || $includePrivate ) {
					$image = isset( $list['image'] ) ? wfFindFile( $list['image'] ) : false;
					$info = new models\CollectionInfo( $list['id'], User::newFromName( $list['owner'] ),
						$list['label'], $list['description'], $public, $image );
					$info->setCount( $list['count'] );
					$info->setUpdated( $list['updated'] );
					if ( $list['perm'] === 'hidden' ) {
						$info->setHidden();
					}
					if ( $memberTitle ) {
						$info->setMember( $memberTitle, $list['title'] );
					}
					$collectionsList->add( $info );
				}
			}
		}
		if ( isset( $data['continue'] ) ) {
			$collectionsList->setContinueQueryString( $data['continue'], $mode );
		}
		return $collectionsList;
	}

	/**
	 * Check whether the item has an image
	 *
	 * @return Boolean
	 */
	public function hasImage() {
		return $this->getFile() !== null;
	}

	/**
	 * @return File Get the file from this item
	 */
	public function getFile() {
		$image = null;
		foreach ( $this->collections as $collection ) {
			if ( $collection->hasImage() ) {
				$image = $collection->getFile();
				break;
			}
		}
		return $image;
	}

}

