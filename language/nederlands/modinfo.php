<?php
// $Id: modinfo.php,v 1.14 2004/09/11 10:37:07 onokazu Exp $
// Module Info

// The name of this module
define('_MI_WIWIMOD_NAME', 'Wiwi');

// A brief description of this module
define('_MI_WIWIMOD_DESC', 'Een wiki-achtige tool.');

// Admin menu
define('_MI_WIWIMOD_ADMENU1', 'Pagina\'s ');
define('_MI_WIWIMOD_ADMENU2', 'Toegangsrechten');
define('_MI_WIWIMOD_ADMENU3', 'Blocks/Groepen');
define('_MI_WIWIMOD_ADMENU4', 'Over...');

// Admin options
define('_MI_WIWIMOD_EDITOR', 'Welke editor moet wiwi gebruiken?');
define('_MI_WIWIMOD_EDITOR_DESC', 'Beschrijving editor');
define('_MI_WIWIMOD_DEFAULTPROFILE', 'Standaard Profiel');

define('_MI_WIWIMOD_ALLOWPDF', 'PDF button op pagina\'s tonen?');
define('_MI_WIWIMOD_ALLOWPDF_DESC', 'ATTENTIE: De HTML naar PDF generatie verkeert nog in een experimentele fase. Zodra er afbeeldingen in een wiwi pagina aanwezig zijn kunnen er problemen optreden.');

define('_MI_WIWIMOD_SHOWTITLES', 'Toon de pagina titels in plaats van de PaginaNaamLink');
define('_MI_WIWIMOD_SHOWTITLES_DESC', 'Toon de pagina titels in plaats van de pagina linknamen opgemaakt in CamelCase/Wiwilinks');

//Added for wiwi 0.8.2
define('_MI_WIWIMOD_XOOPSEDITOR', 'Kies een door Xoops ondersteunde "XoopsEditor" ');
define('_MI_WIWIMOD_XOOPSEDITOR_DESC', 'Alleen geldig indien in bovenstaande setting de XoopsEditor is gekozen');
