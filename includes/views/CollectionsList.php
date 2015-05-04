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
	 * @param models\CollectionsList $collectionsList
	 */
	public function __construct( $collectionsList ) {
		$this->collectionsList = $collectionsList;
	}

	/**
	 * Returns the html for the collections in a list
	 * @param models\CollectionsList
	 * @return string Html
	 */
	public static function getListItemsHtml( $collectionsList ) {
		$html = '';
		foreach ( $collectionsList as $item ) {
			$collectionsListItemCard = new CollectionsListItemCard( $item );
			$html .= $collectionsListItemCard->getHtml();
		}
		$url = $collectionsList->getContinueUrl();
		if ( $url ) {
			$html .= Pagination::more( $url, wfMessage( 'gather-lists-more' )->text() );
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
		$defaults = array(
			'items' => $this->getListItemsHtml( $this->collectionsList ),
			'owner' => $this->collectionsList->getOwner()->getName(),
		);
		return Template::render( 'CollectionsList', array_merge( $defaults, $data ) );
	}
}
