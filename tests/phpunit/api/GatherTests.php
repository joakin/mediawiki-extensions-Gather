<?php
/**
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

/**
 * These tests validate Gather API functionality
 *
 * @group API
 * @group Database
 * @group medium
 * @covers ApiQuery
 */
class GatherTests extends ApiTestCase {
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
				'GatherE1',
				'GatherE2',
			) as $name ) {
				self::$wlUsers[$name] = new TestUser( $name );
			}
		}
		self::$users = array_merge( self::$users, self::$wlUsers );
	}

	public function testListEditAndPages() {
		$n = 'GatherE1';
		$n2 = 'GatherE2';
		$nS = 'sysop';
		$pageW = array( 'ns' => 0, 'title' => 'Gather-ListW' );
		$pageTW = array( 'ns' => 1, 'title' => 'Talk:Gather-ListW' );
		$pageWA = array( 'ns' => 0, 'title' => 'Gather-ListWA' );
		$pageTWA = array( 'ns' => 1, 'title' => 'Talk:Gather-ListWA' );
		$pageWAB = array( 'ns' => 0, 'title' => 'Gather-ListWAB' );
		$pageTWAB = array( 'ns' => 1, 'title' => 'Talk:Gather-ListWAB' );
		$pageAB = array( 'ns' => 0, 'title' => 'Gather-ListAB' );
		$pageTAB = array( 'ns' => 1, 'title' => 'Talk:Gather-ListAB' );
		$pageB = array( 'ns' => 0, 'title' => 'Gather-ListB' );
		$pageTB = array( 'ns' => 1, 'title' => 'Talk:Gather-ListB' );

		$usr = self::$users[$n]->getUser();
		$usr2 = self::$users[$n2]->getUser();
		$usrS = self::$users[$nS]->getUser();
		$usrA = User::newFromId( 0 ); // Anonymous user

		$token = $this->getToken( $usr );
		$token2 = $this->getToken( $usr2 );
		$tokenS = $this->getToken( $usrS );
		$tokenA = $this->getToken( $usrA );

		// Make sure there are no watchlists yet for these users (starting from clean slate)
		foreach ( array( $usr, $usr2 ) as $user ) {
			$res = $this->getLists( 'ed-a0', $user, '{}' );
			$this->assertListsEquals( 'ed-a0', $res,
				'[{"id":0, "watchlist":true, "label":"Watchlist"}]' );
			$this->assertPages( 'ed-a1', $user, null, array(), array() );
		}

		$this->badUsePage( 'ed-a2', $usr, '"lspid": 9999999' );
		$this->badUsePage( 'ed-a3', $usrA, '"lspid": 9999999' );
		$this->badUsePage( 'ed-a4', $usrA, '{}' );

		// General use
		$this->badUseEdit( 'ed-b1', $usr, $token, '{}' );
		$this->badUseEdit( 'ed-b2', $usr, $token, '"label": ""' );
		// TODO/BUG/SECURITY - Token of one user should not be accepted for another user
		// $this->badUseEdit( 'ed-b3', $u, $token2, '"label": "x"' );
		$this->badUseEdit( 'ed-b4', $usr, $tokenA, '"label": "x"' );
		$this->badUseEdit( 'ed-b5', $usr, false, '"label": "x"' );
		$this->badUseEdit( 'ed-b6', $usrA, $tokenA, '"label": "x"' );
		$this->badUseEdit( 'ed-b7', $usrA, false, '"label": "x"' );

		// watchlist should not be modifiable this way
		$idWL = '"id":0';
		$this->badUseEdit( 'ed-ba1', $usr, false, $idWL . ', "description": "x"' );
		$this->badUseEdit( 'ed-ba2', $usrA, false, $idWL . ', "description": "x"' );
		$this->badUseEdit( 'ed-ba3', $usr, $token, $idWL . ', "label": "x"' );
		$this->badUseEdit( 'ed-ba3a', $usr, $token, $idWL . ', "label": ""' );
		$this->badUseEdit( 'ed-ba4', $usrA, $tokenA, $idWL . ', "label": "x"' );
		$this->badUseEdit( 'ed-ba5', $usr, $tokenA, $idWL . ', "description": "x"' );
		// Test #ba6 is ok for ID=0, but not OK for non-zero (#b6)
		$this->badUseEdit( 'ed-ba7', $usr, $token, $idWL . ', "deletelist": 1' );
		$this->badUseEdit( 'ed-ba8', $usr, $token, $idWL . ', "perm": "public"' );

		$expListsW = array( 'id' => 0, 'watchlist' => true, 'label' => 'Watchlist' );
		$expListsW2 = array_merge( $expListsW, array(
			'public' => false,
			'description' => '',
			'image' => false,
			'count' => 0,
		) );
		$this->assertOneList( 'ed-bb1', $usr, 0, $expListsW, $expListsW2 );

		//
		// Add one page to the non-created watchlist
		$res = $this->editList( 'ed-c1', $usr, $token, $idWL . ', "titles": "Gather-ListW"' );
		$this->getVal( 'ed-c1', '"id"', $res, 0 );
		$this->getVal( 'ed-c1', '"status"', $res, 'nochanges' );
		$this->getVal( 'ed-c1', '"pages", 0, "title"', $res, 'Gather-ListW' );
		$this->getVal( 'ed-c1', '"pages", 0, "added"', $res, '' );

		$expListsW2['count'] = 1;
		$expPagesW = array( $pageW, $pageTW );

		$this->assertPages( 'ed-c2', $usr, null, $expPagesW );
		$this->assertPages( 'ed-c3', $usr, 0, $expPagesW );
		$this->assertOneList( 'ed-c4', $usr, 0, $expListsW, $expListsW2 );

		//
		// Create Watchlist row
		$res = $this->editList( 'ed-d1', $usr, $token, '"id":0, "description": "x"' );
		$id0 = $this->getVal( 'ed-d1', '"id"', $res );
		$idWL = '"id":' . $id0;
		$expListsW['id'] = $id0;
		$expListsW2['id'] = $id0;
		$expListsW2['description'] = 'x';

		$this->assertPages( 'ed-d2', $usr, 0, $expPagesW );
		$this->assertPages( 'ed-d3', $usr, $id0, $expPagesW );
		$this->assertOneList( 'ed-d4', $usr, 0, $expListsW, $expListsW2 );
		$this->assertOneList( 'ed-d5', $usr, $id0, $expListsW, $expListsW2 );
		$this->assertOneList( 'ed-d6', $usrA, $id0, null );
		$this->assertOneList( 'ed-d7', $usr2, $id0, null );
		$this->assertOneList( 'ed-d7a', $usrS, $id0, null );
		$this->badUsePage( 'ed-d8', $usrA, '"lspid": ' . $id0 );
		$this->badUsePage( 'ed-d9', $usr2, '"lspid": ' . $id0 );
		$this->badUsePage( 'ed-d10', $usrS, '"lspid": ' . $id0 );

		// watchlist should not be modifiable this way
		$this->badUseEdit( 'ed-da1', $usr, false, $idWL . ', "description": "x"' );
		$this->badUseEdit( 'ed-da2', $usrA, false, $idWL . ', "description": "x"' );
		$this->badUseEdit( 'ed-da3', $usr, $token, $idWL . ', "label": "x"' );
		$this->badUseEdit( 'ed-da4', $usr, $token, $idWL . ', "label": ""' );
		$this->badUseEdit( 'ed-da5', $usrA, $tokenA, $idWL . ', "label": "x"' );
		$this->badUseEdit( 'ed-da6', $usr, $tokenA, $idWL . ', "description": "x"' );
		$this->badUseEdit( 'ed-da7', $usr2, $token2, $idWL . ', "description": "x"' );
		$this->badUseEdit( 'ed-da7a', $usrS, $tokenS, $idWL . ', "description": "x"' );
		$this->badUseEdit( 'ed-da8', $usr, $token, $idWL . ', "deletelist": 1' );
		$this->badUseEdit( 'ed-da9', $usr, $token, $idWL . ', "perm": "public"' );

		//
		// Add Gather-ListWA to the created watchlist
		$res = $this->editList( 'ed-e1', $usr, $token, $idWL . ', "titles": "Gather-ListWA"' );
		$this->getVal( 'ed-e1', '"id"', $res, $id0 );
		$this->getVal( 'ed-e1', '"status"', $res, 'nochanges' );
		$this->getVal( 'ed-e1', '"pages", 0, "title"', $res, 'Gather-ListWA' );
		$this->getVal( 'ed-e1', '"pages", 0, "added"', $res, '' );

		$expListsW2['count'] = 2;
		$expPagesW = array( $pageW, $pageWA, $pageTW, $pageTWA );

		$this->assertPages( 'ed-e2', $usr, null, $expPagesW );
		$this->assertPages( 'ed-e3', $usr, 0, $expPagesW );
		$this->assertPages( 'ed-e4', $usr, $id0, $expPagesW );
		$this->assertOneList( 'ed-e5', $usr, 0, $expListsW, $expListsW2 );
		$this->assertOneList( 'ed-e6', $usr, $id0, $expListsW, $expListsW2 );

		//
		// Add Gather-ListWAB to the created watchlist with ID=0 and description change
		$res = $this->editList( 'ed-f1', $usr, $token,
			'"id":0, "description":"y", "titles": "Gather-ListWAB"' );
		$this->getVal( 'ed-f1', '"id"', $res, $id0 );
		$this->getVal( 'ed-f1', '"status"', $res, 'updated' );
		$this->getVal( 'ed-f1', '"pages", 0, "title"', $res, 'Gather-ListWAB' );
		$this->getVal( 'ed-f1', '"pages", 0, "added"', $res, '' );

		$expListsW2['count'] = 3;
		$expListsW2['description'] = 'y';
		$expPagesW = array( $pageW, $pageWA, $pageWAB, $pageTW, $pageTWA, $pageTWAB );

		$this->assertPages( 'ed-f2', $usr, null, $expPagesW );
		$this->assertPages( 'ed-f3', $usr, 0, $expPagesW );
		$this->assertPages( 'ed-f4', $usr, $id0, $expPagesW );
		$this->assertOneList( 'ed-f5', $usr, 0, $expListsW, $expListsW2 );
		$this->assertOneList( 'ed-f6', $usr, $id0, $expListsW, $expListsW2 );

		//
		// Create new list A
		$res = $this->editList( 'ed-i1', $usr, $token, '"label": "A"' );
		$this->getVal( 'ed-i1', '"status"', $res, 'created' );
		$idA = $this->getVal( 'ed-i1', '"id"', $res );
		$idAs = '"id":' . $idA;
		$expListsA = array( 'id' => $idA, 'label' => 'A' );
		$expListsA2 = array_merge( $expListsA, array(
			'public' => false,
			'description' => '',
			'image' => false,
			'count' => 0,
		) );
		$expPagesA = array();

		$this->assertPages( 'ed-i2', $usr, $idA, $expPagesA );
		$this->assertOneList( 'ed-i3', $usr, $idA, $expListsA, $expListsA2 );
		$this->assertOneList( 'ed-i4', $usrA, $idA, null );
		$this->assertOneList( 'ed-i5', $usr2, $idA, null );
		$this->assertOneList( 'ed-i6', $usrS, $idA, null );

		$this->badUsePage( 'ed-ia1', $usrA, '"lspid": ' . $idA );
		$this->badUsePage( 'ed-ia2', $usr2, '"lspid": ' . $idA );
		$this->badUsePage( 'ed-ia2a', $usrS, '"lspid": ' . $idA );
		$this->badUseEdit( 'ed-ia3', $usr, false, $idAs . ', "description": "x"' );
		$this->badUseEdit( 'ed-ia4', $usrA, false, $idAs . ', "description": "x"' );
		$this->badUseEdit( 'ed-ia5', $usr, $token, $idAs . ', "label": ""' );
		$this->badUseEdit( 'ed-ia6', $usrA, $tokenA, $idAs . ', "label": "x"' );
		$this->badUseEdit( 'ed-ia7', $usr, $tokenA, $idAs . ', "description": "x"' );
		$this->badUseEdit( 'ed-ia8', $usr2, $token2, $idAs . ', "description": "x"' );
		$this->badUseEdit( 'ed-ia9', $usrS, $tokenS, $idAs . ', "description": "x"' );

		//
		// Rename list A to 'a'
		$res = $this->editList( 'ed-j1', $usr, $token, $idAs . ', "label": "a"' );
		$this->getVal( 'ed-j1', '"status"', $res, 'updated' );
		$this->getVal( 'ed-j1', '"id"', $res, $idA );
		$expListsA['label'] = 'a';
		$expListsA2['label'] = 'a';

		$this->assertPages( 'ed-j2', $usr, $idA, $expPagesA );
		$this->assertOneList( 'ed-j3', $usr, $idA, $expListsA, $expListsA2 );
		$this->assertOneList( 'ed-j4', $usrA, $id0, null );
		$this->assertOneList( 'ed-j5', $usr2, $id0, null );
		$this->assertOneList( 'ed-j6', $usrS, $id0, null );

		$this->badUsePage( 'ed-ja1', $usrA, '"lspid": ' . $idA );
		$this->badUsePage( 'ed-ja2', $usr2, '"lspid": ' . $idA );
		$this->badUsePage( 'ed-ja2a', $usrS, '"lspid": ' . $idA );
		$this->badUseEdit( 'ed-ja3', $usr, false, $idAs . ', "description": "x"' );
		$this->badUseEdit( 'ed-ja4', $usrA, false, $idAs . ', "description": "x"' );
		$this->badUseEdit( 'ed-ja5', $usr, $token, $idAs . ', "label": ""' );
		$this->badUseEdit( 'ed-ja6', $usrA, $tokenA, $idAs . ', "label": "x"' );
		$this->badUseEdit( 'ed-ja7', $usr, $tokenA, $idAs . ', "description": "x"' );
		$this->badUseEdit( 'ed-ja8', $usr2, $token2, $idAs . ', "description": "x"' );
		$this->badUseEdit( 'ed-ja8a', $usrS, $tokenS, $idAs . ', "description": "x"' );
		$this->badUseEdit( 'ed-ja9', $usrA, $token, $idAs . ', "deletelist": 1' );
		$this->badUseEdit( 'ed-ja10', $usr2, $token, $idAs . ', "deletelist": 1' );
		$this->badUseEdit( 'ed-ja11', $usrS, $token, $idAs . ', "deletelist": 1' );

		//
		// Make list a public
		$res = $this->editList( 'ed-k1', $usr, $token, '"label": "a", "perm": "public"' );
		$this->getVal( 'ed-k1', '"status"', $res, 'updated' );
		$this->getVal( 'ed-k1', '"id"', $res, $idA );
		$expListsA2['public'] = true;

		$this->assertPages( 'ed-k2', $usr, $idA, $expPagesA );
		$this->assertPages( 'ed-k2a', $usr2, $idA, $expPagesA );
		$this->assertPages( 'ed-k2b', $usrA, $idA, $expPagesA );
		$this->assertPages( 'ed-k2c', $usrS, $idA, $expPagesA );
		$this->assertOneList( 'ed-k3', $usr, $idA, $expListsA, $expListsA2 );
		$this->assertOneList( 'ed-k4', $usrA, $idA, $expListsA, $expListsA2 );
		$this->assertOneList( 'ed-k5', $usr2, $idA, $expListsA, $expListsA2 );
		$this->assertOneList( 'ed-k6', $usrS, $idA, $expListsA, $expListsA2 );

		$this->badUseEdit( 'ed-ka1', $usr, false, $idAs . ', "description": "x"' );
		$this->badUseEdit( 'ed-ka2', $usrA, false, $idAs . ', "description": "x"' );
		$this->badUseEdit( 'ed-ka3', $usr, $token, $idAs . ', "label": ""' );
		$this->badUseEdit( 'ed-ka4', $usrA, $tokenA, $idAs . ', "label": "x"' );
		$this->badUseEdit( 'ed-ka5', $usr, $tokenA, $idAs . ', "description": "x"' );
		$this->badUseEdit( 'ed-ka6', $usr2, $token2, $idAs . ', "description": "x"' );
		$this->badUseEdit( 'ed-ka6', $usrS, $tokenS, $idAs . ', "description": "x"' );
		$this->badUseEdit( 'ed-ka7', $usrA, $tokenA, $idAs . ', "deletelist": 1' );
		$this->badUseEdit( 'ed-ka8', $usr2, $token2, $idAs . ', "deletelist": 1' );
		$this->badUseEdit( 'ed-ka9', $usr2, $token2, $idAs . ', "perm": "public"' );
		$this->badUseEdit( 'ed-ka10', $usr2, $token2, $idAs . ', "label": "xx"' );
		$this->badUseEdit( 'ed-ka11', $usrS, $tokenS, $idAs . ', "deletelist": 1' );
		$this->badUseEdit( 'ed-ka12', $usrS, $tokenS, $idAs . ', "perm": "public"' );
		$this->badUseEdit( 'ed-ka13', $usrS, $tokenS, $idAs . ', "label": "xx"' );

		//
		// Delete list A
		$res = $this->editList( 'ed-l1', $usr, $token, $idAs . ', "deletelist": 1' );
		$this->getVal( 'ed-l1', '"status"', $res, 'deleted' );
		$this->getVal( 'ed-l1', '"id"', $res, $idA );

		$this->badUsePage( 'ed-l2', $usr, '"lspid": ' . $idA );
		$this->badUsePage( 'ed-l3', $usr2, '"lspid": ' . $idA );
		$this->badUsePage( 'ed-l3a', $usrS, '"lspid": ' . $idA );
		$this->badUsePage( 'ed-l4', $usrA, '"lspid": ' . $idA );
		$this->assertOneList( 'ed-l5', $usr, $idA, null );
		$this->assertOneList( 'ed-l6', $usrA, $idA, null );
		$this->assertOneList( 'ed-l7', $usr2, $idA, null );
		$this->assertOneList( 'ed-l7a', $usrS, $idA, null );

		$this->badUseEdit( 'ed-l8', $usr, $token, $idAs . ', "titles": "ABC"' );
		$this->badUseEdit( 'ed-l9', $usr, $token, $idAs . ', "label": "x"' );
		$this->badUseEdit( 'ed-l10', $usr2, $token2, $idAs . ', "label": "xx"' );
		$this->badUseEdit( 'ed-l11', $usrS, $tokenS, $idAs . ', "label": "xx"' );

		//
		// Create public list B
		$res = $this->editList( 'ed-n1', $usr, $token,
			'"label": "B", "perm":"public", ' .
			'"titles":"Gather-ListB|Gather-ListAB|Gather-ListWAB"' );
		$this->getVal( 'ed-n1', '"status"', $res, 'created' );
		$idB = $this->getVal( 'ed-n1', '"id"', $res );
		$idBs = '"id":' . $idB;
		$expListsB = array( 'id' => $idB, 'label' => 'B' );
		$expListsB2 = array_merge( $expListsB, array(
			'public' => true,
			'description' => '',
			'image' => false,
			'count' => 3,
		) );
		// Non-alphabetic order should be preserved
		$expPagesB = array( $pageB, $pageAB, $pageWAB );

		$this->assertPages( 'ed-n2', $usr, $idB, $expPagesB );
		$this->assertPages( 'ed-n3', $usr2, $idB, $expPagesB );
		$this->assertPages( 'ed-n3a', $usrS, $idB, $expPagesB );
		$this->assertPages( 'ed-n4', $usrA, $idB, $expPagesB );
		$this->assertOneList( 'ed-n5', $usr, $idB, $expListsB, $expListsB2 );
		$this->assertOneList( 'ed-n6', $usrA, $idB, $expListsB, $expListsB2 );
		$this->assertOneList( 'ed-n7', $usr2, $idB, $expListsB, $expListsB2 );
		$this->assertOneList( 'ed-n8', $usrS, $idB, $expListsB, $expListsB2 );
	}

	public function testMultipleLists() {
		$this->intTestMultipleLists( false );
	}

	public function testMultipleListsWithWatchlist() {
		$this->intTestMultipleLists( true );
	}

	private function intTestMultipleLists( $createWatchlist ) {
		$p = $createWatchlist ? 'Test With watchlist: #' : 'Test without watchlist: #';
		$n = $createWatchlist ? 'GatherWML' : 'GatherML';
		$n2 = $createWatchlist ? 'GatherWML2' : 'GatherML2';

		$a = User::newFromId( 0 ); // Anonymous user
		$u = self::$users[$n]->getUser(); // User for this test
		$u2 = self::$users[$n2]->getUser(); // Second user for this test

		$token = $this->getToken( $u );
		$n = '"' . $n . '"';

		// Anonymous tests
		$this->badUseLists( "$p 0", $a, '{}' );
		$this->badUseLists( "$p 0", $a, '"lstids": 0' );

		if ( $createWatchlist ) {
			// Create watchlist row
			$res = $this->editList( "$p a0", $u, $token, '"id":0,"description":"x"' );
			$wlId = $this->getVal( "$p a0", '"id"', $res );
		} else {
			$wlId = 0;
		}

		// Add pages to various lists
		$res = $this->editList( "$p a1", $u, $token,
			'"id":0,"titles":"Gather-ListW|Gather-ListWA|Gather-ListWAB"' );
		$this->getVal( "$p a1", '"status"', $res, 'nochanges' );
		$this->getVal( "$p a1", '"pages",0,"added"', $res, '' );
		$this->getVal( "$p a1", '"pages",1,"added"', $res, '' );
		$this->getVal( "$p a1", '"pages",2,"added"', $res, '' );
		$this->getVal( "$p a1", '"id"', $res, $wlId );

		$res = $this->editList( "$p a2", $u, $token,
			'"label":"A","titles":"Gather-ListWA|Gather-ListWAB"' );
		$this->getVal( "$p a2", '"status"', $res, 'created' );
		$this->getVal( "$p a2", '"pages",0,"added"', $res, '' );
		$this->getVal( "$p a2", '"pages",1,"added"', $res, '' );
		$idA = $this->getVal( "$p a2", '"id"', $res );

		$res = $this->editList( "$p a3", $u, $token,
			'"label":"B", "perm":"public", "titles":"Gather-ListWAB"' );
		$this->getVal( "$p a3", '"status"', $res, 'created' );
		$this->getVal( "$p a3", '"pages",0,"added"', $res, '' );
		$idB = $this->getVal( "$p a3", '"id"', $res );

		$res = $this->getLists( "$p b1", $u, '{}' );
		$this->assertListNoId( "$p b1", $res, $wlId
			? '[{"watchlist":true,"label":"Watchlist"}, {"label":"A"}, {"label":"B"}]'
			: '[{"id":0, "watchlist":true,"label":"Watchlist"}, {"label":"A"}, {"label":"B"}]' );
		$this->getVal( "$p b1", '"query","lists",1,"id"', $res, $idA );
		$this->getVal( "$p b1", '"query","lists",2,"id"', $res, $idB );

		//
		// Continuation
		$request = $this->toApiParams( "$p c1", 'lists', false, '"lstlimit": 1' );

		$res = $this->getLists( "$p c1", $u, $request );
		$this->assertListsEquals( "$p c1a", $res,
			'[{"id":' . $wlId . ', "watchlist":true,"label":"Watchlist"}]' );
		$continue = $this->getVal( "$p c1b", '"continue"', $res );

		$res = $this->getLists( "$p c2", $u, array_merge( $continue, $request ) );
		$this->assertListNoId( "$p c2a", $res, '[{"label":"A"}]' );
		$continue = $this->getVal( "$p c2b", '"continue"', $res );

		$res = $this->getLists( "$p c3", $u, array_merge( $continue, $request ) );
		$this->assertListNoId( "$p c3a", $res, '[{"label":"B"}]' );
		$this->assertArrayNotHasKey( 'continue', $res, "$p c3c" );

		//
		// ids=A
		$res = $this->getLists( "$p d1", $u, '"lstids":' . $idA );
		$this->assertListNoId( "$p d1", $res, '[{"label":"A"}]' );

		// ids=A as anon user
		$res = $this->getLists( "$p d2", $a, '"lstids":' . $idA );
		$this->assertListNoId( "$p d2", $res, '[]' );

		// ids=A as another user
		$res = $this->getLists( "$p d3", $u2, '"lstids":' . $idA );
		$this->assertListNoId( "$p d3", $res, '[]' );

		//
		// ids=B
		$res = $this->getLists( "$p e1", $u, '"lstids":' . $idB );
		$this->assertListNoId( "$p e1", $res, '[{"label":"B"}]' );

		// ids=B as anon user
		$res = $this->getLists( "$p e2", $a, '"lstids":' . $idB );
		$this->assertListNoId( "$p e2", $res, '[{"label":"B"}]' );

		// ids=B as another user
		$res = $this->getLists( "$p e3", $u2, '"lstids":' . $idB );
		$this->assertListNoId( "$p e3", $res, '[{"label":"B"}]' );

		//
		// Use owner param
		// user: get all with owner=user
		$res = $this->getLists( "$p i0", $u, '"lstowner":' . $n );
		$this->assertListsEquals( "$p i0", $res,
			'[{"id":' . $wlId . ', "watchlist":true,"label":"Watchlist"}, ' .
			'{"id":' . $idA . ', "label":"A"},' . '{"id":' . $idB . ', "label":"B"}]' );

		// user: get by idA with owner=user
		$res = $this->getLists( "$p i0a", $u,
			'"lstowner": ' . $n . ', "lstids": ' . $idA );
		$this->assertListsEquals( "$p i0a", $res, '[{"id":' . $idA . ', "label":"A"}]' );

		// anon: get all with owner=user
		$res = $this->getLists( "$p i1", $a, '"lstowner":' . $n );
		$this->assertListNoId( "$p i1", $res, '[{"label":"B"}]' );

		// anon: get by idA with owner=user
		$res = $this->getLists( "$p i2", $a, '"lstowner": ' . $n . ', "lstids": ' . $idA );
		$this->assertListNoId( "$p i2", $res, '[]' );

		// anon: get by idB with owner=user
		$res = $this->getLists( "$p i3", $a, '"lstowner": ' . $n . ', "lstids": ' . $idB );
		$this->assertListNoId( "$p i3", $res, '[{"label":"B"}]' );

		// user2: get all with owner=user
		$res = $this->getLists( "$p i4", $u2, '"lstowner":' . $n );
		$this->assertListNoId( "$p i4", $res, '[{"label":"B"}]' );

		// user2: get by idA with owner=user
		$res = $this->getLists( "$p i5", $u2, '"lstowner": ' . $n . ', "lstids": ' . $idA );
		$this->assertListNoId( "$p i5", $res, '[]' );

		// user2: get by idB with owner=user
		$res = $this->getLists( "$p i5", $u2, '"lstowner": ' . $n . ', "lstids": ' . $idB );
		$this->assertListNoId( "$p i5", $res, '[{"label":"B"}]' );
	}

	public function testWatchlistOnly() {
		$u = self::$users['GatherWlOnly']->getUser(); // User for this test
		$a = User::newFromId( 0 ); // Anonymous user
		$u2 = self::$users['GatherWlOnly2']->getUser(); // Second user for this test

		$token = $this->getToken( $u );
		$wlOnly = '[{"id":0, "watchlist":true, "label":"Watchlist"}]';
		$n = '"' . $u->getName() . '"'; // Name of the user for this test

		//
		// Validate empty watchlist / lists
		$res = $this->getLists( 'nc-a0', $u, '{}' );
		$this->assertListNoId( 'nc-a0', $res, $wlOnly );

		$res = $this->getLists( 'nc-a1', $u, '"lstids": 0' );
		$this->assertListNoId( 'nc-a1', $res, $wlOnly );

		$res = $this->getLists( 'nc-a2', $u, '"lstlimit": 1' );
		$this->assertListNoId( 'nc-a2', $res, $wlOnly );

		$res = $this->getLists( 'nc-a3', $u, '"lstprop": "label|description|public|count"' );
		$this->assertListNoId( 'nc-a3', $res,
			'[{"id":0,"watchlist":true,"count":0,"label":"Watchlist","description":"","public":false}]'
		);
		$res = $this->getLists( 'nc-a4', $u, '"lsttitle": "Missing"' );
		$this->assertListNoId( 'nc-a4', $res,
			'[{"id":0,"watchlist":true,"label":"Watchlist","title":false}]' );

		//
		// Add page to watchlist
		$this->legacyAddToWatchlist( 'nc-b0', $u, $token, 'Gather-ListW' );
		$res = $this->getLists( 'nc-b0', $u, '"lstprop": "count"' );
		$this->assertListNoId( 'nc-b0', $res, '[{"id": 0, "watchlist":true, "count": 1}]' );

		$res = $this->getLists( 'nc-b1', $u, '"lsttitle": "Gather-ListW"' );
		$this->assertListNoId( 'nc-b1', $res,
			'[{"id":0,"watchlist":true,"label":"Watchlist","title":true}]' );

		//
		// Re-add the same page, using action=editlist & id=0
		$res = $this->editList( 'nc-c0', $u, $token, '"id":0,"titles":"Gather-ListW"' );
		$this->getVal( "nc-c0", '"status"', $res, 'nochanges' );
		$this->getVal( "nc-c0", '"id"', $res, 0 );
		$this->getVal( "nc-c0", '"pages",0,"added"', $res, '' );

		$res = $this->getLists( 'nc-c0a', $u, '{}' );
		$this->assertListNoId( 'nc-c0a', $res, $wlOnly );

		$res = $this->getLists( 'nc-c1', $u, '"lstids": 0' );
		$this->assertListNoId( 'nc-c1', $res, $wlOnly );

		$res = $this->getLists( 'nc-c3', $u, '"lstprop": "count"' );
		$this->assertListNoId( 'nc-c3', $res, '[{"id":0, "watchlist":true, "count": 1}]' );

		$res = $this->getLists( 'nc-c4', $u, '"lsttitle": "Gather-ListW"' );
		$this->assertListNoId( 'nc-c4', $res,
			'[{"id":0,"watchlist":true,"label":"Watchlist","title":true}]' );

		$res = $this->getLists( 'nc-c5', $u, '"lstids": 0, "lsttitle": "Gather-ListW"' );
		$this->assertListNoId( 'nc-c5', $res,
			'[{"id":0,"watchlist":true,"label":"Watchlist","title":true}]' );

		//
		// What can others see from this user
		$res = $this->getLists( 'nc-e0', $a, '"lstowner":' . $n );
		$this->assertListNoId( 'nc-e0', $res, '[]' );

		$res = $this->getLists( 'nc-e1', $a, '"lstowner": ' . $n . ', "lstids": 0' );
		$this->assertListNoId( 'nc-e1', $res, '[]' );

		$res = $this->getLists( 'nc-e2', $u2, '"lstowner":' . $n );
		$this->assertListNoId( 'nc-e2', $res, '[]' );

		$res =  $this->getLists( 'nc-e3', $u2, '"lstowner": ' . $n . ', "lstids": 0' );
		$this->assertListNoId( 'nc-e3', $res, '[]' );

		//
		// Create watchlist list DB record
		$res = $this->editList( 'nc-f0', $u, $token, '"id":0, "description":"aa"' );
		$this->getVal( 'nc-f0', '"status"', $res, 'created' );
		$id = $this->getVal( 'nc-f0', '"id"', $res );
		$this->assertNotEquals( 0, $id );

		$wlOnly = array( array( 'id' => $id, 'watchlist' => true, 'label' => 'Watchlist' ) );

		$res = $this->getLists( 'nc-f2', $u, '{}' );
		$this->assertListsEquals( 'nc-f2', $res, $wlOnly );

		$res = $this->getLists( 'nc-f3', $u, '"lstids": 0' );
		$this->assertListsEquals( 'nc-f3', $res, $wlOnly );

		$res = $this->getLists( 'nc-f4', $u, '"lstprop": "label|description|public|count"' );
		$this->assertListsEquals( 'nc-f4', $res,
			'[{"id":' . $id .
			',"watchlist":true,"count":1,"label":"Watchlist","description":"aa","public":false}]' );

		$res = $this->getLists( 'nc-f5', $u, '"lsttitle": "Gather-ListW"' );
		$this->assertListsEquals( 'nc-f5', $res, '[{"id":' . $id .
			',"watchlist":true,"label":"Watchlist","title":true}]' );

		//
		// Others still can't see the watchlist
		$res = $this->getLists( 'nc-g0', $a, '"lstowner":' . $n );
		$this->assertListNoId( 'nc-g0', $res, '[]' );

		$res = $this->getLists( 'nc-g1', $a, '"lstowner": ' . $n . ', "lstids": 0' );
		$this->assertListNoId( 'nc-g1', $res, '[]' );

		$res = $this->getLists( 'nc-g2', $a, '"lstids": ' . $id );
		$this->assertListNoId( 'nc-g2', $res, '[]' );

		$res = $this->getLists( 'nc-g3', $a, '"lstowner": ' . $n . ', "lstids": ' . $id );
		$this->assertListNoId( 'nc-g3', $res, '[]' );

		$res = $this->getLists( 'nc-h0', $u2, '"lstowner":' . $n );
		$this->assertListNoId( 'nc-h0', $res, '[]' );

		$res = $this->getLists( 'nc-h1', $u2, '"lstowner": ' . $n . ', "lstids": 0' );
		$this->assertListNoId( 'nc-h1', $res, '[]' );

		$res = $this->getLists( 'nc-h2', $u2, '"lstids": ' . $id );
		$this->assertListNoId( 'nc-h2', $res, '[]' );

		$res = $this->getLists( 'nc-h3', $u2, '"lstowner": ' . $n . ', "lstids": ' . $id );
		$this->assertListNoId( 'nc-h3', $res, '[]' );

		//
		// Watchlist editing assertions
		$this->badUseEdit( 'nc-i0', $u, false, '"id":0, "label":"bb"' );
		$this->badUseEdit( 'nc-i1', $u, false, '"id":' . $id . ', "label":"bb"' );
	}

	private function assertListNoId( $message, $actual, $exp ) {
		$this->assertListsEquals( $message, $actual, $exp, true );
	}

	private function assertListsEquals( $message, $actual, $exp, $removeIds = false ) {
		$actual = $this->getVal( $message, '"query", "lists"', $actual );
		$exp = $this->toArr( $message, $exp );
		if ( $removeIds ) {
			$actual = self::removeIds( $actual );
		}
		$this->assertArrayEquals( $exp, $actual, true, true, $message );
	}

	private function legacyAddToWatchlist( $message, $user, $token, $titles ) {
		$params = array(
			'action' => 'watch',
			'titles' => $titles,
			'token' => $token,
		);
		$res = $this->getLists( $message, $user, $params );
		$this->getVal( $message, '"watch", 0, "watched"', $res );
	}

	private function getToken( User $user ) {
		$message = 'token-' . $user->getName();
		$res = $this->doApiRequest2( $message, $user, array(
			'action' => 'query',
			'meta' => 'tokens',
			'type' => 'watch',
		) );
		return $this->getVal( $message, '0, "query", "tokens", "watchtoken"', $res );
	}

	private function badUseLists( $message, User $user, $params ) {
		$this->badUse( $message, $user, 'lists', false, $params );
	}

	private function badUsePage( $message, User $user, $params ) {
		$this->badUse( $message, $user, 'listpages', false, $params );
	}

	private function badUseEdit( $message, User $user, $token, $params ) {
		$this->badUse( $message, $user, 'editlist', $token, $params );
	}

	private function badUse( $message, User $user, $action, $token, $params ) {
		try {
			$params = $this->toApiParams( $message, $action, $token, $params );
			$result = $this->doApiRequest( $params, null, false, $user );
			$params = $this->toStr( $params );
			$this->fail( "$message: No UsageException for $params, received:\n" .
				$this->toStr( $result[0], true ) );
		} catch ( UsageException $e ) {
			$this->assertTrue( true );
		}
	}

	private function editList( $message, $user, $token, $params ) {
		$params = $this->toApiParams( $message, 'editlist', $token, $params );
		$res = $this->doApiRequest2( $message, $user, $params );
		return $this->getVal( $message, array( $params['action'] ), $res[0] );
	}

	private function getLists( $message, User $user, $params ) {
		$params = $this->toApiParams( $message, 'lists', false, $params );
		$res = $this->doApiRequest2( $message, $user, $params );
		return $res[0];
	}

	private function getPages( $message, User $user, $params ) {
		$params = $this->toApiParams( $message, 'listpages', false, $params );
		$res = $this->doApiRequest2( $message, $user, $params );
		return $res[0];
	}

	private function doApiRequest2( $message, User $user, array $params ) {
		try {
			return parent::doApiRequest( $params, null, false, $user );
		} catch ( Exception $ex ) {
			echo "Failed API call $message\n";
			throw $ex;
		}
	}


	private function toApiParams( $message, $default, $token, $params ) {
		$params = $this->toArr( $message, $params, true );
		if ( !isset( $params['action'] ) ) {
			$params['action'] = $default === 'editlist' ? $default : 'query';
		}
		if ( $params['action'] === 'query' ) {
			if ( !isset( $params['list'] ) ) {
				$params['list'] = $default;
			}
			if ( !isset( $params['continue'] ) ) {
				$params['continue'] = '';
			}
		}
		if ( $token && !isset( $params['token'] ) ) {
			$params['token'] = $token;
		}
		return $params;
	}

	private function toArr( $message, $params, $dictByDefault = false ) {
		if ( is_string( $params ) && $params ) {
			$p = $params;
			if ( $p[0] !== '[' && $p[0] !== '{' ) {
				$p = $dictByDefault ? '{' . $params . '}' : "[$params]";
			}
			$st = FormatJson::parse( $p, FormatJson::FORCE_ASSOC );
			$this->assertTrue( $st->isOK(), "$message: invalid JSON value $params" );
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

	private function getVal( $message, $path, $result, $expValue = null ) {
		$path = $this->toArr( $message, $path );
		$res = $result;
		foreach ( $path as $p ) {
			if ( !array_key_exists( $p, $res ) ) {
				$path = $this->toStr( $path );
				$this->fail( "$message: Request has no key $p of $path in result\n" .
					$this->toStr( $result, true ) );
			}
			$res = $res[$p];
		}
		if ( $expValue !== null ) {
			$this->assertEquals( $expValue, $res, $message );
		}
		return $res;
	}

	/**
	 * Debugging function to track the sate of the table during test development
	 * @param string $table
	 */
	private function dumpTable( $table ) {
		echo "\nTable dump $table:\n";
		foreach ( $this->db->select( $table, '*' ) as $row ) {
			echo $this->toStr( $row ) . "\n";
		}
		echo "\nEnd of the table dump $table\n";
	}

	private function assertOneList( $message, $u, $id, $expected, $expectedProp = null ) {
		$params = '"lstids":' . $id;
		$res = $this->getLists( $message, $u, $params );
		$lst = $this->getVal( $message, '"query", "lists"', $res );
		if ( $expected === null ) {
			$this->assertCount( 0, $lst, $message );
		} else {
			$this->assertCount( 1, $lst, $message );
			$this->assertEquals( $expected, $lst[0], $message );
		}

		if ( $expectedProp ) {
			$params .= ', "lstprop":"label|description|public|image|count"';
			$message .= '-p';
			$res = $this->getLists( $message, $u, $params );
			$lst = $this->getVal( $message, '"query", "lists"', $res );
			$this->assertCount( 1, $lst, $message );
			$this->assertEquals( $expectedProp, $lst[0], $message );
		}
	}

	private function assertPages( $message, $u, $id, $expected ) {
		$params = $id === null ? '{}' : '"lspid":' . $id;
		$res = $this->getPages( $message, $u, $params );
		$this->getVal( $message, '"listpages"', $res, $expected );
	}
}
