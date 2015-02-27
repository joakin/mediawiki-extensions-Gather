<?php

/**
 * CollectionStorage.php
 */

namespace Gather\stores;

/**
 * Interface for stores that will store/retrieve Collections
 */

interface CollectionStorage {

	/**
	 * Get Collection model with an id of a user
	 * @param User $owner of the collection
	 * @param int $id of the collection
	 * @return models\Collection
	 */
	public static function newFromUserAndId( $owner, $id );

}
