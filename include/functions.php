<?php

function getUserName($uid)
{
    global $myts, $xoopsConfig;

    if (!isset($myts)) {
        $myts = MyTextSanitizer::getInstance();
    }

    $uid = (int)$uid;

    if ($uid > 0) {
        $memberHandler = xoops_getHandler('member');

        $user = $memberHandler->getUser($uid);

        if (is_object($user)) {
            return '<a href="' . XOOPS_URL . "/userinfo.php?uid=$uid\">" . htmlspecialchars($user->getVar('uname'), ENT_QUOTES | ENT_HTML5) . '</a>';
        }
    }

    return $xoopsConfig['anonymous'];
}

//ok >> rename to ??? , and check block access rights for current user.
function wiwimod_getXoopsBlock($blkname)
{  // block title or id
    global $xoopsUser;

    global $xoopsDB;

    $block = [];

    $bcontent = '';

    $bid = (int)$blkname;

    //

    // check block to show

    //

    $blk = new XoopsBlock();

    if (0 == $bid) {
        $blklst = $blk->getAllBlocks();

        foreach ($blklst as $b) {
            if (0 == strcasecmp($b->getVar('title'), $blkname)) {
                $bid = $b->getVar('bid');

                break;
            }
        }
    }

    //

    // build block and extract content

    //

    if ($bid > 0) {
        $blk->load($bid);

        $btpl = $blk->getVar('template');

        $bid = $blk->getVar('bid');

        $bresult = $blk->buildBlock();

        if ($bresult) {
            require_once XOOPS_ROOT_PATH . '/class/template.php';

            $xoopsTpl = new XoopsTpl();

            $xoopsTpl->xoops_setCaching(2);

            if ('' != $btpl) {
                $xoopsTpl->assign_by_ref('block', $bresult);

                $bcontent = $xoopsTpl->fetch('db:' . $btpl);

                $xoopsTpl->clear_assign('block');
            } else {
                $xoopsTpl->assign_by_ref('dummy_content', $bresult['content']);

                $bcontent = $xoopsTpl->fetch('db:system_dummy.html', 'blk_' . $bid);

                $xoopsTpl->clear_assign('dummy_content');
            }
        }
    }

    $block['content'] = $bcontent;

    return $block;
}

//ok >> rename to render_block
function wiwiShowBlock($blkname)
{
    $blk = wiwimod_getXoopsBlock($blkname);

    return '<table><tr><td>' . $blk['content'] . '</TD></TR></TABLE>';
}

/*
 * code adapted from the excellent SmartFaq module (www.smartfactory.ca)
 */
function w_adminMenu($currentoption = 0, $breadcrumb = '')
{
    echo getAdminMenu($currentoption, $breadcrumb);
}

/*
 * code adapted from the excellent SmartFaq module (www.smartfactory.ca)
 */
function getAdminMenu($currentoption = 0, $breadcrumb = '')
{
    $html = '';

    /* Nice buttons styles */

    $html .= "
    	<style type='text/css'>
    	#buttontop { float:left; width:100%; background: #e7e7e7; font-size:93%; line-height:normal; border-top: 1px solid black; border-left: 1px solid black; border-right: 1px solid black; margin: 0; }
    	#buttonbar { float:left; width:100%; background: #e7e7e7 url('" . XOOPS_URL . "/modules/wiwimod/images/bg.gif') repeat-x left bottom; font-size:93%; line-height:normal; border-left: 1px solid black; border-right: 1px solid black; margin-bottom: 12px; }
    	#buttonbar ul { margin:0; margin-top: 15px; padding:10px 10px 0; list-style:none; }
		#buttonbar li { display:inline; margin:0; padding:0; }
		#buttonbar a { float:left; background:url('" . XOOPS_URL . "/modules/wiwimod/images/left_both.gif') no-repeat left top; margin:0; padding:0 0 0 9px; border-bottom:1px solid #000; text-decoration:none; }
		#buttonbar a span { float:left; display:block; background:url('" . XOOPS_URL . "/modules/wiwimod/images/right_both.gif') no-repeat right top; padding:5px 15px 4px 6px; font-weight:bold; color:#765; }
		/* Commented Backslash Hack hides rule from IE5-Mac \*/
		#buttonbar a span {float:none;}
		/* End IE5-Mac hack */
		#buttonbar a:hover span { color:#333; }
		#buttonbar #current a { background-position:0 -150px; border-width:0; }
		#buttonbar #current a span { background-position:100% -150px; padding-bottom:5px; color:#333; }
		#buttonbar a:hover { background-position:0% -150px; }
		#buttonbar a:hover span { background-position:100% -150px; }
		</style>
    ";

    // global $xoopsDB, $xoopsModule, $xoopsConfig, $xoopsModuleConfig;

    global $xoopsModule, $xoopsConfig;

    $myts = MyTextSanitizer::getInstance();

    $tblColors = [];

    $tblColors[0] = $tblColors[1] = $tblColors[2] = $tblColors[3] = $tblColors[4] = $tblColors[5] = '';

    $tblColors[$currentoption] = 'current';

    if (file_exists(XOOPS_ROOT_PATH . '/modules/wiwimod/language/' . $xoopsConfig['language'] . '/modinfo.php')) {
        require_once XOOPS_ROOT_PATH . '/modules/wiwimod/language/' . $xoopsConfig['language'] . '/modinfo.php';
    } else {
        require_once XOOPS_ROOT_PATH . '/modules/wiwimod/language/english/modinfo.php';
    }

    $html .= "<div id='buttontop'>";

    $html .= '<table style="width: 100%; padding: 0; " cellspacing="0"><tr>';

    $html .= "<td style='width: 60%; font-size: 14px; font-weight:bolder; text-align: left; color: #2F5376; padding: 0 6px; line-height: 18px;'>" . _MI_WIWIMOD_NAME . ' - ' . _MI_WIWIMOD_DESC . '</td>';

    $html .= "<td style='width: 40%; font-size: 10px; text-align: right; color: #2F5376; padding: 0 6px; line-height: 18px;'>" . _AM_WIWI_ADMIN_TXT . ' : ' . $xoopsModule->name() . ' : ' . $breadcrumb . '</td>';

    $html .= '</tr></table>';

    $html .= '</div>';

    $html .= "<div id='buttonbar'>";

    $html .= '<ul>';

    $html .= "<li id='" . $tblColors[0] . "'><a href=\"" . XOOPS_URL . '/modules/wiwimod/admin/index.php"><span>' . _MI_WIWIMOD_ADMENU1 . '</span></a></li>';

    $html .= "<li id='" . $tblColors[1] . "'><a href=\"" . XOOPS_URL . '/modules/wiwimod/admin/acladmin.php"><span>' . _MI_WIWIMOD_ADMENU2 . '</span></a></li>';

    $html .= "<li id='" . $tblColors[2] . "'><a href=\"" . XOOPS_URL . '/modules/wiwimod/admin/preferences.php"><span>' . _PREFERENCES . '</span></a></li>';

    $html .= "<li id='" . $tblColors[3] . "'><a href=\"" . XOOPS_URL . '/modules/wiwimod/admin/myblocksadmin.php"><span>' . _MI_WIWIMOD_ADMENU3 . '</span></a></li>';

    $html .= "<li id='" . $tblColors[4] . "'><a href=\"" . XOOPS_URL . '/modules/wiwimod/admin/about.php"><span>' . _MI_WIWIMOD_ADMENU4 . '</span></a></li>';

    $html .= '</ul></div>&nbsp;';

    return $html;
}
