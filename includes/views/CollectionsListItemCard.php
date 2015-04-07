<?php
/**
 * CollectionsListItemCard.php
 */

namespace Gather\views;

use Gather\models;
use Html;

/**
 * View for an item card in a mobile collection.
 */
class CollectionsListItemCard extends View {

	/**
	 * @param models\CollectionInfo $collection
	 */
	public function __construct( models\CollectionInfo $collection ) {
		$this->collection = $collection;
		$this->image = new Image( $collection );
	}

	/**
	 * @var models\Collection Model to be rendered on this view
	 */
	protected $collection;

	/**
	 * @var Image view for the collection image
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
	 * Gets the privacy message
	 */
	public function getPrivacyMsg() {
		$status = $this->collection->isPublic() ? 'gather-public' : 'gather-private';
		return wfMessage( $status )->text();
	}

	/**
	 * @inheritdoc
	 */
	public function getHtml() {
		$articleCountMsg = wfMessage(
			'gather-article-count',
			$this->collection->getCount()
		)->text();
		$followingMsg = $this->getPrivacyMsg();
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
			Html::element( 'span', array(), '•' ) .
			Html::element( 'span', array( 'class' => 'collectoin-card-article-count' ), $articleCountMsg ) .
			Html::closeElement( 'div' ) .
			Html::closeElement( 'div' );
		return $html;
	}
}
