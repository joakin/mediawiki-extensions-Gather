<?php

/**
 * Collection.php
 */

namespace Gather\models;

use IteratorAggregate;
use ArrayIterator;
use User;
use ApiMain;
use FauxRequest;
use Title;
use Exception;
use SpecialPage;

/**
 * A collection with a list of items, which are represented by the CollectionItem class.
 */
class Collection extends CollectionBase implements IteratorAggregate {
	const EXTRACTS_CHAR_LIMIT = 140;

	/**
	 * Stores api parameters that were used to generate the collection
	 *
	 * @var array
	 */
	protected $apiParams = array();
	/**
	 * Internal data used for creating url of collections which span multiple urls.
	 *
	 * @var array
	 */
	protected $continue = array();

	/**
	 * The internal collection of items.
	 *
	 * @var CollectionItem[]
	 */
	protected $items = array();

	/**
	 * Image that could be used to illustrate collection.
	 *
	 * @var string
	 */
	protected $imageSuggestion = '';

	/**
	 * Obtain a suggested image from the created collection;
	 *
	 * @return string a suggested illustration based on members of collection.
	 */
	public function getSuggestedImage() {
		return $this->imageSuggestion;
	}

	/**
	 * Adds a item to the collection.
	 *
	 * @param CollectionItem $item
	 */
	public function add( CollectionItem $item ) {
		if ( $item->hasImage() && !$this->imageSuggestion ) {
			$this->imageSuggestion =  $item->getFile()->getTitle()->getText();
		}
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
		foreach ( $this as $item ) {
			$data['items'][] = $item->toArray();
		}
		return $data;
	}

	/**
	 * Return a URL that allows you to retreive the rest of the items of the
	 * collection
	 * @return string|null
	 */
	public function getContinueUrl() {
		return $this->continue ? $this->getUrl( $this->continue ) : false;
	}

	/**
	 * Return a URL that allows you to retreive the rest of the items of the
	 * collection
	 * @return string|null
	 */
	public function getContinueQuery() {
		$allParams = array_merge( $this->apiParams, $this->continue );
		return wfArrayToCgi( $allParams );
	}

	/**
	 * @param array $continue information to obtain further items
	 */
	public function setContinue( $continue, $params = array() ) {
		$this->apiParams = $params;
		$this->continue = $continue;
	}

	public static function newFromApi( $c, $params, $limit = 10, $continue = array() ) {
		$c = self::newFromApiParams( array_merge( array_merge( $params, $continue ),
			self::getDefaultQueryParams( $limit ) ), $c );
		// Continuing a random collection is just a case of having any arbitary api continuation query.
		$c->setContinue( $continue, $params );
		return $c;
	}

	/**
	 * Generate a default set of query parameters that apply to all API requests for
	 * generating collections.
	 * @param Integer [$limit] maximum number of items in the collection
	 */
	private static function getDefaultQueryParams( $limit = 50 ) {
		return array(
			'action' => 'query',
			'prop' => 'pageimages|extracts',
			'explaintext' => true,
			'exintro' => true,
			'exchars' => self::EXTRACTS_CHAR_LIMIT,
			'exlimit' => $limit,
			'pilimit' => $limit,
			'continue' => '',
		);
	}

	/**
	 * Generate a Collection from api result
	 * @param Integer $id the id of the collection
	 * @param User [$user] optional collection list owner (if present will be
	 * included in the query and validated)
	 * @param array [$continue] optional parameters to append to the query.
	 * @return models\Collections a collection
	 */
	public static function newFromListsApi( $id, User $user = null, $continue = array() ) {
		$limit = 50;
		$collection = null;
		$params = array_merge( $continue, array(
			'list' => 'lists',
			'lstids' => $id,
			'lstprop' => 'label|description|public|image|owner',
			'generator' => 'listpages',
			'glspsort' => 'namespace',
			'glspid' => $id,
			'glsplimit' => $limit,
		), self::getDefaultQueryParams( $limit ) );
		// If user is present, include it in the request. Api will return not found
		// if the specified owner doesn't match the actual collection owner.
		if ( $user ) {
			$params['lstowner'] = $user->getName();
		}
		return self::newFromApiParams( $params );
	}

	/**
	 * Generate a Collection from api with provided query parameters
	 * @param Array $params to pass to api.
	 * @param models\Collections [$collection] an existing collection to add to
	 * @return models\Collections a collection
	 */
	private static function newFromApiParams( $params, $collection = null ) {
		$api = new ApiMain( new FauxRequest( $params ) );
		$id = isset( $params['lstids'] ) ? $params['lstids'] : -1;
		try {
			$api->execute();
			$data = $api->getResult()->getResultData( null, array( 'Strip' => 'all' ) );
			// When present is response we override optional parameter
			if ( isset( $data['query']['lists'] ) ) {
				$lists = $data['query']['lists'];
				if ( count( $lists ) === 1 ) {
					$list = $lists[0];
					$image = $list['image'] ? wfFindFile( $list['image'] ) : null;
					$owner = User::newFromName( $list['owner'] );
					$collection = new Collection( $id, $owner, $list['label'], $list['description'],
						$list['perm'] === 'public', $image );
					if ( $list['perm'] === 'hidden' ) {
						$collection->setHidden();
					}
				}
			}

			if ( $collection && isset( $data['query']['pages'] ) ) {
				$pages = $data['query']['pages'];
				foreach ( $pages as $page ) {
					$title = Title::newFromText( $page['title'], $page['ns'] );
					$pi = false;
					if ( isset( $page['pageimage'] ) ) {
						$pi = wfFindFile( $page['pageimage'] );
					}
					$extract = '';
					// See https://phabricator.wikimedia.org/T92673
					if ( isset( $page['extract'] ) ) {
						$extract = $page['extract'];
						if ( isset( $extract['*'] ) ) {
							$extract = $extract['*'];
						}
					}
					$item = new CollectionItem( $title, $pi, $extract );
					if ( isset( $page['missing'] ) ) {
						$item->setMissing( true );
					}
					$collection->add( $item );
				}
			}

			if ( isset( $data['continue'] ) ) {
				$collection->setContinue( $data['continue'], $params );
			}
			if ( isset( $data['query-continue'] ) ) {
				$collection->setContinue( $data['query-continue'], $params );
			}
		} catch ( Exception $e ) {
			// just return collection
		}
		return $collection;
	}

	/**
	 * @param CollectionInfo $info
	 * @return models\Collection
	 */
	public static function newFromCollectionInfo( $info ) {
		return new Collection( $info->getId(), $info->getOwner(),
			$info->getTitle(), $info->getDescription(), $info->isPublic(), $info->getFile() );
	}
}
