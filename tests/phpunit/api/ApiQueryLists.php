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

	/** @var TestUser[] */
	private static $wlUsers = null;

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
		if ( !self::$wlUsers ) {
			foreach ( array(
						  'GatherML',
						  'GatherML2',
						  'GatherWML',
						  'GatherWML2',
						  'GatherWlOnly',
						  'GatherWlOnly2',
					  ) as $name ) {
				self::$wlUsers[$name] = new TestUser( $name );
			}
		}
		self::$users = array_merge( self::$users, self::$wlUsers );
	}

	public function testAnonymous() {
		$a = User::newFromId( 0 );

		$this->assertUsage( 'an-0', '{ "list": "lists" }', $a );
		$this->assertUsage( 'an-1', '{ "list": "lists", "lstids": 0 }', $a );
	}

	public function testMultipleLists() {
		$this->allTests( false );
	}

	public function testMultipleListsWithWatchlist() {
		$this->allTests( true );
	}

	private function allTests( $createWatchlist ) {
		$p = $createWatchlist ? 'Test With watchlist: #' : 'Test without watchlist: #';
		$n = $createWatchlist ? 'GatherWML' : 'GatherML';
		$n2 = $createWatchlist ? 'GatherWML2' : 'GatherML2';

		$a = User::newFromId( 0 ); // Anonymous user
		$u = self::$users[$n]->getUser(); // User for this test
		$u2 = self::$users[$n2]->getUser(); // Second user for this test

		$token = $this->getToken( $u );

		if ( $createWatchlist ) {
			// Create watchlist row
			$res = $this->updateList( "$p a1", '{"id":0,"description":"x"}', $u, $token );
			$wlId = $this->getVal( "$p a1", '"id"', $res );
		} else {
			$wlId = 0;
		}

		// Add pages to various lists
		$res = $this->updateList( "$p a1",
			'{"id":0,"titles":"Gather-ListW|Gather-ListWA|Gather-ListWAB"}', $u, $token );
		$this->getVal( "$p a1", '"status"', $res, 'nochanges' );
		$this->getVal( "$p a1", '"pages",0,"added"', $res, '' );
		$this->getVal( "$p a1", '"pages",1,"added"', $res, '' );
		$this->getVal( "$p a1", '"pages",2,"added"', $res, '' );
		$this->getVal( "$p a1", '"id"', $res, $wlId );

		$res = $this->updateList( "$p a2",
			'{"label":"A","titles":"Gather-ListWA|Gather-ListWAB"}', $u, $token );
		$this->getVal( "$p a2", '"status"', $res, 'created' );
		$this->getVal( "$p a2", '"pages",0,"added"', $res, '' );
		$this->getVal( "$p a2", '"pages",1,"added"', $res, '' );
		$idA = $this->getVal( "$p a2", '"id"', $res );

		$res = $this->updateList( "$p a3",
			'{"label":"B", "perm":"public", "titles":"Gather-ListWAB"}', $u, $token );
		$this->getVal( "$p a3", '"status"', $res, 'created' );
		$this->getVal( "$p a3", '"pages",0,"added"', $res, '' );
		$idB = $this->getVal( "$p a3", '"id"', $res );

		$res = $this->callApi( "$p b1", '{ "list": "lists" }', $u );
		$this->assertListNoId( "$p b1", $res, $wlId
			? '[{"watchlist":true,"label":"Watchlist"}, {"label":"A"}, {"label":"B"}]'
			: '[{"id":0, "watchlist":true,"label":"Watchlist"}, {"label":"A"}, {"label":"B"}]' );
		$this->getVal( "$p b1", '"query","lists",1,"id"', $res, $idA );
		$this->getVal( "$p b1", '"query","lists",2,"id"', $res, $idB );

		//
		// Continuation
		$request = $this->toApiParams( "$p c1", '{ "list": "lists", "lstlimit": 1 }' );

		$res = $this->callApi( "$p c1", $request, $u );
		$this->assertListsEquals( "$p c1a", $res,
			'[{"id":' . $wlId . ', "watchlist":true,"label":"Watchlist"}]' );
		$continue = $this->getVal( "$p c1b", '"continue"', $res );

		$res = $this->callApi( "$p c2", array_merge( $continue, $request ), $u );
		$this->assertListNoId( "$p c2a", $res, '[{"label":"A"}]' );
		$continue = $this->getVal( "$p c2b", '"continue"', $res );

		$res = $this->callApi( "$p c3", array_merge( $continue, $request ), $u );
		$this->assertListNoId( "$p c3a", $res, '[{"label":"B"}]' );
		$this->assertArrayNotHasKey( 'continue', $res, "$p c3c" );

		//
		// ids=A
		$res = $this->callApi( "$p d1", '{ "list": "lists", "lstids":' . $idA . ' }', $u );
		$this->assertListNoId( "$p d1", $res, '[{"label":"A"}]' );

		// ids=A as anon user
		$res = $this->callApi( "$p d2", '{ "list": "lists", "lstids":' . $idA . ' }', $a );
		$this->assertListNoId( "$p d2", $res, '[]' );

		// ids=A as another user
		$res = $this->callApi( "$p d3", '{ "list": "lists", "lstids":' . $idA . ' }', $u2 );
		$this->assertListNoId( "$p d3", $res, '[]' );

		//
		// ids=B
		$res = $this->callApi( "$p e1", '{ "list": "lists", "lstids":' . $idB . ' }', $u );
		$this->assertListNoId( "$p e1", $res, '[{"label":"B"}]' );

		// ids=B as anon user
		$res = $this->callApi( "$p e2", '{ "list": "lists", "lstids":' . $idB . ' }', $a );
		$this->assertListNoId( "$p e2", $res, '[{"label":"B"}]' );

		// ids=B as another user
		$res = $this->callApi( "$p e3", '{ "list": "lists", "lstids":' . $idB . ' }', $u2 );
		$this->assertListNoId( "$p e3", $res, '[{"label":"B"}]' );

		//
		// Use owner param
		// user: get all with owner=user
		$res = $this->callApi( "$p i0", '{ "list": "lists", "lstowner": "' . $n . '" }', $u );
		$this->assertListsEquals( "$p i0", $res,
			'[{"id":' . $wlId . ', "watchlist":true,"label":"Watchlist"}, ' .
			'{"id":' . $idA . ', "label":"A"},' . '{"id":' . $idB . ', "label":"B"}]' );

		// user: get by idA with owner=user
		$res = $this->callApi( "$p i0a", '{ "list": "lists", "lstowner": "' . $n .
			'", "lstids": ' . $idA . ' }', $u );
		$this->assertListsEquals( "$p i0a", $res, '[{"id":' . $idA . ', "label":"A"}]' );

		// anon: get all with owner=user
		$res = $this->callApi( "$p i1", '{ "list": "lists", "lstowner": "' . $n .
									   '" }', $a );
		$this->assertListNoId( "$p i1", $res, '[{"label":"B"}]' );

		// anon: get by idA with owner=user
		$res = $this->callApi( "$p i2", '{ "list": "lists", "lstowner": "' . $n .
									   '", "lstids": ' . $idA . ' }', $a );
		$this->assertListNoId( "$p i2", $res, '[]' );

		// anon: get by idB with owner=user
		$res = $this->callApi( "$p i3", '{ "list": "lists", "lstowner": "' . $n .
									   '", "lstids": ' . $idB . ' }', $a );
		$this->assertListNoId( "$p i3", $res, '[{"label":"B"}]' );

		// user2: get all with owner=user
		$res = $this->callApi( "$p i4", '{ "list": "lists", "lstowner": "' . $n . '" }', $u2 );
		$this->assertListNoId( "$p i4", $res, '[{"label":"B"}]' );

		// user2: get by idA with owner=user
		$res = $this->callApi( "$p i5", '{ "list": "lists", "lstowner": "' . $n .
									   '", "lstids": ' . $idA . ' }', $u2 );
		$this->assertListNoId( "$p i5", $res, '[]' );

		// user2: get by idB with owner=user
		$res = $this->callApi( "$p i5", '{ "list": "lists", "lstowner": "' . $n .
									   '", "lstids": ' . $idB . ' }', $u2 );
		$this->assertListNoId( "$p i5", $res, '[{"label":"B"}]' );
	}

	public function testWatchlistOnly() {
		$u = self::$users['GatherWlOnly']->getUser(); // User for this test
		$n = $u->getName(); // Name of the user for this test
		$a = User::newFromId( 0 ); // Anonymous user
		$u2 = self::$users['GatherWlOnly2']->getUser(); // Second user for this test

		$token = $this->getToken( $u );
		$wlOnly = '[{"id":0, "watchlist":true, "label":"Watchlist"}]';

		//
		// Validate empty watchlist / lists
		$res = $this->callApi( 'nc-a0', '{ "list": "lists" }', $u );
		$this->assertListNoId( 'nc-a0', $res, $wlOnly );

		$res = $this->callApi( 'nc-a1', '{ "list": "lists", "lstids": 0 }', $u );
		$this->assertListNoId( 'nc-a1', $res, $wlOnly );

		$res = $this->callApi( 'nc-a2', '{ "list": "lists", "lstlimit": 1 }', $u );
		$this->assertListNoId( 'nc-a2', $res, $wlOnly );

		$res = $this->callApi( 'nc-a3',
			'{ "list": "lists", "lstprop": "label|description|public|count" }', $u );
		$this->assertListNoId( 'nc-a3', $res,
			'[{"id":0,"watchlist":true,"count":0,"label":"Watchlist","description":"","public":false}]'
		);
		$res = $this->callApi( 'nc-a4', '{ "list": "lists", "lsttitle": "Missing" }', $u );
		$this->assertListNoId( 'nc-a4', $res,
			'[{"id":0,"watchlist":true,"label":"Watchlist","title":false}]' );

		//
		// Add page to watchlist
		$this->legacyAddToWatchlist( 'nc-b0', 'Gather-ListW', $u, $token );
		$res = $this->callApi( 'nc-b0', '{ "list": "lists", "lstprop": "count" }', $u );
		$this->assertListNoId( 'nc-b0', $res, '[{"id": 0, "watchlist":true, "count": 1}]' );

		$res = $this->callApi( 'nc-b1', '{ "list": "lists", "lsttitle": "Gather-ListW" }', $u );
		$this->assertListNoId( 'nc-b1', $res,
			'[{"id":0,"watchlist":true,"label":"Watchlist","title":true}]' );

		//
		// Re-add the same page, using action=editlist & id=0
		$res = $this->updateList( 'nc-c0', '{"id":0,"titles":"Gather-ListW"}', $u, $token );
		$this->getVal( "nc-c0", '"status"', $res, 'nochanges' );
		$this->getVal( "nc-c0", '"id"', $res, 0 );
		$this->getVal( "nc-c0", '"pages",0,"added"', $res, '' );

		$res = $this->callApi( 'nc-c0a', '{ "list": "lists" }', $u );
		$this->assertListNoId( 'nc-c0a', $res, $wlOnly );

		$res = $this->callApi( 'nc-c1', '{ "list": "lists", "lstids": 0 }', $u );
		$this->assertListNoId( 'nc-c1', $res, $wlOnly );

		$res = $this->callApi( 'nc-c3', '{ "list": "lists", "lstprop": "count" }', $u );
		$this->assertListNoId( 'nc-c3', $res, '[{"id":0, "watchlist":true, "count": 1}]' );

		$res = $this->callApi( 'nc-c4', '{ "list": "lists", "lsttitle": "Gather-ListW" }', $u );
		$this->assertListNoId( 'nc-c4', $res,
			'[{"id":0,"watchlist":true,"label":"Watchlist","title":true}]' );

		$res = $this->callApi( 'nc-c5',
			'{ "list": "lists", "lstids": 0, "lsttitle": "Gather-ListW" }', $u );
		$this->assertListNoId( 'nc-c5', $res,
			'[{"id":0,"watchlist":true,"label":"Watchlist","title":true}]' );

		//
		// What can others see from this user
		$res = $this->callApi( 'nc-e0', '{ "list": "lists", "lstowner": "' . $n . '" }', $a );
		$this->assertListNoId( 'nc-e0', $res, '[]' );

		$res = $this->callApi( 'nc-e1', '{ "list": "lists", "lstowner": "' . $n .
			'", "lstids": 0 }', $a );
		$this->assertListNoId( 'nc-e1', $res, '[]' );

		$res = $this->callApi( 'nc-e2', '{ "list": "lists", "lstowner": "' . $n . '" }', $u2 );
		$this->assertListNoId( 'nc-e2', $res, '[]' );

		$res =  $this->callApi( 'nc-e3',
			'{ "list": "lists", "lstowner": "' . $n . '", "lstids": 0 }', $u2 );
		$this->assertListNoId( 'nc-e3', $res, '[]' );

		//
		// Create watchlist list DB record
		$res = $this->updateList( 'nc-f0', '{ "id":0, "description":"aa" }', $u, $token );
		$this->getVal( 'nc-f0', '"status"', $res, 'created' );
		$id = $this->getVal( 'nc-f0', '"id"', $res );
		$this->assertNotEquals( 0, $id );

		$wlOnly = array( array( 'id' => $id, 'watchlist' => true, 'label' => 'Watchlist' ) );

		$res = $this->callApi( 'nc-f2', '{ "list": "lists" }', $u );
		$this->assertListsEquals( 'nc-f2', $res, $wlOnly );

		$res = $this->callApi( 'nc-f3', '{ "list": "lists", "lstids": 0 }', $u );
		$this->assertListsEquals( 'nc-f3', $res, $wlOnly );

		$res = $this->callApi( 'nc-f4',
			'{ "list": "lists", "lstprop": "label|description|public|count" }', $u );
		$this->assertListsEquals( 'nc-f4', $res,
			'[{"id":' . $id .
			',"watchlist":true,"count":1,"label":"Watchlist","description":"aa","public":false}]' );

		$res = $this->callApi( 'nc-f5', '{ "list": "lists", "lsttitle": "Gather-ListW" }', $u );
		$this->assertListsEquals( 'nc-f5', $res, '[{"id":' . $id .
			',"watchlist":true,"label":"Watchlist","title":true}]' );

		//
		// Others still can't see the watchlist
		$res = $this->callApi( 'nc-g0', '{ "list": "lists", "lstowner": "' . $n . '" }', $a );
		$this->assertListNoId( 'nc-g0', $res, '[]' );

		$res = $this->callApi( 'nc-g1', '{ "list": "lists", "lstowner": "' . $n .
			'", "lstids": 0 }', $a );
		$this->assertListNoId( 'nc-g1', $res, '[]' );

		$res = $this->callApi( 'nc-g2', '{ "list": "lists", "lstids": ' . $id . ' }', $a );
		$this->assertListNoId( 'nc-g2', $res, '[]' );

		$res = $this->callApi( 'nc-g3', '{ "list": "lists", "lstowner": "' . $n . '", "lstids": ' .
				$id . ' }', $a );
		$this->assertListNoId( 'nc-g3', $res, '[]' );

		$res = $this->callApi( 'nc-h0', '{ "list": "lists", "lstowner": "' . $n . '" }', $u2 );
		$this->assertListNoId( 'nc-h0', $res, '[]' );

		$res = $this->callApi( 'nc-h1', '{ "list": "lists", "lstowner": "' . $n .
			'", "lstids": 0 }', $u2 );
		$this->assertListNoId( 'nc-h1', $res, '[]' );

		$res = $this->callApi( 'nc-h2', '{ "list": "lists", "lstids": ' . $id . ' }', $u2 );
		$this->assertListNoId( 'nc-h2', $res, '[]' );

		$res = $this->callApi( 'nc-h3', '{ "list": "lists", "lstowner": "' . $n . '", "lstids": ' .
				$id . ' }', $u2 );
		$this->assertListNoId( 'nc-h3', $res, '[]' );

		//
		// Watchlist editing assertions
		$this->assertUsage( 'nc-i0', '{ "action": "editlist", "id":0, "label":"bb" }', $u );
		$this->assertUsage( 'nc-i1', '{ "action": "editlist", "id":' . $id . ', "label":"bb" }', $u );
	}

	private function assertListNoId( $message, $actual, $expected ) {
		$this->assertListsEquals( $message, $actual, $expected, true );
	}

	private function assertListsEquals( $message, $actual, $expected, $removeIds = false ) {
		$actual = $this->getVal( $message, '"query", "lists"', $actual );
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
		$res = $this->callApi( $message, $params, $user );
		return $this->getVal( $message, array( $params['action'] ), $res );

	}

	private function legacyAddToWatchlist( $message, $titles, $user, $token ) {
		$params = array(
			'action' => 'watch',
			'titles' => $titles,
			'token' => $token,
		);
		$res = $this->callApi( $message, $params, $user );
		$this->getVal( $message, '"watch", 0, "watched"', $res );
	}

	private function getToken( User $user ) {
		$message = 'token-' . $user->getName();
		$res = $this->callApi( $message, array(
			'meta' => 'tokens',
			'type' => 'watch',
		), $user );
		return $this->getVal( $message, '"query", "tokens", "watchtoken"', $res );
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

	private function callApi( $message, $params, User $user = null ) {
		$params = $this->toApiParams( $message, $params );
		$res = $this->doApiRequest( $params, null, false, $user );
		return $res[0];
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

	private function getVal( $message, $path, $result, $expectedValue = null ) {
		$path = $this->toArray( $message, $path );
		$res = $result;
		foreach ( $path as $p ) {
			if ( !array_key_exists( $p, $res ) ) {
				$path = $this->toStr( $path );
				$this->fail( "$message: Request has no key $p of $path in result\n" .
							 $this->toStr( $result, true ) );
			}
			$res = $res[$p];
		}
		if ( $expectedValue !== null ) {
			$this->assertEquals( $expectedValue, $res, $message );
		}
		return $res;
	}
}
