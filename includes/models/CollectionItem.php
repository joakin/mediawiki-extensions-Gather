<?php

/**
 * CollectionItem.php
 */

namespace Gather;

use \Title;

/**
 * An item of a Collection. Similar to a Page and MobilePage, but with some
 * extra information like the extract and image.
 */
class CollectionItem {

	/**
	 * @var Title: Title for page
	 */
	private $title;

	/**
	 * @var File Associated page image file (see PageImages extension)
	 */
	private $file;

	/**
	 * @var string Page extract
	 */
	private $extract;

	/**
	 * Constructor
	 * @param Title $title
	 * @param File|bool $file
	 * @param string|bool $extract
	 */
	public function __construct( Title $title, $file = false, $extract = false ) {
		$this->title = $title;
		$this->file = $file;
		$this->extract = $extract;
	}

	/**
	 * Check whether the item has an image
	 *
	 * @return Boolean
	 */
	public function hasImage() {
		return $this->file ? true : false;
	}

	/**
	 * Check whether the item has an extract
	 *
	 * @return Boolean
	 */
	public function hasExtract() {
		return $this->extract ? true : false;
	}

	/**
 	 * @return Title title of the item
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
 	 * @return string extract of the item
	 */
	public function getExtract() {
		return $this->extract;
	}

	/**
	 * @return File|bool Get the file from this item
	 */
	public function getFile() {
		return $this->file;
	}
}
