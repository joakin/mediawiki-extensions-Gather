<?php
/**
 * SpecialGatherLists.php
 */

namespace Gather;

use User;
use SpecialPage;
use ApiMain;
use FauxRequest;
use Html;
use Gather\views\helpers\CSS;
use MWTimestamp;

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
				'ext.gather.lists',
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
		if ( $subPage === 'hidden' && !$this->canHideLists() ) {
			$this->renderError();
			return;
		}
		// FIXME: Make method on CollectionsList
		$api = new ApiMain( new FauxRequest( array(
			'action' => 'query',
			'list' => 'lists',
			'lstmode' => $subPage === 'hidden' ? 'allhidden' : 'allpublic',
			// FIXME: Need owner to link to collection
			'lstprop' => 'label|description|image|count|updated',
			// TODO: Pagination
			'continue' => '',
		) ) );
		$api->execute();
		$data = $api->getResultData();
		if ( isset( $data['query']['lists'] ) ) {
			$lists = $data['query']['lists'];
			$this->render( $lists, $subPage === 'hidden' ? 'show' : 'hide' );
		}
	}

	/**
	 * Render the special page
	 *
	 * @param array $lists
	 * @param string [$action] hide or show - action to associate with the row.
	 */
	public function render( $lists, $action ) {
		$out = $this->getOutput();
		$this->setHeaders();
		$out->setProperty( 'unstyledContent', true );
		$out->setPageTitle( wfMessage( 'gather-lists-title' ) );

		// FIXME: Move below to View.
		$html = '';
		$html .= Html::openElement( 'div', array( 'class' => 'content gather-lists' ) );
		$html .= Html::openElement( 'ul', array() );
		$html .= Html::openElement( 'li', array( 'class' => 'heading' ) )
		. Html::element( 'span', array(), wfMessage( 'gather-lists-collection-owner' ) )
		. Html::element( 'span', array(), wfMessage( 'gather-lists-collection-title' ) )
		. Html::element( 'span', array(), wfMessage( 'gather-lists-collection-description' ) )
		. Html::element( 'span', array(), wfMessage( 'gather-lists-collection-count' ) )
		. Html::element( 'span', array(), wfMessage( 'gather-lists-collection-last-updated' ) );
		if ( $this->canHideLists() ) {
			$html .= Html::element( 'span', array(), '' );
		}
		$html .= Html::closeElement( 'li' );
		foreach ( $lists as $list ) {
			$html .= $this->row( $list, $action );
		}
		$html .= Html::closeElement( 'ul' );
		$html .= Html::closeElement( 'div' );

		$out->addHTML( $html );
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
	 * @param array $data
	 * @param string [$action] hide or show - action to associate with the row.
	 * @return string
	 */
	private function row( $data, $action = 'hide' ) {
		$lang = $this->getLanguage();
		$user = $this->getUser();
		$ts = $lang->userTimeAndDate( new MWTimestamp( $data['updated'] ), $user );

		$html = Html::openElement( 'li' )
			. $this->userLink( $data['owner'] )
			. $this->collectionLink( $data['label'], $data['owner'], $data['id'] )
			. Html::element( 'span', array(), $data['description'] )
			. Html::element( 'span', array(), $data['count'] )
			. Html::element( 'span', array(), $ts );

		if ( $this->canHideLists() ) {
			$className = CSS::buttonClass(
				$action === 'hide' ?  'destructive': 'constructive',
				'moderate-collection'
			);

			$label = $action === 'hide' ? $this->msg( 'gather-lists-hide-collection-label' ) :
				$this->msg( 'gather-lists-show-collection-label' );

			$html .= Html::openElement( 'span', array() )
				. Html::element( 'button', array(
					'class' => $className,
					'data-id' => $data['id'],
					'data-action' => $action,
					'data-label' => $data['label'],
					'data-owner' => $data['owner']
				), $label )
				. Html::closeElement( 'span' );
		}
		$html .= Html::closeElement( 'li' );
		return $html;
	}

	/**
	 * Renders a html link for the user's gather page
	 * @param User $user
	 * @return string
	 */
	private function userLink( $user ) {
		return Html::element( 'a', array(
			'href' => SpecialPage::getTitleFor( 'Gather', $user )->getLocalUrl()
		), $user );
	}

	/**
	 * Renders a html link for a collection page
	 * @param string $text of the link
	 * @param User $user owner of the collection
	 * @param int $id of the collection
	 * @return string
	 */
	private function collectionLink( $text, $user, $id ) {
		return Html::element( 'a', array(
			'href' => SpecialPage::getTitleFor( 'Gather', $user.'/'.$id )->getLocalUrl()
		), $text );
	}
}

