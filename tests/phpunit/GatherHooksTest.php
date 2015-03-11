<?php

/**
 * @group Gather
 */
class GatherHooksTest extends MediaWikiTestCase {
	public function provideGetUserPermissionsErrors() {
		$manifest = Gather\stores\UserPageCollectionsList::MANIFEST_FILE;
		return array(
			// Edit
			array( true, "User:Jdlrobson/$manifest", 'Jdlrobson', 'edit' ),
			array( false, "User:Jdlrobson/$manifest", 'phudex', 'edit' ),
			// View
			array( true, "User:Jdlrobson/$manifest", 'Jdlrobson', 'view' ),
			array( true, "User:Jdlrobson/$manifest", 'phudex', 'view' ),
			// Move
			array( true, "User:Jdlrobson/$manifest", 'Jdlrobson', 'move' ),
			array( false, "User:Jdlrobson/$manifest", 'phuedx', 'move' ),
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
