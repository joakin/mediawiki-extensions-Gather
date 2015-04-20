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
use ApiPageSet;
use ApiQuery;
use ApiQueryBase;
use ApiQueryGeneratorBase;
use MWException;
use Title;

/**
 * Query module to enumerate all available lists
 *
 * @ingroup API
 */
class ApiQueryListPages extends ApiQueryGeneratorBase {

	private $modulePath;

	public function __construct( ApiQuery $query, $moduleName ) {
		parent::__construct( $query, $moduleName, 'lsp' );
		$this->modulePath = array( 'query', $this->getModuleName() );
	}

	public function execute() {
		$this->run();
	}

	public function executeGenerator( $resultPageSet ) {
		$this->run( $resultPageSet );
	}

	/**
	 * @param ApiPageSet $resultPageSet
	 * @throws MWException
	 * @throws \UsageException
	 */
	private function run( $resultPageSet = null ) {

		$params = $this->extractRequestParams();
		$p = $this->getModulePrefix();

		$useOwner = $params['owner'] !== null;
		if ( $useOwner !== ( $params['token'] !== null ) ) {
			$this->dieUsage( "Both {$p}owner and {$p}token must be given or missing",
				'invalidparammix' );
		}

		$isGenerator = $resultPageSet !== null;

		if ( !$params['id'] ) {
			// If id is not given (or equals to 0), permissions the same as watchlistraw access
			$user = $this->getWatchlistUser( $params );
			$titles = $this->queryLegacyWatchlist( $params, $isGenerator, $user->getId() );
		} else {
			// Id was given, this could be public or private list, legacy watchlist or regular
			// Allow access to any public list/watchlist, and to private with proper owner/self
			$db = $this->getDB();
			$listRow = ApiEditList::normalizeRow( $db->selectRow( 'gather_list',
				array( 'gl_label', 'gl_user', 'gl_perm' ),
				array( 'gl_id' => $params['id'] ), __METHOD__ ) );
			if ( $listRow === false ) {
				$this->dieUsage( "List does not exist", 'badid' );
			}
			if ( $useOwner ) {
				// Caller supplied token: treat them as trusted, someone who could see even private
				// At the same time, owner param must match list's owner
				// TODO: if we allow non-matching owner, we could treat it as public-only,
				// but that might be unexpected behavior
				$user = $this->getWatchlistUser( $params );
				if ( $listRow->gl_user !== $user->getId() ) {
					$this->dieUsage( 'The owner supplied does not match the list\'s owner',
						'permissiondenied' );
				}
				$showPrivate = true;
			} else {
				$user = $this->getUser();
				$showPrivate = $user->isLoggedIn() && $listRow->gl_user === $user->getId()
					&& $user->isAllowed( 'viewmywatchlist' );
			}

			// Check if this is a public list (if required)
			if ( !$showPrivate && $listRow->gl_perm !== ApiEditList::PERM_PUBLIC ) {
				$this->dieUsage( "You have no rights to see this list", 'badid' );
			}

			if ( $listRow->gl_label === '' ) {
				// This is actually a watchlist, and it is either public or belongs to current user
				$titles = $this->queryLegacyWatchlist( $params, $isGenerator, $listRow->gl_user );
			} else {
				$titles = $this->queryListItems( $params, $isGenerator );
			}
		}
		if ( !$isGenerator ) {
			$this->getResult()->addIndexedTagName( $this->modulePath, 'wr' );
		} else {
			$resultPageSet->populateFromTitles( $titles );
		}
	}

	/**
	 * @param array $params
	 * @param $isGenerator
	 * @return array
	 */
	private function queryListItems( array $params, $isGenerator ) {
		$this->addTables( 'gather_list_item' );
		$this->addFields( array( 'gli_namespace', 'gli_title', 'gli_order' ) );
		$this->addWhereFld( 'gli_gl_id', $params['id'] );
		$this->addWhereFld( 'gli_namespace', $params['namespace'] );

		if ( isset( $params['continue'] ) ) {
			$cont = $params['continue'];
			$this->dieContinueUsageIf( strval( floatval( $cont ) ) !== $cont );
			$cont = $this->getDB()->addQuotes( $cont );
			$op = $params['dir'] == 'ascending' ? '>=' : '<=';
			$this->addWhere( "gli_order $op $cont" );
		}
		$sort = ( $params['dir'] == 'descending' ? ' DESC' : '' );
		$this->addOption( 'ORDER BY', 'gli_order' . $sort );

		$this->addOption( 'LIMIT', $params['limit'] + 1 );
		$res = $this->select( __METHOD__ );

		$titles = array();
		$count = 0;
		foreach ( $res as $row ) {
			if ( ++$count > $params['limit'] ) {
				// We've reached the one extra which shows that there are
				// additional pages to be had. Stop here...
				$this->setContinueEnumParameter( 'continue', $row->gli_order );
				break;
			}
			$t = Title::makeTitle( $row->gli_namespace, $row->gli_title );
			if ( !$isGenerator ) {
				$vals = array();
				ApiQueryBase::addTitleInfo( $vals, $t );
				$fit = $this->getResult()->addValue( $this->modulePath, null, $vals );
				if ( !$fit ) {
					$this->setContinueEnumParameter( 'continue', $row->gli_order );
					break;
				}
			} else {
				$titles[] = $t;
			}
		}
		return $titles;
	}

