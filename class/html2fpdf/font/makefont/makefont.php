<?php
/*******************************************************************************
 * Utility to generate font definition files                                    *
 * Version: 1.12                                                                *
 * Date:    2003-12-30                                                          *
 ******************************************************************************
 * @param $enc
 * @return array
 */

function ReadMap($enc)
{
    //Read a map file

    $file = __DIR__ . '/' . mb_strtolower($enc) . '.map';

    $a = file($file);

    if (empty($a)) {
        die('<B>Error:</B> encoding not found: ' . $enc);
    }

    $cc2gn = [];

    foreach ($a as $l) {
        if ('!' == $l[0]) {
            $e = preg_preg_split('/[ \\t]+/', rtrim($l));

            $cc = hexdec(mb_substr($e[0], 1));

            $gn = $e[2];

            $cc2gn[$cc] = $gn;
        }
    }

    for ($i = 0; $i <= 255; $i++) {
        if (!isset($cc2gn[$i])) {
            $cc2gn[$i] = '.notdef';
        }
    }

    return $cc2gn;
}

function ReadAFM($file, &$map)
{
    //Read a font metric file

    $a = file($file);

    if (empty($a)) {
        die('File not found');
    }

    $widths = [];

    $fm = [];

    $fix = [
        'Edot' => 'Edotaccent',
        'edot' => 'edotaccent',
        'Idot' => 'Idotaccent',
        'Zdot' => 'Zdotaccent',
        'zdot' => 'zdotaccent',
        'Odblacute' => 'Ohungarumlaut',
        'odblacute' => 'ohungarumlaut',
        'Udblacute' => 'Uhungarumlaut',
        'udblacute' => 'uhungarumlaut',
        'Gcedilla' => 'Gcommaaccent',
        'gcedilla' => 'gcommaaccent',
        'Kcedilla' => 'Kcommaaccent',
        'kcedilla' => 'kcommaaccent',
        'Lcedilla' => 'Lcommaaccent',
        'lcedilla' => 'lcommaaccent',
        'Ncedilla' => 'Ncommaaccent',
        'ncedilla' => 'ncommaaccent',
        'Rcedilla' => 'Rcommaaccent',
        'rcedilla' => 'rcommaaccent',
        'Scedilla' => 'Scommaaccent',
        'scedilla' => 'scommaaccent',
        'Tcedilla' => 'Tcommaaccent',
        'tcedilla' => 'tcommaaccent',
        'Dslash' => 'Dcroat',
        'dslash' => 'dcroat',
        'Dmacron' => 'Dcroat',
        'dmacron' => 'dcroat',
        'combininggraveaccent' => 'gravecomb',
        'combininghookabove' => 'hookabovecomb',
        'combiningtildeaccent' => 'tildecomb',
        'combiningacuteaccent' => 'acutecomb',
        'combiningdotbelow' => 'dotbelowcomb',
        'dongsign' => 'dong',
    ];

    foreach ($a as $l) {
        $e = explode(' ', rtrim($l));

        if (count($e) < 2) {
            continue;
        }

        $code = $e[0];

        $param = $e[1];

        if ('C' == $code) {
            //Character metrics

            $cc = (int)$e[1];

            $w = $e[4];

            $gn = $e[7];

            if ('20AC' == mb_substr($gn, -4)) {
                $gn = 'Euro';
            }

            if (isset($fix[$gn])) {
                //Fix incorrect glyph name

                foreach ($map as $c => $n) {
                    if ($n == $fix[$gn]) {
                        $map[$c] = $gn;
                    }
                }
            }

            if (empty($map)) {
                //Symbolic font: use built-in encoding

                $widths[$cc] = $w;
            } else {
                $widths[$gn] = $w;

                if ('X' == $gn) {
                    $fm['CapXHeight'] = $e[13];
                }
            }

            if ('.notdef' == $gn) {
                $fm['MissingWidth'] = $w;
            }
        } elseif ('FontName' == $code) {
            $fm['FontName'] = $param;
        } elseif ('Weight' == $code) {
            $fm['Weight'] = $param;
        } elseif ('ItalicAngle' == $code) {
            $fm['ItalicAngle'] = (float)$param;
        } elseif ('Ascender' == $code) {
            $fm['Ascender'] = (int)$param;
        } elseif ('Descender' == $code) {
            $fm['Descender'] = (int)$param;
        } elseif ('UnderlineThickness' == $code) {
            $fm['UnderlineThickness'] = (int)$param;
        } elseif ('UnderlinePosition' == $code) {
            $fm['UnderlinePosition'] = (int)$param;
        } elseif ('IsFixedPitch' == $code) {
            $fm['IsFixedPitch'] = ('true' == $param);
        } elseif ('FontBBox' == $code) {
            $fm['FontBBox'] = [$e[1], $e[2], $e[3], $e[4]];
        } elseif ('CapHeight' == $code) {
            $fm['CapHeight'] = (int)$param;
        } elseif ('StdVW' == $code) {
            $fm['StdVW'] = (int)$param;
        }
    }

    if (!isset($fm['FontName'])) {
        die('FontName not found');
    }

    if (!empty($map)) {
        if (!isset($widths['.notdef'])) {
            $widths['.notdef'] = 600;
        }

        if (!isset($widths['Delta']) and isset($widths['increment'])) {
            $widths['Delta'] = $widths['increment'];
        }

        //Order widths according to map

        for ($i = 0; $i <= 255; $i++) {
            if (!isset($widths[$map[$i]])) {
                echo '<B>Warning:</B> character ' . $map[$i] . ' is missing<BR>';

                $widths[$i] = $widths['.notdef'];
            } else {
                $widths[$i] = $widths[$map[$i]];
            }
        }
    }

    $fm['Widths'] = $widths;

    return $fm;
}

