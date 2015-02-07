<?php
/**
 * View.php
 */

namespace Gather;

/**
 * Render a view.
 */
abstract class View {
	/**
	 * Returns the html for the view
	 *
	 * @private
	 * @return string Html
	 */
	abstract protected function getHtml();

	/**
	 * Returns the title for the view
	 *
	 * @private
	 * @return string Html
	 */
	abstract protected function getTitle();

	/**
	 * Adds HTML of the view to the OutputPage.
	 *
	 * @param OutputPage $out
	 */
	public function render( OutputPage $out ) {
		$out->addHTML( $this->getHtml() );
	}
}
