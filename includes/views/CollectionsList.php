<?php
/**
 * CollectionsList.php
 */

namespace Gather\views;

use Gather\models;
use Gather\views\helpers\Template;

/**
 * Renders a mobile collection card list
 */
class CollectionsList extends View {
	/**
	 * @param User $user that is viewing the collection
	 * @param models\CollectionsList $collectionsList
	 */
	public function __construct( $user, $collectionsList ) {
		$this->user = $user;
		$this->collectionsList = $collectionsList;
	}

	/**
	 * Returns the html for the collections in a list
	 * @param models\CollectionsList
	 * @return string Html
	 */
	public static function getListItemsHtml( $collectionsList ) {
		$html = '';
		// Show owner link on card when the collections list doesn't have an owner.
		$showOwnerLink = !$collectionsList->getOwner();
		foreach ( $collectionsList as $item ) {
			$collectionsListItemCard = new CollectionsListItemCard( $item, $showOwnerLink );
			$html .= $collectionsListItemCard->getHtml();
		}
		$url = $collectionsList->getContinueUrl();
		if ( $url ) {
			$messageKey = $collectionsList->getOwner() ? 'gather-lists-more' : 'gather-lists-more-no-owner';
			$html .= Pagination::more( $url, wfMessage( $messageKey )->text() );
		}
		return $html;
	}

	/**
	 * Return title of collection
	 *
	 * @return string title for page showing curated lists
	 */
	public function getTitle() {
		$owner = $this->collectionsList->getOwner();
		$pageTitle = $owner ? wfMessage( 'gather-lists-from-user-title', $owner ) :
			wfMessage( 'gather-lists-title' );
		return $pageTitle->text();
	}

	/**
	 * @inheritdoc
	 */
	public function getHtml( $data = array() ) {
		$cList = $this->collectionsList;
		$defaults = array(
			'mode' => $cList->getMode(),
			'items' => $this->getListItemsHtml( $cList ),
		);
		if ( $cList->getOwner() ) {
			$defaults['owner'] = $cList->getOwner()->getName();
			$defaults['isOwner'] = $cList->isOwner( $this->user ) ? true : false;
		}
		return Template::render( 'CollectionsList', array_merge( $defaults, $data ) );
	}
}
