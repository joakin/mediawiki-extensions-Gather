<?php
/**
 * CollectionsListView.php
 */

namespace Gather;

use \Html;

/**
 * Renders a mobile collection card list
 */
class CollectionsListView extends View {
	/**
	 * @param Collection[] $collectionsList
	 */
	public function __construct( $collectionsList ) {
		$this->collectionsList = $collectionsList;
	}

	/**
	 * Returns the html for the collections in a list
	 *
	 * @param Collection[]
	 *
	 * @return string Html
	 */
	public static function getListItemsHtml( $collectionsList ) {
		$html = Html::openElement( 'div', array( 'class' => 'collection-cards' ) );
		foreach ( $collectionsList as $item ) {
			$view = new CollectionsListItemCardView( $item );
			$html .= $view->getHtml();
		}
		// FIXME: Pagination
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
		$html = Html::openElement( 'div', array( 'class' => 'collection content' ) );
		// Get items
		$html .= $this->getListItemsHtml( $this->collectionsList );
		$html .= Html::closeElement( 'div' );
		return $html;
	}
}
