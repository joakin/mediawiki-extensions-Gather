<?php
/**
 * CollectionItemCard.php
 */

namespace Gather\views;

use Gather\models;
use Gather\views\helpers\CSS;
use Html;
use Linker;
use Gather\views\helpers\Template;

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

		$data = array(
			'dir' => $dir,
			'page' => array(
				'url' => $pageUrl,
				'displayTitle' => $title->getPrefixedText(),
			),
			'msgMissing' => wfMessage( 'gather-page-not-found' )->escaped(),
			'isMissing' => $isMissing,
			'progressiveAnchorClass' => CSS::anchorClass( 'progressive' ),
			'iconClass' => CSS::iconClass(
				'collections-read-more', 'element', 'collections-read-more-arrow'
			),
		);

		// Handle excerpt for titles with an extract or unknown pages
		if ( $item->hasExtract() ) {
			$data['extract'] = $item->getExtract();
		}
		if ( $img ) {
			$data['cardImage'] = $img;
		}
		return Template::render( 'CollectionItemCard', $data );
	}
}
