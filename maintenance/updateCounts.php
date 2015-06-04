<?php

/**
 * To the extent possible under law,  I, Gergő Tisza, have waived all copyright and
 * related or neighboring rights to UpdateCounts. This work is published from the
 * United States.
 *
 * @copyright CC0 http://creativecommons.org/publicdomain/zero/1.0/
 * @author Gergő Tisza <gtisza@wikimedia.org>
 * @ingroup Maintenance
 */

namespace Gather\maintenance;

use Maintenance;

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = dirname( __FILE__ ) . '/../../..';
}
require_once ( "$IP/maintenance/Maintenance.php" );

/**
 * A maintenance script that corrects Gather collection item counts.
 * Watchlist counts will be left at 0.
 */
class UpdateCounts extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Corrects Gather collection item counts';
		$this->setBatchSize( 5 );
	}

	public function execute() {
		$dbw = wfGetDB( DB_MASTER );

		$maxGlId = 0;
		do {
			$dbw->begin( __METHOD__ );

			// This locks the list record. All operations which can add/remove list items (apart
			// from full list deletion) lock the record as well, so there cannot be race conditions.
			$res = $dbw->select( 'gather_list',
				array( 'gl_id' ),
				array( 'gl_id > ' . $maxGlId ),
				__METHOD__,
				array(
					'ORDER BY' => 'gl_id',
					'LIMIT' => $this->mBatchSize,
					'FOR UPDATE',
				)
			);

			$ids = array();
			foreach ( $res as $row ) {
				$ids[] = $row->gl_id;
			}

			if ( !$ids ) {
				$dbw->rollback( __METHOD__ );
				continue;
			}

			$dbw->update( 'gather_list',
				array( 'gl_item_count = (SELECT COUNT(*) FROM gather_list_item WHERE gli_gl_id = gl_id)' ),
				array( 'gl_id' => $ids ),
			__METHOD__ );

			$dbw->commit( __METHOD__ );
			$maxGlId = max( $ids );
		} while ( $res->numRows() );
	}
}

$maintClass = 'Gather\\maintenance\\UpdateCounts';
require_once ( RUN_MAINTENANCE_IF_MAIN );
