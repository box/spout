<?php

namespace Box\Spout\Reader\XLSX;

use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Type;
use Box\Spout\Reader\ReaderFactory;
use Box\Spout\TestUsingResource;

/**
 * Class ReaderTest
 *
 * @package Box\Spout\Reader\XLSX
 */
class ReaderTest extends \PHPUnit_Framework_TestCase
{
    use TestUsingResource;

    /**
     * @return array
     */
    public function dataProviderForTestReadShouldThrowException()
    {
        return [
            ['/path/to/fake/file.xlsx'],
            ['file_with_no_sheets_in_workbook_xml.xlsx'],
            ['file_with_sheet_xml_not_matching_content_types.xlsx'],
            ['file_corrupted.xlsx'],
        ];
    }

    /**
     * @dataProvider dataProviderForTestReadShouldThrowException
     * @expectedException \Box\Spout\Common\Exception\IOException
     *
     * @param string $filePath
     * @return void
     */
    public function testReadShouldThrowException($filePath)
    {
        // using @ to prevent warnings/errors from being displayed
        @$this->getAllRowsForFile($filePath);
    }

    /**
     * @return array
     */
    public function dataProviderForTestReadForAllWorksheets()
    {
        return [
            ['one_sheet_with_shared_strings.xlsx', 5, 5],
            ['one_sheet_with_inline_strings.xlsx', 5, 5],
            ['two_sheets_with_shared_strings.xlsx', 10, 5],
            ['two_sheets_with_inline_strings.xlsx', 10, 5]
        ];
    }

    /**
     * @dataProvider dataProviderForTestReadForAllWorksheets
     *
     * @param string $resourceName
     * @param int $expectedNumOfRows
     * @param int $expectedNumOfCellsPerRow
     * @return void
     */
    public function testReadForAllWorksheets($resourceName, $expectedNumOfRows, $expectedNumOfCellsPerRow)
    {
        $allRows = $this->getAllRowsForFile($resourceName);

        $this->assertEquals($expectedNumOfRows, count($allRows), "There should be $expectedNumOfRows rows");
        foreach ($allRows as $row) {
            $this->assertEquals($expectedNumOfCellsPerRow, count($row), "There should be $expectedNumOfCellsPerRow cells for every row");
        }
    }

    /**
     * @return void
     */
    public function testReadShouldSupportSheetsDefinitionInRandomOrder()
    {
        $allRows = $this->getAllRowsForFile('two_sheets_with_sheets_definition_in_reverse_order.xlsx');

        $expectedRows = [
            ['s1 - A1', 's1 - B1', 's1 - C1', 's1 - D1', 's1 - E1'],
            ['s1 - A2', 's1 - B2', 's1 - C2', 's1 - D2', 's1 - E2'],
            ['s1 - A3', 's1 - B3', 's1 - C3', 's1 - D3', 's1 - E3'],
            ['s1 - A4', 's1 - B4', 's1 - C4', 's1 - D4', 's1 - E4'],
            ['s1 - A5', 's1 - B5', 's1 - C5', 's1 - D5', 's1 - E5'],
            ['s2 - A1', 's2 - B1', 's2 - C1', 's2 - D1', 's2 - E1'],
            ['s2 - A2', 's2 - B2', 's2 - C2', 's2 - D2', 's2 - E2'],
            ['s2 - A3', 's2 - B3', 's2 - C3', 's2 - D3', 's2 - E3'],
            ['s2 - A4', 's2 - B4', 's2 - C4', 's2 - D4', 's2 - E4'],
            ['s2 - A5', 's2 - B5', 's2 - C5', 's2 - D5', 's2 - E5'],
        ];
        $this->assertEquals($expectedRows, $allRows);
    }

    /**
     * @return void
     */
    public function testReadShouldSupportPrefixedXMLFiles()
    {
        // The XML files of this spreadsheet are prefixed.
        // For instance, they use "<x:sheet>" instead of "<sheet>", etc.
        $allRows = $this->getAllRowsForFile('sheet_with_prefixed_xml_files.xlsx');

        $expectedRows = [
            ['s1 - A1', 's1 - B1', 's1 - C1'],
            ['s1 - A2', 's1 - B2', 's1 - C2'],
            ['s1 - A3', 's1 - B3', 's1 - C3'],
        ];
        $this->assertEquals($expectedRows, $allRows);
    }

