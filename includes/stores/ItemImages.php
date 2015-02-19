<?php

namespace Gather\stores;

use \PageImages;
use Title;

/**
 * Loading page images for titles
 */
class ItemImages {

	/**
	 * Load images for a collection of titles
	 * @param Title[] $titles
	 *
	 * @return string[]
	 */
	public static function loadImages( array $titles ) {
		$images = array();
		$titleIds = array();
		// get article ids for page images query
		foreach ( $titles as $title ) {
			$titleIds[] = $title->getArticleId();
		}
		// query to get page images for all pages
		// FIXME: Should probably be in PageImages extension
		$dbr = wfGetDB( DB_SLAVE );
		$result = $dbr->select( 'page_props',
			array( 'pp_value', 'pp_page' ),
			array( 'pp_page' => $titleIds, 'pp_propname' => PageImages::PROP_NAME ),
			__METHOD__
		);
		if ( $result ) {
			// build results array
			foreach ( $result as $row ) {
				$images[$row->pp_page] = wfFindFile( $row->pp_value );
			}
		}
		return $images;
	}

}

