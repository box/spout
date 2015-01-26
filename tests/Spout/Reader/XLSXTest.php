<?php

namespace Box\Spout\Reader;

use Box\Spout\Common\Type;
use Box\Spout\TestUsingResource;

/**
 * Class XLSXTest
 *
 * @package Box\Spout\Reader
 */
class XLSXTest extends \PHPUnit_Framework_TestCase
{
    use TestUsingResource;

    /**
     * @return array
     */
    public function dataProviderForTestReadShouldThrowException()
    {
        return [
            ['/path/to/fake/file.xlsx'],
            ['file_with_no_sheets_in_content_types.xlsx'],
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
        $this->getAllRowsForFile($filePath);
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
    public function testReadShouldSkipEmptyRows()
    {
        $allRows = $this->getAllRowsForFile('sheet_with_empty_rows.xlsx');

        $this->assertEquals(2, count($allRows), 'There should be only 2 rows, because the empty row is skipped');

        $expectedRows = [
            ['s1--A1', 's1--B1', 's1--C1', 's1--D1', 's1--E1'],
            ['s1--A3', 's1--B3', 's1--C3', 's1--D3', 's1--E3'],
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
     * @return void
     */
    public function testReadShouldBeProtectedAgainstBillionLaughsAttack()
    {
        $allRows = $this->getAllRowsForFile('billion_laughs_test_file.xlsx');

        $expectedMaxMemoryUsage = 10 * 1024 * 1024; // 10MB
        $this->assertLessThan($expectedMaxMemoryUsage, memory_get_peak_usage(true), 'Entities should not be expanded and therefore consume all the memory.');

        $expectedFirstRow = ['s1--A1', 's1--B1', 's1--C1', 's1--D1', 's1--E1'];
        $this->assertEquals($expectedFirstRow, $allRows[0], 'Entities should be ignored when reading XML files.');
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
     * @param string $fileName
     * @return array All the read rows the given file
     */
    private function getAllRowsForFile($fileName)
    {
        $allRows = [];
        $resourcePath = $this->getResourcePath($fileName);

        $reader = ReaderFactory::create(Type::XLSX);
        $reader->open($resourcePath);

        while ($reader->hasNextSheet()) {
            $reader->nextSheet();

            while ($reader->hasNextRow()) {
                $allRows[] = $reader->nextRow();
            }
        }

        $reader->close();

        return $allRows;
    }
}
