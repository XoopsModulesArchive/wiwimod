<?php

require_once 'header.php';
require_once 'class/wiwiRevision.class.php';

/*
 * extract all header variables to corresponding php variables ---
 * TODO : - $xoopsUser can be overriden by post variables >> security fix ?
 */
$page = $_REQUEST['page'] ?? '';
if (isset($_REQUEST['pageid'])) {
    $pageid = (int)$_REQUEST['pageid'];
} else {
    $pageid = 0;
}
if (isset($_REQUEST['id'])) {
    $id = (int)$_REQUEST['id'];
} else {
    $id = 0;
}
$op = $_GET['op'] ?? '';
if (!empty($_POST)) {
    extract($_POST);
}

$page = stripslashes($page);  // if page name comes in url, decode it.

//
//-- Retrieve page data
//
if ((('preview' == $op) || ('insert' == $op) || ('quietsave' == $op)) && isset($id)) {
    /*
     * data comes from post variables
     */

    $pageObj = new wiwiRevision();

    $pageObj->keyword = $page;

    $pageObj->title = $title;

    $pageObj->body = $body;

    $pageObj->lastmodified = $lastmodified;

    $pageObj->u_id = $uid;

    $pageObj->parent = $pageObj->normalize($parent);

    $pageObj->visible = (int)$visible;

    $pageObj->contextBlock = $pageObj->normalize($contextBlock);

    $pageObj->pageid = (int)$pageid;

    $pageObj->profile = new wiwiProfile((int)$prid);

    $pageObj->id = (int)$id;
} else {
    /*
     * data is read from database
     */

    $pageObj = new wiwiRevision($page, 0, $pageid);

    if (0 == $pageObj->id) {
        /*
         * page does'nt exist >> edit new one, with default values for title and profile
         */

        $op = 'edit';

        $pageObj->title = $pageObj->keyword;

        if (isset($_GET['back'])) {
            $pageObj->parent = stripslashes($_GET['back']);    // default value for parent field = initial caller.

            $parentObj = new wiwiRevision($pageObj->parent);

            $pageObj->profile = &$parentObj->profile;   // is reference assignment a good idea ?
        }
    }
}

