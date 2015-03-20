<?php

namespace Gather;

use DatabaseUpdater;

/**
 * Class containing updater functions for a Gather environment
 */
class UpdaterHooks {
	public static function onLoadExtensionSchemaUpdates( DatabaseUpdater $du ) {
		$dir = __DIR__;
		$du->addExtensionTable( 'gather_list', "$dir/gather_list.sql", true );
		$du->addExtensionTable( 'gather_list_item', "$dir/gather_list_item.sql", true );
		$du->addExtensionField( 'gather_list', 'gl_perm', "$dir/gather_list-perm-ts.sql" );

		require_once "$dir/GatherListPermissions.php";
		$du->addPostDatabaseUpdateMaintenance( 'Gather\GatherListPermissions' );

		if ( $du->getDB()->getType() === 'sqlite' ) {
			$du->modifyExtensionField( 'gather_list', 'gl_perm', "$dir/gather_list-perm-ts2.sqlite.sql" );
		} else {
			$du->modifyExtensionField( 'gather_list', 'gl_perm', "$dir/gather_list-perm-ts2.sql" );
		}

		return true;
	}
}
