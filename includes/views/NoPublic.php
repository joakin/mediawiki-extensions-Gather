<?php
/**
 * NotFound.php
 */

namespace Gather\views;

use Html;
use User;

/**
 * Renders an error when there are no public lists for a user
 */
class NoPublic extends View {

	/**
	 * Constructor
	 * @param models\WithImage $item
	 */
	public function __construct( User $user ) {
		$this->user = $user;
	}

	/**
	 * @inheritdoc
	 */
	public function getTitle() {
		return wfMessage( 'gather-no-public-lists-title' )->text();
	}

	/**
	 * @inheritdoc
	 */
	public function getHtml( $data = array() ) {
		$html = Html::openElement( 'div', array( 'class' => 'collection not-found content' ) );
		$html .= Html::element( 'span', array( 'class' => 'mw-ui-anchor mw-ui-destructive' ),
			wfMessage( 'gather-no-public-lists-description' )->text(), $this->user );
		$html .= Html::closeElement( 'div' );
		return $html;
	}

}
