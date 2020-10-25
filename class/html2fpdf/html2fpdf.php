<?php
/*
*** General-use version

//////////////////////////////////////////////////////////////////////////////
//////////////DO NOT MODIFY THE CONTENTS OF THIS BOX//////////////////////////
//////////////////////////////////////////////////////////////////////////////
//                                                                          //
// HTML2FPDF is a php script to read a HTML text and generate a PDF file.   //
// Copyright (C) 2004 Renato Coelho                                         //
// This script may be distributed as long as the following files are kept   //
// together: 								                                                //
//	                          					                                    //
// fpdf.php, html2fpdf.php, gif.php, license.txt,credits.txt,htmltoolkit.php//
//                                                                          //
//////////////////////////////////////////////////////////////////////////////

TODO:
- Increase number of CSS/HTML tags/properties, Image/Font Types, recognized/supported
- (there are redundant functions?!? or not?)(countline & numberline?)
- this script does not support table inside table...
- paragraphs (<p>) wont split when they exceed page size, they skip the remaining
space and appear on the other page...
- CSS + align = bug!
- obs: para textos de mais de 1 página, talvez tenha que juntar varios $texto_artigo
antes de mandar gerar o PDF, para que o PDF gerado seja completo.
- Alignment not working properly in all cases
- Make CSS available to more tags (only works on p,b,u,i,div).

OBS0-1: Default font: Arial << make it changeable by outside?
OBS0-2: there are 2 types of spaces 32 and 160 (ascii values)
OBS0-3: //! is a special comment to be used with source2doc.php, a script I created
in order to generate the doc on the site html2fpdf.sf.net
OBS1: var $LineWidth;         line width in user unit   to make css thin/medium/thick difference?
OBS2: Images and DIVs: when they are inserted you can only type below them (==display:block)
OBS3: Optimized to 'A4' paper (default font: Arial , normal , size 11 )
OBS4: Regexp + Perl ([preg]accepts non-greedy quantifiers while PHP[ereg] does not)
Perl:  '/regexp/x'  where x = option ( x = i:ignore case , x = s: DOT gets \n as well)
*/

require_once __DIR__ . '/fpdf.php';
require_once __DIR__ . '/htmltoolkit.php';

class HTML2FPDF extends FPDF
{
    //internal attributes
    public $HREF; //! string
    public $pgwidth; //! float
    public $fontlist; //! array
    public $issetfont; //! bool
    public $issetcolor; //! bool
    public $titulo; //! string
    public $oldx; //! float
    public $oldy; //! float
    public $B; //! int
    public $U; //! int
    public $I; //! int
    public $tablestart; //! bool
    public $tdbegin; //! bool
    public $table; //! array
    public $cell; //! array
    public $col; //! int
    public $row; //! int
    public $divbegin; //! bool
    public $divalign; //! char
    public $divwidth; //! float
    public $divheight; //! float
    public $divbgcolor; //! bool
    public $divcolor; //! bool
    public $divborder; //! int
    public $divrevert; //! bool
    public $issetlist; //! int
    public $orderedlist; //! int
    public $li; //! bool
    public $listbegin; //! bool
    public $pbegin; //! bool
    public $pjustfinished; //! bool
    public $SUP; //! bool
    public $SUB; //! bool
    public $centertag; //! bool
    public $addresstag; //! bool
    public $toupper; //! bool
    public $tolower; //! bool
    public $dash_on; //! bool
    public $dotted_on; //! bool
    public $strike; //! bool
    public $CSS; //! array
    public $textbuffer; //! array
    public $cssbegin; //! bool
    public $currentstyle; //! string
    public $currentfont; //! string
    public $colorarray; //! array
    public $internallink; //! array
    public $enabledtags; //! string
    public $frm_textarea; //! bool
    public $frm_select; //! bool
    //options attributes
    public $usetitle; //! bool
    public $usecss; //! bool
    public $usepre; //! bool

    public function __construct($orientation = 'P', $unit = 'mm', $format = 'A4')
    {
        //! @desc Constructor

        //! @return A class instance

        //Call parent constructor

        parent::__construct($orientation, $unit, $format);

        //To make the function Footer() work properly

        $this->AliasNbPages();

        //Enable all tags as default

        $this->DisableTags();

        //Initialization of the attributes
        $this->SetFont('Arial', '', 11); // Changeable
        $this->pgwidth = $this->fw - $this->lMargin - $this->rMargin;

        $this->HREF = '';

        $this->titulo = '';

        $this->oldx = -1;

        $this->oldy = -1;

        $this->B = 0;

        $this->U = 0;

        $this->I = 0;

        $this->issetlist = 0;

        $this->orderedlist = 0;

        $this->li = false;

        $this->listbegin = false;

        $this->tablestart = false;

        $this->tdbegin = false;

        $this->table = [];

        $this->cell = [];

        $this->col = -1;

        $this->row = -1;

        $this->divbegin = false;

        $this->divalign = 'L';

        $this->divwidth = 0;

        $this->divheight = 0;

        $this->divbgcolor = false;

        $this->divcolor = false;

        $this->divborder = 0;

        $this->divrevert = false;

        //On DIV mode: display == block automatically

        $this->fontlist = ['arial', 'times', 'courier', 'helvetica', 'symbol'];

        $this->issetfont = false;

        $this->issetcolor = false;

        $this->pbegin = false;

        $this->pjustfinished = false;

        $this->toupper = false;

        $this->tolower = false;

        $this->dash_on = false;

        $this->dotted_on = false;

        $this->SUP = false;

        $this->SUB = false;

        $this->centertag = false;

        $this->addresstag = false;

        $this->strike = false;

        $this->currentfont = '';

        $this->currentstyle = '';

        $this->colorarray = [];

        $this->cssbegin = false;

        $this->textbuffer = [];

        $this->CSS = [];

        $this->internallink = [];

        $this->frm_textarea = false;

        $this->frm_select = false;

        //options attributes

        $this->usetitle = true;

        $this->usecss = true;

        $this->usepre = true;
    }

    public function UseTitle($opt = true)
    {
        //! @desc Enable/Disable title tag recognition

        //! @return void

        $this->usetitle = $opt;
    }

    public function UseCSS($opt = true)
    {
        //! @desc Enable/Disable style tag recognition

        //! @return void

        $this->usecss = $opt;
    }

    public function UsePRE($opt = true)
    {
        //! @desc Enable/Disable pre tag recognition

        //! @return void

        $this->usepre = $opt;
    }

    //Page header

    public function Header()
    {
        //! @return void

        if ('' != $this->titulo) {
            //Arial bold 16

            $this->SetFont('Arial', 'B', 16);

            //Move to the right

            $this->Cell(80);

            //Title (Underlined)

            $this->SetStyle('U', true);

            $this->Cell(30, 10, $this->titulo, 0, 0, 'C');

            $this->SetStyle('U', false);

            //Line break

            $this->Ln(20);

            //Return Font to normal

            $this->SetFont('Arial', '', 11);
        }
    }

    //Page footer

    public function Footer()
    {
        //! @return void

        //Position at 1.0 cm from bottom

        $this->SetY(-10);

        //Copyright //especial para esta versão

        $this->SetFont('Arial', 'B', 9);

        $this->SetTextColor(0);

        //Arial italic 9

        $this->SetFont('Arial', 'I', 9);

        //Page number

        $this->Cell(0, 10, $this->PageNo() . '/{nb}', 0, 0, 'C');

        //Return Font to normal

        $this->SetFont('Arial', '', 11);
    }

    ///////////////////

    /// HTML parser ///

    ///////////////////

