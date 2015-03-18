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
use AppendIterator;
use ArrayIterator;
use DatabaseBase;
use FormatJson;
use LinkBatch;
use MWException;
use stdClass;
use Title;
use UnwatchAction;
use User;
use ApiPageSet;
use WatchAction;

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

		$isDeletingList = $params['deletelist'];
		$listId = $params['id'];
		$isNew = $listId === null;
		$isWatchlist = $listId === 0;

		// Validate 'deletelist' parameters
		if ( $isDeletingList ) {

			// ID == 0 is a watchlist
			if ( $isWatchlist ) {
				$this->dieUsage( "List #0 (watchlist) may not be deleted", 'badid' );
			}
			if ( $isNew ) {
				$this->dieUsage(
					"List must be identified with {$p}id when {$p}deletelist is used", 'invalidparammix'
				);
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
		} elseif ( $isNew ) {
			if ( $params['remove'] ) {
				$this->dieUsage( "List must be identified with {$p}id when {$p}remove is used",
					'invalidparammix' );
			}
			if ( !$params['label'] ) {
				$this->dieUsage( "List {$p}label must be given for new lists", 'invalidparammix' );
			}
		}
		if ( $isWatchlist && $params['label'] ) {
			$this->dieUsage( "List {$p}label may not be set for the id==0", 'invalidparammix' );
		}

		$dbw = wfGetDB( DB_MASTER, 'api' );

		if ( $isNew || $isWatchlist ) {
			// ACTION: create a new list
			$this->createRow( $dbw, $user, $params, $isWatchlist );
		} else {
			// Find existing list
			$row = $dbw->selectRow( 'gather_list',
				array( 'gl_id', 'gl_user', 'gl_label', 'gl_info' ), array( 'gl_id' => $listId ),
				__METHOD__
			);
			if ( $row === false ) {
				// No database record with the given ID
				$this->dieUsage( "List {$p}id was not found", 'badid' );
			}
			if ( !$isDeletingList ) {
				$this->updateListDb( $dbw, $params, $row );
			} else {
				// Check again - we didn't know it was a watchlist until DB query
				if ( $row->gl_label === '' ) {
					$this->dieUsage( "Watchlist may not be deleted", 'badid' );
				}
				if ( strval( $row->gl_user ) !== strval( $user->getId() ) ) {
					$this->dieUsage( "List {$p}id does not belong to current user", 'permissiondenied' );
				}
				// ACTION: deleting list
				$dbw->delete( 'gather_list', array( 'gl_id' => $row->gl_id ), __METHOD__ );
				$this->getResult()->addValue( null, $this->getModuleName(), array(
					'status' => 'deleted',
				) );
			}
		}

		if ( !$isDeletingList ) {
			// Add the titles to the list (or subscribe with the legacy watchlist)
			$this->processTitles( $params, $user, $listId, $dbw, $isWatchlist );
		}
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
			'perm' => array(
				ApiBase::PARAM_TYPE => array(
					'public',
					'private',
				),
			),
			'description' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
			'image' => array(
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
	 * Given an info object, update it with arguments from params, and return JSON str if changed
	 * @param stdClass $v
	 * @param Array $params
	 * @return string JSON encoded info object in case it changed, or NULL if update is not needed
	 */
	private function updateInfo( stdClass $v, array $params ) {
		$updated = false;

		//
		// Set default
		if ( !property_exists( $v, 'description' ) ) {
			$v->description = '';
		}
		if ( !property_exists( $v, 'perm' ) ) {
			$v->perm = 'private';
		}
		if ( !property_exists( $v, 'image' ) ) {
			$v->image = '';
		}

		//
		// Update from api parameters
		if ( $params['description'] !== null && $v->description !== $params['description'] ) {
			$v->description = $params['description'];
			$updated = true;
		}
		if ( $params['perm'] !== null && $v->perm !== $params['perm'] ) {
			$v->perm = $params['perm'];
			$updated = true;
		}
		if ( $params['image'] !== null && $v->image !== $params['image'] ) {
			if ( $params['image'] === '' ) {
				$v->image = '';
			} else {
				$file = wfFindFile( $params['image'] );
				if ( !$file ) {
					$this->dieUsage( 'Bad image parameter', 'badimage' );
				}
				$v->image = $file->getTitle()->getDBkey();
			}
			$updated = true;
		}

		return $updated ? FormatJson::encode( $v, false, FormatJson::ALL_OK ) : false;
	}

	/**
	 * Check if the info is a public list
	 * @param stdClass $info
	 * @return bool
	 */
	public static function isPublic( $info ) {
		if ( $info && property_exists( $info, 'perm' ) ) {
			return $info->perm === 'public';
		} else {
			return false;
		}
	}

	/**
	 * Create a new database entry
	 * @param DatabaseBase $dbw
	 * @param User $user
	 * @param array $params
	 * @param $isWatchlist
	 */
	private function createRow( DatabaseBase $dbw, User $user, array $params, &$isWatchlist ) {
		$label = $isWatchlist ? '' : $params['label'];
		$info = $this->updateInfo( new stdClass(), $params );
		$createRow = !$isWatchlist || $info;

		if ( $createRow ) {
			$id = $dbw->nextSequenceValue( 'gather_list_gl_id_seq' );
			$dbw->insert( 'gather_list', array(
				'gl_id' => $id,
				'gl_user' => $user->getId(),
				'gl_label' => $label,
				'gl_info' => $info,
			), __METHOD__, 'IGNORE' );
			$id = $dbw->insertId();
		} else {
			$id = 0;
		}

		if ( $id === 0 ) {
			// List already exists, update instead, or might not need it
			$row = $dbw->selectRow( 'gather_list',
				array( 'gl_id', 'gl_user', 'gl_label', 'gl_info' ),
				array( 'gl_user' => $user->getId(), 'gl_label' => $label ),
				__METHOD__
			);
			if ( $row === false ) {
				if ( $createRow ) {
					// If creation failed, the second query should have succeeded
					$this->dieDebug( "List was not found", 'badid' );
				}
				$this->getResult()->addValue( null, $this->getModuleName(), array(
					'status' => 'nochanges',
					'id' => 0,
				) );
			} else {
				$isWatchlist = $row->gl_label === '';
				$this->updateListDb( $dbw, $params, $row );
			}
		} else {
			$this->getResult()->addValue( null, $this->getModuleName(), array(
				'status' => 'created',
				'id' => $id,
			) );
		}
	}

	/**
	 * Update List in the database with the new data from the user params
	 * @param DatabaseBase $dbw
	 * @param array $params User params
	 * @param stdClass $row The db row as it is stored right now
	 * @throws MWException
	 */
	private function updateListDb( DatabaseBase $dbw, array $params, $row ) {
		$update = array();
		if ( $params['label'] !== null && $row->gl_label !== $params['label'] ) {
			$update['gl_label'] = $params['label'];
		}
		$info = self::parseListInfo( $row->gl_info, $row->gl_id, true );
		$json = $this->updateInfo( $info, $params );
		if ( $json ) {
			$update['gl_info'] = $json;
		}
		if ( $update ) {
			// ACTION: update list record
			$dbw->update( 'gather_list', $update, array( 'gl_id' => $row->gl_id ), __METHOD__, 'IGNORE' );
			if ( $dbw->affectedRows() === 0 ) {
				// update failed due to the duplicate label restriction. Report
				$this->dieUsage( 'A list with this label already exists', 'duplicatelabel' );
			}
			$status = 'updated';
		} else {
			$status = 'nochanges';
		}
		$this->getResult()->addValue( null, $this->getModuleName(), array(
			'status' => $status,
			'id' => intval( $row-> gl_id ),
		) );
	}

	/**
	 * Add titles to the list/watchlist (or remove them from the list/watchlist)
	 * @param array $params API params
	 * @param User $user For legacy watchlist only, current user
	 * @param int $listId
	 * @param DatabaseBase $dbw
	 * @param bool $isWatchlist If true, this is a legacy watchlist
	 * @throws MWException
	 * @throws \DBUnexpectedError
	 */
	private function processTitles( array $params, User $user, $listId, DatabaseBase $dbw,
		$isWatchlist ) {

		$this->getResult()->beginContinuation( $params['continue'], array(), array() );

		$pageSet = $this->getPageSet();
		$pageSet->execute();
		$res = $pageSet->getInvalidTitlesAndRevisions( array(
			'invalidTitles',
			'special',
			'missingIds',
			'missingRevIds',
			'interwikiTitles'
		) );

		$titles = new AppendIterator();
		$titles->append( new ArrayIterator( $pageSet->getGoodTitles() ) );
		$titles->append( new ArrayIterator( $pageSet->getMissingTitles() ) );

		$isRemoving =$params['remove'];
		if ( $isWatchlist ) {
			// Legacy watchlist - watch/unwatch
			foreach ( $titles as $title ) {
				$res[] = $this->watchTitle( $title, $user, $isRemoving );
			}
		} elseif ( !$isRemoving ) {
			// Insert titles into the list
			// For now, insert at the end.
			// TODO: support "insertafter=title" parameter
			$order =
				$dbw->selectField( 'gather_list_item', 'max(gli_order)',
					array( 'gli_gl_id' => $listId ), __METHOD__ );
			if ( $order === false ) {
				$this->dieDebug( __METHOD__, "max(gli_order) failed for id $listId" );
			}
			$order = !$order ? 1 : $order + 1;

			$rows = array();
			foreach ( $titles as $title ) {
				$r = array( 'title' => $title->getPrefixedText() );
				if ( !$title->isWatchable() ) {
					$r['watchable'] = 0;
				} else {
					$r['added'] = '';
					$rows[] = array(
						'gli_gl_id' => $listId,
						'gli_namespace' => $title->getNamespace(),
						'gli_title' => $title->getDBkey(),
						'gli_order' => $order,
					);
					$order += 1;
				}
				$res[] = $r;
			}
			$dbw->insert( 'gather_list_item', $rows, __METHOD__, 'IGNORE' );
		} else {
			// Remove titles from the list
			$linkBatch = new LinkBatch();
			foreach ( $titles as $title ) {
				$r = array( 'title' => $title->getPrefixedText() );
				if ( !$title->isWatchable() ) {
					$r['watchable'] = 0;
				} else {
					$r['removed'] = '';
					$linkBatch->addObj( $title );
				}
				$res[] = $r;
			}
			$set = $linkBatch->constructSet( 'gli', $dbw );
			if ( $set ) {
				$dbw->delete( 'gather_list_item', array(
					'gli_gl_id' => $listId,
					$set
				) );
			}
		}

		$this->getResult()->setIndexedTagName( $res, 'w' );
		$this->getResult()->addValue( $this->getModuleName(), 'pages', $res );
		$this->getResult()->endContinuation();
	}

	/**
	 * @param Title $title
	 * @param User $user
	 * @param bool $remove
	 * @return array
	 * @throws MWException
	 */
	private function watchTitle( Title $title, User $user, $remove ) {
		$prefixedText = $title->getPrefixedText();
		$res = array( 'title' => $prefixedText );

		if ( !$title->isWatchable() ) {
			$res['watchable'] = 0;
		} else {
			if ( $remove ) {
				$status = UnwatchAction::doUnwatch( $title, $user );
			} else {
				$status = WatchAction::doWatch( $title, $user );
			}
			if ( $status->isOK() ) {
				$res[$remove ? 'removed' : 'added'] = '';
				$res['message'] =
					$this->msg( $remove ? 'removedwatchtext' : 'addedwatchtext', $prefixedText )
						->title( $title )
						->parseAsBlock();
			} else {
				$res['error'] = $this->getErrorFromStatus( $status );
			}
		}
		return $res;
	}

	/**
	 * Parse Info blob string into a stdClass
	 * @param string $infoBlob
	 * @param int $listId
	 * @param bool $throwOnError
	 * @return stdClass
	 * @throws MWException
	 */
	public static function parseListInfo( $infoBlob, $listId, $throwOnError ) {
		if ( $infoBlob !== null && $infoBlob !== '' ) {
			$info = FormatJson::parse( $infoBlob );
			if ( $info->isOK() ) {
				return $info->getValue();
			}
			if ( $throwOnError ) {
				throw new MWException( 'Unable to parse ID=' . $listId );
			}
		}
		return new stdClass();
	}
}
