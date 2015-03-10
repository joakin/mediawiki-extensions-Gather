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
use FormatJson;
use stdClass;

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

		$db = $this->getDB();
		$this->addTables( 'gather_list' );
		$this->addFields( 'gl_id' );
		$this->addFields( 'gl_label' );
		$this->addWhereFld( 'gl_user', $user->getId() );

		$continue = $params['continue'];
		if ( $continue ) {
			$cont_from = $db->addQuotes( $continue );
			$this->addWhere( "gl_label >= $cont_from" );
		}

		$fld_label = in_array( 'label', $params['prop'] );
		$fld_description = in_array( 'description', $params['prop'] );
		$fld_public = in_array( 'public', $params['prop'] );
		$fld_image = in_array( 'image', $params['prop'] );
		$useInfo = $fld_description || $fld_public || $fld_image;

		$this->addFieldsIf( 'gl_info', $useInfo );

		$limit = $params['limit'];
		$this->addOption( 'LIMIT', $limit + 1 );
		$this->addOption( 'ORDER BY', 'gl_label' );

		$count = 0;
		$path = array( 'query', $this->getModuleName() );

		// This closure will process one row, even if that row is fake watchlist
		$processRow = function( $row ) use ( &$count, $limit, $fld_label, $useInfo,
			$fld_description, $fld_public, $fld_image, $path
		) {
			if ( $row === null ) {
				// Fake watchlist row
				$row = (object) array(
					'gl_id' => '0',
					'gl_label' => '',
					'gl_info' => '',
				);
			}

			$count++;

			if ( $count > $limit ) {
				// We've reached the one extra which shows that there are
				// additional pages to be had. Stop here...
				$this->setContinueEnumParameter( 'continue', $row->gl_label );
				return false;
			}

			$data = array( 'id' => intval( $row->gl_id ) );
			if ( $fld_label ) {
				// TODO: check if this is the right wfMessage to show
				$data['label'] = $row->gl_label !== ''
					? $row->gl_label
					: wfMessage( 'watchlist' )->plain();
			}

			if ( $useInfo ) {
				if ( $row->gl_info ) {
					$info = FormatJson::parse( $row->gl_info );
					if ( !$info->isOK() ) {
						wfLogWarning( 'Bad info ID=' . $row->gl_id );
						return true;
					}
					$info = $info->getValue();
				} else {
					$info = new stdClass();
				}
				if ( $fld_description ) {
					$data['description'] = property_exists( $info, 'description' ) ? $info->description : '';
				}
				if ( $fld_public ) {
					$data['public'] = property_exists( $info, 'public' ) ? $info->public : '';
				}
				if ( $fld_image ) {
					$data['image'] = property_exists( $info, 'image' ) ? $info->image : '';
				}
			}

			$fit = $this->getResult()->addValue( $path, null, $data );
			if ( !$fit ) {
				$this->setContinueEnumParameter( 'continue', $row->gl_label );
				return false;
			}
			return true;
		};

		// Watchlist, having the label set to '', should always appear first
		// If it doesn't, make sure to insert a fake one in the result
		// $injectWatchlist is true if we should inject a fake watchlist row if its missing
		// This code depends on the result ordered by label, and that watchlist label === ''
		$injectWatchlist = !$continue;

		foreach ( $this->select( __METHOD__ ) as $row ) {
			if ( $injectWatchlist ) {
				if ( $row->gl_label	) {
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
