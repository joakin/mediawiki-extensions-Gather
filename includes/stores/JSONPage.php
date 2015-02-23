<?php

/**
 * JSONPage.php
 */

namespace Gather\stores;

use \WikiPage;
use \FormatJson;

/**
 * Store to retrieve a page content returned as json
 */
class JSONPage {
	/**
	 * Gets a JSON page
	 * @param Title $title of the page to retrieve
	 * @return array $json read
	 */
	public static function get( $title ) {
		$page = WikiPage::factory( $title );
		$data = array();
		if ( $page->exists() ) {
			$content = $page->getContent();
			$type = $page->getContentModel();
			if ( $type === CONTENT_MODEL_JSON ) {
				$status = $content->getData();
				if ( $status->isOK() ) {
					$data = wfObjectToArray( $status->getValue() );
				}
			}
		}
		return $data;
	}
}