    public function WriteHTML($html)
    {
        //! @desc HTML parser

        //! @return void

        /* $e == content */

        if (!$this->usetitle) {
            $regexp = '/<title>.*?<\/title>/si'; // eliminate <TITLE> content </TITLE>

            $html = preg_replace($regexp, '', $html);
        }

        AdjustHTML($html, $this->usepre);

        $html = $this->CreateInternalLinks($html);

        if ($this->usecss) {
            $html = $this->ReadCSS($html);
        }

        //Add new supported tags in the DisableTags function
        $html = strip_tags($html, $this->enabledtags); //remove all unsupported tags, but the ones inside the 'enabledtags' string

        $a = preg_preg_split('/<(.*)>/U', $html, -1, PREG_SPLIT_DELIM_CAPTURE); //explodes the string

        foreach ($a as $i => $e) {
            //echo "i=[" . $i . "]  e[" . $e ."]   pbegin[".$this->pbegin."]<br>\n";

            if (0 == $i % 2) {
                //TEXT

                //Prepare text, if needed

                if (0 == mb_strlen($e)) {
                    continue;
                }

                if ($this->divrevert) {
                    $e = strrev($e);
                }

                if ($this->toupper) {
                    $e = mb_strtoupper($e);
                }

                if ($this->tolower) {
                    $e = mb_strtolower($e);
                }

                if ($this->cssbegin and !$this->tablestart) {
                    if (0 == $this->divwidth) {
                        $this->divwidth = $this->GetStringWidth($e);
                    }

                    if (0 == $this->divheight) {
                        $this->divheight = 5;
                    }

                    $bak_x = $this->x;

                    //  			  if (!$this->divbegin) $this->x = $this->oldx + $this->GetStringWidth($e) - $this->cMargin;

                    if (!$this->divbegin and ($this->oldx > 0)) {
                        $this->x = $this->oldx;
                    }

                    if ($this->divbgcolor) {
                        $this->Cell($this->divwidth, $this->divheight, '', $this->divborder, '', $this->divalign, $this->divbgcolor);
                    }

                    if ($this->dash_on) {
                        $this->Rect($this->x - $this->divwidth, $this->y, $this->divwidth, $this->divheight);
                    }

                    if ($this->dotted_on) {
                        $this->DottedRect($this->x - $this->divwidth, $this->y, $this->divwidth, $this->divheight);
                    }

                    $this->x = $bak_x;
                }

                //Start of 'if/elseif's

                if ($this->titulo) {
                    $this->titulo = $e;

                    $this->Header();
                } elseif ($this->issetlist) {
                    if ($this->li) {
                        if ($this->orderedlist) {
                            $this->textbuffer[] = [$e, $this->HREF, $this->currentstyle, $this->colorarray, $this->currentfont, $this->SUP, $this->SUB, ''/*internal link*/, 0/*img only*/, $this->strike];

                            if ($this->oldx < 0) {
                                $this->oldx = $this->x + $this->GetStringWidth(($this->orderedlist) . '. ') + 3;
                            }

                            $this->oldx += $this->GetStringWidth($e);
                        } else {
                            $this->textbuffer[] = [$e, $this->HREF, $this->currentstyle, $this->colorarray, $this->currentfont, $this->SUP, $this->SUB, ''/*internal link*/, 0/*img only*/, $this->strike];

                            if ($this->oldx < 0) {
                                $this->oldx = $this->x + $this->GetStringWidth(chr(149)) + 3;
                            }

                            $this->oldx += $this->GetStringWidth($e);
                        }
                    }
                } elseif ($this->tablestart) {
                    if ($this->tdbegin) {
                        $this->cell[$this->row][$this->col]['textbuffer'][] = [$e, $this->HREF, $this->currentstyle, $this->colorarray, $this->currentfont, $this->SUP, $this->SUB, ''/*internal link*/, 0/*img only*/, $this->strike];

                        $this->cell[$this->row][$this->col]['text'][] = $e;

                        if ($this->cell[$this->row][$this->col]['s'] < ($this->GetStringWidth($e) + 3)) {
                            $this->cell[$this->row][$this->col]['s'] = $this->GetStringWidth($e) + 3;
                        }
                    }
                } elseif ($this->divbegin) {
                    $this->textbuffer[] = [$e, $this->HREF, $this->currentstyle, $this->colorarray, $this->currentfont, $this->SUP, $this->SUB, ''/*internal link*/, 0/*img only*/, $this->strike];
                } elseif ($this->pbegin) {
                    $this->textbuffer[] = [$e, $this->HREF, $this->currentstyle, $this->colorarray, $this->currentfont, $this->SUP, $this->SUB, ''/*internal link*/, 0/*img only*/, $this->strike];

                    if ($this->oldx < 0) {
                        $this->oldx = $this->x + $this->GetStringWidth(' ');
                    }

                    $this->oldx += $this->GetStringWidth($e);

                    if ($this->oldx < ($this->pgwidth + $this->lMargin)) {
                        $this->oldy = $this->y;
                    } else {
                        $this->oldy = $this->y + (5 * (int)($this->oldx / ($this->pgwidth + $this->lMargin)));
                    }
                } elseif ($this->SUP) {
                    $this->textbuffer[] = [$e];
                } elseif ($this->SUB) {
                    $this->textbuffer[] = [$e];
                } elseif ($this->strike) {
                    $this->textbuffer[] = [$e, $this->HREF, $this->currentstyle, $this->colorarray, $this->currentfont, $this->SUP, $this->SUB, ''/*internal link*/, 0/*img only*/, $this->strike];
                } elseif ($this->centertag) {
                    $this->textbuffer[] = [$e, $this->HREF, $this->currentstyle, $this->colorarray, $this->currentfont, $this->SUP, $this->SUB, ''/*internal link*/, 0/*img only*/, $this->strike];
                } elseif ($this->addresstag) {
                    $this->textbuffer[] = [$e, $this->HREF, $this->currentstyle, $this->colorarray, $this->currentfont, $this->SUP, $this->SUB, ''/*internal link*/, 0/*img only*/, $this->strike];
                } elseif ($this->HREF) {
                    $this->PutLink($this->HREF, $e);
                } elseif ($this->frm_textarea) {
                    $this->textbuffer[] = [$e];
                } elseif ($this->frm_select) {
                    $this->textbuffer[] = [$e];
                } //although only the first option is of interest

                else {
                    $lineheight = 5;

                    //para compensar os H1 H2 H3 H4 usados...

                    if (20 == $this->FontSizePt) {
                        $lineheight = 7;
                    }

                    if (16 == $this->FontSizePt) {
                        $lineheight = 6;
                    }

                    if (11 == $this->FontSizePt) {
                        $lineheight = 5;
                    }

                    if (8 == $this->FontSizePt) {
                        $lineheight = 4;
                    }

                    $this->Write($lineheight, stripslashes(txtentities($e)));
                }
            } else {
                //Tag

                if ('/' == $e[0]) {
                    $this->CloseTag(mb_strtoupper(mb_substr($e, 1)));
                } else {
                    $regexp = '/ (\\w+?)=([^\\s>"]+)/si'; // change algo=algo to algo="algo" (only do this when this happens inside tags)

                    $e = preg_replace($regexp, ' $1="$2"', $e);

                    //Extract attributes

                    $contents = [];

                    preg_match_all('/\\S*=["\'][^"\']*["\']/', $e, $contents);

                    preg_match('/\\S+/', $e, $a2);

                    $tag = mb_strtoupper($a2[0]);

                    $attr = [];

                    if (!empty($contents)) {
                        foreach ($contents[0] as $v) {
                            if (preg_match('^([^=]*)=["\']?([^"\']*)["\']?$', $v, $a3)) {
                                $attr[mb_strtoupper($a3[1])] = $a3[2];
                            }
                        }
                    }

                    $this->OpenTag($tag, $attr);
                }
            }
        }
    }

