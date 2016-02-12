<?php

namespace Box\Spout\Reader\CSV;

use Box\Spout\Reader\ReaderFactory;
use Box\Spout\Common\Type;
use Box\Spout\Common\Helper\EncodingHelper;
use Box\Spout\TestUsingResource;

/**
 * Class ReaderTest
 *
 * @package Box\Spout\Reader\CSV
 */
class ReaderTest extends \PHPUnit_Framework_TestCase
{
    use TestUsingResource;

    /**
     * @expectedException \Box\Spout\Common\Exception\IOException
     *
     * @return void
     */
    public function testOpenShouldThrowExceptionIfFileDoesNotExist()
    {
        ReaderFactory::create(Type::CSV)->open('/path/to/fake/file.csv');
    }

    /**
     * @expectedException \Box\Spout\Reader\Exception\ReaderNotOpenedException
     *
     * @return void
     */
    public function testOpenShouldThrowExceptionIfTryingToReadBeforeOpeningReader()
    {
        ReaderFactory::create(Type::CSV)->getSheetIterator();
    }

    /**
     * @expectedException \Box\Spout\Common\Exception\IOException
     *
     * @return void
     */
    public function testOpenShouldThrowExceptionIfFileNotReadable()
    {
        $helperStub = $this->getMockBuilder('\Box\Spout\Common\Helper\GlobalFunctionsHelper')
                        ->setMethods(['is_readable'])
                        ->getMock();
        $helperStub->method('is_readable')->willReturn(false);

        $resourcePath = $this->getResourcePath('csv_standard.csv');

        $reader = ReaderFactory::create(Type::CSV);
        $reader->setGlobalFunctionsHelper($helperStub);
        $reader->open($resourcePath);
    }

    /**
     * @expectedException \Box\Spout\Common\Exception\IOException
     *
     * @return void
     */
    public function testOpenShouldThrowExceptionIfCannotOpenFile()
    {
        $helperStub = $this->getMockBuilder('\Box\Spout\Common\Helper\GlobalFunctionsHelper')
                        ->setMethods(['fopen'])
                        ->getMock();
        $helperStub->method('fopen')->willReturn(false);

        $resourcePath = $this->getResourcePath('csv_standard.csv');

        $reader = ReaderFactory::create(Type::CSV);
        $reader->setGlobalFunctionsHelper($helperStub);
        $reader->open($resourcePath);
    }


    /**
     * @return void
     */
    public function testReadStandardCSV()
    {
        $allRows = $this->getAllRowsForFile('csv_standard.csv');

        $expectedRows = [
            ['csv--11', 'csv--12', 'csv--13'],
            ['csv--21', 'csv--22', 'csv--23'],
            ['csv--31', 'csv--32', 'csv--33'],
        ];
        $this->assertEquals($expectedRows, $allRows);
    }

    /**
     * @return void
     */
    public function testReadShouldNotStopAtCommaIfEnclosed()
    {
        $allRows = $this->getAllRowsForFile('csv_with_comma_enclosed.csv');
        $this->assertEquals('This is, a comma', $allRows[0][0]);
    }

    /**
     * @return void
     */
    public function testReadShouldKeepEmptyCells()
    {
        $allRows = $this->getAllRowsForFile('csv_with_empty_cells.csv');

        $expectedRows = [
            ['csv--11', 'csv--12', 'csv--13'],
            ['csv--21', '', 'csv--23'],
            ['csv--31', 'csv--32', ''],
        ];
        $this->assertEquals($expectedRows, $allRows);
    }

    /**
     * @return array
     */
    public function dataProviderForTestReadShouldSkipEmptyLines()
    {
        return [
            ['csv_with_empty_line.csv'],
            ['csv_with_empty_last_line.csv'],
        ];
    }

    /**
     * @dataProvider dataProviderForTestReadShouldSkipEmptyLines
     *
     * @param string $fileName
     * @return void
     */
    public function testReadShouldSkipEmptyLines($fileName)
    {
        $allRows = $this->getAllRowsForFile($fileName);

        $expectedRows = [
            ['csv--11', 'csv--12', 'csv--13'],
            ['csv--31', 'csv--32', 'csv--33'],
        ];
        $this->assertEquals($expectedRows, $allRows);
    }

