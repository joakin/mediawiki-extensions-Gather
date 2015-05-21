<?php
/**
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
use DatabaseBase;

/**
 * Shared code for List's API
 *
 * @ingroup API
 */
class ApiMixinListAccess {

	/**
	 * Get parameters used to identify a list with ownership
	 * @return array
	 */
	public static function getListAccessParams() {
		return array(
			'id' => array(
				ApiBase::PARAM_HELP_MSG => 'gather-api-help-param-listid',
				ApiBase::PARAM_DFLT => 0,
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_MIN => 0,
			),
			'owner' => array(
				ApiBase::PARAM_HELP_MSG => 'gather-api-help-param-listowner',
				ApiBase::PARAM_TYPE => 'user',
			),
			'token' => array(
				ApiBase::PARAM_HELP_MSG => 'gather-api-help-param-listtoken',
				ApiBase::PARAM_TYPE => 'string',
			),
		);
	}

	/**
	 * Ensure that current params make sense, specify an existing list, and the requesting user has
	 * access to it. Die if that's not the case.
	 * @param DatabaseBase $db
	 * @param ApiBase $module
	 * @param array $params module parameters
	 * @param bool $isWatchlist Will be set to true if the requested list is a watchlist
	 * @param int $ownerId Will be set to the user ID of the list's owner
	 * @throws \UsageException In case access is not allowed
	 */
	public static function checkListAccess(
		DatabaseBase $db, ApiBase $module, array $params, &$isWatchlist, &$ownerId
	) {
		global $wgGatherAutohideFlagLimit;

		if ( is_null( $params['owner'] ) !== is_null( $params['token'] ) ) {
			$p = $module->getModulePrefix();
			$module->dieUsage( "Both {$p}owner and {$p}token must be given or missing",
				'invalidparammix' );
		}

		if ( !$params['id'] ) {
			// If collection id is not given (or equals to 0), this is a watchlist access;
			// ApiBase::getWatchlistUser does all the necessary checks
			$isWatchlist = true;
			$ownerId = $module->getWatchlistUser( $params )->getId();
			return;
		}

		// Id was given, this could be public or private list, legacy watchlist or regular
		// Allow access to any public list/watchlist, and to private with proper owner/self
		$listRow = $db->selectRow( 'gather_list',
			array( 'gl_label', 'gl_user', 'gl_perm', 'gl_perm_override', 'gl_flag_count',
				'gl_needs_review' ),
			array( 'gl_id' => $params['id'] ),
			__METHOD__ );
		if ( $listRow === false ) {
			$module->dieUsage( 'List does not exist', 'badid' );
		}

		$listRow = ApiEditList::normalizeRow( $listRow );
		if ( $params['owner'] !== null ) {
			// Caller supplied token: treat them as trusted, someone who could see even private
			// At the same time, owner param must match list's owner
			// TODO: if we allow non-matching owner, we could treat it as public-only,
			// but that might be unexpected behavior
			$user = $module->getWatchlistUser( $params );
			if ( $listRow->gl_user !== $user->getId() ) {
				$module->dieUsage( 'The owner supplied does not match the list\'s owner',
					'permissiondenied' );
			}
			$showPrivate = true;
		} else {
			$user = $module->getUser();
			$showPrivate =
				$user->isLoggedIn() && $listRow->gl_user === $user->getId() &&
				$user->isAllowed( 'viewmywatchlist' );
		}

		// Check if this is a public list (if required)
		if ( !$showPrivate && (
			$listRow->gl_perm !== ApiEditList::PERM_PUBLIC
			|| $listRow->gl_perm_override === ApiEditList::PERM_OVERRIDE_HIDDEN
			|| $listRow->gl_flag_count >= $wgGatherAutohideFlagLimit
			   && $listRow->gl_perm_override !== ApiEditList::PERM_OVERRIDE_APPROVED
		) ) {
			$module->dieUsage( 'You have no rights to see this list', 'badid' );
		}

		// If true, this is actually a watchlist, and it is either public or belongs to current user
		$isWatchlist = $listRow->gl_label === '';
		$ownerId = $listRow->gl_user;
	}
}
