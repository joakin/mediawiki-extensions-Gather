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
 * A boilerplate containing common properties for all RL modules served to mobile site special pages
 * Restricted to mobile site.
 */
$wgGatherMobileSpecialPageResourceBoilerplate = $wgGatherResourceBoilerplate + array(
	'targets' => 'mobile',
	'group' => 'other',
);

$wgResourceModules = array_merge( $wgResourceModules, array(

	'ext.gather.icons' => $wgGatherResourceFileModuleBoilerplate + array(
		'class' => 'ResourceLoaderImageModule',
		'prefix' => 'mw-ui',
		'images' => array(
			// FIXME: ':before' suffix should be configurable in image module.
			'icon' => array(
				'collections-read-more:before' => 'ext.gather.icons/next.svg',
				'collection-owner:before' => 'ext.gather.icons/user.svg',
			),
		),
	),

	'ext.gather.styles' => $wgGatherResourceFileModuleBoilerplate + array(
		'styles' => array(
			'ext.gather.styles/collections.less',
		),
		'position' => 'top',
		'group' => 'other',
	),

	'ext.gather.watchstar.icons' => $wgGatherResourceFileModuleBoilerplate + array(
		'class' => 'ResourceLoaderImageModule',
		'prefix' => 'mw-ui',
		'images' => array(
			// FIXME: ':before' suffix should be configurable in image module.
			'icon' => array(
				'tick-disabled:before' => 'ext.gather.watchstar.icons/grey_check.svg',
				'tick:before' => 'ext.gather.watchstar.icons/green_check.svg',
			),
		),
	),

	'ext.gather.api' => $wgGatherResourceFileModuleBoilerplate + array(
		'dependencies' => array(
			'mobile.watchstar',
		),
		'scripts' => array(
			'ext.gather.watchstar/CollectionsApi.js',
		),
	),

	'ext.gather.watchstar' => $wgGatherResourceFileModuleBoilerplate + array(
		'dependencies' => array(
			'mobile.watchstar',
			'ext.gather.api',
			'mobile.contentOverlays',
			'ext.gather.watchstar.icons',
		),
		'styles' => array(
			'ext.gather.watchstar/contentOverlay.less',
		),
		'messages' => array(
			'gather-add-to-existing',
			'gather-watchlist-title',
			'gather-add-toast',
			'gather-remove-toast',
			'gather-anon-cta',
			'gather-collection-member',
			'gather-create-new-button-label',
			'gather-add-to-new',
			'gather-collection-non-member',
		),
		'templates' => array(
			'content.hogan' => 'ext.gather.watchstar/content.hogan',
		),
		'scripts' => array(
			'ext.gather.watchstar/CollectionsContentOverlay.js',
			'ext.gather.watchstar/CollectionsWatchstar.js',
			'ext.gather.watchstar/init.js',
		),
	),

	'ext.gather.collection.editor' => $wgGatherResourceFileModuleBoilerplate + array(
		'dependencies' => array(
			'mobile.overlays',
			'mobile.toast',
			'ext.gather.api',
		),
		'messages' => array(
			'gather-edit-collection-label-name',
			'gather-edit-collection-label-description',
			'gather-edit-collection-label-privacy',
			'gather-edit-collection-save-label',
		),
		'templates' => array(
			'content.hogan' => 'ext.gather.collection.editor/content.hogan',
		),
		'scripts' => array(
			'ext.gather.collection.editor/CollectionEditOverlay.js',
		),
		'styles' => array(
			'ext.gather.collection.editor/editOverlay.less',
		),
	),

	'ext.gather.special' => $wgGatherResourceFileModuleBoilerplate + array(
		'dependencies' => array(
			'ext.gather.collection.editor',
		),
		'scripts' => array(
			'ext.gather.special/init.js',
		),
	),

) );
