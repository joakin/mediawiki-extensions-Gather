<?php
/**
 * Image.php
 */

namespace Gather\views;

use Gather\models;
use Gather\views\helpers\CSS;
use Gather\views\helpers\Template;
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
		return $this->getPageImageHtml();
	}

	/**
	 * @param integer $size the width of the thumbnail
	 * @return string
	 */
	private function getPageImageHtml( $size = 750 ) {
		if ( $this->item->hasImage() ) {
			$thumb = models\Image::getThumbnail( $this->item->getFile(), $size );
			if ( $thumb && $thumb->getUrl() ) {
				$data = array(
					'url' => wfExpandUrl( $thumb->getUrl(), PROTO_CURRENT ),
					'wide' => $thumb->getWidth() > $thumb->getHeight(),
				);
				return Template::render( 'CardImage', $data );
			}
		}
		return '';
	}
}
