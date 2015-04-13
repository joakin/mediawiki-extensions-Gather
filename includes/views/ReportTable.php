<?php
/**
 * ReportTable.php
 */

namespace Gather\views;

use User;
use Language;
use Gather\models;
use Html;

/**
 * Render a view.
 */
class ReportTable extends View {
	/**
	 * @param User $user that is viewing the collection
	 * @param Language $language
	 * @param models\CollectionsList $collectionList
	 */
	public function __construct( User $user, Language $language,
		models\CollectionsList $collectionList ) {
		$this->user = $user;
		$this->language = $language;
		$this->collectionList = $collectionList;
	}

	/**
	 * Returns the html for the view
	 *
	 * @param array $data
	 * @return string Html
	 */
	protected function getHtml( $data = array() ) {
		$html = '';
		$html .= Html::openElement( 'div', array( 'class' => 'content gather-lists' ) );
		// Display protocol for hiding another users list
		if ( $data['canHide'] ) {
			$html .= Html::rawElement( 'div', array( 'class' => 'hide-protocol' ),
				wfMessage( 'gather-lists-hide-protocol' )->parse() );
		}
		$html .= Html::openElement( 'ul', array() );
		$html .= Html::openElement( 'li', array( 'class' => 'heading' ) )
		. Html::element( 'span', array(), wfMessage( 'gather-lists-collection-title' ) )
		. Html::element( 'span', array(), wfMessage( 'gather-lists-collection-description' ) )
		. Html::element( 'span', array(), wfMessage( 'gather-lists-collection-count' ) )
		. Html::element( 'span', array(), wfMessage( 'gather-lists-collection-owner' ) )
		. Html::element( 'span', array(), wfMessage( 'gather-lists-collection-last-updated' ) );
		if ( $data['canHide'] ) {
			$html .= Html::element( 'span', array(), '' );
		}
		$html .= Html::closeElement( 'li' );
		foreach ( $this->collectionList as $collection ) {
			$partial = new ReportTableRow( $this->user, $this->language, $collection );
			$html .= $partial->getHtml( $data );
		}
		$html .= Html::closeElement( 'ul' );
		if ( $data['nextPageUrl'] ) {
			$html .= Pagination::more(
				$data['nextPageUrl'], wfMessage( 'gather-lists-collection-more-link-label' ) );
		}
		$html .= Html::closeElement( 'div' );
		return $html;
	}

	/**
	 * Returns the title for the view
	 *
	 * @private
	 * @return string Html
	 */
	public function getTitle() {
		return '';
	}
}
