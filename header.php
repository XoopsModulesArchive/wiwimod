<?php

/*
 * Guess the path of Xoops, to include mainfile.
 */
if (!defined('XOOPS_URL')) {
    $scriptfilename = __FILE__;

    eregi('(.*)modules', $scriptfilename, $regs);

    include $regs[1] . 'mainfile.php';
}
$myts = MyTextSanitizer::getInstance();

require_once XOOPS_ROOT_PATH . '/class/xoopsformloader.php';
require_once XOOPS_ROOT_PATH . '/modules/wiwimod/include/functions.php';
