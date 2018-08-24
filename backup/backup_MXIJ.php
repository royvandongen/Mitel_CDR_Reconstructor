<?php
$debug = TRUE;

$inputfile = "cdrimport.txt";
$outputfile = "cdrexport.txt";

$output = file_get_contents($outputfile);

$handle = fopen($inputfile, "r");
$linenum = 1;

if ($handle) {
    while (($line = fgets($handle)) !== false) {
        // process the line read.
        echo "\n--------------------\nParsing record: " . $linenum . "\n--------------------\n";

        //Remove CRLF for linux compat, shown as ^M in vim
        $line = str_replace("\r", "", $line);
	$line = str_replace("\n", "", $line);

        //$output .= $line;

        //Check if line starts with 2 spaces, if so, this is a correct line
        if (substr($line, 0, 2) === '  ') {
            //Do Nothing, This line is correct
            if($debug) { echo $linenum . " is correct\n"; }
            echo "Original Line:\n";
            echo $line;
            echo "\n";
        } else {
            //This line is corrupted, let the magic begin
            //$linearray = preg_split("/[a-zA-Z]/",$line,2);
            //echo $linearray[0];
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

            //CL will hold the Corrected Line information made by the parseCC command
            $CL = parseCC($cc, $line);
            echo "\nParsed line:\n";
            var_dump($CL);

            //This character will be used to fill the blanks
            $_fill = "_";

            $outputline = $_fill . $_fill . $stoptime_yr . $stoptime_ld . $_fill . $stoptime_lh . $stoptime_lm . $stoptime_ls . $_fill . $duration . $_fill . $taxpulses . $_fill . $cc . $_fill .$CL['accesscode1'] . $CL['accesscode2'] . $_fill . $CL['dialednumber'] . $_fill . $CL['connectednumber'] . $_fill . $CL['ogtrnkid'] . $_fill . $CL['inctrnkid'] . $_fill . $CL['callingnumber'] . $_fill . $CL['accountcode'] . $_fill . $CL['cilcode'] . $_fill . $CL['trunkquetime'] . $_fill . $CL['ringtimecounter'] . $_fill . $CL['queuetimecounter'] . $_fill . $CL['seqnumber'] . "EOL";
            echo $outputline . "\n";

            // Because the first part is always the same, we have not constructed the starting part of the line. Because the rest of the message differs based on the CC, we are now entering into a case match statement to further parse the message

            echo "\n--------------------\nLine has " . strlen($outputline) . " characters\nEnd of record:" . $linenum . "\n--------------------\n";

            // This part counts the different types of CC messages so we know what to parse
            $cccounter[$cc]++;

            switch($cc) {

            }
        }
        //Increment Linenumber
        $linenum++;
    }

    if($debug) {
        echo "\nDumped CC's in this file: \n";
        var_dump($cccounter);
    }
    //file_put_contents($outputfile, $output);
    fclose($handle);
} else {
    echo "Cannot open file: " . $filename;
    // error opening the file.
} 


