<?php
/**
 * Image.php
 */

namespace Gather\views;

use Gather\models;
use Gather\views\helpers\CSS;
use \Html;

/**
 * View for the image of an item card.
 */
class Image {
	protected $item;

	/**
	 * Constructor
	 * @param models\WithImage $item
	 */
	public function __construct( models\WithImage $item ) {
		$this->item = $item;
	}

	/**
	 * Get the view html
	 */
	public function getHtml() {
		// FIXME: magic number
		return $this->getPageImageHtml( 750, true );
	}

	/**
	 * @param integer $size the width of the thumbnail
	 * @param boolean $useBackgroundImage Whether the thumbnail should have a background image
	 * @return string
	 */
	private function getPageImageHtml( $size = 750, $useBackgroundImage = false ) {
		$imageHtml = '';
		if ( $this->item->hasImage() ) {
			$thumb = $this->item->getThumbnail( $size );
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
				$imageHtml = Html::element( $useBackgroundImage ? 'div' : 'img', $props, $text );
			}
		}
		return $imageHtml;
	}
}
