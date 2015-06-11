<?php
/**
 * CollectionsList.php
 */

namespace Gather\views;

use Gather\models;
use Gather\views\helpers\Template;

/**
 * Renders a mobile collection card list
 */
class Tabs extends View {
	/**
	 * @inheritdoc
	 */
	public function getHtml( $data = array() ) {
		return Template::render( 'tabs', $data );
	}
}
