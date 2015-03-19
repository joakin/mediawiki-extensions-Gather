<?php
/**
 * NotFound.php
 */

namespace Gather\views;

use \Html;

/**
 * Renders an error when there are no public lists for a user
 */
class NoPublic extends View {

	/**
	 * @inheritdoc
	 */
	public function getTitle() {
		return wfMessage( 'gather-no-public-lists-title' )->text();
	}

	/**
	 * @inheritdoc
	 */
	public function getHtml() {
		$html = Html::openElement( 'div', array( 'class' => 'collection not-found content' ) );
		$html .= Html::element( 'span', array( 'class' => 'mw-ui-anchor mw-ui-destructive' ),
			wfMessage( 'gather-no-public-lists-description' )->text() );
		$html .= Html::closeElement( 'div' );
		return $html;
	}

}
