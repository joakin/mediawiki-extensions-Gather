<?php

namespace Gather\stores;

/**
 * Abstraction for collection storage.
 */
interface CollectionStore {
	/**
	 * Get CollectionItem of all pages in the current collection.
	 *
	 * @return CollectionItem[] titles
	 */
	public function getItems();

	/**
	 * Get current collection identifier
	 *
	 * @return int id
	 */
	public function getId();
}
