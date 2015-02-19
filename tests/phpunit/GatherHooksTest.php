<?php

/**
 * @group Gather
 */
class GatherHooksTest extends MediaWikiTestCase {
	public function provideGetUserPermissionsErrors() {
		return array(
			// Edit
			array( true, 'User:Jdlrobson/GatherCollections.json', 'Jdlrobson', 'edit' ),
			array( false, 'User:Jdlrobson/GatherCollections.json', 'phudex', 'edit' ),
			// View
			array( true, 'User:Jdlrobson/GatherCollections.json', 'Jdlrobson', 'view' ),
			array( true, 'User:Jdlrobson/GatherCollections.json', 'phudex', 'view' ),
			// Move
			array( true, 'User:Jdlrobson/GatherCollections.json', 'Jdlrobson', 'move' ),
			array( false, 'User:Jdlrobson/GatherCollections.json', 'phuedx', 'move' ),
			// Normal page editing is not disrupted
			array( true, 'User:JDLR', 'Jdlrobson', 'edit' ),
			array( true, 'User:JDLR/Foo', 'Jdlrobson', 'edit' ),
		);
	}

	/**
	 * @dataProvider provideGetUserPermissionsErrors
	 *
	 */
	public function testOnGetUserPermissionsErrors( $expected, $title, $user, $action ) {
		$canEdit = Gather\Hooks::onGetUserPermissionsErrors( Title::newFromText( $title ),
			User::newFromName( $user ), $action, '' );
		$this->assertEquals( $expected, $canEdit );
	}
}
