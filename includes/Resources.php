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
	'localBasePath' => __DIR__ . '/../resources',
	'remoteExtPath' => 'Gather',
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

	'ext.collections.icons' => $wgGatherResourceFileModuleBoilerplate + array(
		'class' => 'ResourceLoaderImageModule',
		'prefix' => 'mw-ui',
		'images' => array(
			// FIXME: ':before' suffix should be configurable in image module.
			'icon' => array(
				'collections-read-more:before' => 'images/icons/next.svg',
			),
		),
	),

	'ext.collections.styles' => $wgGatherResourceFileModuleBoilerplate + array(
		'styles' => array(
			'ext.collections.styles/icons.less',
			'ext.collections.styles/collections.less',
		),
		'dependencies' => array(
			'mediawiki.ui.anchor',
			'mediawiki.ui.icon',
			'skins.minerva.special.styles',
			'ext.collections.icons',
		),
		'position' => 'top',
		'group' => 'other',
	),

) );
