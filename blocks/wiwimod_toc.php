<?php

require_once XOOPS_ROOT_PATH . '/modules/wiwimod/header.php';
require_once XOOPS_ROOT_PATH . '/modules/wiwimod/class/wiwiProfile.class.php';

function wiwimod_toc()
{
    global $xoopsDB, $xoopsUser;

    $block = [];

    $myts = MyTextSanitizer::getInstance();

    /*
        $sql = "SELECT w1.keyword, w1.title, w1.visible FROM ".$xoopsDB->prefix("wiwimod")." AS w1 LEFT JOIN ".$xoopsDB->prefix("wiwimod")." AS w2 ON w1.keyword=w2.keyword AND w1.id<w2.id WHERE w2.id IS NULL AND w1.visible>0 ORDER BY w1.visible";
        $result = $xoopsDB->query($sql);
    */

    // Select also the "prid" (privilege Id)

    $sql = 'SELECT w1.keyword, w1.title, w1.visible, w1.prid FROM ' . $xoopsDB->prefix('wiwimod') . ' AS w1 LEFT JOIN ' . $xoopsDB->prefix('wiwimod') . ' AS w2 ON w1.keyword=w2.keyword AND w1.id<w2.id WHERE w2.id IS NULL AND w1.visible>0 ORDER BY w1.visible ';

    $result = $xoopsDB->query($sql);

    //Filter each entry according to its privilege

    $prf = new WiwiProfile();

    while (false !== ($tcontent = $xoopsDB->fetchArray($result))) {
        $prf->load($tcontent['prid']);

        if ($prf->canRead()) {
            $link = [];

            $link['page'] = $tcontent['keyword'];

            $link['title'] = htmlspecialchars($tcontent['title'], ENT_QUOTES | ENT_HTML5);

            $block['links'][] = $link;
        }
    }

    //	$result = $xoopsDB->query("SELECT DISTINCT keyword, title, visible FROM ".$xoopsDB->prefix("wiwimod")." WHERE visible>0 ORDER BY visible");

    /*
        while($tcontent = $xoopsDB->fetchArray($result)) {
          $link = array();
          $link['page'] = $tcontent['keyword'];
          $link['title'] = htmlspecialchars($tcontent['title']);
          $block['links'][] = $link;
        }

    */

    return $block;
}
