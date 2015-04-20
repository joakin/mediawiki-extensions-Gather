<?php
/**
 *
 *
 * Created on March 6, 2015
 *
 * Copyright © 2015 Yuri Astrakhan "<Firstname><Lastname>@gmail.com"
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
use ApiQuery;
use ApiQueryBase;
use ApiResult;
use Title;
use User;

/**
 * Query module to enumerate all available lists
 *
 * @ingroup API
 */
class ApiQueryLists extends ApiQueryBase {
	public function __construct( ApiQuery $query, $moduleName ) {
		parent::__construct( $query, $moduleName, 'lst' );
	}

	public function execute() {
		$self = $this; // php 5.3 support - closures can't access $this
		$p = $this->getModulePrefix();
		$params = $this->extractRequestParams();
		$continue = $params['continue'];
		$mode = $params['mode'];
		$ids = $params['ids'];
		$title = $params['title'];
		$fld_label = in_array( 'label', $params['prop'] );
		$fld_description = in_array( 'description', $params['prop'] );
		$fld_public = in_array( 'public', $params['prop'] );
		$fld_image = in_array( 'image', $params['prop'] );
		$fld_updated = in_array( 'updated', $params['prop'] );
		$fld_owner = in_array( 'owner', $params['prop'] );
		$fld_count = in_array( 'count', $params['prop'] );

		// Watchlist, having the label set to '', should always appear first
		// If it doesn't, make sure to insert a fake one in the result
		// $injectWatchlist is true if we should inject a fake watchlist row if its missing
		// This code depends on the result ordered by label, and that watchlist label === ''
		$injectWatchlist = !$continue;

		if ( $mode ) {
			if ( $ids !== null || $title !== null || $params['owner'] !== null ||
				 $params['token'] !== null
			) {
				$this->dieUsage( "Parameters {$p}ids, {$p}title, {$p}owner, {$p}token " .
					"not allowed with mode=allpublic",
					'invalidparammix' );
			}
			if ( $mode === 'allhidden' && !$this->getUser()->isAllowed( 'gather-hidelist' ) ) {
				$this->dieUsage( 'You don\'t have permission to view hidden lists',
					'permissiondenied' );
			}
			$injectWatchlist = false;
			$findWatchlist = false;
			$owner = null;
			$showPrivate = false;
		} else {
			if ( $ids ) {
				$findWatchlist = array_search( 0, $ids );
				if ( $findWatchlist !== false ) {
					unset( $ids[$findWatchlist] );
					$findWatchlist = true;
				} else {
					// When specifying IDs, don't auto-include watchlist
					$injectWatchlist = false;
				}
			} else {
				$findWatchlist = false;
			}

			/** @var User $owner */
			list( $owner, $showPrivate ) = $this->calcPermissions( $params, $ids );
			if ( $showPrivate !== true ) {
				$injectWatchlist = false;
			}
		}

		$db = $this->getDB();
		$this->addTables( 'gather_list' );
		$this->addFields( 'gl_id' );
		if ( $fld_label || !$mode ) {
			$this->addFields( 'gl_label' );
		} else {
			$this->addFields( array( 'isWl' => "gl_label=''" ) );
		}
		$this->addFieldsIf( 'gl_updated', $fld_updated || $mode );
		$this->addFieldsIf( 'gl_perm', $fld_public );

		if ( $fld_owner && !$owner ) {
			// Join user table to get user name
			// else - we already know the user to return, no need to join tables
			$this->addTables( 'user' );
			$this->addJoinConds( array( 'user' => array( 'INNER JOIN', 'user_id=gl_user' ) ) );
			$this->addFields( 'user_name' );
		}

		if ( $owner ) {
			$this->addWhereFld( 'gl_user', $owner->getId() );
			$singleUser = true;
		} else {
			$owner = $this->getUser();
			$singleUser = false;
		}

		if ( $ids || $findWatchlist ) {
			$cond = array();
			if ( $ids ) {
				$cond['gl_id'] = $ids;
			}
			if ( $findWatchlist ) {
				if ( $singleUser ) {
					$cond['gl_label'] = '';
				} else {
					$cond[] =
						$db->makeList( array( 'gl_label' => '', 'gl_user' => $owner->getId() ),
							LIST_AND );
					if ( !$ids ) {
						$singleUser = true;
					}
				}
			}
			$this->addWhere( $db->makeList( $cond, LIST_OR ) );
		}

		if ( $mode === 'allhidden' ) {
			$this->addWhereFld( 'gl_perm', ApiEditList::PERM_HIDDEN );
		} elseif ( $showPrivate !== true ) {
			$cond = array( 'gl_perm' => ApiEditList::PERM_PUBLIC );
			if ( $showPrivate === null ) {
				$cond['gl_user'] = $this->getUser()->getId();
			}
			$this->addWhere( $db->makeList( $cond, LIST_OR ) );
		}

		if ( $continue ) {
			if ( $singleUser ) {
				// Single value continue
				$contLabel = $db->addQuotes( $params['continue'] );
				$this->addWhere( "gl_label >= $contLabel" );
			} else {
				if ( $mode ) {
					$cont = explode( '|', $params['continue'] );
					$this->dieContinueUsageIf( count( $cont ) != 2 );
					$contUpd = $db->addQuotes( $cont[0] );
					$contId = intval( $cont[1] );
					$this->dieContinueUsageIf( strval( $contId ) !== $cont[1] );
					$contId = $db->addQuotes( $contId );
					$this->addWhere( "gl_updated < $contUpd OR " .
									 "(gl_updated = $contUpd AND gl_id <= $contId)" );
				} else {
					$cont = explode( '|', $params['continue'], 2 ); // label may contain '|'
					$this->dieContinueUsageIf( count( $cont ) != 2 );
					$contUser = intval( $cont[0] );
					$this->dieContinueUsageIf( strval( $contUser ) !== $cont[0] );
					$contUser = $db->addQuotes( $contUser );
					$contLabel = $db->addQuotes( $cont[1] );
					$this->addWhere( "gl_user > $contUser OR " .
									 "(gl_user = $contUser AND gl_label >= $contLabel)" );
				}
			}
		}
		if ( $mode ) {
			// The ordering has to be unique so that we can safely
			// continue iteration even if subsequent timestamps are the same
			$this->addOption( 'ORDER BY', 'gl_perm, gl_updated DESC, gl_id DESC' );
			$setContinue = function( $row ) use ( $self ) {
				$self->setContinueEnumParameter( 'continue', "{$row->gl_updated}|{$row->gl_id}" );
			};
		} elseif ( !$singleUser ) {
			$this->addFields( 'gl_user' );
			$this->addOption( 'ORDER BY', 'gl_user, gl_label' );
			$setContinue = function( $row ) use ( $self ) {
				$self->setContinueEnumParameter( 'continue', "{$row->gl_user}|{$row->gl_label}" );
			};
		} else {
			$this->addOption( 'ORDER BY', 'gl_label' );
			$setContinue = function( $row ) use ( $self ) {
				$self->setContinueEnumParameter( 'continue', $row->gl_label );
			};
		}

		if ( $title ) {
			$title = Title::newFromText( $title );
			if ( !$title ) {
				$this->dieUsage( 'Invalid title parameter', 'bad_title' );
			}

			if ( $ids || !$findWatchlist ) {
				$cond = array(
					'gli_namespace' => $title->getNamespace(),
					'gli_title' => $title->getDBkey(),
					'gl_id = gli_gl_id',
				);
				$subsql = $db->selectSQLText( 'gather_list_item', 'gli_gl_id', $cond, __METHOD__ );
				$subsql = "($subsql)";
				$this->addFields( array( 'isIn' => $subsql ) );
			}
			// else - avoid subquery because there would be no results - searching for watchlist
		}

		$useInfo = $fld_description || $fld_image;
		$this->addFieldsIf( 'gl_info', $useInfo );

		$limit = $params['limit'];
		$this->addOption( 'LIMIT', $limit + 1 );

		$count = 0;
		$path = array( 'query', $this->getModuleName() );

		// This closure will process one row, even if that row is fake watchlist
		$processRow = function( $row ) use ( $self, &$count, $mode, $limit,  $useInfo, $title,
			$fld_label, $fld_description, $fld_public, $fld_image, $fld_updated, $fld_owner,
			$path, $owner, $setContinue
		) {
			if ( $row === null ) {
				// Fake watchlist row
				$row = (object) array(
					'gl_id' => 0,
					'gl_label' => '',
					'gl_perm' => ApiEditList::PERM_PRIVATE,
					'gl_updated' => '',
					'gl_info' => '',
				);
			} else {
				$row = ApiEditList::normalizeRow( $row );
			}

			$count++;
			if ( $count > $limit ) {
				// We've reached the one extra which shows that there are
				// additional pages to be had. Stop here...
				$setContinue( $row );
				return false;
			}

			$isWatchlist = property_exists( $row, 'isWl' ) ? $row->isWl : $row->gl_label === '';

			$data = array( 'id' => $row->gl_id );
			if ( $isWatchlist ) {
				$data['watchlist'] = true;
			}
			if ( $fld_label ) {
				// TODO: check if this is the right wfMessage to show
				$data['label'] = !$isWatchlist ? $row->gl_label : wfMessage( 'watchlist' )->plain();
			}
			if ( $fld_owner ) {
				$data['owner'] = property_exists( $row, 'user_name' ) ?
					$row->user_name : $owner->getName();
			}
			if ( $title ) {
				if ( $isWatchlist ) {
					$data['title'] = $self->isTitleInWatchlist( $owner, $title );
				} else {
					$data['title'] = isset( $row->isIn );
				}
			}
			if ( $fld_public ) {
				$data['public'] = $row->gl_perm === ApiEditList::PERM_PUBLIC;
				switch ( $row->gl_perm ) {
					case ApiEditList::PERM_PRIVATE:
						$data['perm'] = 'private';
						break;
					case ApiEditList::PERM_PUBLIC:
						$data['perm'] = 'public';
						break;
					case ApiEditList::PERM_HIDDEN:
						$data['perm'] = 'hidden';
						break;
					default:
						$self->dieDebug( __METHOD__,
							"Unknown gather perm={$row->gl_perm} for id {$row->gl_id}" );
				}
			}
			if ( $useInfo ) {
				$info = ApiEditList::parseListInfo( $row->gl_info, $row->gl_id, false );
				if ( $fld_description ) {
					$data['description'] = property_exists( $info, 'description' ) ? $info->description : '';
				}
				if ( $fld_image ) {
					if ( property_exists( $info, 'image' ) && $info->image ) {
						$data['image'] = $info->image;
						$file = wfFindFile( $info->image );
						if ( !$file ) {
							$data['badimage'] = true;
						} else {
							$data['imageurl'] = $file->getFullUrl();
							$data['imagewidth'] = intval( $file->getWidth() );
							$data['imageheight'] = intval( $file->getHeight() );
						}
					} else {
						$data['image'] = false;
					}
				}
			}
			if ( $fld_updated ) {
				$data['updated'] = wfTimestamp( TS_ISO_8601, $row->gl_updated );
			}

			$fit = $self->getResult()->addValue( $path, null, $data );
			if ( !$fit ) {
				$setContinue( $row );
				return false;
			}
			return true;
		};

		foreach ( $this->select( __METHOD__ ) as $row ) {
			if ( $injectWatchlist ) {
				if ( property_exists( $row, 'isWl' ) ? !$row->isWl : $row->gl_label !== '' ) {
					// The very first DB row already has a label, so inject a fake
					if ( !$processRow( null ) ) {
						break;
					}
				}
				$injectWatchlist = false;
			}
			if ( !$processRow( $row ) ) {
				break;
			}
		}

		if ( $injectWatchlist ) {
			// There are no records in the database, and we need to inject watchlist row
			$processRow( null );
		}

		$this->getResult()->addIndexedTagName( $path, 'c' );

		if ( $fld_count ) {
			$this->updateCounts( $owner );
		}
	}