function MakeFontDescriptor($fm, $symbolic)
{
    //Ascent

    $asc = ($fm['Ascender'] ?? 1000);

    $fd = "array('Ascent'=>" . $asc;

    //Descent

    $desc = ($fm['Descender'] ?? -200);

    $fd .= ",'Descent'=>" . $desc;

    //CapHeight

    if (isset($fm['CapHeight'])) {
        $ch = $fm['CapHeight'];
    } elseif (isset($fm['CapXHeight'])) {
        $ch = $fm['CapXHeight'];
    } else {
        $ch = $asc;
    }

    $fd .= ",'CapHeight'=>" . $ch;

    //Flags

    $flags = 0;

    if (isset($fm['IsFixedPitch']) and $fm['IsFixedPitch']) {
        $flags += 1 << 0;
    }

    if ($symbolic) {
        $flags += 1 << 2;
    }

    if (!$symbolic) {
        $flags += 1 << 5;
    }

    if (isset($fm['ItalicAngle']) and 0 != $fm['ItalicAngle']) {
        $flags += 1 << 6;
    }

    $fd .= ",'Flags'=>" . $flags;

    //FontBBox

    $fbb = $fm['FontBBox'] ?? [0, $des - 100, 1000, $asc + 100];

    $fd .= ",'FontBBox'=>'[" . $fbb[0] . ' ' . $fbb[1] . ' ' . $fbb[2] . ' ' . $fbb[3] . "]'";

    //ItalicAngle

    $ia = ($fm['ItalicAngle'] ?? 0);

    $fd .= ",'ItalicAngle'=>" . $ia;

    //StemV

    if (isset($fm['StdVW'])) {
        $stemv = $fm['StdVW'];
    } elseif (isset($fm['Weight']) and eregi('(bold|black)', $fm['Weight'])) {
        $stemv = 120;
    } else {
        $stemv = 70;
    }

    $fd .= ",'StemV'=>" . $stemv;

    //MissingWidth

    if (isset($fm['MissingWidth'])) {
        $fd .= ",'MissingWidth'=>" . $fm['MissingWidth'];
    }

    $fd .= ')';

    return $fd;
}

function MakeWidthArray($fm)
{
    //Make character width array

    $s = "array(\n\t";

    $cw = $fm['Widths'];

    for ($i = 0; $i <= 255; $i++) {
        if ("'" == chr($i)) {
            $s .= "'\\''";
        } elseif ('\\' == chr($i)) {
            $s .= "'\\\\'";
        } elseif ($i >= 32 and $i <= 126) {
            $s .= "'" . chr($i) . "'";
        } else {
            $s .= "chr($i)";
        }

        $s .= '=>' . $fm['Widths'][$i];

        if ($i < 255) {
            $s .= ',';
        }

        if (0 == ($i + 1) % 22) {
            $s .= "\n\t";
        }
    }

    $s .= ')';

    return $s;
}

function MakeFontEncoding($map)
{
    //Build differences from reference encoding

    $ref = ReadMap('cp1252');

    $s = '';

    $last = 0;

    for ($i = 32; $i <= 255; $i++) {
        if ($map[$i] != $ref[$i]) {
            if ($i != $last + 1) {
                $s .= $i . ' ';
            }

            $last = $i;

            $s .= '/' . $map[$i] . ' ';
        }
    }

    return rtrim($s);
}

function SaveToFile($file, $s, $mode = 't')
{
    $f = fopen($file, 'w' . $mode);

    if (!$f) {
        die('Can\'t write to file ' . $file);
    }

    fwrite($f, $s, mb_strlen($s));

    fclose($f);
}

function ReadShort($f)
{
    $a = unpack('n1n', fread($f, 2));

    return $a['n'];
}

function ReadLong($f)
{
    $a = unpack('N1N', fread($f, 4));

    return $a['N'];
}

function CheckTTF($file)
{
    //Check if font license allows embedding

    $f = fopen($file, 'rb');

    if (!$f) {
        die('<B>Error:</B> Can\'t open ' . $file);
    }

    //Extract number of tables

    fseek($f, 4, SEEK_CUR);

    $nb = ReadShort($f);

    fseek($f, 6, SEEK_CUR);

    //Seek OS/2 table

    $found = false;

    for ($i = 0; $i < $nb; $i++) {
        if ('OS/2' == fread($f, 4)) {
            $found = true;

            break;
        }

        fseek($f, 12, SEEK_CUR);
    }

    if (!$found) {
        fclose($f);

        return;
    }

    fseek($f, 4, SEEK_CUR);

    $offset = ReadLong($f);

    fseek($f, $offset, SEEK_SET);

    //Extract fsType flags

    fseek($f, 8, SEEK_CUR);

    $fsType = ReadShort($f);

    $rl = 0 != ($fsType & 0x02);

    $pp = 0 != ($fsType & 0x04);

    $e = 0 != ($fsType & 0x08);

    fclose($f);

    if ($rl and !$pp and !$e) {
        echo '<B>Warning:</B> font license does not allow embedding';
    }
}

