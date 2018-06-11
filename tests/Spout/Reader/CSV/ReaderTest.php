<?php

namespace Box\Spout\Reader\CSV;

use Box\Spout\Common\Creator\HelperFactory;
use Box\Spout\Common\Entity\Row;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Helper\EncodingHelper;
use Box\Spout\Common\Helper\GlobalFunctionsHelper;
use Box\Spout\Reader\CSV\Creator\InternalEntityFactory;
use Box\Spout\Reader\CSV\Manager\OptionsManager;
use Box\Spout\Reader\Exception\ReaderNotOpenedException;
use Box\Spout\TestUsingResource;
use PHPUnit\Framework\TestCase;

/**
 * Class ReaderTest
 */
class ReaderTest extends TestCase
{
    use TestUsingResource;

    /**
     * @return void
     */
    public function testOpenShouldThrowExceptionIfFileDoesNotExist()
    {
        $this->expectException(IOException::class);

        $this->createCSVReader()->open('/path/to/fake/file.csv');
    }

    /**
     * @return void
     */
    public function testOpenShouldThrowExceptionIfTryingToReadBeforeOpeningReader()
    {
        $this->expectException(ReaderNotOpenedException::class);

        $this->createCSVReader()->getSheetIterator();
    }

    /**
     * @return void
     */
    public function testOpenShouldThrowExceptionIfFileNotReadable()
    {
        $this->expectException(IOException::class);

        /** @var \Box\Spout\Common\Helper\GlobalFunctionsHelper|\PHPUnit_Framework_MockObject_MockObject $helperStub */
        $helperStub = $this->getMockBuilder('\Box\Spout\Common\Helper\GlobalFunctionsHelper')
                        ->setMethods(['is_readable'])
                        ->getMock();
        $helperStub->method('is_readable')->willReturn(false);

        $resourcePath = $this->getResourcePath('csv_standard.csv');

        $reader = $this->createCSVReader(null, $helperStub);
        $reader->open($resourcePath);
    }

    /**
     * @return void
     */
    public function testOpenShouldThrowExceptionIfCannotOpenFile()
    {
        $this->expectException(IOException::class);

        /** @var \Box\Spout\Common\Helper\GlobalFunctionsHelper|\PHPUnit_Framework_MockObject_MockObject $helperStub */
        $helperStub = $this->getMockBuilder('\Box\Spout\Common\Helper\GlobalFunctionsHelper')
                        ->setMethods(['fopen'])
                        ->getMock();
        $helperStub->method('fopen')->willReturn(false);

        $resourcePath = $this->getResourcePath('csv_standard.csv');

        $reader = $this->createCSVReader(null, $helperStub);
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
     * @return void
     */
    public function testReadShouldSkipEmptyLinesIfShouldPreserveEmptyRowsNotSet()
    {
        $allRows = $this->getAllRowsForFile('csv_with_multiple_empty_lines.csv');

        $expectedRows = [
            // skipped row here
            ['csv--21', 'csv--22', 'csv--23'],
            // skipped row here
            ['csv--41', 'csv--42', 'csv--43'],
            // skipped row here
            // last row empty
        ];
        $this->assertEquals($expectedRows, $allRows);
    }

    /**
     * @return void
     */
    public function testReadShouldReturnEmptyLinesIfShouldPreserveEmptyRowsSet()
    {
        $allRows = $this->getAllRowsForFile(
            'csv_with_multiple_empty_lines.csv',
            ',',
            '"',
            EncodingHelper::ENCODING_UTF8,
            $shouldPreserveEmptyRows = true
        );

        $expectedRows = [
            [''],
            ['csv--21', 'csv--22', 'csv--23'],
            [''],
            ['csv--41', 'csv--42', 'csv--43'],
            [''],
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
     * @return void
     */
    public function testReadShouldSupportEscapedCharacters()
    {
        $allRows = $this->getAllRowsForFile('csv_with_escaped_characters.csv');

        $expectedRow = ['"csv--11"', 'csv--12\\', 'csv--13\\\\', 'csv--14\\\\\\'];
        $this->assertEquals([$expectedRow], $allRows);
    }

    /**
     * @return void
     */
    public function testReadShouldNotTruncateLineBreak()
    {
        $allRows = $this->getAllRowsForFile('csv_with_line_breaks.csv');
        $this->assertEquals("This is,\na comma", $allRows[0][0]);
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

        /** @var \Box\Spout\Common\Helper\GlobalFunctionsHelper|\PHPUnit_Framework_MockObject_MockObject $helperStub */
        $helperStub = $this->getMockBuilder('\Box\Spout\Common\Helper\GlobalFunctionsHelper')
                        ->setMethods(['function_exists'])
                        ->getMock();

        $returnValueMap = [
            ['iconv', $shouldUseIconv],
            ['mb_convert_encoding', true],
        ];
        $helperStub->method('function_exists')->will($this->returnValueMap($returnValueMap));

        /** @var \Box\Spout\Reader\CSV\Reader $reader */
        $reader = $this->createCSVReader(null, $helperStub);
        $reader
            ->setEncoding($fileEncoding)
            ->open($resourcePath);

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $allRows[] = $row->toArray();
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

        $reader = $this->createCSVReader();
        $reader->open($resourcePath);

        foreach ($reader->getSheetIterator() as $sheet) {
            // do nothing
        }

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $allRows[] = $row->toArray();
                break;
            }

            foreach ($sheet->getRowIterator() as $row) {
                $allRows[] = $row->toArray();
                break;
            }
        }

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $allRows[] = $row->toArray();
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
     * https://github.com/box/spout/issues/184
     * @return void
     */
    public function testReadShouldInludeRowsWithZerosOnly()
    {
        $allRows = $this->getAllRowsForFile('sheet_with_zeros_in_row.csv');

        $expectedRows = [
            ['A', 'B', 'C'],
            ['1', '2', '3'],
            ['0', '0', '0'],
        ];
        $this->assertEquals($expectedRows, $allRows, 'There should be only 3 rows, because zeros (0) are valid values');
    }

    /**
     * https://github.com/box/spout/issues/184
     * @return void
     */
    public function testReadShouldCreateOutputEmptyCellPreserved()
    {
        $allRows = $this->getAllRowsForFile('sheet_with_empty_cells.csv');

        $expectedRows = [
            ['A', 'B', 'C'],
            ['0', '', ''],
            ['1', '1', ''],
        ];
        $this->assertEquals($expectedRows, $allRows, 'There should be 3 rows, with equal length');
    }

    /**
     * https://github.com/box/spout/issues/195
     * @return void
     */
    public function testReaderShouldNotTrimCellValues()
    {
        $allRows = $this->getAllRowsForFile('sheet_with_untrimmed_strings.csv');

        $expectedRows = [
            ['A'],
            [' A '],
            ["\n\tA\n\t"],
        ];

        $this->assertEquals($expectedRows, $allRows, 'Cell values should not be trimmed');
    }

    /**
     * @return void
     */
    public function testReadCustomStreamWrapper()
    {
        $allRows = [];
        $resourcePath = 'spout://csv_standard';

        // register stream wrapper
        stream_wrapper_register('spout', SpoutTestStream::CLASS_NAME);

        /** @var \Box\Spout\Reader\CSV\Reader $reader */
        $reader = $this->createCSVReader();
        $reader->open($resourcePath);

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $allRows[] = $row->toArray();
            }
        }

        $reader->close();

        $expectedRows = [
            ['csv--11', 'csv--12', 'csv--13'],
            ['csv--21', 'csv--22', 'csv--23'],
            ['csv--31', 'csv--32', 'csv--33'],
        ];
        $this->assertEquals($expectedRows, $allRows);

        // cleanup
        stream_wrapper_unregister('spout');
    }

