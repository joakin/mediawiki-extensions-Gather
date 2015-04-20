<?php
/**
 * CollectionItemCard.php
 */

namespace Gather\views;

use Gather\models;
use Gather\views\helpers\CSS;
use Html;
use Linker;

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
	protected function getHtml( $data = array() ) {
		$dir = isset( $data['langdir'] ) ? $data['langdir'] : 'ltr';
		$item = $this->item;
		$title = $item->getTitle();
		$img = $this->image->getHtml();
		$pageUrl = $title->getLocalUrl();
		$isMissing = $item->isMissing();

		if ( $img ) {
			$img = Html::openElement( 'a', array( 'href' => $title->getLocalUrl() ) ) .
				$img .
				Html::closeElement( 'a' );
		}
		$html = Html::openElement( 'div', array( 'class' => 'collection-card' ) ) .
			$img .
			Html::openElement( 'h2', array( 'class' => 'collection-card-title', 'dir' => $dir ) ) .
			Html::element( 'a', array( 'href' => $pageUrl, 'class' => $isMissing ? 'new' : '' ),
				$title->getPrefixedText() ) .
			Html::closeElement( 'h2' );
		// Handle excerpt for titles with an extract or unknown pages
		if ( $item->hasExtract() || $isMissing ) {
			if ( $item->hasExtract() ) {
				$itemExcerpt = $item->getExtract();
			} elseif ( $isMissing ) {
				$itemExcerpt = wfMessage( 'gather-page-not-found' )->escaped();
			}
			$html .= Html::element(
				'p', array( 'class' => 'collection-card-excerpt', 'dir' => $dir ), $itemExcerpt
			);
		}

		if ( !$isMissing ) {
			$html .= Html::openElement( 'div', array( 'class' => 'collection-card-footer' ) )
				. Html::openElement( 'a',
					array(
						'href' => $pageUrl,
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
				. Html::closeElement( 'div' );
		}
		$html .= Html::closeElement( 'div' );

		return $html;
	}
}
