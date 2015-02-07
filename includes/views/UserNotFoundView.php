
<?php
/**
 * UserNotFoundView.php
 */

namespace Gather;

/**
 * Renders an error when the user wasn't found
 */
class UserNotFoundView extends Gather\View {

	/**
	 * @inheritdoc
	 */
	public function getTitle() {
		return wfMessage( 'mobile-frontend-generic-404-title' )->text();
	}

	/**
	 * @inheritdoc
	 */
	public function getHtml() {
		// FIXME: Showing generic not found error right now. Show user not found instead
		$html = Html::openElement( 'div', array( 'class' => 'collection user-not-found' ) );
		$html .= Html::element( 'span', array( 'class' => 'mw-ui-anchor mw-ui-destructive' ),
			wfMessage( 'mobile-frontend-generic-404-desc' ) )->text();
		$html .= Html::closeElement( 'div' );
		return $html;
	}

}
