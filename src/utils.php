<?php


namespace datagutten\mobitec;


class utils
{

    /**
     * Format a value for debugging
     * @param $string
     * @param bool $return_as_hex
     * @return float|int|string
     */
    public static function format_value($string, $return_as_hex = false) //Convert a string to character codes
    {
        $chars = str_split($string); //Split the string into an array
        $chars_dec = array_map('ord', $chars); //Get the character code for each char
        $chars_hex = array_map('dechex', $chars_dec); //Convert the character codes to hex
        foreach ($chars_hex as $key => $char)
            $chars_hex[$key] = str_pad($char, 2, '0', STR_PAD_LEFT); //Pad each hex char

        $number_hex = implode('-', $chars_hex); //Merge the chars to a string

        if ($return_as_hex === false)
            return hexdec($number_hex); //Return as decimal integer
        else
            return $number_hex; //Return as hex string
    }

    /**
     * Get the character code for a multi-byte character
     * Found at: http://php.net/manual/en/function.ord.php#42778
     * @param string $u
     * @return int
     */
    public static function uniord($u)
    {
        $k = mb_convert_encoding($u, 'UCS-2LE', 'UTF-8');
        $k1 = ord(substr($k, 0, 1));
        $k2 = ord(substr($k, 1, 1));
        return $k2 * 256 + $k1;
    }

    /**
     * Calculate checksum
     * @param $output
     * @param bool $return_int
     * @return int|string
     */
    public static function checksum($output, $return_int = false)
    {
        /*if(substr($output,0,1)!=chr(0xFF) || substr($output,-1,1)!=chr(0xFF))
            return false;*/
        $stringarray = str_split($output, 1); //Split the string into an array
        $numarray = array_map('ord', $stringarray); //Turn the array into numeric character code
        $sum = array_sum($numarray); //Get the sum of the array
        $checksum = $sum & 0xFF;
        if ($return_int)
            return $checksum;
        if ($checksum == 0xFE)
            $checksum = chr(0xFE) . chr(0x00);
        elseif ($checksum == 0XFF)
            $checksum = chr(0xFE) . chr(0x01);
        else
            $checksum = chr($checksum);

        return $checksum;
    }
}