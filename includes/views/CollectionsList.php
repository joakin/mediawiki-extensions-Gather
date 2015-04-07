<?php
/**
 * CollectionsList.php
 */

namespace Gather\views;

use Gather\models;
use Html;

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
		$html = Html::openElement( 'div', array( 'class' => 'collection-cards' ) );
		foreach ( $collectionsList as $item ) {
			$view = new CollectionsListItemCard( $item );
			$html .= $view->getHtml();
		}
		$url = $collectionsList->getContinueUrl();
		if ( $url ) {
			$html .= Pagination::more( $url, wfMessage( 'gather-lists-more' )->text() );
		}
		$html .= Html::closeElement( 'div' );
		return $html;
	}

	/**
	 * Return title of collection
	 *
	 * @return string title for page showing curated lists
	 */
	public function getTitle() {
		return wfMessage( 'gather-lists-title' )->text();
	}

	/**
	 * @inheritdoc
	 */
	public function getHtml() {
		$html = Html::openElement(
			'div',
			array( 'class' => 'collections-list content view-border-box' )
		);
		// Get items
		$html .= $this->getListItemsHtml( $this->collectionsList );
		$html .= Html::closeElement( 'div' );
		return $html;
	}
}
