<?php

require_once XOOPS_ROOT_PATH . '/modules/wiwimod/header.php';
require_once XOOPS_ROOT_PATH . '/modules/wiwimod/class/wiwiProfile.class.php';

function wiwimod_recent()
{
    global $xoopsDB;

    $block = [];

    $myts = MyTextSanitizer::getInstance();

    $sql = 'SELECT w1.keyword, w1.title, w1.lastmodified, w1.u_id, w1.prid FROM ' . $xoopsDB->prefix('wiwimod') . ' AS w1 LEFT JOIN ' . $xoopsDB->prefix('wiwimod') . ' AS w2 ON w1.keyword=w2.keyword AND w1.id<w2.id WHERE w2.id IS NULL ORDER BY w1.lastmodified DESC LIMIT 5';

    $result = $xoopsDB->query($sql);

    //Filter each entry according to its privilege

    $prf = new WiwiProfile();

    while (false !== ($content = $xoopsDB->fetchArray($result))) {
        $prf->load($content['prid']);

        if ($prf->canRead()) {
            $link = [];

            $link['page'] = $content['keyword'];

            $link['title'] = $content['title'];

            $link['lastmodified'] = date('d.m.y', strtotime($content['lastmodified']));

            $link['user'] = getUserName($content['u_id']);

            //		$link['user'] = $content["u_id"];

            $block['links'][] = $link;
        }
    }

    return $block;
}
