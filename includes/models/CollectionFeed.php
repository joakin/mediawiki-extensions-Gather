<?php

/**
 * Collection.php
 */

namespace Gather\models;

use IteratorAggregate;
use ArrayIterator;
use User;
use Title;
use ResultWrapper;
use Linker;
use Sanitizer;
use MWTimestamp;
use IP;
use ChangeTags;
use Hooks;

/**
 * A collection with a list of items, which are represented by the CollectionFeedItem class.
 */
class CollectionFeed implements IteratorAggregate {
	const LIMIT = 50;

	/**
	 * The internal collection of items.
	 *
	 * @var CollectionFeedItem[]
	 */
	protected $items = array();

	/**
	 * Adds a item to the collection.
	 *
	 * @param CollectionFeedItem $item
	 */
	public function add( CollectionFeedItem $item ) {
		$this->items[] = $item;
	}

	/**
	 * Gets the iterator for the internal array
	 *
	 * @return ArrayIterator
	 */
	public function getIterator() {
		return new ArrayIterator( $this->items );
	}

	/**
	 * @return array list of feed
	 */
	public function getItems() {
		return $this->items;
	}

	/**
	 * Returns items count
	 *
	 * @return int count of items in feed
	 */
	public function getCount() {
		return count( $this->items );
	}

	/**
	 * Formats a comment of revision via Linker:formatComment and Sanitizer::stripAllTags
	 * @param string $comment the comment
	 * @param string $title the title object of comments page
	 * @return string formatted comment
	 */
	public static function formatComment( $comment, $title ) {
		if ( $comment !== '' ) {
			$comment = Linker::formatComment( $comment, $title );
			// flatten back to text
			$comment = Sanitizer::stripAllTags( $comment );
		}
		return $comment;
	}

	/**
	 * @param ResultWrapper $res
	 * @return CollectionFeed a collection
	 */
	public static function newFromResultWrapper( ResultWrapper $res ) {
		$feed = new CollectionFeed();
		foreach ( $res as $row ) {
			$user = User::newFromId( $row->rc_user );
			$title = Title::makeTitle( $row->rc_namespace, $row->rc_title );
			$comment = self::formatComment( $row->rc_comment, $title );
			$ts = new MWTimestamp( $row->rc_timestamp );
			$revId = $row->rc_this_oldid;
			$bytes = $row->rc_new_len - $row->rc_old_len;
			$isMinor = $row->rc_minor != 0;

			if ( !$row->rc_deleted ) {
				$item = new CollectionFeedItem( $title, $user, $comment, $ts, $revId, $isMinor, $bytes );
				if ( $row->rc_user == 0 ) {
					$item->setUsername( IP::prettifyIP( $row->rc_user_text ) );
				}
				$feed->add( $item );
			}
		}
		return $feed;
	}

	/**
	 * @param User $user
	 * @param Integer $id of collection
	 * @param string $ns namespace keyword [articles,all,talk,other]
	 * @return CollectionFeed a collection
	 */
	public static function newFromDatabase( $user, $id, $ns ) {
		$dbr = wfGetDB( DB_SLAVE, 'watchlist' );
		$res = $dbr->select( 'gather_list', 'count(*)',
			array(
				"gl_perm = 'public' OR gl_id = " . $dbr->addQuotes( $id ),
			),
			__METHOD__, array( 'LIMIT' => 1 ) );
		// permission not granted.
		if ( !$res || count( $res ) === 0 ) {
			return false;
		}

		$conds = array();
		$column = 'rc_namespace';
		switch ( $ns ) {
			case 'all':
				// no-op
				break;
			case 'articles':
				// @fixme content namespaces
				$conds[] = "$column = 0"; // Has to be unquoted or MySQL will filesort for wl_namespace
				break;
			case 'talk':
				// check project talk, user talk and talk pages
				$conds[] = "$column IN (1, 3, 5)";
				break;
			case 'other':
				// @fixme
				$conds[] = "$column NOT IN (0, 1, 3, 5)";
				break;
		}

		$fields = array( $dbr->tableName( 'recentchanges' ) . '.*' );
		if ( $id === 0 ) {
			$joinTable = 'watchlist';
			$joinConds = array(
				'watchlist' => array(
					'INNER JOIN',
					array(
						'wl_user' => $user->getId(),
						'wl_namespace=rc_namespace',
						'wl_title=rc_title',
						// FIXME: Filter out wikidata changes which currently show as anonymous (see bug 49315)
						'rc_type!=' . RC_EXTERNAL,
					),
				),
			);
		} else {
			$joinTable = 'gather_list_item';
			// FIXME: Don't expose private lists here
			$joinConds = array(
				'gather_list_item' => array(
					'INNER JOIN',
					array(
						'gli_gl_id' => $id,
						'gli_namespace=rc_namespace',
						'gli_title=rc_title',
						// FIXME: Filter out wikidata changes which currently show as anonymous (see bug 49315)
						'rc_type!=' . RC_EXTERNAL,
					),
				),
			);
		}

		$tables = array( 'recentchanges', $joinTable );
		$options = array( 'ORDER BY' => 'rc_timestamp DESC' );
		$options['LIMIT'] = self::LIMIT;

		$canRollback = $user->isAllowed( 'rollback' );
		if ( $canRollback ) {
			$tables[] = 'page';
			$joinConds['page'] = array( 'LEFT JOIN', 'rc_cur_id=page_id' );
			$fields[] = 'page_latest';
		}

		ChangeTags::modifyDisplayQuery( $tables, $fields, $conds, $joinConds, $options, '' );
		// Until 1.22, MediaWiki used an array here. Since 1.23 (Iec4aab87), it uses a FormOptions
		// object (which implements array-like interface ArrayAccess).
		// Let's keep using an array and hope any new extensions are compatible with both styles...
		$values = array();
		Hooks::run(
			'SpecialWatchlistQuery',
			array( &$conds, &$tables, &$joinConds, &$fields, &$values )
		);

		$res = $dbr->select( $tables, $fields, $conds, __METHOD__, $options, $joinConds );
		return self::newFromResultWrapper( $res );
	}
}