    public function OpenTag($tag, $attr)
    {
        //! @return void

        /*
* My obs:
* What this gets: < $tag $attr['WIDTH']="90px" > does not get content here </closeTag here>
*/

        $align = ['left' => 'L', 'center' => 'C', 'right' => 'R', 'top' => 'T', 'middle' => 'M', 'bottom' => 'B'];

        //Opening tag

        switch ($tag) {
            case 'BDO':
                if (isset($attr['DIR']) and ('RTL' == mb_strtoupper($attr['DIR']))) {
                    $this->divrevert = true;
                }
                break;
            case 'S':
            case 'STRIKE':
            case 'DEL':
                $this->strike = true;
                break;
            case 'SUB':
                $this->SUB = true;
                break;
            case 'SUP':
                $this->SUP = true;
                break;
            case 'CENTER':
                $this->centertag = true;
                if ($this->tdbegin) {
                    $this->cell[$this->row][$this->col]['a'] = $align['center'];
                } else {
                    $this->divalign = $align['center'];

                    $this->Ln(5);
                }
                break;
            case 'ADDRESS':
                $this->addresstag = true;
                if ($this->tdbegin) {
                    $this->SetStyle('I', true);
                } else {
                    $this->SetStyle('I', true);

                    if ($this->x != $this->lMargin) {
                        $this->Ln(5);
                    }
                }
                break;
            case 'TABLE': // TABLE-BEGIN
                if ($this->x != $this->lMargin) {
                    $this->Ln(5);
                }
                $this->tablestart = true;
                $this->table['nc'] = $this->table['nr'] = 0;
                if (isset($attr['WIDTH'])) {
                    $this->table['w'] = ConvertSize($attr['WIDTH'], $this->pgwidth);
                }
                if (isset($attr['HEIGHT'])) {
                    $this->table['h'] = ConvertSize($attr['HEIGHT'], $this->pgwidth);
                }
                if (isset($attr['ALIGN'])) {
                    $this->table['a'] = $align[mb_strtolower($attr['ALIGN'])];
                }
                if (isset($attr['BORDER'])) {
                    $this->table['border'] = $attr['BORDER'];
                }
                if (isset($attr['BGCOLOR'])) {
                    $this->table['bgcolor'][-1] = $attr['BGCOLOR'];
                }
                break;
            case 'TR':
                $this->row++;
                $this->table['nr']++;
                $this->col = -1;
                if (isset($attr['BGCOLOR'])) {
                    $this->table['bgcolor'][$this->row] = $attr['BGCOLOR'];
                }
                break;
            case 'TH':
                $this->SetStyle('B', true);
                $attr['ALIGN'] = 'center';
                // no break
            case 'TD':
                $this->tdbegin = true;
                $this->col++;
                while (isset($this->cell[$this->row][$this->col])) {
                    $this->col++;
                }
                //Update number column
                if ($this->table['nc'] < $this->col + 1) {
                    $this->table['nc'] = $this->col + 1;
                }
                $this->cell[$this->row][$this->col] = [];
                $this->cell[$this->row][$this->col]['text'] = [];
                $this->cell[$this->row][$this->col]['s'] = 2;
                if (isset($attr['WIDTH'])) {
                    $this->cell[$this->row][$this->col]['w'] = ConvertSize($attr['WIDTH'], $this->pgwidth);
                }
                if (isset($attr['HEIGHT'])) {
                    $this->cell[$this->row][$this->col]['h'] = ConvertSize($attr['HEIGHT'], $this->pgwidth);
                }
                if (isset($attr['ALIGN'])) {
                    $this->cell[$this->row][$this->col]['a'] = $align[mb_strtolower($attr['ALIGN'])];
                }
                if (isset($attr['VALIGN'])) {
                    $this->cell[$this->row][$this->col]['va'] = $align[mb_strtolower($attr['VALIGN'])];
                }
                if (isset($attr['BORDER'])) {
                    $this->cell[$this->row][$this->col]['border'] = $attr['BORDER'];
                }
                if (isset($attr['BGCOLOR'])) {
                    $this->cell[$this->row][$this->col]['bgcolor'] = $attr['BGCOLOR'];
                }
                $cs = $rs = 1;
                if (isset($attr['COLSPAN']) && $attr['COLSPAN'] > 1) {
                    $cs = $this->cell[$this->row][$this->col]['colspan'] = $attr['COLSPAN'];
                }
                if (isset($attr['ROWSPAN']) && $attr['ROWSPAN'] > 1) {
                    $rs = $this->cell[$this->row][$this->col]['rowspan'] = $attr['ROWSPAN'];
                }
                //Chiem dung vi tri de danh cho cell span (?mais hein?)
                for ($k = $this->row; $k < $this->row + $rs; $k++) {
                    for ($l = $this->col; $l < $this->col + $cs; $l++) {
                        if ($k - $this->row || $l - $this->col) {
                            $this->cell[$k][$l] = 0;
                        }
                    }
                }
                if (isset($attr['NOWRAP'])) {
                    $this->cell[$this->row][$this->col]['nowrap'] = 1;
                }
                break;
            case 'OL':
                $this->orderedlist = 1;
                // no break
            case 'UL':
                $this->li = false;
                if (!$this->listbegin) {
                    $this->listbegin = true;

                    $this->Ln(5);
                }
                $this->oldx = -1;
                $bak_x = $this->x;
                if (!empty($this->textbuffer)) {
                    if ($this->orderedlist) {
                        $this->orderedlist++; // orderedlist != 0

                        $blt = ($this->orderedlist - 1) . '. ';
                    } else {
                        if (0 == $this->issetlist % 2) {
                            $blt = chr(186);
                        }//circulo branco

                        else {
                            $blt = chr(149);
                        } //circulo preto
                    }

                    //Get bullet width including margins

                    $blt_width = $this->GetStringWidth($blt) + $this->cMargin * 2;

                    //Output bullet

                    $this->Cell($blt_width, 5, $blt, 0, '', 0);

                    //Print content

                    $this->printbuffer($this->textbuffer);

                    $this->textbuffer = [];
                }
                $this->x = $bak_x;
                $this->x += 5;
                $this->issetlist++;
                break;
            case 'LI':
                $this->li = true;
                $this->oldx = -1;
                $bak_x = $this->x;
                if (!empty($this->textbuffer)) {
                    if ($this->orderedlist) {
                        $this->orderedlist++; // orderedlist != 0

                        $blt = ($this->orderedlist - 1) . '. ';
                    } else {
                        if (0 == $this->issetlist % 2) {
                            $blt = chr(186);
                        }//circulo branco

                        else {
                            $blt = chr(149);
                        } //circulo preto
                    }

                    //Get bullet width including margins

                    $blt_width = $this->GetStringWidth($blt) + $this->cMargin * 2;

                    //Output bullet

                    $this->Cell($blt_width, 5, $blt, 0, '', 0);

                    //Print content

                    $this->printbuffer($this->textbuffer);

                    $this->textbuffer = [];
                }
                $this->Ln(5);
                $this->x = $bak_x;
                break;
            case 'H1':
                $this->SetStyle('B', true);
                $this->SetFontSize(20);
                break;
            case 'H2':
                $this->SetStyle('B', true);
                $this->SetFontSize(16);
                break;
            case 'H3':
                $this->SetStyle('B', true);
                $this->SetFontSize(11);
                break;
            case 'H4':
                $this->SetStyle('B', true);
                $this->SetFontSize(8);
                break;
            case 'HR': //editado para esta versão
                //			if( $attr['WIDTH'] != '' )	$Width = $attr['WIDTH'];
                //			else $Width = $this->w - $this->lMargin - $this->rMargin;
                $x = $this->GetX();
                $y = $this->GetY();
                $this->SetLineWidth(0.2);
                $this->Line($x, $y, $x + $this->pgwidth, $y);
                $this->SetLineWidth(0.2);
                $this->Ln(1);
                break;
            case 'INS':
                $this->SetStyle('U', true);
                break;
            case 'SMALL':
                $newsize = $this->FontSizePt - 1;
                $this->SetFontSize($newsize);
                break;
            case 'BIG':
                $newsize = $this->FontSizePt + 1;
                $this->SetFontSize($newsize);
                // no break
            case 'STRONG':
                $this->SetStyle('B', true);
                break;
            case 'CITE':
            case 'EM':
                $this->SetStyle('I', true);
                break;
            case 'TITLE':
                $this->titulo = 'not empty'; //In order to call Header()
                break;
            case 'B':
            case 'I':
            case 'U':
                if (isset($attr['CLASS']) or isset($attr['ID'])) {
                    $this->cssbegin = true;

                    if ('' != $attr['CLASS']) {
                        $name = $attr['CLASS'];
                    } elseif ('' != $attr['ID']) {
                        $name = $attr['ID'];
                    }

                    //Look for name in the $this->CSS array

                    $properties = $this->CSS[$name];

                    if (0 != $properties) { //name found in the CSS array
                        $this->setCSS($properties);
                    }
                }
                $this->SetStyle($tag, true);
                break;
            case 'A':
                if ('' != $attr['NAME']) {
                    $this->textbuffer[] = ['', '', '', [], '', false, false, '#' . $attr['NAME']];
                }
                $this->HREF = $attr['HREF'];
                break;
            case 'DIV':
                $this->divbegin = true;
                if ($this->x != $this->lMargin) {
                    $this->Ln(5);
                }
                if ('' != $attr['ALIGN']) {
                    $this->divalign = $align[mb_strtolower($attr['ALIGN'])];
                }
                if (isset($attr['CLASS']) or isset($attr['ID'])) {
                    $this->cssbegin = true;

                    if ('' != $attr['CLASS']) {
                        $name = $attr['CLASS'];
                    } elseif ('' != $attr['ID']) {
                        $name = $attr['ID'];
                    }

                    //Look for name in the $this->CSS array

                    $properties = $this->CSS[$name];

                    if (0 != $properties) { //name found in the CSS array
                        $this->setCSS($properties);
                    }
                }
                break;
            case 'IMG':
                if (!empty($this->textbuffer) and !$this->tablestart) {
                    $this->printbuffer($this->textbuffer);

                    $this->textbuffer = [];

                    $this->Ln(5);
                }
                if (isset($attr['SRC'])) {
                    //-- XJ : hack to handle relative and full urls

                    if ('http://' != mb_substr($attr['SRC'], 0, 7)) {
                        $attr['SRC'] = XOOPS_URL . ('/' == mb_substr($attr['SRC'], 0, 1) ? '' : '/') . $attr['SRC'];
                    }

                    if (!isset($attr['WIDTH'])) {
                        $attr['WIDTH'] = 0;
                    } else {
                        $attr['WIDTH'] /= 4;
                    }

                    if (!isset($attr['HEIGHT'])) {
                        $attr['HEIGHT'] = 0;
                    } else {
                        $attr['HEIGHT'] /= 4;
                    }

                    if ($this->tdbegin) {
                        $bak_x = $this->x;

                        $bak_y = $this->y;

                        $sizesarray = $this->Image($attr['SRC'], $this->GetX(), $this->GetY(), ConvertSize($attr['WIDTH'], $this->pgwidth), ConvertSize($attr['HEIGHT'], $this->pgwidth), '', '', false);

                        $this->y = $bak_y;

                        $this->x = $bak_x;
                    } else {
                        $sizesarray = $this->Image($attr['SRC'], $this->GetX(), $this->GetY(), ConvertSize($attr['WIDTH'], $this->pgwidth), ConvertSize($attr['HEIGHT'], $this->pgwidth), '', $this->HREF);
                    }

                    if ($sizesarray['X'] < $this->x) {
                        $this->x = $this->lMargin;
                    }

                    if ($this->tablestart) {
                        $this->cell[$this->row][$this->col]['textbuffer'][] = [
                            '¬¤¶'/*unique string to be parsed later*/ . $sizesarray['OUTPUT'],
                            $this->HREF,
                            $this->currentstyle,
                            $this->colorarray,
                            $this->currentfont,
                            $this->SUP,
                            $this->SUB,
                            ''
                            /*internal link*/,
                            $sizesarray['HEIGHT'],
                            /*img height*/
                        ];

                        if (!isset($this->cell[$this->row][$this->col]['w'])) {
                            $this->cell[$this->row][$this->col]['w'] = $sizesarray['WIDTH'] + 3;
                        }

                        if (!isset($this->cell[$this->row][$this->col]['h'])) {
                            $this->cell[$this->row][$this->col]['h'] = $sizesarray['HEIGHT'] + 3;
                        }
                    }
                }
                break;
            case 'BLOCKQUOTE':
            case 'BR':
                if ($this->tablestart) {
                    $this->cell[$this->row][$this->col]['text'][] = "\n";
                } elseif ($this->divbegin) {
                    $this->textbuffer[] = ["\n", $this->HREF, $this->currentstyle, $this->colorarray, $this->currentfont];
                } elseif ($this->pbegin) {
                    $this->textbuffer[] = ["\n", $this->HREF, $this->currentstyle, $this->colorarray, $this->currentfont];
                } elseif ($this->addresstag) {
                    $this->textbuffer[] = ["\n", $this->HREF, $this->currentstyle, $this->colorarray, $this->currentfont];
                } else {
                    $this->Ln(5);
                }
                break;
            case 'P':
                if ($this->tablestart) {
                    break;
                } //especial para esta versão
                $this->pbegin = true;
                if ($this->x != $this->lMargin) {
                    $this->Ln(10);
                } elseif (!$this->pjustfinished) {
                    $this->Ln(5);
                }
                if ('' != $attr['ALIGN']) {
                    $this->divalign = $align[mb_strtolower($attr['ALIGN'])];
                }
                if (isset($attr['CLASS']) or isset($attr['ID'])) {
                    $this->cssbegin = true;

                    if ('' != $attr['CLASS']) {
                        $name = $attr['CLASS'];
                    } elseif ('' != $attr['ID']) {
                        $name = $attr['ID'];
                    }

                    //Look for name in the $this->CSS array

                    $properties = $this->CSS[$name];

                    if (0 != $properties) { //name found in the CSS array
                        $this->setCSS($properties);
                    }
                }
                break;
            case 'PRE':
                if ($this->x != $this->lMargin) {
                    $this->Ln(10);
                } else {
                    $this->Ln(5);
                }
                // no break
            case 'CODE':
                $this->SetFont('courier');
                $this->currentfont = 'courier';
                break;
            case 'TEXTAREA':
                $this->frm_textarea = true;
                if ($this->x != $this->lMargin) {
                    $this->Ln(10);
                } else {
                    $this->Ln(5);
                }
                $this->col = 20; //HTML default value
                $this->row = 2; //HTML default value
                if (isset($attr['COLS'])) {
                    $this->col = $attr['COLS'];
                }
                if (isset($attr['ROWS'])) {
                    $this->row = $attr['ROWS'];
                }
                break;
            case 'SELECT':
                $this->frm_select = true;
                break;
            case 'FORM':
                if ($this->x != $this->lMargin) {
                    $this->Ln(5);
                }
                break;
            case 'INPUT':
                if (isset($attr['TYPE'])) {
                    switch (mb_strtoupper($attr['TYPE'])) {
                        case 'TEXT': //Draw TextField
                            $texto   = $attr['VALUE'] ?? '';
                            $tamanho = 20;
                            if (isset($attr['SIZE']) and ctype_digit($attr['SIZE'])) {
                                $tamanho = $attr['SIZE'];
                            }
                            $this->SetFillColor(235, 235, 235);
                            $this->x += 3;
                            $this->Rect($this->x, $this->y, 2 * $tamanho, 5, 'DF');
                            if ('' != $texto) {
                                $this->Write(5, $texto, $this->x);

                                $this->x -= $this->GetStringWidth($texto);
                            }
                            $this->SetFillColor(0);
                            $this->x += 2 * $tamanho;
                            break;
                        case 'CHECKBOX': //Draw Checkbox
                            $checked = false;
                            if (isset($attr['CHECKED'])) {
                                $checked = true;
                            }
                            $this->SetFillColor(235, 235, 235);
                            $this->Rect($this->x + 3, $this->y + 1, 3, 3, 'DF');
                            if ($checked) {
                                $this->Line($this->x + 3, $this->y + 1, $this->x + 3 + 3, $this->y + 1 + 3);

                                $this->Line($this->x + 3, $this->y + 1 + 3, $this->x + 3 + 3, $this->y + 1);
                            }
                            $this->SetFillColor(0);
                            $this->x += 3 + 3;
                            break;
                        case 'RADIO': //Draw Radio button
                            $checked = false;
                            if (isset($attr['CHECKED'])) {
                                $checked = true;
                            }
                            $this->x += 4;
                            $this->Circle($this->x, $this->y + 2.2, 1, 'D');
                            if ($checked) {
                                $this->Circle($this->x, $this->y + 2.2, 0.4, 'DF');
                            }
                            $this->Write(5, $texto, $this->x);
                            $this->x += 2;
                            break;
                        case 'BUTTON': // Draw a button
                        case 'SUBMIT':
                        case 'RESET':
                            $texto = '';
                            if (isset($attr['VALUE'])) {
                                $texto = ' ' . $attr['VALUE'] . ' ';
                            }
                            $this->SetFillColor(190, 190, 190);
                            $this->Rect($this->x + 3, $this->y, $this->GetStringWidth($texto) + 3, 5, 'DF');
                            $this->x += 3;
                            $this->Write(5, $texto, $this->x);
                            $this->x += 3;
                            $this->SetFillColor(0);
                            break;
                    }
                }
                break;
            case 'FONT':
                if (isset($attr['COLOR']) and '' != $attr['COLOR']) {
                    $coul = ConvertColor($attr['COLOR']);

                    $this->colorarray = $coul;

                    $this->SetTextColor($coul['R'], $coul['G'], $coul['B']);

                    $this->issetcolor = true;
                }
                if (isset($attr['FACE']) and in_array(mb_strtolower($attr['FACE']), $this->fontlist, true)) {
                    $this->SetFont(mb_strtolower($attr['FACE']));

                    $this->issetfont = true;
                }
                //'If' disabled in this version due lack of testing (you may enable it if you want)
                //			if (isset($attr['FACE']) and in_array(strtolower($attr['FACE']), $this->fontlist) and isset($attr['SIZE']) and $attr['SIZE']!='') {
                //				$this->SetFont(strtolower($attr['FACE']),'',$attr['SIZE']);
                //				$this->issetfont=true;
                //			}
                break;
        }//end of switch

        $this->pjustfinished = false;
    }

