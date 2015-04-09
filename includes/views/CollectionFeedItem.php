<?php
/**
 * CollectionFeedItem.php
 */

namespace Gather\views;

use Gather\models;
use Gather\views\helpers\CSS;
use User;
use Html;
use ChangesList;
use Language;
use SpecialPage;

/**
 * View for an item card in a collection feed
 */
class CollectionFeedItem extends View {
	/**
	 * @var models\CollectionFeedItem Item that is going to be shown in this view
	 */
	protected $item;

	/**
	 * Constructor
	 * @param models\CollectionFeedItem $item
	 * @param Language $language
	 */
	public function __construct( models\CollectionFeedItem $item, Language $language ) {
		$this->item = $item;
		$this->language = $language;
	}

	/**
	 * Returns title of collection page
	 * @returns string collection page title
	 */
	public function getTitle() {
		return $this->item->getTitle()->getText();
	}

	/**
	 * @inheritdoc
	 */
	protected function getHtml( $data = array() ) {
		$lang = $this->language;
		$item = $this->item;
		$title = $item->getTitle();
		$bytes = $item->getBytes();
		$isMinor = $item->isMinor();
		$user = $item->getUser();
		$username = $item->getUsername();
		$comment = $item->getEditSummary();
		$ts = $item->getTimestamp();
		$diffLink = $item->getChangeUrl();

		$html = Html::openElement( 'li', array( 'class' => 'page-summary' ) );
		$html .= Html::element( 'p', array( 'class' => 'mw-changeslist-date' ),
			$lang->userTime( $ts, $user ) );

		if ( $diffLink ) {
			$html .= Html::openElement( 'a', array( 'href' => $diffLink, 'class' => 'title' ) );
		} else {
			$html .= Html::openElement( 'div', array( 'class' => 'title' ) );
		}

		if ( $title ) {
			$html .= Html::element( 'h3', array(), $title->getPrefixedText() );
		}
		if ( $diffLink ) {
			$html .= Html::closeElement( 'a' );
		} else {
			$html .= Html::closeElement( 'div' );
		}

		if ( $bytes ) {
			$formattedBytes = $lang->formatNum( $bytes );
			if ( $bytes > 0 ) {
				$formattedBytes = '+' . $formattedBytes;
				$bytesClass = 'mw-plusminus-pos';
			} else {
				$bytesClass = 'mw-plusminus-neg';
			}
			$html .= Html::element(
				'p',
				array(
					'class' => $bytesClass,
					'dir' => 'ltr',
				),
				$formattedBytes
			);
		}

		if ( $username ) {
			$usernameClass = self::getUsernameCSSClasses( $user );
			$userPage = $user->getUserPage();

			$html .= Html::element( 'a', array( 'href' => $userPage->getLocalUrl(),
				'class' => $usernameClass ), $username );
			$html .= Html::openElement( 'span', array( 'class' => 'mw-usertoollinks' ) ) .
				Html::element( 'a', array(
					'href' => $userPage->getTalkPage()->getLocalUrl(),
					'class' => $usernameClass ), wfMessage( 'talkpagelinktext' ) ) .
				Html::element( 'a', array(
					'href' => SpecialPage::getTitleFor( 'Contributions', $username )->getLocalUrl(),
					'class' => $usernameClass ), wfMessage( 'contribslink' ) ) .
				Html::closeElement( 'span' );
		}

		if ( $comment ) {
			$html .= Html::element(
				'p', array( 'class' => 'edit-summary component truncated-text multi-line two-line' ), $comment
			);
		}

		if ( $isMinor ) {
			$html .= ChangesList::flag( 'minor' );
		}

		$html .= Html::openElement( 'div', array( 'class' => 'list-thumb' ) );
		$html .= Html::closeElement( 'div' );
		$html .= Html::closeElement( 'li' );
		return $html;
	}

	/**
	 * @param User $user
	 * @return string
	 */
	public static function getUsernameCSSClasses( User $user ) {
		$isAnon = $user->isAnon();
		if ( $isAnon ) {
			return CSS::iconClass( 'anonymous', 'before', 'icon-16px mw-userlink mw-anonuserlink' );
		} else {
			return CSS::iconClass( 'user', 'before', 'icon-16px mw-userlink' );
		}
	}
}
