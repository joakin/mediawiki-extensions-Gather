<?php

namespace Gather;

use \PageImages;

/**
 * Loading page images for titles
 */
class ItemImagesStore {

	/**
	 * Load images for a collection of titles
	 * @param Title[] $titles
	 *
	 * @return string[]
	 */
	public static function loadImages( $titles ) {
		$images = array();
		foreach ( $titles as $title ) {
			$images[] = PageImages::getPageImage( $title );
		}
		return $images;
	}

}

