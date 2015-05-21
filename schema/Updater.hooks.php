<?php

namespace Gather;

use DatabaseUpdater;

/**
 * Class containing updater functions for a Gather environment
 */
class UpdaterHooks {
	/**
	 * Sets up tables needed by Gather.
	 * @param DatabaseUpdater $du
	 * @return bool
	 */
	public static function onLoadExtensionSchemaUpdates( DatabaseUpdater $du ) {
		$dir = __DIR__;

		$du->addExtensionTable( 'gather_list', "$dir/gather_list.sql" );
		$du->addExtensionTable( 'gather_list_item', "$dir/gather_list_item.sql" );
		$du->addExtensionTable( 'gather_list_flag', "$dir/gather_list_flag.sql" );

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
		$du->addExtensionIndex( 'gather_list_item', 'gli_id_ns_title',
			__DIR__ . '/archive/add-gli_id_ns_title.sql' );

		// adds gl_flag_count, gl_perm_override and gl_needs_review at the same time
		$du->addExtensionField( 'gather_list', 'gl_flag_count',
			__DIR__ . '/archive/add-gl_list-flag-columns.sql' );

		return true;
	}
}