/*******************************************************************************
 * $fontfile: path to TTF file (or empty string if not to be embedded)          *
 * $afmfile:  path to AFM file                                                  *
 * $enc:      font encoding (or empty string for symbolic fonts)                *
 * $patch:    optional patch for encoding                                       *
 * $type :    font type if $fontfile is empty                                   *
 ******************************************************************************
 * @param        $fontfile
 * @param        $afmfile
 * @param string $enc
 * @param array  $patch
 * @param string $type
 */
function MakeFont($fontfile, $afmfile, $enc = 'cp1252', $patch = [], $type = 'TrueType')
{
    //Generate a font definition file

    set_magic_quotes_runtime(0);

    if ($enc) {
        $map = ReadMap($enc);

        foreach ($patch as $cc => $gn) {
            $map[$cc] = $gn;
        }
    } else {
        $map = [];
    }

    if (!file_exists($afmfile)) {
        die('<B>Error:</B> AFM file not found: ' . $afmfile);
    }

    $fm = ReadAFM($afmfile, $map);

    if ($enc) {
        $diff = MakeFontEncoding($map);
    } else {
        $diff = '';
    }

    $fd = MakeFontDescriptor($fm, empty($map));

    //Find font type

    if ($fontfile) {
        $ext = mb_strtolower(mb_substr($fontfile, -3));

        if ('ttf' == $ext) {
            $type = 'TrueType';
        } elseif ('pfb' == $ext) {
            $type = 'Type1';
        } else {
            die('<B>Error:</B> unrecognized font file extension: ' . $ext);
        }
    } else {
        if ('TrueType' != $type and 'Type1' != $type) {
            die('<B>Error:</B> incorrect font type: ' . $type);
        }
    }

    //Start generation

    $s = '<?php' . "\n";

    $s .= '$type=\'' . $type . "';\n";

    $s .= '$name=\'' . $fm['FontName'] . "';\n";

    $s .= '$desc=' . $fd . ";\n";

    if (!isset($fm['UnderlinePosition'])) {
        $fm['UnderlinePosition'] = -100;
    }

    if (!isset($fm['UnderlineThickness'])) {
        $fm['UnderlineThickness'] = 50;
    }

    $s .= '$up=' . $fm['UnderlinePosition'] . ";\n";

    $s .= '$ut=' . $fm['UnderlineThickness'] . ";\n";

    $w = MakeWidthArray($fm);

    $s .= '$cw=' . $w . ";\n";

    $s .= '$enc=\'' . $enc . "';\n";

    $s .= '$diff=\'' . $diff . "';\n";

    $basename = mb_substr(basename($afmfile), 0, -4);

    if ($fontfile) {
        //Embedded font

        if (!file_exists($fontfile)) {
            die('<B>Error:</B> font file not found: ' . $fontfile);
        }

        if ('TrueType' == $type) {
            CheckTTF($fontfile);
        }

        $f = fopen($fontfile, 'rb');

        if (!$f) {
            die('<B>Error:</B> Can\'t open ' . $fontfile);
        }

        $file = fread($f, filesize($fontfile));

        fclose($f);

        if ('Type1' == $type) {
            //Find first two sections and discard third one

            $pos = mb_strpos($file, 'eexec');

            if (!$pos) {
                die('<B>Error:</B> font file does not seem to be valid Type1');
            }

            $size1 = $pos + 6;

            $pos = mb_strpos($file, '00000000');

            if (!$pos) {
                die('<B>Error:</B> font file does not seem to be valid Type1');
            }

            $size2 = $pos - $size1;

            $file = mb_substr($file, 0, $size1 + $size2);
        }

        if (function_exists('gzcompress')) {
            $cmp = $basename . '.z';

            SaveToFile($cmp, gzcompress($file), 'b');

            $s .= '$file=\'' . $cmp . "';\n";

            echo 'Font file compressed (' . $cmp . ')<BR>';
        } else {
            $s .= '$file=\'' . basename($fontfile) . "';\n";

            echo '<B>Notice:</B> font file could not be compressed (gzcompress not available)<BR>';
        }

        if ('Type1' == $type) {
            $s .= '$size1=' . $size1 . ";\n";

            $s .= '$size2=' . $size2 . ";\n";
        } else {
            $s .= '$originalsize=' . filesize($fontfile) . ";\n";
        }
    } else {
        //Not embedded font

        $s .= '$file=' . "'';\n";
    }

    $s .= "?>\n";

    SaveToFile($basename . '.php', $s);

    echo 'Font definition file generated (' . $basename . '.php' . ')<BR>';
}
