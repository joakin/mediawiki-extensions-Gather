<?php
/**
 *
 * Created on Feb 6, 2013
 *
 * Copyright © 2013 Yuri Astrakhan "<Firstname><Lastname>@gmail.com"
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

/**
 * These tests validate basic functionality of the api query module
 *
 * @group API
 * @group Database
 * @group medium
 * @covers ApiQuery
 */
class ApiQueryLists extends ApiQueryTestBase {
	protected $exceptionFromAddDBData;
	/** @var TestUser */
	private static $noWlUser, $wlUser, $wlOnly;

	static function initUsers() {
		if ( !self::$wlUser ) {
			self::$wlUser = new TestUser( 'GatherWL', 'GatherWL', 'GatherWL@example.com' );
			self::$noWlUser = new TestUser( 'GatherNoWL', 'GatherNoWL', 'GatherNoWL@example.com' );
			self::$wlOnly = new TestUser( 'GatherNoLst', 'GatherNoLst', 'GatherNoLst@example.com' );

			User::createNew( self::$wlUser->getUser()->getName() );
			User::createNew( self::$noWlUser->getUser()->getName() );
			User::createNew( self::$wlOnly->getUser()->getName() );
		}
		self::$users['GatherWL'] = self::$wlUser;
		self::$users['GatherNoWL'] = self::$noWlUser;
		self::$users['GatherNoLst'] = self::$wlOnly;
	}

	/**
	 * Create a set of pages. These must not change, otherwise the tests might give wrong results.
	 * @see MediaWikiTestCase::addDBData()
	 */
	public function addDBData() {
		try {
			if ( !Title::newFromText( 'Gather-All' )->exists() ) {
				$this->editPage( 'Gather-ListA', 'a' );
				$this->editPage( 'Gather-ListB', 'b' );
				$this->editPage( 'Gather-ListAB', 'ab' );
				$this->editPage( 'Gather-ListW', 'w' );
				$this->editPage( 'Gather-ListWA', 'wa' );
				$this->editPage( 'Gather-ListWAB', 'wab' );
			}
		} catch ( Exception $e ) {
			$this->exceptionFromAddDBData = $e;
		}
	}

	protected function setUp() {
		parent::setUp();
		self::initUsers();
	}

	public function testAnonymous() {
		$a = User::newFromId( 0 );

		$this->assertUsage( 'an-0', '{ "list": "lists" }', $a );
		$this->assertUsage( 'an-1', '{ "list": "lists", "lstids": 0 }', $a );
	}

	public function testProps() {
		$wl = self::$wlUser->getUser();

		$res = $this->callApi( 'p-a0', '{ "list": "lists" }', $wl );
		$this->assertListsEquals( 'p-a0', $res, '[{"id":0, "watchlist":true, "label":"Watchlist"}]' );

		$token = $this->getToken( $wl );
		$this->legacyAddToWatchlist( 'p-a1', 'Gather-ListW|Gather-ListWA|Gather-ListWAB', $wl, $token );
		$res = $this->callApi( 'p-a1', '{ "list": "lists" }', $wl );
		$this->assertListsEquals( 'p-a1', $res, '[{"id":0, "watchlist":true, "label":"Watchlist"}]' );

		$this->addToList( 'p-a2a', 'A', 'Gather-ListWA|Gather-ListWAB', $wl, $token );
		$this->addToList( 'p-a2b', 'B', 'Gather-ListWAB', $wl, $token );
		$res = $this->callApi( 'p-a2', '{ "list": "lists" }', $wl );
		$this->assertListsEquals( 'p-a2', $res,
			'[{"id":0, "watchlist":true,"label":"Watchlist"}, {"label":"A"}, {"label":"B"}]' );
	}

