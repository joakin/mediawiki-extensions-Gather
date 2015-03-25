<?php
/**
 * SpecialGather.php
 */

namespace Gather;

use Gather\models;
use Gather\views;
use \User;
use \SpecialPage;
use \UsageException;
use \DerivativeRequest;
use \ApiMain;
use \Exception;

/**
 * Render a collection of articles.
 */
class SpecialGather extends SpecialPage {

	public function __construct() {
		parent::__construct( 'Gather' );
		$out = $this->getOutput();
		$out->addModules(
			array(
				'ext.gather.special',
			)
		);
		$out->addModuleStyles( array(
			'mediawiki.ui.anchor',
			'mediawiki.ui.icon',
			'ext.gather.icons',
			'ext.gather.styles',
		) );
	}

	/**
	 * Render the special page
	 *
	 * @param string $subpage
	 */
	public function execute( $subpage ) {

		if ( preg_match( '/^$/', $subpage ) ) {
			// Root subpage. User owned collections.
			// For listing own lists, you need to be logged in
			$this->requireLogin( 'gather-anon-view-lists' );
			$user = $this->getUser();
			$this->renderUserCollectionsList( $user );

		} elseif ( preg_match( '/^by\/(?<user>\w+)\/?$/', $subpage, $matches ) ) {
			// User's collections
			// /by/:user = /by/:user/
			$user = User::newFromName( $matches['user'] );

			if ( !( $user && $user->getId() ) ) {
				// Invalid user
				$this->renderError( new views\NotFound() );
			} else {
				$this->renderUserCollectionsList( $user );
			}

		} elseif ( preg_match( '/^by\/(?<user>\w+)\/(?<id>\d+)$/', $subpage, $matches ) ) {
			// Collection page
			// /by/:user/:id
			$id = (int)$matches['id'];
			$user = User::newFromName( $matches['user'] );

			if ( !( $user && $user->getId() ) ) {
				// Invalid user
				$this->renderError( new views\NotFound() );
			} else {
				$this->renderUserCollection( $user, $id );
			}

		} elseif ( preg_match( '/^all(\/(?<mode>\w+))?\/?$/', $subpage, $matches ) ) {
			// All collections. Public or hidden
			// /all = /all/ = /all/public = /all/public/
			// /all/hidden = /all/hidden/

			// mode can be hidden or public only
			$mode = isset( $matches['mode'] ) && $matches['mode'] === 'hidden' ?
				'hidden' : 'public';
			// FIXME: Migrate Special:GatherLists here instead of redirecting
			$this->getOutput()->redirect(
				SpecialPage::getTitleFor( 'GatherLists', $mode )->getLocalURL() );

		} else {
			// Unknown subpage
			$this->renderError( new views\NotFound() );
		}

	}

	/**
	 * Render an error to the special page
	 *
	 * @param View $view View of error to render
	 */
	public function renderError( $view ) {
		$this->render( $view );
	}

	/**
	 * Renders a user collection
	 *
	 * @param User $user collection owner
	 * @param int $id collection id
	 */
	public function renderUserCollection( User $user, $id ) {
		if ( !is_int( $id ) ) {
			throw new Exception( __METHOD__ . ' requires the second parameter to be an integer, '
				. gettype( $id ) . ' given.' );
		}
		$collection = models\Collection::newFromApi( $id, $user );

		if ( $collection === null ||
			( !$collection->isPublic() && !$this->isOwner( $user ) ) ) {
			// FIXME: No permissions to visit this. Showing not found ATM.
			$this->renderError( new views\NotFound() );
		} else {
			$this->getOutput()->addJsConfigVars( 'wgGatherCollections', $collection->toArray() );
			$this->render( new views\Collection( $this->getUser(), $collection ) );
			$this->updateCollectionImage( $collection );
		}
	}

	/**
	 * Renders a list of user collections
	 *
	 * @param User $user owner of collections
	 */
	public function renderUserCollectionsList( User $user ) {
		$collectionsList = models\CollectionsList::newFromApi( $user, $this->isOwner( $user ) );
		if ( $collectionsList->getCount() > 0 ) {
			$this->render( new views\CollectionsList( $collectionsList ) );
		} else {
			$this->renderError( new views\NoPublic() );
		}
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

	// FIXME: Re-evaluate when UI supports editing image of collection.
	private function updateCollectionImage( $collection ) {
		$currentImage = $collection->getFile();
		$user = $collection->getOwner();
		$suggestedImage = $collection->getSuggestedImage();
		$imageChanged = !$currentImage || $currentImage->getTitle()->getText() !== $suggestedImage;
		if ( $imageChanged && $this->isOwner( $user ) && !$collection->isWatchlist() ) {
			// try to set the collection image to the first item in the collection.
			try {
				$api = new ApiMain( new DerivativeRequest(
					$this->getRequest(),
					array(
						'action' => 'editlist',
						'id' => $collection->getId(),
						'image' => $suggestedImage,
						'token' => $user->getEditToken( 'watch' ),
					),
					true
				), true );
				$api->execute();
			} catch ( UsageException $e ) {
			}
		}
	}
}
