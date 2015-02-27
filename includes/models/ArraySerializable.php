<?php

/**
 * ArraySerializable.php
 */

namespace Gather\models;

interface ArraySerializable {
	/**
	 * Serialise to PHP array structure.
	 */
	public function toArray();
}

