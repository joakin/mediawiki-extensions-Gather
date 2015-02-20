<?php

namespace Gather\stores;

use \ApiQuery;
use \ApiMain;
use \FauxRequest;
use Title;

/**
 * Loading extracts for titles
 */
class ItemExtracts {
	const CHAR_LIMIT = 140;

	/**
	 * Load extracts for a collection of titles
	 * @param Title[] $titles
	 *
	 * @return string[]
	 */
	public static function loadExtracts( array $titles ) {
		$api = new ApiMain( new FauxRequest( array(
			'action' => 'query',
			'prop' => 'extracts',
			'explaintext' => true,
			'exintro' => true,
			'exchars' => ItemExtracts::CHAR_LIMIT,
			'titles' => implode( '|', $titles ),
			'exlimit' => count( $titles ),
		) ) );
		$api->execute();
		$data = $api->getResultData();
		$pages = $data['query']['pages'];

		$extracts = array();
		foreach ( $pages as $page ) {
			if ( isset( $page['extract']['*'] ) ) {
				$extracts[] = $page['extract']['*'];
			} else {
				$extracts[] = null;
			}
		}
		return $extracts;
	}

}

