<?php
if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'Gather' );
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['Gather'] = __DIR__ . '/i18n';
	$wgExtensionMessagesFiles['GatherAlias'] = __DIR__ . '/Gather.alias.php';
	/* wfWarn(
		'Deprecated PHP entry point used for Gather extension. Please use wfLoadExtension instead, ' .
		'see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	); */
	return;
} else {
	die( 'This version of the Gather extension requires MediaWiki 1.25+' );
}
