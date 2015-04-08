<?php

/**
 * CollectionInfo.php
 */

namespace Gather\models;

/**
 * The info of collection of items.
 *
 * Extends the base class with a count you can set.
 */
class CollectionFeedItem {
	/**
	 * @param \Title $title
	 * @param \User $user
	 * @param string $editSummary
	 * @param \MWTimestamp $ts
	 * @param integer $revisionId
	 * @param bool $isMinor
	 * @param integer $bytes
	 */
	public function __construct( $title, $user, $editSummary, $ts, $revisionId,
		$isMinor = false, $bytes = null ) {
		$this->title = $title;
		$this->user = $user;
		$this->username = $this->getUser()->getName();
		$this->editSummary = $editSummary;
		$this->timestamp = $ts;
		$this->revisionId = $revisionId;
		$this->isMinor = $isMinor;
		$this->bytes = $bytes;
	}

	public function getChangeUrl() {
		$args = array(
			'diff' => $this->revisionId,
		);
		return $this->getTitle()->getLocalUrl( $args );
	}

	public function getTimestamp() {
		return $this->timestamp;
	}

	public function getEditSummary() {
		return $this->editSummary;
	}

	public function getUsername() {
		return $this->username;
	}

	/**
	 * Override the username. This is useful when dealing with anonymous users.
	 * @param string $string
	 */
	public function setUsername( $string ) {
		$this->username = $string;
	}

	public function getTitle() {
		return $this->title;
	}

	public function isMinor() {
		return $this->isMinor;
	}

	public function getBytes() {
		return $this->bytes;
	}

	public function getUser() {
		return $this->user;
	}
}
