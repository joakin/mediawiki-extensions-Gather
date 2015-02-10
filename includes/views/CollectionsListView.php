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
	 * @param Collection $collection
	 */
	public function __construct( CollectionsList $collectionsList ) {
		$this->collectionsList = $collectionsList;
	}

	/**
	 * Returns the html for the items of a collection
	 *
	 * @param CollectionsList
	 *
	 * @return string Html
	 */
	public static function getListItemsHtml( CollectionsList $collectionsList ) {
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
