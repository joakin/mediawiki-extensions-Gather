<?php
/**
 *
 *
 * Created on Mar 6, 2015
 *
 * Copyright © 2015 Yuri Astrakhan "<Firstname><Lastname>@gmail.com",
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 */

namespace Gather\api;

use ApiBase;
use FormatJson;
use JsonContent;
use MWException;
use Status;
use stdClass;
use Title;
use UnwatchAction;
use User;
use ApiPageSet;
use WatchAction;
use WikiPage;

/**
 * API module to allow users to manage lists
 *
 * @ingroup API
 */
class ApiEditList extends ApiBase {
	private $mPageSet = null;

	/**
	 * @throws \UsageException
	 */
	public function execute() {

		$params = $this->extractRequestParams();

		if ( $params['label'] !== null ) {
			$params['label'] = trim( $params['label'] );
			if ( $params['label'] === '' ) {
				$this->dieUsage( 'If given, label must not be empty', 'badlabel' );
			}
		}

		$user = $this->getUser(); // TBD: We might want to allow other users with getWatchlistUser()

		if ( !$user->isLoggedIn() ) {
			$this->dieUsage( 'You must be logged-in to have a list', 'notloggedin' );
		}
		if ( !$user->isAllowed( 'editmywatchlist' ) ) {
			$this->dieUsage( 'You don\'t have permission to edit your list',
				'permissiondenied' );
		}

		$pageSet = $this->getPageSet();
		$p = $this->getModulePrefix();

		$remove = $params['remove'];
		$id = $params['id'];
		$isNew = $id === null;

		// Validate 'deletelist' parameters
		if ( $params['deletelist'] ) {

			// ID == 0 is a watchlist
			if ( $id === 0 ) {
				$this->dieUsage( "List #0 (watchlist) may not be deleted", 'badid' );
			}
			if ( $isNew ) {
				$this->dieUsage( "List must be identified with {$p}id when {$p}deletelist is used", 'invalidparammix' );
			}

			// For deletelist, disallow all parameters except those unset
			$tmp = $params + $pageSet->extractRequestParams();
			unset( $tmp['deletelist'] );
			unset( $tmp['id'] );
			unset( $tmp['token'] );
			$extraParams =
				array_keys( array_filter( $tmp, function ( $x ) {
					return $x !== null && $x !== false;
				} ) );
			if ( $extraParams ) {
				$this->dieUsage( "The parameter {$p}deletelist must not be used with " .
								 implode( ", ", $extraParams ), 'invalidparammix' );
			}
		}

		$manifest = self::loadManifest( $user );

		/** @var stdClass $list */
		$list = null;

		if ( $isNew ) {
			if ( $remove ) {
				$this->dieUsage( "List must be identified with {$p}id when {$p}remove is used", 'invalidparammix' );
			}
			if ( !$params['label'] ) {
				$this->dieUsage( "List {$p}label must be given for new lists", 'invalidparammix' );
			}

			// ACTION: add new list to manifest.
			// work out a new id
			$id = 1;
			if ( $manifest ) {
				foreach ( $manifest as $c ) {
					if ( $c->id > $id ) {
						$id = $c->id + 1;
					}
				}
			}
			$list = $this->createList( $id, $params, $user );
			$manifest[] = $list;
		} else {
			$list = $this->findList( $manifest, $id, $user );
			if ( $list === null ) {
				$this->dieUsage( "List {$p}id was not found", 'badid' );
			}

			// ACTION: update existing list
			if ( $params['label'] !== null ) {
				$list->title = $params['label'];
			}
			if ( $params['description'] !== null ) {
				$list->description = $params['description'];
			}
		}

		if ( $params['deletelist'] ) {
			// ACTION: drop the list from the manifest
			$manifest = array_filter( $manifest, function ( $x ) use ( $id ) {
				return $x->id !== $id;
			} );
		} else {

			$this->getResult()->beginContinuation( $params['continue'], array(), array() );

			$pageSet->execute();
			$res = $pageSet->getInvalidTitlesAndRevisions( array(
				'invalidTitles',
				'special',
				'missingIds',
				'missingRevIds',
				'interwikiTitles'
			) );

			// If 0, use user's watchlist instead of our temp list
			$col = $id === 0 ? null : $list;

			foreach ( $pageSet->getMissingTitles() as $title ) {
				$r = $this->watchTitle( $col, $title, $user, $remove );
				$r['missing'] = 1;
				$res[] = $r;
			}

			foreach ( $pageSet->getGoodTitles() as $title ) {
				$r = $this->watchTitle( $col, $title, $user, $remove );
				$res[] = $r;
			}
			$this->getResult()->setIndexedTagName( $res, 'w' );
			$this->getResult()->addValue( $this->getModuleName(), 'pages', $res );
			$this->getResult()->endContinuation();

			$list->count = count( $list->items );
		}

		self::save( $user, $manifest );
	}

