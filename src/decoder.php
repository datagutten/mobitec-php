<?php


namespace datagutten\mobitec;


use Exception;

class decoder
{
    /**
     * Find checksum
     * @param string $sign
     * @return array checksum position, checksum
     * @throws Exception Escape char 0xFE is not followed by 0x00 or 0x01
     */
    public static function find_checksum($sign)
    {
        if(ord($sign[-3]) == 0xFE) //Escaped checksum
        {
            $checksum_pos = strlen($sign) -3;
            $checksum_temp = ord($sign[-2]);
            if($checksum_temp==0x00)
                $checksum = 0xFE;
            elseif ($checksum_temp==0x01)
                $checksum = 0xFF;
            else
                throw new Exception(sprintf('Invalid escaped checksum, 0xFE is followed by 0x%s', dechex($checksum_temp)));
        }
        else //Regular checksum
        {
            $checksum_pos = strlen($sign) -2;
            $checksum = ord($sign[-2]);
        }
        return[$checksum_pos, $checksum];
    }

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
        $lines = array();
        foreach ($signs[0] as $sign) {
            list($checksum_pos, $checksum) = self::find_checksum($sign);
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
            $previous_was_printable = false;

            for ($i = 0; $i <= strlen($sign)-2; $i++) //Loop through the bytes, omitting the last 0xFF
            {
                $byte = ord($sign[$i]); //Get the ASCII code
                $nextbyte = ord($sign[$i + 1]); //Get the ASCII code
                if ($byte == 0xA2) //Message start
                    continue;

                if (isset($fields[$byte])) //Control character
                {
                    if($previous_was_printable) { //Start of new line
                        $lines[$signkey][$linekey]['Text'] = $message;
                        $message='';
                        $linekey++;
                    }
                    if ($show_output)
                        echo sprintf("%s: %s\n", $fields[$byte], dechex(ord($sign[$i + 1])));
                    $lines[$signkey][$linekey][$fields[$byte]] = ord($sign[$i + 1]);

                    $previous_was_printable = false;
                    $i++;
                }
                elseif ($byte == 0xFE || $nextbyte==0xFF)
                {
                    if($show_output)
                        echo "Checksum found, end of sign\n";
                    break;
                }
                else //ASCII
                {
                    if (!utils::is_printable($byte)) //Valid signs does not contain non-printable characters
                    {
                        if($show_output)
                            printf("Message %d is not printable\n", $signkey);
                        unset($lines[$signkey]); //Remove the message
                        continue;
                    }

                    $message .= $sign[$i];
                    $previous_was_printable = true;
                }
            }

            $lines[$signkey][$linekey]['Text'] = $message;

            if ($show_output) {
                echo "\nMessage: <<$message>>\n";
                echo sprintf("Address: %s\n", ord(substr($sign, 1, 1)));
            }

            //First message must have text and address to be valid, continue to next message without increasing key
            if (!isset($lines[$signkey][0]['Text']) || empty(trim($lines[$signkey][0]['Text'])) || empty($lines[$signkey][0]['Address']))
            {
                unset($lines[$signkey]);
                continue;
            }

            $body = substr($sign, 1, $checksum_pos-1);
            $checksum_real = utils::checksum($body, true); //Remove end byte and checksum (variable length) for new checksum calculation
            if ($checksum != $checksum_real && $show_output)
                echo sprintf(_('Checksum for sign %s does not match, should be %s but is %s'), $signkey, dechex($checksum), dechex($checksum_real)) . "\n";
            $signkey++;
        }
        if (empty($lines))
            throw new Exception('No valid signs found');

        return $lines;
    }
}