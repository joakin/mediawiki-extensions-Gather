<?php

/**
 * @group Gather
 */
class CollectionTest extends MediaWikiTestCase {
	public function provideHasMember() {
		return array(
			array(
				true,
				array( 'foo', 'bar', 'baz' ),
				'foo',
			),
			array(
				true,
				array( 'foo', 'bar', 'baz' ),
				'baz',
			),
			array(
				false,
				array( 'foo', 'bar', 'baz' ),
				'baz',
			),
		);
	}

	/**
	 * @dataProvider provideHasMember
	 *
	 */
	public function testHasMember( $expected, $items, $member ) {
		$collection = new models\Collection();
		foreach ( $items as $item ) {
			$collection->add( new models\CollectionItem( Title::newFromText( $title ), false, 'Test' ) );
		}

		$this->assertEquals( $expected, $collection->hasMember( Title::newFromText( $member ) ) );
	}
}
