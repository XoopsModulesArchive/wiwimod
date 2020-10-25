<?php

require_once '../../../mainfile.php';
require_once XOOPS_ROOT_PATH . '/kernel/module.php';
require_once '../include/functions.php';
if (!defined('WIWI_NOCPFUNC')) {
    require_once XOOPS_ROOT_PATH . '/include/cp_functions.php';
}

// language files
if (file_exists('../language/' . $xoopsConfig['language'] . '/modinfo.php')) {
    require_once '../language/' . $xoopsConfig['language'] . '/modinfo.php';
} else {
    require_once '../language/english/modinfo.php';
}

if (file_exists('../language/' . $xoopsConfig['language'] . '/admin.php')) {
    require_once '../language/' . $xoopsConfig['language'] . '/admin.php';
} else {
    require_once '../language/english/admin.php';
}

if (file_exists('../language/' . $xoopsConfig['language'] . '/main.php')) {
    require_once '../language/' . $xoopsConfig['language'] . '/main.php';
} else {
    require_once '../language/english/main.php';
}

if ($xoopsUser) {
    $xoopsModule = XoopsModule::getByDirname('wiwimod');

    if (!$xoopsUser->isAdmin($xoopsModule->mid())) {
        redirect_header(XOOPS_URL . '/', 3, _NOPERM);

        exit();
    }
} else {
    redirect_header(XOOPS_URL . '/', 3, _NOPERM);

    exit();
}

$myts = MyTextSanitizer::getInstance();
