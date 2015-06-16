<?php
/**
 * View.php
 */

namespace Gather\views;

use OutputPage;

/**
 * Render a view.
 */
abstract class View {
	/**
	 * Returns the html for the view
	 *
	 * @param array additional $data to help construct the view
	 * @return string Html
	 */
	abstract protected function getHtml( $data = array() );

	/**
	 * Returns the title for the view
	 *
	 * @private
	 * @return string Html
	 */
	abstract public function getTitle();

	/**
	 * Returns the title for the HTML tag title
	 *
	 * @private
	 * @return string Html
	 */
	public function getHTMLTitle() {
		return $this->getTitle();
	}

	/**
	 * Adds HTML of the view to the OutputPage.
	 *
	 * @param OutputPage $out
	 */
	public function render( OutputPage $out, $data = array() ) {
		$data['langdir'] = $out->getLanguage()->getDir();
		$out->addHTML( $this->getHtml( $data ) );
	}
}