//
// process required action
//
switch ($op) {
    case 'insert':
    case 'quietsave':
        /*
         *  save page modifications and redirect
         */
        if ($pageObj->concurrentlySaved()) {
            redirect_header('index.php?page=' . $pageObj->keyword, 2, _MD_WIWI_EDITCONFLICT_MSG);
        } elseif (!$pageObj->canWrite()) {
            redirect_header('index.php?page=' . $pageObj->keyword, 2, _MD_WIWI_NOWRITEACCESS_MSG);
        } else {
            $success = ('insert' == $op) ? $pageObj->add() : $pageObj->save();

            redirect_header('index.php?page=' . $pageObj->keyword, 2, ($success) ? _MD_WIWI_DBUPDATED_MSG : _MD_WIWI_ERRORINSERT_MSG);
        }
        exit();
        break;
    case 'edit':
    case 'preview':
        //
        //  show page in editor (after privileges check)
        //
        if (!$pageObj->canWrite()) {
            require_once XOOPS_ROOT_PATH . '/header.php';

            echo "<br><br><center><table style='align:center; border: 1px solid gray; width:50%; background:#F0F0F0'; ><tr><td align=center><br>" . _MD_WIWI_PAGENOTFOUND_MSG . "<br><br></td></tr></table><br><br><input type='button' value=" . _CANCEL . " onclick='history.back();'></center>";

            require_once XOOPS_ROOT_PATH . '/footer.php';

            break;
        }
        /*
         * privileges ok -> proceed.
         */
        $GLOBALS['xoopsOption']['template_main'] = 'wiwimod_edit.html';
        require_once XOOPS_ROOT_PATH . '/header.php';

        if ('preview' == $op) {
            /*
             * Note : content came through "post" >> Strip eventual slashes (depending on the magic_quotes_gpc() value)
             */

            $pageObj->title = $myts->stripSlashesGPC($pageObj->title);

            $pageObj->body = $myts->stripSlashesGPC($pageObj->body);

            $xoopsTpl->assign(
                'wiwimod',
                [
                    'keyword' => $pageObj->keyword,
                    'title' => $pageObj->title,
                    'body' => $pageObj->render(),
                ]
            );
        }

        /*
         * Build form
         */
        $form = new XoopsThemeForm(_MD_WIWI_EDIT_TXT . ": $page", 'wiwimodform', 'index.php');
        $btn_tray = new XoopsFormElementTray('', ' ');

        $form->addElement(new XoopsFormHidden('op', 'insert'));
        $form->addElement(new XoopsFormHidden('page', htmlspecialchars($pageObj->keyword, ENT_QUOTES | ENT_HTML5)));
        $form->addElement(new XoopsFormHidden('pageid', $pageObj->pageid));
        $form->addElement(new XoopsFormHidden('id', $pageObj->id));
        $form->addElement(new XoopsFormHidden('uid', ($xoopsUser) ? $xoopsUser->getVar('uid') : 0));
        $form->addElement(new XoopsFormHidden('lastmodified', $pageObj->lastmodified));

        $form->addElement(new XoopsFormText(_MD_WIWI_TITLE_FLD, 'title', 80, 250, htmlspecialchars($pageObj->title, ENT_QUOTES | ENT_HTML5)));

        switch ($xoopsModuleConfig['Editor']) {
            case 0: // standard xoops
                $t_area = new XoopsFormDhtmlTextArea(_MD_WIWI_BODY_FLD, 'body', $pageObj->body, '30', '70');

                break;
            case 1: // XoopsEditor
                require_once XOOPS_ROOT_PATH . '/class/xoopseditor/xoopseditor.php';
                $editorhandler = new XoopsEditorHandler();
                $editor_name = $xoopsModuleConfig['XoopsEditor'];

                $options['caption'] = _MD_WIWI_BODY_FLD;
                $options['name'] = 'body';
                $options['value'] = $pageObj->body;
                $options['rows'] = 25;
                $options['cols'] = 60;
                $options['width'] = '100%';
                $options['height'] = '400px';
                $t_area = $editorhandler->get($editor_name, $options, 'textarea');
                if ($t_area) {
                    $editorhandler->setConfig(
                        $t_area,
                        [
                            'filepath' => XOOPS_UPLOAD_PATH . '/' . $xoopsModule->getVar('dirname'),
                            'upload' => true,
                            'extensions' => ['txt', 'jpg', 'zip'],
                        ]
                    );
                }
                break;
            case 2: // Spaw class
                require XOOPS_ROOT_PATH . '/class/spaw/formspaw.php';
                $t_area = new XoopsFormSpaw(_MD_WIWI_BODY_FLD, 'body', $pageObj->body, '100%', '400px');
                break;
            default:
            case 3: // HTMLArea class
                require XOOPS_ROOT_PATH . '/class/htmlarea/formhtmlarea.php';
                $t_area = new XoopsFormHtmlarea(_MD_WIWI_BODY_FLD, 'body', $pageObj->body, '100%', '400px');
                break;
            case 4: // Koivi
                require XOOPS_ROOT_PATH . '/class/wysiwyg/formwysiwygtextarea.php';
                $t_area = new XoopsFormWysiwygTextArea(_MD_WIWI_BODY_FLD, 'body', $pageObj->body, '100%', '400px', '');
                break;
            case 5: // FCK class
                require XOOPS_ROOT_PATH . '/class/fckeditor/formfckeditor.php';
                $t_area = new XoopsFormFckeditor(_MD_WIWI_BODY_FLD, 'body', $pageObj->body, '100%', '400px');
                break;
        }
        $form->addElement($t_area);

        $form->addElement(new XoopsFormText(_MD_WIWI_PARENT_FLD, 'parent', 15, 100, htmlspecialchars($pageObj->parent, ENT_QUOTES | ENT_HTML5)));

        if ($pageObj->canAdministrate()) {
            $prflst = $pageObj->profile->getAdminProfiles($xoopsUser);

            $prfsel = new XoopsFormSelect(_MD_WIWI_PROFILE_FLD, 'prid', $pageObj->profile->prid);

            $prfsel->addOptionArray($prflst);

            $form->addElement($prfsel);
        } else {
            $form->addElement(new XoopsFormLabel(_MD_WIWI_PROFILE_FLD, $pageObj->profile->name));

            $form->addElement(new XoopsFormHidden('prid', $pageObj->profile->prid));
        }

        $form->addElement(new XoopsFormText(_MD_WIWI_VISIBLE_FLD, 'visible', 3, 3, $pageObj->visible));
        $form->addElement(new XoopsFormText(_MD_WIWI_CONTEXTBLOCK_FLD, 'contextBlock', 15, 100, htmlspecialchars($pageObj->contextBlock, ENT_QUOTES | ENT_HTML5)));

        $preview_btn = new XoopsFormButton('', 'preview', _PREVIEW, 'button');
        $preview_btn->setExtra("onclick='document.forms.wiwimodform.op.value=\"preview\"; document.forms.wiwimodform.submit.click();'");
        $btn_tray->addElement($preview_btn);

        $btn_tray->addElement(new XoopsFormButton('', 'submit', _MD_WIWI_SUBMITREVISION_BTN, 'submit'));

        if ($pageObj->id > 0) {
            $quietsave_btn = new XoopsFormButton('', 'quietsave', _MD_WIWI_QUIETSAVE_BTN, 'button');

            $quietsave_btn->setExtra("onclick='document.forms.wiwimodform.op.value=\"quietsave\"; document.forms.wiwimodform.submit.click();'");

            $btn_tray->addElement($quietsave_btn);
        }

        $cancel_btn = new XoopsFormButton('', 'cancel', _CANCEL, 'button');
        $cancel_btn->setExtra(('edit' == $op) ? "onclick='history.back();'" : "onclick='document.location.href=\"index.php" . ((0 != $pageObj->id) ? '?page=' . $pageObj->keyword : '') . "\"'");
        $btn_tray->addElement($cancel_btn);
        $form->addElement($btn_tray);
        $form->assign($xoopsTpl);
        break;
    case 'history':
    case 'diff':
        /*
         *  show page history
         */
        $GLOBALS['xoopsOption']['template_main'] = 'wiwimod_history.html';
        require_once XOOPS_ROOT_PATH . '/header.php';

        $pageObj = new wiwiRevision($page, ($id ?? 0));
        if ('history' == $op) {
            $xoopsTpl->assign(
                'wiwimod',
                [
                    'keyword' => $pageObj->keyword,
                    'revid' => $pageObj->id,
                    'title' => $pageObj->title,
                    'body' => $pageObj->render(),
                ]
            );
        } else {
            $pageObj->diff($bodyDiff, $titleDiff);

            $xoopsTpl->assign(
                'wiwimod',
                [
                    'keyword' => $pageObj->keyword,
                    'revid' => $pageObj->id,
                    'title' => $titleDiff,
                    'body' => $bodyDiff,
                ]
            );
        }

        $hist = $pageObj->history();
        foreach ($hist as $key => $value) {
            $hist[$key]['username'] = getUserName($hist[$key]['u_id']);
        }

        $xoopsTpl->assign('hist', $hist);
        $xoopsTpl->assign('allowRestore', $pageObj->canAdministrate());
        break;
    case 'restore':
        //
        // Creates a new revision whom content is copied from the selected one, but with other data (parent, privileges etc..) untouched.
        //
        $restoredRevision = new wiwiRevision('', $id);
        $pageObj->title = addslashes($restoredRevision->title);
        $pageObj->body = addslashes($restoredRevision->body);
        $pageObj->contextBlock = $restoredRevision->contextBlock;
        $success = $pageObj->add();
        redirect_header('index.php?page=' . $pageObj->keyword . '&op=history', 2, ($success) ? _MD_WIWI_DBUPDATED_MSG : _MD_WIWI_ERRORINSERT_MSG);
        break;
    default:
        //
        //  show page content (after privileges check)
        //
        $GLOBALS['xoopsOption']['template_main'] = 'wiwimod_view.html';
        require_once XOOPS_ROOT_PATH . '/header.php';

        if ($pageObj->canRead()) {
            $pagecontent = $pageObj->render();
        } else {
            $pagecontent = "<center><table style='align:center; border: 3px solid red; width:50%; background:#F0F0F0'; ><tr><td align=center>" . _MD_WIWI_NOREADACCESS_MSG . '</td></tr></table></center><br><br>';
        }

        //
        // Handle pagebreaks
        //
        $cpages = explode('[pagebreak]', $pagecontent);
        if (isset($_GET['startpage'])) {
            $startpage = (int)$_GET['startpage'];
        } else {
            $startpage = 0;
        }
        if (count($cpages) > 0) {
            require_once XOOPS_ROOT_PATH . '/class/pagenav.php';

            $pagenav = new XoopsPageNav(count($cpages), 1, $startpage, 'startpage', 'page=' . $pageObj->keyword);

            $xoopsTpl->assign(
                'nav',
                [
                    'startpage' => $startpage,
                    'html' => $pagenav->RenderNav(),
                ]
            );

            $pagecontent = $cpages[$startpage];
        }

        $xoopsTpl->assign(
            'wiwimod',
            [
                'keyword' => $pageObj->keyword,
                'encodedurl' => $pageObj->encode($pageObj->keyword),
                'title' => $pageObj->title,
                'body' => $pagecontent,
                'lastmodified' => date('d.m.y', strtotime($pageObj->lastmodified)),
                'author' => getUserName($pageObj->u_id),
                'mayEdit' => $pageObj->canWrite(),
                'showComments' => $pageObj->canViewComments() && (0 != $xoopsModuleConfig['com_rule']),
                'showHistory' => $pageObj->canViewHistory(),
                'allowPDF' => $xoopsModuleConfig['allowPDF'],
            ]
        );

        $xoopsTpl->assign('parentlist', $pageObj->parentList());

        $pageid = $pageObj->pageid;
        if ($pageObj->canViewComments()) {
            /*
             * set header variables for comment system to operate
             */

            if (!isset($_GET['pageid']) || !isset($_GET['pageid'])) {
                $_GET['pageid'] = $pageid;

                $_GET['pageid'] = $pageid;  // patch to be compatible with Xoops 2.0.7
            }

            //

            // patch to deal with a bug in the standard Xoops 2.05 comment_view file,

            // (generated a disgraceful "undefined index notice" in debug mode ;-)

            //

            if (!isset($_GET['com_order']) || !isset($_GET['com_order'])) {
                $_GET['com_order'] = (is_object($xoopsUser) ? $xoopsUser->getVar('uorder') : $xoopsConfig['com_order']);

                $_GET['com_order'] = $_GET['com_order'];  // patch to be compatible with Xoops 2.0.7
            }

            require XOOPS_ROOT_PATH . '/include/comment_view.php';
        }
        break;
}

$xoopsTpl->assign('xoops_pagetitle', htmlspecialchars($xoopsModule->name(), ENT_QUOTES | ENT_HTML5) . ' - ' . htmlspecialchars($pageObj->title, ENT_QUOTES | ENT_HTML5));
require XOOPS_ROOT_PATH . '/footer.php';
