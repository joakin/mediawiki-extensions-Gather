<?php
/**
 * SpecialGather.php
 */

namespace Gather;

use Gather\models;
use Gather\views;
use User;
use SpecialPage;
use UsageException;
use DerivativeRequest;
use ApiMain;
use InvalidArgumentException;
use Html;

/**
 * Render a collection of articles.
 */
class SpecialGather extends SpecialPage {

	public function __construct() {
		parent::__construct( 'Gather' );
	}

	/**
	 * Render the special page
	 *
	 * @param string $subpage
	 */
	public function execute( $subpage ) {
		$out = $this->getOutput();
		$out->addModules( array(
			'ext.gather.special',
		) );
		$out->addModuleStyles( array(
			'mediawiki.ui.anchor',
			'mediawiki.ui.icon',
			'ext.gather.icons',
			'ext.gather.styles',
		) );
		if ( $subpage === '' || preg_match( '/^by\/?/', $subpage ) ) {
			// Root subpage. User owned collections.
			// / = /by = /by/
			// For listing own lists, you need to be logged in
			$this->requireLogin( 'gather-anon-view-lists' );
			$user = $this->getUser();
			$this->renderUserCollectionsList( $user );

		} elseif ( preg_match( '/^by\/(?<user>[^\/]+)\/?$/', $subpage, $matches ) ) {
			// User's collections
			// /by/:user = /by/:user/
			$user = User::newFromName( $matches['user'] );

			if ( !( $user && $user->getId() ) ) {
				// Invalid user
				$this->renderError( new views\NotFound() );
			} else {
				$this->renderUserCollectionsList( $user );
			}

		} elseif ( preg_match( '/^by\/(?<user>[^\/]+)\/(?<id>\d+)$/', $subpage, $matches ) ) {
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

		} elseif ( preg_match( '/^all(\/(?<mode>[^\/]+))?\/?$/', $subpage, $matches ) ) {
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
			throw new InvalidArgumentException(
				__METHOD__ . ' requires the second parameter to be an integer, '
				. gettype( $id ) . ' given.'
			);
		}
		$collection = models\Collection::newFromApi( $id, $user, $this->getRequest()->getValues() );

		if ( $collection === null ||
			// If collection is private and current user doesn't own it
			// FIXME: No permissions to visit this. Showing not found ATM.
			( !$collection->isPublic() && !$collection->isOwner( $this->getUser() ) ) ) {
			$this->renderError( new views\NotFound() );
		} else {
			$out = $this->getOutput();
			$out->addJsConfigVars( 'wgGatherCollections', $collection->toArray() );
			$this->render( new views\Collection( $this->getUser(), $collection ) );
			$this->updateCollectionImage( $collection );
			$this->addMetaInformation(
				$collection->getDescription(),
				models\Image::getThumbnail( $collection->getFile() )
			);
		}
	}

	/**
	 * Adds meta tags to the page.
	 *
	 * @param string $description
	 * @param Thumbnail $thumb
	 */
	protected function addMetaInformation( $description, $thumb ) {
		$out = $this->getOutput();
		$out->addHeadItem( 'description',
			Html::element(
				'meta', array(
					'name' => 'description',
					'content' => $description,
				)
			)
		);
		if ( $thumb ) {
			$out->addHeadItem(
				'ogimage',
				Html::element(
					'meta',
					array(
						'property' => 'og:image',
						'content' => wfExpandUrl( $thumb->getUrl(), PROTO_CURRENT ),
					)
				)
			);
		}
	}

	/**
	 * Renders a list of user collections
	 *
	 * @param User $user owner of collections
	 */
	public function renderUserCollectionsList( User $user ) {
		$collectionsList = models\CollectionsList::newFromApi(
			$user, $this->getUser()->equals( $user ), false,
			$this->getRequest()->getValues()
		);
		if ( $collectionsList->getCount() > 0 ) {
			$this->addMetaInformation(
				wfMessage( 'gather-meta-description', $user->getName() ),
				models\Image::getThumbnail( $collectionsList->getFile() )
			);
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
		// disable visible page title
		$out->setPageTitle( '' );
		// add title of the actual view to html title-tag
		$out->setHTMLTitle( $view->getTitle() );
		$view->render( $out );
	}

	// FIXME: Re-evaluate when UI supports editing image of collection.
	private function updateCollectionImage( $collection ) {
		$currentImage = $collection->getFile();
		$suggestedImage = $collection->getSuggestedImage();
		$imageChanged = !$currentImage || $currentImage->getTitle()->getText() !== $suggestedImage;
		if ( $imageChanged &&
			$collection->isOwner( $this->getUser() ) &&
			!$collection->isWatchlist() ) {
			// try to set the collection image to the first item in the collection.
			try {
				$api = new ApiMain( new DerivativeRequest(
					$this->getRequest(),
					array(
						'action' => 'editlist',
						'id' => $collection->getId(),
						'image' => $suggestedImage,
						'token' => $collection->getOwner()->getEditToken( 'watch' ),
					),
					true
				), true );
				$api->execute();
			} catch ( UsageException $e ) {
			}
		}
	}
}