    /**
     * @return void
     */
    public function testReadShouldSupportSheetWithSharedStringsMissingUniqueCountAttribute()
    {
        $allRows = $this->getAllRowsForFile('one_sheet_with_shared_strings_missing_unique_count.xlsx');

        $expectedRows = [
            ['s1--A1', 's1--B1'],
            ['s1--A2', 's1--B2'],
        ];
        $this->assertEquals($expectedRows, $allRows);
    }

    /**
     * @return void
     */
    public function testReadShouldSupportSheetWithSharedStringsMissingUniqueCountAndCountAttributes()
    {
        $allRows = $this->getAllRowsForFile('one_sheet_with_shared_strings_missing_unique_count_and_count.xlsx');

        $expectedRows = [
            ['s1--A1', 's1--B1'],
            ['s1--A2', 's1--B2'],
        ];
        $this->assertEquals($expectedRows, $allRows);
    }

    /**
     * @return void
     */
    public function testReadShouldSupportFilesWithoutSharedStringsFile()
    {
        $allRows = $this->getAllRowsForFile('sheet_with_no_shared_strings_file.xlsx');

        $expectedRows = [
            [10, 11],
            [20, 21],
        ];
        $this->assertEquals($expectedRows, $allRows);
    }

    /**
     * @return void
     */
    public function testReadShouldSupportFilesWithoutCellReference()
    {
        // file where the cell definition does not have a "r" attribute
        // as in <c r="A1">...</c>
        $allRows = $this->getAllRowsForFile('sheet_with_missing_cell_reference.xlsx');

        $expectedRows = [
            ['s1--A1'],
        ];
        $this->assertEquals($expectedRows, $allRows);
    }

    /**
     * @return void
     */
    public function testReadShouldSupportAllCellTypes()
    {
        // make sure dates are always created with the same timezone
        date_default_timezone_set('UTC');

        $allRows = $this->getAllRowsForFile('sheet_with_all_cell_types.xlsx');

        $expectedRows = [
            [
                's1--A1', 's1--A2',
                false, true,
                \DateTime::createFromFormat('Y-m-d H:i:s', '2015-06-03 13:21:58'),
                \DateTime::createFromFormat('Y-m-d H:i:s', '2015-06-01 00:00:00'),
                10, 10.43,
                null,
                'weird string', // valid 'str' string
                null, // invalid date
            ],
            ['', '', '', '', '', '', '', '', ''],
        ];
        $this->assertEquals($expectedRows, $allRows);
    }

    /**
     * @return void
     */
    public function testReadShouldSupportNumericTimestampFormattedDifferentlyAsDate()
    {
        // make sure dates are always created with the same timezone
        date_default_timezone_set('UTC');

        $allRows = $this->getAllRowsForFile('sheet_with_same_numeric_value_date_formatted_differently.xlsx');

        $expectedDate = \DateTime::createFromFormat('Y-m-d H:i:s', '2015-01-01 00:00:00');
        $expectedRows = [
            array_fill(0, 10, $expectedDate),
            array_fill(0, 10, $expectedDate),
            array_fill(0, 10, $expectedDate),
            array_merge(array_fill(0, 7, $expectedDate), ['', '', '']),
        ];

        $this->assertEquals($expectedRows, $allRows);
    }

    /**
     * @return void
     */
    public function testReadShouldSupportDifferentDatesAsNumericTimestamp()
    {
        // make sure dates are always created with the same timezone
        date_default_timezone_set('UTC');

        $allRows = $this->getAllRowsForFile('sheet_with_different_numeric_value_dates.xlsx');

        $expectedRows = [
            [
                \DateTime::createFromFormat('Y-m-d H:i:s', '2015-09-01 00:00:00'),
                \DateTime::createFromFormat('Y-m-d H:i:s', '2015-09-02 00:00:00'),
                \DateTime::createFromFormat('Y-m-d H:i:s', '2015-09-01 22:23:00'),
            ],
            [
                \DateTime::createFromFormat('Y-m-d H:i:s', '1900-02-28 23:59:59'),
                \DateTime::createFromFormat('Y-m-d H:i:s', '1900-03-01 00:00:00'),
                \DateTime::createFromFormat('Y-m-d H:i:s', '1900-02-28 11:00:00'), // 1900-02-29 should be converted to 1900-02-28
            ]
        ];
        $this->assertEquals($expectedRows, $allRows);
    }