    public function CloseTag($tag)
    {
        //! @return void

        //Closing tag

        if ('BDO' == $tag) {
            $this->divrevert = false;
        }

        if ('INS' == $tag) {
            $tag = 'U';
        }

        if ('STRONG' == $tag) {
            $tag = 'B';
        }

        if ('EM' == $tag or 'CITE' == $tag) {
            $tag = 'I';
        }

        if ('A' == $tag) {
            $this->HREF = '';
        }

        if ('LI' == $tag) {
            $this->li = false;
        }

        if ('TH' == $tag) {
            $this->SetStyle('B', false);
        }

        if ('TH' == $tag or 'TD' == $tag) {
            $this->tdbegin = false;
        }

        if ('P' == $tag or 'DIV' == $tag) { //CSS in BLOCK mode
            if (!$this->tablestart) {
                if (0 == $this->divwidth) {
                    $this->divwidth = $this->pgwidth;
                }

                if ('P' == $tag) {
                    $this->pbegin = false;

                    $this->pjustfinished = true;
                } else {
                    $this->divbegin = false;
                }

                $content = '';

                foreach ($this->textbuffer as $aux) {
                    $content .= $aux[0];
                }

                $numlines = $this->WordWrap($content, $this->divwidth);

                //Print Background color

                $aux_x = $this->x;

                if (0 == $this->divheight) {
                    $this->divheight = $numlines * 5;
                }

                $this->Cell($this->divwidth, $this->divheight, '', $this->divborder, '', $this->divalign, $this->divbgcolor);

                if ($this->dash_on) {
                    $this->Rect($this->x - $this->divwidth, $this->y, $this->divwidth, $this->divheight);
                }

                if ($this->dotted_on) {
                    $this->DottedRect($this->x - $this->divwidth, $this->y, $this->divwidth, $this->divheight);
                }

                $this->x = $aux_x;

                //Print content

                $this->printbuffer($this->textbuffer);

                $this->textbuffer = [];

                if ('P' == $tag) {
                    $this->Ln(10);
                } else {
                    $this->Ln(5);
                }
            }

            //Reset values

            if (true === $this->issetcolor) {
                $this->SetTextColor(0);

                $this->SetDrawColor(0);

                $this->colorarray = [];
            }

            $this->SetFontSize(11);

            $this->SetStyle('B', false);

            $this->SetStyle('I', false);

            $this->SetStyle('U', false);

            $this->SetFont('arial');

            $this->divrevert = false;

            $this->divborder = 0;

            $this->divwidth = 0;

            $this->divalign = 'L';

            $this->divbgcolor = false;

            $this->divheight = 0;

            $this->toupper = false;

            $this->tolower = false;

            $this->SetDash(); //restore to no dash

            $this->dash_on = false;

            $this->dotted_on = false;

            $this->oldx = -1;

            $this->oldy = -1;
        }

        if ($this->cssbegin) {
            if (true === $this->issetcolor) {
                $this->SetTextColor(0);

                $this->SetDrawColor(0);

                $this->colorarray = [];
            }

            $this->cssbegin = false;

            $this->divrevert = false;

            $this->divborder = 0;

            $this->divwidth = 0;

            $this->divalign = 'L';

            $this->divbgcolor = false;

            $this->divheight = 0;

            $this->toupper = false;

            $this->tolower = false;

            $this->SetDash(); //restore to no dash

            $this->dash_on = false;

            $this->dotted_on = false;
        }

        if ('TABLE' == $tag) { // TABLE-END
            $this->table['cells'] = $this->cell;

            $this->table['wc'] = array_pad([], $this->table['nc'], ['miw' => 0, 'maw' => 0]);

            $this->table['hr'] = array_pad([], $this->table['nr'], 0);

            $this->_tableColumnWidth($this->table);

            $this->_tableWidth($this->table);

            $this->_tableHeight($this->table);

            //Output table on PDF

            $this->_tableWrite($this->table);

            $this->tablestart = false; //bool
            $this->table = []; //array
            $this->cell = []; //array
            $this->col = -1; //int
            $this->row = -1; //int
            $this->oldx = -1;

            $this->oldy = -1;

            $this->Ln(3);
        }

        if (('UL' == $tag) or ('OL' == $tag)) {
            $this->oldx = -1;

            $bak_x = $this->x;

            if (!empty($this->textbuffer)) {
                if ($this->orderedlist) {
                    $this->orderedlist++;

                    $blt = ($this->orderedlist - 1) . '. ';
                } else {
                    if (0 == $this->issetlist % 2) {
                        $blt = chr(186);
                    }//circulo branco

                    else {
                        $blt = chr(149);
                    } //circulo preto
                }

                //Get bullet width including margins

                $blt_width = $this->GetStringWidth($blt) + $this->cMargin * 2;

                //Output bullet

                $this->Cell($blt_width, 5, $blt, 0, '', $fill);

                //Print content

                $this->printbuffer($this->textbuffer);

                $this->textbuffer = [];
            }

            $this->x = $bak_x;

            $this->x -= 5;

            $this->issetlist--;

            if (0 == $this->issetlist) { //end of list
                $this->listbegin = false;

                $this->Ln(10);
            }

            $this->orderedlist = 0;
        }

        /*	if($tag=='H1') $this->Ln(7);
    if($tag=='H2') $this->Ln(6);
    if($tag=='H3') $this->Ln(5);
    if($tag=='H4') $this->Ln(4);*/

        if ('H1' == $tag or 'H2' == $tag or 'H3' == $tag or 'H4' == $tag) {
            $this->SetFontSize(11);

            $this->SetStyle('B', false);
        }

        if ('TITLE' == $tag) {
            $this->titulo = '';
        } // becomes empty to avoid calling Header() again!

        if ('FORM' == $tag) {
            $this->Ln(5);
        }

        if ('PRE' == $tag) {
            $this->Ln(6);
        }

        if ('CODE' == $tag or 'PRE' == $tag) {
            $this->currentfont = '';

            $this->SetFont('arial');
        }

        if ('B' == $tag or 'I' == $tag or 'U' == $tag) {
            $this->SetStyle($tag, false);
        }

        if ('TEXTAREA' == $tag) {
            //Draw arrows too?

            $texto = '';

            foreach ($this->textbuffer as $v) {
                $texto .= $v[0];
            }

            $this->SetFillColor(235, 235, 235);

            $this->x += 3;

            $this->Rect($this->x, $this->y, 2 * $this->col, 5 * $this->row, 'DF');

            if ('' != $texto) {
                $this->MultiCell(2 * $this->col, 5, $texto);
            }

            $this->SetFillColor(0);

            $this->Ln(5);

            $this->textbuffer = [];

            $this->frm_textarea = false;
        }

        if ('SELECT' == $tag) {
            //Draw arrows too?

            $texto = '';

            foreach ($this->textbuffer as $v) {
                //shows only the first option

                //(not supported: the <option selected="selected">SHOW THIS ONE</option>)

                $texto = trim($v[0]);

                if ('' != $texto) {
                    break;
                }
            }

            $tamanho = 15;

            if ($this->GetStringWidth($texto) > (2 * $tamanho)) {
                $tamanho = $this->GetStringWidth($texto) / 2;
            }

            $this->SetFillColor(235, 235, 235);

            $this->x += 3;

            $this->Rect($this->x, $this->y, 2 * $tamanho + 2, 5, 'DF'); //+2 margin

            if ('' != $texto) {
                $this->Write(5, $texto, $this->x);
            }

            $this->x += 2;

            $this->SetFillColor(190, 190, 190);

            $this->Rect($this->x, $this->y, 5, 5, 'DF'); //Arrow Box

            $this->SetFont('zapfdingbats');

            $this->Write(5, chr(116), $this->x); //Down arrow

            $this->SetFont('arial');

            $this->SetFillColor(0);

            $this->x += 2;

            $this->textbuffer = [];

            $this->frm_select = false;
        }

        if ('SUB' == $tag) {  //subscript
            if (!$this->pbegin and !$this->divbegin and !$this->tablestart and !$this->centertag) {
                $texto = '';

                foreach ($this->textbuffer as $vetor) {
                    $texto .= $vetor[0];
                }

                //Set current font to: Bold, 6pt

                $this->SetFont('', '', 6);

                $bak_y = $this->y;

                //Start 125cm plus width of cell to the right of left margin

                //Subscript "1"

                $this->y += 2.5;

                $this->Cell($this->GetStringWidth($texto), 2, $texto, 0, 0, 'L');

                $this->y = $bak_y;

                $this->SetFontSize(11);

                $this->textbuffer = [];
            }

            $this->SUB = false;
        }

        if ('SUP' == $tag) { //superscript
            if (!$this->pbegin and !$this->divbegin and !$this->tablestart and !$this->centertag) {
                $texto = '';

                foreach ($this->textbuffer as $vetor) {
                    $texto .= $vetor[0];
                }

                //Set current font to: Bold, 6pt

                $this->SetFont('', '', 6);

                //Start 125cm plus width of cell to the right of left margin

                //Superscript "1"

                $this->Cell($this->GetStringWidth($texto), 2, $texto, 0, 0, 'L');

                $this->SetFontSize(11);

                $this->textbuffer = [];
            }

            $this->SUP = false;
        }

        if ('S' == $tag or 'STRIKE' == $tag or 'DEL' == $tag) {
            if (!$this->pbegin and !$this->divbegin and !$this->tablestart) {
                $texto = '';

                foreach ($this->textbuffer as $vetor) {
                    $texto .= $vetor[0];
                }

                //Print content

                $this->printbuffer($this->textbuffer);

                $this->textbuffer = [];

                //Reset values

                if (true === $this->issetcolor) {
                    $this->SetTextColor(0);

                    $this->SetDrawColor(0);

                    $this->colorarray = [];
                }

                $this->SetFontSize(11);

                $this->SetStyle('B', false);

                $this->SetStyle('I', false);

                $this->SetStyle('U', false);

                $this->SetFont('arial');

                $this->divalign = 'L';
            }

            $this->strike = false;
        }

        if ('ADDRESS' == $tag) { // <ADDRESS> tag
            if (!$this->pbegin and !$this->divbegin and !$this->tablestart) {
                $texto = '';

                foreach ($this->textbuffer as $vetor) {
                    $texto .= $vetor[0];
                }

                //Print content

                $this->printbuffer($this->textbuffer);

                $this->textbuffer = [];

                if ($this->x != $this->lMargin) {
                    $this->Ln(5);
                }

                //Reset values

                if (true === $this->issetcolor) {
                    $this->SetTextColor(0);

                    $this->SetDrawColor(0);

                    $this->colorarray = [];
                }

                $this->SetFontSize(11);

                $this->SetStyle('B', false);

                $this->SetStyle('I', false);

                $this->SetStyle('U', false);

                $this->SetFont('arial');

                $this->divalign = 'L';
            }

            $this->addresstag = false;

            $this->SetStyle('I', false);
        }

        if ('CENTER' == $tag) { // <CENTER> tag
            if (!$this->pbegin and !$this->divbegin and !$this->tablestart) {
                $texto = '';

                foreach ($this->textbuffer as $vetor) {
                    $texto .= $vetor[0];
                }

                //Print content

                $this->printbuffer($this->textbuffer);

                $this->textbuffer = [];

                $this->Ln(5);

                //Reset values

                if (true === $this->issetcolor) {
                    $this->SetTextColor(0);

                    $this->SetDrawColor(0);

                    $this->colorarray = [];
                }

                $this->SetFontSize(11);

                $this->SetStyle('B', false);

                $this->SetStyle('I', false);

                $this->SetStyle('U', false);

                $this->SetFont('arial');

                $this->divalign = 'L';
            }

            $this->centertag = false;
        }

        if ('BIG' == $tag) {
            $newsize = $this->FontSizePt - 1;

            $this->SetFontSize($newsize);

            $this->SetStyle('B', false);
        }

        if ('SMALL' == $tag) {
            $newsize = $this->FontSizePt + 1;

            $this->SetFontSize($newsize);
        }

        if ('FONT' == $tag) {
            if (true === $this->issetcolor) {
                $this->colorarray = [];

                $this->SetTextColor(0);
            }

            if ($this->issetfont) {
                $this->SetFont('arial');

                $this->issetfont = false;
            }
        }
    }

