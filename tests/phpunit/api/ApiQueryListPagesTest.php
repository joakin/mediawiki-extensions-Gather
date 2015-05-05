<?php

require_once ( __DIR__ . '/GatherTestCase.php' );

/**
 * @group API
 * @group Database
 * @group medium
 */
class ApiQueryListPagesTest extends GatherTestCase {
	public function testSort() {
		$listId = $this->createList( 'gatherUser', array( 'P1', 'P2', 'Talk:P3', 'Help:P4', 'P5' ) );

		// FIXME pages are not created in order
		foreach ( array( 'P1', 'P2', 'P3', 'P4', 'P5' ) as $pos => $page ) {
			$this->db->update( 'gather_list_item', array( 'gli_order' => 1 . $pos ),
				array( 'gli_gl_id' => $listId, 'gli_title' => $page ), __METHOD__ );
		}

		$pages = $this->getPagenamesFromResults( $this->doApiRequest( array(
			'action' => 'query',
			'list' => 'listpages',
			'lspid' => $listId,
			'lspsort' => 'position',
		) ) );
		$this->assertArrayEquals( array( 'P1', 'P2', 'Talk:P3', 'Help:P4', 'P5' ), $pages, true );

		$pages = $this->getPagenamesFromResults( $this->doApiRequest( array(
			'action' => 'query',
			'list' => 'listpages',
			'lspid' => $listId,
			'lspsort' => 'namespace',
		) ) );
		$this->assertArrayEquals( array( 'P1', 'P2', 'P5', 'Talk:P3', 'Help:P4' ), $pages, true );
	}

	/**
	 * Returns page names (without namespace) from a listpages API query result
	 * @param array $ret Return value of doApiRequest()
	 * @return array
	 */
	protected function getPagenamesFromResults( $ret ) {
		$results = $this->getFromResults( $ret, 'listpages' );
		return array_map( function ( $item ) {
			return $item['title'];
		}, $results );
	}
}
