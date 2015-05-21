<?php

require_once ( __DIR__ . '/GatherTestCase.php' );

/**
 * @group API
 * @group Database
 * @group medium
 */
class ApiQueryListsTest extends GatherTestCase {
	protected $tablesUsed = array( 'gather_list', 'gather_list_item' );

	public function setUp() {
		parent::setUp();
		$this->setMwGlobals( 'wgGatherAutohideFlagLimit', 3 );
	}

	public function testMinitems() {
		$list1 = $this->createList( 'gatherUser', array( 'P1', 'P2' ) );
		$list2 = $this->createList( 'gatherUser', array( 'P1', 'P2', 'P3' ) );
		$list3 = $this->createList( 'gatherUser', array( 'P1', 'P2', 'P3', 'P4', 'P5' ) );

		$minitems3 = $this->getListIdsFromResults( $this->doApiRequest( array(
			'action' => 'query',
			'list' => 'lists',
			'lstminitems' => 3,
		) ) );
		unset( $minitems3[0] ); // we don't care about the watchlist
		$this->assertArrayEquals( array( $list2, $list3 ), $minitems3 );
	}

	public function testVisibility() {
		$this->addTestUsers( 'flagger1', 'flagger2', 'flagger3' );

		// normal list
		$list1 = $this->createList( 'gatherUser', array( 'P1' ) );

		// hidden list
		$list2 = $this->createList( 'gatherUser', array( 'P1' ) );
		$this->setListPermissionOverride( $list2, 'hidelist' );

		// normal list with 3 flags
		$list3 = $this->createList( 'gatherUser', array( 'P1' ) );
		$this->flagList( 'flagger1', $list3 );
		$this->flagList( 'flagger2', $list3 );
		$this->flagList( 'flagger3', $list3 );

		// approved list with 3 flags
		$list4 = $this->createList( 'gatherUser', array( 'P1' ) );
		$this->setListPermissionOverride( $list4, 'approve' );
		$this->flagList( 'flagger1', $list4 );
		$this->flagList( 'flagger2', $list4 );
		$this->flagList( 'flagger3', $list4 );

		// normal list with 3 flags which have been reviewed
		$list5 = $this->createList( 'gatherUser', array( 'P1' ) );
		$this->flagList( 'flagger1', $list5 );
		$this->flagList( 'flagger2', $list5 );
		$this->flagList( 'flagger3', $list5 );
		$this->setListPermissionOverride( $list5, 'showlist' );

		// hidden list which needs review
		$list6 = $this->createList( 'gatherUser', array( 'P1' ) );
		$this->setListPermissionOverride( $list6, 'hidelist' );
		$this->doApiRequestWithWatchToken( array(
			'action' => 'editlist',
			'id' => $list6,
			'label' => 'new test label',
		), null, false, static::$users['gatherUser']->getUser() );

		$normalList = $this->getListIdsFromResults( $this->doApiRequest( array(
			'action' => 'query',
			'list' => 'lists',
			'lstmode' => 'allpublic',
		) ) );
		$this->assertArrayEquals( array( $list1, $list4, $list5 ), $normalList );

		$hiddenlList = $this->getListIdsFromResults( $this->doApiRequest( array(
			'action' => 'query',
			'list' => 'lists',
			'lstmode' => 'allhidden',
		), null, false, static::$users['sysop']->getUser() ) );
		$this->assertArrayEquals( array( $list2, $list3, $list6 ), $hiddenlList );

		$reviewList = $this->getListIdsFromResults( $this->doApiRequest( array(
			'action' => 'query',
			'list' => 'lists',
			'lstmode' => 'review',
		), null, false, static::$users['sysop']->getUser() ) );
		$this->assertArrayEquals( array( $list3, $list4, $list6 ), $reviewList );
	}

	/**
	 * Returns Gather list IDs from a lists API query result
	 * @param array $ret Return value of doApiRequest()
	 * @return array
	 */
	protected function getListIdsFromResults( $ret ) {
		$results = $this->getFromResults( $ret, 'lists' );
		return array_map( function ( $list ) {
			return $list['id'];
		}, $results );
	}
}
