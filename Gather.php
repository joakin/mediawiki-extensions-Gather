<?php
/**
 * Extension Gather
 *
 * @file
 * @ingroup Extensions
 * @author Jon Robson
 * @author Joaquin Hernandez
 * @author Rob Moen
 * @licence GNU General Public Licence 2.0 or later
 */

// Needs to be called within MediaWiki; not standalone
if ( !defined( 'MEDIAWIKI' ) ) {
	echo "This is a MediaWiki extension and cannot run standalone.\n";
	die( -1 );
}

// Extension credits that will show up on Special:Version
$wgExtensionCredits['other'][] = array(
	'path' => __FILE__,
	'name' => 'Gather',
	'author' => array( 'Jon Robson', 'Joaquin Hernandez', 'Rob Moen' ),
	'descriptionmsg' => 'gather-desc',
	'url' => 'https://www.mediawiki.org/wiki/Gather',
	'license-name' => 'GPL-2.0+',
);

$wgMessagesDirs['Gather'] = __DIR__ . '/i18n';
$wgExtensionMessagesFiles['GatherAlias'] = __DIR__ . "/Gather.alias.php";

function efGatherExtensionSetup() {
	// FIXME: This doesn't do anything as if mobilefrontend is not present
	// The reported error is "This requires Gather."
	if ( !defined( 'MOBILEFRONTEND' ) ) {
		echo "Gather extension requires MobileFrontend.\n";
		die( -1 );
	}
}

// autoload extension classes
$autoloadClasses = array(
	'Gather\Hooks' => 'Gather.hooks',

	'Gather\models\CollectionItem' => 'models/CollectionItem',
	'Gather\models\Collection' => 'models/Collection',

	'Gather\stores\Collection' => 'stores/Collection',
	'Gather\stores\WatchlistCollection' => 'stores/WatchlistCollection',
	'Gather\stores\CollectionsList' => 'stores/CollectionsList',
	'Gather\stores\DumbWatchlistOnlyCollectionsList' => 'stores/DumbWatchlistOnlyCollectionsList',
	'Gather\stores\ItemExtracts' => 'stores/ItemExtracts',
	'Gather\stores\ItemImages' => 'stores/ItemImages',

	'Gather\views\View' => 'views/View',
	'Gather\views\UserNotFound' => 'views/UserNotFound',
	'Gather\views\Collection' => 'views/Collection',
	'Gather\views\CollectionItemCard' => 'views/CollectionItemCard',
	'Gather\views\ItemImage' => 'views/ItemImage',
	'Gather\views\CollectionsList' => 'views/CollectionsList',
	'Gather\views\CollectionsListItemCard' => 'views/CollectionsListItemCard',

	'Gather\views\helpers\CSS' => 'views/helpers/CSS',

	'Gather\SpecialGather' => 'specials/SpecialGather',
);

foreach ( $autoloadClasses as $className => $classFilename ) {
	$wgAutoloadClasses[$className] = __DIR__ . "/includes/$classFilename.php";
}

$wgExtensionFunctions[] = 'efGatherExtensionSetup';

$wgSpecialPages['Gather'] = 'Gather\SpecialGather';

// Hooks
$wgHooks['MobilePersonalTools'][] = 'Gather\Hooks::onMobilePersonalTools';

// ResourceLoader modules
require_once __DIR__ . "/includes/Resources.php";

