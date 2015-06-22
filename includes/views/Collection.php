<?php
/**
 * Collection.php
 */

namespace Gather\views;

use Html;
use User;
use SpecialPage;
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
		$privacyMsg = false;

		if ( $collection->isHidden() ) {
			$privacyMsg = wfMessage( 'gather-hidden' )->plain();
		} elseif ( !$collection->isPublic() ) {
			$privacyMsg = wfMessage( 'gather-private' )->plain();
		}

		$html = Html::element( 'div', array(
				'class' => $owner ? 'collection-moderation' : ''
			) ) .
			Html::openElement( 'div', array( 'class' => 'collection-header' ) ) .
			Html::openElement( 'div', array( 'class' => 'collection-meta' ) );
		// Provide privacy tag if collection is not public
		if ( $privacyMsg ) {
			$html .= Html::element( 'div', array( 'class' => 'collection-privacy' ), $privacyMsg );
		}
		$html .= Html::closeElement( 'div' );
		// collection doesn't necessarily have an owner
		if ( $owner ) {
			$html .= $this->getOwnerHtml( $owner );
		}
		$html .=Html::element( 'h1', array( 'id' => 'section_0' ), $collection->getTitle() );
		if ( $description ) {
			$html .= Html::element( 'div', array( 'class' => 'collection-description' ), $description );
		}

		$html .= $this->getActionButtonsHtml() .
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
		$ownerName = $owner->getName();
		$userIconLink = Html::openElement( 'a', array(
				'href' => SpecialPage::getTitleFor( 'Gather' )->getSubPage( 'by' )->
					getSubPage( $ownerName )->getLocalUrl(),
			) ) .
			Html::element( 'span', array(
				'class' => CSS::iconClass( 'collection-owner', 'before', 'collection-owner-icon' ) ) ) .
			$owner->getName() .
			Html::closeElement( 'a' );

		return Html::openElement( 'div', array( 'class' => 'collection-owner' ) )
			. wfMessage( 'gather-collection-owner-text' )
				->rawParams( $userIconLink )->params( $ownerName )->parse()
			. Html::closeElement( 'div' );
	}

	/**
	 * Get action buttons of the header
	 * @return string Html
	 */
	public function getActionButtonsHtml() {
		return Html::openElement( 'div',
				array(
					'class' => 'collection-actions',
				)
			) .
			$this->getEditButtonHtml() .
			Html::closeElement( 'div' );
	}

	/**
	 * Get the edit button html if user should edit
	 * FIXME: Move this to JavaScript.
	 */
	public function getEditButtonHtml() {
		$id = $this->collection->getId();
		// Do not edit watchlist
		if ( $id !== 0 && $this->collection->isOwner( $this->user ) ) {
			return Html::element( 'a', array(
				// FIXME: This should work without JavaScript
				'href' => '#/collection/edit/' . $id,
				'class' => CSS::buttonClass( 'progressive', 'collection-action-button edit-collection' )
			), wfMessage( 'gather-edit-button' )->text() );
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
		$key = $this->collection->isOwner( $this->user ) ? 'gather-empty-footer-mine' :
			'gather-empty-footer';
		return Html::openElement( 'div', array( 'class' => 'collection-empty' ) ) .
			Html::element( 'h3', array(), wfMessage( 'gather-empty' )->text() ) .
			Html::element( 'div', array(),
				wfMessage( $key, $this->user->getName() )->text() ) .
			Html::closeElement( 'div' );
	}

	/**
	 * Disable regular page title
	 * @return string HTML
	 */
	public function getTitle() {
		return '';
	}

	/** @inheritdoc */
	public function getHTMLTitle() {
		return $this->collection->getTitle();
	}

	/**
	 * Returns the html for the items of a collection
	 * @param models\Collection
	 * @param Array data passed to initial template rendering
	 *
	 * @return string HTML
	 */
	protected function getCollectionItems( models\Collection $collection, $data = array() ) {
		$html = Html::openElement( 'div', array( 'class' => 'collection-cards' ) );
		foreach ( $collection as $item ) {
			$view = new CollectionItemCard( $item );
			$html .= $view->getHtml( $data );
		}
		$html .= Html::closeElement( 'div' );
		return $html;
	}

	/**
	 * @inheritdoc
	 */
	protected function getHtml( $data = array() ) {
		$collection = $this->collection;
		$owner = $collection->getOwner();

		$html = Html::openElement( 'div', array(
				'class' => 'collection content view-border-box',
				'data-id' => $collection->getId(),
				'data-label' => $collection->getTitle(),
				'data-owner' => $owner ? $owner->getName() : false,
				'data-is-admin' => $this->user->isAllowed( 'gather-hidelist' ),
				'data-is-owner' => $collection->isOwner( $this->user ) ? true : false,
			) ) .
			$this->getHeaderHtml( $collection );

		if ( $collection->getCount() > 0 ) {
			$html .= $this->getCollectionItems( $collection, $data );
			$url = $collection->getContinueUrl();
			if ( $url ) {
				$html .= Pagination::more( $url, wfMessage( 'gather-collection-more' )->text(),
					array(
						'data-pagination-query' => $collection->getContinueQuery(),
					)
				);
			}
		} else {
			$html .= $this->getEmptyCollectionMessage();
		}

		$html .= Html::closeElement( 'div' );

		return $html;
	}
}