    public function printbuffer($array, $istable = false, $wordwrappedtext = '')
    {
        //! @return void

        if ($istable) {
            $currpos = 0;

            $npos = mb_strpos($wordwrappedtext, "\n", $currpos);

            if (false !== $npos) {
                $maxsize = $npos - 1;
            }

            if (empty($array)) {
                $array = [' ', '', ''];
            }
        }

        $bak_y = $this->y;

        $aux_x = $this->x;

        $align = $this->divalign;

        $dx = 0; //not-implemented yet

        //Default align == 'L'eft so ignore these cases

        //In order to make align work in some cases (when there is only one style applied to the whole text)

        $monostyle = false;

        if (1 == count($array)) {
            $monostyle = true;
        }

        ///debug///

        $notn = '';

        ///

        foreach ($array as $vetor) {
            if ($istable) { //Pass '\n' from wordwrappedtext to vetor[0]
                $once = true;

                $repeat = true;

                while ($repeat) {
                    $repeat = false;

                    if (false !== $npos) {
                        if ($once) {
                            $strsize = mb_strlen($vetor[0]);
                        }

                        $once = false;

                        if ($npos < $currpos + $strsize) {
                            if (' ' == $vetor[0]) {
                                $vetor[0] = ' ' . $vetor[0]; //improve?

                                $strsize++;
                            }

                            if (' ' != $vetor[0][$npos - $currpos] and "\n" != $vetor[0][$npos - $currpos]) {
                                $vetor[0] = ' ' . $vetor[0]; //improve?

                                $strsize++;
                            }

                            $vetor[0][$npos - $currpos] = "\n";

                            $npos = mb_strpos($wordwrappedtext, "\n", $npos + 1);

                            $repeat = true;
                        } else {
                            $currpos += $strsize;
                        }
                    }
                }
            }

            ///debug///

            $notn .= $vetor[0];

            ///
            if (true === $vetor[9]) { // strike-through the text
                $xini = $this->x;

                $yini = $this->y;
            }

            //vetor[8] contains image height
            if ('' != $vetor[7]) { //<a name="#algo"> '#' added for identification
                if ('' != $this->internallink[$vetor[7]]) {
                    $this->SetLink($this->internallink[$vetor[7]], -1);
                }
            }

            if (true === $vetor[6]) { // Subscript
                $this->y += 1;

                $this->SetFontSize(6);
            }

            if (true === $vetor[5]) { // Superscript
                $this->y -= 1;

                $this->SetFontSize(6);
            }

            if ('' != $vetor[4]) {
                $this->SetFont($vetor[4]);
            } // Font Family
            if ('' != $vetor[3]) { //Font Color
                $coul = $vetor[3];

                $this->SetTextColor($coul['R'], $coul['G'], $coul['B']);
            }

            if ('' != $vetor[2]) {
                $this->SetFont('', $vetor[2]);
            } //Bold,Italic,Underline
            if ($vetor[1]) { //LINK
                if (true === $this->internallink[$vetor[1]]) {
                    $this->internallink[$vetor[1]] = $this->AddLink();

                    $vetor[1] = $this->internallink[$vetor[1]];
                }

                $this->SetTextColor(0, 0, 255);

                $this->SetStyle('U', true);
            }

            if ('¬' == $vetor[0][0] and '¤' == $vetor[0][1] and '¶' == $vetor[0][2]) { //in order to recognize an image in a cell
                $vetor[0] = str_replace('¬¤¶', '', $vetor[0]); //decode
                //Is this the best way of fixing x,y coords? More tests are needed...
                $fix_x = ($this->x + 2) * $this->k; //+2 margin
                $fix_y = ($this->h - (($this->y + 2) + $vetor[8])) * $this->k; //+2 margin
                $imgtemp = explode(' ', $vetor[0]);

                $imgtemp[5] = $fix_x; // x
                $imgtemp[6] = $fix_y; // y
                $vetor[0] = implode(' ', $imgtemp);

                $this->_out($vetor[0]);
            } elseif ($monostyle and 'L' != $align) {
                $this->MultiCell($this->divwidth, $this->divheight, $vetor[0], $this->divborder, $align, 0, $vetor[1]);
            } else { //THE text
                $posarray = [];

                if (!$this->pbegin and !$this->divbegin and !$this->tablestart) {
                    $posarray = $this->Write(5, $vetor[0], 0, $vetor[1], $align);
                } else {
                    $posarray = $this->Write(5, $vetor[0], $aux_x + $dx, $vetor[1], $align);
                }
            }

            if ($vetor[1]) {
                $this->SetTextColor(0);

                $this->SetStyle('U', false);
            }

            if ('' != $vetor[2]) {
                $this->SetFont('', '');
            }

            if ('' != $vetor[3]) {
                unset($coul);

                $this->SetTextColor(0);
            }

            if ('' != $vetor[4]) {
                $this->SetFont('arial');
            }

            if (true === $vetor[5]) {
                $this->y += 1;

                $this->SetFontSize(11);
            }

            if (true === $vetor[6]) {
                $this->y -= 1;

                $this->SetFontSize(11);
            }

            //vetor7-internal links
            //vetor8-img height
            if (true === $vetor[9]) { // strike-through the text
                //Supposition: word's height won't change and we are not inside a table
                //does not work properly on table (when the strike spans more than one line)
                if (0 == $this->divwidth) {
                    $this->divwidth = $this->pgwidth;
                }

                $xend = $this->x;

                $yend = $this->y;

                //Aesthetical Adjustments: Go a bit to the right and a bit down

                $xini += 0.7;

                $yini += 0.15;

                $xend += 0.7;

                $yend += 0.15;

                $i = 0;

                while ($yini != ($yend)) { //if strike spans more than one line
                    if (!empty($posarray)) {
                        $strikesize = $posarray[$i];
                    } else {
                        $strikesize = $this->divwidth;
                    }

                    $this->Line($xini, $yini + $this->FontSize / 2, $xini + (($strikesize + $this->lMargin) - ($xini - 0.7)), $yini + $this->FontSize / 2);

                    $xini = $this->lMargin + 0.7; // '\r' (carriage return)
                    $yini += 5; //skip a line
                    $i++;
                }

                $this->Line($xini, $yini + $this->FontSize / 2, $xend, $yend + $this->FontSize / 2);

                $xini = -1;

                $yini = -1;

                $xend = -1;

                $yend = -1;
            }
        }

        ///////////DEBUG////////////////////////
        /*
$yesn = $wordwrappedtext;
$notn = str_replace("\n", "|", $notn);
$yesn = str_replace("\n", "|", $yesn);

$i = 0;
while($i < strlen($notn))
{
echo "i[".$i."]----n[".$notn{$i}."] y[".$yesn{$i}."] npos[".$npos."]";
echo "<br>\n";
$i++;
}
*/
        ////////////////////////////////////
    }

