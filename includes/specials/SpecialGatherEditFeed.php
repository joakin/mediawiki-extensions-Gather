<?php
/**
 * SpecialGatherEditFeed.php
 */
namespace Gather;

use User;
use Html;
use SpecialPage;
use Gather\models;
use Gather\views\helpers\CSS;

/**
 * Implements the Watchlist special page
 */
class SpecialGatherEditFeed extends SpecialPage {
	// integer id of selected collection
	private $id = 0;

	/** @var string $filter Saves the actual used filter in feed view */
	private $filter;

	/**
	 * Construct function
	 */
	public function __construct() {
		parent::__construct( 'GatherEditFeed' );
	}

	/**
	 * Render the special page
	 * @param string $par parameter submitted as subpage
	 */
	function execute( $par ) {
		// Anons don't get a watchlist
		$this->requireLogin( 'watchlistanontext' );

		$user = $this->getUser();
		$out = $this->getOutput();
		$out->setPageTitle( $this->msg( 'gather-editfeed-title' ) );
		$req = $this->getRequest();
		$this->filter = $req->getVal( 'filter', 'all' );
		$this->id = $req->getInt( 'collection-id', 0 );
		$out->addHtml( $this->getHeader( $user, $this->id, $this->getPageTitle()->getLocalUrl() ) );
		$out->addModuleStyles( array(
			'mediawiki.ui.input',
			'ext.gather.styles',
		) );
		$out->setProperty( 'unstyledContent', true );

		$this->showRecentChangesHeader();
		$user = $this->getUser();
		$id = (int)$this->id;
		$feed = models\CollectionFeed::newFromDatabase( $user, $id, $this->filter );
		$feedView = new views\CollectionFeed( $feed, $this->getLanguage() );
		$feedView->render( $out );
	}

	/**
	 * Get the header for the watchlist page
	 * @param User $user
	 * @param int $id
	 * @param string $actionUrl
	 * @return string Parsed HTML
	 */
	private function getHeader( User $user, $id, $actionUrl ) {
		$html = Html::openElement( 'form', array( 'action' => $actionUrl ) )
			. Html::openElement( 'select', array(
				'name' => 'collection-id',
				'class' => 'mw-ui-input mw-ui-input-inline',
			) );
		$collections = models\CollectionsList::newFromApi( $user, true );
		foreach ( $collections as $collection ) {
			$attrs = array(
				'value' => $collection->getId(),
			);
			if ( $collection->getId() === $id ) {
				$attrs['selected'] = true;
			}
			$html .= Html::element( 'option', $attrs, $collection->getTitle() );
		}

		$html .= Html::closeElement( 'select' )
			. Html::submitButton( wfMessage( 'gather-editfeed-show' ), array(
				'class' => CSS::buttonClass( 'progressive' ) ) )
			. Html::closeElement( 'form' );
		return '<div class="gather-edit-feed-header content-header">' . $html . '</div>';
	}

	/**
	 * Render "second" header for filter in feed view of watchlist
	 */
	function showRecentChangesHeader() {
		$filters = array(
			'all' => 'mobile-frontend-watchlist-filter-all',
			'articles' => 'mobile-frontend-watchlist-filter-articles',
			'talk' => 'mobile-frontend-watchlist-filter-talk',
			'other' => 'mobile-frontend-watchlist-filter-other',
		);
		$out = $this->getOutput();

		$out->addHtml(
			Html::openElement( 'ul', array( 'class' => 'gather-ui-tabs' ) )
		);

		foreach ( $filters as $filter => $msg ) {
			$itemAttrs = array();
			if ( $filter === $this->filter ) {
				$itemAttrs['class'] = 'selected';
			}
			$linkAttrs = array(
				'href' => $this->getPageTitle()->getLocalUrl(
					array(
						'filter' => $filter,
						'collection-id' => $this->id,
					)
				)
			);
			$out->addHtml(
				Html::openElement( 'li', $itemAttrs ) .
				Html::element( 'a', $linkAttrs, $this->msg( $msg )->plain() ) .
				Html::closeElement( 'li' )
			);
		}

		$out->addHtml(
			Html::closeElement( 'ul' )
		);
	}
}
