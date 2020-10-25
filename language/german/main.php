<?php
/*
 * Module and admin language definition
 *
 *	_BTN	: text within buttons or action links
 *  _COL	: column headers
 *  _TXT	: "verbose" text (probably within content)
 *  _FLD	: title of form elements
 *  _DESC	: description under the title for form elements
 *  _MSG	: messages, alerts ...
 *
 */

define('_MD_WIWI_MODIFIED_TXT', 'Aktualisierung ');
define('_MD_WIWI_BY', 'von');
define('_MD_WIWI_HISTORY_TXT', 'Geschichte');
define('_MD_WIWI_EDIT_TXT', 'Seite bearbeiten');
define('_MD_WIWI_BODY_TXT', 'Inhalt');
define('_MD_WIWI_DIFF_TXT', 'Differences between current and latest revisions');

//define('_MD_WIWI_EDIT_BTN','Edit');
//define('_MD_WIWI_PREVIEW_BTN','Preview');
define('_MD_WIWI_SUBMITREVISION_BTN', 'New revision');
define('_MD_WIWI_QUIETSAVE_BTN', 'Save');
define('_MD_WIWI_HISTORY_BTN', 'Geschichte');
define('_MD_WIWI_PAGEVIEW_BTN', 'Zurück zu Seite Ansicht');
define('_MD_WIWI_VIEW_BTN', 'Ansicht');
define('_MD_WIWI_RESTORE_BTN', 'Wiederherstellen');
define('_MD_WIWI_FIX_BTN', 'Fix');
define('_MD_WIWI_COMPARE_BTN', 'Vergleichen Sie');

define('_MD_WIWI_TITLE_FLD', 'Titel');
define('_MD_WIWI_BODY_FLD', 'Inhalt');
define('_MD_WIWI_VISIBLE_FLD', 'Sichtbar');
define('_MD_WIWI_CONTEXTBLOCK_FLD', 'Inhalt des Seitenblock');
define('_MD_WIWI_PARENT_FLD', 'Bezugsseite');
define('_MD_WIWI_PROFILE_FLD', 'Benutzerprofil');

define('_MD_WIWI_TITLE_COL', 'Titel');
define('_MD_WIWI_MODIFIED_COL', 'Aktualisierung');
define('_MD_WIWI_AUTHOR_COL', 'Autor');
define('_MD_WIWI_ACTION_COL', 'Aktion');
define('_MD_WIWI_KEYWORD_COL', 'Seiten ID');

define('_MD_WIWI_PAGENOTFOUND_MSG', 'Die Seite existiert bisher nicht.');
define('_MD_WIWI_DBUPDATED_MSG', 'Datenbank erfolgreich aktualisiert!');
define('_MD_WIWI_ERRORINSERT_MSG', 'Fehler beim Aktualisieren der Datenbank!');
define('_MD_WIWI_EDITCONFLICT_MSG', 'Ung&uuml;ltige Angaben! - Die &Auml;nderungen wurden abgelehnt!');
define('_MD_WIWI_NOREADACCESS_MSG', '<br><h4>Entschuldigung, die Ansicht dieser Seite erfordert weitergehende Benutzerrechte.</h4><br>');
define('_MD_WIWI_NOWRITEACCESS_MSG', '<br><h4>Sorry, you don\'t have write access on this page.</h4><br>');

// Wiwi special pages - DO NOT TRANSLATE -
// Change these names, if you want a different homepage and error page
// for this language - just make sure that they are legal WiwiLink names.
define('_MI_WIWIMOD_WIWIHOME', 'WiwiHome');
define('_MI_WIWIMOD_WIWI404', 'IllegalName');
