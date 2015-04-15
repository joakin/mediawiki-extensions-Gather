<?php
/**
 * Image.php
 */

namespace Gather\views;

use Gather\models;
use Gather\views\helpers\CSS;
use Html;

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
	public function getHtml( $data = array() ) {
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
			$thumb = models\Image::getThumbnail( $this->item->getFile(), $size );
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
