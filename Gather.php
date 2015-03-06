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

// autoload extension classes
$autoloadClasses = array(
	'Gather\Hooks' => 'Gather.hooks',

	'Gather\models\CollectionItem' => 'models/CollectionItem',
	'Gather\models\CollectionBase' => 'models/CollectionBase',
	'Gather\models\CollectionInfo' => 'models/CollectionInfo',
	'Gather\models\Collection' => 'models/Collection',
	'Gather\models\CollectionsList' => 'models/CollectionsList',
	'Gather\models\WithImage' => 'models/WithImage',
	'Gather\models\ArraySerializable' => 'models/ArraySerializable',

	'Gather\stores\JSONPage' => 'stores/JSONPage',
	'Gather\stores\Collection' => 'stores/Collection',
	'Gather\stores\WatchlistCollection' => 'stores/WatchlistCollection',
	'Gather\stores\CollectionStorage' => 'stores/CollectionStorage',
	'Gather\stores\UserPageCollection' => 'stores/UserPageCollection',
	'Gather\stores\CollectionsListStorage' => 'stores/CollectionsListStorage',
	'Gather\stores\UserPageCollectionsList' => 'stores/UserPageCollectionsList',
	'Gather\stores\ItemExtracts' => 'stores/ItemExtracts',
	'Gather\stores\ItemImages' => 'stores/ItemImages',

	'Gather\views\View' => 'views/View',
	'Gather\views\NotFound' => 'views/NotFound',
	'Gather\views\Collection' => 'views/Collection',
	'Gather\views\CollectionItemCard' => 'views/CollectionItemCard',
	'Gather\views\Image' => 'views/Image',
	'Gather\views\CollectionsList' => 'views/CollectionsList',
	'Gather\views\CollectionsListItemCard' => 'views/CollectionsListItemCard',

	'Gather\views\helpers\CSS' => 'views/helpers/CSS',

	'Gather\SpecialGather' => 'specials/SpecialGather',

	'Gather\api\CollectionsListApi' => 'api/CollectionsListApi',

);

foreach ( $autoloadClasses as $className => $classFilename ) {
	$wgAutoloadClasses[$className] = __DIR__ . "/includes/$classFilename.php";
}


$wgSpecialPages['Gather'] = 'Gather\SpecialGather';

// Hooks
$wgExtensionFunctions[] = 'Gather\Hooks::onExtensionSetup';
$wgHooks['MobilePersonalTools'][] = 'Gather\Hooks::onMobilePersonalTools';
$wgHooks['UnitTestsList'][] = 'Gather\Hooks::onUnitTestsList';
$wgHooks['getUserPermissionsErrors'][] = 'Gather\Hooks::onGetUserPermissionsErrors';
$wgHooks['ContentHandlerDefaultModelFor'][] = 'Gather\Hooks::onContentHandlerDefaultModelFor';
$wgHooks['SkinMinervaDefaultModules'][] = 'Gather\Hooks::onSkinMinervaDefaultModules';
$wgHooks['MakeGlobalVariablesScript'][] = 'Gather\Hooks::onMakeGlobalVariablesScript';
$wgHooks['ResourceLoaderTestModules'][] = 'Gather\Hooks::onResourceLoaderTestModules';

// Api
$wgAPIModules['gather'] = 'Gather\api\CollectionsListApi';

// ResourceLoader modules
require_once __DIR__ . "/resources/Resources.php";

