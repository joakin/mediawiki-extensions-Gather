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

/**
 * Render a collection of articles.
 */
class SpecialGatherLists extends SpecialPage {

	public function __construct() {
		parent::__construct( 'GatherLists' );
		$out = $this->getOutput();
		$out->addModules( array(
			'ext.gather.lists'
		) );
		$out->addModuleStyles( array(
			'mediawiki.ui.anchor',
			'mediawiki.ui.icon',
			'ext.gather.icons',
			'ext.gather.styles',
		) );
	}

	/**
	 * Render the special page
	 */
	public function execute() {
		$api = new ApiMain( new FauxRequest( array(
			'action' => 'query',
			'list' => 'lists',
			'lstmode' => 'allpublic',
			// FIXME: Need owner to link to collection
			'lstprop' => 'label|description|image|count',
			// TODO: Pagination
			'continue' => '',
		) ) );
		$api->execute();
		$data = $api->getResultData();
		if ( isset( $data['query']['lists'] ) ) {
			$lists = $data['query']['lists'];
			$this->render( $lists );
		}
	}

	/**
	 * Render the special page
	 *
	 * @param array $lists
	 */
	public function render( $lists ) {
		$out = $this->getOutput();
		$this->setHeaders();
		$out->setProperty( 'unstyledContent', true );
		$out->setPageTitle( wfMessage( 'gather-lists-title' ) );

		$html = '';
		$html .= Html::openElement( 'div', array( 'class' => 'content gather-lists' ) );
		$html .= Html::openElement( 'ul', array() );
		$html .= Html::openElement( 'li', array( 'class' => 'heading' ) )
		. Html::element( 'span', array(), wfMessage( 'gather-lists-collection-owner' ) )
		. Html::element( 'span', array(), wfMessage( 'gather-lists-collection-title' ) )
		. Html::element( 'span', array(), wfMessage( 'gather-lists-collection-description' ) )
		. Html::element( 'span', array(), wfMessage( 'gather-lists-collection-count' ) )
		. Html::closeElement( 'li' );
		foreach ( $lists as $list ) {
			$html .= $this->row( $list );
		}
		$html .= Html::closeElement( 'ul' );
		$html .= Html::closeElement( 'div' );

		$out->addHTML( $html );
	}

	private function row( $data ) {
		return Html::openElement( 'li', array( 'class' => $additionalClasses ) )
			. $this->userLink( $data['owner'] )
			. $this->collectionLink( $data['label'], $data['owner'], $data['id'] )
			. Html::element( 'span', array(), $data['description'] )
			. Html::element( 'span', array(), $data['count'] )
			. Html::closeElement( 'li' );
	}

	private function userLink( $user ) {
		return Html::element( 'a', array(
			'href' => SpecialPage::getTitleFor( 'Gather', $user )->getLocalUrl()
		), $user );
	}

	private function collectionLink( $text, $user, $id ) {
		return Html::element( 'a', array(
			'href' => SpecialPage::getTitleFor( 'Gather', $user.'/'.$id )->getLocalUrl()
		), $text );
	}
}