    /**
     * @return array
     */
    public function dataProviderForTestReadShouldReadEmptyFile()
    {
        return [
            ['csv_empty.csv'],
            ['csv_all_lines_empty.csv'],
        ];
    }

    /**
     * @dataProvider dataProviderForTestReadShouldReadEmptyFile
     *
     * @param string $fileName
     * @return void
     */
    public function testReadShouldReadEmptyFile($fileName)
    {
        $allRows = $this->getAllRowsForFile($fileName);
        $this->assertEquals([], $allRows);
    }

    /**
     * @return void
     */
    public function testReadShouldHaveTheRightNumberOfCells()
    {
        $allRows = $this->getAllRowsForFile('csv_with_different_cells_number.csv');

        $expectedRows = [
            ['csv--11', 'csv--12', 'csv--13'],
            ['csv--21', 'csv--22'],
            ['csv--31'],
        ];
        $this->assertEquals($expectedRows, $allRows);
    }

    /**
     * @return void
     */
    public function testReadShouldSupportCustomFieldDelimiter()
    {
        $allRows = $this->getAllRowsForFile('csv_delimited_with_pipes.csv', '|');

        $expectedRows = [
            ['csv--11', 'csv--12', 'csv--13'],
            ['csv--21', 'csv--22', 'csv--23'],
            ['csv--31', 'csv--32', 'csv--33'],
        ];
        $this->assertEquals($expectedRows, $allRows);
    }

    /**
     * @return void
     */
    public function testReadShouldSupportCustomFieldEnclosure()
    {
        $allRows = $this->getAllRowsForFile('csv_text_enclosed_with_pound.csv', ',', '#');
        $this->assertEquals('This is, a comma', $allRows[0][0]);
    }

    /**
     * @return array
     */
    public function dataProviderForTestReadShouldSkipBom()
    {
        return [
            ['csv_with_utf8_bom.csv', EncodingHelper::ENCODING_UTF8],
            ['csv_with_utf16le_bom.csv', EncodingHelper::ENCODING_UTF16_LE],
            ['csv_with_utf16be_bom.csv', EncodingHelper::ENCODING_UTF16_BE],
            ['csv_with_utf32le_bom.csv', EncodingHelper::ENCODING_UTF32_LE],
            ['csv_with_utf32be_bom.csv', EncodingHelper::ENCODING_UTF32_BE],
        ];
    }

    /**
     * @dataProvider dataProviderForTestReadShouldSkipBom
     *
     * @param string $fileName
     * @param string $fileEncoding
     * @return void
     */
    public function testReadShouldSkipBom($fileName, $fileEncoding)
    {
        $allRows = $this->getAllRowsForFile($fileName, ',', '"', $fileEncoding);

        $expectedRows = [
            ['csv--11', 'csv--12', 'csv--13'],
            ['csv--21', 'csv--22', 'csv--23'],
            ['csv--31', 'csv--32', 'csv--33'],
        ];
        $this->assertEquals($expectedRows, $allRows);
    }

    /**
     * @return array
     */
    public function dataProviderForTestReadShouldSupportNonUTF8FilesWithoutBOMs()
    {
        $shouldUseIconv = true;
        $shouldNotUseIconv = false;

        return [
            ['csv_with_encoding_utf16le_no_bom.csv', EncodingHelper::ENCODING_UTF16_LE, $shouldUseIconv],
            ['csv_with_encoding_utf16le_no_bom.csv', EncodingHelper::ENCODING_UTF16_LE, $shouldNotUseIconv],
            ['csv_with_encoding_cp1252.csv', 'CP1252', $shouldUseIconv],
            ['csv_with_encoding_cp1252.csv', 'CP1252', $shouldNotUseIconv],
        ];
    }

