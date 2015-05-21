<?php

require_once ( __DIR__ . '/GatherTestCase.php' );

/**
 * @group API
 * @group Database
 * @group medium
 */
class ApiEditListTest extends GatherTestCase {
	protected $tablesUsed = array( 'gather_list', 'gather_list_item' );

	public function testItemCount() {
		$listId = $this->createList( 'gatherUser', array( 'P1', 'P2', 'P3' ) );
		$this->assertItemCount( 3, $listId );

		$this->doApiRequestWithWatchToken( array(
			'action' => 'editlist',
			'id' => $listId,
			'titles' => 'P4|P5|P6|P7',
		) );
		$this->assertItemCount( 7, $listId );

		$this->doApiRequestWithWatchToken( array(
			'action' => 'editlist',
			'id' => $listId,
			'titles' => 'P2|P4',
			'mode' => 'remove',
		) );
		$this->assertItemCount( 5, $listId );
	}

	public function testPermissionOverrideChangeOnEdit() {
		$listId = $this->createList( 'gatherUser', array( 'P1' ) );
		$this->setListPermissionOverride( $listId, 'approve' );
		$this->doApiRequestWithWatchToken( array(
			'action' => 'editlist',
			'id' => $listId,
			'label' => 'new test label',
		), null, false, static::$users['gatherUser']->getUser() );

		$listData = $this->getListData( $listId );
		$this->assertArrayNotHasKey( 'perm_override', $listData );
	}

	public function testOneFlagPerUser() {
		$this->addTestUsers( 'flagger' );
		$user = static::$users['flagger']->getUser();
		$listId = $this->createList( 'gatherUser', array( 'P1' ) );
		$params = array(
			'action' => 'editlist',
			'mode' => 'flag',
			'id' => $listId,
		);

		$this->doApiRequestWithWatchToken( $params, null, false, $user );
		$this->assertRequestFails( $params, $user, true );
	}

	/**
	 * Verifies (via direct DB access) gather_list.gl_item_count
	 * @param int $expected
	 * @param int $listId
	 */
	protected function assertItemCount( $expected, $listId ) {
		$count = $this->db->selectField( 'gather_list', 'gl_item_count',
			array( 'gl_id' => $listId ), __METHOD__ );
		$this->assertEquals( $expected, $count );
	}
}
