<?php

/*	=========================================

        Class wiwiProfile

    TODO :  remove any $xoopsModule or $xoopsModuleConfig references, to
            enable the class being used from within any other module.
    =========================================
 */
if (!defined('_WI_READ')) {
    define('_WI_READ', 1);

    define('_WI_WRITE', 2);

    define('_WI_ADMIN', 3);

    define('_WI_COMMENTS', 4);

    define('_WI_HISTORY', 5);

    class WiwiProfile
    {
        public $name;

        public $prid;                    // this attribute is naturally read-only...

        //

        //  arrays below have group ids as keys, and group names as values.

        //

        public $readers;

        public $writers;

        public $administrators;

        public $commentslevel;            // 0 if no comments allowed, otherwise _WI_READ, _WI_WRITE or _WI_ADMIN
        public $historylevel;            // 0 if no history allowed, otherwise _WI_READ, _WI_WRITE or _WI_ADMIN
        public $db;

        // private usage;

        //

        // Constructor

        //

        public function __construct($prid = 0)
        {
            $this->db = XoopsDatabaseFactory::getDatabaseConnection();

            $this->name = '';

            $this->readers = [];

            $this->writers = [];

            $this->administrators = [];

            $this->prid = $prid;

            $this->commentslevel = 0;

            $this->historylevel = _WI_WRITE;

            if (0 != $prid) {
                $this->load($prid);
            }
        }

        //

        // sets object with db data

        //

        public function load($prid = 0)
        {
            if (0 == $prid) {
                return;
            }  // 0 isn't a valid profile id.

            //

            // retrieve profile info

            //

            $sql = 'SELECT prname, commentslevel, historylevel FROM ' . $this->db->prefix('wiwimod_profiles') . ' WHERE prid=' . $prid;

            $res = $this->db->query($sql);

            if (0 == $this->db->getRowsNum($res)) {
                return false;
            }

            [$this->name, $this->commentslevel, $this->historylevel] = $this->db->fetchRow($res);

            $this->prid = $prid;

            //

            // retrieve groups info

            //

            $this->readers = [];

            $this->writers = [];

            $this->administrators = [];

            $memberHandler = xoops_getHandler('member');

            $grps = $memberHandler->getGroupList();

            $sql = 'SELECT gid, priv FROM ' . $this->db->prefix('wiwimod_prof_groups') . ' WHERE prid=' . $prid . ' ORDER BY priv';

            $res = $this->db->query($sql);

            while (false !== ($rows = $this->db->fetchArray($res))) {
                switch ($rows['priv']) {
                    case _WI_WRITE:
                        $dst = &$this->writers;
                        break;
                    case _WI_ADMIN:
                        $dst = &$this->administrators;
                        break;
                    case _WI_READ:
                    default:
                        $dst = &$this->readers;
                        break;
                }

                $dst[$rows['gid']] = $grps[$rows['gid']];
            }
        }

        public function getDefaultProfileId()
        {
            /*
             * cannot use globals xoopsModule or xoopsModuleConfig, if called from within another module ;
             * so must guess wiwimod module id from its folder ...
             */

            $moduleHandler = xoops_getHandler('module');

            $configHandler = xoops_getHandler('config');

            $wiwiModule = $moduleHandler->getByDirname('wiwimod');

            $wiwiConfig = $configHandler->getConfigsByCat(0, $wiwiModule->getVar('mid'));

            $prid = $wiwiConfig['DefaultProfile'];

            return $prid;
        }

        //

        // Saves object data to the database

        //

        public function store()
        {
            if ('' == $this->name) {
                return false;
            }

            if (0 == $this->prid) {
                //

                // Create new profile

                //

                $sql = sprintf('INSERT INTO %s ( prname, commentslevel, historylevel ) VALUES ( %s , %s , %s)', $this->db->prefix('wiwimod_profiles'), $this->db->quoteString($this->name), $this->commentslevel, $this->historylevel);

                $success = $this->db->query($sql);

                if ($success) {
                    $this->prid = $this->db->getInsertId();  // gets new profile id
                }
            } else {
                //

                // Update profile

                //

                $sql = sprintf('UPDATE %s SET prname = %s , commentslevel = %s, historylevel = %s WHERE prid = %u', $this->db->prefix('wiwimod_profiles'), $this->db->quoteString($this->name), $this->commentslevel, $this->historylevel, $this->prid);

                $success = $this->db->query($sql);

                if ($success) {
                    //

                    // delete old groups info

                    //

                    $sql = sprintf('DELETE FROM %s WHERE prid = %u', $this->db->prefix('wiwimod_prof_groups'), $this->prid);

                    $success = $this->db->query($sql);
                }
            }

            if ($success) {
                //

                // Insert groups info

                //

                foreach ($this->readers as $key) {
                    $sql = sprintf(
                        'INSERT INTO %s ( prid, gid, priv ) VALUES ( %u, %u, %u )',
                        $this->db->prefix('wiwimod_prof_groups'),
                        $this->prid,
                        $key,
                        _WI_READ
                    );

                    $success = $this->db->query($sql);
                }

                foreach ($this->writers as $key) {
                    $sql = sprintf(
                        'INSERT INTO %s ( prid, gid, priv ) VALUES ( %u, %u, %u )',
                        $this->db->prefix('wiwimod_prof_groups'),
                        $this->prid,
                        $key,
                        _WI_WRITE
                    );

                    $success = $success && $this->db->query($sql);
                }

                foreach ($this->administrators as $key) {
                    $sql = sprintf(
                        'INSERT INTO %s ( prid, gid, priv ) VALUES ( %u, %u, %u )',
                        $this->db->prefix('wiwimod_prof_groups'),
                        $this->prid,
                        $key,
                        _WI_ADMIN
                    );

                    $success = $success && $this->db->query($sql);
                }
            }

            if ($success) {    //-- update possible values for default profile
                $this->updateModuleConfig();
            }

            return ($success ? $this->prid : false);
        }

        //

        // Delete a profile, and modifies impacted Wiwi pages profile

        //

        public function delete($newprf = 0)
        {
            if (null === $this->prid) {
                return true;
            }

            $sql = sprintf('DELETE FROM %s WHERE prid = %u', $this->db->prefix('wiwimod_prof_groups'), $this->prid);

            $success = $this->db->query($sql);

            $sql = sprintf('DELETE FROM %s WHERE prid=%u', $this->db->prefix('wiwimod_profiles'), $this->prid);

            $success = $this->db->query($sql);

            $sql = sprintf('UPDATE %s SET prid=%u WHERE prid=%u', $this->db->prefix('wiwimod'), $newprf, $this->prid);

            $success = $this->db->query($sql);

            if ($success) {    //-- update possible values for default profile
                $this->updateModuleConfig();
            }

            return $success;
        }

        //

        // Retrieves an array with all profiles name and id.

        //

        public function getAllProfiles()
        {
            $sql = 'SELECT prname, prid FROM ' . $this->db->prefix('wiwimod_profiles');

            $res = $this->db->query($sql);

            $prlist = [];

            while (false !== ($rows = $this->db->fetchArray($res))) {
                $prlist[$rows['prid']] = $rows['prname'];
            }

            return $prlist;
        }

        //

        // Retrieves an array with all profile name and id where the selected user has admin privilege

        // Xoops Webmasters have admin access to all profiles of course.

        //

        public function getAdminProfiles($user)
        {
            $memberHandler = xoops_getHandler('member');

            $usergroups = $user ? $memberHandler->getGroupsByUser($user->getVar('uid')) : [XOOPS_GROUP_ANONYMOUS];

            if (in_array(XOOPS_GROUP_ADMIN, $usergroups, true)) {
                $prlist = $this->getAllProfiles();
            } else {
                $t1 = $this->db->prefix('wiwimod_profiles');

                $t2 = $this->db->prefix('wiwimod_prof_groups');

                $sql = sprintf(
                    'SELECT DISTINCT %s.prid, prname FROM %s LEFT JOIN %s ON %s.prid = %s.prid WHERE gid IN (%s) AND priv = %s',
                    $t1,
                    $t2,
                    $t1,
                    $t2,
                    $t1,
                    implode(',', $usergroups),
                    _WI_ADMIN
                );

                $res = $this->db->query($sql);

                $prlist = [];

                while (false !== ($rows = $this->db->fetchArray($res))) {
                    $prlist[$rows['prid']] = $rows['prname'];
                }
            }

            return $prlist;
        }

        //

        // Retrieves selected user read, write and administrator privileges on the current profile,

        // depending on all groups he is member of.

        // Xoops webmasters have full access of course.

        // Returns an three items array with keys _WI_READ, _WI_WRITE, _WI_ADMIN, _WI_COMMENTS

        //

        public function getUserPrivileges($user = '')
        {
            global $xoopsUser;

            $memberHandler = xoops_getHandler('member');

            if ('' == $user) {
                $user = $xoopsUser;
            }

            $usergroups = $user ? $memberHandler->getGroupsByUser($user->getVar('uid')) : [XOOPS_GROUP_ANONYMOUS];

            $priv = [];

            $priv[_WI_ADMIN] = in_array(XOOPS_GROUP_ADMIN, $usergroups, true) || (count(array_intersect($usergroups, array_keys($this->administrators))) > 0);

            $priv[_WI_WRITE] = $priv[_WI_ADMIN] || (count(array_intersect($usergroups, array_keys($this->writers))) > 0);

            $priv[_WI_READ] = $priv[_WI_WRITE] || (count(array_intersect($usergroups, array_keys($this->readers))) > 0);

            $priv[_WI_COMMENTS] = (($priv[_WI_READ] && (_WI_READ == $this->commentslevel))
                                   || ($priv[_WI_WRITE] && ((_WI_READ == $this->commentslevel) || (_WI_WRITE == $this->commentslevel)))
                                   || ($priv[_WI_ADMIN] && ((_WI_READ == $this->commentslevel) || (_WI_WRITE == $this->commentslevel) || (_WI_ADMIN == $this->commentslevel))));

            $priv[_WI_HISTORY] = (($priv[_WI_READ] && (_WI_READ == $this->historylevel))
                                  || ($priv[_WI_WRITE] && ((_WI_READ == $this->historylevel) || (_WI_WRITE == $this->historylevel)))
                                  || ($priv[_WI_ADMIN] && ((_WI_READ == $this->historylevel) || (_WI_WRITE == $this->historylevel) || (_WI_ADMIN == $this->historylevel))));

            return $priv;
        }

        public function canRead()
        {
            $priv = $this->getUserPrivileges();

            return ($priv[_WI_READ]);
        }

        public function canWrite()
        {
            $priv = $this->getUserPrivileges();

            return ($priv[_WI_WRITE]);
        }

        public function canAdministrate()
        {
            $priv = $this->getUserPrivileges();

            return ($priv[_WI_ADMIN]);
        }

        public function canViewComments()
        {
            $priv = $this->getUserPrivileges();

            return ($priv[_WI_COMMENTS]);
        }

        public function canViewHistory()
        {
            $priv = $this->getUserPrivileges();

            return ($priv[_WI_HISTORY]);
        }

        /*
         * Updates wiwimod's module options with the uptodate list of profiles
         * (to enable selecting the "default" profile within module's preferences.
         */

        public function updateModuleConfig()
        {
            /*
             * cannot use the global xoopsModule, if called from within another module ;
             * so must guess wiwimod module id from its folder ...
             */

            $moduleHandler = xoops_getHandler('module');

            $myXoopsModule = $moduleHandler->getByDirname('wiwimod');

            //-- get the config item options from the database

            $criteria = new CriteriaCompo(new Criteria('conf_modid', $myXoopsModule->getVar('mid')));

            $criteria->add(new Criteria('conf_name', 'DefaultProfile'));

            $configHandler = xoops_getHandler('config');

            $configs = &$configHandler->getConfigs($criteria, false);

            $confid = $configs[0]->getVar('conf_id');

            $old_options = &$configHandler->getConfigOptions(new Criteria('conf_id', $confid), false);

            //-- create the new options

            $optionshandler = xoops_getHandler('configoption');

            $prlist = $this->getAllProfiles();

            foreach ($prlist as $prid => $prname) {
                $opt = $optionshandler->create();

                $opt->setVar('conf_id', $confid);

                $opt->setVar('confop_name', $prname);

                $opt->setVar('confop_value', $prid);

                $optionshandler->insert($opt);

                unset($opt);
            }

            //-- delete old ones;

            foreach ($old_options as $opt) {
                $optionshandler->delete($opt);
            }

            //-- clear cache
            /*		$cnf =& $configs[0];
                    if (!empty($configHandler->_cachedConfigs[$cnf->getVar('conf_modid')][$cnfg->getVar('conf_catid')])) {
                        unset ($configHandler->_cachedConfigs[$cnf->getVar('conf_modid')][$cnfg->getVar('conf_catid')]);
                    } */
        }
    }  // end class wiwiProfile
}  // end "ifdefined"
