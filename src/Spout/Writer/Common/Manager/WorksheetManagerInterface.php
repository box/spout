<?php

namespace Box\Spout\Writer\Common\Manager;

use Box\Spout\Common\Entity\Row;
use Box\Spout\Writer\Common\Entity\Worksheet;

/**
 * Interface WorksheetManagerInterface
 * Inteface for worksheet managers, providing the generic interfaces to work with worksheets.
 */
interface WorksheetManagerInterface
{
    /**
     * @param float|null $width
     */
    public function setDefaultColumnWidth($width);

    /**
     * @param float|null $height
     */
    public function setDefaultRowHeight($height);

    /**
     * @param float $width
     * @param array $columns One or more columns with this width
     */
    public function setColumnWidth(float $width, ...$columns);

    /**
     * @param float $width The width to set
     * @param int $start First column index of the range
     * @param int $end Last column index of the range
     */
    public function setColumnWidthForRange(float $width, int $start, int $end);

    /**
     * Adds a row to the worksheet.
     *
     * @param Worksheet $worksheet The worksheet to add the row to
     * @param Row $row The row to be added
     * @throws \Box\Spout\Common\Exception\IOException If the data cannot be written
     * @throws \Box\Spout\Common\Exception\InvalidArgumentException If a cell value's type is not supported
     * @return void
     */
    public function addRow(Worksheet $worksheet, Row $row);

    /**
     * Prepares the worksheet to accept data
     *
     * @param Worksheet $worksheet The worksheet to start
     * @throws \Box\Spout\Common\Exception\IOException If the sheet data file cannot be opened for writing
     * @return void
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
