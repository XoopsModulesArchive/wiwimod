<?php

require_once 'header.php';
require_once 'class/wiwiRevision.class.php';
require_once XOOPS_ROOT_PATH . '/class/template.php';
define('FPDF_FONTPATH', 'class/html2fpdf/font/');
require __DIR__ . '/class/html2fpdf/html2fpdf.php';

$page = $_GET['page'] ?? '';

function printPage($pageObj, $pdf)
{
    global $xoopsConfig;

    /*
     * initialize template system (copied from /header.php)
     */

    $xoopsTpl = new XoopsTpl();

    $xoopsTpl->xoops_setCaching(0);

    if (3 == $xoopsConfig['debug_mode']) {
        $xoopsTpl->xoops_setDebugging(true);
    }

    $xoopsTpl->assign(
        [
            'xoops_theme' => $xoopsConfig['theme_set'],
            'xoops_imageurl' => XOOPS_THEME_URL . '/' . $xoopsConfig['theme_set'] . '/',
            'xoops_themecss' => xoops_getcss($xoopsConfig['theme_set']),
            'xoops_requesturi' => htmlspecialchars($GLOBALS['xoopsRequestUri'], ENT_QUOTES),
            'xoops_sitename' => htmlspecialchars($xoopsConfig['sitename'], ENT_QUOTES),
            'xoops_slogan' => htmlspecialchars($xoopsConfig['slogan'], ENT_QUOTES),
        ]
    );

    // Meta tags

    $configHandler = xoops_getHandler('config');

    $criteria = new CriteriaCompo(new Criteria('conf_modid', 0));

    $criteria->add(new Criteria('conf_catid', XOOPS_CONF_METAFOOTER));

    $config = &$configHandler->getConfigs($criteria, true);

    foreach (array_keys($config) as $i) {
        // prefix each tag with 'xoops_'

        $xoopsTpl->assign('xoops_' . $config[$i]->getVar('conf_name'), $config[$i]->getConfValueForOutput());
    }

    //unset($config);

    /*
     * get content
     */

    $pagecontent = $pageObj->render();

    $xoopsTpl->assign(
        'wiwimod',
        [
            'keyword' => $pageObj->keyword,
            'title' => $pageObj->title,
            'body' => $pagecontent,
            'lastmodified' => date('d.m.y', strtotime($pageObj->lastmodified)),
            'author' => getUserName($pageObj->u_id),
        ]
    );

    ob_start();

    $xoopsTpl->xoops_setCaching(0);

    $xoopsTpl->display('db:wiwimod_pdf.html');

    $html = ob_get_contents();

    ob_end_clean();

    error_reporting(E_ALL & ~E_NOTICE);  // brrr, don't like that ..

    $pdf->AddPage();

    $pdf->WriteHTML($html);

    return true;
}

/*
 * Prints the current page, and all pages linked-to into a single pdf.
 */
function printPagesRecurr($page, &$pdf, $followLinks, &$retcode, &$printedPages)
{
    if (in_array($page, $printedPages, true)) {
        return true;  //-- prevent infinite loops ...
    }  

    $printedPages[] = $page;

    $pageObj = new wiwiRevision($page);

    if (0 == $pageObj->id) {
        $retcode = _MD_WIWI_NOPAGE_MSG;

        return true;                        // at least one page did'nt exist
    }

    if (!$pageObj->canRead()) {
        $retcode = _MD_WIWI_NOREADACCESS_MSG;

        return true;                        // at least one page had restricted access
    }

    if (!printPage($pageObj, $pdf)) {
        $retcode = _MD_WIWI_PDF_ERROR_MSG;

        return false;
    }

    $links = $pageObj->getLinks();

    foreach ($links as $lnk) {
        if ($lnk['isWiwiPage']) {
            printPagesRecurr($lnk['url'], $pdf, $followlinks, $retcode, $printedPages);
        }
    }

    return true;
}

function printPages($page, &$pdf, $followLinks, &$retcode)
{
    $printedPages = [];

    $retcode = '';

    return printPagesRecurr($page, $pdf, $followLinks, $retcode, $printedPages);
}

/*
 * Remove pdf files older than one hour in the server directory.
 * Supposely, one hour is enough to download the pdf.
 * Doing this avoids having to install a "cron" action on the server ;
 *
 * Note : real files must be created because IE does'nt handle correctly
 * direct pdf streams being sent to the navigator. So this is a workaround.
 * (see http://www.fpdf.org/ FAQ for more details)
 */
function cleanupDir($dir)
{
    $t = time();

    $h = opendir($dir);

    while ($file = readdir($h)) {
        if ('wiwitmp' == mb_substr($file, 0, 7) and '.pdf' == mb_substr($file, -4)) {
            $path = $dir . '/' . $file;

            if ($t - filemtime($path) > 3600) {
                @unlink($path);
            }
        }
    }

    closedir($h);
}

$pdf = new HTML2FPDF();
$msg = '';

if (!printPages($page, $pdf, true, $msg)) {
    redirect_header('index.php?page=' . $page, 2, $msg);

    exit();
}
// create a temp file
//$file=tempnam(XOOPS_ROOT_PATH.'/uploads','wiwitmp');
//rename($file,$file.'.pdf');
//$file.='.pdf';
//$pdf->Output($file);
$pdf->Output();

// cleanup old temp files (more than one hour old)
//cleanupDir(XOOPS_ROOT_PATH.'/uploads');

// redirect page to the newly created pdf.
//echo "<HTML><SCRIPT>document.location='".XOOPS_URL."/uploads/".basename($file)."';</SCRIPT></HTML>";