	public function testWatchlistOnly() {
		$u = self::$wlOnly->getUser();
		$token = $this->getToken( $u );
		$wlOnly = '[{"id":0, "watchlist":true, "label":"Watchlist"}]';

		//
		// Validate empty watchlist / lists
		$res = $this->callApi( 'nc-a0', '{ "list": "lists" }', $u );
		$this->assertListsEquals( 'nc-a0', $res, $wlOnly );

		$res = $this->callApi( 'nc-a1', '{ "list": "lists", "lstids": 0 }', $u );
		$this->assertListsEquals( 'nc-a1', $res, $wlOnly );

		$res = $this->callApi( 'nc-a2', '{ "list": "lists", "lstlimit": 1 }', $u );
		$this->assertListsEquals( 'nc-a2', $res, $wlOnly );

		$res = $this->callApi( 'nc-a3',
			'{ "list": "lists", "lstprop": "label|description|public|count" }', $u );
		$this->assertListsEquals( 'nc-a3', $res,
			'[{"id":0,"watchlist":true,"count":0,"label":"Watchlist","description":"","public":false}]'
		);

		//
		// Add page to watchlist
		$this->legacyAddToWatchlist( 'nc-b0', 'Gather-ListW', $u, $token );
		$res = $this->callApi( 'nc-b0', '{ "list": "lists", "lstprop": "count" }', $u );
		$this->assertListsEquals( 'nc-b0', $res, '[{"id": 0, "watchlist":true, "count": 1}]' );

		//
		// Re-add the same page, using action=editlist & id=0
		$this->addToList( 'nc-c0', 0, 'Gather-ListW', $u, $token );
		$res = $this->callApi( 'nc-c0', '{ "list": "lists" }', $u );
		$this->assertListsEquals( 'nc-c0', $res, $wlOnly );

		$res = $this->callApi( 'nc-c1', '{ "list": "lists", "lstids": 0 }', $u );
		$this->assertListsEquals( 'nc-c1', $res, $wlOnly );

		$res = $this->callApi( 'nc-c3', '{ "list": "lists", "lstprop": "count" }', $u );
		$this->assertListsEquals( 'nc-c3', $res, '[{"id":0, "watchlist":true, "count": 1}]' );

		//
		// What can others see from this user
		$n = $u->getName();
		$u2 = self::$wlUser->getUser();

		$res = $this->callApi( 'nc-e0', '{ "list": "lists", "lstowner": "' . $n . '" }', $u2 );
		$this->assertListsEquals( 'nc-e0', $res, '[]' );

		$res = $this->callApi( 'nc-e1', '{ "list": "lists", "lstowner": "' . $n .
			'", "lstids": 0 }', $u2 );
		$this->assertListsEquals( 'nc-e1', $res, '[]' );

		//
		// Create watchlist list DB record
		$res = $this->updateList( 'nc-f0', '{ "id":0, "description":"aa" }', $u, $token );
		$this->assertEquals( 'created', $res['status'], 'nc-f0' );
		$this->assertNotEquals( 0, $res['id'], 'nc-f1' );
		$id = $res['id'];
		$wlOnly = array( array( 'id' => $id, 'watchlist' => true, 'label' => 'Watchlist' ) );

		$res = $this->callApi( 'nc-f2', '{ "list": "lists" }', $u );
		$this->assertListsEquals( 'nc-f2', $res, $wlOnly, false );

		$res = $this->callApi( 'nc-f3', '{ "list": "lists", "lstids": 0 }', $u );
		$this->assertListsEquals( 'nc-f3', $res, $wlOnly, false );

		$res = $this->callApi( 'nc-f4',
			'{ "list": "lists", "lstprop": "label|description|public|count" }', $u );
		$this->assertListsEquals( 'nc-f4', $res,
			'[{"id":' . $id .
			',"watchlist":true,"count":1,"label":"Watchlist","description":"aa","public":false}]',
			false );

		//
		// Others still can't see the watchlist
		$res = $this->callApi( 'nc-g0', '{ "list": "lists", "lstowner": "' . $n . '" }', $u2 );
		$this->assertListsEquals( 'nc-g0', $res, '[]' );

		$res = $this->callApi( 'nc-g1', '{ "list": "lists", "lstowner": "' . $n .
			'", "lstids": 0 }', $u2 );
		$this->assertListsEquals( 'nc-g1', $res, '[]' );

		$res = $this->callApi( 'nc-g2', '{ "list": "lists", "lstids": ' . $id . ' }', $u2 );
		$this->assertListsEquals( 'nc-g2', $res, '[]' );

		$res = $this->callApi( 'nc-g3', '{ "list": "lists", "lstowner": "' . $n . '", "lstids": ' .
				$id . ' }', $u2 );
		$this->assertListsEquals( 'nc-g3', $res, '[]' );

		$this->assertUsage( 'nc-i0', '{ "action": "editlist", "id":0, "label":"bb" }', $u );
		$this->assertUsage( 'nc-i1', '{ "action": "editlist", "id":' . $id . ', "label":"bb" }', $u );
	}

