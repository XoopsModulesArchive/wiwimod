#
# Table structure for wiwimod 0.7
#

CREATE TABLE wiwimod (
    id           INT(10)      NOT NULL AUTO_INCREMENT,
    keyword      VARCHAR(255) NOT NULL DEFAULT '',
    title        VARCHAR(255) NOT NULL DEFAULT '',
    body         TEXT         NOT NULL DEFAULT '',
    lastmodified DATETIME     NOT NULL DEFAULT '0000-00-00 00:00:00',
    u_id         INT(10)      NOT NULL DEFAULT '0',
    visible      INT(3)                DEFAULT '0',
    contextBlock VARCHAR(255)          DEFAULT '',
    parent       VARCHAR(255)          DEFAULT '',
    pageid       INT(10)               DEFAULT '0',
    prid         INTEGER               DEFAULT '0',
    PRIMARY KEY (id)
)
    ENGINE = ISAM;

CREATE TABLE wiwimod_profiles (
    prid          INTEGER     NOT NULL AUTO_INCREMENT,
    prname        VARCHAR(20) NOT NULL DEFAULT '',
    commentslevel INTEGER              DEFAULT 0,
    historylevel  INTEGER              DEFAULT 1,
    PRIMARY KEY (prid)
)
    ENGINE = ISAM;

CREATE TABLE wiwimod_prof_groups (
    prid INTEGER,
    gid  INTEGER,
    priv SMALLINT
)
    ENGINE = ISAM;

INSERT INTO wiwimod_profiles (prname, commentslevel)
VALUES ('Open content', 1);
INSERT INTO wiwimod_prof_groups (prid, gid, priv)
VALUES (LAST_INSERT_ID(), 3, 1);
INSERT INTO wiwimod_prof_groups (prid, gid, priv)
VALUES (LAST_INSERT_ID(), 2, 1);
INSERT INTO wiwimod_prof_groups (prid, gid, priv)
VALUES (LAST_INSERT_ID(), 1, 1);

INSERT INTO wiwimod_prof_groups (prid, gid, priv)
VALUES (LAST_INSERT_ID(), 3, 2);
INSERT INTO wiwimod_prof_groups (prid, gid, priv)
VALUES (LAST_INSERT_ID(), 2, 2);
INSERT INTO wiwimod_prof_groups (prid, gid, priv)
VALUES (LAST_INSERT_ID(), 1, 2);

INSERT INTO wiwimod_prof_groups (prid, gid, priv)
VALUES (LAST_INSERT_ID(), 3, 3);
INSERT INTO wiwimod_prof_groups (prid, gid, priv)
VALUES (LAST_INSERT_ID(), 2, 3);
INSERT INTO wiwimod_prof_groups (prid, gid, priv)
VALUES (LAST_INSERT_ID(), 1, 3);

INSERT INTO wiwimod_profiles (prname, commentslevel)
VALUES ('Public content', 1);
INSERT INTO wiwimod_prof_groups (prid, gid, priv)
VALUES (LAST_INSERT_ID(), 3, 1);
INSERT INTO wiwimod_prof_groups (prid, gid, priv)
VALUES (LAST_INSERT_ID(), 2, 1);
INSERT INTO wiwimod_prof_groups (prid, gid, priv)
VALUES (LAST_INSERT_ID(), 1, 1);

INSERT INTO wiwimod_prof_groups (prid, gid, priv)
VALUES (LAST_INSERT_ID(), 2, 2);
INSERT INTO wiwimod_prof_groups (prid, gid, priv)
VALUES (LAST_INSERT_ID(), 1, 2);

INSERT INTO wiwimod_prof_groups (prid, gid, priv)
VALUES (LAST_INSERT_ID(), 1, 3);

INSERT INTO wiwimod_profiles (prname, commentslevel)
VALUES ('Private content', 1);
INSERT INTO wiwimod_prof_groups (prid, gid, priv)
VALUES (LAST_INSERT_ID(), 2, 1);
INSERT INTO wiwimod_prof_groups (prid, gid, priv)
VALUES (LAST_INSERT_ID(), 1, 1);

INSERT INTO wiwimod_prof_groups (prid, gid, priv)
VALUES (LAST_INSERT_ID(), 2, 2);
INSERT INTO wiwimod_prof_groups (prid, gid, priv)
VALUES (LAST_INSERT_ID(), 1, 2);

INSERT INTO wiwimod_prof_groups (prid, gid, priv)
VALUES (LAST_INSERT_ID(), 1, 3);

INSERT INTO wiwimod
VALUES (1, 'WiwiHome', 'Your Wiwi home page',
        '<P>\r\n<TABLE border=0>\r\n<TBODY>\r\n<TR>\r\n<TD>\r\n<P>Welcome ;<BR>This is Wiwi\'s default home page. Feel free to edit and modify it . To create new pages, type in anywhere a page name and surround it with double square brackets ( [[ ). When you save this page, the brackets will be replaced by a link to your new page.</P>\r\n<P>Check <A href=\"manual.html\" target=_blank>the manual</A> for an in depth view of editing features.</P></TD>\r\n<TD><IMG src=\"/modules/wiwimod/images/wiwilogo.gif\"></TD></TR></TBODY></TABLE></P>\r\n<P>\r\n<TABLE style=\"WIDTH: 100%\" cellSpacing=5 cellPadding=5 width=\"100%\" border=0>\r\n<TBODY>\r\n<TR>\r\n<TD bgColor=#e4e4e4>Pages index</TD>\r\n<TD bgColor=#e4e4e4>Recently modified pages</TD></TR>\r\n<TR>\r\n<TD bgColor=#f6f6f6>&lt;[PageIndex]&gt;</TD>\r\n<TD bgColor=#f6f6f6>&lt;[RecentChanges]&gt;</TD></TR></TBODY></TABLE><BR><BR></P>',
        '2005-03-06 00:02:09', 1, 0, '', '', 1, 1);

