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
    
    return '<div class="content-header">
	<ul class="button-bar mw-ui-button-group">
		'.LCRun3::sec($cx, LCRun3::v($cx, $in, array('tabs')), $in, false, function($cx, $in) {return '<li class="mw-ui-button '.LCRun3::sec($cx, LCRun3::v($cx, $in, array('isCurrentTab')), $in, false, function($cx, $in) {return 'mw-ui-progressive';}).'">
			<a href="'.htmlentities((string)LCRun3::v($cx, $in, array('href')), ENT_QUOTES, 'UTF-8').'">'.htmlentities((string)LCRun3::v($cx, $in, array('label')), ENT_QUOTES, 'UTF-8').'</a></li>';}).'
	</ul>
</div>';
}
?>