    /**
     * @return void
     */
    public function testReadWithUnsupportedCustomStreamWrapper()
    {
        $this->expectException(IOException::class);

        /** @var \Box\Spout\Reader\CSV\Reader $reader */
        $reader = $this->createCSVReader();
        $reader->open('unsupported://foobar');
    }

    /**
     * @return void
     */
    public function testReadWithStartAndEndColumn()
    {
        $fileName = 'csv_with_headers.csv';
        $allRows = $this->getAllRowsForFile($fileName);

        $expectedRows = [
            ['Header-1', 'Header-2', 'Header-3', ''],
            ['Test-1', 'Test-2', 'Test-3', ''],
            ['Test-1', '', '', ''],
            ['Test-1', 'Test-2', 'Test-3', 'Test-4'],
            ['', '', 'Test-3', ''],
        ];

        $this->assertEquals($expectedRows, $allRows, 'All columns are respected without starting column');

        $expectedRowsWithStartAndEnd = [
            ['Header-2', 'Header-3'],
            ['Test-2', 'Test-3'],
            ['', ''],
            ['Test-2', 'Test-3'],
            ['', 'Test-3'],
        ];

        $rowsWithRange = $this->getAllRowsForFileWithRange($fileName, 1, 2);

        $this->assertEquals(
            $expectedRowsWithStartAndEnd,
            $rowsWithRange,
            'All columns are read starting at index 1 and ending at index 2'
        );

        $expectedRowsWithStart = [
            ['Header-3', ''],
            ['Test-3', ''],
            ['', ''],
            ['Test-3', 'Test-4'],
            ['Test-3', ''],
        ];

        $rowsWithStart = $this->getAllRowsForFileWithRange($fileName, 2);

        $this->assertEquals(
            $expectedRowsWithStart,
            $rowsWithStart,
            'All columns are read starting at index 2'
        );

        $expectedRowsWithEnd = [
            ['Header-1', 'Header-2', 'Header-3'],
            ['Test-1', 'Test-2', 'Test-3'],
            ['Test-1', '', ''],
            ['Test-1', 'Test-2', 'Test-3'],
            ['', '', 'Test-3'],
        ];

        $rowsWithEnd = $this->getAllRowsForFileWithRange($fileName, 0, 2);

        $this->assertEquals(
            $expectedRowsWithEnd,
            $rowsWithEnd,
            'All columns are read ending at index 2'
        );
    }

