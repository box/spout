<?php

namespace Box\Spout\Reader\ODS;

use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Box\Spout\TestUsingResource;
use PHPUnit\Framework\TestCase;

/**
 * Class ReaderPerfTest
 * Performance tests for ODS Reader
 */
class ReaderPerfTest extends Testcase
{
    use TestUsingResource;

    /**
     * 1 million rows (each row containing 3 cells) should be read
     * in less than 10 minutes and the execution should not require
     * more than 1MB of memory
     *
     * @group perf-tests
     *
     * @return void
     */
    public function testPerfWhenReadingOneMillionRowsODS()
    {
        // getting current memory peak to avoid taking into account the memory used by PHPUnit
        $beforeMemoryPeakUsage = memory_get_peak_usage(true);

        $expectedMaxExecutionTime = 600; // 10 minutes in seconds
        $expectedMaxMemoryPeakUsage = 1 * 1024 * 1024; // 1MB in bytes
        $startTime = time();

        $fileName = 'ods_with_one_million_rows.ods';
        $resourcePath = $this->getResourcePath($fileName);

        $reader = ReaderEntityFactory::createODSReader();
        $reader->open($resourcePath);

        $numReadRows = 0;

        /** @var Sheet $sheet */
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $numReadRows++;
            }
        }

        $reader->close();

        $expectedNumRows = 1000000;
        $this->assertEquals($expectedNumRows, $numReadRows, "$expectedNumRows rows should have been read");

        $executionTime = time() - $startTime;
        $this->assertTrue($executionTime < $expectedMaxExecutionTime, "Reading 1 million rows should take less than $expectedMaxExecutionTime seconds (took $executionTime seconds)");

        $memoryPeakUsage = memory_get_peak_usage(true) - $beforeMemoryPeakUsage;
        $this->assertTrue($memoryPeakUsage < $expectedMaxMemoryPeakUsage, 'Reading 1 million rows should require less than ' . ($expectedMaxMemoryPeakUsage / 1024 / 1024) . ' MB of memory (required ' . round($memoryPeakUsage / 1024 / 1024, 2) . ' MB)');
    }
}
