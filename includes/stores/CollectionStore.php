<?php

namespace Gather;

/**
 * Abstraction for collection storage.
 */
interface CollectionStore {
	/**
	 * Get titles of all pages in the current collection.
	 *
	 * @return array titles
	 */
	public function getTitles();

	/**
	 * Get current collection identifier
	 *
	 * @return int id
	 */
	public function getId();
}
