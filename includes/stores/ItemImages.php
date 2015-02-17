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
		foreach ( $titles as $title ) {
			$images[] = PageImages::getPageImage( $title );
		}
		return $images;
	}

}

