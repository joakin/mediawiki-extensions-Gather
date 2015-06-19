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
    
    return '
<div class=\'collections-list content view-border-box\' data-owner="'.htmlentities((string)LCRun3::v($cx, $in, array('owner')), ENT_QUOTES, 'UTF-8').'"
  data-is-owner=\''.LCRun3::v($cx, $in, array('isOwner')).'\' data-mode=\''.htmlentities((string)LCRun3::v($cx, $in, array('mode')), ENT_QUOTES, 'UTF-8').'\'>
  <div class=\'collection-cards\'>
    '.LCRun3::v($cx, $in, array('items')).'
  </div>
  <div class=\'collection-actions\'></div>
</div>
';
}
?>