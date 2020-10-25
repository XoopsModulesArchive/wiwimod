<?php

/*	=========================================

        Class wiwiRevision

    =========================================
 */
if (!defined('_WIWIPAGE')) {
    define('_WIWIPAGE', 1);

    require_once 'wiwiProfile.class.php';

    require_once XOOPS_ROOT_PATH . '/modules/wiwimod/include/functions.php';

    require_once XOOPS_ROOT_PATH . '/modules/wiwimod/include/diff.php';

    class WiwiRevision
    {
        public $keyword;            // the CamelCase code of the page

        public $title;

        public $body;

        public $lastmodified;

        public $u_id;                // user id of last author
        public $parent;            // CamelCase code of the parent page
        public $visible;            // weight of the reviison in TOC block
        public $contextBlock;        // CamelCase code of the page to be shwon in the side block
        public $pageid;            // id of the initial revision (necessary to keep comments visible)
        public $profile;            // the current revision profile (@link WiwiProfile)
        public $id;                // revision unique id.
        public $db;                // private usage;
        public $ts;                // private usage;
        public $wiwiConfig;        // private usage : used to get Wiwimod configs, even when called from other modules

        /*
         * Constructor.
         * Loads revision from database :
         *    - loads requested revision if $id is provided ;
         *    - loads latest revision of page if $id isn't provided
         *		Note : in this case, $page can be either the page CamelCase keyword,
         *             or the corresponding pageid field (which both are common to
         *             all page revisions.
         */

        public function __construct($page = '', $id = 0, $pageid = 0)
        {
            $this->db = XoopsDatabaseFactory::getDatabaseConnection();

            $this->ts = MyTextSanitizer::getInstance();

            $moduleHandler = xoops_getHandler('module');

            $configHandler = xoops_getHandler('config');

            $wiwiMod = $moduleHandler->getByDirname('wiwimod');

            $this->wiwiConfig = $configHandler->getConfigsByCat(0, $wiwiMod->getVar('mid'));

            if (('' == $page) && (0 == $id) && (0 == $pageid)) {
                $page = _MI_WIWIMOD_WIWIHOME;
            }

            $this->keyword = $page;

            $this->title = '';

            $this->body = '';

            $this->lastmodified = null;

            $this->u_id = 0;

            $this->parent = '';

            $this->visible = 0;

            $this->contextBlock = '';

            $this->pageid = $pageid;

            $this->profile = new wiwiProfile(); // loads an empty profile

            $this->id = 0;

            if (0 != $id) {
                $sql = 'SELECT * FROM ' . $this->db->prefix('wiwimod') . " WHERE id = $id";
            } elseif ('' != $page) {
                $sql = 'SELECT * FROM ' . $this->db->prefix('wiwimod') . " WHERE keyword='" . addslashes($page) . "' ORDER BY id DESC LIMIT 1";
            } elseif ('' != $pageid) {
                $sql = 'SELECT * FROM ' . $this->db->prefix('wiwimod') . " WHERE pageid=$pageid ORDER BY id DESC LIMIT 1";
            } else {
                $sql = '';
            }

            if ('' != $sql) {
                $result = $this->db->query($sql);

                if (0 == $this->db->getRowsNum($result)) {
                    return false;
                }

                $row = $this->db->fetchArray($result);

                $this->keyword = $row['keyword'];

                $this->title = $row['title'];

                $this->body = $row['body'];

                $this->lastmodified = $row['lastmodified'];

                $this->u_id = $row['u_id'];

                $this->parent = $row['parent'];

                $this->visible = $row['visible'];

                $this->contextBlock = $row['contextBlock'];

                $this->pageid = $row['pageid'];

                $this->id = $row['id'];

                $this->profile = new wiwiProfile(0 != $row['prid'] ? $row['prid'] : WiwiProfile::getDefaultProfileId());
            }

            return $this;
        }

        /*
         * Creates a new revision from current object.
         */

        public function add()
        {
            global $xoopsUser;

            $sql = sprintf(
                "INSERT INTO %s (keyword, title, body, lastmodified, u_id, parent, visible, contextBlock, pageid, prid) VALUES('%s', '%s', '%s', '%s', '%u', '%s', %u, '%s', %u, %u)",
                $this->db->prefix('wiwimod'),
                addslashes($this->keyword),
                $this->ts->addSlashes($this->title),
                $this->ts->addSlashes($this->body),
                date('Y-m-d H:i:s'),                          //-- lastmodified is Now
                $xoopsUser ? $xoopsUser->getVar('uid') : 0,   //-- author is always the current user
                addslashes($this->parent),
                $this->visible,
                addslashes($this->contextBlock),
                $this->pageid,
                $this->profile->prid
            );

            $result = $this->db->query($sql);

            if (!$result) {
                return false;
            }

            if (0 == $this->pageid) {
                $this->pageid = $this->db->getInsertId();

                $sql = 'UPDATE ' . $this->db->prefix('wiwimod') . ' SET pageid = ' . $this->pageid . ' WHERE id = ' . $this->pageid;

                $result = $this->db->query($sql);

                if (!$result) {
                    return false;
                }
            }

            return true;
        }

        /*
         * Saves the current revision on the database.
         */

        public function save()
        {
            global $xoopsUser;

            $sql = sprintf(
                "UPDATE %s SET title='%s', body='%s', lastmodified='%s', u_id='%s', parent='%s', visible=%u, contextBlock='%s', pageid=%u, prid=%u WHERE id=%s",
                $this->db->prefix('wiwimod'),
                $this->ts->addSlashes($this->title),
                $this->ts->addSlashes($this->body),
                date('Y-m-d H:i:s'),
                $xoopsUser ? $xoopsUser->getVar('uid') : 0,   //-- author is always the current user
                addslashes($this->parent),
                $this->visible,
                addslashes($this->contextBlock),
                $this->pageid,
                $this->profile->prid,
                $this->id
            );

            $result = $this->db->query($sql);

            return ($result ? true : false);
        }

        /*
         * Renders revision content, interpreting wiki codes and XoopsCodes.
         */

        public function render($body = '')
        {
            $lbr = '<P>|</P>|<HR>|<TABLE>|</TABLE>|<DIV>|</DIV>|<BR>|<UL>|<LI>|</LI>|</UL>';

            $nl = '^|' . $lbr;

            $eol = $lbr . '|$';

            $lt = '(?:&lt;|<)';

            $gt = '(?:&gt;|>)';

            $search = [
                "#\r\n?#",                                                // is this one still useful ?
                '#' . $lt . '{2}(.*?)' . $gt . '{2}#s',                            // <<bold text>>
                "#\{{2}(.*?)\}{2}#s",                                    // {{italic text}}
                '#(' . $nl . ')-{4,}(' . $eol . ')#m',                            // ---- : horizontal rule
                "#\[\[BR\]\]#i",                                        // [br] : line break .. still useful ?

                "#\[\[XBLK (.+?)\]\]#ie",                                // Xoops block ($1 is the block id or title)
                "#\[\[IMG ([^\s\"\[>{}]+)( ([^\"<\n]+?))?\]\]#i",        // [[IMG url title]] : inline image ...

                "#(^|\s|>)(([A-Z][a-z]+){2,}\d*)\b#e",                    // CamelCase
                "#(^|\s|>)~(([A-Z][a-z]+){2,}\d*)\b#",                    // escaped CamelCase
                "#\[\[(([A-Z][a-z]+){2,}\d*) (.+?)\]\]#e",                // [[CamelCase title]]
                "#\[\[(<a.*>)(.*)</a> (.+?)\]\]#i",                        // [[www.mysite.org title]] and [[<a ... /a> title]]
                "#\[\[([^\[\]]+?)\s*\|\s*(.+?)\]\]#e",                    // [[free link | title]]
                "#\[\[(.+?)\]\]#e",                                        // [[free link]]
                //        "#([\w.-]+@[\w.-]+)(?![\w.]*(\">|<))#",
                "#(<a.+\?page=(([A-Z][a-z]+){2,}\d*))(\">.*)</a>#Uie",    // link with href ending with ?page=

                '#(' . $nl . ')=(.*)=(' . $eol . ')#m',                            // =Title=
                '#(' . $nl . ')' . $gt . ' .* (' . $eol . ')#me',                    // > quoted text
                '#(' . $nl . ")\* (.*)#m",                                    // * list item
                //		"#^(<li>.*</li>\n)+#m",
                '#' . $lt . "\[(PageIndex|RecentChanges)\]" . $gt . '#ie',        // <[PageIndex]> and <[RecentChanges]>
                "#^(?!\n|<h2>|<blockquote>|<hr>)(.*?)\n$#sm",            // surrounds with <P> and </P> some lines .. hum, still useful ?
                "#\n+#",                                                    // removes multiple line ends .. still useful ?
            ];

            $replace = [
                "\n",
                '<strong>\\1</strong>',
                '<em>\\1</em>',
                '\\1<hr>\\2',
                '<br>',

                '$this->render_block("$1")',
                '<img src="\\1" alt="\\3">',

                '"$1".$this->render_wikiLink("$2" , "",' . $this->wiwiConfig['ShowTitles'] . ')',
                '\\1\\2',
                '$this->render_wikiLink("$1" ,"$3", ' . $this->wiwiConfig['ShowTitles'] . ')',
                '$1$3</a>',
                '$this->render_wikiLink("$1" ,"$2", ' . $this->wiwiConfig['ShowTitles'] . ')',
                '$this->render_wikiLink("$1" ,"", ' . $this->wiwiConfig['ShowTitles'] . ')',
                //        '<a href="mailto:\\1">\\1</a>',
                '$this->render_wiwiLink("$2","$1","$4");',

                "\n\\1<h2>\\2</h2>\\3\n",
                '"<blockquote>".str_replace("\n", " ", preg_replace("#^> #m", "", "$0"))."</blockquote>\n"',
                '\\1<li>\\2</li>\\3',
                //		"<ul>\n\\0</ul>\n",
                '$this->render_index("$1")',
                '<p>\\1</p>',
                "\n",
            ];

            if ('' == $body) {
                $body = $this->body;
            }

            return preg_replace($search, $replace, $this->ts->displayTarea($body, 1, 1, 1, 1, 0));
        }

        /*
         * Utilities for page rendering ;
         */

        public function render_wiwiLink($pg, $a, $txt)
        {
            return $a . '&back=' . $this->keyword . $txt . ($this->pageExists($pg) ? '' : "<img src='" . XOOPS_URL . "/modules/wiwimod/images/nopage.gif'>") . '</a>';
        }

        public function render_wikiLink($keyword, $customTitle = '', $show_titles = false)
        {
            $normKeyword = $this->normalize($keyword);

            $sql = 'SELECT title FROM ' . $this->db->prefix('wiwimod') . " WHERE keyword='" . addslashes($normKeyword) . "' ORDER BY id DESC LIMIT 1";

            $dbresult = $this->db->query($sql);

            if ($this->db->getRowsNum($dbresult) > 0) {
                $pageExists = true;

                [$title] = $this->db->fetchRow($dbresult);

                $txt = '' == $customTitle ? (('' != $title) && $show_titles) ? $title : $normKeyword : $customTitle;
            } else {
                $pageExists = false;

                $txt = ('' != $customTitle ? $customTitle : $normKeyword);
            }

            return sprintf('<a href="%s">%s%s</a>', XOOPS_URL . '/modules/wiwimod/index.php?page=' . $this->encode($normKeyword) . '&back=' . $this->encode($this->keyword), $txt, ($pageExists ? '' : '<img src=' . XOOPS_URL . '/modules/wiwimod/images/nopage.gif>'));
        }

        public function render_index($type)
        {
            $settings = [
                'PageIndex' => [
                    'ORDER BY title ASC',
                    'title',
                    1,
                    '"<span class=wiwi_titre style=\"font-size:large;\">[$counter]</span><br>"',
                    '"&nbsp;&nbsp;<A href=\"index.php?page=".$this->encode($content["keyword"])."\">".($content["title"] == "" ? $content["keyword"] : $content["title"])."</a><br>"',
                    '',
                ],
                'PageIndexI' => [
                    'ORDER BY w1.keyword ASC',
                    'keyword',
                    1,
                    '"<span class=wiwi_titre>$counter</span><br>"',
                    '"&nbsp;&nbsp;<A href=\"index.php?page=".$content["keyword"]."\">".$content["keyword"]."</a> : ".$content["title"]."<br>"',
                    '',
                ],
                'RecentChanges' => [
                    'ORDER BY w1.lastmodified DESC LIMIT 20',
                    'lastmodified',
                    10,
                    '"<tr><td colspan=3><strong>".date("d.m.y", strtotime($counter))."</strong></td></tr>"',
                    '"<tr><td>&nbsp;".date("H:i",strtotime($content["lastmodified"]))."</td><td><A href=\"index.php?page=".$this->encode($content["keyword"])."\">".($content["title"] == "" ? $content["keyword"] : $content["title"])."</a></td><td><span class=\"itemPoster\">".getUserName($content["u_id"])."</span></td></tr>"',
                    '',
                ],
            ];

            $cfg = $settings[$type];

            $sql = 'SELECT w1.keyword, w1.title, w1.lastmodified, w1.u_id FROM ' . $this->db->prefix('wiwimod') . ' AS w1 LEFT JOIN ' . $this->db->prefix('wiwimod') . ' AS w2 ON w1.keyword=w2.keyword AND w1.id<w2.id WHERE w2.id IS NULL ' . $cfg[0];

            $result = $this->db->query($sql);

            $body = '';

            $counter = '[';

            while (false !== ($content = $this->db->fetchArray($result))) {
                if ($counter != mb_strtoupper(mb_substr($content[$cfg[1]], 0, $cfg[2]))) {
                    $counter = mb_strtoupper(mb_substr($content[$cfg[1]], 0, $cfg[2]));

                    eval('$body .= (($body)?"' . $cfg[5] . '":"")."\n\n".' . $cfg[3] . ';');
                }

                eval('$body .= ' . $cfg[4] . '."\n";');
            }

            return '<table>' . $body . (($body) ? $cfg[5] : '') . "</table>\n\n";
        }

        public function render_block($blkname)
        {
            $blk = wiwimod_getXoopsBlock($blkname);

            return '<table><tr><td>' . $blk['content'] . '</TD></TR></TABLE>';
        }

        /*
         * private function , used by parentList method.
         * Note : this was formerly an inline function, but php5 doesn't seem to accept it recursively.
         */

        public function parentList_recurr($child, &$parlist, &$db)
        {
            $sql = 'SELECT parent FROM ' . $db->prefix('wiwimod') . " WHERE keyword='" . addslashes($child) . "' ORDER BY id DESC LIMIT 1";

            $result = $db->query($sql);

            [$parent] = $db->fetchRow($result);

            if (('' != $parent) && (!in_array($parent, $parlist, true))) {
                $parlist[] = $parent;

                $this->parentList_recurr($parent, $parlist, $db);
            }
        }

        public function parentList()
        {
            $parlist = [];

            if ('' != $this->keyword) {
                $this->parentList_recurr($this->keyword, $parlist, $this->db);
            }

            foreach ($parlist as $key => $parent) {
                $parlist[$key] = $this->render_wikiLink($parent, '', $this->wiwiConfig['ShowTitles']);
            }

            return array_reverse($parlist);
        }

        public function history($limit = 0, $start = 0)
        {
            $sql = 'SELECT keyword, id, title, body, lastmodified, u_id FROM ' . $this->db->prefix('wiwimod') . " WHERE keyword='" . addslashes($this->keyword) . "' ORDER BY id DESC";

            $result = $this->db->query($sql, $limit, $start);

            $hist = [];

            for ($i = 0; $i < $this->db->getRowsNum($result); $i++) {
                $hist[] = $this->db->fetchArray($result);
            }

            return $hist;
        }

        public function historyNum()
        {
            $sql = 'SELECT id FROM ' . $this->db->prefix('wiwimod') . " WHERE keyword='" . addslashes($this->keyword) . "' ORDER BY id DESC";

            $result = $this->db->query($sql);

            $maxcount = $this->db->getRowsNum($result);

            return $maxcount;
        }

        public function diff(&$bodyDiff, &$titleDiff)
        {
            //

            // Get the latest revision contents

            //

            $sql = 'SELECT title, body FROM ' . $this->db->prefix('wiwimod') . " WHERE keyword='" . addslashes($this->keyword) . "' ORDER BY id DESC LIMIT 1";

            $result = $this->db->query($sql);

            [$title, $body] = $this->db->fetchRow($result);

            //

            // remove formatting tags, replace tags generating a line break with a "\n".

            //

            $search = [
                '#<(/?TABLE|TD|P|HR|DIV|UL|LI|PRE|BR)>#i',
                '#<(?!/?A|IMG)[/!]*?[^<>]*?>#si',
            ];

            $replace = [
                "<$1>\n",
                '',
            ];

            $body = preg_replace($search, $replace, $body);

            $body2 = preg_replace($search, $replace, $this->body);

            $bodyDiff = $this->render(diffDisplay($body2, $body));

            $titleDiff = ($title == $this->title) ? '<h2>' . $this->ts->htmlSpecialChars($title) . '</h2>' : '<h2><span style="color: red;">' . $this->ts->htmlSpecialChars($this->title) . '</span> &rarr; <span style="color: green;">' . $this->ts->htmlSpecialChars($title) . '</span></h2>';
        }

        public function canRead()
        {
            return ($this->profile->canRead());
        }

        public function canWrite()
        {
            return ($this->profile->canWrite());
        }

        public function canAdministrate()
        {
            return ($this->profile->canAdministrate());
        }

        public function canViewComments()
        {
            return ($this->profile->canViewComments());
        }

        public function canViewHistory()
        {
            return ($this->profile->canViewHistory());
        }

        /*
         * checks if current revision has been saved concurrently by another user
         * Note : even if this "was" a new page when first edited the doc, another
         *        user may have created a doc with the same "keyword" meanwhile ..
         */

        public function concurrentlySaved()
        {
            $sql = 'SELECT lastmodified FROM ' . $this->db->prefix('wiwimod') . " WHERE keyword='" . addslashes($this->keyword) . "' ORDER BY id DESC LIMIT 1";

            $result = $this->db->query($sql);

            $rowsnum = $this->db->getRowsNum($result);

            if (0 == $this->id) {
                return ($rowsnum > 0);  // this was a page creation : somebody did it before ...
            }  

            [$db_lastmodified] = $this->db->fetchRow($result);

            return ($this->lastmodified != $db_lastmodified);
        }

        public function pageExists($page = '', $id = 0)
        {
            $page = addslashes($this->normalize($page));

            if ($id > 0) {
                $sql = 'SELECT keyword FROM ' . $this->db->prefix('wiwimod') . " WHERE id = $id";
            } elseif (('' != $page) && ((int)(0 == $page))) {
                $sql = 'SELECT keyword FROM ' . $this->db->prefix('wiwimod') . " WHERE keyword='$page' ORDER BY id DESC LIMIT 1";
            } elseif ('' != $page) {
                $sql = 'SELECT keyword FROM ' . $this->db->prefix('wiwimod') . " WHERE pageid=$page ORDER BY id DESC LIMIT 1";
            } else {
                return false;
            }

            return ($this->db->getRowsNum($this->db->query($sql)) > 0);
        }

        /*
         * Returns an array of wiwiRevisions, selected upon given criteria
         * $where :
         * $order :
         * $items_perpage :		if 0, returns all the results
         * $current_start :		position of the first returned element within the results
         */

        public function getPages($where = '', $order = '', $items_perpage = 0, $current_start = 0)
        {
            if ('' == $order) {
                $order = 'keyword ASC';
            }

            $fieldlist = 'keyword|title|body|lastmodified|u_id|parent|visible|contextBlock|prid|id';

            $sql_a = 'SELECT w1.* FROM ' . $this->db->prefix('wiwimod') . ' AS w1 LEFT JOIN ' . $this->db->prefix('wiwimod') . ' AS w2 ON w1.keyword=w2.keyword AND w1.id < w2.id WHERE w2.id IS NULL';

            /*
             * the passed "where" clause has to be adapted because of he jointure : fields must be prefixed with "w1."
             *
             */

            if ('' != $where) {
                $sql_a .= ' AND (' . preg_replace('#(' . $fieldlist . ')#i', 'w1.$1', $where) . ') ';
            }

            if ('' != $order) {
                $sql_a .= ' ORDER BY ' . preg_replace('#(' . $fieldlist . ')#i', 'w1.$1', $order) . ' ';
            }

            $result_a = $this->db->query($sql_a, $items_perpage, $current_start);

            $pageArr = [];

            for ($i = 0; $i < $this->db->getRowsNum($result_a); $i++) {
                $row = $this->db->fetchArray($result_a);

                $pageObj = new self();

                $pageObj->keyword = $row['keyword'];

                $pageObj->title = $row['title'];

                $pageObj->body = $row['body'];

                $pageObj->lastmodified = $row['lastmodified'];

                $pageObj->u_id = $row['u_id'];

                $pageObj->parent = $row['parent'];

                $pageObj->visible = $row['visible'];

                $pageObj->contextBlock = $row['contextBlock'];

                $pageObj->pageid = $row['pageid'];

                $pageObj->id = $row['id'];

                $pageObj->profile = new wiwiProfile($row['prid']);

                $pageArr[$i] = $pageObj;

                unset($pageObj);
            }

            return $pageArr;
        }

        public function getPagesNum($where = '', $order = '')
        {
            if ('' == $order) {
                $order = 'keyword ASC';
            }

            $fieldlist = 'keyword|title|body|lastmodified|u_id|parent|visible|contextBlock|prid|id';

            $sql_a = 'SELECT w1.id FROM ' . $this->db->prefix('wiwimod') . ' AS w1 LEFT JOIN ' . $this->db->prefix('wiwimod') . ' AS w2 ON w1.keyword=w2.keyword AND w1.id < w2.id WHERE w2.id IS NULL';

            if ('' != $where) {
                $sql_a .= ' AND (' . preg_replace('#(' . $fieldlist . ')#i', 'w1.$1', $where) . ') ';
            }

            if ('' != $order) {
                $sql_a .= ' ORDER BY ' . preg_replace('#(' . $fieldlist . ')#i', 'w1.$1', $order) . ' ';
            }

            $result = $this->db->query($sql_a);

            $maxcount = $this->db->getRowsNum($result);

            return $maxcount;
        }

        //

        // Creates a new revision whom content is copied from the selected one, but with other data (parent, privileges etc..) untouched.

        //

        public function restore()
        {
            $latestRev = new self($this->keyword);

            $latestRev->title = addslashes($this->title);

            $latestRev->body = addslashes($this->body);

            $latestRev->contextBlock = $this->contextBlock;

            return $latestRev->add();
        }

        /*
         * Deletes all revisions of current page, anterior to current revision.
         */

        public function fix()
        {
            $sql = 'DELETE FROM ' . $this->db->prefix('wiwimod') . " WHERE keyword='" . addslashes($this->keyword) . "' AND id<" . $this->id;

            $success = $this->db->query($sql);

            return $success;
        }

        public function cleanPagesHistory()
        {
            $success = true;

            $sql = 'SELECT keyword, MAX(id) AS id FROM ' . $this->db->prefix('wiwimod') . " WHERE lastmodified<'" . date('Y-m-d H:i:s', time() - 61 * 24 * 3600) . "' GROUP BY keyword";

            $result = $this->db->query($sql);

            while (false !== ($content = $this->db->fetchArray($result))) {
                $rev = new self('', $content['id']);

                $success &= $rev->fix();
            }

            return $success;
        }

        public function deletePage()
        {
            $sql = 'DELETE FROM ' . $this->db->prefix('wiwimod') . " WHERE keyword='" . addslashes($this->keyword) . "'";

            $success = $this->db->query($sql);

            if ($success) {
                $this->id = 0;

                $this->pageid = 0;
            }

            return $success;
        }

        /*
         * Returns an array with all links on the current page.
         */

        public function getLinks($allowExternals = false)
        {
            $links = [];

            $search = [
                "#(^|\s|>)(([A-Z][a-z]+){2,}\d*)\b#",                    // CamelCase
                "#\[\[(([A-Z][a-z]+){2,}\d*) (.+?)\]\]#",                // [[CamelCase title]]
                "#\[\[<a href=\"([^\"]*)\"(?:[^>]*)>(.*)</a> (.+?)\]\]#i",                        // [[www.mysite.org title]] and [[<a ... /a> title]]
                "#\[\[([^\[\]]+?)\s*\|\s*(.+?)\]\]#",                    // [[free link | title]]
                "#\[\[(.+?)\]\]#",                                        // [[free link]]
                "#(<a.+\?page=(([A-Z][a-z]+){2,}\d*))\">(.*)</a>#Ui",    // link with href ending with ?page=
            ];

            $replace = [
                [2, 2, true],
                [1, 3, true],
                [1, 2, false],
                [1, 2, true],
                [1, 1, true],
                [2, 2, true],
            ];

            foreach ($search as $key => $pattern) {
                if (preg_match_all($pattern, $this->body, $matches, PREG_SET_ORDER)) {
                    foreach ($matches as $match) {
                        $links[] = ['url' => $match[$replace[$key][0]], 'txt' => $match[$replace[$key][1]], 'isWiwiPage' => $match[$replace[$key][2]]];
                    }
                }
            }

            return $links;
        }

        public function normalize($keyword)
        {
            $search = ["\'", '\"', '&quot;', '&nbsp;'];

            $replace = ["'", '"', '"', ' '];

            return str_replace($search, $replace, $keyword);
        }

        public function encode($keyword)
        {
            $search = ["\'", "'", '\"', '"', '&quot;', ' ', '&nbsp;'];

            $replace = ['%27', '%27', '%22', '%22', '%22', '+', '+'];

            return str_replace($search, $replace, $keyword);
        }

        public function decode($keyword)
        {
            $replace = ["'", '"', ' '];

            $search = ['%27', '%22', '+'];

            return str_replace($search, $replace, $keyword);
        }
    }  // end class wiwiRevision

    class WiwiPage extends WiwiRevision
    {
        public function __construct($keyword = '', $pageid = 0)
        {
            return parent::__construct($keyword, 0, $pageid);
        }
    }
}  // end "ifdefined"
