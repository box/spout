<?php

namespace Box\Spout\Reader\ODS;

use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Type;
use Box\Spout\Reader\ReaderFactory;
use Box\Spout\TestUsingResource;

/**
 * Class ReaderTest
 *
 * @package Box\Spout\Reader\ODS
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
            ['/path/to/fake/file.ods'],
            ['file_corrupted.ods'],
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
            ['one_sheet_with_strings.ods', 2, 3],
            ['two_sheets_with_strings.ods', 4, 3],
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
    public function testReadShouldSupportRowWithOnlyOneCell()
    {
        $allRows = $this->getAllRowsForFile('sheet_with_only_one_cell.ods');
        $this->assertEquals([['foo']], $allRows);
    }

    /**
     * @return void
     */
    public function testReadShouldSupportNumberColumnsRepeated()
    {
        $allRows = $this->getAllRowsForFile('sheet_with_number_columns_repeated.ods');
        $expectedRows = [
            [
                'foo', 'foo', 'foo',
                '', '',
                true, true,
                10.43, 10.43, 10.43, 10.43,
            ],
        ];
        $this->assertEquals($expectedRows, $allRows);
    }

    /**
     * @return array
     */
    public function dataProviderForTestReadWithFilesGeneratedByExternalSoftwares()
    {
        return [
            ['file_generated_by_libre_office.ods', true],
            ['file_generated_by_excel_2010_windows.ods', false],
            ['file_generated_by_excel_office_online.ods', false],
        ];
    }

    /**
     * @dataProvider dataProviderForTestReadWithFilesGeneratedByExternalSoftwares
     * The files contain styles, different value types, gaps between cells,
     * repeated values, empty row, different number of cells per row.
     *
     * @param bool $skipLastEmptyValues
     * @param string $fileName
     * @return void
     */
    public function testReadWithFilesGeneratedByExternalSoftwares($fileName, $skipLastEmptyValues)
    {
        $allRows = $this->getAllRowsForFile($fileName);

        $expectedRows = [
            ['header1','header2','header3','header4'],
            ['val11','val12','val13','val14'],
            ['val21','','val23','val23'],
            ['', 10.43, 29.11],
        ];

        // In the description of the last cell, Excel specifies that the empty value needs to be repeated
        // a lot of times (16384 - number of cells used in the row). To avoid creating 16384 cells all the time,
        // this cell is skipped alltogether.
        if ($skipLastEmptyValues) {
            $expectedRows[3][] = '';
        }

        $this->assertEquals($expectedRows, $allRows);
    }


    /**
     * @return void
     */
    public function testReadShouldSupportAllCellTypes()
    {
        $utcTz = new \DateTimeZone('UTC');
        $honoluluTz = new \DateTimeZone('Pacific/Honolulu'); // UTC-10

        $allRows = $this->getAllRowsForFile('sheet_with_all_cell_types.ods');

        $expectedRows = [
            [
                'ods--11', 'ods--12',
                true, false,
                0, 10.43,
                new \DateTime('1987-11-29T00:00:00', $utcTz), new \DateTime('1987-11-29T13:37:00', $utcTz),
                new \DateTime('1987-11-29T13:37:00', $utcTz), new \DateTime('1987-11-29T13:37:00', $honoluluTz),
                new \DateInterval('PT13H37M00S'),
                0, 0.42,
                '42 USD', '9.99 EUR',
                '',
            ],
        ];
        $this->assertEquals($expectedRows, $allRows);
    }

    /**
     * @return void
     */
    public function testReadShouldReturnEmptyStringOnUndefinedCellType()
    {
        $allRows = $this->getAllRowsForFile('sheet_with_undefined_value_type.ods');
        $this->assertEquals([['ods--11', '', 'ods--13']], $allRows);
    }

    /**
     * @return void
     */
    public function testReadShouldReturnNullOnInvalidDateOrTime()
    {
        $allRows = $this->getAllRowsForFile('sheet_with_invalid_date_time.ods');
        $this->assertEquals([[null, null]], $allRows);
    }

    /**
     * @return void
     */
    public function testReadShouldSupportMultilineStrings()
    {
        $allRows = $this->getAllRowsForFile('sheet_with_multiline_string.ods');

        $expectedRows = [["string\non multiple\nlines!"]];
        $this->assertEquals($expectedRows, $allRows);
    }

    /**
     * @return void
     */
    public function testReadShouldSkipEmptyRow()
    {
        $allRows = $this->getAllRowsForFile('sheet_with_empty_row.ods');
        $this->assertEquals(2, count($allRows), 'There should be only 2 rows, because the empty row is skipped');

        $expectedRows = [
            ['ods--11', 'ods--12', 'ods--13'],
            // row skipped here
            ['ods--21', 'ods--22', 'ods--23'],
        ];
        $this->assertEquals($expectedRows, $allRows);
    }

    /**
     * @return void
     */
    public function testReadShouldPreserveSpacing()
    {
        $allRows = $this->getAllRowsForFile('sheet_with_various_spaces.ods');

        $expectedRow = [
            '    4 spaces before and after    ',
            ' 1 space before and after ',
            '2 spaces after  ',
            '  2 spaces before',
            "3 spaces   in the middle\nand 2 spaces  in the middle",
        ];
        $this->assertEquals([$expectedRow], $allRows);
    }


    /**
     * @NOTE: The LIBXML_NOENT is used to ACTUALLY substitute entities (and should therefore not be used)
     *
     * @return void
     */
    public function testReadShouldBeProtectedAgainstBillionLaughAttack()
    {
        $startTime = microtime(true);
        $fileName = 'attack_billion_laughs.ods';

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
     * @NOTE: The LIBXML_NOENT is used to ACTUALLY substitute entities (and should therefore not be used)
     *
     * @return void
     */
    public function testReadShouldBeProtectedAgainstQuadraticBlowupAttack()
    {
        $startTime = microtime(true);

        $fileName = 'attack_quadratic_blowup.ods';
        $allRows = $this->getAllRowsForFile($fileName);

        $this->assertEquals('', $allRows[0][0], 'Entities should not have been expanded');

        $duration = microtime(true) - $startTime;
        $this->assertLessThan(10, $duration, 'Entities should not be expanded and therefore take more than 10 seconds to be parsed.');

        $expectedMaxMemoryUsage = 30 * 1024 * 1024; // 30MB
        $this->assertLessThan($expectedMaxMemoryUsage, memory_get_peak_usage(true), 'Entities should not be expanded and therefore consume all the memory.');
    }

    /**
     * @return void
     */
    public function testReadShouldBeAbleToProcessEmptySheets()
    {
        $allRows = $this->getAllRowsForFile('sheet_with_no_cells.ods');
        $this->assertEquals([], $allRows, 'Sheet with no cells should be correctly processed.');
    }

    /**
     * @return void
     */
    public function testReadShouldSkipFormulas()
    {
        $allRows = $this->getAllRowsForFile('sheet_with_formulas.ods');

        $expectedRows = [
            ['val1', 'val2', 'total1', 'total2'],
            [10, 20, 30, 21],
            [11, 21, 32, 41],
        ];
        $this->assertEquals($expectedRows, $allRows);
    }

    /**
     * @expectedException \Box\Spout\Reader\Exception\IteratorNotRewindableException
     *
     * @return void
     */
    public function testReadShouldThrowIfTryingToRewindRowIterator()
    {
        $resourcePath = $this->getResourcePath('one_sheet_with_strings.ods');
        $reader = ReaderFactory::create(Type::ODS);
        $reader->open($resourcePath);

        foreach ($reader->getSheetIterator() as $sheet) {
            // start looping throw the rows
            foreach ($sheet->getRowIterator() as $row) {
                break;
            }

            // this will rewind the row iterator
            foreach ($sheet->getRowIterator() as $row) {
                break;
            }
        }
    }

    /**
     * @return void
     */
    public function testReadMultipleTimesShouldRewindReader()
    {
        $allRows = [];
        $resourcePath = $this->getResourcePath('two_sheets_with_strings.ods');

        $reader = ReaderFactory::create(Type::ODS);
        $reader->open($resourcePath);

        foreach ($reader->getSheetIterator() as $sheet) {
            // do nothing
        }

        // this loop should only add the first row of each sheet
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $allRows[] = $row;
                break;
            }
        }

        // this loop should only add the first row of the first sheet
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $allRows[] = $row;
                break;
            }

            // stop reading more sheets
            break;
        }

        $reader->close();

        $expectedRows = [
            ['ods--sheet1--11', 'ods--sheet1--12', 'ods--sheet1--13'], // 1st row, 1st sheet
            ['ods--sheet2--11', 'ods--sheet2--12', 'ods--sheet2--13'], // 1st row, 2nd sheet
            ['ods--sheet1--11', 'ods--sheet1--12', 'ods--sheet1--13'], // 1st row, 1st sheet
        ];
        $this->assertEquals($expectedRows, $allRows);
    }

    /**
     * @param string $fileName
     * @return array All the read rows the given file
     */
    private function getAllRowsForFile($fileName)
    {
        $allRows = [];
        $resourcePath = $this->getResourcePath($fileName);

        $reader = ReaderFactory::create(Type::ODS);
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
