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
		$user = $this->getUser();
		if ( !$user->isLoggedIn() ) {
			$this->dieUsage( 'You must be logged-in to have a list', 'notloggedin' );
		}

		$params = $this->extractRequestParams();

		$limit = $params['limit'];
		$continue = $params['continue'];
		if ( $continue ) {
			$c = intval( $continue );
			$this->dieContinueUsageIf( strval( $c ) !== $continue );
			$continue = $c;
		}

		$prop = array_flip( $params['prop'] );
		$fld_label = isset( $prop['label'] );
		$fld_description = isset( $prop['description'] );

		$manifest = ApiEditList::loadManifest( $user );
		// Create ID 0 (watchlist) if it doesn't exist
		ApiEditList::findList( $manifest, 0, $user );
		usort( $manifest, function ( $a, $b ) { return $a->id - $b->id; } );

		$count = 0;
		$result = $this->getResult();
		$path = array( 'query', $this->getModuleName() );
		foreach ( $manifest as $row ) {

			if ( $continue ) {
				if ( $row->id < $continue ) {
					continue;
				}
				$continue = false;
			}

			$count++;

			if ( $count > $limit ) {
				// We've reached the one extra which shows that there are
				// additional pages to be had. Stop here...
				$this->setContinueEnumParameter( 'continue', $row->id );
				break;
			}

			$data = array( 'id' => $row->id );
			if ( $fld_label ) {
				$data['label'] = $row->title;
			}
			if ( $fld_description ) {
				$data['description'] = $row->description;
			}

			$fit = $result->addValue( $path, null, $data );
			if ( !$fit ) {
				$this->setContinueEnumParameter( 'continue', $row->id );
				break;
			}
		}

		$result->setIndexedTagName_internal( $path, 'c' );
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
					'description'
				)
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
}
