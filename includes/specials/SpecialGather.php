<?php
/**
 * SpecialGather.php
 */

namespace Gather;

use Gather\models;
use Gather\stores;
use Gather\views;
use \User;
use \SpecialPage;

/**
 * Render a collection of articles.
 */
class SpecialGather extends SpecialPage {

	public function __construct() {
		parent::__construct( 'Gather' );
	}

	/**
	 * Render the special page and redirect the user to the editor (if page exists)
	 *
	 * @param string $subpage The name of the page to edit
	 */
	public function execute( $subpage ) {

		if ( $subpage ) {
			$args = explode( '/', $subpage );
			// If there is a user argument, that's what we want to use
			if ( isset( $args[0] ) ) {
				// Show specified user's collections
				$user = User::newFromName( $args[0] );
			} else {
				// Otherwise use current user
				$user = $this->getUser();
			}
		} else {
			// For listing own lists, you need to be logged in
			$this->requireLogin( 'gather-anon-view-lists' );
			$user = $this->getUser();
		}

		if ( !( $user && $user->getId() ) ) {
			// Invalid user
			$this->renderNotFoundError();
		} else {
			if ( isset( $args ) && isset( $args[1] ) ) {
				$id = intval( $args[1] );
				$this->renderUserCollection( $user, $id );
			} else {
				$this->renderUserCollectionsList( $user );
			}
		}
	}

	/**
	 * Render an error when the user was not found
	 */
	public function renderNotFoundError() {
		$this->render( new views\NotFound() );
	}

	/**
	 * Renders a user collection
	 *
	 * @param User $user collection owner
	 * @param int $id collection id
	 */
	public function renderUserCollection( User $user, $id ) {
		$collection = stores\UserPageCollection::newFromUserAndId( $user, $id );
		if ( $collection === null ||
			( !$collection->isPublic() && !$this->isOwner( $user ) ) ) {
			// FIXME: No permissions to visit this. Showing not found ATM.
			$this->renderNotFoundError();
		} else {
			$this->render( new views\Collection( $collection ) );
		}
	}

	/**
	 * Renders a list of user collections
	 *
	 * @param User $user owner of collections
	 */
	public function renderUserCollectionsList( User $user ) {
		$collectionsList = stores\UserPageCollectionsList::newFromUser( $user, $this->isOwner( $user ) );
		$this->render( new views\CollectionsList( $collectionsList ) );
	}

	/**
	 * Render the special page using CollectionView and given collection
	 *
	 * @param views\View $view
	 */
	public function render( $view ) {
		$out = $this->getOutput();
		$this->setHeaders();
		$out->setProperty( 'unstyledContent', true );
		$out->addModuleStyles( array(
			'mediawiki.ui.anchor',
			'mediawiki.ui.icon',
			'ext.gather.icons',
			'ext.gather.styles',
		) );
		$out->setPageTitle( $view->getTitle() );
		$view->render( $out );
	}

	/**
	 * Returns if the user viewing the page is the owner of the collection/list
	 * we are viewing
	 *
	 * @param User $user user owner of the current page
	 *
	 * @return boolean
	 */
	private function isOwner( User $user ) {
		return $this->getUser()->getName() == $user->getName();
	}
}