    //Get internal references and remove them first (they start with '#')

    //Information gathered goes to $this->internallink

    public function CreateInternalLinks($html)
    {
        //! @return string

        $regexp = '/href=["]?#([^\\s>"]+)/si';

        preg_match_all($regexp, $html, $aux);

        foreach ($aux[1] as $val => $key) {
            $this->internallink['#' . $key] = true;
        }

        //Fix name=something to name="something"

        $regexp = '/ name=([^\\s>"]+)/si';

        $html = preg_replace($regexp, ' name=' . '"$1"', $html);

        return $html;
    }

    //////////////////

    /// CSS parser ///

    //////////////////

    public function ReadCSS($html)
    {
        //! @desc CSS parser

        //! @return string

        /*
* This version ONLY supports:  .class {...} / #id { .... }
* It does NOT support: body{...} / a#hover { ... } / p.right { ... } / other mixed names
* This function must read the CSS code (internal or external) and order its value inside $this->CSS.
*/

        $match = 0; // no match for instance
        $regexp = ''; // This helps debugging: showing what is the REAL string being processed

        //CSS external

        $regexp = '/<link rel="stylesheet".*?href="(.+?)"\\s*?\/?>/si';

        $match = preg_match_all($regexp, $html, $CSSext);

        $ind = 0;

        while ($match) {
            /*
 * XJ  hack to allow "remote" css sheets
 *
    $file = fopen($CSSext[1][$ind],"r");
    $CSSextblock = fread($file,filesize($CSSext[1][$ind]));
    fclose($file);
*/

            $CSSextblock = file_get_contents($CSSext[1][$ind]);

            //Get class/id name and its characteristics from $CSSblock[1]
            $regexp = '/[.# ]([^.]+?)\\s*?\{(.+?)\}/s'; // '/s' PCRE_DOTALL including \n
            preg_match_all($regexp, $CSSextblock, $extstyle);

            //Make CSS[Name-of-the-class] = array(key => value)

            $regexp = '/\\s*?(\\S+?):(.+?);/si';

            for ($i = 0, $iMax = count($extstyle[1]); $i < $iMax; $i++) {
                preg_match_all($regexp, $extstyle[2][$i], $extstyleinfo);

                $extproperties = $extstyleinfo[1];

                $extvalues = $extstyleinfo[2];

                for ($j = 0, $jMax = count($extproperties); $j < $jMax; $j++) {
                    //Array-properties and Array-values must have the SAME SIZE!

                    $extclassproperties[mb_strtoupper($extproperties[$j])] = trim($extvalues[$j]);
                }

                $this->CSS[$extstyle[1][$i]] = $extclassproperties;

                $extproperties = [];

                $extvalues = [];

                $extclassproperties = [];
            }

            $match--;

            $ind++;
        } //end of match

        $match = 0; // reset value, if needed

        //CSS internal
        //Get content between tags and order it, using regexp
        $regexp = '/<style.*?>(.*?)<\/style>/si'; // it can be <style> or <style type="txt/css">
        $match = preg_match($regexp, $html, $CSSblock);

        if ($match) {
            //Get class/id name and its characteristics from $CSSblock[1]
            $regexp = '/[.#]([^.]+?)\\s*?\{(.+?)\}/s'; // '/s' PCRE_DOTALL including \n
            preg_match_all($regexp, $CSSblock[1], $style);

            //Make CSS[Name-of-the-class] = array(key => value)

            $regexp = '/\\s*?(\\S+?):(.+?);/si';

            for ($i = 0, $iMax = count($style[1]); $i < $iMax; $i++) {
                preg_match_all($regexp, $style[2][$i], $styleinfo);

                $properties = $styleinfo[1];

                $values = $styleinfo[2];

                for ($j = 0, $jMax = count($properties); $j < $jMax; $j++) {
                    //Array-properties and Array-values must have the SAME SIZE!

                    $classproperties[mb_strtoupper($properties[$j])] = trim($values[$j]);
                }

                $this->CSS[$style[1][$i]] = $classproperties;

                $properties = [];

                $values = [];

                $classproperties = [];
            }
        } // end of match

        //print_r($this->CSS);// Important debug-line!

        //Remove CSS (tags and content), if any
        $regexp = '/<style.*?>(.*?)<\/style>/si'; // it can be <style> or <style type="txt/css">
        $html = preg_replace($regexp, '', $html);

        return $html;
    }

    public function setCSS($array)
    {
        //! @return void

        foreach ($array as $k => $v) {
            switch ($k) {
                case 'WIDTH':
                    $this->divwidth = ConvertSize($v, $this->pgwidth);
                    break;
                case 'HEIGHT':
                    $this->divheight = ConvertSize($v, $this->pgwidth);
                    break;
                case 'BORDER': // width style color (width not supported correctly - it is always considered as normal)
                    $prop = explode(' ', $v);
                    if (3 != count($prop)) {
                        break;
                    } // It does not support: borders not fully declared
                    //style: dashed dotted none (anything else => solid )
                    if (0 == strnatcasecmp($prop[1], 'dashed')) { //found "dashed"! (ignores case)
                        $this->dash_on = true;

                        $this->SetDash(2, 2); //2mm on, 2mm off
                    } elseif (0 == strnatcasecmp($prop[1], 'dotted')) { //found "dotted"! (ignores case)
                        $this->dotted_on = true;
                    } elseif (0 == strnatcasecmp($prop[1], 'none')) {
                        $this->divborder = 0;
                    } else {
                        $this->divborder = 1;
                    }
                    //color
                    $coul = ConvertColor($prop[2]);
                    $this->SetDrawColor($coul['R'], $coul['G'], $coul['B']);
                    $this->issetcolor = true;
                    break;
                case 'FONT-FAMILY': // one of the $this->fontlist fonts
                    if (in_array(mb_strtolower($v), $this->fontlist, true)) {
                        $this->SetFont(mb_strtolower($v));
                    }
                    break;
                case 'FONT-SIZE':
                    $this->SetFontSize(ConvertSize($v, $this->pgwidth));
                    break;
                case 'FONT-STYLE': // italic normal oblique
                    switch (mb_strtoupper($v)) {
                        case 'ITALIC':
                        case 'OBLIQUE':
                            $this->SetStyle('I', true);
                            break;
                        case 'NORMAL':
                            break;
                    }
                    break;
                case 'FONT-WEIGHT': // normal bold
                    switch (mb_strtoupper($v)) {
                        case 'BOLD':
                            $this->SetStyle('B', true);
                            break;
                        case 'NORMAL':
                            break;
                    }
                    break;
                case 'TEXT-DECORATION': // none underline
                    switch (mb_strtoupper($v)) {
                        case 'UNDERLINE':
                            $this->SetStyle('U', true);
                            break;
                        case 'NONE':
                            break;
                    }
                    // no break
                case 'TEXT-TRANSFORM': // none uppercase lowercase
                    switch (mb_strtoupper($v)) { //Not working 100%
                        case 'UPPERCASE':
                            $this->toupper = true;
                            break;
                        case 'LOWERCASE':
                            $this->tolower = true;
                            break;
                        case 'NONE':
                            break;
                    }
                    // no break
                case 'TEXT-ALIGN': //left right center justify
                    switch (mb_strtoupper($v)) {
                        case 'LEFT':
                            $this->divalign = 'L';
                            break;
                        case 'CENTER':
                            $this->divalign = 'C';
                            break;
                        case 'RIGHT':
                            $this->divalign = 'R';
                            break;
                        case 'JUSTIFY':
                            $this->divalign = 'J';
                            break;
                    }
                    break;
                case 'DIRECTION': //ltr(default) rtl
                    if ('rtl' == mb_strtolower($v)) {
                        $this->divrevert = true;
                    }
                    break;
                case 'BACKGROUND': // bgcolor only
                    $coul = ConvertColor($v);
                    $this->SetFillColor($coul['R'], $coul['G'], $coul['B']);
                    $this->divbgcolor = true;
                    break;
                case 'COLOR': // font color
                    $coul = ConvertColor($v);
                    $this->colorarray = $coul;
                    $this->SetTextColor($coul['R'], $coul['G'], $coul['B']);
                    $this->issetcolor = true;
                    break;
            }//end of switch
        }//end of foreach
    }

