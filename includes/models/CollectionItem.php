<?php

/**
 * CollectionItem.php
 */

namespace Gather\models;

use \Title;

/**
 * An item of a Collection. Similar to a Page and MobilePage, but with some
 * extra information like the extract and image.
 */
class CollectionItem implements WithImage {

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
	 * @inheritdoc
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
	 * @inheritdoc
	 */
	public function getFile() {
		return $this->file;
	}

	/**
	 * Serialise to JSON
	 */
	public function toJSON() {
		return array(
			'title' => $this->title,
			'extract' => $this->extract,
		);
	}

}
