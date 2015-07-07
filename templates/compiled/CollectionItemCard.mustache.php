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
    
    return '<div class="collection-card">
'.LCRun3::sec($cx, LCRun3::v($cx, $in, array('cardImage')), $in, false, function($cx, $in) {return '	<a href="'.htmlentities((string)LCRun3::v($cx, $in, array('page','url')), ENT_QUOTES, 'UTF-8').'">'.LCRun3::v($cx, $in, array('cardImage')).'</a>
';}).'	<h2 class="collection-card-title" dir="'.htmlentities((string)LCRun3::v($cx, $in, array('dir')), ENT_QUOTES, 'UTF-8').'">
'.LCRun3::sec($cx, LCRun3::v($cx, $in, array('page')), $in, false, function($cx, $in) {return '		<a href="'.htmlentities((string)LCRun3::v($cx, $in, array('url')), ENT_QUOTES, 'UTF-8').'" class="'.LCRun3::sec($cx, LCRun3::v($cx, $in, array('isMissing')), $in, false, function($cx, $in) {return 'new';}).'">'.htmlentities((string)LCRun3::v($cx, $in, array('displayTitle')), ENT_QUOTES, 'UTF-8').'</a>
';}).'	</h2>
'.LCRun3::sec($cx, LCRun3::v($cx, $in, array('extract')), $in, false, function($cx, $in) {return '	<p class="collection-card-excerpt" dir="'.htmlentities((string)LCRun3::v($cx, $in, array('dir')), ENT_QUOTES, 'UTF-8').'">'.htmlentities((string)LCRun3::v($cx, $in, array('extract')), ENT_QUOTES, 'UTF-8').'</p>
';}).''.LCRun3::sec($cx, LCRun3::v($cx, $in, array('isMissing')), $in, false, function($cx, $in) {return '	<p class="collection-card-excerpt" dir="'.htmlentities((string)LCRun3::v($cx, $in, array('dir')), ENT_QUOTES, 'UTF-8').'">'.htmlentities((string)LCRun3::v($cx, $in, array('msgMissing')), ENT_QUOTES, 'UTF-8').'</p>
';}).''.((LCRun3::isec($cx, LCRun3::v($cx, $in, array('isMissing')))) ? '	<div class="collection-card-footer">
		<a href="'.htmlentities((string)LCRun3::v($cx, $in, array('page','url')), ENT_QUOTES, 'UTF-8').'" class="'.htmlentities((string)LCRun3::v($cx, $in, array('progressiveAnchorClass')), ENT_QUOTES, 'UTF-8').'">
			'.htmlentities((string)LCRun3::v($cx, $in, array('msg')), ENT_QUOTES, 'UTF-8').'<span class="'.htmlentities((string)LCRun3::v($cx, $in, array('iconClass')), ENT_QUOTES, 'UTF-8').'">
		</a>
	</div>
' : '').'</div>';
}
?>