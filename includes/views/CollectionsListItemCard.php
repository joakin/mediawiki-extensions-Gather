<?php
/**
 * CollectionsListItemCard.php
 */

namespace Gather\views;

use Gather\models;
use \Html;

/**
 * View for an item card in a mobile collection.
 */
class CollectionsListItemCard extends View {

	/**
	 * @param models\Collection $collection
	 */
	public function __construct( models\Collection $collection ) {
		$this->collection = $collection;
		$this->image = new ItemImage( $collection );
	}

	/**
	 * @var models\Collection Model to be rendered on this view
	 */
	protected $collection;

	/**
	 * @var ItemImage view for the collection image
	 */
	protected $image;

	/**
	 * Return title of collection
	 *
	 * @returns string collection title
	 */
	public function getTitle() {
		return $this->collection->getTitle();
	}

	/**
	 * @inheritdoc
	 */
	public function getHtml() {
		$articleCountMsg = wfMessage(
			'gather-article-count',
			$this->collection->getCount()
		)->text();
		// FIXME: should consider privacy in collection
		$followingMsg = wfMessage( 'gather-private' )->text();
		$collectionUrl = $this->collection->getUrl();
		$hasImage = $this->collection->hasImage();

		$html = Html::openElement( 'div', array(
			'class' => 'collection-card ' . ( $hasImage ? '' : 'without-image' )
			) ) .
			Html::openElement( 'a', array(
				'href' => $collectionUrl, 'class' => 'collection-card-image',
			) ) .
			$this->image->getHtml() .
			Html::closeElement( 'a' ) .
			Html::openElement( 'div', array( 'class' => 'collection-card-overlay' ) ) .
			Html::openElement( 'div', array( 'class' => 'collection-card-title' ) ) .
			Html::element( 'a', array( 'href' => $collectionUrl ), $this->getTitle() ) .
			Html::closeElement( 'div' ) .
			Html::element( 'span', array( 'class' => 'collection-card-following' ), $followingMsg ) .
			Html::element( 'span', array( 'class' => 'collection-card-following' ), '•' ) .
			Html::element( 'span', array( 'class' => 'collectoin-card-article-count' ), $articleCountMsg ) .
			Html::closeElement( 'div' ) .
			Html::closeElement( 'div' );
		return $html;
	}
}
