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
		$out = $this->getOutput();
		$out->addModuleStyles( array(
			'mediawiki.ui.anchor',
			'mediawiki.ui.icon',
			'ext.gather.icons',
			'ext.gather.styles',
		) );
		$out->addModules(
			array(
				'ext.gather.moderation',
			)
		);
	}

	public function renderError() {
		$out = $this->getOutput();
		// FIXME: Get better i18n message for this view.
		$view = new views\NotFound();
		$out->setPageTitle( $view->getTitle() );
		$view->render( $out );
	}

	/**
	 * Render the special page
	 */
	public function execute( $subPage ) {
		$out = $this->getOutput();
		if ( $subPage === 'hidden' ) {
			if ( !$this->canHideLists() ) {
				$this->renderError();
				return;
			}
			$out->addSubtitle( $this->getSubTitle() );
		} elseif ( $this->canHideLists() ) {
			$out->addSubtitle( $this->getSubTitle( true ) );
		}
		$req = $this->getRequest();
		$continue = $req->getValues();
		$cList = models\CollectionsList::newFromApi( null, false,
			false, $continue, $subPage === 'hidden' ? 'allhidden' : 'allpublic', 100 );
		$this->render( $cList, $subPage === 'hidden' ? 'show' : 'hide' );
	}

	/**
	 * Get subtitle text with a link to show the (un-)hidden collections.
	 * @param boolean $hidden Whether to get a link to show the hidden collections
	 * @return string
	 */
	public function getSubTitle( $hidden = false ) {
		return Linker::link(
			SpecialPage::getTitleFor( 'GatherLists', ( $hidden ? 'hidden' : false ) ),
			( $hidden ? $this->msg( 'gather-lists-showhidden' ) : $this->msg( 'gather-lists-showvisible' ) )
		);
	}

	/**
	 * Render the special page
	 *
	 * @param CollectionsList $lists
	 * @param string $action hide or show - action to associate with the row.
	 * @param string $nextPageUrl url to access the next page of results.
	 */
	public function render( $cList, $action ) {
		$out = $this->getOutput();
		$this->setHeaders();
		$out->setProperty( 'unstyledContent', true );
		$out->setPageTitle( wfMessage( 'gather-lists-title' ) );
		$data = array(
			'canHide' => $this->canHideLists(),
			'action' => $action,
			'nextPageUrl' => $cList->getContinueUrl(),
		);

		$view = new views\ReportTable( $this->getUser(), $this->getLanguage(), $cList );
		$view->render( $this->getOutput(), $data );
	}

	/**
	 * Returns if the current user can hide public lists
	 * @return bool
	 */
	private function canHideLists() {
		return $this->getUser()->isAllowed( 'gather-hidelist' );
	}

	/**
	 * Renders a html row of data
	 * @param models\CollectionInfo $collection
	 * @param string [$action] hide or show - action to associate with the row.
	 * @return string
	 */
	private function row( $collection, $data ) {
		$view = new views\ReportTableRow( $this->getUser(), $this->getLanguage(), $collection );
		$view->render( $this->getOutput(), $data );
	}
}

