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

use Gather\models\Collection;
use ManualLogEntry;
use AbuseFilter;
use AbuseFilterVariableHolder;
use ApiBase;
use ApiContinuationManager;
use ApiResult;
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
use SpecialPage;
use EchoEvent;

/**
 * API module to allow users to manage lists
 *
 * @ingroup API
 */
class ApiEditList extends ApiBase {
	private $mPageSet = null;

	const PERM_PRIVATE = 0;
	const PERM_PUBLIC = 1;
	const PERM_OVERRIDE_NONE = 0;
	const PERM_OVERRIDE_HIDDEN = 1; // like private but cannot be published by owner
	const PERM_OVERRIDE_APPROVED = 2; // like public but cannot be autohidden

	/**
	 * Maps API actions (perm parameter values) to PERM_* constants (DB values);
	 * also DB values to lists API properties
	 * @var array
	 */
	public static $permMap = array(
		'public' => self::PERM_PUBLIC,
		'private' => self::PERM_PRIVATE,
	);

	/**
	 * Maps API actions (mode parameter values) to PERM_OVERRIDE_* constants (DB values)
	 * @var array
	 */
	public static $permOverrideMap = array(
		'hidelist' => self::PERM_OVERRIDE_HIDDEN,
		'showlist' => self::PERM_OVERRIDE_NONE,
		'approve' => self::PERM_OVERRIDE_APPROVED,
	);

	/**
	 * Maps API actions (mode parameter values) to Echo notification types
	 * @var array
	 */
	public static $permOverrideNotificationMap = array(
		'hidelist' => 'gather-hide',
		'showlist' => 'gather-unhide',
		'approve' => 'gather-approve',
	);


	/**
	 * @param string $type Log type
	 * @param string $action Log action
	 * @param Title|null $title Title object or null
	 * @param \Skin|null $skin Skin object or null. If null, we want to use the wiki
	 *   content language, since that will go to the IRC feed.
	 * @param array $params Parameters
	 * @return string
	 */
	public static function getGatherLogFormattedString( $type, $action, $title, $skin, $params ) {
		return wfMessage( 'gather-checkuser-log-action' )
			->rawParams( $params['action'], '[[' . $title->getPrefixedText() . ']]' )->parse();
	}

