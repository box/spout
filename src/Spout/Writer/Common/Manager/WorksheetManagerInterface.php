<?php

namespace Box\Spout\Writer\Common\Manager;

use Box\Spout\Writer\Common\Entity\Row;
use Box\Spout\Writer\Common\Entity\Worksheet;

/**
 * Interface WorksheetManagerInterface
 * Inteface for worksheet managers, providing the generic interfaces to work with worksheets.
 *
 * @package Box\Spout\Writer\Common\Manager
 */
interface WorksheetManagerInterface
{
    /**
     * Adds a row to the worksheet.
     *
     * @param Worksheet $worksheet The worksheet to add the row to
     * @param Row $row The row to be added
     * @return void
     * @throws \Box\Spout\Common\Exception\IOException If the data cannot be written
     * @throws \Box\Spout\Common\Exception\InvalidArgumentException If a cell value's type is not supported
     */
    public function addRow(Worksheet $worksheet, Row $row);

    /**
     * Prepares the worksheet to accept data
     *
     * @param Worksheet $worksheet The worksheet to start
     * @return void
     * @throws \Box\Spout\Common\Exception\IOException If the sheet data file cannot be opened for writing
     */
    public function startSheet(Worksheet $worksheet);

    /**
     * Closes the worksheet
     *
     * @param Worksheet $worksheet
     * @return void
     */
    public function close(Worksheet $worksheet);
}
