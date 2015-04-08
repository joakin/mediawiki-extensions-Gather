<?php
/**
 * CollectionFeed.php
 */
namespace Gather\views;

use Gather\models;
use Html;

/**
 * Renders a collection in feed form.
 */
class CollectionFeed extends View {

	/**
	 * @param models\CollectionFeed $feed
	 * @param \Language $language
	 */
	public function __construct( models\CollectionFeed $feed, $language ) {
		$this->feed = $feed;
		$this->language = $language;
	}

	/**
	 * @inheritdoc
	 */
	public function getTitle() {
		return '';
	}

	/**
	 * Returns the html for an empty feed.
	 *
	 * @return string HTML
	 */
	private function getEmptyCollectionMessage() {
		return Html::element( 'div', array( 'class' => 'content' ),
			wfMessage( 'gather-editfeed-empty' ) );
	}

	/**
	 * Renders a date header when necessary.
	 * @param string $date The date of the current item
	 * @return string
	 */
	protected function listHeaders( $date ) {
		$html = '';
		if ( !isset( $this->lastDate ) || $date !== $this->lastDate ) {
			if ( isset( $this->lastDate ) ) {
				$html .= Html::closeElement( 'ul' );
			}
			$html .=
				Html::element( 'h2', array( 'class' => 'list-header' ), $date ) .
				Html::openElement( 'ul', array( 'class' => 'page-list side-list' ) );
		}
		$this->lastDate = $date;
		return $html;
	}

	/**
	 * Returns the html for a feed.
	 *
	 * @return string HTML
	 */
	private function getCollectionItems() {
		$language = $this->language;
		$html = '';
		foreach ( $this->feed as $item ) {
			$date = $language->userDate( $item->getTimestamp(), $item->getUser() );
			$html .= $this->listHeaders( $date );
			$view = new CollectionFeedItem( $item, $language );
			$html .= $view->getHtml();
		}
		return $html;
	}

	/**
	 * @inheritdoc
	 */
	public function getHtml() {
		$html = Html::openElement( 'div', array( 'class' => 'mw-changeslist' ) );
		if ( $this->feed->getCount() > 0 ) {
			$html .= $this->getCollectionItems();
		} else {
			$html .= $this->getEmptyCollectionMessage();
		}
		return $html . Html::closeElement( 'div' );
	}

}