    /**
     * @dataProvider dataProviderForTestReadShouldSupportNonUTF8FilesWithoutBOMs
     *
     * @param string $fileName
     * @param string $fileEncoding
     * @param bool $shouldUseIconv
     * @return void
     */
    public function testReadShouldSupportNonUTF8FilesWithoutBOMs($fileName, $fileEncoding, $shouldUseIconv)
    {
        $allRows = [];
        $resourcePath = $this->getResourcePath($fileName);

        $helperStub = $this->getMockBuilder('\Box\Spout\Common\Helper\GlobalFunctionsHelper')
                        ->setMethods(['function_exists'])
                        ->getMock();

        $returnValueMap = [
            ['iconv', $shouldUseIconv],
            ['mb_convert_encoding', true],
        ];
        $helperStub->method('function_exists')->will($this->returnValueMap($returnValueMap));

        /** @var \Box\Spout\Reader\CSV\Reader $reader */
        $reader = ReaderFactory::create(Type::CSV);
        $reader
            ->setGlobalFunctionsHelper($helperStub)
            ->setEncoding($fileEncoding)
            ->open($resourcePath);

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $allRows[] = $row;
            }
        }

        $reader->close();

        $expectedRows = [
            ['csv--11', 'csv--12', 'csv--13'],
            ['csv--21', 'csv--22', 'csv--23'],
            ['csv--31', 'csv--32', 'csv--33'],
        ];
        $this->assertEquals($expectedRows, $allRows);
    }

    /**
     * @return void
     */
    public function testReadMultipleTimesShouldRewindReader()
    {
        $allRows = [];
        $resourcePath = $this->getResourcePath('csv_standard.csv');

        $reader = ReaderFactory::create(Type::CSV);
        $reader->open($resourcePath);

        foreach ($reader->getSheetIterator() as $sheet) {
            // do nothing
        }

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $allRows[] = $row;
                break;
            }

            foreach ($sheet->getRowIterator() as $row) {
                $allRows[] = $row;
                break;
            }
        }

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $allRows[] = $row;
                break;
            }
        }

        $reader->close();

        $expectedRows = [
            ['csv--11', 'csv--12', 'csv--13'],
            ['csv--11', 'csv--12', 'csv--13'],
            ['csv--11', 'csv--12', 'csv--13'],
        ];
        $this->assertEquals($expectedRows, $allRows);
    }

    /**
     * @param string $fileName
     * @param string|void $fieldDelimiter
     * @param string|void $fieldEnclosure
     * @param string|void $encoding
     * @return array All the read rows the given file
     */
    private function getAllRowsForFile(
        $fileName,
        $fieldDelimiter = ',',
        $fieldEnclosure = '"',
        $encoding = EncodingHelper::ENCODING_UTF8)
    {
        $allRows = [];
        $resourcePath = $this->getResourcePath($fileName);

        /** @var \Box\Spout\Reader\CSV\Reader $reader */
        $reader = ReaderFactory::create(Type::CSV);
        $reader
            ->setFieldDelimiter($fieldDelimiter)
            ->setFieldEnclosure($fieldEnclosure)
            ->setEncoding($encoding)
            ->open($resourcePath);

        foreach ($reader->getSheetIterator() as $sheetIndex => $sheet) {
            foreach ($sheet->getRowIterator() as $rowIndex => $row) {
                $allRows[] = $row;
            }
        }

        $reader->close();

        return $allRows;
    }

    /**
     * @return array
     */
    public function dataProviderForTestReadCustomEOL()
    {
        return [
            ['csv_with_CR_EOL.csv', "\r"],
            ['csv_standard.csv', "\n"],
        ];
    }

    /**
     * @dataProvider dataProviderForTestReadCustomEOL
     *
     * @param string $fileName
     * @param string $customEOL
     * @return void
     */
    public function testReadCustomEOLs($fileName, $customEOL)
    {
        $allRows = [];
        $resourcePath = $this->getResourcePath($fileName);

        /** @var \Box\Spout\Reader\CSV\Reader $reader */
        $reader = ReaderFactory::create(Type::CSV);
        $reader
            ->setEndOfLineCharacter($customEOL)
            ->open($resourcePath);

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $allRows[] = $row;
            }
        }

        $reader->close();

        $expectedRows = [
            ['csv--11', 'csv--12', 'csv--13'],
            ['csv--21', 'csv--22', 'csv--23'],
            ['csv--31', 'csv--32', 'csv--33'],
        ];
        $this->assertEquals($expectedRows, $allRows);
    }

}
