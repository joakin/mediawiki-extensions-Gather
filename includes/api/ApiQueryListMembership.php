<?php
/**
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

use ApiQueryBase;
use ApiQuery;
use LinkBatch;
use ApiBase;

/**
 * A query prop module to show if pages belong to a specific list
 *
 * @ingroup API
 */
class ApiQueryListMembership extends ApiQueryBase {

	public function __construct( ApiQuery $query, $moduleName ) {
		parent::__construct( $query, $moduleName, 'lsm' );
	}

	public function execute() {
		$titles = $this->getPageSet()->getGoodAndMissingTitles();
		$titleLookup = $this->getPageSet()->getGoodAndMissingTitlesByNamespace();

		if ( !count( $titles ) ) {
			# Nothing to do
			return;
		}

		$params = $this->extractRequestParams();
		$db = $this->getDB();

		// watchlist and gather_list_item tables are very similar, and have one identifying value -
		// UserID for watchlists, and ListID for lists. CheckListAccess will tell us which table we
		// should use. If userId is returned, use watchlist, otherwise the $params['id'] is valid,
		// and should be used for the gather_list_item.
		ApiMixinListAccess::checkListAccess( $db, $this, $params, $isWatchlist, $ownerId );

		if ( $isWatchlist ) {
			$prefix = 'wl';
			$db = $this->selectNamedDB( 'watchlist', DB_SLAVE, 'watchlist' );
			$this->addTables( 'watchlist' );
			$this->addWhereFld( 'wl_user', $ownerId );
		} else {
			$prefix = 'gli';
			$this->addTables( 'gather_list_item' );
			$this->addWhereFld( 'gli_gl_id', $params['id'] );
		}

		$this->addFields( array( 'ns' => "{$prefix}_namespace", 'title' => "{$prefix}_title" ) );

		$lb = new LinkBatch( $titles );
		$this->addWhere( $lb->constructSet( $prefix, $db ) );

		if ( $params['continue'] !== null ) {
			$cont = explode( '|', $params['continue'] );
			$this->dieContinueUsageIf( count( $cont ) != 2 );
			$contNs = intval( $cont[0] );
			$this->dieContinueUsageIf( strval( $contNs ) !== $cont[0] );
			$contTitle = $db->addQuotes( $cont[1] );
			$this->addWhere( "{$prefix}_namespace > $contNs OR " .
							 "({$prefix}_namespace = $contNs AND " . "{$prefix}_title >= $contTitle)" );
		}

		// Don't ORDER BY namespace if it's constant in the WHERE clause
		if ( count( $titleLookup ) === 1 ) {
			$this->addOption( 'ORDER BY', "{$prefix}_title" );
		} else {
			$this->addOption( 'ORDER BY', array( "{$prefix}_namespace", "{$prefix}_title" ) );
		}

		// NOTE: We never set listmembership=false because we don't really know which ones are not
		// in the database. If we ran out of memory halfway and need to continue, next time we will
		// skip those already done, so even though DB contains rows, we skipped them and gotten
		// the next batch. In other words, the pages that at the end of this module do not have
		// listmembership=true might still be true, but they were reported in the previous API call.

		$result = $this->getResult();
		foreach ( $this->select( __METHOD__ ) as $row ) {
			$ns = intval( $row->ns );
			if ( !isset( $titleLookup[$ns][$row->title] ) ) {
				wfDebug( __METHOD__ . " Unexpected DB row {$row->ns}:{$row->title}\n" );
				continue;
			}
			$fit = $result->addValue( array( 'query', 'pages', $titleLookup[$ns][$row->title] ),
				'listmembership', true );
			if ( !$fit ) {
				$this->setContinueEnumParameter( 'continue', $row->ns . '|' . $row->title );
				break;
			}
		}
	}

	public function getCacheMode( $params ) {
		return 'anon-public-user-private';
	}

	public function getAllowedParams() {
		return array_merge( ApiMixinListAccess::getListAccessParams(), array(
			'continue' => array(
				ApiBase::PARAM_HELP_MSG => 'api-help-param-continue',
			),
		) );
	}

	protected function getExamplesMessages() {
		return array(
			'action=query&prop=listmembership&titles=Page&lsmid=0'
			=> 'apihelp-query+listmembership-example-1',
		);
	}

	public function getHelpUrls() {
		return '//www.mediawiki.org/wiki/Extension:Gather';
	}
}