    public function SetStyle($tag, $enable)
    {
        //! @return void

        //Modify style and select corresponding font

        $this->$tag += ($enable ? 1 : -1);

        $style = '';

        //Fix some SetStyle misuse

        if ($this->$tag < 0) {
            $this->$tag = 0;
        }

        if ($this->$tag > 1) {
            $this->$tag = 1;
        }

        foreach (['B', 'I', 'U'] as $s) {
            if ($this->$s > 0) {
                $style .= $s;
            }
        }

        $this->currentstyle = $style;

        $this->SetFont('', $style);
    }

    public function PutLink($URL, $txt)
    {
        //! @return void

        //Put a hyperlink

        $this->SetTextColor(0, 0, 255);

        $this->SetStyle('U', true);

        $this->Write(5, $txt, $this->x, $URL);

        $this->SetStyle('U', false);

        $this->SetTextColor(0);
    }

    public function DisableTags($str = '')
    {
        //! @return void
        //! @desc Disable some tags using ',' as separator. Enable all tags calling this function without parameters.
        if ('' == $str) { //enable all tags
            //Insert new supported tags in the long string below.
            $this->enabledtags = '<s><strike><del><bdo><big><small><address><ins><cite><font><center><sup><sub><input><select><option><textarea><title><form><ol><ul><li><h1><h2><h3><h4><pre><b><u><i><a><img><p><br><strong><em><code><th><tr><blockquote><hr><td><tr><table><div>';
        } else {
            $str = explode(',', $str);

            foreach ($str as $v) {
                $this->enabledtags = str_replace(trim($v), '', $this->enabledtags);
            }
        }
    }

    ////////////////////////TABLE CODE (from PDFTable)/////////////////////////////////////

    //Thanks to vietcom (vncommando at yahoo dot com)

    /*     Modified by Renato Coelho
   in order to print tables that span more than 1 page and to allow
   bold,italic and the likes inside table cells (alignment is lost when this is used)
*/

    /* * returns int
   * description Tinh so dong cua $txt khi hien thi trong cell co width la $w
*/

    public function _countLine($w, $txt)
    {
        //! @return int

        //Computes the number of lines a MultiCell of width w will take

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

        $sep = -1;

        $i = $j = $l = 0;

        $nl = 1;

        while ($i < $nb) {
            $c = $s[$i];

            if ("\n" == $c) {
                $i++;

                $sep = -1;

                $j = $i;

                $l = 0;

                $nl++;

                continue;
            }

            if (' ' == $c) {
                $sep = $i;
            }

            $l += $cw[$c];

            if ($l > $wmax) {
                if (-1 == $sep) {
                    if ($i == $j) {
                        $i++;
                    }
                } else {
                    $i = $sep + 1;
                }

                $sep = -1;

                $j = $i;

                $l = 0;

                $nl++;
            } else {
                $i++;
            }
        }

        return $nl;
    }

    //table		Array of (w, h, bc, nr, wc, hr, cells)

    //w			Width of table

    //h			Height of table

    //nc		Number column

    //nr		Number row

    //hr		List of height of each row

    //wc		List of width of each column

    //cells		List of cells of each rows, cells[i][j] is a cell in table

    public function _tableColumnWidth(&$table)
    {
        //! @return void

        $cs = &$table['cells'];

        $mw = $this->GetStringWidth('W');

        $nc = $table['nc'];

        $nr = $table['nr'];

        $listspan = [];

        //Xac dinh do rong cua cac cell va cac cot tuong ung

        for ($j = 0; $j < $nc; $j++) {
            $wc = &$table['wc'][$j];

            for ($i = 0; $i < $nr; $i++) {
                if (isset($cs[$i][$j]) && $cs[$i][$j]) {
                    $c = &$cs[$i][$j];

                    $miw = $mw;

                    $c['maw'] = $c['s'];

                    if (isset($c['nowrap'])) {
                        $miw = $c['maw'];
                    }

                    if (isset($c['w'])) {
                        if ($miw < $c['w']) {
                            $c['miw'] = $c['w'];
                        }

                        if ($miw > $c['w']) {
                            $c['miw'] = $c['w'] = $miw;
                        }

                        if (!isset($wc['w'])) {
                            $wc['w'] = 1;
                        }
                    } else {
                        $c['miw'] = $miw;
                    }

                    if ($c['maw'] < $c['miw']) {
                        $c['maw'] = $c['miw'];
                    }

                    if (!isset($c['colspan'])) {
                        if ($wc['miw'] < $c['miw']) {
                            $wc['miw'] = $c['miw'];
                        }

                        if ($wc['maw'] < $c['maw']) {
                            $wc['maw'] = $c['maw'];
                        }
                    } else {
                        $listspan[] = [$i, $j];
                    }
                }
            }
        }

        //Xac dinh su anh huong cua cac cell colspan len cac cot va nguoc lai

        $wc = &$table['wc'];

        foreach ($listspan as $span) {
            [$i, $j] = $span;

            $c = &$cs[$i][$j];

            $lc = $j + $c['colspan'];

            if ($lc > $nc) {
                $lc = $nc;
            }

            $wis = $wisa = 0;

            $was = $wasa = 0;

            $list = [];

            for ($k = $j; $k < $lc; $k++) {
                $wis += $wc[$k]['miw'];

                $was += $wc[$k]['maw'];

                if (!isset($c['w'])) {
                    $list[] = $k;

                    $wisa += $wc[$k]['miw'];

                    $wasa += $wc[$k]['maw'];
                }
            }

            if ($c['miw'] > $wis) {
                if (!$wis) {//Cac cot chua co kich thuoc => chia deu
                    for ($k = $j; $k < $lc; $k++) {
                        $wc[$k]['miw'] = $c['miw'] / $c['colspan'];
                    }
                } elseif (!count($list)) {//Khong co cot nao co kich thuoc auto => chia deu phan du cho tat ca
                    $wi = $c['miw'] - $wis;

                    for ($k = $j; $k < $lc; $k++) {
                        $wc[$k]['miw'] += ($wc[$k]['miw'] / $wis) * $wi;
                    }
                } else {//Co mot so cot co kich thuoc auto => chia deu phan du cho cac cot auto
                    $wi = $c['miw'] - $wis;

                    foreach ($list as $k) {
                        $wc[$k]['miw'] += ($wc[$k]['miw'] / $wisa) * $wi;
                    }
                }
            }

            if ($c['maw'] > $was) {
                if (!$wis) {//Cac cot chua co kich thuoc => chia deu
                    for ($k = $j; $k < $lc; $k++) {
                        $wc[$k]['maw'] = $c['maw'] / $c['colspan'];
                    }
                } elseif (!count($list)) {//Khong co cot nao co kich thuoc auto => chia deu phan du cho tat ca
                    $wi = $c['maw'] - $was;

                    for ($k = $j; $k < $lc; $k++) {
                        $wc[$k]['maw'] += ($wc[$k]['maw'] / $was) * $wi;
                    }
                } else {//Co mot so cot co kich thuoc auto => chia deu phan du cho cac cot auto
                    $wi = $c['maw'] - $was;

                    foreach ($list as $k) {
                        $wc[$k]['maw'] += ($wc[$k]['maw'] / $wasa) * $wi;
                    }
                }
            }
        }
    }

    /**
     * @desc Xac dinh chieu rong cua table
     * @param mixed $table
     */

    public function _tableWidth(&$table)
    {
        //! @return void

        $wc = &$table['wc'];

        $nc = $table['nc'];

        $a = 0;

        for ($i = 0; $i < $nc; $i++) {
            $a += isset($wc[$i]['w']) ? $wc[$i]['miw'] : $wc[$i]['maw'];
        }

        if ($a > $this->pgwidth) {
            $table['w'] = $this->pgwidth;
        }

        if (isset($table['w'])) {
            $wis = $wisa = 0;

            $list = [];

            for ($i = 0; $i < $nc; $i++) {
                $wis += $wc[$i]['miw'];

                if (!isset($wc[$i]['w'])) {
                    $list[] = $i;

                    $wisa += $wc[$i]['miw'];
                }
            }

            if ($table['w'] > $wis) {
                if (!count($list)) {//Khong co cot nao co kich thuoc auto => chia deu phan du cho tat ca
                    //$wi = $table['w'] - $wis;

                    $wi = ($table['w'] - $wis) / $nc;

                    for ($k = 0; $k < $nc; $k++) { //$wc[$k]['miw'] += ($wc[$k]['miw']/$wis)*$wi;
                        $wc[$k]['miw'] += $wi;
                    }
                } else {//Co mot so cot co kich thuoc auto => chia deu phan du cho cac cot auto
                    //$wi = $table['w'] - $wis;

                    $wi = ($table['w'] - $wis) / count($list);

                    foreach ($list as $k) { //$wc[$k]['miw'] += ($wc[$k]['miw']/$wisa)*$wi;
                        $wc[$k]['miw'] += $wi;
                    }
                }
            }

            for ($i = 0; $i < $nc; $i++) {
                $a = $wc[$i]['miw'];

                unset($wc[$i]);

                $wc[$i] = $a;
            }
        } else {
            $table['w'] = $a;

            for ($i = 0; $i < $nc; $i++) {
                $a = isset($wc[$i]['w']) ? $wc[$i]['miw'] : $wc[$i]['maw'];

                unset($wc[$i]);

                $wc[$i] = $a;
            }
        }
    }

