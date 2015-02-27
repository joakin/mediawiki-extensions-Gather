<?php

/**
 * CollectionsListStorage.php
 */

namespace Gather\stores;

use Gather\models;
use \User;

/**
 * Interface for a store that loads the collections of a user.
 */
interface CollectionsListStorage {

	/**
	 * Get list of collections by user
	 * @param User $user collection list owner
	 * @param boolean $includePrivate if the list should show private collections or not
	 * @return models\CollectionsList List of collections.
	 */
	public static function newFromUser( User $user, $includePrivate = false );

}

