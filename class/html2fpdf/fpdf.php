<?php
/*******************************************************************************
 * Software: FPDF                                                               *
 * Version:  1.52                                                               *
 * Date:     2003-12-30                                                         *
 * Author:   Olivier PLATHEY                                                    *
 * License:  Freeware                                                           *
 *                                                                              *
 * You may use, modify and redistribute this software as you wish.              *
 *******************************************************************************/

/*
* Modified by Renato Coelho
* (look for 'EDITEI')
*/

if (!class_exists('FPDF')) {
    define('FPDF_VERSION', '1.52');

    class FPDF
    {
        //Private properties
        public $page;               //current page number
        public $n;                  //current object number
        public $offsets;            //array of object offsets
        public $buffer;             //buffer holding in-memory PDF
        public $pages;              //array containing pages
        public $state;              //current document state
        public $compress;           //compression flag
        public $DefOrientation;     //default orientation
        public $CurOrientation;     //current orientation
        public $OrientationChanges; //array indicating orientation changes
        public $k;                  //scale factor (number of points in user unit)
        public $fwPt;

        public $fhPt;         //dimensions of page format in points

        public $fw;

        public $fh;             //dimensions of page format in user unit

        public $wPt;

        public $hPt;           //current dimensions of page in points

        public $w;

        public $h;               //current dimensions of page in user unit
        public $lMargin;            //left margin
        public $tMargin;            //top margin
        public $rMargin;            //right margin
        public $bMargin;            //page break margin
        public $cMargin;            //cell margin
        public $x;

        public $y;               //current position in user unit for cell positioning
        public $lasth;              //height of last cell printed
        public $LineWidth;          //line width in user unit
        public $CoreFonts;          //array of standard font names
        public $fonts;              //array of used fonts
        public $FontFiles;          //array of font files
        public $diffs;              //array of encoding differences
        public $images;             //array of used images
        public $PageLinks;          //array of links in pages
        public $links;              //array of internal links
        public $FontFamily;         //current font family
        public $FontStyle;          //current font style
        public $underline;          //underlining flag
        public $CurrentFont;        //current font info
        public $FontSizePt;         //current font size in points
        public $FontSize;           //current font size in user unit
        public $DrawColor;          //commands for drawing color
        public $FillColor;          //commands for filling color
        public $TextColor;          //commands for text color
        public $ColorFlag;          //indicates whether fill and text colors are different
        public $ws;                 //word spacing
        public $AutoPageBreak;      //automatic page breaking
        public $PageBreakTrigger;   //threshold used to trigger page breaks
        public $InFooter;           //flag set when processing footer
        public $ZoomMode;           //zoom display mode
        public $LayoutMode;         //layout display mode
        public $title;              //title
        public $subject;            //subject
        public $author;             //author
        public $keywords;           //keywords
        public $creator;            //creator
        public $AliasNbPages;       //alias for total number of pages

        /*******************************************************************************
         *                                                                              *
         *                               Public methods                                 *
         *                                                                              *
         ******************************************************************************
         * @param string $orientation
         * @param string $unit
         * @param string $format
         */

        public function __construct($orientation = 'P', $unit = 'mm', $format = 'A4')
        {
            //Some checks

            $this->_dochecks();

            //Initialization of properties

            $this->page = 0;

            $this->n = 2;

            $this->buffer = '';

            $this->pages = [];

            $this->OrientationChanges = [];

            $this->state = 0;

            $this->fonts = [];

            $this->FontFiles = [];

            $this->diffs = [];

            $this->images = [];

            $this->links = [];

            $this->InFooter = false;

            $this->lasth = 0;

            $this->FontFamily = '';

            $this->FontStyle = '';

            $this->FontSizePt = 12;

            $this->underline = false;

            $this->DrawColor = '0 G';

            $this->FillColor = '0 g';

            $this->TextColor = '0 g';

            $this->ColorFlag = false;

            $this->ws = 0;

            //Standard fonts

            $this->CoreFonts = [
                'courier' => 'Courier',
                'courierB' => 'Courier-Bold',
                'courierI' => 'Courier-Oblique',
                'courierBI' => 'Courier-BoldOblique',
                'helvetica' => 'Helvetica',
                'helveticaB' => 'Helvetica-Bold',
                'helveticaI' => 'Helvetica-Oblique',
                'helveticaBI' => 'Helvetica-BoldOblique',
                'times' => 'Times-Roman',
                'timesB' => 'Times-Bold',
                'timesI' => 'Times-Italic',
                'timesBI' => 'Times-BoldItalic',
                'symbol' => 'Symbol',
                'zapfdingbats' => 'ZapfDingbats',
            ];

            //Scale factor

            if ('pt' == $unit) {
                $this->k = 1;
            } elseif ('mm' == $unit) {
                $this->k = 72 / 25.4;
            } elseif ('cm' == $unit) {
                $this->k = 72 / 2.54;
            } elseif ('in' == $unit) {
                $this->k = 72;
            } else {
                $this->Error('Incorrect unit: ' . $unit);
            }

            //Page format

            if (is_string($format)) {
                $format = mb_strtolower($format);

                if ('a3' == $format) {
                    $format = [841.89, 1190.55];
                } elseif ('a4' == $format) {
                    $format = [595.28, 841.89];
                } elseif ('a5' == $format) {
                    $format = [420.94, 595.28];
                } elseif ('letter' == $format) {
                    $format = [612, 792];
                } elseif ('legal' == $format) {
                    $format = [612, 1008];
                } else {
                    $this->Error('Unknown page format: ' . $format);
                }

                $this->fwPt = $format[0];

                $this->fhPt = $format[1];
            } else {
                $this->fwPt = $format[0] * $this->k;

                $this->fhPt = $format[1] * $this->k;
            }

            $this->fw = $this->fwPt / $this->k;

            $this->fh = $this->fhPt / $this->k;

            //Page orientation

            $orientation = mb_strtolower($orientation);

            if ('p' == $orientation or 'portrait' == $orientation) {
                $this->DefOrientation = 'P';

                $this->wPt = $this->fwPt;

                $this->hPt = $this->fhPt;
            } elseif ('l' == $orientation or 'landscape' == $orientation) {
                $this->DefOrientation = 'L';

                $this->wPt = $this->fhPt;

                $this->hPt = $this->fwPt;
            } else {
                $this->Error('Incorrect orientation: ' . $orientation);
            }

            $this->CurOrientation = $this->DefOrientation;

            $this->w = $this->wPt / $this->k;

            $this->h = $this->hPt / $this->k;

            //Page margins (1 cm)

            $margin = 28.35 / $this->k;

            $this->SetMargins($margin, $margin);

            //Interior cell margin (1 mm)

            $this->cMargin = $margin / 10;

            //Line width (0.2 mm)

            $this->LineWidth = .567 / $this->k;

            //Automatic page break

            $this->SetAutoPageBreak(true, 2 * $margin);

            //Full width display mode

            $this->SetDisplayMode('fullwidth');

            //Compression

            $this->SetCompression(true);
        }

        public function SetMargins($left, $top, $right = -1)
        {
            //Set left, top and right margins

            $this->lMargin = $left;

            $this->tMargin = $top;

            if (-1 == $right) {
                $right = $left;
            }

            $this->rMargin = $right;
        }

        public function SetLeftMargin($margin)
        {
            //Set left margin

            $this->lMargin = $margin;

            if ($this->page > 0 and $this->x < $margin) {
                $this->x = $margin;
            }
        }

        public function SetTopMargin($margin)
        {
            //Set top margin

            $this->tMargin = $margin;
        }

        public function SetRightMargin($margin)
        {
            //Set right margin

            $this->rMargin = $margin;
        }

        public function SetAutoPageBreak($auto, $margin = 0)
        {
            //Set auto page break mode and triggering margin

            $this->AutoPageBreak = $auto;

            $this->bMargin = $margin;

            $this->PageBreakTrigger = $this->h - $margin;
        }

        public function SetDisplayMode($zoom, $layout = 'continuous')
        {
            //Set display mode in viewer

            if ('fullpage' == $zoom or 'fullwidth' == $zoom or 'real' == $zoom or 'default' == $zoom or !is_string($zoom)) {
                $this->ZoomMode = $zoom;
            } else {
                $this->Error('Incorrect zoom display mode: ' . $zoom);
            }

            if ('single' == $layout or 'continuous' == $layout or 'two' == $layout or 'default' == $layout) {
                $this->LayoutMode = $layout;
            } else {
                $this->Error('Incorrect layout display mode: ' . $layout);
            }
        }

        public function SetCompression($compress)
        {
            //Set page compression

            if (function_exists('gzcompress')) {
                $this->compress = $compress;
            } else {
                $this->compress = false;
            }
        }

        public function SetTitle($title)
        {
            //Title of document

            $this->title = $title;
        }

        public function SetSubject($subject)
        {
            //Subject of document

            $this->subject = $subject;
        }

        public function SetAuthor($author)
        {
            //Author of document

            $this->author = $author;
        }

        public function SetKeywords($keywords)
        {
            //Keywords of document

            $this->keywords = $keywords;
        }

        public function SetCreator($creator)
        {
            //Creator of document

            $this->creator = $creator;
        }

        public function AliasNbPages($alias = '{nb}')
        {
            //Define an alias for total number of pages

            $this->AliasNbPages = $alias;
        }

        public function Error($msg)
        {
            //Fatal error

            die('<B>FPDF error: </B>' . $msg);
        }

        public function Open()
        {
            //Begin document

            if (0 == $this->state) {
                $this->_begindoc();
            }
        }

        public function Close()
        {
            //Terminate document

            if (3 == $this->state) {
                return;
            }

            if (0 == $this->page) {
                $this->AddPage();
            }

            //Page footer

            $this->InFooter = true;

            $this->Footer();

            $this->InFooter = false;

            //Close page

            $this->_endpage();

            //Close document

            $this->_enddoc();
        }

        public function AddPage($orientation = '')
        {
            //Start a new page

            if (0 == $this->state) {
                $this->Open();
            }

            $family = $this->FontFamily;

            $style = $this->FontStyle . ($this->underline ? 'U' : '');

            $size = $this->FontSizePt;

            $lw = $this->LineWidth;

            $dc = $this->DrawColor;

            $fc = $this->FillColor;

            $tc = $this->TextColor;

            $cf = $this->ColorFlag;

            if ($this->page > 0) {
                //Page footer

                $this->InFooter = true;

                $this->Footer();

                $this->InFooter = false;

                //Close page

                $this->_endpage();
            }

            //Start new page

            $this->_beginpage($orientation);

            //Set line cap style to square

            $this->_out('2 J');

            //Set line width

            $this->LineWidth = $lw;

            $this->_out(sprintf('%.2f w', $lw * $this->k));

            //Set font

            if ($family) {
                $this->SetFont($family, $style, $size);
            }

            //Set colors

            $this->DrawColor = $dc;

            if ('0 G' != $dc) {
                $this->_out($dc);
            }

            $this->FillColor = $fc;

            if ('0 g' != $fc) {
                $this->_out($fc);
            }

            $this->TextColor = $tc;

            $this->ColorFlag = $cf;

            //Page header

            $this->Header();

            //Restore line width

            if ($this->LineWidth != $lw) {
                $this->LineWidth = $lw;

                $this->_out(sprintf('%.2f w', $lw * $this->k));
            }

            //Restore font

            if ($family) {
                $this->SetFont($family, $style, $size);
            }

            //Restore colors

            if ($this->DrawColor != $dc) {
                $this->DrawColor = $dc;

                $this->_out($dc);
            }

            if ($this->FillColor != $fc) {
                $this->FillColor = $fc;

                $this->_out($fc);
            }

            $this->TextColor = $tc;

            $this->ColorFlag = $cf;
        }

        public function Header()
        {
            //To be implemented in your own inherited class
        }

        public function Footer()
        {
            //To be implemented in your own inherited class
        }

        public function PageNo()
        {
            //Get current page number

            return $this->page;
        }

        public function SetDrawColor($r, $g = -1, $b = -1)
        {
            //Set color for all stroking operations

            if ((0 == $r and 0 == $g and 0 == $b) or -1 == $g) {
                $this->DrawColor = sprintf('%.3f G', $r / 255);
            } else {
                $this->DrawColor = sprintf('%.3f %.3f %.3f RG', $r / 255, $g / 255, $b / 255);
            }

            if ($this->page > 0) {
                $this->_out($this->DrawColor);
            }
        }

        public function SetFillColor($r, $g = -1, $b = -1)
        {
            //Set color for all filling operations

            if ((0 == $r and 0 == $g and 0 == $b) or -1 == $g) {
                $this->FillColor = sprintf('%.3f g', $r / 255);
            } else {
                $this->FillColor = sprintf('%.3f %.3f %.3f rg', $r / 255, $g / 255, $b / 255);
            }

            $this->ColorFlag = ($this->FillColor != $this->TextColor);

            if ($this->page > 0) {
                $this->_out($this->FillColor);
            }
        }

        public function SetTextColor($r, $g = -1, $b = -1)
        {
            //Set color for text

            if ((0 == $r and 0 == $g and 0 == $b) or -1 == $g) {
                $this->TextColor = sprintf('%.3f g', $r / 255);
            } else {
                $this->TextColor = sprintf('%.3f %.3f %.3f rg', $r / 255, $g / 255, $b / 255);
            }

            $this->ColorFlag = ($this->FillColor != $this->TextColor);
        }

        public function GetStringWidth($s)
        {
            //Get width of a string in the current font

            $s = (string)$s;

            $cw = &$this->CurrentFont['cw'];

            $w = 0;

            $l = mb_strlen($s);

            for ($i = 0; $i < $l; $i++) {
                $w += $cw[$s[$i]];
            }

            return $w * $this->FontSize / 1000;
        }

        public function SetLineWidth($width)
        {
            //Set line width

            $this->LineWidth = $width;

            if ($this->page > 0) {
                $this->_out(sprintf('%.2f w', $width * $this->k));
            }
        }

        public function Line($x1, $y1, $x2, $y2)
        {
            //Draw a line

            $this->_out(sprintf('%.2f %.2f m %.2f %.2f l S', $x1 * $this->k, ($this->h - $y1) * $this->k, $x2 * $this->k, ($this->h - $y2) * $this->k));
        }

        public function Rect($x, $y, $w, $h, $style = '')
        {
            //Draw a rectangle

            if ('F' == $style) {
                $op = 'f';
            } elseif ('FD' == $style or 'DF' == $style) {
                $op = 'B';
            } else {
                $op = 'S';
            }

            $this->_out(sprintf('%.2f %.2f %.2f %.2f re %s', $x * $this->k, ($this->h - $y) * $this->k, $w * $this->k, -$h * $this->k, $op));
        }

        public function AddFont($family, $style = '', $file = '')
        {
            //Add a TrueType or Type1 font

            $family = mb_strtolower($family);

            if ('arial' == $family) {
                $family = 'helvetica';
            }

            $style = mb_strtoupper($style);

            if ('IB' == $style) {
                $style = 'BI';
            }

            if (isset($this->fonts[$family . $style])) {
                $this->Error('Font already added: ' . $family . ' ' . $style);
            }

            if ('' == $file) {
                $file = str_replace(' ', '', $family) . mb_strtolower($style) . '.php';
            }

            if (defined('FPDF_FONTPATH')) {
                $file = FPDF_FONTPATH . $file;
            }

            include $file;

            if (!isset($name)) {
                $this->Error('Could not include font definition file');
            }

            $i = count($this->fonts) + 1;

            $this->fonts[$family . $style] = ['i' => $i, 'type' => $type, 'name' => $name, 'desc' => $desc, 'up' => $up, 'ut' => $ut, 'cw' => $cw, 'enc' => $enc, 'file' => $file];

            if ($diff) {
                //Search existing encodings

                $d = 0;

                $nb = count($this->diffs);

                for ($i = 1; $i <= $nb; $i++) {
                    if ($this->diffs[$i] == $diff) {
                        $d = $i;

                        break;
                    }
                }

                if (0 == $d) {
                    $d = $nb + 1;

                    $this->diffs[$d] = $diff;
                }

                $this->fonts[$family . $style]['diff'] = $d;
            }

            if ($file) {
                if ('TrueType' == $type) {
                    $this->FontFiles[$file] = ['length1' => $originalsize];
                } else {
                    $this->FontFiles[$file] = ['length1' => $size1, 'length2' => $size2];
                }
            }
        }

        public function SetFont($family, $style = '', $size = 0)
        {
            //Select a font; size given in points

            global $fpdf_charwidths;

            $family = mb_strtolower($family);

            if ('' == $family) {
                $family = $this->FontFamily;
            }

            if ('arial' == $family) {
                $family = 'helvetica';
            } elseif ('symbol' == $family or 'zapfdingbats' == $family) {
                $style = '';
            }

            $style = mb_strtoupper($style);

            if (is_int(mb_strpos($style, 'U'))) {
                $this->underline = true;

                $style = str_replace('U', '', $style);
            } else {
                $this->underline = false;
            }

            if ('IB' == $style) {
                $style = 'BI';
            }

            if (0 == $size) {
                $size = $this->FontSizePt;
            }

            //Test if font is already selected

            if ($this->FontFamily == $family and $this->FontStyle == $style and $this->FontSizePt == $size) {
                return;
            }

            //Test if used for the first time

            $fontkey = $family . $style;

            if (!isset($this->fonts[$fontkey])) {
                //Check if one of the standard fonts

                if (isset($this->CoreFonts[$fontkey])) {
                    if (!isset($fpdf_charwidths[$fontkey])) {
                        //Load metric file

                        $file = $family;

                        if ('times' == $family or 'helvetica' == $family) {
                            $file .= mb_strtolower($style);
                        }

                        $file .= '.php';

                        if (defined('FPDF_FONTPATH')) {
                            $file = FPDF_FONTPATH . $file;
                        }

                        include $file;

                        if (!isset($fpdf_charwidths[$fontkey])) {
                            $this->Error('Could not include font metric file');
                        }
                    }

                    $i = count($this->fonts) + 1;

                    $this->fonts[$fontkey] = ['i' => $i, 'type' => 'core', 'name' => $this->CoreFonts[$fontkey], 'up' => -100, 'ut' => 50, 'cw' => $fpdf_charwidths[$fontkey]];
                } else {
                    $this->Error('Undefined font: ' . $family . ' ' . $style);
                }
            }

            //Select it

            $this->FontFamily = $family;

            $this->FontStyle = $style;

            $this->FontSizePt = $size;

            $this->FontSize = $size / $this->k;

            $this->CurrentFont = &$this->fonts[$fontkey];

            if ($this->page > 0) {
                $this->_out(sprintf('BT /F%d %.2f Tf ET', $this->CurrentFont['i'], $this->FontSizePt));
            }
        }

        public function SetFontSize($size)
        {
            //Set font size in points

            if ($this->FontSizePt == $size) {
                return;
            }

            $this->FontSizePt = $size;

            $this->FontSize = $size / $this->k;

            if ($this->page > 0) {
                $this->_out(sprintf('BT /F%d %.2f Tf ET', $this->CurrentFont['i'], $this->FontSizePt));
            }
        }

        public function AddLink()
        {
            //Create a new internal link

            $n = count($this->links) + 1;

            $this->links[$n] = [0, 0];

            return $n;
        }

        public function SetLink($link, $y = 0, $page = -1)
        {
            //Set destination of internal link

            if (-1 == $y) {
                $y = $this->y;
            }

            if (-1 == $page) {
                $page = $this->page;
            }

            $this->links[$link] = [$page, $y];
        }

        public function Link($x, $y, $w, $h, $link)
        {
            //Put a link on the page

            $this->PageLinks[$this->page][] = [$x * $this->k, $this->hPt - $y * $this->k, $w * $this->k, $h * $this->k, $link];
        }

        public function Text($x, $y, $txt)
        {
            //Output a string

            $s = sprintf('BT %.2f %.2f Td (%s) Tj ET', $x * $this->k, ($this->h - $y) * $this->k, $this->_escape($txt));

            if ($this->underline and '' != $txt) {
                $s .= ' ' . $this->_dounderline($x, $y, $txt);
            }

            if ($this->ColorFlag) {
                $s = 'q ' . $this->TextColor . ' ' . $s . ' Q';
            }

            $this->_out($s);
        }

        public function AcceptPageBreak()
        {
            //Accept automatic page break or not

            return $this->AutoPageBreak;
        }

        public function Cell($w, $h = 0, $txt = '', $border = 0, $ln = 0, $align = '', $fill = 0, $link = '')
        {
            //Output a cell

            $k = $this->k;

            if ($this->y + $h > $this->PageBreakTrigger and !$this->InFooter and $this->AcceptPageBreak()) {
                //Automatic page break

                $x = $this->x;

                $ws = $this->ws;

                if ($ws > 0) {
                    $this->ws = 0;

                    $this->_out('0 Tw');
                }

                $this->AddPage($this->CurOrientation);

                $this->x = $x;

                if ($ws > 0) {
                    $this->ws = $ws;

                    $this->_out(sprintf('%.3f Tw', $ws * $k));
                }
            }

            if (0 == $w) {
                $w = $this->w - $this->rMargin - $this->x;
            }

            $s = '';

            if (1 == $fill or 1 == $border) {
                if (1 == $fill) {
                    $op = (1 == $border) ? 'B' : 'f';
                } else {
                    $op = 'S';
                }

                $s = sprintf('%.2f %.2f %.2f %.2f re %s ', $this->x * $k, ($this->h - $this->y) * $k, $w * $k, -$h * $k, $op);
            }

            if (is_string($border)) {
                $x = $this->x;

                $y = $this->y;

                if (is_int(mb_strpos($border, 'L'))) {
                    $s .= sprintf('%.2f %.2f m %.2f %.2f l S ', $x * $k, ($this->h - $y) * $k, $x * $k, ($this->h - ($y + $h)) * $k);
                }

                if (is_int(mb_strpos($border, 'T'))) {
                    $s .= sprintf('%.2f %.2f m %.2f %.2f l S ', $x * $k, ($this->h - $y) * $k, ($x + $w) * $k, ($this->h - $y) * $k);
                }

                if (is_int(mb_strpos($border, 'R'))) {
                    $s .= sprintf('%.2f %.2f m %.2f %.2f l S ', ($x + $w) * $k, ($this->h - $y) * $k, ($x + $w) * $k, ($this->h - ($y + $h)) * $k);
                }

                if (is_int(mb_strpos($border, 'B'))) {
                    $s .= sprintf('%.2f %.2f m %.2f %.2f l S ', $x * $k, ($this->h - ($y + $h)) * $k, ($x + $w) * $k, ($this->h - ($y + $h)) * $k);
                }
            }

            if ('' != $txt) {
                if ('R' == $align) {
                    $dx = $w - $this->cMargin - $this->GetStringWidth($txt);
                } elseif ('C' == $align) {
                    $dx = ($w - $this->GetStringWidth($txt)) / 2;
                } else {
                    $dx = $this->cMargin;
                }

                if ($this->ColorFlag) {
                    $s .= 'q ' . $this->TextColor . ' ';
                }

                $txt2 = str_replace(')', '\\)', str_replace('(', '\\(', str_replace('\\', '\\\\', $txt)));

                $s .= sprintf('BT %.2f %.2f Td (%s) Tj ET', ($this->x + $dx) * $k, ($this->h - ($this->y + .5 * $h + .3 * $this->FontSize)) * $k, $txt2);

                if ($this->underline) {
                    $s .= ' ' . $this->_dounderline($this->x + $dx, $this->y + .5 * $h + .3 * $this->FontSize, $txt);
                }

                if ($this->ColorFlag) {
                    $s .= ' Q';
                }

                if ('' != $link) {
                    $this->Link($this->x + $dx, $this->y + .5 * $h - .5 * $this->FontSize, $this->GetStringWidth($txt), $this->FontSize, $link);
                }
            }

            if ($s) {
                $this->_out($s);
            }

            $this->lasth = $h;

            if ($ln > 0) {
                //Go to next line

                $this->y += $h;

                if (1 == $ln) {
                    $this->x = $this->lMargin;
                }
            } else {
                $this->x += $w;
            }
        }

        //EDITEI

        public function MultiCell($w, $h, $txt, $border = 0, $align = 'J', $fill = 0, $link = '')
        {
            //Output text with automatic or explicit line breaks

            $cw = &$this->CurrentFont['cw'];

            if (0 == $w) {
                $w = $this->w - $this->rMargin - $this->x;
            }

            $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;

            $s = str_replace("\r", '', $txt);

            $nb = mb_strlen($s);

            if ($nb > 0 and "\n" == $s[$nb - 1]) {
                $nb--;
            }

            $b = 0;

            if ($border) {
                if (1 == $border) {
                    $border = 'LTRB';

                    $b = 'LRT';

                    $b2 = 'LR';
                } else {
                    $b2 = '';

                    if (is_int(mb_strpos($border, 'L'))) {
                        $b2 .= 'L';
                    }

                    if (is_int(mb_strpos($border, 'R'))) {
                        $b2 .= 'R';
                    }

                    $b = is_int(mb_strpos($border, 'T')) ? $b2 . 'T' : $b2;
                }
            }

            $sep = -1;

            $i = 0;

            $j = 0;

            $l = 0;

            $ns = 0;

            $nl = 1;

            while ($i < $nb) {
                //Get next character

                $c = $s[$i];

                if ("\n" == $c) {
                    //Explicit line break

                    if ($this->ws > 0) {
                        $this->ws = 0;

                        $this->_out('0 Tw');
                    }

                    $this->Cell($w, $h, mb_substr($s, $j, $i - $j), $b, 2, $align, $fill, $link);

                    $i++;

                    $sep = -1;

                    $j = $i;

                    $l = 0;

                    $ns = 0;

                    $nl++;

                    if ($border and 2 == $nl) {
                        $b = $b2;
                    }

                    continue;
                }

                if (' ' == $c) {
                    $sep = $i;

                    $ls = $l;

                    $ns++;
                }

                $l += $cw[$c];

                if ($l > $wmax) {
                    //Automatic line break

                    if (-1 == $sep) {
                        if ($i == $j) {
                            $i++;
                        }

                        if ($this->ws > 0) {
                            $this->ws = 0;

                            $this->_out('0 Tw');
                        }

                        $this->Cell($w, $h, mb_substr($s, $j, $i - $j), $b, 2, $align, $fill, $link);
                    } else {
                        if ('J' == $align) {
                            $this->ws = ($ns > 1) ? ($wmax - $ls) / 1000 * $this->FontSize / ($ns - 1) : 0;

                            $this->_out(sprintf('%.3f Tw', $this->ws * $this->k));
                        }

                        $this->Cell($w, $h, mb_substr($s, $j, $sep - $j), $b, 2, $align, $fill, $link);

                        $i = $sep + 1;
                    }

                    $sep = -1;

                    $j = $i;

                    $l = 0;

                    $ns = 0;

                    $nl++;

                    if ($border and 2 == $nl) {
                        $b = $b2;
                    }
                } else {
                    $i++;
                }
            }

            //Last chunk

            if ($this->ws > 0) {
                $this->ws = 0;

                $this->_out('0 Tw');
            }

            if ($border and is_int(mb_strpos($border, 'B'))) {
                $b .= 'B';
            }

            $this->Cell($w, $h, mb_substr($s, $j, $i - $j), $b, 2, $align, $fill, $link);

            $this->x = $this->lMargin;
        }

        public function Write($h, $txt, $currentx = 0, $link = '', $posarray = 0) //EDITEI
        {
            //EDITEI

            $lastpos = -1;

            $posarray = [];

            //Output text in flowing mode

            $cw = &$this->CurrentFont['cw'];

            $w = $this->w - $this->rMargin - $this->x;

            $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;

            $s = str_replace("\r", '', $txt);

            $nb = mb_strlen($s);

            $sep = -1;

            $i = 0;

            $j = 0;

            $l = 0;

            $nl = 1;

            while ($i < $nb) {
                //Get next character

                $c = $s[$i];

                if ("\n" == $c) {
                    //Explicit line break

                    $this->Cell($w, $h, mb_substr($s, $j, $i - $j), 0, 2, '', 0, $link);

                    $i++;

                    $sep = -1;

                    $j = $i;

                    $l = 0;

                    if (1 == $nl) {
                        if (0 != $currentx) {
                            $this->x = $currentx;
                        }//EDITEI

                        else {
                            $this->x = $this->lMargin;
                        }

                        $w = $this->w - $this->rMargin - $this->x;

                        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
                    }

                    $nl++;

                    continue;
                }

                if (' ' == $c) {
                    $sep = $i;

                    $lastpos = $l;
                }

                $l += $cw[$c];

                if ($l > $wmax) {
                    //Automatic line break

                    if (-1 == $sep) {
                        if ($this->x > $this->lMargin) {
                            //Move to next line

                            if (0 != $currentx) {
                                $this->x = $currentx;
                            }//EDITEI

                            else {
                                $this->x = $this->lMargin;
                            }

                            $this->y += $h;

                            $w = $this->w - $this->rMargin - $this->x;

                            $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;

                            $i++;

                            $nl++;

                            continue;
                        }

                        if ($i == $j) {
                            $i++;
                        }

                        $this->Cell($w, $h, mb_substr($s, $j, $i - $j), 0, 2, '', 0, $link);
                    } else {
                        if (-1 != $lastpos) {
                            $lastpos = ($lastpos * $this->FontSize / 1000) + 2 * $this->cMargin;

                            $posarray[] = $lastpos + $this->x - $this->lMargin;
                        }

                        $this->Cell($w, $h, mb_substr($s, $j, $sep - $j), 0, 2, '', 0, $link);

                        $i = $sep + 1;
                    }

                    $sep = -1;

                    $j = $i;

                    $l = 0;

                    if (1 == $nl) {
                        if (0 != $currentx) {
                            $this->x = $currentx;
                        }//EDITEI

                        else {
                            $this->x = $this->lMargin;
                        }

                        $w = $this->w - $this->rMargin - $this->x;

                        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
                    }

                    $nl++;
                } else {
                    $i++;
                }
            }

            //Last chunk

            if ($i != $j) {
                $this->Cell($l / 1000 * $this->FontSize, $h, mb_substr($s, $j), 0, 0, '', 0, $link);
            }

            //EDITEI (in order to make strike work correcly)

            return $posarray;
        }

        //EDITEI AKI

        //Thanks to Ron Korving for the WordWrap() function

        public function WordWrap(&$text, $maxwidth)
        {
            $biggestword = 0; //EDITEI
            $toonarrow = false; //EDITEI

            $text = trim($text);

            if ('' === $text) {
                return 0;
            }

            $space = $this->GetStringWidth(' ');

            $lines = explode("\n", $text);

            $text = '';

            $count = 0;

            foreach ($lines as $line) {
                $words = preg_preg_split('/ +/', $line);

                $width = 0;

                foreach ($words as $word) {
                    $wordwidth = $this->GetStringWidth($word);

                    //EDITEI

                    //Warn user that maxwidth is insufficient

                    if ($wordwidth > $maxwidth) {
                        if ($wordwidth > $biggestword) {
                            $biggestword = $wordwidth;
                        }

                        $toonarrow = true; //EDITEI
                    }

                    if ($width + $wordwidth <= $maxwidth) {
                        $width += $wordwidth + $space;

                        $text .= $word . ' ';
                    } else {
                        $width = $wordwidth + $space;

                        $text = rtrim($text) . "\n" . $word . ' ';

                        $count++;
                    }
                }

                $text = rtrim($text) . "\n";

                $count++;
            }

            $text = rtrim($text);

            //Return -(wordsize) if word is bigger than maxwidth

            if ($toonarrow) {
                return -$biggestword;
            }
  

            return $count;
        }

        //EDITEI AKI

        //Thanks to Patrick Benny for the MultiCellBlt() function

        public function MultiCellBlt($w, $h, $blt, $txt, $border = 0, $align = 'J', $fill = 0, $link = '')
        {
            //Get bullet width including margins

            $blt_width = $this->GetStringWidth($blt) + $this->cMargin * 2;

            //Save x

            $bak_x = $this->x;

            //Output bullet
            $this->Cell($blt_width + 5, $h, $blt, 0, '', $fill); // EDITEI: +5 to increase indentation
            //Output text
            $this->MultiCell($w - $blt_width, $h, $txt, $border, $align, $fill, $link);

            //Restore x

            $this->x = $bak_x;
        }

        //function Circle() thanks to Olivier PLATHEY

        //EDITEI

        public function Circle($x, $y, $r, $style = '')
        {
            $this->Ellipse($x, $y, $r, $r, $style);
        }

        //function Ellipse() thanks to Olivier PLATHEY

        //EDITEI

        public function Ellipse($x, $y, $rx, $ry, $style = 'D')
        {
            if ('F' == $style) {
                $op = 'f';
            } elseif ('FD' == $style or 'DF' == $style) {
                $op = 'B';
            } else {
                $op = 'S';
            }

            $lx = 4 / 3 * (M_SQRT2 - 1) * $rx;

            $ly = 4 / 3 * (M_SQRT2 - 1) * $ry;

            $k = $this->k;

            $h = $this->h;

            $this->_out(
                sprintf(
                    '%.2f %.2f m %.2f %.2f %.2f %.2f %.2f %.2f c',
                    ($x + $rx) * $k,
                    ($h - $y) * $k,
                    ($x + $rx) * $k,
                    ($h - ($y - $ly)) * $k,
                    ($x + $lx) * $k,
                    ($h - ($y - $ry)) * $k,
                    $x * $k,
                    ($h - ($y - $ry)) * $k
                )
            );

            $this->_out(
                sprintf(
                    '%.2f %.2f %.2f %.2f %.2f %.2f c',
                    ($x - $lx) * $k,
                    ($h - ($y - $ry)) * $k,
                    ($x - $rx) * $k,
                    ($h - ($y - $ly)) * $k,
                    ($x - $rx) * $k,
                    ($h - $y) * $k
                )
            );

            $this->_out(
                sprintf(
                    '%.2f %.2f %.2f %.2f %.2f %.2f c',
                    ($x - $rx) * $k,
                    ($h - ($y + $ly)) * $k,
                    ($x - $lx) * $k,
                    ($h - ($y + $ry)) * $k,
                    $x * $k,
                    ($h - ($y + $ry)) * $k
                )
            );

            $this->_out(
                sprintf(
                    '%.2f %.2f %.2f %.2f %.2f %.2f c %s',
                    ($x + $lx) * $k,
                    ($h - ($y + $ry)) * $k,
                    ($x + $rx) * $k,
                    ($h - ($y + $ly)) * $k,
                    ($x + $rx) * $k,
                    ($h - $y) * $k,
                    $op
                )
            );
        }

        public function Image($file, $x, $y, $w = 0, $h = 0, $type = '', $link = '', $paint = true)
        {
            //Put an image on the page

            if (!isset($this->images[$file])) {
                //First use of image, get info

                if ('' == $type) {
                    $pos = mb_strrpos($file, '.');

                    if (!$pos) {
                        $this->Error('Image file has no extension and no type was specified: ' . $file);
                    }

                    $type = mb_substr($file, $pos + 1);
                }

                $type = mb_strtolower($type);

                $mqr = get_magic_quotes_runtime();

                set_magic_quotes_runtime(0);

                if ('jpg' == $type or 'jpeg' == $type) {
                    $info = $this->_parsejpg($file);
                } elseif ('png' == $type) {
                    $info = $this->_parsepng($file);
                } elseif ('gif' == $type) { //EDITEI - updated
                    $info = $this->_parsegif($file);
                } else {
                    //Allow for additional formats

                    $mtd = '_parse' . $type;

                    if (!method_exists($this, $mtd)) {
                        $this->Error('Unsupported image type: ' . $type);
                    }

                    $info = $this->$mtd($file);
                }

                set_magic_quotes_runtime($mqr);

                $info['i'] = count($this->images) + 1;

                $this->images[$file] = $info;
            } else {
                $info = $this->images[$file];
            }

            //Automatic width and height calculation if needed

            if (0 == $w and 0 == $h) {
                //Put image at 72 dpi

                $w = $info['w'] / $this->k;

                $h = $info['h'] / $this->k;
            }

            if (0 == $w) {
                $w = $h * $info['w'] / $info['h'];
            }

            if (0 == $h) {
                $h = $w * $info['h'] / $info['w'];
            }

            $changedpage = false; //EDITEI

            //Avoid drawing out of the paper(exceeding width limits). //EDITEI

            if (($x + $w) > $this->fw) {
                $x = $this->lMargin;

                $y += 5;
            }

            //Avoid drawing out of the page. //EDITEI

            if (($y + $h) > $this->fh) {
                $this->AddPage();

                $y = $tMargin + 10; // +10 to avoid drawing too close to border of page

                $changedpage = true;
            }

            $outstring = sprintf('q %.2f 0 0 %.2f %.2f %.2f cm /I%d Do Q', $w * $this->k, $h * $this->k, $x * $this->k, ($this->h - ($y + $h)) * $this->k, $info['i']);

            if ($paint) { //EDITEI
                $this->_out($outstring);

                if ($link) {
                    $this->Link($x, $y, $w, $h, $link);
                }
            }

            //Avoid writing text on top of the image. //EDITEI

            if ($changedpage) {
                $this->y = $y + $h;
            } else {
                $this->y = $y + $h;
            }

            //Return width-height array //EDITEI

            $sizesarray['WIDTH'] = $w;

            $sizesarray['HEIGHT'] = $h;

            $sizesarray['X'] = $x; //Position before painting image
            $sizesarray['Y'] = $y; //Position before painting image
            $sizesarray['OUTPUT'] = $outstring;

            return $sizesarray;
        }

        //EDITEI - Done after reading a little about PDF reference guide

        public function DottedRect($x = 100, $y = 150, $w = 50, $h = 50)
        {
            $x *= $this->k;

            $y = ($this->h - $y) * $this->k;

            $w *= $this->k;

            $h *= $this->k; // - h?

            $herex = $x;

            $herey = $y;

            //Make fillcolor == drawcolor

            $bak_fill = $this->FillColor;

            $this->FillColor = $this->DrawColor;

            $this->FillColor = str_replace('RG', 'rg', $this->FillColor);

            $this->_out($this->FillColor);

            while ($herex < ($x + $w)) { //draw from upper left to upper right
                $this->DrawDot($herex, $herey);

                $herex += (3 * $this->k);
            }

            $herex = $x + $w;

            while ($herey > ($y - $h)) { //draw from upper right to lower right
                $this->DrawDot($herex, $herey);

                $herey -= (3 * $this->k);
            }

            $herey = $y - $h;

            while ($herex > $x) { //draw from lower right to lower left
                $this->DrawDot($herex, $herey);

                $herex -= (3 * $this->k);
            }

            $herex = $x;

            while ($herey < $y) { //draw from lower left to upper left
                $this->DrawDot($herex, $herey);

                $herey += (3 * $this->k);
            }

            $herey = $y;

            $this->FillColor = $bak_fill;

            $this->_out($this->FillColor); //return fillcolor back to normal
        }

        //EDITEI - Done after reading a little about PDF reference guide
        public function DrawDot($x, $y) //center x y
        {
            $op = 'B'; // draw Filled Dots
            //F == fill //S == stroke //B == stroke and fill
            $r = 0.5 * $this->k;  //raio

            //Start Point

            $x1 = $x - $r;

            $y1 = $y;

            //End Point

            $x2 = $x + $r;

            $y2 = $y;

            //Auxiliar Point

            $x3 = $x;

            $y3 = $y + (2 * $r); // 2*raio to make a round (not oval) shape

            //Round join and cap

            $s = "\n" . '1 J' . "\n";

            $s .= '1 j' . "\n";

            //Upper circle
            $s .= sprintf('%.3f %.3f m' . "\n", $x1, $y1); //x y start drawing
            $s .= sprintf('%.3f %.3f %.3f %.3f %.3f %.3f c' . "\n", $x1, $y1, $x3, $y3, $x2, $y2); //Bezier curve
            //Lower circle
            $y3 = $y - (2 * $r);

            $s .= sprintf("\n" . '%.3f %.3f m' . "\n", $x1, $y1); //x y start drawing

            $s .= sprintf('%.3f %.3f %.3f %.3f %.3f %.3f c' . "\n", $x1, $y1, $x3, $y3, $x2, $y2);

            $s .= $op . "\n"; //stroke and fill

            //Draw in PDF file

            $this->_out($s);
        }

        public function SetDash($black = false, $white = false)
        {
            if ($black and $white) {
                $s = sprintf('[%.3f %.3f] 0 d', $black * $this->k, $white * $this->k);
            } else {
                $s = '[] 0 d';
            }

            $this->_out($s);
        }

        public function Ln($h = '')
        {
            //Line feed; default value is last cell height

            $this->x = $this->lMargin;

            if (is_string($h)) {
                $this->y += $this->lasth;
            } else {
                $this->y += $h;
            }
        }

        public function GetX()
        {
            //Get x position

            return $this->x;
        }

        public function SetX($x)
        {
            //Set x position

            if ($x >= 0) {
                $this->x = $x;
            } else {
                $this->x = $this->w + $x;
            }
        }

        public function GetY()
        {
            //Get y position

            return $this->y;
        }

        public function SetY($y)
        {
            //Set y position and reset x

            $this->x = $this->lMargin;

            if ($y >= 0) {
                $this->y = $y;
            } else {
                $this->y = $this->h + $y;
            }
        }

        public function SetXY($x, $y)
        {
            //Set x and y positions

            $this->SetY($y);

            $this->SetX($x);
        }

        public function Output($name = '', $dest = '')
        {
            //Output PDF to some destination

            global $HTTP_SERVER_VARS;

            //Finish document if necessary

            if ($this->state < 3) {
                $this->Close();
            }

            //Normalize parameters

            if (is_bool($dest)) {
                $dest = $dest ? 'D' : 'F';
            }

            $dest = mb_strtoupper($dest);

            if ('' == $dest) {
                if ('' == $name) {
                    $name = 'doc.pdf';

                    $dest = 'I';
                } else {
                    $dest = 'F';
                }
            }

            switch ($dest) {
                case 'I':
                    //Send to standard output
                    if (isset($HTTP_SERVER_VARS['SERVER_NAME'])) {
                        //We send to a browser

                        header('Content-Type: application/pdf');

                        if (headers_sent()) {
                            $this->Error('Some data has already been output to browser, can\'t send PDF file');
                        }

                        header('Content-Length: ' . mb_strlen($this->buffer));

                        header('Content-disposition: inline; filename=' . $name);
                    }
                    echo $this->buffer;
                    break;
                case 'D':
                    //Download file
                    if (isset($HTTP_SERVER_VARS['HTTP_USER_AGENT']) and mb_strpos($HTTP_SERVER_VARS['HTTP_USER_AGENT'], 'MSIE')) {
                        header('Content-Type: application/force-download');
                    } else {
                        header('Content-Type: application/octet-stream');
                    }
                    if (headers_sent()) {
                        $this->Error('Some data has already been output to browser, can\'t send PDF file');
                    }
                    header('Content-Length: ' . mb_strlen($this->buffer));
                    header('Content-disposition: attachment; filename=' . $name);
                    echo $this->buffer;
                    break;
                case 'F':
                    //Save to local file
                    $f = fopen($name, 'wb');
                    if (!$f) {
                        $this->Error('Unable to create output file: ' . $name);
                    }
                    fwrite($f, $this->buffer, mb_strlen($this->buffer));
                    fclose($f);
                    break;
                case 'S':
                    //Return as a string
                    return $this->buffer;
                default:
                    $this->Error('Incorrect output destination: ' . $dest);
            }

            return '';
        }

        /*******************************************************************************
         *                                                                              *
         *                              Protected methods                               *
         *                                                                              *
         *******************************************************************************/

        public function _dochecks()
        {
            //Check for locale-related bug

            if (1.1 == 1) {
                $this->Error('Don\'t alter the locale before including class file');
            }

            //Check for decimal separator

            if ('1.0' != sprintf('%.1f', 1.0)) {
                setlocale(LC_NUMERIC, 'C');
            }
        }

        public function _begindoc()
        {
            //Start document

            $this->state = 1;

            $this->_out('%PDF-1.3');
        }

        public function _putpages()
        {
            $nb = $this->page;

            if (!empty($this->AliasNbPages)) {
                //Replace number of pages

                for ($n = 1; $n <= $nb; $n++) {
                    $this->pages[$n] = str_replace($this->AliasNbPages, $nb, $this->pages[$n]);
                }
            }

            if ('P' == $this->DefOrientation) {
                $wPt = $this->fwPt;

                $hPt = $this->fhPt;
            } else {
                $wPt = $this->fhPt;

                $hPt = $this->fwPt;
            }

            $filter = ($this->compress) ? '/Filter /FlateDecode ' : '';

            for ($n = 1; $n <= $nb; $n++) {
                //Page

                $this->_newobj();

                $this->_out('<</Type /Page');

                $this->_out('/Parent 1 0 R');

                if (isset($this->OrientationChanges[$n])) {
                    $this->_out(sprintf('/MediaBox [0 0 %.2f %.2f]', $hPt, $wPt));
                }

                $this->_out('/Resources 2 0 R');

                if (isset($this->PageLinks[$n])) {
                    //Links

                    $annots = '/Annots [';

                    foreach ($this->PageLinks[$n] as $pl) {
                        $rect = sprintf('%.2f %.2f %.2f %.2f', $pl[0], $pl[1], $pl[0] + $pl[2], $pl[1] - $pl[3]);

                        $annots .= '<</Type /Annot /Subtype /Link /Rect [' . $rect . '] /Border [0 0 0] ';

                        if (is_string($pl[4])) {
                            $annots .= '/A <</S /URI /URI ' . $this->_textstring($pl[4]) . '>>>>';
                        } else {
                            $l = $this->links[$pl[4]];

                            $h = isset($this->OrientationChanges[$l[0]]) ? $wPt : $hPt;

                            $annots .= sprintf('/Dest [%d 0 R /XYZ 0 %.2f null]>>', 1 + 2 * $l[0], $h - $l[1] * $this->k);
                        }
                    }

                    $this->_out($annots . ']');
                }

                $this->_out('/Contents ' . ($this->n + 1) . ' 0 R>>');

                $this->_out('endobj');

                //Page content

                $p = ($this->compress) ? gzcompress($this->pages[$n]) : $this->pages[$n];

                $this->_newobj();

                $this->_out('<<' . $filter . '/Length ' . mb_strlen($p) . '>>');

                $this->_putstream($p);

                $this->_out('endobj');
            }

            //Pages root

            $this->offsets[1] = mb_strlen($this->buffer);

            $this->_out('1 0 obj');

            $this->_out('<</Type /Pages');

            $kids = '/Kids [';

            for ($i = 0; $i < $nb; $i++) {
                $kids .= (3 + 2 * $i) . ' 0 R ';
            }

            $this->_out($kids . ']');

            $this->_out('/Count ' . $nb);

            $this->_out(sprintf('/MediaBox [0 0 %.2f %.2f]', $wPt, $hPt));

            $this->_out('>>');

            $this->_out('endobj');
        }

        public function _putfonts()
        {
            $nf = $this->n;

            foreach ($this->diffs as $diff) {
                //Encodings

                $this->_newobj();

                $this->_out('<</Type /Encoding /BaseEncoding /WinAnsiEncoding /Differences [' . $diff . ']>>');

                $this->_out('endobj');
            }

            $mqr = get_magic_quotes_runtime();

            set_magic_quotes_runtime(0);

            foreach ($this->FontFiles as $file => $info) {
                //Font file embedding

                $this->_newobj();

                $this->FontFiles[$file]['n'] = $this->n;

                if (defined('FPDF_FONTPATH')) {
                    $file = FPDF_FONTPATH . $file;
                }

                $size = filesize($file);

                if (!$size) {
                    $this->Error('Font file not found');
                }

                $this->_out('<</Length ' . $size);

                if ('.z' == mb_substr($file, -2)) {
                    $this->_out('/Filter /FlateDecode');
                }

                $this->_out('/Length1 ' . $info['length1']);

                if (isset($info['length2'])) {
                    $this->_out('/Length2 ' . $info['length2'] . ' /Length3 0');
                }

                $this->_out('>>');

                $f = fopen($file, 'rb');

                $this->_putstream(fread($f, $size));

                fclose($f);

                $this->_out('endobj');
            }

            set_magic_quotes_runtime($mqr);

            foreach ($this->fonts as $k => $font) {
                //Font objects

                $this->fonts[$k]['n'] = $this->n + 1;

                $type = $font['type'];

                $name = $font['name'];

                if ('core' == $type) {
                    //Standard font

                    $this->_newobj();

                    $this->_out('<</Type /Font');

                    $this->_out('/BaseFont /' . $name);

                    $this->_out('/Subtype /Type1');

                    if ('Symbol' != $name and 'ZapfDingbats' != $name) {
                        $this->_out('/Encoding /WinAnsiEncoding');
                    }

                    $this->_out('>>');

                    $this->_out('endobj');
                } elseif ('Type1' == $type or 'TrueType' == $type) {
                    //Additional Type1 or TrueType font

                    $this->_newobj();

                    $this->_out('<</Type /Font');

                    $this->_out('/BaseFont /' . $name);

                    $this->_out('/Subtype /' . $type);

                    $this->_out('/FirstChar 32 /LastChar 255');

                    $this->_out('/Widths ' . ($this->n + 1) . ' 0 R');

                    $this->_out('/FontDescriptor ' . ($this->n + 2) . ' 0 R');

                    if ($font['enc']) {
                        if (isset($font['diff'])) {
                            $this->_out('/Encoding ' . ($nf + $font['diff']) . ' 0 R');
                        } else {
                            $this->_out('/Encoding /WinAnsiEncoding');
                        }
                    }

                    $this->_out('>>');

                    $this->_out('endobj');

                    //Widths

                    $this->_newobj();

                    $cw = &$font['cw'];

                    $s = '[';

                    for ($i = 32; $i <= 255; $i++) {
                        $s .= $cw[chr($i)] . ' ';
                    }

                    $this->_out($s . ']');

                    $this->_out('endobj');

                    //Descriptor

                    $this->_newobj();

                    $s = '<</Type /FontDescriptor /FontName /' . $name;

                    foreach ($font['desc'] as $k => $v) {
                        $s .= ' /' . $k . ' ' . $v;
                    }

                    $file = $font['file'];

                    if ($file) {
                        $s .= ' /FontFile' . ('Type1' == $type ? '' : '2') . ' ' . $this->FontFiles[$file]['n'] . ' 0 R';
                    }

                    $this->_out($s . '>>');

                    $this->_out('endobj');
                } else {
                    //Allow for additional types

                    $mtd = '_put' . mb_strtolower($type);

                    if (!method_exists($this, $mtd)) {
                        $this->Error('Unsupported font type: ' . $type);
                    }

                    $this->$mtd($font);
                }
            }
        }

        public function _putimages()
        {
            $filter = ($this->compress) ? '/Filter /FlateDecode ' : '';

            reset($this->images);

            while (list($file, $info) = each($this->images)) {
                $this->_newobj();

                $this->images[$file]['n'] = $this->n;

                $this->_out('<</Type /XObject');

                $this->_out('/Subtype /Image');

                $this->_out('/Width ' . $info['w']);

                $this->_out('/Height ' . $info['h']);

                if ('Indexed' == $info['cs']) {
                    $this->_out('/ColorSpace [/Indexed /DeviceRGB ' . (mb_strlen($info['pal']) / 3 - 1) . ' ' . ($this->n + 1) . ' 0 R]');
                } else {
                    $this->_out('/ColorSpace /' . $info['cs']);

                    if ('DeviceCMYK' == $info['cs']) {
                        $this->_out('/Decode [1 0 1 0 1 0 1 0]');
                    }
                }

                $this->_out('/BitsPerComponent ' . $info['bpc']);

                $this->_out('/Filter /' . $info['f']);

                if (isset($info['parms'])) {
                    $this->_out($info['parms']);
                }

                if (isset($info['trns']) and is_array($info['trns'])) {
                    $trns = '';

                    for ($i = 0, $iMax = count($info['trns']); $i < $iMax; $i++) {
                        $trns .= $info['trns'][$i] . ' ' . $info['trns'][$i] . ' ';
                    }

                    $this->_out('/Mask [' . $trns . ']');
                }

                $this->_out('/Length ' . mb_strlen($info['data']) . '>>');

                $this->_putstream($info['data']);

                unset($this->images[$file]['data']);

                $this->_out('endobj');

                //Palette

                if ('Indexed' == $info['cs']) {
                    $this->_newobj();

                    $pal = ($this->compress) ? gzcompress($info['pal']) : $info['pal'];

                    $this->_out('<<' . $filter . '/Length ' . mb_strlen($pal) . '>>');

                    $this->_putstream($pal);

                    $this->_out('endobj');
                }
            }
        }

        public function _putresources()
        {
            $this->_putfonts();

            $this->_putimages();

            //Resource dictionary

            $this->offsets[2] = mb_strlen($this->buffer);

            $this->_out('2 0 obj');

            $this->_out('<</ProcSet [/PDF /Text /ImageB /ImageC /ImageI]');

            $this->_out('/Font <<');

            foreach ($this->fonts as $font) {
                $this->_out('/F' . $font['i'] . ' ' . $font['n'] . ' 0 R');
            }

            $this->_out('>>');

            if (count($this->images)) {
                $this->_out('/XObject <<');

                foreach ($this->images as $image) {
                    $this->_out('/I' . $image['i'] . ' ' . $image['n'] . ' 0 R');
                }

                $this->_out('>>');
            }

            $this->_out('>>');

            $this->_out('endobj');
        }

        public function _putinfo()
        {
            $this->_out('/Producer ' . $this->_textstring('FPDF ' . FPDF_VERSION));

            if (!empty($this->title)) {
                $this->_out('/Title ' . $this->_textstring($this->title));
            }

            if (!empty($this->subject)) {
                $this->_out('/Subject ' . $this->_textstring($this->subject));
            }

            if (!empty($this->author)) {
                $this->_out('/Author ' . $this->_textstring($this->author));
            }

            if (!empty($this->keywords)) {
                $this->_out('/Keywords ' . $this->_textstring($this->keywords));
            }

            if (!empty($this->creator)) {
                $this->_out('/Creator ' . $this->_textstring($this->creator));
            }

            $this->_out('/CreationDate ' . $this->_textstring('D:' . date('YmdHis')));
        }

        public function _putcatalog()
        {
            $this->_out('/Type /Catalog');

            $this->_out('/Pages 1 0 R');

            if ('fullpage' == $this->ZoomMode) {
                $this->_out('/OpenAction [3 0 R /Fit]');
            } elseif ('fullwidth' == $this->ZoomMode) {
                $this->_out('/OpenAction [3 0 R /FitH null]');
            } elseif ('real' == $this->ZoomMode) {
                $this->_out('/OpenAction [3 0 R /XYZ null null 1]');
            } elseif (!is_string($this->ZoomMode)) {
                $this->_out('/OpenAction [3 0 R /XYZ null null ' . ($this->ZoomMode / 100) . ']');
            }

            if ('single' == $this->LayoutMode) {
                $this->_out('/PageLayout /SinglePage');
            } elseif ('continuous' == $this->LayoutMode) {
                $this->_out('/PageLayout /OneColumn');
            } elseif ('two' == $this->LayoutMode) {
                $this->_out('/PageLayout /TwoColumnLeft');
            }
        }

        public function _puttrailer()
        {
            $this->_out('/Size ' . ($this->n + 1));

            $this->_out('/Root ' . $this->n . ' 0 R');

            $this->_out('/Info ' . ($this->n - 1) . ' 0 R');
        }

        public function _enddoc()
        {
            $this->_putpages();

            $this->_putresources();

            //Info

            $this->_newobj();

            $this->_out('<<');

            $this->_putinfo();

            $this->_out('>>');

            $this->_out('endobj');

            //Catalog

            $this->_newobj();

            $this->_out('<<');

            $this->_putcatalog();

            $this->_out('>>');

            $this->_out('endobj');

            //Cross-ref

            $o = mb_strlen($this->buffer);

            $this->_out('xref');

            $this->_out('0 ' . ($this->n + 1));

            $this->_out('0000000000 65535 f ');

            for ($i = 1; $i <= $this->n; $i++) {
                $this->_out(sprintf('%010d 00000 n ', $this->offsets[$i]));
            }

            //Trailer

            $this->_out('trailer');

            $this->_out('<<');

            $this->_puttrailer();

            $this->_out('>>');

            $this->_out('startxref');

            $this->_out($o);

            $this->_out('%%EOF');

            $this->state = 3;
        }

        public function _beginpage($orientation)
        {
            $this->page++;

            $this->pages[$this->page] = '';

            $this->state = 2;

            $this->x = $this->lMargin;

            $this->y = $this->tMargin;

            $this->FontFamily = '';

            //Page orientation

            if (!$orientation) {
                $orientation = $this->DefOrientation;
            } else {
                $orientation = mb_strtoupper($orientation[0]);

                if ($orientation != $this->DefOrientation) {
                    $this->OrientationChanges[$this->page] = true;
                }
            }

            if ($orientation != $this->CurOrientation) {
                //Change orientation

                if ('P' == $orientation) {
                    $this->wPt = $this->fwPt;

                    $this->hPt = $this->fhPt;

                    $this->w = $this->fw;

                    $this->h = $this->fh;
                } else {
                    $this->wPt = $this->fhPt;

                    $this->hPt = $this->fwPt;

                    $this->w = $this->fh;

                    $this->h = $this->fw;
                }

                $this->PageBreakTrigger = $this->h - $this->bMargin;

                $this->CurOrientation = $orientation;
            }
        }

        public function _endpage()
        {
            //End of page contents

            $this->state = 1;
        }

        public function _newobj()
        {
            //Begin a new object

            $this->n++;

            $this->offsets[$this->n] = mb_strlen($this->buffer);

            $this->_out($this->n . ' 0 obj');
        }

        public function _dounderline($x, $y, $txt)
        {
            //Underline text

            $up = $this->CurrentFont['up'];

            $ut = $this->CurrentFont['ut'];

            $w = $this->GetStringWidth($txt) + $this->ws * mb_substr_count($txt, ' ');

            return sprintf('%.2f %.2f %.2f %.2f re f', $x * $this->k, ($this->h - ($y - $up / 1000 * $this->FontSize)) * $this->k, $w * $this->k, -$ut / 1000 * $this->FontSizePt);
        }

        public function _parsejpg($file)
        {
            //Extract info from a JPEG file

            $a = getimagesize($file);

            if (!$a) {
                $this->Error('Missing or incorrect image file: ' . $file);
            }

            if (2 != $a[2]) {
                $this->Error('Not a JPEG file: ' . $file);
            }

            if (!isset($a['channels']) or 3 == $a['channels']) {
                $colspace = 'DeviceRGB';
            } elseif (4 == $a['channels']) {
                $colspace = 'DeviceCMYK';
            } else {
                $colspace = 'DeviceGray';
            }

            $bpc = $a['bits'] ?? 8;

            //Read whole file

            $f = fopen($file, 'rb');

            $data = '';

            while (!feof($f)) {
                $data .= fread($f, 4096);
            }

            fclose($f);

            return ['w' => $a[0], 'h' => $a[1], 'cs' => $colspace, 'bpc' => $bpc, 'f' => 'DCTDecode', 'data' => $data];
        }

        public function _parsepng($file)
        {
            //Extract info from a PNG file

            $f = fopen($file, 'rb');

            if (!$f) {
                $this->Error('Can\'t open image file: ' . $file);
            }

            //Check signature

            if (fread($f, 8) != chr(137) . 'PNG' . chr(13) . chr(10) . chr(26) . chr(10)) {
                $this->Error('Not a PNG file: ' . $file);
            }

            //Read header chunk

            fread($f, 4);

            if ('IHDR' != fread($f, 4)) {
                $this->Error('Incorrect PNG file: ' . $file);
            }

            $w = $this->_freadint($f);

            $h = $this->_freadint($f);

            $bpc = ord(fread($f, 1));

            if ($bpc > 8) {
                $this->Error('16-bit depth not supported: ' . $file);
            }

            $ct = ord(fread($f, 1));

            if (0 == $ct) {
                $colspace = 'DeviceGray';
            } elseif (2 == $ct) {
                $colspace = 'DeviceRGB';
            } elseif (3 == $ct) {
                $colspace = 'Indexed';
            } else {
                $this->Error('Alpha channel not supported: ' . $file);
            }

            if (0 != ord(fread($f, 1))) {
                $this->Error('Unknown compression method: ' . $file);
            }

            if (0 != ord(fread($f, 1))) {
                $this->Error('Unknown filter method: ' . $file);
            }

            if (0 != ord(fread($f, 1))) {
                $this->Error('Interlacing not supported: ' . $file);
            }

            fread($f, 4);

            $parms = '/DecodeParms <</Predictor 15 /Colors ' . (2 == $ct ? 3 : 1) . ' /BitsPerComponent ' . $bpc . ' /Columns ' . $w . '>>';

            //Scan chunks looking for palette, transparency and image data

            $pal = '';

            $trns = '';

            $data = '';

            do {
                $n = $this->_freadint($f);

                $type = fread($f, 4);

                if ('PLTE' == $type) {
                    //Read palette

                    $pal = fread($f, $n);

                    fread($f, 4);
                } elseif ('tRNS' == $type) {
                    //Read transparency info

                    $t = fread($f, $n);

                    if (0 == $ct) {
                        $trns = [ord(mb_substr($t, 1, 1))];
                    } elseif (2 == $ct) {
                        $trns = [ord(mb_substr($t, 1, 1)), ord(mb_substr($t, 3, 1)), ord(mb_substr($t, 5, 1))];
                    } else {
                        $pos = mb_strpos($t, chr(0));

                        if (is_int($pos)) {
                            $trns = [$pos];
                        }
                    }

                    fread($f, 4);
                } elseif ('IDAT' == $type) {
                    //Read image data block

                    $data .= fread($f, $n);

                    fread($f, 4);
                } elseif ('IEND' == $type) {
                    break;
                } else {
                    fread($f, $n + 4);
                }
            } while ($n);

            if ('Indexed' == $colspace and empty($pal)) {
                $this->Error('Missing palette in ' . $file);
            }

            fclose($f);

            return ['w' => $w, 'h' => $h, 'cs' => $colspace, 'bpc' => $bpc, 'f' => 'FlateDecode', 'parms' => $parms, 'pal' => $pal, 'trns' => $trns, 'data' => $data];
        }

        public function _parsegif($file) //EDITEI - updated
        {
            //Function by Jérôme Fenal
            require_once __DIR__ . '/gif.php'; //GIF class in pure PHP from Yamasoft (formerly at http://www.yamasoft.com)

            $h = 0;

            $w = 0;

            $gif = new CGIF();

            if (!$gif->loadFile($file, 0)) {
                $this->Error("GIF parser: unable to open file $file");
            }

            if ($gif->m_img->m_gih->m_bLocalClr) {
                $nColors = $gif->m_img->m_gih->m_nTableSize;

                $pal = $gif->m_img->m_gih->m_colorTable->toString();

                if (-1 != $bgColor) {
                    $bgColor = $gif->m_img->m_gih->m_colorTable->colorIndex($bgColor);
                }

                $colspace = 'Indexed';
            } elseif ($gif->m_gfh->m_bGlobalClr) {
                $nColors = $gif->m_gfh->m_nTableSize;

                $pal = $gif->m_gfh->m_colorTable->toString();

                if (-1 != $bgColor) {
                    $bgColor = $gif->m_gfh->m_colorTable->colorIndex($bgColor);
                }

                $colspace = 'Indexed';
            } else {
                $nColors = 0;

                $bgColor = -1;

                $colspace = 'DeviceGray';

                $pal = '';
            }

            $trns = '';

            if ($gif->m_img->m_bTrans && ($nColors > 0)) {
                $trns = [$gif->m_img->m_nTrans];
            }

            $data = $gif->m_img->m_data;

            $w = $gif->m_gfh->m_nWidth;

            $h = $gif->m_gfh->m_nHeight;

            if ('Indexed' == $colspace and empty($pal)) {
                $this->Error('Missing palette in ' . $file);
            }

            if ($this->compress) {
                $data = gzcompress($data);

                return ['w' => $w, 'h' => $h, 'cs' => $colspace, 'bpc' => 8, 'f' => 'FlateDecode', 'pal' => $pal, 'trns' => $trns, 'data' => $data];
            }
  

            return ['w' => $w, 'h' => $h, 'cs' => $colspace, 'bpc' => 8, 'pal' => $pal, 'trns' => $trns, 'data' => $data];
        }

        public function _freadint($f)
        {
            //Read a 4-byte integer from file

            $i = ord(fread($f, 1)) << 24;

            $i += ord(fread($f, 1)) << 16;

            $i += ord(fread($f, 1)) << 8;

            $i += ord(fread($f, 1));

            return $i;
        }

        public function _textstring($s)
        {
            //Format a text string

            return '(' . $this->_escape($s) . ')';
        }

        public function _escape($s)
        {
            //Add \ before \, ( and )

            return str_replace(')', '\\)', str_replace('(', '\\(', str_replace('\\', '\\\\', $s)));
        }

        public function _putstream($s)
        {
            $this->_out('stream');

            $this->_out($s);

            $this->_out('endstream');
        }

        public function _out($s)
        {
            //Add a line to the document

            if (2 == $this->state) {
                $this->pages[$this->page] .= $s . "\n";
            } else {
                $this->buffer .= $s . "\n";
            }
        }

        //End of class
    }

    //Handle special IE contype request

    if (isset($HTTP_SERVER_VARS['HTTP_USER_AGENT']) and 'contype' == $HTTP_SERVER_VARS['HTTP_USER_AGENT']) {
        header('Content-Type: application/pdf');

        exit;
    }
}
