<?php

//Set to false to reduce console output
$debug = TRUE;

//Project Variables
$inputdirectory = "import/";
$inputextention = ".dly";

$outputdirectory = "export/";
$outputextention = ".parsed.dly";

$errorextention = ".error.txt";

$_fill = " "; // Delimiter for use in the output

//Start of script
$linenum = 1;

if ( file_exists($inputdirectory) ) {
   foreach ( glob($inputdirectory . '*' . $inputextension) as $inputfile ) {
      $handle = fopen($inputfile, "r");
      if ($handle) {
        $outputfile = fopen($outputdirectory . pathinfo($inputfile, PATHINFO_FILENAME) . $outputextention, w) or die("Unable to open outputfile!");
        $errorfile = fopen($outputdirectory . pathinfo($inputfile, PATHINFO_FILENAME) . $errorextention, w) or die("Unable to open outputfile!");
        while (($line = fgets($handle)) !== false) {

          // Clear Output variable to prevent errors
          unset($outputline);
          unset($stoptime_yr);
          unset($stoptime_ld);
          unset($stoptime_lh);
          unset($stoptime_lm);
          unset($stoptime_ls);
          unset($duration);
          unset($taxpulses);
          unset($cc);
          unset($CL);

          // process the line read.
          echo "\n--------------------\nParsing record: " . $linenum . "\n--------------------\n";

          //Remove CRLF for linux compat, shown as ^M in vim
          $line = str_replace("\r", "", $line);
	  $line = str_replace("\n", "", $line);

          //Check if line starts with 2 spaces, if so, this is a correct line
          if (substr($line, 0, 2) === '  ' || strpos($line, 'HEARTBEAT FROM DSM') !== false) {
            //Do Nothing, This line is correct
            if($debug) { echo $linenum . " is correct\n"; }
            echo "Original Line:\n";
            echo $line;
            echo "\n";

            //Write original file to outputfile because there was nothing wrong with this line
            $line = $line . "\n";
            fwrite($outputfile, $line);

          } elseif(strlen($line) < 10) {
            echo "Original Line:\n";
            echo $line;
            echo "\nWARNING: Line Corrupted\n";
            echo "\n--------------------\nLine has " . strlen($outputline) . " characters\nEnd of record:" . $linenum . "\n--------------------\n";

            //We do not write the faulty line to the export file but to the error file
            $line = $line . "\n";
            fwrite($errorfile, $line);
          } else {
            //This line is corrupted, let the magic begin
            echo "Original Line:\n";
            echo $line;
            echo "\n";
                                                // End position of string selection
            $stoptime_yr = substr($line, 0, 2); // 2
            $stoptime_ld = substr($line, 2, 4); // 6
            $stoptime_lh = substr($line, 6, 2); // 8
            $stoptime_lm = substr($line, 8, 2); // 10
            $stoptime_ls = substr($line, 10, 2); // 12
            $duration = substr($line, 12, 5); // 17
            $taxpulses = substr($line, 17, 4); // 21
            $cc = substr($line, 21,2); // 23

            // Because the first part is always the same, we have not constructed the starting part of the line. Because the rest of the message differs based on the CC, we are now entering into a case match statement to further parse the message

            //CL will hold the Corrected Line information made by the parseCC command
            $CL = parseCC($cc, $line);
            echo "\nParsed line:\n";
            if($debug) { var_dump($CL); }

            $outputline = $_fill . $_fill . $stoptime_yr . $stoptime_ld . $_fill . $stoptime_lh . $stoptime_lm . $stoptime_ls . $_fill . $duration . $_fill . $taxpulses . $_fill . $cc . $_fill .$CL['accesscode1'] . $CL['accesscode2'] . $_fill . $CL['dialednumber'] . $_fill . $CL['connectednumber'] . $_fill . $CL['ogtrnkid'] . $_fill . $CL['inctrnkid'] . $_fill . $CL['callingnumber'] . $_fill . $CL['accountcode'] . $_fill . $CL['cilcode'] . $_fill . $CL['trunkquetime'] . $_fill . $CL['ringtimecounter'] . $_fill . $CL['queuetimecounter'] . $_fill . $CL['seqnumber'] . "\n";
            echo $outputline . "\n";

            //Check if line is 155 + \n char long. If this is not the case, something went wrong and we need to write to the error file
            if(strlen($outputline) != 156) {
              $error[] = array(
                "lineid" => $linenum,
                "output" => $outputline,
                "cc" => $cc,
              );
              fwrite($errorfile, $outputline);
            } else {
              fwrite($outputfile, $outputline);
            }

            echo "\n--------------------\nLine has " . strlen($outputline) . " characters\nEnd of record:" . $linenum . "\n--------------------\n";

            // This part counts the different types of CC messages so we know what to parse
            $cccounter[$cc]++;
          }
          //Increment Linenumber
          $linenum++;
      }

      if($debug) {
        echo "\nDumped CC's in this file: \n";
        var_dump($cccounter);
      }
      fclose($outputfile);
      fclose($errorfile);
      fclose($handle);
    } else {
      echo "Cannot open file: " . $filename;
      // error opening the file.
    } 
  }
}

