<?php

namespace datagutten\mobitec\tests;

use datagutten\mobitec\decoder;
use Exception;
use PHPUnit\Framework\TestCase;

class decoderTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testParse()
    {
        $data = file_get_contents(__DIR__.'/test_data/fonts.mobitec');
        $signs = decoder::parse($data);
        $compare = array('Address'=>6, 'Width'=>112, 'Height'=>20, 'Horizontal offset'=>6, 'Vertical offset'=>21, 'Font'=>104, 'Text'=>'Std h 16x9 15');
        $this->assertEquals($compare, $signs[4][0]);
    }

    /**
     * @throws Exception
     */
    public function testOutput()
    {
        $data = file_get_contents(__DIR__.'/test_data/fonts.mobitec');
        ob_start();
        decoder::parse($data, true);
        $output = ob_get_clean();
        $this->assertStringContainsString('Message: <<Std f 6x6 5>>', $output);
    }

    /**
     * @throws Exception
     */
    public function testEmptyInput()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No signs found');
        decoder::parse('');
    }

    /**
     * @throws Exception
     */
    public function testInvalidInput()
    {
        ob_start();
        try {
            decoder::parse(chr(0xFF) . 'asdf' . chr(0xFF), true);
        }
        catch (Exception $e)
        {
            $this->assertEquals('No valid signs found', $e->getMessage());
        }
        $output = ob_get_clean();
        $this->assertEquals("Invalid sign, first byte is ff, third is 73\n", $output);
    }

    /**
     * @throws Exception
     */
    public function testTwoLines()
    {
        $data = file_get_contents(__DIR__.'/test_data/two_lines');
        $signs = decoder::parse($data);
        $this->assertEquals('Oslo Akershus', $signs[0][0]['Text']);
        $this->assertEquals('RHF avd.', $signs[0][1]['Text']);
    }

    /**
     * @throws Exception
     */
    public function testEscapedChecksum()
    {
        $data = file_get_contents(__DIR__.'/test_data/escaped_checksum');
        $signs = decoder::parse($data);
        $this->assertEquals('Std b 9x10 9', $signs[0][0]['Text']);
    }

    /**
     * Check if the correct checksum is found at the correct position
     * @throws Exception
     */
    public function testFindChecksum()
    {
        $string_fe = sprintf('%c%c%c%c', 0xFF, 0xFE, 0x00, 0xFF);
        $string_ff = sprintf('%c%c%c%c', 0xFF, 0xFE, 0x01, 0xFF);
        $string_regular = sprintf('%c%c%c%c', 0xFF, 0xBC, 0xCD, 0xFF);

        list($pos, $checksum) = decoder::find_checksum($string_fe);
        $this->assertEquals(0xFE, $checksum);
        $this->assertEquals(1, $pos);

        list($pos, $checksum) = decoder::find_checksum($string_ff);
        $this->assertEquals(0xFF, $checksum);
        $this->assertEquals(1, $pos);

        list($pos, $checksum) = decoder::find_checksum($string_regular);
        $this->assertEquals(0xCD, $checksum);
        $this->assertEquals(2, $pos);
    }

    /**
     * @throws Exception
     */
    public function testNotPrintable()
    {
        $data = file_get_contents(__DIR__.'/test_data/non_printable');
        ob_start();
        try {
            decoder::parse($data, true);
        }
        catch (Exception $e)
        {
            $this->assertEquals('No valid signs found', $e->getMessage());
        }
        $output = ob_get_clean();
        $this->assertStringContainsString('Message 0 is not printable', $output);
    }

}