	/**
	 * Query legacy watchlist without any permission checks
	 * @param array $params
	 * @param bool $isGenerator
	 * @param int $userId
	 * @return array
	 */
	private function queryLegacyWatchlist( array $params, $isGenerator, $userId ) {
		$this->selectNamedDB( 'watchlist', DB_SLAVE, 'watchlist' );
		$this->addTables( 'watchlist' );
		$this->addFields( array( 'wl_namespace', 'wl_title' ) );
		$this->addWhereFld( 'wl_user', $userId );
		$this->addWhereFld( 'wl_namespace', $params['namespace'] );

		if ( isset( $params['continue'] ) ) {
			$cont = explode( '|', $params['continue'] );
			$this->dieContinueUsageIf( count( $cont ) != 2 );
			$ns = intval( $cont[0] );
			$this->dieContinueUsageIf( strval( $ns ) !== $cont[0] );
			$title = $this->getDB()->addQuotes( $cont[1] );
			$op = $params['dir'] == 'ascending' ? '>' : '<';
			$this->addWhere(
				"wl_namespace $op $ns OR " .
				"(wl_namespace = $ns AND wl_title $op= $title)"
			);
		}

		$sort = ( $params['dir'] == 'descending' ? ' DESC' : '' );
		// Don't ORDER BY wl_namespace if it's constant in the WHERE clause
		if ( count( $params['namespace'] ) == 1 ) {
			$this->addOption( 'ORDER BY', 'wl_title' . $sort );
		} else {
			$this->addOption( 'ORDER BY', array(
				'wl_namespace' . $sort,
				'wl_title' . $sort
			) );
		}

		$this->addOption( 'LIMIT', $params['limit'] + 1 );
		$res = $this->select( __METHOD__ );

		$titles = array();
		$count = 0;
		foreach ( $res as $row ) {
			if ( ++$count > $params['limit'] ) {
				// We've reached the one extra which shows that there are
				// additional pages to be had. Stop here...
				$this->setContinueEnumParameter( 'continue', $row->wl_namespace . '|' . $row->wl_title );
				break;
			}
			$t = Title::makeTitle( $row->wl_namespace, $row->wl_title );
			if ( !$isGenerator ) {
				$vals = array();
				ApiQueryBase::addTitleInfo( $vals, $t );
				$fit = $this->getResult()->addValue( $this->modulePath, null, $vals );
				if ( !$fit ) {
					$this->setContinueEnumParameter( 'continue', $row->wl_namespace . '|' . $row->wl_title );
					break;
				}
			} else {
				$titles[] = $t;
			}
		}
		return $titles;
	}

	public function getCacheMode( $params ) {
		return 'anon-public-user-private';
	}

	public function getAllowedParams() {
		return array(
			'continue' => array(
				ApiBase::PARAM_HELP_MSG => 'api-help-param-continue',
			),
			'id' => array(
				ApiBase::PARAM_DFLT => 0,
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_MIN => 0,
			),
			'namespace' => array(
				ApiBase::PARAM_ISMULTI => true,
				ApiBase::PARAM_TYPE => 'namespace',
			),
			'limit' => array(
				ApiBase::PARAM_DFLT => 10,
				ApiBase::PARAM_TYPE => 'limit',
				ApiBase::PARAM_MIN => 1,
				ApiBase::PARAM_MAX => ApiBase::LIMIT_BIG1,
				ApiBase::PARAM_MAX2 => ApiBase::LIMIT_BIG2,
			),
			'owner' => array(
				ApiBase::PARAM_TYPE => 'user',
			),
			'token' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
			'dir' => array(
				ApiBase::PARAM_DFLT => 'ascending',
				ApiBase::PARAM_TYPE => array(
					'ascending',
					'descending',
				),
				ApiBase::PARAM_HELP_MSG => 'api-help-param-direction',
			),
		);
	}

	protected function getExamplesMessages() {
		return array(
			'action=query&list=listpages' => 'apihelp-query+listpages',
		);
	}

	public function getHelpUrls() {
		return '//www.mediawiki.org/wiki/Extension:Gather';
	}
}