    public function _tableHeight(&$table)
    {
        //! @return void

        $cs = &$table['cells'];

        $nc = $table['nc'];

        $nr = $table['nr'];

        $listspan = [];

        for ($i = 0; $i < $nr; $i++) {
            $hr = &$table['hr'][$i];

            for ($j = 0; $j < $nc; $j++) {
                if (isset($cs[$i][$j]) && $cs[$i][$j]) {
                    $c = &$cs[$i][$j];

                    [$x, $cw] = $this->_tableGetWidth($table, $i, $j);

                    $ch = $this->_countLine($cw, implode("\n", $c['text'])) * $this->FontSizePt / 2;

                    //If height is bigger than page height...

                    if ($ch > ($this->fh - $this->bMargin - $this->tMargin)) {
                        $ch = ($this->fh - $this->bMargin - $this->tMargin);
                    }

                    if (isset($c['h']) && $c['h'] > $ch) {
                        $ch = $c['h'];
                    }

                    if (isset($c['rowspan'])) {
                        $listspan[] = [$i, $j];
                    } elseif ($hr < $ch) {
                        $hr = $ch;
                    }

                    $c['mih'] = $ch;
                }
            }
        }

        $hr = &$table['hr'];

        foreach ($listspan as $span) {
            [$i, $j] = $span;

            $c = &$cs[$i][$j];

            $lr = $i + $c['rowspan'];

            if ($lr > $nr) {
                $lr = $nr;
            }

            $hs = $hsa = 0;

            $list = [];

            for ($k = $i; $k < $lr; $k++) {
                $hs += $hr[$k];

                if (!isset($c['h'])) {
                    $list[] = $k;

                    $hsa += $hr[$k];
                }
            }

            if ($c['mih'] > $hs) {
                if (!$hs) {//Cac dong chua co kich thuoc => chia deu
                    for ($k = $i; $k < $lr; $k++) {
                        $hr[$k] = $c['mih'] / $c['rowspan'];
                    }
                } elseif (!count($list)) {//Khong co dong nao co kich thuoc auto => chia deu phan du cho tat ca
                    $hi = $c['mih'] - $hs;

                    for ($k = $i; $k < $lr; $k++) {
                        $hr[$k] += ($hr[$k] / $hs) * $hi;
                    }
                } else {//Co mot so dong co kich thuoc auto => chia deu phan du cho cac dong auto
                    $hi = $c['mih'] - $hsa;

                    foreach ($list as $k) {
                        $hr[$k] += ($hr[$k] / $hsa) * $hi;
                    }
                }
            }
        }
    }

    /**
     * @desc Xac dinh toa do va do rong cua mot cell
     * @param mixed $table
     * @param mixed $i
     * @param mixed $j
     * @return array|int[]
     * @return array|int[]
     */

    public function _tableGetWidth(&$table, $i, $j)
    {
        //! @return array(x,w)

        $c = &$table['cells'][$i][$j];

        if ($c) {
            if (isset($c['x0'])) {
                return [$c['x0'], $c['w0']];
            }

            $x = 0;

            $wc = &$table['wc'];

            for ($k = 0; $k < $j; $k++) {
                $x += $wc[$k];
            }

            $w = $wc[$j];

            if (isset($c['colspan'])) {
                for ($k = $j + $c['colspan'] - 1; $k > $j; $k--) {
                    $w += $wc[$k];
                }
            }

            $c['x0'] = $x;

            $c['w0'] = $w;

            return [$x, $w];
        }

        return [0, 0];
    }

    public function _tableGetHeight(&$table, $i, $j)
    {
        //! @return array(y,h)

        $c = &$table['cells'][$i][$j];

        if ($c) {
            if (isset($c['y0'])) {
                return [$c['y0'], $c['h0']];
            }

            $y = 0;

            $hr = &$table['hr'];

            for ($k = 0; $k < $i; $k++) {
                $y += $hr[$k];
            }

            $h = $hr[$i];

            if (isset($c['rowspan'])) {
                for ($k = $i + $c['rowspan'] - 1; $k > $i; $k--) {
                    $h += $hr[$k];
                }
            }

            $c['y0'] = $y;

            $c['h0'] = $h;

            return [$y, $h];
        }

        return [0, 0];
    }

    public function _tableRect($x, $y, $w, $h, $type = 1)
    {
        //! @return void

        if (1 == $type) {
            $this->Rect($x, $y, $w, $h);
        } elseif (4 == mb_strlen($type)) {
            $x2 = $x + $w;

            $y2 = $y + $h;

            if ((int)$type[0]) {
                $this->Line($x, $y, $x2, $y);
            }

            if ((int)$type[1]) {
                $this->Line($x2, $y, $x2, $y2);
            }

            if ((int)$type[2]) {
                $this->Line($x, $y2, $x2, $y2);
            }

            if ((int)$type[3]) {
                $this->Line($x, $y, $x, $y2);
            }
        }
    }

    public function _tableWrite(&$table)
    { //EDITEI
        //! @return void

        $cs = &$table['cells'];

        $nc = $table['nc'];

        $nr = $table['nr'];

        $x0 = $this->x;

        $y0 = $this->y;

        $right = $this->pgwidth - $this->rMargin;

        if (isset($table['a']) and ($table['w'] != $this->pgwidth)) {
            if ('C' == $table['a']) {
                $x0 += (($right - $x0) - $table['w']) / 2;
            } elseif ('R' == $table['a']) {
                $x0 = $right - $table['w'];
            }
        }

        $returny = 0;

        //Draw Table Contents and Borders
        for ($i = 0; $i < $nr; $i++) { //rows
            $skippage = false;

            for ($j = 0; $j < $nc; $j++) { //columns
                if (isset($cs[$i][$j]) && $cs[$i][$j]) {
                    $c = &$cs[$i][$j];

                    [$x, $w] = $this->_tableGetWidth($table, $i, $j);

                    [$y, $h] = $this->_tableGetHeight($table, $i, $j);

                    $x += $x0;

                    $y += $y0;

                    $y -= $returny;

                    if ((($y + $h) > ($this->fh - $this->bMargin)) && ($y0 > 0 || $x0 > 0)) {
                        if (!$skippage) {
                            $y -= $y0;

                            $returny += $y;

                            $this->AddPage();

                            $y0 = $this->tMargin;

                            $y = $y0;
                        }

                        $skippage = true;
                    }

                    //Align

                    $this->x = $x;

                    $this->y = $y;

                    $align = $c['a'] ?? 'L';

                    //Vertical align

                    if (!isset($c['va']) || 'M' == $c['va']) {
                        $this->y += ($h - $c['mih']) / 2;
                    } elseif (isset($c['va']) && 'B' == $c['va']) {
                        $this->y += $h - $c['mih'];
                    }

                    //Fill

                    $fill = $c['bgcolor'] ?? ($table['bgcolor'][$i] ?? ($table['bgcolor'][-1] ?? 0));

                    if ($fill) {
                        $color = ConvertColor($fill);

                        $this->SetFillColor($color['R'], $color['G'], $color['B']);

                        $this->Rect($x, $y, $w, $h, 'F');
                    }

                    //Border

                    if (isset($c['border'])) {
                        $this->_tableRect($x, $y, $w, $h, $c['border']);
                    } elseif (isset($table['border']) && $table['border']) {
                        $this->Rect($x, $y, $w, $h);
                    }

                    //Content (EDITEI - now accepts bold italic underline inside table!)

                    $c['text'] = implode('', $c['text']);

                    //BUG?

                    //WORDWRAP eliminates some spaces creating differences between text and textbuffer?!?

                    //paliative fix: if w == pgwidth dont call WordWrap, let it wordwrap automatically

                    if ($w != $this->pgwidth) {
                        $this->WordWrap($c['text'], $w - 2);
                    } // -2 to leave a small margin

                    $this->divalign = $align;

                    $this->divwidth = $w;

                    $this->divheight = $this->FontSizePt / 2;

                    if (!empty($c['textbuffer'])) {
                        $this->printbuffer($c['textbuffer'], true, $c['text']);
                    }

                    $this->divalign = 'L';

                    $this->divwidth = 0;

                    $this->divheight = 0;
                }//end of (if isset...)
            }// end of cols
            if ($i == $nr - 1) {
                $this->y = $y + $h;
            } //last row jump (update this->y position)
        }// end of rows
    }

    /////////////////////////END OF TABLE CODE//////////////////////////////////
}//end of class

/*
----  JUNK(?) CODE: ------

 // <? <- this fixes HIGHLIGHT PSPAD bug ... --REMOVE THIS LINE---

            if( $attr['BGCOLOR'] != '' ) {
                $coul=hex2dec($attr['BGCOLOR']);
                $this->SetFillColor($coul['R'],$coul['G'],$coul['B']);
                $this->tdbgcolor=true;
            }

// ALIGNment not working properly (temp code)
        if($align=='R')
            $dx=$w-$this->cMargin-$this->GetStringWidth($txt);
        elseif($align=='C')
            $dx=($w-$this->GetStringWidth($txt))/2;
        else
            $dx=$this->cMargin;

TABLE ALIGN
           if($this->tablealign=='R')
                $dx=$table->bestwidth[$j] - $this->cMargin - $this->GetStringWidth($this->tablecontent[$i][$j]);
              elseif($this->tablealign=='C')
                 $dx=($bestwidth[$j] - $this->GetStringWidth($this->tablecontent[$i][$j]))/2;
              else
                 $dx=$this->cMargin;
CHANGE rectangle style (filled draw or both)
    if($style=='F') $op='f';
    elseif($style=='FD' or $style=='DF') $op='B';
    else $op='S';

//Fix name=something to name="something"
//	$regexp = '/<a name=(\\S+?)>/si';
//	$html = preg_replace($regexp,'<a name=' . "\"\$1\"" . '>',$html);

//Below prepare the text, if needed
//    if (!preg_match('/\\S/',$e)) continue; //$e must have at least one non-space
// <b>something</b> <i>and</i> inner space vanishes with upper regexp => somethingand

*/