	private function assertListsEquals( $message, $actual, $expected, $removeIds = true ) {
		$actual = $this->checkResult( $message, $message, '"query", "lists"', $actual );
		$expected = $this->toArray( $message, $expected );
		if ( $removeIds ) {
			$actual = self::removeIds( $actual );
		}
		$this->assertArrayEquals( $expected, $actual, true, true, $message );
	}

	private function updateList( $message, $params, $user, $token ) {
		$params = $this->toArray( $message, $params );
		if ( !isset( $params['action'] ) ) {
			$params['action'] = 'editlist';
		}
		if ( !isset( $params['token'] ) ) {
			$params['token'] = $token;
		}
		return $this->callApi( $message, $params, $user, array( $params['action'] ) );
	}

	private function addToList( $message, $label, $titles, $user, $token ) {
		$params = array(
			'action' => 'editlist',
			'titles' => $titles,
			'token' => $token,
		);
		$params[is_string( $label ) ? 'label' : 'id'] = $label;
		$this->callApi( $message, $params, $user, '"editlist", "pages", 0, "added"' );
	}

	private function legacyAddToWatchlist( $message, $titles, $user, $token ) {
		$params = array(
			'action' => 'watch',
			'titles' => $titles,
			'token' => $token,
		);
		$this->callApi( $message, $params, $user, '"watch", 0, "watched"' );
	}

	private function getToken( User $user ) {
		return $this->callApi( 'token-' . $user->getName(), array(
			'meta' => 'tokens',
			'type' => 'watch',
		), $user, '"query", "tokens", "watchtoken"' );
	}

	private function assertUsage( $message, $params, User $user = null ) {
		try {
			$params = $this->toApiParams( $message, $params );
			$result = $this->callApi( $message, $params, $user );
			$params = $this->toStr( $params );
			$this->fail( "No UsageException for $params, received:\n" .
				$this->toStr( $result[0], true ) );
		} catch ( UsageException $e ) {
			$this->assertTrue( true );
		}
	}

	private function callApi( $message, $params, User $user = null, $path = '' ) {
		$params = $this->toApiParams( $message, $params );
		$result = $this->doApiRequest( $params, null, false, $user );
		return $path ? $this->checkResult( $message, $params, $path, $result ) : $result;
	}

	private function toApiParams( $message, $params ) {
		$params = $this->toArray( $message, $params );
		if ( !isset( $params['action'] ) ) {
			$params['action'] = 'query';
		}
		if ( $params['action'] === 'query' && !isset( $params['continue'] ) ) {
			$params['continue'] = '';
		}
		return $params;
	}

	private function toArray( $message, $params ) {
		if ( is_string( $params ) && $params ) {
			$p = $params[0] !== '[' && $params[0] !== '{' ? "[$params]" : $params;
			$st = FormatJson::parse( $p, FormatJson::FORCE_ASSOC );
			$this->assertTrue( $st->isOK(), 'invalid JSON value ' . $params, $message );
			$params = $st->getValue();
			return $params;
		}
		return $params;
	}

	private function toStr( $params, $pretty = false ) {
		if ( is_string( $params ) ) {
			return $params;
		}
		return FormatJson::encode( $params, $pretty, FormatJson::ALL_OK );
	}

	private static function removeIds( $arr ) {
		foreach ( $arr as &$v ) {
			if ( array_key_exists( 'id', $v ) && $v['id'] !== 0 ) {
				unset( $v['id'] );
			}
		}
		return $arr;
	}

	private function checkResult( $message, $params, $path, $result ) {
		$path = $this->toArray( $message, $path );
		array_unshift( $path, 0 );
		$res = $result;
		foreach ( $path as $p ) {
			if ( !array_key_exists( $p, $res ) ) {
				$params = $this->toStr( $params );
				$path = $this->toStr( $path );
				$this->fail( "$message: Request $params has no key $p of $path in result\n" .
							 $this->toStr( $result, true ) );
			}
			$res = $res[$p];
		}
		$result = $res;
		return $result;
	}
}
