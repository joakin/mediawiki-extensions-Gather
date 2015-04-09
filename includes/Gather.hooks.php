<?php
/**
 * Gather.hooks.php
 */

namespace Gather;

use SpecialPage;
use Gather\models;
use Gather\views\helpers\CSS;
use MobileContext;
use ResourceLoader;
use PageImages;

/**
 * Hook handlers for Gather extension
 *
 * Hook handler method names should be in the form of:
 *	on<HookName>()
 * For intance, the hook handler for the 'RequestContextCreateSkin' would be called:
 *	onRequestContextCreateSkin()
 */
class Hooks {
	/**
	 * ResourceLoaderRegisterModules hook handler
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ResourceLoaderRegisterModules
	 * @param ResourceLoader &$resourceLoader The ResourceLoader object
	 * @return bool Always true
	 */
	public static function onResourceLoaderRegisterModules( ResourceLoader &$resourceLoader ) {
		// register an empty RL module
		self::registerSchemas();
		return true;
	}

	public static function registerSchemas( $dependencies = array() ) {
		global $wgResourceModules;
		$schema = array(
			'dependencies' => $dependencies,
			'targets' => array( 'desktop', 'mobile' ),
		);
		// exploits fact onEventLoggingRegisterSchemas runs after onResourceLoaderRegisterModules
		if ( !isset( $wgResourceModules['ext.gather.schema'] ) || count( $dependencies ) > 0 ) {
			$wgResourceModules['ext.gather.schema'] = $schema;
		}
		return true;
	}

	/**
	 * EventLoggingRegisterSchemas hook handler.
	 *
	 * Registers our EventLogging schemas
	 *
	 * This will override the previous definition of an empty schema written in
	 * onResourceLoaderRegisterModules.
	 *
	 * @param array $schemas The schemas currently registered with the EventLogging
	 *  extension
	 * @return bool Always true
	 */
	public static function onEventLoggingRegisterSchemas( &$schemas ) {
		$schemas += array(
			'GatherClicks' => 11770314,
		);
		self::registerSchemas( array( 'schema.GatherClicks' ) );
		return true;
	}

	public static function onExtensionSetup() {
		// FIXME: This doesn't do anything as if mobilefrontend is not present
		// The reported error is "This requires Gather."
		if ( !defined( 'MOBILEFRONTEND' ) ) {
			echo "Gather extension requires MobileFrontend.\n";
			die( -1 );
		}
	}

	/**
	 * Modify mobile frontend modules to hook into the watchstar
	 * @param SkinMinerva $skin
	 * @param array $modules Resource loader modules
	 * @return boolean
	 */
	public static function onSkinMinervaDefaultModules( $skin, &$modules ) {
		if ( MobileContext::singleton()->isBetaGroupMember() ) {
			$modules['watch'] = array( 'ext.gather.init' );
		}
		// FIXME: abuse of the hook.
		$skin->getOutput()->addModuleStyles( 'ext.gather.menu.icon' );
		return true;
	}

	/**
	 * Add collections link in personal tools menu
	 * @param array &$items Items array to be added to menu
	 */
	public static function onMobilePersonalTools( &$items ) {
		if ( MobileContext::singleton()->isBetaGroupMember() ) {
			// Add collections link below watchlist
			$itemArray = array_slice( $items, 0, 1, true ) +
				array(
					'collections' => array(
						'links' => array(
							array(
								'text' => wfMessage( 'gather-lists-title' )->escaped(),
								'href' => SpecialPage::getTitleFor( 'Gather' )->getLocalURL(),
								// FIXME: Temporarily watchlist icon
								'class' => CSS::iconClass( 'collections-icon', 'before' ),
								'data-event-name' => 'collections',
							),
						),
					),
				) +
				array_slice( $items, 1, count( $items ) - 1, true );
			$items = $itemArray;
		}
	}

	/**
	 * UnitTestsList hook handler
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UnitTestsList
	 *
	 * @param array $files
	 * @return bool
	 */
	public static function onUnitTestsList( &$files ) {
		$files[] = __DIR__ . '/../tests/phpunit';

		return true;
	}

	/**
	 * Register QUnit tests.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ResourceLoaderTestModules
	 *
	 * @param array $files
	 * @return bool
	 */
	public static function onResourceLoaderTestModules( &$modules, &$rl ) {
		$boilerplate = array(
			'localBasePath' => __DIR__ . '/../tests/qunit/',
			'remoteExtPath' => 'Gather/tests/qunit',
			'targets' => array( 'desktop', 'mobile' ),
		);

		$modules['qunit']['ext.gather.watchstar.tests'] = $boilerplate + array(
			'scripts' => array(
				'ext.gather.watchstar/test_CollectionsContentOverlay.js',
			),
			'dependencies' => array( 'ext.gather.watchstar' ),
		);
		$modules['qunit']['ext.gather.collection.editor.tests'] = $boilerplate + array(
			'scripts' => array(
				'ext.gather.collection.editor/test_CollectionEditOverlay.js',
			),
			'dependencies' => array( 'ext.gather.collection.editor' ),
		);
		return true;
	}

	/**
	 * Load user collections
	 */
	public static function onMakeGlobalVariablesScript( &$vars, $out ) {
		global $wgGatherShouldShowTutorial;
		$user = $out->getUser();
		$title = $out->getTitle();
		$vars['wgGatherShouldShowTutorial'] = $wgGatherShouldShowTutorial;
		// Expose page image.
		// FIXME: Should probably be in PageImages extension
		if ( defined( 'PAGE_IMAGES_INSTALLED' ) && $title->getNamespace() === NS_MAIN ) {
			$pageImage = PageImages::getPageImage( $title );
			if ( $pageImage ) {
				$thumb = $pageImage->transform( array( 'height' => 100, 'width' => 100 ) );
				if ( $thumb ) {
					$vars['wgGatherPageImageThumbnail'] = $thumb->getUrl();
				}
			}
		}
		return true;
	}

	/**
	 * LoginFormValidErrorMessages hook handler.
	 * Add valid error messages for Gather login pages.
	 *
	 * @see https://wwww.mediawiki.org/wiki/Manual:Hooks/LoginFormValidErrorMessages
	 *
	 * @param array $messages Array of valid messages, already added
	 */
	public static function onLoginFormValidErrorMessages( &$messages ) {
		$messages[] = 'gather-anon-view-lists';
	}
}
