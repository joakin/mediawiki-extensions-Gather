<?php

namespace Gather;

use DatabaseUpdater;

/**
 * Class containing updater functions for a Gather environment
 */
class UpdaterHooks {
	public static function onLoadExtensionSchemaUpdates( DatabaseUpdater $du ) {
		$dir = __DIR__;

		// TODO BUG !!!
		// Remove this before going to production - good enough migration for BETA & DEV
		if ( $du->getDB()->indexExists( 'gather_list_item', 'gli_id_order_ns_title', __METHOD__ ) ) {
			$du->dropExtensionTable( 'gather_list_item', false );
			$du->dropExtensionTable( 'gather_list', false );
		}

		$du->addExtensionTable( 'gather_list', "$dir/gather_list.sql" );
		$du->addExtensionTable( 'gather_list_item', "$dir/gather_list_item.sql" );

		return true;
	}
}
