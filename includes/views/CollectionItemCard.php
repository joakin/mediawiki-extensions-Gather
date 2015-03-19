<?php
/**
 * CollectionItemCard.php
 */

namespace Gather\views;

use Gather\models;
use Gather\views\helpers\CSS;
use \Html;
use \Linker;

/**
 * View for an item card in a mobile collection.
 */
class CollectionItemCard extends View {
	/**
	 * @var models\CollectionItem Item that is going to be shown in this view
	 */
	protected $item;

	/**
	 * @var Image view for the item image
	 */
	protected $image;

	/**
	 * Constructor
	 * @param models\CollectionItem $item
	 */
	public function __construct( models\CollectionItem $item ) {
		$this->item = $item;
		$this->image = new Image( $item );
	}

	/**
	 * Returns title of collection page
	 * @returns string collection page title
	 */
	public function getTitle() {
		return $this->item->getTitle()->getText();
	}

	/**
	 * @inheritdoc
	 */
	protected function getHtml() {
		$item = $this->item;
		$title = $item->getTitle();
		$html = Html::openElement( 'div', array( 'class' => 'collection-item' ) ) .
			Html::openElement( 'a', array( 'href' => $title->getLocalUrl() ) ) .
			$this->image->getHtml() .
			Html::closeElement( 'a' ) .
			Html::openElement( 'h2', array( 'class' => 'collection-item-title' ) ) .
			Linker::link( $title ) .
			Html::closeElement( 'h2' );
		// Handle excerpt for titles with an extract or unknown pages
		if ( $item->hasExtract() || !$title->isKnown() ) {
			if ( $item->hasExtract() ) {
				$itemExcerpt = $item->getExtract();
			} elseif ( !$title->isKnown() ) {
				$itemExcerpt = wfMessage( 'gather-page-not-found' )->escaped();
			}
			$html .= Html::element(
				'p', array( 'class' => 'collection-item-excerpt' ), $itemExcerpt
			);
		}
		$html .= Html::openElement( 'div', array( 'class' => 'collection-item-footer' ) )
			. Html::openElement( 'a',
				array(
					'href' => $title->getLocalUrl(),
					'class' => CSS::anchorClass( 'progressive' )
				)
			)
			. wfMessage( 'gather-read-more' )->escaped()
			. Html::element(
				'span',
				array( 'class' => CSS::iconClass(
					'collections-read-more', 'element', 'collections-read-more-arrow'
				) ),
				''
			)
			. Html::closeElement( 'a' )
			. Html::closeElement( 'div' )
			. Html::closeElement( 'div' );

		return $html;
	}
}
