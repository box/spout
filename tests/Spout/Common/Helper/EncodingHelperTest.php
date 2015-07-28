<?php

namespace Box\Spout\Common\Helper;

use Box\Spout\TestUsingResource;

/**
 * Class EncodingHelperTest
 *
 * @package Box\Spout\Common\Helper
 */
class EncodingHelperTest extends \PHPUnit_Framework_TestCase
{
    use TestUsingResource;

    /**
     * @return array
     */
    public function dataProviderForTestGetBytesOffsetToSkipBOM()
    {
        return [
            ['csv_with_utf8_bom.csv', EncodingHelper::ENCODING_UTF8, 3],
            ['csv_with_utf16be_bom.csv', EncodingHelper::ENCODING_UTF16_BE, 2],
            ['csv_with_utf32le_bom.csv', EncodingHelper::ENCODING_UTF32_LE, 4],
            ['csv_with_encoding_utf16le_no_bom.csv', EncodingHelper::ENCODING_UTF16_LE, 0],
            ['csv_standard.csv', EncodingHelper::ENCODING_UTF8, 0],
        ];
    }

    /**
     * @dataProvider dataProviderForTestGetBytesOffsetToSkipBOM
     *
     * @param string $fileName
     * @param string $encoding
     * @param int $expectedBytesOffset
     * @return void
     */
    public function testGetBytesOffsetToSkipBOM($fileName, $encoding, $expectedBytesOffset)
    {
        $resourcePath = $this->getResourcePath($fileName);
        $filePointer = fopen($resourcePath, 'r');

        $encodingHelper = new EncodingHelper(new GlobalFunctionsHelper());
        $bytesOffset = $encodingHelper->getBytesOffsetToSkipBOM($filePointer, $encoding);

        $this->assertEquals($expectedBytesOffset, $bytesOffset);
    }

    /**
     * @return array
     */
    public function dataProviderForIconvOrMbstringUsage()
    {
        return [
            [$shouldUseIconv = true],
            [$shouldNotUseIconv = false],
        ];
    }

    /**
     * @dataProvider dataProviderForIconvOrMbstringUsage
     * @expectedException \Box\Spout\Common\Exception\EncodingConversionException
     *
     * @param bool $shouldUseIconv
     * @return void
     */
    public function testAttemptConversionToUTF8ShouldThrowIfConversionFailed($shouldUseIconv)
    {
        $helperStub = $this->getMockBuilder('\Box\Spout\Common\Helper\GlobalFunctionsHelper')
                        ->setMethods(['iconv', 'mb_convert_encoding'])
                        ->getMock();
        $helperStub->method('iconv')->willReturn(false);
        $helperStub->method('mb_convert_encoding')->willReturn(false);

        /** @var EncodingHelper $encodingHelperStub */
        $encodingHelperStub = $this->getMockBuilder('\Box\Spout\Common\Helper\EncodingHelper')
                        ->setConstructorArgs([$helperStub])
                        ->setMethods(['canUseIconv', 'canUseMbString'])
                        ->getMock();
        $encodingHelperStub->method('canUseIconv')->willReturn($shouldUseIconv);
        $encodingHelperStub->method('canUseMbString')->willReturn(true);

        $encodingHelperStub->attemptConversionToUTF8('input', EncodingHelper::ENCODING_UTF16_LE);
    }

    /**
     * @expectedException \Box\Spout\Common\Exception\EncodingConversionException
     *
     * @return void
     */
    public function testAttemptConversionToUTF8ShouldThrowIfConversionNotSupported()
    {
        /** @var EncodingHelper $encodingHelperStub */
        $encodingHelperStub = $this->getMockBuilder('\Box\Spout\Common\Helper\EncodingHelper')
                        ->disableOriginalConstructor()
                        ->setMethods(['canUseIconv', 'canUseMbString'])
                        ->getMock();
        $encodingHelperStub->method('canUseIconv')->willReturn(false);
        $encodingHelperStub->method('canUseMbString')->willReturn(false);

        $encodingHelperStub->attemptConversionToUTF8('input', EncodingHelper::ENCODING_UTF16_LE);
    }

    /**
     * @dataProvider dataProviderForIconvOrMbstringUsage
     *
     * @param bool $shouldUseIconv
     * @return void
     */
    public function testAttemptConversionToUTF8ShouldReturnReencodedString($shouldUseIconv)
    {
        /** @var EncodingHelper $encodingHelperStub */
        $encodingHelperStub = $this->getMockBuilder('\Box\Spout\Common\Helper\EncodingHelper')
                        ->setConstructorArgs([new GlobalFunctionsHelper()])
                        ->setMethods(['canUseIconv', 'canUseMbString'])
                        ->getMock();
        $encodingHelperStub->method('canUseIconv')->willReturn($shouldUseIconv);
        $encodingHelperStub->method('canUseMbString')->willReturn(true);

        $encodedString = iconv(EncodingHelper::ENCODING_UTF8, EncodingHelper::ENCODING_UTF16_LE, 'input');
        $decodedString = $encodingHelperStub->attemptConversionToUTF8($encodedString, EncodingHelper::ENCODING_UTF16_LE);

        $this->assertEquals('input', $decodedString);
    }

