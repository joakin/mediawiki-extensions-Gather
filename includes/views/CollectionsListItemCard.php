<?php
/**
 * CollectionsListItemCard.php
 */

namespace Gather\views;

use Gather\models;
use Gather\views\helpers\Template;
use Html;
use helpers\CSS;

/**
 * View for an item card in a mobile collection.
 */
class CollectionsListItemCard extends View {

	/**
	 * @param models\CollectionInfo $collection
	 * @param Boolean $showOwnerLink Whether the card should show the owner of
	 * the collection link.
	 */
	public function __construct( models\CollectionInfo $collection, $showOwnerLink = false ) {
		$this->collection = $collection;
		$this->image = new Image( $collection );
		$this->showOwnerLink = $showOwnerLink;
	}

	/**
	 * @var models\Collection Model to be rendered on this view
	 */
	protected $collection;

	/**
	 * @var Image view for the collection image
	 */
	protected $image;

	/**
	 * Return title of collection
	 *
	 * @returns string collection title
	 */
	public function getTitle() {
		return $this->collection->getTitle();
	}

	/**
	 * Gets the privacy message
	 */
	public function getPrivacyMsg() {
		if ( $this->collection->isHidden() ) {
			$status = 'gather-hidden';
		} else {
			$status = $this->collection->isPublic() ? 'gather-public' : 'gather-private';
		}
		return wfMessage( $status )->text();
	}

	/**
	 * @inheritdoc
	 */
	public function getHtml( $data = array() ) {
		$collection = $this->collection;
		$defaults = array(
			'langdir' => 'ltr',
			'articleCountMsg' => wfMessage( 'gather-article-count', $collection->getCount() )->text(),
			'privacyMsg' => $this->getPrivacyMsg(),
			'collectionUrl' => $collection->getUrl(),
			'hasImage' => $collection->hasImage(),
			'image' => $this->image->getHtml(),
			'title' => $this->getTitle(),
		);
		$owner = $collection->getOwner();
		if ( $owner && $this->showOwnerLink ) {
			$defaults['owner'] = array(
				'link' => $collection->getOwnerUrl(),
				'class' => helpers\CSS::iconClass( 'profile', 'before' ),
				'label' => $owner->getName(),
			);
		}
		return Template::render( 'CollectionsListItemCard', array_merge( $defaults, $data ) );
	}
}
