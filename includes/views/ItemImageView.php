<?php
/**
 * ItemImageView.php
 */

namespace Gather;

use Gather\views\helpers\CSS;
use \Html;

/**
 * View for the image of an item card.
 */
class ItemImageView {
	protected $item;

	/**
	 * Constructor
	 * @param CollectionItem $item
	 */
	public function __construct( CollectionItem $item ) {
		$this->item = $item;
	}

	/**
	 * Get the view html
	 */
	public function getHtml() {
		return $this->getPageImageHtml(750, true);
	}

	/**
	 * @param integer $size the width of the thumbnail
	 * @param boolean $useBackgroundImage Whether the thumbnail should have a background image
	 * @return string
	 */
	private function getPageImageHtml( $size = 750, $useBackgroundImage = false ) {
		$imageHtml = '';
		if ( $this->item->hasImage() ) {
			$file = $this->item->getFile();
			$thumb = $file->transform( array( 'width' => $size ) );
			if ( $thumb && $thumb->getUrl() ) {
				$className = 'list-thumb ';
				$className .= $thumb->getWidth() > $thumb->getHeight()
					? 'list-thumb-y'
					: 'list-thumb-x';
				$props = array(
					'class' => $className,
				);

				$imgUrl = wfExpandUrl( $thumb->getUrl(), PROTO_CURRENT );
				if ( $useBackgroundImage ) {
					$props['style'] = 'background-image: url("' . wfExpandUrl( $imgUrl, PROTO_CURRENT ) . '")';
					$text = '';
				} else {
					$props['src'] = $imgUrl;
					$text = $this->title->getText();
				}
				$imageHtml = Html::openElement( 'a', array(
						'href' => $this->item->getTitle()->getLocalUrl(),
						'class' => CSS::anchorClass( 'progressive' )
					)
				) .
				Html::element( $useBackgroundImage ? 'div' : 'img', $props, $text ) .
				Html::closeElement( 'a' );
			}
		}
		return $imageHtml;
	}
}