    /**
     * @return void
     */
    public function testReadShouldSupportDifferentTimesAsNumericTimestamp()
    {
        // make sure dates are always created with the same timezone
        date_default_timezone_set('UTC');

        $allRows = $this->getAllRowsForFile('sheet_with_different_numeric_value_times.xlsx');

        $expectedRows = [
            [
                \DateTime::createFromFormat('Y-m-d H:i:s', '1900-01-01 00:00:00'),
                \DateTime::createFromFormat('Y-m-d H:i:s', '1900-01-01 11:29:00'),
                \DateTime::createFromFormat('Y-m-d H:i:s', '1900-01-01 23:29:00'),
                \DateTime::createFromFormat('Y-m-d H:i:s', '1900-01-01 01:42:25'),
                \DateTime::createFromFormat('Y-m-d H:i:s', '1900-01-01 13:42:25'),
            ]
        ];
        $this->assertEquals($expectedRows, $allRows);
    }

    /**
     * @return void
     */
    public function testReadShouldSupportFormatDatesAndTimesIfSpecified()
    {
        $shouldFormatDates = true;
        $allRows = $this->getAllRowsForFile('sheet_with_dates_and_times.xlsx', $shouldFormatDates);

        $expectedRows = [
            ['1/13/2016', '01/13/2016', '13-Jan-16', 'Wednesday January 13, 16', 'Today is 1/13/2016'],
            ['4:43:25', '04:43', '4:43', '4:43:25 AM', '4:43:25 PM'],
            ['1976-11-22T08:30:00.000', '1976-11-22T08:30', '1582-10-15', '08:30:00', '08:30'],
        ];
        $this->assertEquals($expectedRows, $allRows);
    }

    /**
     * @return void
     */
    public function testReadShouldApplyCustomDateFormatNumberEvenIfApplyNumberFormatNotSpecified()
    {
        $shouldFormatDates = true;
        $allRows = $this->getAllRowsForFile('sheet_with_custom_date_formats_and_no_apply_number_format.xlsx', $shouldFormatDates);

        $expectedRows = [
            // "General", "GENERAL", "MM/DD/YYYY", "MM/dd/YYYY", "H:MM:SS"
            ['42382', '42382', '01/13/2016', '01/13/2016', '4:43:25'],
        ];
        $this->assertEquals($expectedRows, $allRows);
    }

    /**
     * @return void
     */
    public function testReadShouldKeepEmptyCellsAtTheEndIfDimensionsSpecified()
    {
        $allRows = $this->getAllRowsForFile('sheet_without_dimensions_but_spans_and_empty_cells.xlsx');

        $this->assertEquals(2, count($allRows), 'There should be 2 rows');
        foreach ($allRows as $row) {
            $this->assertEquals(5, count($row), 'There should be 5 cells for every row, because empty rows should be preserved');
        }

        $expectedRows = [
            ['s1--A1', 's1--B1', 's1--C1', 's1--D1', 's1--E1'],
            ['s1--A2', 's1--B2', 's1--C2', '', ''],
        ];
        $this->assertEquals($expectedRows, $allRows);
    }

    /**
     * @return void
     */
    public function testReadShouldKeepEmptyCellsAtTheEndIfNoDimensionsButSpansSpecified()
    {
        $allRows = $this->getAllRowsForFile('sheet_without_dimensions_and_empty_cells.xlsx');

        $this->assertEquals(2, count($allRows), 'There should be 2 rows');
        $this->assertEquals(5, count($allRows[0]), 'There should be 5 cells in the first row');
        $this->assertEquals(3, count($allRows[1]), 'There should be only 3 cells in the second row, because empty rows at the end should be skip');

        $expectedRows = [
            ['s1--A1', 's1--B1', 's1--C1', 's1--D1', 's1--E1'],
            ['s1--A2', 's1--B2', 's1--C2'],
        ];
        $this->assertEquals($expectedRows, $allRows);
    }

    /**
     * @return void
     */
    public function testReadShouldSkipEmptyCellsAtTheEndIfDimensionsNotSpecified()
    {
        $allRows = $this->getAllRowsForFile('sheet_without_dimensions_and_empty_cells.xlsx');

        $this->assertEquals(2, count($allRows), 'There should be 2 rows');
        $this->assertEquals(5, count($allRows[0]), 'There should be 5 cells in the first row');
        $this->assertEquals(3, count($allRows[1]), 'There should be only 3 cells in the second row, because empty rows at the end should be skip');

        $expectedRows = [
            ['s1--A1', 's1--B1', 's1--C1', 's1--D1', 's1--E1'],
            ['s1--A2', 's1--B2', 's1--C2'],
        ];
        $this->assertEquals($expectedRows, $allRows);
    }

