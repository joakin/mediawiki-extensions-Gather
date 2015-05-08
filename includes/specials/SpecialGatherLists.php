<?php
/**
 * SpecialGatherLists.php
 */

namespace Gather;

use User;
use SpecialPage;
use ApiMain;
use FauxRequest;
use Linker;
use Gather\views;
use Exception;

/**
 * Render a collection of articles.
 */
class SpecialGatherLists extends SpecialPage {

	public function __construct() {
		parent::__construct( 'GatherLists' );
	}
	/**
	 * Render the special page
	 */
	public function execute( $subPage ) {
		$mode = isset( $subPage ) ? $subPage : 'public';
		$this->getOutput()->redirect(
			SpecialPage::getTitleFor( 'Gather' )->getSubPage( 'all' )->getSubPage( $mode )->getFullUrl()
		);
	}
}

