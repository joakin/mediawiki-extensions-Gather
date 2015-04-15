<?php

/**
 * Image.php
 */

namespace Gather\models;

/**
 * Image class for image manipulation methods.
 */
class Image {

	const SHARING_THUMBNAIL_WIDTH = 360;

	/**
	 * @param int $size
	 * @return bool|\MediaTransformOutput
	 */
	public static function getThumbnail( $image, $size = self::SHARING_THUMBNAIL_WIDTH ) {
		if ( $image !== null ) {
			$thumb = $image->transform( array( 'width' => $size ) );
			if ( $thumb && $thumb->getUrl() ) {
				return $thumb;
			}
		}
		return false;
	}

}
