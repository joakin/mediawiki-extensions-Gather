<?php

/**
 * @group Gather
 */
class SpecialGatherTest extends MediaWikiTestCase {
	public function provideRoutes() {
		return array(
			array(
				'id/501',
				array( 'id/501', '501', '', 'id' => '501' ),
			),
			array(
				'id/501/Title',
				array( 'id/501/Title', '501', '/Title', 'id' => '501' ),
			),
			array(
				'id/501Title',
				false,
			),
			array(
				'id/',
				false
			),
			array(
				'foo',
				false
			),
		);
	}

	/**
	 * @dataProvider provideRoutes
	 *
	 */
	public function testCheckRoute( $subpage, $expected ) {
		$sp = new Gather\SpecialGather();
		$this->assertEquals( $expected, $sp->checkRoute( $subpage ) );
	}
}