	/**
	 * @param null|stdClass $list
	 * @param Title $title
	 * @param User $user
	 * @param bool $remove
	 * @return array
	 * @throws MWException
	 */
	private function watchTitle( $list, Title $title, User $user, $remove ) {
		$titleStr = $title->getPrefixedText();

		if ( !$title->isWatchable() ) {
			return array( 'title' => $titleStr, 'watchable' => 0 );
		}

		$res = array( 'title' => $titleStr );

		if ( $list ) {
			$key = array_search( $titleStr, $list->items );
			$status = Status::newGood();
			if ( $remove && $key !== false ) {
				unset( $list->items[$key] );
				// Must reset keys, otherwise will get converted to an object on save
				$list->items = array_values( $list->items );
			} elseif ( !$remove && $key === false ) {
				$list->items[] = $titleStr;
			}
		} else {
			if ( $remove ) {
				$status = UnwatchAction::doUnwatch( $title, $user );
			} else {
				$status = WatchAction::doWatch( $title, $user );
			}
		}

		if ( $status->isOK() ) {
			$res[$remove ? 'removed' : 'added'] = '';
			$res['message'] = $this->msg(
				$remove ? 'removedwatchtext' : 'addedwatchtext',
				$title->getPrefixedText()
			)->title( $title )->parseAsBlock();
		} else {
			$res['error'] = $this->getErrorFromStatus( $status );
		}

		return $res;
	}

	/**
	 * Get a cached instance of an ApiPageSet object
	 * @return ApiPageSet
	 */
	private function getPageSet() {
		if ( $this->mPageSet === null ) {
			$this->mPageSet = new ApiPageSet( $this );
		}

		return $this->mPageSet;
	}

	public function mustBePosted() {
		return true;
	}

	public function isWriteMode() {
		return true;
	}

	public function needsToken() {
		return 'watch';
	}

	public function getAllowedParams( $flags = 0 ) {
		$result = array(
			'id' => array(
				ApiBase::PARAM_TYPE => 'integer',
			),
			'label' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
			'description' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
			'remove' => false,
			'deletelist' => false,
			'continue' => array(
				ApiBase::PARAM_HELP_MSG => 'api-help-param-continue',
			),
		);
		if ( $flags ) {
			$result += $this->getPageSet()->getFinalParams( $flags );
		}

		return $result;
	}

	public function getHelpUrls() {
		return '//www.mediawiki.org/wiki/Extension:Gather';
	}

	/**
	 * Temporary function to get data from a JSON blob stored on the user's page
	 * @param User $user
	 * @return array
	 * @throws MWException
	 */
	public static function loadManifest( $user ) {
		$title = self::getStorageTitle( $user );
		$page = WikiPage::factory( $title );
		if ( $page->exists() ) {
			$content = $page->getContent();
			if ( method_exists( $content, 'getData' ) ) {
				$status = $content->getData();
			} else {
				$status = FormatJson::parse( $content->getNativeData() );
			}
			if ( !$status->isOK() ) {
				throw new MWException( 'Internal error in ' . __METHOD__ . ' loading ' . $title->getFullText() . ' : ' . $status->getMessage() );
			}
			return $status->getValue();
		} else {
			// Page doesn't exist, return empty data
			return array();
		}
	}

	/**
	 * Temporary function to save data to the JSON blob stored on the user's page
	 * @param User $user
	 * @param Array $manifest representation of all the user's existing lists.
	 * @return Status
	 */
	private function save( User $user, $manifest ) {
		$title = self::getStorageTitle( $user );
		$page = WikiPage::factory( $title );
		$content = new JsonContent( FormatJson::encode( $manifest, FormatJson::ALL_OK ) );
		return $page->doEditContent( $content, 'ApiEditList.php' );
	}

	/**
	 * Get formatted title of the page that contains the manifest
	 * @param User $user
	 * @return Title
	 */
	private static function getStorageTitle( User $user ) {
		$title = $user->getName() . '/GatherCollections.json';
		return Title::makeTitleSafe( NS_USER, $title );
	}

	/**
	 * Returns representation of a new list with the given id and parameters.
	 * @param Integer $id
	 * @param Array $params
	 * @param User $user
	 * @return stdClass
	 */
	private static function createList( $id, array $params, User $user ) {
		$list = new stdClass();
		$list->id = $id;
		$list->owner = $user->getName();
		$list->title = $params['label'];
		$list->description = $params['description'];
		$list->public = true;
		$list->image = null;
		$list->items = array();
		$list->count = 0;
		return $list;
	}

	/**
	 * Retrieve the list from the manifest of all the users existing
	 * list using the given id, or null if not found.
	 * @param Array $manifest
	 * @param Integer $id
	 * @param User $user
	 * @return null|stdClass
	 */
	public static function findList( &$manifest, $id, User $user ) {
		// Find the list with the given id.
		foreach ( $manifest as $c ) {
			if ( $c->id === $id ) {
				return $c;
			}
		}
		if ( $id === 0 ) {
			// watchlist metadata should always exist
			$params = array(
				'label' => wfMessage( 'mywatchlist' )->text(),
				'description' => '',
			);
			$c = self::createList( 0, $params, $user );
			$manifest[] = $c;
			return $c;
		}
		return null;
	}
}
