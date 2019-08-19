<?php


namespace datagutten\mobitec;


use Exception;
use mobitec;

class decoder
{

    /**
     * Parse data
     * @param $data
     * @param bool $show_output Show debug output
     * @return array
     * @throws Exception
     */
    public static function parse($data, $show_output = false)
    {
        $matches = preg_match_all('/\xff.+?\xff/s', $data, $signs); //Get all signs
        //print_r($signs);
        if ($matches == 0)
            throw new Exception('No signs found');

        $fields = array(0xFF => 'Address', 0xD0 => 'Width', 0xD1 => 'Height', 0xD2 => 'Horizontal offset', 0xD3 => 'Vertical offset', 0xD4 => 'Font'); //Field definitions
        $signkey = 0;
        foreach ($signs[0] as $sign) {
            unset($checksum);
            $sign_pos = strpos($data, $sign); //Position in file for start byte (0xFF) of current sign
            if ($sign == chr(0xFF) . chr(0x01) . chr(0xFF)) //ICU transmits 0xFF 0x01 0xFF continiously when not changing
                continue;
            if (substr($sign, 0, 1) != chr(0xFF) || substr($sign, 2, 1) != chr(0xA2)) //Valid messages starts with 0xFF, then the sign address and then 0xA2
            {
                if ($show_output)
                    echo sprintf("Invalid sign, first byte is %s, third is %s\n", dechex(ord($sign[0])), dechex(ord($sign[2])));
                continue;
            }

            if ($show_output)
                echo "----\n\n";
            $message = '';
            $linekey = 0;
            for ($i = 0; $i < strlen($sign) - 2; $i++) //Loop through the bytes, omitting the last 0xFF
            {
                $byte = ord($sign[$i]); //Get the ASCII code
                $nextbyte = ord($sign[$i + 1]); //Get the ASCII code
                if ($byte == 0xA2)
                    continue;

                if (isset($fields[$byte])) //Control character
                {
                    if ($show_output)
                        echo sprintf("%s: %s\n", $fields[$byte], dechex(ord($sign[$i + 1])));
                    $lines[$signkey][$linekey][$fields[$byte]] = ord($sign[$i + 1]);
                    $last_printable = false;
                    $i++;
                } elseif ($byte == 0xFE) //Escaped checksum
                {
                    $message_end = $i - 1; //Message ends at previous byte
                    if ($nextbyte == 0x00)
                        $checksum = 0xFE;
                    if ($nextbyte == 0x01)
                        $checksum = 0xFF;
                    $checksum_length = 2;
                    break;
                } else //ASCII
                {
                    if (!ctype_print($sign[$i])) //Valid signs does not contain non-printable characters
                    {
                        unset($lines[$signkey]);
                        continue;
                    }
                    if ($show_output) {
                        if ($last_printable === false && (!ctype_print($sign[$i - 1]) || !ctype_print($sign[$i - 2])))
                            echo "<<";
                        echo $sign[$i];
                    }

                    $message .= $sign[$i];
                    //Message end is when the next character is a control character or the second next is 0xFF
                    if ($sign[$i + 2] == chr(0xFF) || isset($fields[ord($sign[$i + 1])])) {
                        //echo ">>";
                        $lines[$signkey][$linekey]['Text'] = $message;
                        $message = '';
                        $linekey++;
                    }
                    /*if($last_printable===false)
                        $message."\n";*/

                    $last_printable = true;
                }
            }
            $message_start = strpos($sign, chr(0xD4)) + 2; //Message starts two bytes after 0xD4
            if (substr($sign, -2, 1) != chr(0xFE)) //Non escaped checksum
            {
                $checksum_pos = $sign_pos + strlen($sign) - 1 - 2;
                $checksum = ord(substr($sign, -2, 1));
                $message_end = strlen($sign) - 3;
                $checksum_length = 1;
            }

            if ($show_output) {
                echo "Message: " . substr($sign, $message_start, strlen($sign) - $message_start - 2) . "\n";
                echo "\nMessage: $message\n";
                echo sprintf("Address: %s\n", ord(substr($sign, 1, 1)));
            }

            //First message must have text and address to be valid, continue to next message without increasing key
            if (!isset($lines[$signkey][0]['Text']) || empty(trim($lines[$signkey][0]['Text'])) || empty($lines[$signkey][0]['Address']))
            {
                unset($lines[$signkey]);
                continue;
            }
            $checksum_real = utils::checksum($checksum_data = substr($sign, 1, (-$checksum_length) - 1), true); //Remove end byte and checksum (variable length) for new checksum calculation
            if ($checksum != $checksum_real && $show_output)
                echo sprintf(_('Checksum for sign %s does not match, should be %s but is %s'), $signkey, dechex($checksum), dechex($checksum_real)) . "\n";
            $signkey++;
        }
        if (empty($lines))
            throw new Exception('No valid signs found');

        return $lines;
    }
}