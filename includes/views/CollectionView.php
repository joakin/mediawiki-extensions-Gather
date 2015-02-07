<?php
/**
 * CollectionView.php
 */

namespace Gather;

/**
 * Render a mobile card.
 */
class CollectionView extends Gather\View {
	/**
	 * @var Gather\Collection
	 */
	protected $collection;

	/**
	 * @param Gather\Collection $collection
	 */
	public function __construct( Gather\Collection $collection ) {
		$this->collection = $collection;
	}

	/**
	 * Returns the rendered html for the collection header
	 * @param Gather\Collection $collection
	 *
	 * @return string Html
	 */
	private function getHeaderHtml( Gather\Collection $collection ) {
		$collection = $this->collection;
		$description = $collection->getDescription();
		$owner = $collection->getOwner();

		$html = Html::openElement( 'div', array( 'class' => 'collection-header' ) ) .
			Html::element( 'div', array( 'class' => 'collection-description' ), $description ) .
			$this->getOwnerHtml( $owner ) .
			Html::closeElement( 'div' );

		return $html;
	}

	/**
	 * Returns the html for showing the owner on the collection header
	 *
	 * @param User $owner Owner of the collection
	 * @return string Html
	 */
	private function getOwnerHtml( $owner ) {
		$name = $owner->getName();
		$attrs = array(
			'class' => 'collection-owner mw-ui-icon mw-ui-icon-before mw-ui-icon-user',
			'href' => SpecialPage::getTitleFor( 'UserProfile', $name )->getLocalUrl(),
		);
		return Html::element( 'span', $attrs, $name );
	}

	/**
	 * Returns the html for an empty collection
	 *
	 * @return string HTML
	 */
	private function getEmptyCollectionMessage() {
		// FIXME: i18n this messagesinclude 'CollectionView.php';
		return Html::openElement( 'div', array( 'class' => 'collection-empty' ) ) .
			Html::element( 'h3', array(), wfMessage( 'gather-empty' ) ) .
			Html::element( 'div', array(),
				wfMessage( 'gather-empty-footer' ) ) .
			Html::closeElement( 'div' );
	}

	/**
	 * Return title of collection
	 *
	 * @return string collection title
	 */
	public function getTitle() {
		return $this->collection->getTitle();
	}

	/**
	 * Returns the html for the items of a collection
	 *
	 * @param Gather\Collection
	 *
	 * @return string HTML
	 */
	public function getCollectionItems( Gather\Collection $collection ) {
		$html = Html::openElement( 'div', array( 'class' => 'collection-items' ) );
		foreach ( $collection as $item ) {
			if ( $item->getTitle()->getNamespace() === NS_MAIN ) {
				$view = new CollectionItemCardView( $item );
				$html .= $view->getHtml();
			}
		}
		// FIXME: Pagination(??) Note the WatchlistCollectionStore
		// limits the size of the collection to 50.
		// Pagination may or may not be needed.
		$html .= Html::closeElement( 'div' );
		return $html;
	}

	/**
	 * @inheritdoc
	 */
	protected function getHtml() {
		$collection = $this->collection;

		$html = Html::openElement( 'div', array( 'class' => 'collection content' ) ) .
			$this->getHeaderHtml( $collection );

		if ( count( $collection->getPages() ) > 0 ) {
			$html .= $this->getCollectionItems( $collection );
		} else {
			$html .= $this->getEmptyCollectionMessage();
		}

		$html .= Html::closeElement( 'div' );

		return $html;
	}
}
