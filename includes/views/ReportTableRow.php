<?php
/**
 * View.php
 */

namespace Gather\views;

use User;
use Language;
use Gather\models;
use Html;
use SpecialPage;
use Gather\views\helpers\CSS;

/**
 * Render a view.
 */
class ReportTableRow extends View {
	/**
	 * Renders a html link for the user's gather page
	 * @param User $user
	 * @return string
	 */
	private function userLink( $user ) {
		return Html::element( 'a', array(
			'href' => SpecialPage::getTitleFor( 'Gather', 'by/' . $user )->getLocalUrl()
		), $user );
	}

	/**
	 * Renders a html link for a collection page
	 * @param models\CollectionInfo $collection
	 * @return string
	 */
	private function collectionLink( $collection ) {
		return Html::element( 'a', array(
			'href' => $collection->getUrl()
		), $collection->getTitle() );
	}

	/**
	 * @param User $user that is viewing the collection
	 * @param models\CollectionInfo $collection
	 */
	public function __construct( User $user, Language $language, models\CollectionInfo $collection ) {
		$this->user = $user;
		$this->language = $language;
		$this->collection = $collection;
	}

	/**
	 * Returns the html for the view
	 *
	 * @param array $data
	 * @return string Html
	 */
	public function getHtml( $data = array() ) {
		$lang = $this->language;
		$user = $this->user;
		$collection = $this->collection;
		$action = isset( $data['action'] ) ? $data['action'] : 'hide';

		$ts = $lang->userTimeAndDate( $collection->getUpdated(), $user );
		$owner = $collection->getOwner();
		$title = $collection->getTitle();
		$id = $collection->getId();

		$html = Html::openElement( 'li' )
			. $this->collectionLink( $collection )
			. Html::element( 'span', array(), $collection->getDescription() )
			. Html::element( 'span', array(), $collection->getCount() )
			. $this->userLink( $owner )
			. Html::element( 'span', array(), $ts );

		if ( $data['canHide'] ) {
			$className = CSS::buttonClass(
				$action === 'hide' ?  'destructive': 'constructive',
				'moderate-collection'
			);

			$label = $action === 'hide' ? wfMessage( 'gather-lists-hide-collection-label' )->text() :
				wfMessage( 'gather-lists-show-collection-label' )->text();

			$html .= Html::openElement( 'span', array() )
				. Html::element( 'button', array(
					'class' => $className,
					'data-id' => $id,
					'data-action' => $action,
					'data-label' => $title,
					'data-owner' => $owner->getName(),
				), $label )
				. Html::closeElement( 'span' );
		}
		$html .= Html::closeElement( 'li' );
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