	/**
	 * @throws \UsageException
	 */
	public function execute() {

		$params = $this->extractRequestParams();
		if ( $params['label'] !== null ) {
			$label = trim( $params['label'] );
			$params['label'] = $label;
		}

		$this->checkPermissions( $params );

		$p = $this->getModulePrefix();
		$user = $this->getUser(); // TBD: We might want to allow other users with getWatchlistUser()
		$mode = $params['mode'];
		$listId = $params['id'];
		$isNew = $listId === null;
		$isWatchlist = $listId === 0;

		$dbw = wfGetDB( DB_MASTER, 'api' );
		$logEventName = false;

		if ( $isNew || $isWatchlist ) {
			// ACTION: create a new list
			$listId = $this->createRow( $dbw, $user, $params, $isWatchlist );
			$logEventName = 'new';
		} else {
			// Find existing list
			$row = $this->getListRow( $params, $dbw, array( 'gl_id' => $listId ) );
			if ( !$row ) {
				$this->dieUsage( "List {$p}id was not found", 'badid' );
			}
			$isWatchlist = $row->gl_label === '';
			switch ( $mode ) {
				case 'update':
				case 'remove':
					// ACTION: update list
					$this->updateListDb( $dbw, $params, $row );
					$logEventName = $mode;
					break;
				case 'deletelist':
					// ACTION: delete list (items + list itself)
					$dbw->delete( 'gather_list_item', array( 'gli_gl_id' => $listId ), __METHOD__ );
					$dbw->delete( 'gather_list_flag', array( 'glf_gl_id' => $listId ), __METHOD__ );
					$dbw->delete( 'gather_list', array( 'gl_id' => $listId ), __METHOD__ );
					$this->setResultStatus( $listId, 'deleted' );
					$logEventName = $mode;
					break;
				case 'hidelist':
				case 'showlist':
				case 'approve':
					$update = array( 'gl_flag_count' => 0, 'gl_needs_review' => 0 );
					$permOverride = self::$permOverrideMap[$mode];
					$update['gl_perm_override'] = $permOverride;
					$update = array_diff_assoc( $update, (array)$row ); // remove fields with no changes

					$dbw->begin( __METHOD__ );
					$this->updateRow( $dbw, $row, $update );
					if ( $dbw->affectedRows() ) {
						$logEventName = $mode;
					}
					$dbw->update( 'gather_list_flag',
						array( 'glf_reviewed' => 1 ),
						array( 'glf_gl_id' => $listId ),
					__METHOD__ );
					$dbw->commit( __METHOD__ );

					if ( $logEventName ) {
						// do echo notification, unless the action was a noop
						if ( class_exists( 'EchoEvent' ) && in_array( $mode, self::$permOverrideNotificationMap ) ) {
							$eventType = self::$permOverrideNotificationMap[$mode];
							// FIXME: better long term solution for generating collection urls needed
							// Model currently handles it which is not accessible from here
							$collectionTitle = SpecialPage::getTitleFor( 'Gather' )
								->getSubpage( 'id' )
								->getSubpage( $row->gl_id )
								->getSubpage( $row->gl_label );

							EchoEvent::create( array(
								'type' => $eventType,
								'title' => $collectionTitle,
								'extra' => array(
									'collection-owner-id' => $row->gl_user,
								),
								'agent' => $user,
							) );
						}
					}
					break;
				case 'flag':
					$dbw->begin( __METHOD__ );
					// lock list to avoid race condition with a show/hide/approve
					$dbw->select( 'gather_list', 'gl_id', array( 'gl_id' => $listId ), __METHOD__,
						array( 'FOR UPDATE' ) );
					$dbw->insert( 'gather_list_flag',
						array(
							'glf_user_id' => $user->getId(),
							'glf_user_ip' => $user->getId() ? '' : $this->getRequest()->getIP(),
							'glf_gl_id' => $listId,
						),
						__METHOD__,
						array( 'IGNORE' )
					);
					if ( !$dbw->affectedRows() ) {
						$dbw->rollback( __METHOD__ );
						$this->dieUsage( "List already flagged by user",
							'alreadyflagged' );
					}
					$dbw->update( 'gather_list',
						array( 'gl_flag_count = gl_flag_count + 1' ),
						array( 'gl_id' => $listId ),
						__METHOD__ );
					$dbw->commit( __METHOD__ );
					$this->setResultStatus( $row->gl_id, 'flagged' );
					$logEventName = $mode;
					break;
			}
		}

		if ( $mode === 'update' || $mode === 'remove' ) {
			// Add the titles to the list (or subscribe with the legacy watchlist)
			$this->processTitles( $params, $user, $listId, $dbw, $isWatchlist );
		}

		if ( $logEventName ) {
			$this->logEntry( $logEventName, $listId );
		}
	}

	/**
	 * When CheckUser extension is installed log events.
	 * @param string $action that occurred
	 * @param integer $id of the collection that was operated on.
	 * @throws MWException
	 */
	private function logEntry( $action, $id ) {
		// If CheckUser installed, give it a heads up
		$user = $this->getUser();
		$target = SpecialPage::getTitleFor( 'Gather' )->getSubPage( 'id' )
			->getSubPage( $id );
		$entry = new ManualLogEntry( 'gather', 'action' );
		$entry->setPerformer( $user );
		$entry->setTarget( $target );
		$params = array(
			'action' => $action,
		);
		$entry->setParameters( $params );
		$rc = $entry->getRecentChange();

		if ( is_callable( '\CheckUserHooks::updateCheckUserData' ) ) {
			\CheckUserHooks::updateCheckUserData( $rc );
		}

		// Surface hide, unhide and approve actions in Special:Log
		if ( $action === 'hidelist' || $action === 'showlist' || $action === 'approve' ) {
			$logId = $entry->insert();
			$entry->publish( $logId, 'udp' );
		}
	}

	/**
	 * Checks whether the collection description or title is disallowed according to AbuseFilter
	 * if available. If no abuse filters in place returns false.
	 * @param string $string
	 * @param int $maxLength maximum allowed number of characters in a string
	 * @param bool $titleRules if true, enforce same rules as for a page title
	 * @return bool
	 * @throws MWException
	 */
	private function isValidStr( $string, $maxLength, $titleRules ) {
		if ( mb_strlen( $string ) > $maxLength ) {
			return false;
		}
		if ( $titleRules ) {
			// TODO: the label should be normalized to $title->getPrefixedText()
			if ( !Title::newFromText( $string ) ) {
				return false;
			}
		}
		if ( class_exists( 'AbuseFilterVariableHolder' ) ) {
			$vars = new AbuseFilterVariableHolder();
			$user = $this->getUser();
			$vars->addHolders( AbuseFilter::generateUserVars( $user ) );
			$vars->setVar( 'action', 'gatheredit' );
			$vars->setVar( 'old_wikitext', '' );
			$vars->setVar( 'new_wikitext', $string );
			$vars->setVar( 'added_lines', $string );
			$title = SpecialPage::getTitleFor( 'Gather' )->getSubPage( 'by' )->
					getSubPage( $user->getName() );
			$result = AbuseFilter::filterAction( $vars, $title );
			return $result->isGood();
		}
		return true;
	}