	public function getCacheMode( $params ) {
		return 'anon-public-user-private';
	}

	public function getAllowedParams() {
		return array(
			'mode' => array(
				ApiBase::PARAM_TYPE => array(
					'allpublic',
					'allhidden',
				)
			),
			'prop' => array(
				ApiBase::PARAM_DFLT => 'label',
				ApiBase::PARAM_ISMULTI => true,
				ApiBase::PARAM_TYPE => array(
					'label',
					'description',
					'public',
					'image',
					'count',
					'updated',
					'owner',
				)
			),
			'ids' => array(
				ApiBase::PARAM_ISMULTI => true,
				ApiBase::PARAM_TYPE => 'integer',
			),
			'title' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
			'owner' => array(
				ApiBase::PARAM_TYPE => 'user',
			),
			'token' => array(
				ApiBase::PARAM_TYPE => 'string'
			),
			'limit' => array(
				ApiBase::PARAM_DFLT => 10,
				ApiBase::PARAM_TYPE => 'limit',
				ApiBase::PARAM_MIN => 1,
				ApiBase::PARAM_MAX => ApiBase::LIMIT_BIG1,
				ApiBase::PARAM_MAX2 => ApiBase::LIMIT_BIG2
			),
			'continue' => array(
				ApiBase::PARAM_HELP_MSG => 'api-help-param-continue',
			),
		);
	}

