<?php

namespace Gather;

use DatabaseUpdater;

/**
 * Class containing updater functions for a Gather environment
 */
class UpdaterHooks {
	public static function onLoadExtensionSchemaUpdates( DatabaseUpdater $du ) {
		$base = dirname( __FILE__ );

		$du->addExtensionTable( 'gather_list', "$base/gather_list.sql", true );
		$du->addExtensionTable( 'gather_list_item', "$base/gather_list_item.sql", true );

		return true;
	}
}
