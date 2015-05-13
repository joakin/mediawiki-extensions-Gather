<?php

namespace Gather;

use DatabaseUpdater;

/**
 * Class containing updater functions for a Gather environment
 */
class UpdaterHooks {
	public static function onLoadExtensionSchemaUpdates( DatabaseUpdater $du ) {
		$dir = __DIR__;

		$du->addExtensionTable( 'gather_list', "$dir/gather_list.sql" );
		$du->addExtensionTable( 'gather_list_item', "$dir/gather_list_item.sql" );

		return true;
	}

	/**
	 * Migrates older schemas to the current version.
	 * @param DatabaseUpdater $du
	 * @return bool
	 */
	public static function onLoadExtensionSchemaUpdatesBC( DatabaseUpdater $du ) {
		$du->addExtensionField( 'gather_list', 'gl_item_count',
			__DIR__ . '/archive/add-gl_item_count.sql' );

		return true;
	}
}
