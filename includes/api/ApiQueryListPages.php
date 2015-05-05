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
		$isGenerator = $resultPageSet !== null;

		ApiMixinListAccess::checkListAccess( $this->getDB(), $this, $params, $isWatchlist, $ownerId );

		if ( $isWatchlist ) {
			$titles = $this->queryLegacyWatchlist( $params, $isGenerator, $ownerId );
		} else {
			$titles = $this->queryListItems( $params, $isGenerator );
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

		$sort = ( $params['dir'] == 'descending' ? ' DESC' : '' );
		$sortField = 'gli_order' . $sort;
		if ( $params['sort'] === 'namespace' ) {
			$sortField = array( 'gli_namespace' . $sort, 'gli_title' . $sort );
		}
		$this->addOption( 'ORDER BY', $sortField );

		if ( isset( $params['continue'] ) ) {
			if ( $params['sort'] === 'namespace' ) {
				$cont = explode( '|', $params['continue'] );
				$this->dieContinueUsageIf( count( $cont ) !== 2 );
				$continueFromNamespace = intval( $cont[0] );
				$continueFromTitle = $cont[1];
				$this->dieContinueUsageIf( strval( $continueFromNamespace ) !== $cont[0] );
				$continueFromNamespace = $this->getDB()->addQuotes( $continueFromNamespace );
				$continueFromTitle = $this->getDB()->addQuotes( $continueFromTitle );
				$op = $params['dir'] == 'ascending' ? '>' : '<';
				$this->addWhere(
					"gli_namespace $op $continueFromNamespace OR " .
					"(gli_namespace = $continueFromNamespace AND gli_title $op= $continueFromTitle)"
				);
			} else {
				$continueFromOrder = floatval( $params['continue'] );
				$this->dieContinueUsageIf( strval( $continueFromOrder ) !== $params['continue'] );
				$cont = $this->getDB()->addQuotes( $continueFromOrder );
				$op = $params['dir'] == 'ascending' ? '>=' : '<=';
				$this->addWhere( "gli_order $op $cont" );
			}
		}

		$this->addOption( 'LIMIT', $params['limit'] + 1 );
		$res = $this->select( __METHOD__ );

		$titles = array();
		$count = 0;
		$needContinue = false;
		foreach ( $res as $row ) {
			if ( ++$count > $params['limit'] ) {
				// We've reached the one extra which shows that there are
				// additional pages to be had. Stop here...
				$needContinue = true;
				break;
			}
			$t = Title::makeTitle( $row->gli_namespace, $row->gli_title );
			if ( !$isGenerator ) {
				$vals = array();
				ApiQueryBase::addTitleInfo( $vals, $t );
				$fit = $this->getResult()->addValue( $this->modulePath, null, $vals );
				if ( !$fit ) {
					$needContinue = true;
					break;
				}
			} else {
				$titles[] = $t;
			}
		}
		if ( $needContinue ) {
			// PHP does not scope iterators to the loop, which is handy here.
			if ( $params['sort'] === 'namespace' ) {
				$this->setContinueEnumParameter( 'continue', $row->gli_namespace . '|' . $row->gli_title );
			} else {
				$this->setContinueEnumParameter( 'continue', $row->gli_order );
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
		$needContinue = false;
		foreach ( $res as $row ) {
			if ( ++$count > $params['limit'] ) {
				// We've reached the one extra which shows that there are
				// additional pages to be had. Stop here...
				$needContinue = true;
				break;
			}
			$t = Title::makeTitle( $row->wl_namespace, $row->wl_title );
			if ( !$isGenerator ) {
				$vals = array();
				ApiQueryBase::addTitleInfo( $vals, $t );
				$fit = $this->getResult()->addValue( $this->modulePath, null, $vals );
				if ( !$fit ) {
					$needContinue = true;
					break;
				}
			} else {
				$titles[] = $t;
			}
		}
		if ( $needContinue ) {
			// PHP does not scope iterators to the loop, which is handy here.
			$this->setContinueEnumParameter( 'continue', $row->wl_namespace . '|' . $row->wl_title );
		}
		return $titles;
	}

	public function getCacheMode( $params ) {
		return 'anon-public-user-private';
	}

	public function getAllowedParams() {
		return array_merge( ApiMixinListAccess::getListAccessParams(), array(
			'continue' => array(
				ApiBase::PARAM_HELP_MSG => 'api-help-param-continue',
			),
			'namespace' => array(
				ApiBase::PARAM_ISMULTI => true,
				ApiBase::PARAM_TYPE => 'namespace',
			),
			'sort' => array(
				ApiBase::PARAM_DFLT => 'position',
				ApiBase::PARAM_TYPE => array(
					'position',
					'namespace',
				),
				ApiBase::PARAM_HELP_MSG_PER_VALUE => array(),
			),
			'dir' => array(
				ApiBase::PARAM_DFLT => 'ascending',
				ApiBase::PARAM_TYPE => array(
					'ascending',
					'descending',
				),
				ApiBase::PARAM_HELP_MSG_PER_VALUE => array(),
			),
			'limit' => array(
				ApiBase::PARAM_DFLT => 10,
				ApiBase::PARAM_TYPE => 'limit',
				ApiBase::PARAM_MIN => 1,
				ApiBase::PARAM_MAX => ApiBase::LIMIT_BIG1,
				ApiBase::PARAM_MAX2 => ApiBase::LIMIT_BIG2,
			),
		) );
	}

	protected function getExamplesMessages() {
		return array(
			'action=query&list=listpages' => 'apihelp-query+listpages-example-1',
			'action=query&list=listpages&lspid=2' => 'apihelp-query+listpages-example-2',
		);
	}

	public function getHelpUrls() {
		return '//www.mediawiki.org/wiki/Extension:Gather';
	}
}
