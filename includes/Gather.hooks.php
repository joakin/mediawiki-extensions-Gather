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
use User;

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
	 *  Add Gather notifications events to Echo
	 *
	 * @param $notifications array of Echo notifications
	 * @param $notificationCategories array of Echo notification categories
	 * @param $icons array of icon details
	 * @return bool
	 */
	public static function onBeforeCreateEchoEvent(
		&$notifications, &$notificationCategories, &$icons ) {

		$notificationCategories['gather'] = array(
			'priority' => 3,
			'tooltip' => 'gather-echo-pref-tooltip',
		);

		$notifications['gather-hide'] = array(
			'category' => 'gather',
			'group' => 'negative',
			'title-message' => 'gather-moderation-hidden',
			'title-params' => array( 'title' ),
			'email-subject-message' => 'gather-moderation-hidden-email-subject',
			'email-subject-params' => array( 'title' ),
			'email-body-batch-message' => 'gather-moderation-hidden-email-batch-body',
			'email-body-batch-params' => array( 'title' ),
		);

		$notifications['gather-unhide'] = array(
			'category' => 'gather',
			'group' => 'positive',
			'title-message' => 'gather-moderation-unhidden',
			'title-params' => array( 'title' ),
			'email-subject-message' => 'gather-moderation-unhidden-email-subject',
			'email-subject-params' => array( 'title' ),
			'email-body-batch-message' => 'gather-moderation-unhidden-email-batch-body',
			'email-body-batch-params' => array( 'title' ),
		);

		return true;
	}

	/**
	 * Add user to be notified on echo event
	 * @param $event EchoEvent
	 * @param $users array
	 * @return bool
	 */
	public static function onEchoGetDefaultNotifiedUsers( $event, &$users ) {
		switch ( $event->getType() ) {
			case 'gather-hide':
			case 'gather-unhide':
				$extra = $event->getExtra();
				if ( !$extra || !isset( $extra['collection-owner-id'] ) ) {
					break;
				}
				$recipientId = $extra['collection-owner-id'];
				$recipient = User::newFromId( $recipientId );
				$users[$recipientId] = $recipient;
				break;
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
								'class' => CSS::iconClass( 'collections-icon', 'before', 'collection-menu-item' ),
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
		// support for both, mobile and desktop, remove the key "Gather",
		// when mobile uses desktop login page
		$messages['Gather'] = 'gather-loginpage-desc';
		$messages[] = 'gather-anon-view-lists';
	}
}
