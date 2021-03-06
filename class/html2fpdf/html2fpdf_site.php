<?php

/*
*** Site-oriented version

//////////////////////////////////////////////////////////////////////////////
//////////////DO NOT MODIFY THE CONTENTS OF THIS BOX//////////////////////////
//////////////////////////////////////////////////////////////////////////////
//                                                                          //
// HTML2FPDF is a php script to read a HTML text and generate a PDF file.   //
// Copyright (C) 2004 Renato Coelho                                         //
// This script may be distributed as long as the following files are kept   //
// together: 	                                            							    //
//	                                                                		    //
// fpdf.php, html2fpdf.php, gif.php, license.txt,credits.txt,htmltoolkit.php//
//                                                                          //
//////////////////////////////////////////////////////////////////////////////
*/
require_once __DIR__ . '/html2fpdf.php';

class PDF extends HTML2FPDF
{
    public function __construct()
    {
        //! @return A class instance

        //! @desc Constructor

        //Call parent constructor

        parent::__construct();

        //Disable some tags

        $this->DisableTags('<big>,<small>');

        //Disable <title>/CSS/<pre> in order to increase script performance

        $this->usetitle = false;

        $this->usecss = false;

        $this->usepre = false;
    }

    //Common Logo for all HTML files (Montfort)

    public function InitLogo($src)
    {
        //! @desc Insert Image Logo on 1st page

        //! @return void

        if ('' == $src) {
            return;
        }

        $this->y = $this->tMargin - 45;

        $this->x = $this->lMargin;

        $halfwidth = $this->pgwidth / 2;

        $sizesarray = $this->Image($src, $this->GetX(), $this->GetY(), 0, 0, '', '', false);

        //Alinhar imagem ao centro

        $this->x = ($halfwidth - ($sizesarray['WIDTH'] / 2));

        $sizesarray = $this->Image($src, $this->GetX(), $this->GetY(), 0, 0, '', 'http://www.montfort.org.br/');

        $this->Ln(1);

        //Contruir <HR> particular

        $this->SetLineWidth(0.3);

        $this->Line($this->x, $this->y, $this->x + $this->pgwidth, $this->y);

        $this->SetLineWidth(0.3);

        $this->Ln(2);
    }

    //Put title in page

    public function PutTitle($titulo)
    {
        //! @desc Insert Title on 1st page

        //! @return void

        $this->SetTitle($titulo);

        $this->Ln(4);

        $this->SetFont('Arial', 'B', 22);

        $this->divalign = 'C';

        $this->divwidth = $this->pgwidth;

        $this->divheight = 8.5;

        //Custom Word Wrap (para melhorar organização das palvras no titulo)

        $maxwidth = $this->divwidth;

        $titulo = trim($titulo);

        $words = preg_preg_split('/ +/', $titulo);

        $space = $this->GetStringWidth(' ');

        $titulo = '';

        $width = 0;

        $numwords = count($words);

        for ($i = 0; $i < $numwords; $i++) {
            $word = $words[$i];

            if ($i + 1 < $numwords) {
                $nextword = $words[$i + 1];
            } else {
                $nextword = '';
            }

            $wordwidth = $this->GetStringWidth($word);

            $nextwordwidth = $this->GetStringWidth($nextword);

            if ((mb_strlen($word) <= 3) and ('' != $nextword) and ($width + $wordwidth + $nextwordwidth > $maxwidth)) {
                //Para não ficar um artigo/preposição esquecido(a) no final de uma linha

                $width = $wordwidth + $space;

                $titulo = rtrim($titulo) . "\n" . $word . ' ';
            } elseif ($width + $wordwidth <= $maxwidth) { //Palavra cabe, inserir
                $width += $wordwidth + $space;

                $titulo .= $word . ' ';
            } else { //Palavra não cabe, pular linha e inserir na outra linha
                $width = $wordwidth + $space;

                $titulo = rtrim($titulo) . "\n" . $word . ' ';
            }
        }

        $titulo = rtrim($titulo);

        //End of Custom WordWrap

        $this->textbuffer[] = [$titulo, '', '', []];

        //Print content

        $this->printbuffer($this->textbuffer);

        //Reset values

        $this->textbuffer = [];

        $this->divwidth = 0;

        $this->divheight = 0;

        $this->divalign = 'L';

        $this->SetFont('Arial', '', 11);

        $this->Ln(4);

        //Contruir <HR> particular

        $this->SetLineWidth(0.3);

        $this->Line($this->x, $this->y, $this->x + $this->pgwidth, $this->y);

        $this->SetLineWidth(0.3);

        $this->Ln(2);
    }

    //Put author in page

    public function PutAuthor($autor)
    {
        //! @desc Insert Author on 1st page

        //! @return void

        $this->SetAuthor($autor);

        $this->SetFont('Arial', '', 14);

        $this->SetStyle('B', true);

        $this->SetStyle('I', true);

        $texto = 'por ' . $autor; //'by author'

        $this->MultiCell(0, 5, $texto, 0, 'R');

        $this->SetFont('Arial', '', 11);

        $this->SetStyle('B', false);

        $this->SetStyle('I', false);
    }

    //Page footer

    public function Footer()
    {
        //! @desc Insert footer on every page

        //! @return void

        //Position at 1.0 cm from bottom

        $this->SetY(-10);

        //Copyright //especial para esta versão

        $this->SetFont('Arial', 'B', 9);

        $this->SetTextColor(0);

        $texto = 'Copyright ' . chr(169) . ' 1999-' . date('Y') . '  -  Associação Cultural Montfort  -  ';

        $this->Cell($this->GetStringWidth($texto), 10, $texto, 0, 0, 'L');

        $this->SetTextColor(0, 0, 255);

        $this->SetStyle('U', true);

        $this->SetStyle('B', false);

        $this->Cell(0, 10, 'http://www.montfort.org.br/', 0, 0, 'L', 0, 'http://www.montfort.org.br/');

        $this->SetStyle('U', false);

        $this->SetTextColor(0);

        //Arial italic 9

        $this->SetFont('Arial', 'I', 9);

        //Page number

        $this->Cell(0, 10, 'Pág. ' . $this->PageNo() . '/{nb}', 0, 0, 'R');

        //Return Font to normal

        $this->SetFont('Arial', '', 11);
    }
}//end of class
