<?php

namespace Gather\stores;

/**
 * Abstraction for collection storage.
 */
interface Collection {
	/**
	 * Get Collection model of the current collection.
	 *
	 * @return Collection collection
	 */
	public function getCollection();
}