function parseCC($cc, $line) {
    switch($cc) {
        case "A ";
            echo "A : N/A Call handled by a PBX operator";
            break;
        case "B ";
            echo "B : Calls to a busy party";
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
            $record['accesscode1'] = padLeft(substr($line, 24,4), 5); // 29 ( added space in front )
            $record['accesscode2'] = padLeft(substr($line, 29,4), 5); // 33
            $record['dialednumber'] = padLeft(substr($line, 34, 19), 20); //53 ( added space in front )
            $record['connectednumber'] = padLeft(substr($line, 54, 14), 15); // 68 ( added space in front )
            $record['ogtrnkid'] = padRight(" ", 10); 
            $record['inctrnkid'] = substr($line, 68, 10); // 78
            $record['callingnumber'] = padRight(substr($line, 78, 10), 15); // 83 !! Notice Callingnumber is longer for other types
            $record['accountcode'] = padRight(" ", 15);
            $record['cilcode'] = padRight(" ",6);
            $record['trunkquetime'] = substr($line,-16 ,2);
            $record['ringtimecounter'] = substr($line, -13, 3);
            $record['queuetimecounter'] = substr($line, -9, 3);
            $record['seqnumber'] = substr($line, -5);
            return $record;
            break;
        case "J ";
            echo "J : Internal call";
            $record['accesscode1'] = padLeft(substr($line, 24,4), 5); // 29 ( added space in front )
            $record['accesscode2'] = padLeft(substr($line, 29,4), 5); // 33
            $record['dialednumber'] = padLeft(substr($line, 34, 19), 20); //53 ( added space in front )
            $record['connectednumber'] = padLeft(substr($line, 54, 14), 15); // 68 ( added space in front )
            $record['ogtrnkid'] = padRight(" ", 10);
            $record['inctrnkid'] = substr($line, 68, 10); // 78
            $record['callingnumber'] = padRight(substr($line, 78, 5), 15); // 93 !! Notice Callingnumber is longer for other types
            $record['accountcode'] = padRight(" ", 15);
            $record['cilcode'] = padRight(" ",6);
            $record['trunkquetime'] = substr($line,-16 ,2);
            $record['ringtimecounter'] = substr($line, -13, 3);
            $record['queuetimecounter'] = substr($line, -9, 3);
            $record['seqnumber'] = substr($line, -5);
            return $record;
            break;
        case "K ";
            echo "K : N/A Calls to vacant numbers";
            break;
        case "M ";
            echo "M : Least cost routing";
            $record['accesscode1'] = padLeft(substr($line, 24,4), 5); // 29 ( added space in front )
            $record['accesscode2'] = padLeft(substr($line, 29,4), 5); // 33
            $record['dialednumber'] = padLeft(substr($line, 34, 19), 20); //53 ( added space in front )
            $record['connectednumber'] = padLeft(substr($line, 54, 14), 15); // 68 ( added space in front )
            $record['ogtrnkid'] = substr($line, 68,10); // 78
            $record['inctrnkid'] = substr($line, 78, 10); // 88
            $record['callingnumber'] = padRight(substr($line, 88, 5), 15); // 93 !! Notice Callingnumber is longer for other types
            $record['accountcode'] = padRight(" ", 15);
            $record['cilcode'] = padRight(" ",6);
            $record['trunkquetime'] = substr($line,-16 ,2);
            $record['ringtimecounter'] = substr($line, -13, 3);
            $record['queuetimecounter'] = substr($line, -9, 3);
            $record['seqnumber'] = substr($line, -5);
            return $record;
            break;
        case "T ";
            echo "T : Transferred call";
            break;
        case "X ";
            echo "X : External follow me";
            $record['accesscode1'] = padLeft(substr($line, 24,4), 5); // 29 ( added space in front )
            $record['accesscode2'] = padLeft(substr($line, 29,4), 5); // 33
            $record['dialednumber'] = padLeft(substr($line, 34, 19), 20); //53 ( added space in front )
            $record['connectednumber'] = padLeft(substr($line, 54, 14), 15); // 68 ( added space in front )
            $record['ogtrnkid'] = substr($line, 68,10); // 78
            $record['inctrnkid'] = substr($line, 78, 10); // 88
            $record['callingnumber'] = padRight(substr($line, 88, 5), 15); // 93 !! Notice Callingnumber is longer for other types
            $record['accountcode'] = padRight(" ", 15);
            $record['cilcode'] = padRight(" ",6);
            $record['trunkquetime'] = substr($line,-16 ,2);
            $record['ringtimecounter'] = substr($line, -13, 3);
            $record['queuetimecounter'] = substr($line, -9, 3);
            $record['seqnumber'] = substr($line, -5);
            return $record;
            break;
        case "AT";
            echo "AT: Operator Extended + Transfer";
            break;
        case "DI";
            echo "DI: Long incoming call /Direct Diverted /Diversion on Busy /Diversion on No Reply incoming calls";
            break;
        case "CO";
            echo "CO: Abandoned outgoing call in a private network for Segment 0";
            $record['accesscode1'] = " " . substr($line, 24,5); // 29 ( added space in front )
            $record['accesscode2'] = substr($line, 29,4); // 33
            $record['dialednumber'] = padLeft(substr($line, 34, 19), 20); //53 ( added space in front )
            return $record;
            break;
        case "CJ";
            echo "CJ : Abandoned calls / Internal call";
            break;
        case "FJ";
            echo "FJ: Group Hunting calls / Internal call";
            break;
        case "EJ";
            echo "EJ: Group call pickup calls / Internal call";
            break;
        case "EI";
            echo "EI: Group call pickup calls / Incoming call or tandem call";
            break;
        case "NI";
            echo "NI: Dialed party is not the answering party on a transferred call";
            break;
        case "FX";
            echo "FX: Group Hunting calls / External follow me";
            break;
        case "DJ";
            echo "DJ: Long internal call /Direct Diverted /Diversion on Busy /Diversion on No Answer internal calls";
            break;
        case "NJ";
            echo "NJ: Dialed party is not the answering party on an internal call";
            break;
        case "DC";
            echo "DC: Abandoned outgoing /Incoming /Internal call due to Direct Diversion /Diversion on Busy /Diversion on No Reply in a private network";
            break;
        case "NT";
            echo "NT: Dialed party is not the answering party on a transferred call";
            break;
        case "DT";
            echo "DT: Extremely long call duration / Transferred call";
            break;
        case "NC";
            echo "NC: Dialed party not equal to answering party / Abandoned calls";
            break;
        case "  ";
            echo "  : Whitespace CC found, Dumping line:\n" . $line . "\n";
            break;
        default:
            echo "No valid CC Found";
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
