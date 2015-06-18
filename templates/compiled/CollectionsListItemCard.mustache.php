<?php return function ($in, $debugopt = 1) {
    $cx = array(
        'flags' => array(
            'jstrue' => false,
            'jsobj' => false,
            'spvar' => false,
            'prop' => false,
            'method' => false,
            'mustlok' => true,
            'echo' => false,
            'debug' => $debugopt,
        ),
        'constants' => array(),
        'helpers' => array(),
        'blockhelpers' => array(),
        'hbhelpers' => array(),
        'partials' => array(),
        'scopes' => array(),
        'sp_vars' => array('root' => $in),
        'lcrun' => 'LCRun3',

    );
    
    return '<div class=\'collection-card '.((LCRun3::isec($cx, LCRun3::v($cx, $in, array('hasImage')))) ? 'without-image' : '').'\'>
	<a href=\''.htmlentities((string)LCRun3::v($cx, $in, array('collectionUrl')), ENT_QUOTES, 'UTF-8').'\' class=\'collection-card-image\'>
		'.LCRun3::v($cx, $in, array('image')).'
	</a>
	<div class=\'collection-card-overlay\' dir=\''.htmlentities((string)LCRun3::v($cx, $in, array('langdir')), ENT_QUOTES, 'UTF-8').'\'>
		<div class=\'collection-card-title\'>
			<a href=\''.htmlentities((string)LCRun3::v($cx, $in, array('collectionUrl')), ENT_QUOTES, 'UTF-8').'\'>'.htmlentities((string)LCRun3::v($cx, $in, array('title')), ENT_QUOTES, 'UTF-8').'</a>
		</div>
'.LCRun3::sec($cx, LCRun3::v($cx, $in, array('owner')), $in, false, function($cx, $in) {return '			<a
				class="'.htmlentities((string)LCRun3::v($cx, $in, array('class')), ENT_QUOTES, 'UTF-8').' collection-owner"
				href="'.htmlentities((string)LCRun3::v($cx, $in, array('link')), ENT_QUOTES, 'UTF-8').'">'.htmlentities((string)LCRun3::v($cx, $in, array('label')), ENT_QUOTES, 'UTF-8').'</a>
		<span>•</span>
';}).''.((LCRun3::isec($cx, LCRun3::v($cx, $in, array('owner')))) ? '		<span class=\'collection-card-following\'>'.htmlentities((string)LCRun3::v($cx, $in, array('privacyMsg')), ENT_QUOTES, 'UTF-8').'</span>
		<span>•</span>
' : '').'		<span class=\'collection-card-article-count\'>'.htmlentities((string)LCRun3::v($cx, $in, array('articleCountMsg')), ENT_QUOTES, 'UTF-8').'</span>
	</div>
</div>
';
}
?>