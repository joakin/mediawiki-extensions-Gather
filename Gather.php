<?php
/**
 * Extension Gather
 *
 * @file
 * @ingroup Extensions
 * @author Jon Robson
 * @author Joaquin Hernandez
 * @author Rob Moen
 * @author Yuri Astrakhan
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
	'author' => array( 'Jon Robson', 'Joaquin Hernandez', 'Rob Moen', 'Yuri Astrakhan' ),
	'descriptionmsg' => 'gather-desc',
	'url' => 'https://www.mediawiki.org/wiki/Gather',
	'license-name' => 'GPL-2.0+',
);

$wgMessagesDirs['Gather'] = __DIR__ . '/i18n';
$wgExtensionMessagesFiles['GatherAlias'] = __DIR__ . "/Gather.alias.php";

// autoload extension classes
$autoloadClasses = array(
	'Gather\Hooks' => 'Gather.hooks',
	'Gather\UpdaterHooks' => '../schema/Updater.hooks',

	'Gather\models\CollectionItem' => 'models/CollectionItem',
	'Gather\models\CollectionBase' => 'models/CollectionBase',
	'Gather\models\CollectionFeed' => 'models/CollectionFeed',
	'Gather\models\CollectionFeedItem' => 'models/CollectionFeedItem',
	'Gather\models\CollectionInfo' => 'models/CollectionInfo',
	'Gather\models\Collection' => 'models/Collection',
	'Gather\models\CollectionsList' => 'models/CollectionsList',
	'Gather\models\WithImage' => 'models/WithImage',
	'Gather\models\ArraySerializable' => 'models/ArraySerializable',

	'Gather\views\View' => 'views/View',
	'Gather\views\NotFound' => 'views/NotFound',
	'Gather\views\NoPublic' => 'views/NoPublic',
	'Gather\views\CollectionFeed' => 'views/CollectionFeed',
	'Gather\views\CollectionFeedItem' => 'views/CollectionFeedItem',
	'Gather\views\Collection' => 'views/Collection',
	'Gather\views\CollectionItemCard' => 'views/CollectionItemCard',
	'Gather\views\Image' => 'views/Image',
	'Gather\views\CollectionsList' => 'views/CollectionsList',
	'Gather\views\CollectionsListItemCard' => 'views/CollectionsListItemCard',
	'Gather\views\Pagination' => 'views/Pagination',

	'Gather\views\helpers\CSS' => 'views/helpers/CSS',

	'Gather\SpecialGather' => 'specials/SpecialGather',
	'Gather\SpecialGatherLists' => 'specials/SpecialGatherLists',
	'Gather\SpecialGatherEditFeed' => 'specials/SpecialGatherEditFeed',

	'Gather\api\ApiEditList' => 'api/ApiEditList',
	'Gather\api\ApiQueryLists' => 'api/ApiQueryLists',
	'Gather\api\ApiQueryListPages' => 'api/ApiQueryListPages',

);

foreach ( $autoloadClasses as $className => $classFilename ) {
	$wgAutoloadClasses[$className] = __DIR__ . "/includes/$classFilename.php";
}


$wgSpecialPages['Gather'] = 'Gather\SpecialGather';
$wgSpecialPages['GatherLists'] = 'Gather\SpecialGatherLists';
$wgSpecialPages['GatherEditFeed'] = 'Gather\SpecialGatherEditFeed';

// Hooks
$wgExtensionFunctions[] = 'Gather\Hooks::onExtensionSetup';
$wgHooks['MobilePersonalTools'][] = 'Gather\Hooks::onMobilePersonalTools';
$wgHooks['UnitTestsList'][] = 'Gather\Hooks::onUnitTestsList';
$wgHooks['SkinMinervaDefaultModules'][] = 'Gather\Hooks::onSkinMinervaDefaultModules';
$wgHooks['MakeGlobalVariablesScript'][] = 'Gather\Hooks::onMakeGlobalVariablesScript';
$wgHooks['ResourceLoaderTestModules'][] = 'Gather\Hooks::onResourceLoaderTestModules';
$wgHooks['EventLoggingRegisterSchemas'][] = 'Gather\Hooks::onEventLoggingRegisterSchemas';
$wgHooks['ResourceLoaderRegisterModules'][] = 'Gather\Hooks::onResourceLoaderRegisterModules';
$wgHooks['LoginFormValidErrorMessages'][] = 'Gather\Hooks::onLoginFormValidErrorMessages';

// Maintenance Hooks
$wgHooks['LoadExtensionSchemaUpdates'][] = 'Gather\UpdaterHooks::onLoadExtensionSchemaUpdates';

// Api
$wgAPIModules['editlist'] = 'Gather\api\ApiEditList';
$wgAPIListModules['lists'] = 'Gather\api\ApiQueryLists';
$wgAPIListModules['listpages'] = 'Gather\api\ApiQueryListPages';

// Configuration
$wgGatherShouldShowTutorial = true;

// Permissions
$wgAvailableRights[] = 'gather-hidelist';
$wgGroupPermissions['*']['gather-hidelist'] = false;
$wgGroupPermissions['sysop']['gather-hidelist'] = true;

/**
 * If true, user's watchlist can be made public
 */
$wgGatherAllowPublicWatchlist = false;

// ResourceLoader modules
require_once __DIR__ . "/resources/Resources.php";
