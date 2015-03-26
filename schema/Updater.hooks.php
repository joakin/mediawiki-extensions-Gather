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
}
