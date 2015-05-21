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
use Linker;

/**
 * Render a collection of articles.
 */
class SpecialGather extends SpecialPage {

	protected $specialCollections;

	public function __construct() {
		parent::__construct( 'Gather' );
		$this->specialCollections = array(
				'random' => array(
					'title' => wfMessage( 'gather-collection-random-title' ),
					'description' => wfMessage( 'gather-collection-random-description' ),
					'params' => array(
						'generator' => 'random',
						'grnnamespace' => 0,
						'grnlimit' => 10,
					),
					'limit' => 10,
					'continue' => array( 'r' => 4 ),
				),
				'edited' => array(
					'title' => wfMessage( 'gather-collection-edited-title' ),
					'description' => wfMessage( 'gather-collection-edited-description' ),
					'params' => array(
						'generator' => 'recentchanges',
						'grcnamespace' => 0,
						'grclimit' => 20,
					),
					'limit' => 10,
				),
			);
	}

	/**
	 * Check whether the given route matches a known route.
	 * Return matches if route matches or false when it doesn't.
	 * @param string $subpage
	 * @return boolean|Array
	 */
	public function checkRoute( $subpage ) {
		$matched = preg_match( '/^id\/(?<id>\d+)(\/.*$|$)/', $subpage, $matches );
		return $matched ? $matches : false;
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
			'ext.gather.moderation',
		) );
		$out->addModuleStyles( array(
			// FIXME: This is needed only for the tabs at the top of the page.
			'mobile.special.pagefeed.styles',
			'mediawiki.ui.anchor',
			'mediawiki.ui.icon',
			'ext.gather.icons',
			'ext.gather.styles',
			'ext.gather.menu.icon',
		) );
		if ( !isset( $subpage ) || $subpage === '' ) {
			// Root subpage. For anons will be active collections, for logged in,
			// their own.
			$user = $this->getUser();
			if ( !$user->isAnon() ) {
				$page = SpecialPage::getTitleFor( 'Gather' )->getSubPage( 'by' )
						->getSubPage( $user->getName() );
			} else {
				$page = SpecialPage::getTitleFor( 'Gather' )->getSubPage( 'all' )
						->getSubPage( 'recent' );
			}
			$out->redirect( $page->getLocalUrl() );
		} elseif ( $subpage === 'by' || $subpage === 'by/' ) {
			// User owned collections.
			// For listing own lists, you need to be logged in
			$this->requireLogin( 'gather-anon-view-lists' );
			$user = $this->getUser();
			$url = SpecialPage::getTitleFor( 'Gather' )->getSubPage( 'by' )
					->getSubPage( $user->getName() )->getLocalUrl();
			$out->redirect( $url );
		} elseif ( preg_match( '/^explore\/(?<key>.*)$/', $subpage, $matches ) ) {
			// Special explorable collections
			// /explore/:type & /explore/
			$key = $matches['key'];
			if ( $key ) {
				// /explore/:type -> Show the collection of type :type
				if ( isset( $this->specialCollections[$key] ) ) {
					$args = $this->specialCollections[$key];
					$c = new models\Collection( 0, null, $args['title'], $args['description'] );
					$c = models\Collection::newFromApi( $c, $args['params'], $args['limit'],
						( $args['continue'] ? $args['continue'] : array() ) );
					$c->setUrl( SpecialPage::getTitleFor( 'Gather' )
						->getSubpage( 'explore' )
						->getSubpage( $key )->getLocalUrl() );
					$this->renderCollection( $c );
				} else {
					$this->renderError( new views\NotFound() );
				}
			} else {
				// /explore/ -> List all explorable collections
				$collectionList = new models\CollectionsList();
				foreach ( $this->specialCollections as $key => $definition ) {
					$ci = new models\CollectionInfo( null, null,
						$definition['title'], $definition['description'] );
					$ci->setCount( '∞' );
					$ci->setUrl( SpecialPage::getTitleFor( 'Gather' )
						->getSubpage( 'explore' )
						->getSubpage( $key )->getLocalUrl() );
					$collectionList->add( $ci );
				}
				$this->renderCollectionsList( $collectionList );
			}
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
		} elseif ( preg_match( '/^id\/(?<id>\d+)(\/.*$|$)/', $subpage, $matches ) ) {
			// Collection page
			// /id/:id
			$id = (int)$matches['id'];
			$this->renderUserCollection( $id );
		} elseif ( preg_match( '/^by\/(?<user>[^\/]+)\/(?<id>\d+)$/', $subpage, $matches ) ) {
			// Collection page
			// /by/:user/:id -- Deprecated -- Redirects to /id/:id
			$id = (int)$matches['id'];
			$this->getOutput()->redirect(
				SpecialPage::getTitleFor( 'Gather' )->getSubPage( 'id' )->getSubPage( $id )->getLocalURL()
			);
		} elseif ( preg_match( '/^all(\/(?<cond>[^\/]+))?\/?$/', $subpage, $matches ) ) {
			// All collections. Public or hidden or pending review
			// /all = /all/ = /all/public = /all/public/
			// /all/hidden = /all/hidden/
			// /all/recent = /all/recent/
			// /all/review = /all/review/

			$apiParams = array();
			$displayAsTable = true;

			// Mode defaults to public
			$allowedModes = array( 'public', 'hidden', 'recent', 'review' );
			if ( !isset( $matches['cond'] ) ) {
				$mode = 'public';
			} elseif ( in_array( $matches['cond'], $allowedModes ) ) {
				$mode = $matches['cond'];
			} else {
				$mode = 'error';
			}
			$originalMode = $mode;

			if ( $mode === 'recent' ) {
				// Fancy list of collections with a certain amount of items.

				$displayAsTable = false;
				$apiParams = array(
					'lstminitems' => 4
				);
				// Active is a concrete view of public with different params.
				$mode = 'public';

			} elseif ( $mode === 'hidden' || $mode === 'review' ) {
				// Table list of hidden collections.

				// If user doesn't have permissions, bail out.
				if ( !$this->canHideLists() ) {
					$view = new views\NotFound();
					$this->renderError( $view );
					return;
				}
				$out->addSubtitle( $this->getSubTitle() );
			} elseif ( $mode === 'public' ) {
				// Table list of public collections.

				// If the user has permissions to hide add a link to the /all/hidden
				// view for switching there.
				if ( $this->canHideLists() ) {
					$out->addSubtitle( $this->getSubTitle( true ) );
				}
			} else {
				$this->renderError( new views\NotFound() );
				return;
			}

			$req = $this->getRequest();
			$apiParams = array_merge( $apiParams, $req->getValues() );
			$cList = models\CollectionsList::newFromApi( null, $mode,
				false, $apiParams, $mode, 100 );
			$cList->setMode( $originalMode );
			if ( $displayAsTable ) {
				$this->renderRows( $cList, $mode === 'hidden' ? 'show' : 'hide' );
			} else {
				$this->renderTabs( 0 );
				$this->renderCollectionsList( $cList );
			}
		} else {
			// Unknown subpage
			$this->renderError( new views\NotFound() );
		}

	}

	/**
	 * Render tabs to my collections and public collections
	 *
	 * @param integer [$activeTab] - the tab that is currently selected.
	 */
	protected function renderTabs( $activeTab = 0 ) {
		$currentUser = $this->getUser();
		if ( $currentUser->isAnon() ) {
			$myUrl = SpecialPage::getTitleFor( 'Userlogin' )
				->getLocalUrl(
					array(
						'returnto' => 'Special:Gather',
						'warning' => 'gather-anon-view-lists',
					)
				);
		} else {
			$myUrl = SpecialPage::getTitleFor( 'Gather' )->getSubPage( 'by' )
				->getSubPage( $this->getUser()->getName() )
				->getLocalUrl();
		}
		$data = array(
			'tabs' =>array(
				array(
					'label' => wfMessage( 'gather-all-collections' )->text(),
					'href' => SpecialPage::getTitleFor( 'Gather' )->getSubPage( 'all/recent' )
						->getLocalUrl(),
				),
				array(
					'label' => wfMessage( 'gather-my-collections' )->text(),
					'href' => $myUrl,
				),
			),
		);
		$data['tabs'][$activeTab]["isCurrentTab"] = true;
		$this->render( new views\Tabs(), $data );
	}

	/**
	 * Get subtitle text with a link to show the (un-)hidden collections.
	 * @param boolean $hidden Whether to get a link to show the hidden collections
	 * @return string
	 */
	public function getSubTitle( $hidden = false ) {
		return Linker::link(
			SpecialPage::getTitleFor( 'Gather', ( $hidden ? 'hidden' : false ) ),
			( $hidden ? $this->msg( 'gather-lists-showhidden' ) : $this->msg( 'gather-lists-showvisible' ) )
		);
	}

	/**
	 * Render the special page
	 *
	 * @param CollectionsList $lists
	 * @param string $action hide or show - action to associate with the row.
	 */
	public function renderRows( $cList, $action ) {
		$out = $this->getOutput();
		$out->setPageTitle( wfMessage( 'gather-lists-title' ) );
		$data = array(
			'canHide' => $this->canHideLists(),
			'action' => $action,
			'nextPageUrl' => $cList->getContinueUrl(),
		);

		$view = new views\ReportTable( $this->getUser(), $this->getLanguage(), $cList );
		$view->render( $this->getOutput(), $data );
	}

	/**
	 * Returns if the current user can hide public lists
	 * @return bool
	 */
	private function canHideLists() {
		return $this->getUser()->isAllowed( 'gather-hidelist' );
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
	 * @param int $id collection id
	 */
	public function renderUserCollection( $id ) {
		if ( !is_int( $id ) ) {
			throw new InvalidArgumentException(
				__METHOD__ . ' requires the parameter to be an integer, '
				. gettype( $id ) . ' given.'
			);
		}
		$collection = models\Collection::newFromListsApi( $id, null, $this->getRequest()->getValues() );
		$this->renderCollection( $collection );
	}

	/**
	 * Renders a Collection model
	 * @param models|Collection $collection to render
	 */
	public function renderCollection( $collection ) {
		if ( $collection === null ||
			// If collection is private and current user doesn't own it
			// FIXME: No permissions to visit this. Showing not found ATM.
			( !$collection->isPublic() && !$collection->isOwner( $this->getUser() ) ) ) {
			$this->renderError( new views\NotFound() );
		} else {
			$out = $this->getOutput();
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
	 * @param models\CollectionsList $collectionsList owner of collections
	 */
	public function renderCollectionsList( models\CollectionsList $collectionsList ) {
		$this->render( new views\CollectionsList( $this->getUser(), $collectionsList ) );
	}

	/**
	 * Renders a list of user collections
	 *
	 * @param User $user owner of collections
	 */
	public function renderUserCollectionsList( User $user ) {
		$currentUser = $this->getUser();
		$collectionsList = models\CollectionsList::newFromApi(
			$user, $this->getUser()->equals( $user ), false,
			$this->getRequest()->getValues()
		);
		if ( $collectionsList->getCount() > 0 ) {
			$this->addMetaInformation(
				wfMessage( 'gather-meta-description', $user->getName() ),
				models\Image::getThumbnail( $collectionsList->getFile() )
			);
			if ( $collectionsList->isOwner( $currentUser ) ) {
				$this->renderTabs( 1 );
			}
			$this->render( new views\CollectionsList( $currentUser, $collectionsList ) );
		} else {
			$this->renderError( new views\NoPublic( $user ) );
		}
	}

	/**
	 * Render the special page using a View
	 *
	 * @param views\View $view
	 */
	public function render( $view, $data = array() ) {
		$out = $this->getOutput();
		$this->setHeaders();
		$out->setProperty( 'unstyledContent', true );
		// disable visible page title
		$out->setPageTitle( $view->getTitle() );
		// add title of the actual view to html title-tag
		$out->setHTMLTitle( $view->getHTMLTitle() );
		$view->render( $out, $data );
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
