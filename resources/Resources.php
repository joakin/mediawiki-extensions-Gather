<?php
/**
 * Definition of Gather's ResourceLoader modules.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

/**
 * A boilerplate for RL modules that do not support templates
 * Agnostic to whether desktop or mobile specific.
 */
$wgGatherResourceBoilerplate = array(
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'Gather/resources',
);

/**
 * A mobile enabled ResourceLoaderFileModule template
 */
$wgGatherResourceFileModuleBoilerplate = $wgGatherResourceBoilerplate + array(
	'targets' => array( 'mobile', 'desktop' ),
);

/**
 * A ResourceLoaderFileModule template for special pages
 */
$wgGatherMobileSpecialPageResourceBoilerplate = $wgGatherResourceFileModuleBoilerplate + array(
	'group' => 'other',
);

$wgResourceModules += array(

	'ext.gather.menu.icon' => $wgGatherResourceFileModuleBoilerplate + array(
		'position' => 'top',
		'class' => 'ResourceLoaderImageModule',
		'selector' => '.mw-ui-icon-{name}:before',
		'images' => array(
			'collections-icon' => 'ext.gather.menu.icon/plus.svg',
		),
	),

	'ext.gather.icons' => $wgGatherResourceFileModuleBoilerplate + array(
		'position' => 'top',
		'class' => 'ResourceLoaderImageModule',
		'selector' => '.mw-ui-icon-{name}:before',
		'images' => array(
			'collections-read-more' => 'ext.gather.icons/next.svg',
			'collection-owner' => 'ext.gather.icons/user.svg',
			'collection-flag' => 'ext.gather.icons/flag.svg',
			'collection-hide' => 'ext.gather.icons/suppress.svg',
		),
	),

	'ext.gather.styles' => $wgGatherMobileSpecialPageResourceBoilerplate + array(
		'position' => 'top',
		'styles' => array(
			'ext.gather.styles/collections.less',
			'ext.gather.styles/lists.less',
			'ext.gather.styles/editfeed.less',
		),
		'skinStyles' => array(
			'minerva' => 'ext.gather.styles/minerva.less',
			'vector' => 'ext.gather.styles/vector.less',
		),
	),

	'ext.gather.watchstar.icons' => $wgGatherResourceFileModuleBoilerplate + array(
		'class' => 'ResourceLoaderImageModule',
		'selector' => '.mw-ui-icon-{name}:before',
		'images' => array(
			'tick-disabled' => 'ext.gather.watchstar.icons/grey_check.svg',
			'tick' => 'ext.gather.watchstar.icons/green_check.svg',
		),
	),

	'ext.gather.logging' => $wgGatherResourceFileModuleBoilerplate + array(
		'dependencies' => array(
			// FIXME: Break Schema.js out of MobileFrontend
			'mobile.startup',
			// FIXME: getUserEditCount should be part of mw.user
			'mobile.user',
			'ext.gather.schema',
		),
		'scripts' => array(
			'ext.gather.logging/SchemaGather.js',
			'ext.gather.logging/SchemaGatherFlags.js',
		),
	),

	'ext.gather.api' => $wgGatherResourceFileModuleBoilerplate + array(
		'dependencies' => array(
			// FIXME: All we need is Api.js ...
			'mobile.startup',
		),
		'scripts' => array(
			'ext.gather.api/CollectionsApi.js',
		),
	),

	'ext.gather.collections.list' => $wgGatherResourceFileModuleBoilerplate + array(
		'dependencies' => array(
		),
		'styles' => array(
		),
		'messages' => array(
		),
		'templates' => array(
			'CollectionsList.hogan' => '../templates/CollectionsList.mustache',
			'CollectionsListItemCard.hogan' => '../templates/CollectionsListItemCard.mustache',
		),
		'scripts' => array(
			'ext.gather.collections.list/CollectionsList.js',
		),
	),

	'ext.gather.collection.base' => $wgGatherResourceFileModuleBoilerplate + array(
		'dependencies' => array(
			'ext.gather.logging',
			'mobile.contentOverlays',
			'mobile.toast',
			'ext.gather.api',
			'mediawiki.util'
		),
		'scripts' => array(
			'ext.gather.collection.base/CollectionsContentOverlayBase.js',
		),
	),

	'ext.gather.collection.confirm' => $wgGatherResourceFileModuleBoilerplate + array(
		'dependencies' => array(
			'ext.gather.collection.base',
		),
		'styles' => array(
			'ext.gather.collection.confirm/confirmationOverlay.less',
		),
		'messages' => array(
			'gather-error-unknown-collection',
			'gather-confirmation-cancel-button-label',
		),
		'templates' => array(
			'confirmationOverlay.hogan' => 'ext.gather.collection.confirm/confirmationOverlay.hogan',
		),
		'scripts' => array(
			'ext.gather.collection.confirm/ConfirmationOverlay.js',
		),
	),

	'ext.gather.watchstar' => $wgGatherResourceFileModuleBoilerplate + array(
		'dependencies' => array(
			'ext.gather.routes',
			'mediawiki.util',
			'mobile.user',
			'ext.gather.api',
			'ext.gather.collection.base',
			'mobile.settings',
			'ext.gather.watchstar.icons',
			// FIXME: Cannot push to stable until buttonWithSpinner is a view (T95490)
			'mobile.buttonWithSpinner',
		),
		'skinStyles' => array(
			'vector' => 'ext.gather.watchstar/vector.less',
		),
		'styles' => array(
			'ext.gather.watchstar/contentOverlay.less',
			'ext.gather.watchstar/tag.less',
		),
		'messages' => array(
			'gather-remove-from-collection-failed-toast',
			'gather-add-to-collection-failed-toast',
			'gather-new-collection-failed-toast',
			'gather-add-to-existing',
			'gather-watchlist-title',
			'gather-add-toast',
			'gather-add-failed-toast',
			'gather-add-title-invalid-toast',
			'gather-remove-toast',
			'gather-anon-cta',
			'gather-collection-member',
			'gather-create-new-button-label',
			'gather-add-to-new',
			'gather-collection-non-member',
			'gather-add-new-placeholder',
			'gather-add-to-collection-summary',
			'gather-add-to-collection-confirm',
			'gather-add-to-collection-cancel',
			'gather-add-to-another',
			'gather-watchstar-button-label',
		),
		'templates' => array(
			'star.hogan' => 'ext.gather.watchstar/star.hogan',
			'content.hogan' => 'ext.gather.watchstar/content.hogan',
			'Tag.hogan' => 'ext.gather.watchstar/Tag.hogan',
		),
		'scripts' => array(
			'ext.gather.watchstar/CollectionsContentOverlay.js',
			'ext.gather.watchstar/CollectionsWatchstar.js',
			'ext.gather.watchstar/WatchstarPageActionOverlay.js',
			'ext.gather.watchstar/Tag.js',
		),
	),

	'ext.gather.desktop' => $wgGatherResourceFileModuleBoilerplate + array(
		'dependencies' => array(
			'mediawiki.util',
		),
		'messages' => array(
			'gather-lists-title',
		),
		'position' => 'top',
		'skinStyles' => array(
			'default' => 'ext.gather.desktop/default.less',
			'minerva' => '',
		),
		'scripts' => array(
			'ext.gather.desktop/init.js',
		),
	),

	'ext.gather.init' => $wgGatherResourceFileModuleBoilerplate + array(
		'dependencies' => array(
			'ext.gather.alerts.init',
			'ext.gather.watchstar',
		),
		'messages' => array(
			'gather-menu-guider',
		),
		'scripts' => array(
			'ext.gather.init/init.js',
		),
		'skinStyles' => array(
			'vector' => 'ext.gather.init/vector.less',
			'minerva' => 'ext.gather.init/minerva.less',
		),
	),

	'ext.gather.collection.editor' => $wgGatherResourceFileModuleBoilerplate + array(
		'dependencies' => array(
			'ext.gather.page.search',
			'ext.gather.logging',
			'mobile.overlays',
			'mobile.toast',
			'ext.gather.api',
			'ext.gather.collection.delete',
		),
		'messages' => array(
			"gather-edit-collection-failed-error",
			'gather-edit-collection-label-name',
			'gather-edit-collection-label-description',
			'gather-edit-collection-label-public',
			'gather-edit-collection-save-label',
			'gather-error-unknown-collection',
			'gather-overlay-continue',
			'gather-overlay-edit-button',
			'gather-delete-button',
			'gather-edit-collection-clear-label',
			'gather-update-collection-success',
			'gather-edit-collection-confirm',
		),
		'templates' => array(
			'header.hogan' => 'ext.gather.collection.editor/header.hogan',
			'content.hogan' => 'ext.gather.collection.editor/content.hogan',
		),
		'scripts' => array(
			'ext.gather.collection.editor/CollectionEditOverlay.js',
		),
		'styles' => array(
			'ext.gather.collection.editor/editOverlay.less',
		),
	),

	'ext.gather.page.search' => $wgGatherResourceFileModuleBoilerplate + array(
		'dependencies' => array(
			'ext.gather.api',
			'mobile.pagelist.scripts',
			'mobile.search',
		),
		'messages' => array(
			// FIXME: Duplicates messages in ext.gather.watchstar
			'gather-remove-toast',
			'gather-add-toast',
		),
		'templates' => array(
			'CollectionSearchPanel.hogan' => 'ext.gather.page.search/CollectionSearchPanel.hogan',
			'item.hogan' => 'ext.gather.page.search/item.hogan',
		),
		'styles' => array(
			'ext.gather.page.search/searchPanel.less',
		),
		'scripts' => array(
			'ext.gather.page.search/CollectionPageList.js',
			'ext.gather.page.search/CollectionSearchPanel.js',
		),
	),

	'ext.gather.alerts.futureToasts' => $wgGatherResourceFileModuleBoilerplate + array(
		'dependencies' => array(
			'mobile.settings',
			'mobile.toast',
		),
		'scripts' => array(
			'ext.gather.alerts/futureToasts.js',
		),
	),

	'ext.gather.alerts.init' => $wgGatherResourceFileModuleBoilerplate + array(
		'dependencies' => array(
			'ext.gather.alerts.futureToasts',
		),
		'scripts' => array(
			'ext.gather.alerts/init.js',
		),
	),

	'ext.gather.collection.delete' => $wgGatherResourceFileModuleBoilerplate + array(
		'dependencies' => array(
			'ext.gather.collection.confirm',
			'mobile.toast',
			'ext.gather.alerts.futureToasts',
			'ext.gather.api',
			'mediawiki.util'
		),
		'messages' => array(
			'gather-delete-collection-confirm',
			'gather-delete-collection-heading',
			'gather-delete-collection-delete-label',
			'gather-delete-collection-success',
			'gather-delete-collection-failed-error',
		),
		'scripts' => array(
			'ext.gather.collection.delete/CollectionDeleteOverlay.js',
		),
	),

	'ext.gather.collection.flag' => $wgGatherResourceFileModuleBoilerplate + array(
		'dependencies' => array(
			'ext.gather.collection.confirm',
			'mobile.toast',
			'ext.gather.api',
			'mediawiki.util'
		),
		'messages' => array(
			'gather-flag-collection-confirm',
			'gather-flag-collection-heading',
			'gather-flag-collection-flag-label',
			'gather-flag-collection-success',
		),
		'scripts' => array(
			'ext.gather.collection.flag/CollectionFlagOverlay.js',
			'ext.gather.collection.flag/CollectionFlagButton.js',
		),
	),

	'ext.gather.routes' => $wgGatherMobileSpecialPageResourceBoilerplate + array(
		'dependencies' => array(
			'ext.gather.api',
			'mobile.toast',
		),
		'scripts' => array(
			'ext.gather.routes/routes.js',
		),
		'messages' => array(
			'gather-no-such-action',
			'gather-unknown-error',
		),
	),

	'ext.gather.special.base' => $wgGatherMobileSpecialPageResourceBoilerplate + array(
		'dependencies' => array(
			'ext.gather.alerts.init',
			'ext.gather.collection.editor',
			'ext.gather.routes',
		),
	),

	'ext.gather.special.usercollections' => $wgGatherMobileSpecialPageResourceBoilerplate + array(
		'dependencies' => array(
			'ext.gather.special.base',
			'ext.gather.collections.list',
		),
		'scripts' => array(
			'ext.gather.special.usercollections/init.js',
		),
	),

	'ext.gather.special.collection' => $wgGatherMobileSpecialPageResourceBoilerplate + array(
		'dependencies' => array(
			'ext.gather.special.base',
			'ext.gather.collection.flag',
			'ext.gather.moderation',
		),
		'scripts' => array(
			'ext.gather.special.collection/init.js',
		),
	),

	'ext.gather.special' => $wgGatherMobileSpecialPageResourceBoilerplate + array(
		'dependencies' => array(
			'ext.gather.special.collection',
			'ext.gather.special.usercollections',
		),
	),

	'ext.gather.moderation' => $wgGatherMobileSpecialPageResourceBoilerplate + array(
		'dependencies' => array(
			'mobile.toast',
			'ext.gather.api',
		),
		'messages' => array(
			'gather-lists-hide-collection',
			'gather-lists-hide-success-toast',
			'gather-lists-hide-failure-toast',
			'gather-lists-show-collection',
			'gather-lists-show-success-toast',
			'gather-lists-show-failure-toast',
		),
		'scripts' => array(
			'ext.gather.moderation/init.js',
		),
	),
);

unset( $wgGatherResourceFileModuleBoilerplate );
unset( $wgGatherResourceBoilerplate );
unset( $wgGatherMobileSpecialPageResourceBoilerplate );
