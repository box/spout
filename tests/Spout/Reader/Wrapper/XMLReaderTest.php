<?php

namespace Box\Spout\Reader\Wrapper;

use Box\Spout\TestUsingResource;
use Box\Spout\Reader\Exception\XMLProcessingException;

/**
 * Class XMLReaderTest
 *
 * @package Box\Spout\Reader\Wrapper
 */
class XMLReaderTest extends \PHPUnit_Framework_TestCase
{
    use TestUsingResource;

    /**
     * @return void
     */
    public function testOpenShouldFailIfFileInsideZipDoesNotExist()
    {
        $resourcePath = $this->getResourcePath('one_sheet_with_inline_strings.xlsx');
        $nonExistingXMLFilePath = 'zip://' . $resourcePath . '#path/to/fake/file.xml';

        $xmlReader = new XMLReader();

        // using "@" to prevent errors/warning to be displayed
        $wasOpenSuccessful = @$xmlReader->open($nonExistingXMLFilePath);

        $this->assertTrue($wasOpenSuccessful === false);
    }

    /**
     * Testing a HHVM bug: https://github.com/facebook/hhvm/issues/5779
     * The associated code in XMLReader::open() can be removed when the issue is fixed (and this test starts failing).
     * @see XMLReader::open()
     *
     * @return void
     */
    public function testHHVMStillDoesNotComplainWhenCallingOpenWithFileInsideZipNotExisting()
    {
        // Test should only be run on HHVM
        if ($this->isRunningHHVM()) {
            $resourcePath = $this->getResourcePath('one_sheet_with_inline_strings.xlsx');
            $nonExistingXMLFilePath = 'zip://' . $resourcePath . '#path/to/fake/file.xml';

            libxml_clear_errors();
            $initialUseInternalErrorsSetting = libxml_use_internal_errors(true);

            // using the built-in XMLReader
            $xmlReader = new \XMLReader();
            $this->assertTrue($xmlReader->open($nonExistingXMLFilePath) !== false);
            $this->assertTrue(libxml_get_last_error() === false);

            libxml_use_internal_errors($initialUseInternalErrorsSetting);
        }
    }

    /**
     * @return bool TRUE if running on HHVM, FALSE otherwise
     */
    private function isRunningHHVM()
    {
        return defined('HHVM_VERSION');
    }

    /**
     * @expectedException \Box\Spout\Reader\Exception\XMLProcessingException
     *
     * @return void
     */
    public function testReadShouldThrowExceptionOnError()
    {
        $resourcePath = $this->getResourcePath('one_sheet_with_invalid_xml_characters.xlsx');
        $sheetDataXMLFilePath = 'zip://' . $resourcePath . '#xl/worksheets/sheet1.xml';

        $xmlReader = new XMLReader();
        if ($xmlReader->open($sheetDataXMLFilePath) === false) {
            $this->fail();
        }

        // using "@" to prevent errors/warning to be displayed
        while (@$xmlReader->read()) {
            // do nothing
        }
    }

    /**
     * @expectedException \Box\Spout\Reader\Exception\XMLProcessingException
     *
     * @return void
     */
    public function testNextShouldThrowExceptionOnError()
    {
        // The sharedStrings.xml file in "attack_billion_laughs.xlsx" contains
        // a doctype element that causes read errors
        $resourcePath = $this->getResourcePath('attack_billion_laughs.xlsx');
        $sheetDataXMLFilePath = 'zip://' . $resourcePath . '#xl/sharedStrings.xml';

        $xmlReader = new XMLReader();
        if ($xmlReader->open($sheetDataXMLFilePath) !== false) {
            @$xmlReader->next('sst');
        }
    }

    /**
     * @return array
     */
    public function dataProviderForTestIsZipStream()
    {
        return [
            ['/absolute/path/to/file.xlsx', false],
            ['relative/path/to/file.xlsx', false],
            ['php://temp', false],
            ['zip:///absolute/path/to/file.xlsx', true],
            ['zip://relative/path/to/file.xlsx', true],
        ];
    }

    /**
     * @dataProvider dataProviderForTestIsZipStream
     *
     * @param string $URI
     * @param bool $expectedResult
     * @return void
     */
    public function testIsZipStream($URI, $expectedResult)
    {
        $xmlReader = new XMLReader();
        $isZipStream = \ReflectionHelper::callMethodOnObject($xmlReader, 'isZipStream', $URI);

        $this->assertEquals($expectedResult, $isZipStream);
    }

    /**
     * @return array
     */
    public function dataProviderForTestFileExistsWithinZip()
    {
        return [
            ['[Content_Types].xml', true],
            ['xl/sharedStrings.xml', true],
            ['xl/worksheets/sheet1.xml', true],
            ['/invalid/file.xml', false],
            ['another/invalid/file.xml', false],
        ];
    }

    /**
     * @dataProvider dataProviderForTestFileExistsWithinZip
     *
     * @param string $innerFilePath
     * @param bool $expectedResult
     * @return void
     */
    public function testFileExistsWithinZip($innerFilePath, $expectedResult)
    {
        $resourcePath = $this->getResourcePath('one_sheet_with_inline_strings.xlsx');
        $zipStreamURI = 'zip://' . $resourcePath . '#' . $innerFilePath;

        $xmlReader = new XMLReader();
        $isZipStream = \ReflectionHelper::callMethodOnObject($xmlReader, 'fileExistsWithinZip', $zipStreamURI);

        $this->assertEquals($expectedResult, $isZipStream);
    }

    /**
     * @return array
     */
    public function dataProviderForTestConvertURIToUseRealPath()
    {
        $tempFolder = realpath(sys_get_temp_dir());

        return [
            ['/../../../' . $tempFolder . '/test.xlsx', $tempFolder . '/test.xlsx'],
            [$tempFolder . '/test.xlsx', $tempFolder . '/test.xlsx'],
            ['zip://' . $tempFolder . '/test.xlsx#test.xml', 'zip://' . $tempFolder . '/test.xlsx#test.xml'],
            ['zip:///../../../' . $tempFolder . '/test.xlsx#test.xml', 'zip://' . $tempFolder . '/test.xlsx#test.xml'],
        ];
    }

    /**
     * @dataProvider dataProviderForTestConvertURIToUseRealPath
     *
     * @param string $URI
     * @param string $expectedConvertedURI
     * @return void
     */
    public function testConvertURIToUseRealPath($URI, $expectedConvertedURI)
    {
        $tempFolder = sys_get_temp_dir();
        touch($tempFolder . '/test.xlsx');

        $xmlReader = new XMLReader();
        $convertedURI = \ReflectionHelper::callMethodOnObject($xmlReader, 'convertURIToUseRealPath', $URI);

        $this->assertEquals($expectedConvertedURI, $convertedURI);

        unlink($tempFolder . '/test.xlsx');
    }
}
