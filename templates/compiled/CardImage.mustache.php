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
    
    return '<div
  class=\'list-thumb
        '.LCRun3::sec($cx, LCRun3::v($cx, $in, array('wide')), $in, false, function($cx, $in) {return 'list-thumb-y';}).'
        '.((LCRun3::isec($cx, LCRun3::v($cx, $in, array('wide')))) ? 'list-thumb-x' : '').'\'
  style=\'background-image: url("'.htmlentities((string)LCRun3::v($cx, $in, array('url')), ENT_QUOTES, 'UTF-8').'")\'></div>
';
}
?>