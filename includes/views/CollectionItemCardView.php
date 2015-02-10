<?php
/**
 * CollectionItemCardView.php
 */

namespace Gather;

use \MobilePage;
use Gather\views\helpers\CSS;
use \Html;

/**
 * View for an item card in a mobile collection.
 */
class CollectionItemCardView extends View {
	protected $item;

	/**
	 * Constructor
	 * @param MobilePage $item
	 */
	public function __construct( MobilePage $item ) {
		$this->item = $item;
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
		$page = $this->item;
		$title = $page->getTitle();
		$html = Html::openElement( 'div', array( 'class' => 'collection-item' ) ) .
			Html::openElement( 'h2', array( 'class' => 'collection-item-title' ) ) .
			Html::element( 'a', array( 'href' => $title->getLocalUrl() ),
				$this->getTitle()
			).
			Html::closeElement( 'h2' ) .
			Html::openElement( 'div', array( 'class' => 'collection-item-footer' ) ) .
			Html::openElement( 'a',
				array(
					'href' => $title->getLocalUrl(),
					'class' => CSS::anchorClass( 'progressive' )
				)
			) .
			wfMessage( 'gather-read-more' )->text() .
			Html::element(
				'span',
				array( 'class' => CSS::iconClass( 'collections-read-more', 'element', 'collections-read-more-arrow' ) ),
				''
			) .
			Html::closeElement( 'a' ) .
			Html::closeElement( 'div' ) .
			Html::closeElement( 'div' );
		return $html;
	}
}