	/**
	 * This method should be called twice - once before accessing DB, and once when db row is found
	 * @param array $params
	 * @param stdClass $row
	 * @throws \UsageException
	 */
	private function checkPermissions( array $params, $row = null ) {

		if ( $row ) {
			$isNew = false;
			$isWatchlist = $row->gl_label === '';
		} else {
			$isNew = $params['id'] === null;
			$isWatchlist = $params['id'] === 0;
		}

		$user = $this->getUser(); // TBD: We might want to allow other users with getWatchlistUser()
		$p = $this->getModulePrefix();
		$mode = $params['mode'];
		$label = $params['label'];
		// These modes cannot change list items or change other params like label/description/...
		// Incidentally, these are also modes that cannot be applied to the watchlist
		$isNoUpdatesMode = in_array( $mode,
			array( 'showlist', 'hidelist', 'deletelist', 'approve', 'flag' ) );

		if ( !$user->isLoggedIn() && $mode !== 'flag' ) {
			$this->dieUsage( 'You must be logged-in to edit a list', 'notloggedin' );
		} elseif ( !$user->isAllowed( 'editmywatchlist' ) && $mode !== 'flag' ) {
			$this->dieUsage( 'You don\'t have permission to edit your list', 'permissiondenied' );
		} elseif ( $user->isBlocked() ) {
			$this->dieUsage( 'You are blocked from editing your list', 'blocked' );
		} elseif ( $label === '' ) {
			$this->dieUsage( 'If given, label must not be empty', 'badlabel' );
		}

		if ( $isWatchlist ) {
			global $wgGatherAllowPublicWatchlist;
			if ( $label !== null ) {
				$this->dieUsage( "{$p}label cannot be set for a watchlist", 'invalidparammix' );
			} elseif ( $mode === 'deletelist' ) {
				$this->dieUsage( "Watchlist may not be deleted", 'invalidparammix' );
			} elseif ( !$wgGatherAllowPublicWatchlist ) {
				// Per team discussion, introducing artificial limitation for now
				// until we establish that making watchlist public would cause no harm.
				if ( $isNoUpdatesMode ) {
					$this->dieUsage( "{$p}mode=$mode is not allowed for watchlist", 'watchlist' );
				} elseif ( $params['perm'] === 'public' ) {
					$this->dieUsage( 'Making watchlist public is not supported for security reasons',
						'publicwatchlist' );
				}
			}
		}
		if ( $isNew ) {
			if ( $isNoUpdatesMode || $mode === 'remove' ) {
				// These modes are not allowed for the new list (no ID)
				$this->dieUsage( "List must be identified with {$p}id when {$p}mode=$mode is used",
					'invalidparammix' );
			} elseif ( $label === null ) {
				$this->dieUsage( "List {$p}label must be given for new lists", 'invalidparammix' );
			}
		}
		if ( $params['perm'] ) {
			if ( $mode !== 'update' ) {
				$this->dieUsage( "{$p}mode and {$p}perm cannot be used together", 'invalidparammix' );
			}
			if ( $row && $row->gl_perm_override !== self::PERM_OVERRIDE_NONE ) {
				$this->dieUsage( "Cannot change visibility when it is overriden by an admin",
					'invalidparammix' );
			}
		}
		switch ( $mode ) {
			case 'update':
			case 'remove':
			case 'deletelist':
				if ( $row && $row->gl_user !== $user->getId() ) {
					$this->dieUsage( "List {$p}id does not belong to the current user",
						'permissiondenied' );
				}
				break;
			case 'hidelist':
			case 'showlist':
			case 'approve':
				if ( !$user->isAllowed( 'gather-hidelist' ) ) {
					$this->dieUsage( "{$p}mode=$mode requires gather-hidelist permissions",
						'permissiondenied' );
				}
				if ( $row &&
					 $row->gl_perm === self::PERM_PRIVATE && $row->gl_user !== $user->getId()
				) {
					$this->dieUsage( "Visibility of private list may only be changed by author",
						'invalidparammix' );
				}
				break;
			case 'flag':
				break;
			default:
				$this->dieDebug( __METHOD__, 'Unknown mode=' . $mode );
				break;
		}
		if ( !$row ) {
			// First pass optimization - don't validate on the second pass (after DB row load)
			if ( $isNoUpdatesMode ) {
				// Special modes, disallow all parameters except those unset
				$tmp = $params + $this->getPageSet()->extractRequestParams();
				unset( $tmp['mode'] );
				unset( $tmp['id'] );
				unset( $tmp['token'] );
				$extraParams = array_keys( array_filter( $tmp, function ( $x ) {
					return $x !== null && $x !== false;
				} ) );
				if ( $extraParams ) {
					$this->dieUsage( "The parameter {$p}mode=$mode must not be used with " .
						implode( ", ", $extraParams ), 'invalidparammix' );
				}
			}
			if ( $label !== null && !$this->isValidStr( $label, 90, true ) ) {
				$this->dieUsage( 'Label too long or denied by the abuse filter', 'badlabel' );
			}
			$description = $params['description'];
			if ( $description !== null && !$this->isValidStr( $description, 280, false ) ) {
				$this->dieUsage( 'Description too long or denied by the abuse filter', 'baddesc' );
			}
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
				ApiBase::PARAM_HELP_MSG_PER_VALUE => array(),
			),
			'description' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
			'image' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
			'mode' => array(
				ApiBase::PARAM_DFLT => 'update',
				ApiBase::PARAM_TYPE => array(
					'update',
					'remove',
					'deletelist',
					'hidelist',
					'showlist',
					'flag',
					'approve',
				),
				ApiBase::PARAM_HELP_MSG_PER_VALUE => array(),
			),
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
	 * @param bool $resetPermission true if the list has been edited in such a way that the
	 *   review status is reset (APPROVED becomes plain PUBLIC, HIDDEN becomes flagged for review)
	 * @return string JSON encoded info object in case it changed, or NULL if update is not needed
	 * @throws \UsageException
	 */
	private function updateInfo( stdClass $v, array $params, &$resetPermission = null ) {
		$updated = false;
		$resetPermission = false;

		// Set default
		if ( !property_exists( $v, 'description' ) ) {
			$v->description = '';
		}
		if ( !property_exists( $v, 'image' ) ) {
			$v->image = '';
		}

		// Update from api parameters
		if ( $params['description'] !== null && $v->description !== $params['description'] ) {
			$v->description = $params['description'];
			$updated = true;
			$resetPermission = true;
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
	 * @deprecated must use DB column instead (To be deleted once DB is updated)
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
	 * @return int
	 */
	private function createRow( DatabaseBase $dbw, User $user, array $params, &$isWatchlist ) {
		$label = $isWatchlist ? '' : $params['label'];
		$info = $this->updateInfo( new stdClass(), $params );
		$createRow = !$isWatchlist || $info || $params['perm'] === 'public';
		$perm = $params['perm'] ? self::$permMap[$params['perm']] : self::PERM_PRIVATE;

		if ( $createRow ) {
			$id = $dbw->nextSequenceValue( 'gather_list_gl_id_seq' );
			$dbw->insert( 'gather_list', array(
				'gl_id' => $id,
				'gl_user' => $user->getId(),
				'gl_label' => $label,
				'gl_info' => $info,
				'gl_perm' => $perm,
				'gl_updated' => $dbw->timestamp( wfTimestampNow() ),
			), __METHOD__, 'IGNORE' );
			$id = $dbw->insertId();
		} else {
			$id = 0;
		}

		if ( $id === 0 ) {
			// List already exists, update instead, or might not need it
			$row = $this->getListRow( $params, $dbw, array(
				'gl_user' => $user->getId(), 'gl_label' => $label
			) );
			if ( $row !== false ) {
				$id = $row->gl_id;
				$isWatchlist = $row->gl_label === '';
				$this->updateListDb( $dbw, $params, $row );
			} elseif ( $createRow ) {
				// If creation failed, the second query should have succeeded
				$this->dieDebug( "List was not found", 'badid' );
			} else {
				// Watchlist, no changes
				$this->setResultStatus( 0, 'nochanges' );
			}
		} else {
			$this->setResultStatus( $id, 'created' );
		}
		return $id;
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
		$info = self::parseListInfo( $row->gl_info, $row->gl_id, true );
		$info = $this->updateInfo( $info, $params, $resetPermission );
		if ( $info ) {
			$update['gl_info'] = $info;
		}
		if ( $params['label'] !== null && $row->gl_label !== $params['label'] ) {
			$update['gl_label'] = $params['label'];
			$resetPermission = true;
		}
		if ( $resetPermission ) {
			if ( $row->gl_perm_override === self::PERM_OVERRIDE_HIDDEN ) {
				$update['gl_needs_review'] = 1;
			} elseif ( $row->gl_perm_override === self::PERM_OVERRIDE_APPROVED ) {
				$update['gl_perm_override'] = self::PERM_OVERRIDE_NONE;
			}
		}
		if ( $params['perm'] !== null ) {
			$perm = $params['perm'] === 'public' ?
				self::PERM_PUBLIC : self::PERM_PRIVATE;
			if ( $row->gl_perm !== $perm ) {
				$update['gl_perm'] = $perm;
			}
		}
		$this->updateRow( $dbw, $row, $update );
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

		$continuationManager = new ApiContinuationManager( $this, array(), array() );
		$this->setContinuationManager( $continuationManager );

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

		$isRemoving = $params['mode'] === 'remove';
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

			$dbw->begin( __METHOD__ );
			$dbw->insert( 'gather_list_item', $rows, __METHOD__, 'IGNORE' );
			$dbw->update( 'gather_list',
				array( 'gl_item_count = gl_item_count + ' . $dbw->affectedRows() ),
				array( 'gl_id' => $listId ),
				__METHOD__ );
			$dbw->commit( __METHOD__ );
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
				$dbw->begin( __METHOD__ );
				$dbw->delete( 'gather_list_item', array(
					'gli_gl_id' => $listId,
					$set
				), __METHOD__ );
				$dbw->update( 'gather_list',
					array( 'gl_item_count = gl_item_count - ' . $dbw->affectedRows() ),
					array( 'gl_id' => $listId ),
					__METHOD__ );
				$dbw->commit( __METHOD__ );
			}
		}

		ApiResult::setIndexedTagName( $res, 'w' );
		$this->getResult()->addValue( $this->getModuleName(), 'pages', $res );

		$this->setContinuationManager( null );
		$continuationManager->setContinuationIntoResult( $this->getResult() );
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

	/**
	 * Get DB row and if found, validate it against user parameters
	 * @param array $params
	 * @param DatabaseBase $dbw
	 * @param array $conds
	 * @return bool|stdClass
	 * @throws MWException
	 */
	private function getListRow( array $params, DatabaseBase $dbw, array $conds ) {
		$row = self::normalizeRow( $dbw->selectRow( 'gather_list',
			array( 'gl_id', 'gl_user', 'gl_label', 'gl_perm', 'gl_perm_override', 'gl_item_count',
				   'gl_flag_count', 'gl_needs_review', 'gl_info' ), $conds, __METHOD__ ) );
		if ( $row ) {
			$this->checkPermissions( $params, $row );
		}
		return $row;
	}

	/**
	 * @param stdClass|bool $row
	 * @return stdClass|bool
	 * @throws MWException
	 */
	public static function normalizeRow( $row ) {
		if ( $row ) {
			// MySQL returns int value as a string. Normalize.
			self::normalizeInt( $row, 'gl_id' );
			self::normalizeInt( $row, 'gl_user' );
			self::normalizeInt( $row, 'gl_perm' );
			self::normalizeInt( $row, 'gl_perm_override' );
			self::normalizeInt( $row, 'gl_item_count' );
			self::normalizeInt( $row, 'gl_flag_count' );
			self::normalizeInt( $row, 'gl_needs_review' );
		}
		return $row;
	}

	private static function normalizeInt( $row, $property ) {
		if ( property_exists( $row, $property ) ) {
			$v = intval( $row->$property );
			if ( strval( $v ) !== strval( $row->$property ) ) {
				throw new MWException( 'Internal error in ' . __METHOD__ .
					": $property is expected to be an integer" );
			}
			$row->$property = $v;
		}
	}

	/**
	 * @param DatabaseBase $dbw
	 * @param stdClass $row
	 * @param array $update
	 * @throws \UsageException
	 */
	private function updateRow( DatabaseBase $dbw, $row, array $update ) {
		if ( $update ) {
			// ACTION: update list record
			$update['gl_updated'] = $dbw->timestamp( wfTimestampNow() );
			$dbw->update( 'gather_list', $update, array( 'gl_id' => $row->gl_id ), __METHOD__,
				'IGNORE' );
			if ( $dbw->affectedRows() === 0 ) {
				// update failed due to the duplicate label restriction. Report
				$this->dieUsage( 'A list with this label already exists', 'duplicatelabel' );
			}
			$status = 'updated';
		} else {
			$status = 'nochanges';
		}
		$this->setResultStatus( $row->gl_id, $status );
	}

	/**
	 * @param int $id
	 * @param string $status
	 */
	private function setResultStatus( $id, $status ) {
		$this->getResult()->addValue( null, $this->getModuleName(), array(
			'status' => $status,
			'id' => $id,
		) );
	}
}
