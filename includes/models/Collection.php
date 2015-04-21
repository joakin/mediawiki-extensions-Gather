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
	 * @param array $continue information to obtain further items
	 */
	public function setContinueQueryString( $continue ) {
		$this->continue = $continue;
	}

	/**
	 * Generate a Collection from api result
	 * @param Integer $id the id of the collection
	 * @param User [$user] optional collection list owner (if present will be
	 * included in the query and validated)
	 * @param array [$continue] optional parameters to append to the query.
	 * @return models\Collections a collection
	 */
	public static function newFromApi( $id, User $user = null, $continue = array() ) {
		$limit = 50;
		$collection = null;
		$params = array_merge( $continue, array(
			'action' => 'query',
			'list' => 'lists',
			'lstids' => $id,
			'lstprop' => 'label|description|public|image|owner',
			'prop' => 'pageimages|extracts',
			'generator' => 'listpages',
			'glspid' => $id,
			'explaintext' => true,
			'exintro' => true,
			'exchars' => self::EXTRACTS_CHAR_LIMIT,
			'glsplimit' => $limit,
			'exlimit' => $limit,
			'pilimit' => $limit,
			'continue' => '',
		) );
		// If user is present, include it in the request. Api will return not found
		// if the specified owner doesn't match the actual collection owner.
		if ( $user ) {
			$params['lstowner'] = $user->getName();
		}
		$api = new ApiMain( new FauxRequest( $params ) );

		try {
			$api->execute();
			$data = $api->getResult()->getResultData( null, array( 'Strip' => 'all' ) );
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
					$collection->add( new CollectionItem( $title, $pi, $extract ) );
				}
			}
			if ( isset( $data['continue'] ) ) {
				$collection->setContinueQueryString( $data['continue'] );
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
			$info->getTitle(), $info->getDescription(), $info->isPublic(), $info->getFile());
	}
}