    /**
     * @return void
     */
    public function testReadShouldSkipEmptyRowsIfShouldPreserveEmptyRowsNotSet()
    {
        $allRows = $this->getAllRowsForFile('sheet_with_empty_rows_and_missing_row_index.xlsx');

        $this->assertEquals(3, count($allRows), 'There should be only 3 rows, because the empty rows are skipped');

        $expectedRows = [
            // skipped row here
            ['s1--A2', 's1--B2', 's1--C2'],
            // skipped row here
            // skipped row here
            ['s1--A5', 's1--B5', 's1--C5'],
            ['s1--A6', 's1--B6', 's1--C6'],
        ];
        $this->assertEquals($expectedRows, $allRows);
    }

    /**
     * @return void
     */
    public function testReadShouldReturnEmptyLinesIfShouldPreserveEmptyRowsSet()
    {
        $allRows = $this->getAllRowsForFile('sheet_with_empty_rows_and_missing_row_index.xlsx', false, true);

        $this->assertEquals(6, count($allRows), 'There should be 6 rows');

        $expectedRows = [
            [''],
            ['s1--A2', 's1--B2', 's1--C2'],
            [''],
            [''],
            ['s1--A5', 's1--B5', 's1--C5'],
            ['s1--A6', 's1--B6', 's1--C6'],
        ];
        $this->assertEquals($expectedRows, $allRows);
    }

    /**
     * @return void
     */
    public function testReadShouldSupportEmptySharedString()
    {
        $allRows = $this->getAllRowsForFile('sheet_with_empty_shared_string.xlsx');

        $expectedRows = [
            ['s1--A1', '', 's1--C1'],
        ];
        $this->assertEquals($expectedRows, $allRows);
    }

    /**
     * @return void
     */
    public function testReadShouldPreserveSpaceIfSpecified()
    {
        $allRows = $this->getAllRowsForFile('sheet_with_preserve_space_shared_strings.xlsx');

        $expectedRows = [
            ['  s1--A1', 's1--B1  ', '  s1--C1  '],
        ];
        $this->assertEquals($expectedRows, $allRows);
    }

    /**
     * @return void
     */
    public function testReadShouldSkipPronunciationData()
    {
        $allRows = $this->getAllRowsForFile('sheet_with_pronunciation.xlsx');

        $expectedRow = ['名前', '一二三四'];
        $this->assertEquals($expectedRow, $allRows[0], 'Pronunciation data should be removed.');
    }


    /**
     * @return array
     */
    public function dataProviderForTestReadShouldBeProtectedAgainstAttacks()
    {
        return [
            ['attack_billion_laughs.xlsx'],
            ['attack_quadratic_blowup.xlsx'],
        ];
    }

    /**
     * @dataProvider dataProviderForTestReadShouldBeProtectedAgainstAttacks
     * @NOTE: The LIBXML_NOENT is used to ACTUALLY substitute entities (and should therefore not be used)
     *
     * @param string $fileName
     * @return void
     */
    public function testReadShouldBeProtectedAgainstAttacks($fileName)
    {
        $startTime = microtime(true);

        try {
            // using @ to prevent warnings/errors from being displayed
            @$this->getAllRowsForFile($fileName);
            $this->fail('An exception should have been thrown');
        } catch (IOException $exception) {
            $duration = microtime(true) - $startTime;
            $this->assertLessThan(10, $duration, 'Entities should not be expanded and therefore take more than 10 seconds to be parsed.');

            $expectedMaxMemoryUsage = 30 * 1024 * 1024; // 30MB
            $this->assertLessThan($expectedMaxMemoryUsage, memory_get_peak_usage(true), 'Entities should not be expanded and therefore consume all the memory.');
        }
    }

    /**
     * @return void
     */
    public function testReadShouldBeAbleToProcessEmptySheets()
    {
        $allRows = $this->getAllRowsForFile('sheet_with_no_cells.xlsx');
        $this->assertEquals([], $allRows, 'Sheet with no cells should be correctly processed.');
    }

    /**
     * @return void
     */
    public function testReadShouldSkipFormulas()
    {
        $allRows = $this->getAllRowsForFile('sheet_with_formulas.xlsx');

        $expectedRows = [
            ['val1', 'val2', 'total1', 'total2'],
            [10, 20, 30, 21],
            [11, 21, 32, 41],
        ];
        $this->assertEquals($expectedRows, $allRows);
    }

