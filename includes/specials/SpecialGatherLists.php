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
		$limit = 100;

		// FIXME: Make method on CollectionsList
		$api = new ApiMain( new FauxRequest( array_merge( array(
			'action' => 'query',
			'list' => 'lists',
			'lstmode' => $subPage === 'hidden' ? 'allhidden' : 'allpublic',
			'lstlimit' => $limit,
			// FIXME: Need owner to link to collection
			'lstprop' => 'label|description|image|count|updated|owner',
			'continue' => '',
		), $req->getValues() ) ) );
		try {
			$api->execute();
			$data = $api->getResult()->getResultData( null, array( 'Strip' => 'all' ) );
			if ( isset( $data['query']['lists'] ) ) {
				$lists = $data['query']['lists'];
				if ( isset( $data['continue'] ) ) {
					$nextPageUrl = $this->getTitle()->getLocalUrl( $data['continue'] );
				} else {
					$nextPageUrl = '';
				}
				$this->render( $lists, $subPage === 'hidden' ? 'show' : 'hide', $nextPageUrl );
			}
		} catch ( Exception $e ) {
		}
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
	 * @param array $lists
	 * @param string $action hide or show - action to associate with the row.
	 * @param string $nextPageUrl url to access the next page of results.
	 */
	public function render( $lists, $action, $nextPageUrl = '' ) {
		$out = $this->getOutput();
		$this->setHeaders();
		$out->setProperty( 'unstyledContent', true );
		$out->setPageTitle( wfMessage( 'gather-lists-title' ) );
		$data = array(
			'canHide' => $this->canHideLists(),
			'action' => $action,
			'nextPageUrl' => $nextPageUrl,
		);

		$cList = new models\CollectionsList();
		foreach ( $lists as $list ) {
			$collection = new models\CollectionInfo( $list['id'], User::newFromName( $list['owner'] ),
				$list['label'], $list['description'] );
			$collection->setCount( $list['count'] );
			$collection->setUpdated( $list['updated'] );
			$cList->add( $collection );
		}
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

