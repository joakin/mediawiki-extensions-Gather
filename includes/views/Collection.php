<?php
/**
 * Collection.php
 */

namespace Gather\views;

use \Html;
use \User;
use \SpecialPage;
use Gather\views\helpers\CSS;
use Gather\models;

/**
 * Render a mobile card.
 */
class Collection extends View {
	/**
	 * @var Collection
	 */
	protected $collection;

	/**
	 * @var User $user viewing the collection
	 */
	protected $user;

	/**
	 * @param User $user that is viewing the collection
	 * @param models\Collection $collection
	 */
	public function __construct( $user, models\Collection $collection ) {
		$this->user = $user;
		$this->collection = $collection;
	}

	/**
	 * Returns the rendered html for the collection header
	 * @param Collection $collection
	 *
	 * @return string Html
	 */
	private function getHeaderHtml( models\Collection $collection ) {
		$collection = $this->collection;
		$description = $collection->getDescription();
		$owner = $collection->getOwner();

		$html = Html::openElement( 'div', array( 'class' => 'collection-header' ) ) .
			Html::element( 'div', array( 'class' => 'collection-description' ), $description ) .
			$this->getOwnerHtml( $owner ) .
			$this->getActionButtonsHtml() .
			Html::closeElement( 'div' );

		return $html;
	}

	/**
	 * Returns the html for showing the owner on the collection header
	 *
	 * @param User $owner Owner of the collection
	 * @return string Html
	 */
	private function getOwnerHtml( $owner ) {
		return Html::openElement( 'a', array(
				'href' => SpecialPage::getTitleFor( 'UserProfile', $owner->getName() )->getLocalUrl(),
				'class' => 'collection-owner',
			) ) .
			Html::element( 'span', array(
				'class' => CSS::iconClass( 'collection-owner', 'before', 'collection-owner-icon' ) ) ) .
			$owner->getName() .
			Html::closeElement( 'a' );
	}

	/**
	 * Get action buttons of the header
	 * @return string Html
	 */
	public function getActionButtonsHtml() {
		return Html::openElement( 'div',
				array(
					'class' => 'collection-actions',
					// FIXME: This should work without JavaScript
					'style' => 'display:none;',
				)
			) .
			$this->getEditButtonHtml() .
			Html::closeElement( 'div' );
	}

	/**
	 * Get the edit button html if user should edit
	 */
	public function getEditButtonHtml() {
		if ( $this->collection->isOwner( $this->user ) ) {
			return Html::element( 'a', array(
				// FIXME: This should work without JavaScript
				'href' => '#/collection/edit/' . $this->collection->getId(),
				'class' => CSS::buttonClass( 'progressive', 'collection-action-button edit-collection' )
			), wfMessage( 'gather-edit-button' ) );
		} else {
			return '';
		}
	}

	/**
	 * Returns the html for an empty collection
	 *
	 * @return string HTML
	 */
	private function getEmptyCollectionMessage() {
		// FIXME: i18n this messagesinclude 'Collection.php';
		return Html::openElement( 'div', array( 'class' => 'collection-empty' ) ) .
			Html::element( 'h3', array(), wfMessage( 'gather-empty' ) ) .
			Html::element( 'div', array(),
				wfMessage( 'gather-empty-footer' ) ) .
			Html::closeElement( 'div' );
	}

	/**
	 * Return title of collection
	 *
	 * @return string collection title
	 */
	public function getTitle() {
		return $this->collection->getTitle();
	}

	/**
	 * Returns the html for the items of a collection
	 * @param models\Collection
	 *
	 * @return string HTML
	 */
	public function getCollectionItems( models\Collection $collection ) {
		$html = Html::openElement( 'div', array( 'class' => 'collection-items' ) );
		foreach ( $collection as $item ) {
			if ( $item->getTitle()->getNamespace() === NS_MAIN ) {
				$view = new CollectionItemCard( $item );
				$html .= $view->getHtml();
			}
		}
		// FIXME: Pagination(??) Note the stores\WatchlistCollection
		// limits the size of the collection to 50.
		// Pagination may or may not be needed.
		$html .= Html::closeElement( 'div' );
		return $html;
	}

	/**
	 * @inheritdoc
	 */
	protected function getHtml() {
		$collection = $this->collection;

		$html = Html::openElement( 'div', array( 'class' => 'collection content' ) ) .
			$this->getHeaderHtml( $collection );

		if ( $collection->getCount() > 0 ) {
			$html .= $this->getCollectionItems( $collection );
		} else {
			$html .= $this->getEmptyCollectionMessage();
		}

		$html .= Html::closeElement( 'div' );

		return $html;
	}
}
