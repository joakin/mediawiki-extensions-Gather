<?php
/**
 * CollectionsListView.php
 */

namespace Gather;

/**
 * Renders a mobile collection card list
 */
class CollectionsListView extends Gather\View {
	/**
	 * @param Gather\Collection $collection
	 */
	public function __construct( Gather\CollectionList $collectionList ) {
		$this->collectionList = $collectionList;
	}

	/**
	 * Returns the html for the items of a collection
	 *
	 * @param Gather\CollectionList
	 *
	 * @return string Html
	 */
	public static function getListItemsHtml( Gather\CollectionList $collectionList ) {
		$html = Html::openElement( 'div', array( 'class' => 'collection-cards' ) );
		foreach ( $collectionList as $item ) {
			$view = new Gather\CollectionsListItemCardView( $item );
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
		$html .= $this->getListItemsHtml( $this->collectionList );
		$html .= Html::closeElement( 'div' );
		return $html;
	}
}
