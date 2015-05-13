<?php

require_once ( __DIR__ . '/GatherTestCase.php' );

/**
 * @group API
 * @group Database
 * @group medium
 */
class ApiQueryListsTest extends GatherTestCase {
	protected $tablesUsed = array( 'gather_list', 'gather_list_item' );

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