var_dump($error);




function parseCC($cc, $line) {
    switch($cc) {
        case "A ";
            echo "A : N/A Call handled by a PBX operator";
            break;
        case "B ";
            echo "B : Calls to a busy party";
            $record = doParse("X",$line);
            return $record;
            break;
        case "C ";
            echo "C : N/A Abandoned calls";
            break;
        case "D ";
            echo "D : N/A Extremely long call duration";
            break;
        case "E ";
            echo "E : N/A Group call pickup calls";
            break;
        case "F ";
            echo "F : N/A Group Hunting calls";
            break;
        case "G ";
            echo "G : N/A Call has been connected with alternative route selection";
            break;
        case "H ";
            echo "H : N/A Recall to route";
            break;
        case "I ";
            echo "I : Incoming call or tandem call";
            $record = doParse("I",$line);
            return $record;
            break;
        case "J ";
            echo "J : Internal call";
            $record = doParse("J",$line);
            return $record;
            break;
        case "K ";
            echo "K : N/A Calls to vacant numbers";
            $record = doParse("J",$line);
            return $record;
            break;
        case "L ";
            echo "L : ";
            $record = doParse("J",$line);
            return $record;
            break;
        case "M ";
            echo "M : Least cost routing";
            $record = doParse("X",$line);
            return $record;
            break;
        case "T ";
            echo "T : Transferred call";
            $record = doParse("B",$line);
            return $record;
            break;
        case "X ";
            echo "X : External follow me";
            $record = doParse("X",$line);
            return $record;
            break;
        case "AT";
            echo "AT: Operator Extended + Transfer";
            $record = doParse("B",$line);
            return $record;
            break;
        case "CO";
            echo "CO: Abandoned outgoing call in a private network for Segment 0";
            $record = doParse("X",$line);
            return $record;
            break;
        case "CJ";
            echo "CJ : Abandoned calls / Internal call";
            $record = doParse("J",$line);
            return $record;
            break;
        case "DC";
            echo "DC: Abandoned outgoing /Incoming /Internal call due to Direct Diversion /Diversion on Busy /Diversion on No Reply in a private network";
            $record = doParse("J",$line);
            return $record;
            break;
        case "DI";
            echo "DI: Long incoming call /Direct Diverted /Diversion on Busy /Diversion on No Reply incoming calls";
            $record = doParse("J",$line);
            return $record;
            break;
        case "DJ";
            echo "DJ: Long internal call /Direct Diverted /Diversion on Busy /Diversion on No Answer internal calls";
            $record = doParse("J",$line);
            return $record;
            break;
        case "DT";
            echo "DT: Extremely long call duration / Transferred call";
            $record = doParse("B",$line);
            return $record;
            break;
        case "EI";
            echo "EI: Group call pickup calls / Incoming call or tandem call";
            $record = doParse("I",$line);
            return $record;
            break;
        case "EJ";
            echo "EJ: Group call pickup calls / Internal call";
            $record = doParse("J",$line);
            return $record;
            break;
        case "EL";
            echo "EL: ";
            $record = doParse("J",$line);
            return $record;
            break;
        case "ET";
            echo "ET: ";
            $record = doParse("B",$line);
            return $record;
            break;
        case "FC";
            echo "FC: ";
            $record = doParse("J",$line);
            return $record;
            break;
        case "FJ";
            echo "FJ: Group Hunting calls / Internal call";
            $record = doParse("J",$line);
            return $record;
            break;
        case "FX";
            echo "FX: Group Hunting calls / External follow me";
            $record = doParse("X",$line);
            return $record;
            break;
        case "NC";
            echo "NC: Dialed party not equal to answering party / Abandoned calls";
            $record = doParse("J",$line);
            return $record;
            break;
        case "NI";
            echo "NI: Dialed party is not the answering party on a transferred call";
            $record = doParse("J",$line);
            return $record;
            break;
        case "NJ";
            echo "NJ: Dialed party is not the answering party on an internal call";
            $record = doParse("J",$line);
            return $record;
            break;
        case "NL";
            echo "NL: ";
            $record = doParse("J",$line);
            return $record;
            break;
        case "NT";
            echo "NT: Dialed party is not the answering party on a transferred call";
            $record = doParse("B",$line);
            return $record;
            break;
        case "  ";
            echo "  : Whitespace CC found, Dumping line:\n" . $line . "\n";
            $record = doParse("WS",$line);
            return $record;
            break;
        default:
            echo "No valid CC Found";
            break;
    }
}

