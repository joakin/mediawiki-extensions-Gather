<?php

/**
 * WithImage.php
 */

namespace Gather\models;

/**
 * Interface for items that have an image.
 *
 * Exposes the necessary methods for handling the file.
 */
interface WithImage {
	/**
	 * Check whether the item has an image
	 *
	 * @return Boolean
	 */
	public function hasImage();

	/**
	 * @return File Get the file from this item
	 */
	public function getFile();

	/**
	 * @param integer $size of thumbnail
	 * @return File Get the thumbnail from this item
	 */
	public function getThumbnail( $size );
}

