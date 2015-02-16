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
	 * @return File|bool Get the file from this item
	 */
	public function getFile();
}

