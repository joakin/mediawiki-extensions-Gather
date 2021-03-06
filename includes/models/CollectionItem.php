<?php

/**
 * CollectionItem.php
 */

namespace Gather\models;

use Title;

/**
 * An item of a Collection. Similar to a Page and MobilePage, but with some
 * extra information like the extract and image.
 */
class CollectionItem implements WithImage, ArraySerializable {

	/**
	 * @var Title: Title for page
	 */
	private $title;

	/**
	 * @var File|null Associated page image file (see PageImages extension)
	 */
	private $file;

	/**
	 * @var string|null Page extract
	 */
	private $extract;

	/**
	 * @param Title $title
	 * @param File $file
	 * @param string $extract
	 */
	public function __construct( Title $title, $file, $extract ) {
		$this->title = $title;
		$this->file = $file;
		$this->extract = $extract;
		$this->missing = false;
	}

	/**
	 * Cheap method for determining whether the page is in the collection or not.
	 * @return Boolean
	 */
	public function isMissing() {
		return $this->missing;
	}

	/**
	 * @param Boolean $missing whether the associated title exists.
	 */
	public function setMissing( $missing ) {
		$this->missing = $missing;
	}

	/**
	 * @inheritdoc
	 */
	public function hasImage() {
		return (bool)$this->file;
	}

	/**
	 * Check whether the item has an extract
	 *
	 * @return Boolean
	 */
	public function hasExtract() {
		return $this->extract !== null && $this->extract !== '';
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

	/** @inheritdoc */
	public function toArray() {
		return $this->title->getText();
	}

}
