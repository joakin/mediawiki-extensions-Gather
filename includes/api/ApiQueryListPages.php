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
use User;

/**
 * Query module to enumerate all available lists
 *
 * @ingroup API
 */
class ApiQueryListPages extends ApiQueryGeneratorBase {

	public function __construct( ApiQuery $query, $moduleName ) {
		parent::__construct( $query, $moduleName, 'lsp' );
	}

	public function execute() {
		$this->run();
	}

	public function executeGenerator( $resultPageSet ) {
		$this->run( $resultPageSet );
	}

	private function run( $resultPageSet = null ) {

		$params = $this->extractRequestParams();
		$user = $this->getWatchlistUser( $params );

		if ( $params['id'] ) {
			// Runs this code whenever ID is given and is not 0 (watchlist)
			// Will be removed once DB storage is implemented
			$this->tempProcessCollectionFromJson( $resultPageSet, $params, $user );
			return;
		}

		$this->selectNamedDB( 'watchlist', DB_SLAVE, 'watchlist' );
		$this->addTables( 'watchlist' );
		$this->addFields( array( 'wl_namespace', 'wl_title' ) );
		$this->addWhereFld( 'wl_user', $user->getId() );
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

			if ( is_null( $resultPageSet ) ) {
				$vals = array();
				ApiQueryBase::addTitleInfo( $vals, $t );
				$fit = $this->getResult()->addValue( $this->getModuleName(), null, $vals );
				if ( !$fit ) {
					$this->setContinueEnumParameter( 'continue', $row->wl_namespace . '|' . $row->wl_title );
					break;
				}
			} else {
				$titles[] = $t;
			}
		}
		if ( is_null( $resultPageSet ) ) {
			$this->getResult()->setIndexedTagName_internal( $this->getModuleName(), 'wr' );
		} else {
			$resultPageSet->populateFromTitles( $titles );
		}
	}

	/**
	 * Temporary workaround until DB table is implemented. Loads and filters pages (items) data
	 * from the JSON manifest page.
	 * @param ApiPageSet $resultPageSet
	 * @param array $params
	 * @param User $user
	 * @throws MWException
	 */
	private function tempProcessCollectionFromJson( $resultPageSet, array $params, User $user ) {
		$manifest = ApiEditList::loadManifest( $user );
		$list = ApiEditList::findList( $manifest, $params['id'], $user );
		if ( !$list ) {
			$this->dieUsageMsg( 'unknown-list-id' );
		}

		$limit = $params['limit'];
		$isAscending = $params['dir'] === 'ascending';
		if ( isset( $params['continue'] ) ) {
			$cont = explode( '|', $params['continue'] );
			$this->dieContinueUsageIf( count( $cont ) != 2 );
			$continueNs = intval( $cont[0] );
			$this->dieContinueUsageIf( strval( $continueNs ) !== $cont[0] );
			$continue = $cont[1];
		} else {
			$continue = false;
			$continueNs = false;
		}

		$items = array_map( function( $v ) {
			return Title::newFromText( $v );
		}, $list->items );

		$items = array_filter( $items, function( Title $v ) use ( $params ) {
			return !$params['namespace'] || in_array( $v->getNamespace(), $params['namespace'] );
		} );

		usort( $items, function( Title $a, Title $b ) use ( $isAscending ) {
			$d = $a->getNamespace() - $b->getNamespace();
			if ( $d === 0 ) {
				$d = strcmp( $a->getText(), $b->getText() );
			}
			return $isAscending ? $d : -$d;
		} );

		$count = 0;
		$titles = array();
		foreach ( $items as $row ) {
			if ( $continue !== false ) {
				/** @var Title $row */
				$ns = $row->getNamespace();
				if ( $isAscending ? $ns < $continueNs : $ns > $continueNs  ) {
					continue;
				}
				if ( $ns === $continueNs ) {
					$cmp = strcmp( $row->getText(), $continue );
					if ( $isAscending ? $cmp < 0 : $cmp > 0 ) {
						continue;
					}
				}
				$continue = false;
			}
			$count++;
			if ( $count > $limit ) {
				$this->setContinueEnumParameter( 'continue', $row->getNamespace() . '|' . $row->getText() );
				break;
			}
			if ( is_null( $resultPageSet ) ) {
				$vals = array();
				ApiQueryBase::addTitleInfo( $vals, $row );
				$fit = $this->getResult()->addValue( $this->getModuleName(), null, $vals );
				if ( !$fit ) {
					$this->setContinueEnumParameter( 'continue', $row->getNamespace() . '|' . $row->getText() );
					break;
				}
			} else {
				$titles[] = $row;
			}
		}
		if ( is_null( $resultPageSet ) ) {
			$this->getResult()->setIndexedTagName_internal( $this->getModuleName(), 'wr' );
		} else {
			$resultPageSet->populateFromTitles( $titles );
		}
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
