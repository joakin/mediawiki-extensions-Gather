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
		$params = $this->extractRequestParams();
		$continue = $params['continue'];

		// Watchlist, having the label set to '', should always appear first
		// If it doesn't, make sure to insert a fake one in the result
		// $injectWatchlist is true if we should inject a fake watchlist row if its missing
		// This code depends on the result ordered by label, and that watchlist label === ''
		$injectWatchlist = !$continue;

		$ids = $params['ids'];
		if ( $ids ) {
			$findWatchlist = array_search( 0, $ids );
			if ( $findWatchlist !== false) {
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
		$userId = $owner ? $owner->getId() : $this->getUser()->getId();

		$db = $this->getDB();
		$this->addTables( 'gather_list' );
		$this->addFields( 'gl_id' );
		$this->addFields( 'gl_label' );
		$this->addFieldsIf( 'gl_user', $showPrivate === null ); // won't know if private until later
		if ( $owner ) {
			$this->addWhereFld( 'gl_user', $owner->getId() );
		}
		if ( $ids || $findWatchlist ) {
			$cond = array();
			if ( $ids ) {
				$cond['gl_id'] = $ids;
			}
			if ( $findWatchlist ) {
				$cond['gl_label'] = '';
			}
			$this->addWhere( $db->makeList( $cond, LIST_OR ) );
		}

		if ( $continue ) {
			$cont = $db->addQuotes( $continue );
			$this->addWhere( "gl_label >= $cont" );
		}

		$title = $params['title'];
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
			} else {
				// Avoid subquery because there would be no results - searching for watchlist
				$this->addFields( array( 'isIn' => 'NULL' ) );
			}
		}

		$fld_label = in_array( 'label', $params['prop'] );
		$fld_description = in_array( 'description', $params['prop'] );
		$fld_public = in_array( 'public', $params['prop'] );
		$fld_image = in_array( 'image', $params['prop'] );

		// If we need it for privacy checks or we need to return a prop field
		$useInfo = $showPrivate !== true || $fld_description || $fld_public || $fld_image;

		$this->addFieldsIf( 'gl_info', $useInfo );

		$limit = $params['limit'];
		// TODO: Disabling this until we stop skipping rows in processing
		// $this->addOption( 'LIMIT', $limit + 1 );
		$this->addOption( 'ORDER BY', 'gl_label' );

		$count = 0;
		$path = array( 'query', $this->getModuleName() );
		$currUserId = strval( $this->getUser()->getId() );

		// This closure will process one row, even if that row is fake watchlist
		$processRow = function( $row ) use ( &$count, $limit, $fld_label, $useInfo, $title,
			$fld_description, $fld_public, $fld_image, $path, $showPrivate, $currUserId, $userId
		) {
			if ( $row === null ) {
				// Fake watchlist row
				$row = (object) array(
					'gl_id' => '0',
					'gl_label' => '',
					'gl_user' => false,
					'gl_info' => '',
				);
			}

			$info = $useInfo ?
				ApiEditList::parseListInfo( $row->gl_info, $row->gl_id, false ) : null;

			// Determine if this gather_list row is viewable by the current user
			// TODO: this should be part of the SQL query once fields are cerated
			$show = $showPrivate;
			if ( $show === null && $row->gl_user === $currUserId ) {
				$show = true;
			} elseif ( $show !== true && ApiEditList::isPublic( $info ) ) {
				$show = true;
			}
			if ( !$show ) {
				return true;
			}

			$count++;

			if ( $count > $limit ) {
				// We've reached the one extra which shows that there are
				// additional pages to be had. Stop here...
				$this->setContinueEnumParameter( 'continue', $row->gl_label );
				return false;
			}

			$isWatchlist = $row->gl_label === '';

			$data = array( 'id' => intval( $row->gl_id ) );
			if ( $isWatchlist ) {
				$data['watchlist'] = true;
			}
			if ( $fld_label ) {
				// TODO: check if this is the right wfMessage to show
				$data['label'] = !$isWatchlist ? $row->gl_label : wfMessage( 'watchlist' )->plain();
			}
			if ( $title ) {
				if ( $isWatchlist ) {
					$data['title'] = $this->isTitleInWatchlist( $userId, $title );
				} else {
					$data['title'] = $row->isIn !== null;
				}
			}
			if ( $useInfo ) {
				if ( $fld_description ) {
					$data['description'] = property_exists( $info, 'description' ) ? $info->description : '';
				}
				if ( $fld_public ) {
					$data['public'] = ApiEditList::isPublic( $info );
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

			$fit = $this->getResult()->addValue( $path, null, $data );
			if ( !$fit ) {
				$this->setContinueEnumParameter( 'continue', $row->gl_label );
				return false;
			}
			return true;
		};

		foreach ( $this->select( __METHOD__ ) as $row ) {
			if ( $injectWatchlist ) {
				if ( $row->gl_label !== '' ) {
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

		$this->getResult()->setIndexedTagName_internal( $path, 'c' );

		$this->updateCounts( $params, $userId );
	}

	public function getCacheMode( $params ) {
		return 'anon-public-user-private';
	}

	public function getAllowedParams() {
		return array(
			'prop' => array(
				ApiBase::PARAM_DFLT => 'label',
				ApiBase::PARAM_ISMULTI => true,
				ApiBase::PARAM_TYPE => array(
					'label',
					'description',
					'public',
					'image',
					'count',
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
				$showPrivate = false;
			} else {
				$showPrivate = null; // check each id against $currUserId
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
	 * @param $params
	 * @param int $userId
	 */
	private function updateCounts( $params, $userId ) {
		if ( !in_array( 'count', $params['prop'] ) ) {
			return;
		}
		$data = $this->getResult()->getData();
		if ( !isset( $data['query'] ) || !isset( $data['query'][$this->getModuleName()] ) ) {
			return;
		}
		$data = $data['query'][$this->getModuleName()];

		$ids = array();
		$wlListId = false;
		$wlUserId = false;
		foreach ( $data as $page ) {
			if ( $page['id'] === 0 || isset( $page['watchlist'] ) ) {
				$wlListId = $page['id'];
				$wlUserId = $userId;
			} else {
				$ids[] = $page['id'];
			}
		}

		$counts = array();
		if ( $wlListId !== false ) {
			// TODO: estimateRowCount() might be much faster, TBD if ok
			$db = $this->getQuery()->getNamedDB( 'watchlist', DB_SLAVE, 'watchlist' );
			// Must divide in two because of duplicate talk pages (same as the special page)
			$counts[$wlListId] = intval( floor(
				$db->selectRowCount( 'watchlist', '*', array( 'wl_user' => $wlUserId ),
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

		foreach ( $data as &$page ) {
			$id = $page['id'];
			$page['count'] = isset( $counts[$id] ) ? $counts[$id] : 0;
		}
		// Replace result with the results with counts
		$this->getResult()->addValue( 'query', $this->getModuleName(), $data,
			ApiResult::OVERRIDE | ApiResult::NO_SIZE_CHECK );
	}

	/**
	 * @param int $userId
	 * @param Title $title
	 * @return bool
	 * @throws \DBUnexpectedError
	 */
	private function isTitleInWatchlist( $userId, $title ) {
		$db = $this->getQuery()->getNamedDB( 'watchlist', DB_SLAVE, 'watchlist' );
		return (bool)$db->selectField( 'watchlist', '1', array(
			'wl_user' => $userId,
			'wl_namespace' => $title->getNamespace(),
			'wl_title' => $title->getDBkey(),
		), __METHOD__ );
	}
}
