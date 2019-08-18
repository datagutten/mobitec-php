<?php


namespace datagutten\mobitec;


use mobitec;

class encoder
{

    /**
     * Write text at specified position with specified font
     * If parameteres are not specified the parameters from the previous text are re-used
     * @param $text
     * @param int $x X position
     * @param int $y Y position
     * @param int $font Font
     * @return string String to be written
     */
    public static function write_text($text, $x = null, $y = null, $font = null)
    {
        $output = '';
        $text = self::special_chars_to_sign($text);

        if (!empty($x)) //X position
            $output .= chr(0xD2) . chr($x);
        if (!empty($y)) //Y position
            $output .= chr(0xD3) . chr($y);
        if (!empty($font)) //Font
            $output .= chr(0xD4) . chr($font);
        $output .= $text;
        return $output;
    }

    /**
     * Add header and checksum to data to make it ready to send to sign
     * @param $data
     * @param int $address Sign address
     * @param $width
     * @param $height
     * @return string
     */
    public static function output($data, $address, $width, $height)
    {
        //Add header
        $output = chr(0xff) . chr($address) . chr(0xa2); //Start byte, address and text marker
        $output .= chr(0xD0) . chr($width); //Width
        $output .= chr(0xD1) . chr($height); //Height

        $output .= $data; //Add data
        $output .= utils::checksum(substr($output, 1)); //Add checksum (calucated without start byte)
        $output .= chr(0xFF); //And end byte
        return $output;
    }

    /**
     * Convert special characters for use on the sign
     * @param $chars
     * @return mixed
     */
    public static function special_chars_to_sign($chars)
    {
        return str_replace(array('æ', 'ø', 'å', 'Æ', 'Ø', 'Å'), array('$', ';', '}', '*', ',', ']'), $chars);
    }
}