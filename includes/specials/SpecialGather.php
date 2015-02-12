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
				$id = $args[1];
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
		$collection = new models\Collection(
			$user,
			$this->msg( 'gather-watchlist-title' ),
			$this->msg( 'gather-watchlist-description' )
		);
		// Watchlist lives at id 0
		if ( (int)$id === 0 ) {
			// Watchlist is private
			$collection->setPublic( false );
			if ( $this->isOwner( $user ) ) {
				$collection->load( new stores\WatchlistCollection( $user ) );
			}
		}
		// FIXME: For empty-collection and not-allowed-to-see-this we are doing the
		// same thing right now.
		$this->render( new views\Collection( $collection ) );
	}

	/**
	 * Renders a list of user collections
	 *
	 * @param User $user owner of collections
	 */
	public function renderUserCollectionsList( User $user ) {
		// FIXME: Substitute with proper storage class
		$collectionsListStore = new stores\DumbWatchlistOnlyCollectionsList(
			$user, $this->isOwner( $user )
		);
		$this->render( new views\CollectionsList( $collectionsListStore->getLists() ) );
	}

	/**
	 * Render the special page using CollectionView and given collection
	 *
	 * @param View $view
	 */
	public function render( $view ) {
		$out = $this->getOutput();
		$this->setHeaders();
		$out->setProperty( 'unstyledContent', true );
		$out->addModules( array( 'ext.collections.styles' ) );
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