/*
 This function will parse the string based on one of the below filter masks and returns the result
*/
function doParse($type, $line) {
    switch($type) {
        case "B":
            $result['accesscode1'] = padLeft(substr($line, 24,4), 5); // 29 ( added space in front )
            $result['accesscode2'] = padLeft(substr($line, 29,4), 5); // 33
            $result['dialednumber'] = padLeft(substr($line, 34, 19), 20); //53 ( added space in front )
            $result['connectednumber'] = padLeft(substr($line, 54, 14), 15); // 68 ( added space in front )
            $result['ogtrnkid'] = padRight(" ", 10);
            $result['inctrnkid'] = substr($line, 68, 10); // 78
            $result['callingnumber'] = padRight(substr($line, 78, 10), 15); // 83 !! Notice Callingnumber is longer for other types
            $result['accountcode'] = padRight(" ", 15);
            $result['cilcode'] = padRight(" ",6);
            $result['trunkquetime'] = substr($line,-16 ,2);
            $result['ringtimecounter'] = substr($line, -13, 3);
            $result['queuetimecounter'] = substr($line, -9, 3);
            $result['seqnumber'] = substr($line, -5);
            return $result;
            break;
        case "I":
            $result['accesscode1'] = padLeft(substr($line, 24,4), 5); // 29 ( added space in front )
            $result['accesscode2'] = padLeft(substr($line, 29,4), 5); // 33
            $result['dialednumber'] = padLeft(substr($line, 34, 19), 20); //53 ( added space in front )
            $result['connectednumber'] = padLeft(substr($line, 54, 14), 15); // 68 ( added space in front )
            $result['ogtrnkid'] = padRight(" ", 10);
            $result['inctrnkid'] = substr($line, 68, 10); // 78
            $result['callingnumber'] = padRight(substr($line, 78, 10), 15); // 83 !! Notice Callingnumber is longer for other types
            $result['accountcode'] = padRight(" ", 15);
            $result['cilcode'] = padRight(" ",6);
            $result['trunkquetime'] = substr($line,-16 ,2);
            $result['ringtimecounter'] = substr($line, -13, 3);
            $result['queuetimecounter'] = substr($line, -9, 3);
            $result['seqnumber'] = substr($line, -5);
            return $result;
            break;
        case "J":
            $result['accesscode1'] = padLeft(substr($line, 24,4), 5); // 29 ( added space in front )
            $result['accesscode2'] = padLeft(substr($line, 29,4), 5); // 33
            $result['dialednumber'] = padLeft(substr($line, 34, 19), 20); //53 ( added space in front )
            $result['connectednumber'] = padLeft(substr($line, 54, 14), 15); // 68 ( added space in front )
            $result['ogtrnkid'] = padRight(" ", 10);
            $result['inctrnkid'] = substr($line, 68, 10); // 78
            $result['callingnumber'] = padRight(substr($line, 78, 5), 15); // 93 !! Notice Callingnumber is longer for other types
            $result['accountcode'] = padRight(" ", 15);
            $result['cilcode'] = padRight(" ",6);
            $result['trunkquetime'] = substr($line,-16 ,2);
            $result['ringtimecounter'] = substr($line, -13, 3);
            $result['queuetimecounter'] = substr($line, -9, 3);
            $result['seqnumber'] = substr($line, -5);           
            return $result;
            break;
        case "X":
            $result['accesscode1'] = padLeft(substr($line, 24,4), 5); // 29 ( added space in front )
            $result['accesscode2'] = padLeft(substr($line, 29,4), 5); // 33
            $result['dialednumber'] = padLeft(substr($line, 34, 19), 20); //53 ( added space in front )
            $result['connectednumber'] = padLeft(substr($line, 54, 14), 15); // 68 ( added space in front )
            $result['ogtrnkid'] = substr($line, 68,10); // 78
            $result['inctrnkid'] = substr($line, 78, 10); // 88
            $result['callingnumber'] = padRight(substr($line, 88, 5), 15); // 93 !! Notice Callingnumber is longer for other types
            $result['accountcode'] = padRight(" ", 15);
            $result['cilcode'] = padRight(" ",6);
            $result['trunkquetime'] = substr($line,-16 ,2);
            $result['ringtimecounter'] = substr($line, -13, 3);
            $result['queuetimecounter'] = substr($line, -9, 3);
            $result['seqnumber'] = substr($line, -5);
            return $result;
            break;
        case "WS":
            $result['accesscode1'] = padLeft(substr($line, 23,5), 5); // 28 ( NOTICE!!! WHITESPACE PARSER is different in startpointer ) 
            $result['accesscode2'] = padLeft(substr($line, 28,4), 5); // 32
            $result['dialednumber'] = padLeft(substr($line, 34, 19), 20); //53 ( added space in front )
            $result['connectednumber'] = padLeft(substr($line, 54, 14), 15); // 68 ( added space in front )
            $result['ogtrnkid'] = substr($line, 68,10); // 78
            $result['inctrnkid'] = substr($line, 78, 10); // 88
            $result['callingnumber'] = padRight(substr($line, 88, 5), 15); // 93 !! Notice Callingnumber is longer for other types
            $result['accountcode'] = padRight(" ", 15);
            $result['cilcode'] = padRight(" ",6);
            $result['trunkquetime'] = substr($line,-16 ,2);
            $result['ringtimecounter'] = substr($line, -13, 3);
            $result['queuetimecounter'] = substr($line, -9, 3);
            $result['seqnumber'] = substr($line, -5);
            return $result;
            break;
        default:
            echo "something went wrong in doParse()";
            break;
    }
}

/*
 This function fills a string to a certain length by padding spaces AFTER the original string
*/
function padRight($string, $length) {
    $result = str_pad($string, $length, " ", STR_PAD_RIGHT);

    return $result;
}

/*
 This function fills a string to a certain length by padding spaces IN FRONT OF the original string
*/
function padLeft($string, $length) {
    $result = str_pad($string, $length, " ", STR_PAD_LEFT);

    return $result;
}


?>