	protected function getExamplesMessages() {
		return array(
			'action=query&list=lists' => 'apihelp-query+lists',
		);
	}

	public function getHelpUrls() {
		return '//www.mediawiki.org/wiki/Extension:Gather';
	}

	/**
	 * Determine what the user may or may not see based on api parameters
	 * Returns the user (owner) non-anonymous object to filter by (if needed)
	 * Returns a bool|null if private list should be hidden
	 * The second returned val is null if each list ID should be checked against current user
	 * @param array $params must contain owner and token values
	 * @param bool $ids true if the user supplied specific list ID(s)
	 * @return array [Owner user object, true|false|null]
	 * @throws \UsageException
	 */
	private function calcPermissions( array $params, $ids ) {
		if ( $params['owner'] !== null && $params['token'] !== null ) {
			// Caller supplied token - treat them as trusted, someone who could see even private
			return array( $this->getWatchlistUser( $params ), true );
		}

		if ( $params['owner'] !== null ) {
			// Caller supplied owner only - treat them as untrusted, except
			// if owner == currentUser, allow private
			$owner = User::newFromName( $params['owner'], false );
			if ( !( $owner && $owner->getId() ) ) {
				// Note: keep original "bad_wlowner" error code for consistency
				$this->dieUsage( 'Specified user does not exist', 'bad_wlowner' );
			}
			$showPrivate = $owner->getId() === $this->getUser()->getId();
		} elseif ( !$ids ) {
			// neither ids nor owner parameter is given - shows all lists of the current user
			$owner = $this->getUser();
			if ( !$owner->isLoggedIn() ) {
				$this->dieUsage( 'You must be logged-in or use owner and/or ids parameters',
					'notloggedin' );
			}
			$showPrivate = true;
		} else {
			// ids given - will need to validate each id to belong to the current user for privacy
			$owner = false;
			if ( !$this->getUser()->isLoggedIn() ) {
				$showPrivate = false; // Anonymous user - optimize, none of the results are private
			} else {
				$showPrivate = null; // check each id against current user's ID
			}
		}
		if ( $showPrivate !== false ) {
			// Both 'null' and 'true' may be changed to 'false' here
			// Private is treated the same as 'viewmywatchlist' right
			if ( !$this->getUser()->isAllowed( 'viewmywatchlist' ) ) {
				$showPrivate = false;
			}
		}
		return array( $owner, $showPrivate );
	}

