<?php
/*
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
*/

class PDF2Text {
    var $filename = '';
    var $decodedtext = '';
    
    function setFilename($filename) { 
        $this->decodedtext = '';
        $this->filename = $filename;
    }

    function output($echo = false) { 
        if($echo) echo $this->decodedtext;
        else return $this->decodedtext;
    }

    function decodePDF() { 
        $infile = @file_get_contents($this->filename); 
        if (empty($infile)) 
            return ""; 
    
        $texts = array(); 
    
        preg_match_all("#obj[\n|\r](.*)endobj[\n|\r]#ismU", $infile, $objects); 
        $objects = @$objects[1]; 
    
        for ($i = 0; $i < count($objects); $i++) { 
            $currentObject = $objects[$i]; 
    
            if (preg_match("#stream[\n|\r](.*)endstream[\n|\r]#ismU", $currentObject, $stream)) { 
                $stream = ltrim($stream[1]); 
                $options = $this->getObjectOptions($currentObject); 
    
                if (!(empty($options["Length1"]) && empty($options["Type"]) && empty($options["Subtype"]))) 
                    continue; 
    
                unset($options["Length"]);
                $data = $this->getDecodedStream($stream, $options);  
    
                if (strlen($data)) { 
                    if (preg_match_all("#BT\s+(.*?)\s+ET#ismU", $data, $textContainers)) {
                        $textContainers = @$textContainers[1]; 
                        $this->getDirtyTexts($texts, $textContainers); 
                    }
                } 
            } 
        } 
        
        $clean_lines = array();
        foreach ($texts as $t) {
            $t = $this->cleanPdfString($t);
            $clean_lines[] = $this->toUtf8($t);
        }
        
        $this->decodedtext = implode("\n", $clean_lines); 
    }

    function decodeAsciiHex($input) {
        $output = "";
        $isOdd = true;
        $isComment = false;
        for($i = 0, $codeHigh = -1; $i < strlen($input) && $input[$i] != '>'; $i++) {
            $c = $input[$i];
            if($isComment) {
                if ($c == '\r' || $c == '\n')
                    $isComment = false;
                continue;
            }
            switch($c) {
                case '\0': case '\t': case '\r': case '\f': case '\n': case ' ': break;
                case '%': 
                    $isComment = true;
                break;
                default:
                    $code = hexdec($c);
                    if($code === 0 && $c != '0')
                        return "";
                    if($isOdd)
                        $codeHigh = $code;
                    else
                        $output .= chr($codeHigh * 16 + $code);
                    $isOdd = !$isOdd;
                break;
            }
        }
        if($input[$i] != '>')
            return "";
        if($isOdd)
            $output .= chr($codeHigh * 16);
        return $output;
    }
    
    function decodeAscii85($input) {
        $output = "";
        $isComment = false;
        $ords = array();
        for($i = 0, $state = 0; $i < strlen($input) && $input[$i] != '~'; $i++) {
            $c = $input[$i];
            if($isComment) {
                if ($c == '\r' || $c == '\n')
                    $isComment = false;
                continue;
            }
            if ($c == '\0' || $c == '\t' || $c == '\r' || $c == '\f' || $c == '\n' || $c == ' ')
                continue;
            if ($c == '%') {
                $isComment = true;
                continue;
            }
            if ($c == 'z' && $state === 0) {
                $output .= str_repeat(chr(0), 4);
                continue;
            }
            if ($c < '!' || $c > 'u')
                return "";
            $code = ord($input[$i]) & 0xff;
            $ords[$state++] = $code - ord('!');
            if ($state == 5) {
                $state = 0;
                for ($sum = 0, $j = 0; $j < 5; $j++)
                    $sum = $sum * 85 + $ords[$j];
                for ($j = 3; $j >= 0; $j--)
                    $output .= chr($sum >> ($j * 8));
            }
        }
        if ($state === 1)
            return "";
        elseif ($state > 1) {
            for ($i = 0, $sum = 0; $i < $state; $i++)
                $sum += ($ords[$i] + ($i == $state - 1)) * pow(85, 4 - $i);
            for ($i = 0; $i < $state - 1; $i++)
                $output .= chr($sum >> ((3 - $i) * 8));
        }
        return $output;
    }
    
    function decodeFlate($input) {
        return @gzuncompress($input);
    }
    
    function getObjectOptions($object) {
        $options = array();
        if (preg_match("#<<(.*)>>#ismU", $object, $match)) {
            $dict = $match[1];
            $parts = explode('/', $dict);
            array_shift($parts);
            foreach ($parts as $p) {
                $p = trim($p);
                if ($p === '') continue;
                $tokens = preg_split('#\s+#', $p, 2);
                $key = $tokens[0];
                $val = isset($tokens[1]) ? $tokens[1] : true;
                if (is_string($val) && strpos($val, '/') === 0) {
                    $val = substr($val, 1);
                }
                $options[$key] = $val;
                if ($key === 'FlateDecode' || $val === 'FlateDecode') {
                    $options['FlateDecode'] = true;
                }
            }
        }
        return $options;
    }
    
    function getDecodedStream($stream, $options) {
        $data = "";
        if (empty($options["Filter"]) && !isset($options["FlateDecode"]))
            $data = $stream;
        else {
            $length = !empty($options["Length"]) ? $options["Length"] : strlen($stream);
            $_stream = substr($stream, 0, $length);
            foreach ($options as $key => $value) {
                if ($key == "ASCIIHexDecode")
                    $_stream = $this->decodeAsciiHex($_stream);
                if ($key == "ASCII85Decode")
                    $_stream = $this->decodeAscii85($_stream);
                if ($key == "FlateDecode")
                    $_stream = $this->decodeFlate($_stream);
            }
            $data = $_stream;
        }
        return $data;
    }
    
    function getDirtyTexts(&$texts, $textContainers) {
        for ($j = 0; $j < count($textContainers); $j++) {
            // Match (string) Tj
            if (preg_match_all("#\((.*?)\)\s*Tj#is", $textContainers[$j], $parts1)) {
                $texts = array_merge($texts, $parts1[1]);
            }
            // Match [array] TJ
            if (preg_match_all("#\[(.*?)\]\s*TJ#is", $textContainers[$j], $parts2)) {
                foreach ($parts2[1] as $array_str) {
                    if (preg_match_all("#\((.*?)\)#is", $array_str, $subparts)) {
                        $texts = array_merge($texts, $subparts[1]);
                    }
                }
            }
        }
    }

    function cleanPdfString($str) {
        $str = str_replace(['\\(', '\\)', '\\\\'], ['(', ')', '\\'], $str);
        $str = preg_replace_callback('/\\\\([0-7]{3})/', function($m) {
            return chr(octdec($m[1]));
        }, $str);
        return $str;
    }

    function toUtf8($str) {
        if (!mb_check_encoding($str, 'UTF-8')) {
            return mb_convert_encoding($str, 'UTF-8', 'CP1254');
        }
        return $str;
    }
}
?>