    /**
     * @return void
     */
    public function testAttemptConversionToUTF8ShouldBeNoopWhenTargetIsUTF8()
    {
        /** @var EncodingHelper $encodingHelperStub */
        $encodingHelperStub = $this->getMockBuilder('\Box\Spout\Common\Helper\EncodingHelper')
                        ->disableOriginalConstructor()
                        ->setMethods(['canUseIconv'])
                        ->getMock();
        $encodingHelperStub->expects($this->never())->method('canUseIconv');

        $decodedString = $encodingHelperStub->attemptConversionToUTF8('input', EncodingHelper::ENCODING_UTF8);
        $this->assertEquals('input', $decodedString);
    }

    /**
     * @dataProvider dataProviderForIconvOrMbstringUsage
     * @expectedException \Box\Spout\Common\Exception\EncodingConversionException
     *
     * @param bool $shouldUseIconv
     * @return void
     */
    public function testAttemptConversionFromUTF8ShouldThrowIfConversionFailed($shouldUseIconv)
    {
        $helperStub = $this->getMockBuilder('\Box\Spout\Common\Helper\GlobalFunctionsHelper')
                        ->setMethods(['iconv', 'mb_convert_encoding'])
                        ->getMock();
        $helperStub->method('iconv')->willReturn(false);
        $helperStub->method('mb_convert_encoding')->willReturn(false);

        /** @var EncodingHelper $encodingHelperStub */
        $encodingHelperStub = $this->getMockBuilder('\Box\Spout\Common\Helper\EncodingHelper')
                        ->setConstructorArgs([$helperStub])
                        ->setMethods(['canUseIconv', 'canUseMbString'])
                        ->getMock();
        $encodingHelperStub->method('canUseIconv')->willReturn($shouldUseIconv);
        $encodingHelperStub->method('canUseMbString')->willReturn(true);

        $encodingHelperStub->attemptConversionFromUTF8('input', EncodingHelper::ENCODING_UTF16_LE);
    }

    /**
     * @expectedException \Box\Spout\Common\Exception\EncodingConversionException
     *
     * @return void
     */
    public function testAttemptConversionFromUTF8ShouldThrowIfConversionNotSupported()
    {
        /** @var EncodingHelper $encodingHelperStub */
        $encodingHelperStub = $this->getMockBuilder('\Box\Spout\Common\Helper\EncodingHelper')
                        ->disableOriginalConstructor()
                        ->setMethods(['canUseIconv', 'canUseMbString'])
                        ->getMock();
        $encodingHelperStub->method('canUseIconv')->willReturn(false);
        $encodingHelperStub->method('canUseMbString')->willReturn(false);

        $encodingHelperStub->attemptConversionFromUTF8('input', EncodingHelper::ENCODING_UTF16_LE);
    }

    /**
     * @dataProvider dataProviderForIconvOrMbstringUsage
     *
     * @param bool $shouldUseIconv
     * @return void
     */
    public function testAttemptConversionFromUTF8ShouldReturnReencodedString($shouldUseIconv)
    {
        /** @var EncodingHelper $encodingHelperStub */
        $encodingHelperStub = $this->getMockBuilder('\Box\Spout\Common\Helper\EncodingHelper')
                        ->setConstructorArgs([new GlobalFunctionsHelper()])
                        ->setMethods(['canUseIconv', 'canUseMbString'])
                        ->getMock();
        $encodingHelperStub->method('canUseIconv')->willReturn($shouldUseIconv);
        $encodingHelperStub->method('canUseMbString')->willReturn(true);

        $encodedString = $encodingHelperStub->attemptConversionFromUTF8('input', EncodingHelper::ENCODING_UTF16_LE);
        $encodedStringWithIconv = iconv(EncodingHelper::ENCODING_UTF8, EncodingHelper::ENCODING_UTF16_LE, 'input');

        $this->assertEquals($encodedStringWithIconv, $encodedString);
    }

    /**
     * @return void
     */
    public function testAttemptConversionFromUTF8ShouldBeNoopWhenTargetIsUTF8()
    {
        /** @var EncodingHelper $encodingHelperStub */
        $encodingHelperStub = $this->getMockBuilder('\Box\Spout\Common\Helper\EncodingHelper')
                        ->disableOriginalConstructor()
                        ->setMethods(['canUseIconv'])
                        ->getMock();
        $encodingHelperStub->expects($this->never())->method('canUseIconv');

        $encodedString = $encodingHelperStub->attemptConversionFromUTF8('input', EncodingHelper::ENCODING_UTF8);
        $this->assertEquals('input', $encodedString);
    }
}