	/**
	 * Update result lists with their page counts
	 * @param User $wlOwner In case there is a watchlist, this is the user it belongs to
	 */
	private function updateCounts( User $wlOwner ) {
		$result = $this->getResult();
		$data = $result->getResultData( array( 'query', $this->getModuleName() ) );
		if ( $data === null ) {
			return;
		}

		$ids = array();
		$wlListId = false;
		foreach ( $data as $key => $page ) {
			if ( !ApiResult::isMetadataKey( $key ) ) {
				if ( $page['id'] === 0 || isset( $page['watchlist'] ) ) {
					$wlListId = $page['id'];
				} else {
					$ids[] = $page['id'];
				}
			}
		}

		$counts = array();
		if ( $wlListId !== false ) {
			// TODO: estimateRowCount() might be much faster, TBD if ok
			$db = $this->getQuery()->getNamedDB( 'watchlist', DB_SLAVE, 'watchlist' );
			// Must divide in two because of duplicate talk pages (same as the special page)
			$counts[$wlListId] = intval( floor(
				$db->selectRowCount( 'watchlist', '*', array( 'wl_user' => $wlOwner->getId() ),
					__METHOD__ ) / 2 ) );
		}
		if ( count( $ids ) > 0 ) {
			$db = $this->getDB();
			$sql =
				$db->select( 'gather_list_item',
					array( 'id' => 'gli_gl_id', 'cnt' => 'COUNT(*)' ),
					array( 'gli_gl_id' => $ids ), __METHOD__,
					array( 'GROUP BY' => 'gli_gl_id' ) );

			foreach ( $sql as $row ) {
				$counts[intval( $row->id )] = intval( $row->cnt );
			}
		}

		foreach ( $data as $key => $page ) {
			if ( !ApiResult::isMetadataKey( $key ) ) {
				$id = $page['id'];

				// This really shouldn't be using ApiResult::NO_SIZE_CHECK, but
				// there's no sane way to handle failure without rewriting a
				// bunch of other code.
				$result->addValue( array( 'query', $this->getModuleName(), $key ), 'count',
					isset( $counts[$id] ) ? $counts[$id] : 0, ApiResult::NO_SIZE_CHECK );
			}
		}
	}

	/**
	 * @param User $user
	 * @param Title $title
	 * @return bool
	 * @throws \DBUnexpectedError
	 */
	private function isTitleInWatchlist( User $user, Title $title ) {
		$db = $this->getQuery()->getNamedDB( 'watchlist', DB_SLAVE, 'watchlist' );
		return (bool)$db->selectField( 'watchlist', '1', array(
			'wl_user' => $user->getId(),
			'wl_namespace' => $title->getNamespace(),
			'wl_title' => $title->getDBkey(),
		), __METHOD__ );
	}
}
