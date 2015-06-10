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
use BetaFeatures;
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
			'GatherClicks' => 12114785,
			'GatherFlags' => 11793295,
		);
		self::registerSchemas( array( 'schema.GatherClicks', 'schema.GatherFlags' ) );
		return true;
	}

	public static function onExtensionSetup() {
		// FIXME: This doesn't do anything as if mobilefrontend is not present
		// The reported error is "This requires Gather."
		if (
			!defined( 'MOBILEFRONTEND' ) &&
			!\ExtensionRegistry::getInstance()->isLoaded( 'MobileFrontend' )
		) {
			echo "Gather extension requires MobileFrontend.\n";
			die( -1 );
		}
	}

	/**
	 * BeforePageDisplay hook handler
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/BeforePageDisplay
	 *
	 * @param OutputPage $out
	 * @param Skin $sk
	 * @return bool
	 */
	public static function onBeforePageDisplay( &$out, &$sk ) {
		global $wgGatherEnableBetaFeature;

		$desktopBetaFeatureEnabled = class_exists( 'BetaFeatures' ) &&
				BetaFeatures::isFeatureEnabled( $out->getUser(), 'betafeatures-gather' ) &&
				$wgGatherEnableBetaFeature;

		if ( $desktopBetaFeatureEnabled ) {
			$out->addModules( 'ext.gather.desktop' );
			$out->addModuleStyles( array(
				'mediawiki.ui.input',
				'mediawiki.ui.icon',
			) );
		}
	}

	/**
	 * GetBetaFeaturePreferences hook handler
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/GetPreferences
	 *
	 * @param User $user
	 * @param array $preferences
	 *
	 * @return bool
	 */
	public static function onGetBetaFeaturePreferences( $user, &$preferences ) {
		global $wgExtensionAssetsPath, $wgGatherEnableBetaFeature;

		if ( $wgGatherEnableBetaFeature ) {
			// Enable the mobile skin on desktop
			$preferences['betafeatures-gather'] = array(
				'label-message' => 'beta-feature-gather',
				'desc-message' => 'beta-feature-gather-description',
				'info-link' => '//www.mediawiki.org/wiki/Extension:Gather',
				'discussion-link' => '//www.mediawiki.org/wiki/Extension talk:Gather',
				'screenshot' => array(
					'ltr' => "$wgExtensionAssetsPath/Gather/images/beta-feature-ltr.svg",
					'rtl' => "$wgExtensionAssetsPath/Gather/images/beta-feature-rtl.svg",
				),
			);
		}

		return true;
	}

	/**
	 * Modify mobile frontend modules to hook into the watchstar
	 * @param SkinMinerva $skin
	 * @param array $modules Resource loader modules
	 * @return boolean
	 */
	public static function onSkinMinervaDefaultModules( $skin, &$modules ) {
		// load gather only on pages, that can contain content and in mobile beta view
		if (
			$skin->getTitle()->canExist() &&
			MobileContext::singleton()->isBetaGroupMember()
		) {
			$modules['watch'] = array( 'ext.gather.init' );
		}
		return true;
	}

	/**
	 * Add collections link in personal tools menu
	 * @param array &$items Items array to be added to menu
	 */
	public static function onMobilePersonalTools( &$items ) {
		if ( MobileContext::singleton()->isBetaGroupMember() ) {
			// Add collections link below watchlist
			$itemArray = array_slice( $items, 0, 1, true );
			$itemArray[] = array(
				'name' => 'collections',
				'components' => array(
					array(
						'text' => wfMessage( 'gather-lists-title' )->escaped(),
						'href' => SpecialPage::getTitleFor( 'Gather' )->getLocalURL(),
						'class' => CSS::iconClass( 'collections-icon', 'before', 'collection-menu-item' ),
						'data-event-name' => 'collections',
					),
				),
			);
			$itemArray += array_slice( $items, 1, count( $items ) - 1, true );
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

		$modules['qunit']['ext.gather.api.tests'] = $boilerplate + array(
			'scripts' => array(
				'ext.gather.api/test_RelatedPagesApi.js',
			),
			'dependencies' => array( 'ext.gather.api' ),
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
		$messages = array_merge( $messages,
			array(
				'gather-anon-view-lists',
				'gather-purpose-login-action', // watchstar button login CTA
				'gather-purpose-signup-action', // watchstar button sign-up CTA
			)
		);
	}

	/**
	 * Mark fields which contain copies of user IDs and need to be updated when those IDs change.
	 * Lists with conflicting labels are ignored and will be dealt with by onMergeAccountFromTo().
	 * @param array $updateFields Array of [tableName, idField, textField,
	 *    'batchKey' => unique field, 'options' => array(...), 'db' => DatabaseBase] records.
	 *    all except tableName and idField are optional.
	 */
	public static function onUserMergeAccountFields( array &$updateFields ) {
		$updateFields[] = array( 'gather_list', 'gl_user', 'batchKey' => 'gl_id',
			'options' => array( 'IGNORE' ) );
	}

	/**
	 * Handle user ID changes where the simple approach via onUserMergeAccountFields() led to a
	 * key conflict. Non-conflicting rows have already been updated by this point.
	 * @param User $oldUser
	 * @param User $newUser
	 */
	public static function onMergeAccountFromTo( User $oldUser, User $newUser ) {
		$oldId = $oldUser->getId();
		$newId = $newUser->getId();
		$dbw = wfGetDB( DB_MASTER );
		$disambiguator = $dbw->addQuotes( ' (' . $oldUser->getName() . ')' );

		// Some edge cases are not handled well (does not seem worth the effort):
		// - in the extremely unlikely case that oldUser has a list labeled "foo" and newUser has
		//   both a list labeled "foo" and a list labeled "foo (oldUser)", the list is not migrated
		// - the update is not batched and will perform poorly if someone has thousands of
		//   lists with conflicting labels, which is extremely unlikely to happen
		// - the disambiguated list name can exceed the normal 90 character label limit
		// - if the disambiguated list name would exceed 255 characters, it gets truncated
		$dbw->update( 'gather_list',
			array( 'gl_user' => $newId, "gl_label = CONCAT(gl_label, $disambiguator )" ),
			array( 'gl_user' => $oldId ),
		__METHOD__, array( 'IGNORE' ) );
	}
}
