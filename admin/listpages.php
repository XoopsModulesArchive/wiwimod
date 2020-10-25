<?php

/*
 * get history related variables
 */
$post_selwhere = $_POST['post_selwhere'] ?? '';
$post_text = $_POST['post_text'] ?? '';
$post_profile = (isset($_POST['post_profile'])) ? (int)$_POST['post_profile'] : 0;
$post_selorderby = $_POST['post_selorderby'] ?? 'keyword';
$post_selorderdir = $_POST['post_selorderdir'] ?? 'ASC';
$startlist = isset($_GET['startlist']) ? (int)$_GET['startlist'] : 0;

$pgitemsnum = 15;  // numbre of items per result page.

/*
 * Query form
 */
$selWhere = [
    '' => ['desc' => _AM_WIWI_LISTPAGES_ALLPAGES_OPT, 'type' => 'none'],
    'keyword' => ['desc' => _AM_WIWI_LISTPAGES_KEYWORD_OPT, 'type' => 'text'],
    'title' => ['desc' => _AM_WIWI_LISTPAGES_TITLE_OPT, 'type' => 'text'],
    'body' => ['desc' => _AM_WIWI_LISTPAGES_BODY_OPT, 'type' => 'text'],
    //			"u_id"			=> array( 'desc'=> _AM_WIWI_LISTPAGES_UID_OPT ,	'type' =>"user"),
    'parent' => ['desc' => _AM_WIWI_LISTPAGES_PARENT_OPT, 'type' => 'text'],
    'prid' => ['desc' => _AM_WIWI_LISTPAGES_PRID_OPT, 'type' => 'profile'],
    //			"lastmodified"	=> array( 'desc'=> _AM_WIWI_LISTPAGES_LASTMODIFIED_OPT,	'type' =>"date"),
];
$selOrder = [
    ['desc' => _AM_WIWI_LISTPAGES_KEYWORD_OPT, 'col' => 'keyword'],
    ['desc' => _AM_WIWI_LISTPAGES_TITLE_OPT, 'col' => 'title'],
    ['desc' => _AM_WIWI_LISTPAGES_PARENT_OPT, 'col' => 'parent'],
    ['desc' => _AM_WIWI_LISTPAGES_LASTMODIFIED_OPT, 'col' => 'lastmodified'],
];

$selOrderDir = [
    ['desc' => _AM_WIWI_LISTPAGES_ORDERASC_OPT, 'col' => 'ASC'],
    ['desc' => _AM_WIWI_LISTPAGES_ORDERDESC_OPT, 'col' => 'DESC'],
];

//	echo "<fieldset><legend style='font-weight: bold; color: #900;'>Wiwi Pages</legend>";

echo '<script>function showSelectOperands(ele) {';
echo 'var ctltype = "none";';
echo 'var ctl_text = document.getElementById("post_text");';
echo 'var ctl_profile = document.getElementById("post_profile");';
foreach ($selWhere as $key => $sel) {
    echo 'if (ele.value == "' . $key . '") ctltype = "' . $sel['type'] . '";';
}
echo 'ctl_text.style.display = (ctltype == "text") ? "" : "none";';
echo 'ctl_profile.style.display = (ctltype == "profile") ? "" : "none";';

echo '}</script>';
echo '<br><br>' . _AM_WIWI_PAGESFILTER_TXT . '&nbsp;<select name="post_selwhere" onchange="showSelectOperands(this);">';
foreach ($selWhere as $key => $sel) {
    echo '<option value="' . $key . '" ' . ($key == $post_selwhere ? 'selected ' : '') . '>' . $sel['desc'] . '</option>';
}
echo '</select>&nbsp;';
echo '<span id="post_text" style="display:' . ('text' == $selWhere[$post_selwhere]['type'] ? '' : 'none') . '">&nbsp;' . _AM_WIWI_LIKE_TXT . '&nbsp;<input name="post_text" type="text" value="' . $post_text . '" size=10>&nbsp;</span>';
echo '<span id="post_profile" style="display:' . ('profile' == $selWhere[$post_selwhere]['type'] ? '' : 'none') . '">&nbsp;' . _AM_WIWI_PROFILEIS_TXT . '&nbsp;<select name="post_profile">';
$prf = new WiwiProfile();
$prflist = $prf->getAllProfiles();
//		echo '<option value=0'.(0 == $post_profile ? " SELECTED" : "").'>(no profile)</option>';
foreach ($prflist as $key => $value) {
    echo '<option value=' . $key . ($key == $post_profile ? ' SELECTED' : '') . '>' . $value . '</option>';
}
echo '</select>&nbsp;</span>';

