<?php
/**
 * CollectionView.php
 */

namespace Gather\views;

use \Html;
use \User;
use \SpecialPage;
use Gather\views\helpers\CSS;
use Gather\models\Collection;

/**
 * Render a mobile card.
 */
class CollectionView extends View {
	/**
	 * @var Collection
	 */
	protected $collection;

	/**
	 * @param Collection $collection
	 */
	public function __construct( Collection $collection ) {
		$this->collection = $collection;
	}

	/**
	 * Returns the rendered html for the collection header
	 * @param Collection $collection
	 *
	 * @return string Html
	 */
	private function getHeaderHtml( Collection $collection ) {
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
		return Html::openElement( 'a', array(
				'href' => SpecialPage::getTitleFor( 'UserProfile', $name )->getLocalUrl() ) ) .
			Html::element( 'span', array(
				'class' => CSS::iconClass( 'user', 'before', 'collections-owner' ) ) ) .
			$owner->getName() .
			Html::closeElement( 'a' );
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
	 * @param Collection
	 *
	 * @return string HTML
	 */
	public function getCollectionItems( Collection $collection ) {
		$html = Html::openElement( 'div', array( 'class' => 'collection-items' ) );
		foreach ( $collection as $item ) {
			if ( $item->getTitle()->getNamespace() === NS_MAIN ) {
				$view = new CollectionItemCardView( $item );
				$html .= $view->getHtml();
			}
		}
		// FIXME: Pagination(??) Note the stores\WatchlistCollection
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

		if ( $collection->getCount() > 0 ) {
			$html .= $this->getCollectionItems( $collection );
		} else {
			$html .= $this->getEmptyCollectionMessage();
		}

		$html .= Html::closeElement( 'div' );

		return $html;
	}
}