    /**
     * @return void
     */
    public function testSetStartAndEndColumnAfterReaderOpened()
    {
        $fileName = 'csv_with_headers.csv';
        $resourcePath = $this->getResourcePath($fileName);
        $allRows = [];
        $expectedRowsWithStartAndEnd = [
            ['Header-2', 'Header-3'],
            ['Test-2', 'Test-3'],
            ['', ''],
            ['Test-2', 'Test-3'],
            ['', 'Test-3'],
        ];

        /** @var \Box\Spout\Reader\CSV\Reader $reader */
        $reader = $this->createCSVReader();
        $reader->open($resourcePath);
        $reader->setStartColumnIndex(1);
        $reader->setEndColumnIndex(2);
        foreach ($reader->getSheetIterator() as $sheetIndex => $sheet) {
            /**
             * @var int
             * @var Row $row
             */
            foreach ($sheet->getRowIterator() as $rowIndex => $row) {
                $allRows[] = $row->toArray();
            }
        }
        $reader->close();
        $this->assertEquals($expectedRowsWithStartAndEnd, $allRows, 'Correct range set after reader was opened');
    }

    public function testDifferentCellsAndRange()
    {
        $fileName = 'csv_with_different_cells_number.csv';
        $allRows = $this->getAllRowsForFileWithRange($fileName, 0, 2);

        $expectedRows = [
            ['csv--11', 'csv--12', 'csv--13'],
            ['csv--21', 'csv--22', ''],
            ['csv--31', '', ''],
        ];
        $this->assertEquals($expectedRows, $allRows);
    }

    /**
     * @return void
     * @expectedException \Box\Spout\Reader\Exception\InvalidReaderOptionValueException
     */
    public function testNegativeStartColumnIndex()
    {
        $fileName = 'csv_with_headers.csv';
        $this->getAllRowsForFileWithRange($fileName, -1);
    }

    /**
     * @return void
     * @expectedException \Box\Spout\Reader\Exception\InvalidReaderOptionValueException
     */
    public function testEndColumnIndexSmallerThanStartIndex()
    {
        $fileName = 'csv_with_headers.csv';
        $this->getAllRowsForFileWithRange($fileName, 3, 1);
    }

    /**
     * @param \Box\Spout\Common\Helper\GlobalFunctionsHelper|null $optionsManager
     * @param \Box\Spout\Common\Manager\OptionsManagerInterface|null $globalFunctionsHelper
     * @return Reader
     */
    private function createCSVReader($optionsManager = null, $globalFunctionsHelper = null)
    {
        $optionsManager = $optionsManager ?: new OptionsManager();
        $globalFunctionsHelper = $globalFunctionsHelper ?: new GlobalFunctionsHelper();
        $entityFactory = new InternalEntityFactory(new HelperFactory());

        return new Reader($optionsManager, $globalFunctionsHelper, $entityFactory);
    }

    /**
     * @param string $fileName
     * @param string $fieldDelimiter
     * @param string $fieldEnclosure
     * @param string $encoding
     * @param bool $shouldPreserveEmptyRows
     * @param int $startColumnIndex
     * @param int|null $endColumnIndex
     * @return array All the read rows the given file
     */
    private function getAllRowsForFile(
        string $fileName,
        string $fieldDelimiter = ',',
        string $fieldEnclosure = '"',
        string $encoding = EncodingHelper::ENCODING_UTF8,
        bool $shouldPreserveEmptyRows = false,
        int $startColumnIndex = 0,
        int $endColumnIndex = null
    ) : array {
        $allRows = [];
        $resourcePath = $this->getResourcePath($fileName);

        /** @var \Box\Spout\Reader\CSV\Reader $reader */
        $reader = $this->createCSVReader();

        if ($endColumnIndex) {
            $reader->setEndColumnIndex($endColumnIndex);
        }

        $reader
            ->setFieldDelimiter($fieldDelimiter)
            ->setFieldEnclosure($fieldEnclosure)
            ->setEncoding($encoding)
            ->setShouldPreserveEmptyRows($shouldPreserveEmptyRows)
            ->setStartColumnIndex($startColumnIndex)
            ->open($resourcePath);

        foreach ($reader->getSheetIterator() as $sheetIndex => $sheet) {
            /**
             * @var int
             * @var Row $row
             */
            foreach ($sheet->getRowIterator() as $rowIndex => $row) {
                $allRows[] = $row->toArray();
            }
        }

        $reader->close();

        return $allRows;
    }

    /**
     * @param string $fileName
     * @param int $startColumnIndex
     * @param int|null $endColumnIndex
     * @return array
     */
    protected function getAllRowsForFileWithRange(
        string $fileName,
        int $startColumnIndex = 0,
        int $endColumnIndex = null
    ) : array {
        return $this->getAllRowsForFile(
            $fileName,
            ',',
            '"',
            EncodingHelper::ENCODING_UTF8,
            false,
            $startColumnIndex,
            $endColumnIndex
        );
    }
}
