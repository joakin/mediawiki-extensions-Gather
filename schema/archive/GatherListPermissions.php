<?php
/**
 * @ingroup Maintenance
 */

namespace Gather;

use FormatJson;
use Gather\api\ApiEditList;
use LoggedUpdateMaintenance;

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}
require_once ( "$IP/maintenance/Maintenance.php" );

/**
 * @ingroup Maintenance
 */
class GatherListPermissions extends LoggedUpdateMaintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Fix permissions and timestamp fields on gather_list';
	}

	protected function getUpdateKey() {
		return 'update gather list permissions';
	}

	protected function updateSkippedMessage() {
		return 'gather list permissions already updated';
	}

	protected function doDBUpdates() {
		$this->output( "Populating gl_perm column in gather_list table\n" );

		$db = wfGetDB( DB_MASTER );

		$totalCount = 0;
		$batchSize = $count = $this->mBatchSize;

		while ( $batchSize === $count ) {
			$count = 0;

			$res = $db->select(
				'gather_list',
				array( 'gl_id', 'gl_info', 'gl_label' ),
				array( 'gl_perm' => null ),
				__METHOD__,
				array( 'LIMIT' => $batchSize )
			);

			foreach ( $res as $row ) {
				$count++;

				$info = ApiEditList::parseListInfo( $row->gl_info, $row->gl_id, true );
				$perm = ( ApiEditList::isPublic( $info ) && $row->gl_label !== '' ) ? 1 : 0;
				unset( $info->perm );
				unset( $info->public );

				$db->update(
					'gather_list',
					array(
						'gl_perm' => $perm,
						'gl_info' => FormatJson::encode( $info, false, FormatJson::ALL_OK ),
						'gl_updated' => $db->timestamp( wfTimestampNow() ),
					),
					array( 'gl_id' => $row->gl_id ),
					__METHOD__ );
			}

			$totalCount += $count;

			wfWaitForSlaves();
		}

		$this->output( "Done, $totalCount rows updated.\n" );
		return true;
	}
}

$maintClass = 'GatherListPermissions';
require_once ( RUN_MAINTENANCE_IF_MAIN );