echo '&nbsp;' . _AM_WIWI_ORDERBY_TXT . '&nbsp;<select name="post_selorderby">';
foreach ($selOrder as $sel) {
    echo '<option value="' . $sel['col'] . '" ' . ($sel['col'] == $post_selorderby ? 'selected ' : '') . '>' . $sel['desc'] . '</option>';
}
echo '</select>&nbsp;';

echo '<select name="post_selorderdir">';
foreach ($selOrderDir as $sel) {
    echo '<option value="' . $sel['col'] . '" ' . ($sel['col'] == $post_selorderdir ? 'selected ' : '') . '>' . $sel['desc'] . '</option>';
}
echo '</select>&nbsp;';

echo '&nbsp;<input type=button value="go" onclick="javascript:submitaction(\'op=listpages\');"><br>';

/*
 * Results
 */

switch ($selWhere[$post_selwhere]['type']) {
    case 'text':
        $wherexpr = ' lower(' . $post_selwhere . ") LIKE '%" . $post_text . "%' ";
        break;
    case 'profile':
        $wherexpr = $post_selwhere . ' = ' . $post_profile . ' ';
        break;
    default:
        $wherexpr = '';
        break;
}
$pageObj  = new WiwiRevision();
$pageArr  = $pageObj->getPages($wherexpr, $post_selorderby . ' ' . $post_selorderdir, $pgitemsnum, $startlist);
$maxcount = $pageObj->getPagesNum($wherexpr, $post_selorderby . ' ' . $post_selorderdir);

echo '<table border="0" cellpadding="0" cellspacing="1" width="100%" class="outer">';
echo '<tr class="head"><td width="20%"><b>' . _MD_WIWI_KEYWORD_COL . '</b></td><td><b>' . _MD_WIWI_TITLE_COL . '</b></td><td width=10%><b>' . _MD_WIWI_MODIFIED_COL . '</b></td><td width="30%"><b>' . _MD_WIWI_ACTION_COL . '</b></td></tr>';

for ($i = 0, $iMax = count($pageArr); $i < $iMax; $i++) {
    $encodedKeyword = $pageObj->encode($pageArr[$i]->keyword);

    echo '<tr class="' . (($i % 2) ? 'even' : 'odd') . '"><td><a href="#" onclick="submitaction(\'op=history&page=' . $encodedKeyword . '\');">' . $pageArr[$i]->keyword . '</a></td>
		<td>' . htmlspecialchars($pageArr[$i]->title, ENT_QUOTES | ENT_HTML5) . '</td>
		<td>' . date('d.m.y', @strtotime($pageArr[$i]->lastmodified)) . '</td>
		<td><a href="#" onclick="submitaction(\'op=history&page=' . $encodedKeyword . '\');">' . _MD_WIWI_HISTORY_BTN . '</a> | <a href="javascript:submitaction(\'op=delete&page=' . urlencode($encodedKeyword) . '\');">' . _DELETE . '</a></td></tr>';
}
echo '</table></br>';
echo '<input type="hidden" name="startlist" value="' . $startlist . '">';
$pagenav = new wiwiPageNav($maxcount, $pgitemsnum, $startlist, 'startlist', '', 'submitaction');
echo '<table width=100%><tr><td width=15%>(' . $maxcount . ' ' . _AM_WIWI_LISTPAGES_RESULTS_TXT . ')</td><td><center>' . $pagenav->renderNav() . '</center></td></tr></table>';
echo '<hr>';

//	echo "</fieldset>";
