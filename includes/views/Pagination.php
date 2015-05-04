<?php
/**
 * Pagination.php
 */

namespace Gather\views;

use Gather\views\helpers\CSS;
use Html;

/**
 * View for the pagination buttons on lists
 */
class Pagination {

	/**
	 * Get the HTML for the more collections button (infinite scrolling)
	 * @param string $url url where the more button will point to
	 * @param string $text text for the button
	 * @param string $data data attributes for the button
	 * @param string $classes Additional css classes for the pagination button
	 */
	public static function more( $url, $text, $data = array(), $classes = '' ) {
		return Html::openElement( 'div', array( 'class' => 'collections-pagination' ) )
			. Html::element( 'a', array_merge( array(
				'href' => $url,
				'class' => CSS::buttonClass( 'progressive', $classes ),
			), $data ), $text )
			. Html::closeElement( 'div' );
	}

}