    /**
     * @return void
     */
    public function testReadMultipleTimesShouldRewindReader()
    {
        $allRows = [];
        $resourcePath = $this->getResourcePath('two_sheets_with_inline_strings.xlsx');

        $reader = ReaderFactory::create(Type::XLSX);
        $reader->open($resourcePath);

        foreach ($reader->getSheetIterator() as $sheet) {
            // do nothing
        }

        foreach ($reader->getSheetIterator() as $sheet) {
            // this loop should only add the first row of the first sheet
            foreach ($sheet->getRowIterator() as $row) {
                $allRows[] = $row;
                break;
            }

            // this loop should rewind the iterator and restart reading from the 1st row again
            // therefore, it should only add the first row of the first sheet
            foreach ($sheet->getRowIterator() as $row) {
                $allRows[] = $row;
                break;
            }

            // not reading any more sheets
            break;
        }

        foreach ($reader->getSheetIterator() as $sheet) {
            // this loop should only add the first row of the current sheet
            foreach ($sheet->getRowIterator() as $row) {
                $allRows[] = $row;
                break;
            }

            // not breaking, so we keep reading the next sheets
        }

        $reader->close();

        $expectedRows = [
            ['s1 - A1', 's1 - B1', 's1 - C1', 's1 - D1', 's1 - E1'],
            ['s1 - A1', 's1 - B1', 's1 - C1', 's1 - D1', 's1 - E1'],
            ['s1 - A1', 's1 - B1', 's1 - C1', 's1 - D1', 's1 - E1'],
            ['s2 - A1', 's2 - B1', 's2 - C1', 's2 - D1', 's2 - E1'],
        ];
        $this->assertEquals($expectedRows, $allRows);
    }

    /**
     * @expectedException \Box\Spout\Common\Exception\IOException
     *
     * @return void
     */
    public function testReadWithUnsupportedCustomStreamWrapper()
    {
        /** @var \Box\Spout\Reader\XLSX\Reader $reader */
        $reader = ReaderFactory::create(Type::XLSX);
        $reader->open('unsupported://foobar');
    }

    /**
     * @expectedException \Box\Spout\Common\Exception\IOException
     *
     * @return void
     */
    public function testReadWithSupportedCustomStreamWrapper()
    {
        /** @var \Box\Spout\Reader\XLSX\Reader $reader */
        $reader = ReaderFactory::create(Type::XLSX);
        $reader->open('php://memory');
    }

    /**
     * https://github.com/box/spout/issues/184
     * @return void
     */
    public function testReadShouldInludeRowsWithZerosOnly()
    {
        $allRows = $this->getAllRowsForFile('sheet_with_zeros_in_row.xlsx');

        $expectedRows = [
            ['A', 'B', 'C'],
            ['1', '2', '3'],
            ['0', '0', '0']
        ];
        $this->assertEquals($expectedRows, $allRows, 'There should be only 3 rows, because zeros (0) are valid values');
    }

    /**
     * https://github.com/box/spout/issues/184
     * @return void
     */
    public function testReadShouldCreateOutputEmptyCellPreserved()
    {
        $allRows = $this->getAllRowsForFile('sheet_with_empty_cells.xlsx');

        $expectedRows = [
            ['A', 'B', 'C'],
            ['0', '', ''],
            ['1', '1', '']
        ];
        $this->assertEquals($expectedRows, $allRows, 'There should be 3 rows, with equal length');
    }


    /**
     * https://github.com/box/spout/issues/195
     * @return void
     */
    public function testReaderShouldNotTrimCellValues()
    {
        $allRows = $this->getAllRowsForFile('sheet_with_untrimmed_inline_strings.xlsx');

        $expectedRows = [
            ['A'],
            [' A '],
            ["\n\tA\n\t"],
        ];

        $this->assertEquals($expectedRows, $allRows, 'Cell values should not be trimmed');
    }


    /**
     * @param string $fileName
     * @param bool|void $shouldFormatDates
     * @param bool|void $shouldPreserveEmptyRows
     * @return array All the read rows the given file
     */
    private function getAllRowsForFile($fileName, $shouldFormatDates = false, $shouldPreserveEmptyRows = false)
    {
        $allRows = [];
        $resourcePath = $this->getResourcePath($fileName);

        /** @var \Box\Spout\Reader\XLSX\Reader $reader */
        $reader = ReaderFactory::create(Type::XLSX);
        $reader->setShouldFormatDates($shouldFormatDates);
        $reader->setShouldPreserveEmptyRows($shouldPreserveEmptyRows);
        $reader->open($resourcePath);

        foreach ($reader->getSheetIterator() as $sheetIndex => $sheet) {
            foreach ($sheet->getRowIterator() as $rowIndex => $row) {
                $allRows[] = $row;
            }
        }

        $reader->close();

        return $allRows;
    }
}
