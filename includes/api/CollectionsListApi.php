<?php

/**
 * CollectionsListApi.php
 */

namespace Gather\api;

use Gather\stores;
use Gather\models;
use ApiBase;
use User;

// FIXME: ApiQueryGeneratorBase should be used here in future.
class CollectionsListApi extends ApiBase {
	/**
	 * Execute the requested api actions
	 */
	public function execute() {
		$this->getMain()->setCacheMode( 'anon-public-user-private' );
		$params = $this->extractRequestParams();
		$action = $params['gather'];

		// Get the list of collections for a user
		if ( $action === 'list' ) {
			// If an owner wasn't specified, then get the collections of the current user
			$owner = isset( $params['owner'] ) ?
				User::newFromName( $params['owner'] ) : $this->getUser();
			// If the name is invalid – it contains illegal characters then this'll return false
			if ( $owner !== false ) {
				$collections = $this->getCollectionsList( $owner );
				$res = array();
				foreach ( $collections as $collection ) {
					$res[] = $collection->toArray();
				}
				$this->addResult( $res, 'collection' );
			}
		}
	}

	/**
	 * Add a result to the response
	 * @param string $result result in json to add to the response
	 * @param string $tagName xml tagName in case it needs to be set
	 */
	private function addResult( $result, $tagName = null ) {
		$apiResult = $this->getResult();
		if ( $tagName !== null ) {
			$apiResult->setIndexedTagName( $result, $tagName );
			$apiResult->setIndexedTagName_recursive( $result, $tagName );
		}
		$apiResult->addValue( null, $this->getModuleName(), $result );
	}

	/**
	 * Get the list of collections for a user
	 * @param User $owner Owner of the collections
	 * @return models\Collection[]
	 */
	protected function getCollectionsList( $owner ) {
		$ownsCollection = $this->getUser()->getName() === $owner->getName();
		$collectionsListStore = new stores\UserPageCollectionsList( $owner, $ownsCollection );
		return $collectionsListStore->getLists();
	}

	/**
	 * Returns usage examples for this module.
	 * @see ApiBase::getExamplesMessages()
	 * @todo Fill up examples
	 */
	protected function getExamplesMessages() {
		return array(
			'action=gather&owner=john' => 'gather-apihelp-list-example-1',
		);
	}

	/**
	 * Returns the help url for this API
	 * @return string
	 * @todo Add specific section for the api to Extension:Gather in mw.org
	 */
	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Extension:Gather';
	}

	/**
	 * Get allowed API parameters
	 * @return array
	 */
	public function getAllowedParams() {
		return array(
			'gather' => array(
				ApiBase::PARAM_DFLT => 'list',
				ApiBase::PARAM_TYPE => array(
					'list'
				)
			),
			'owner' => array(
				ApiBase::PARAM_TYPE => 'user'
			)
		);
	}
}
