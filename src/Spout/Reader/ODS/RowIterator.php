<?php

namespace Box\Spout\Reader\ODS;

use Box\Spout\Common\Exception\IOException;
use Box\Spout\Reader\Exception\IteratorNotRewindableException;
use Box\Spout\Reader\Exception\XMLProcessingException;
use Box\Spout\Reader\IteratorInterface;
use Box\Spout\Reader\ODS\Helper\CellValueFormatter;
use Box\Spout\Reader\Wrapper\XMLReader;
use Box\Spout\Reader\ReaderOptions;

/**
 * Class RowIterator
 *
 * @package Box\Spout\Reader\ODS
 */
class RowIterator implements IteratorInterface
{
    /** Definition of XML nodes names used to parse data */
    const XML_NODE_TABLE = 'table:table';
    const XML_NODE_ROW = 'table:table-row';
    const XML_NODE_CELL = 'table:table-cell';
    const MAX_COLUMNS_EXCEL = 16384;
    const MAX_ROWS_EXCEL = 1048576;

    /** Definition of XML attribute used to parse data */
    const XML_ATTRIBUTE_NUM_COLUMNS_REPEATED = 'table:number-columns-repeated';

    /** Definition of XML attribute used to parse data */
    const XML_ATTRIBUTE_NUM_ROWS_REPEATED = 'table:number-rows-repeated';

    /** @var \Box\Spout\Reader\Wrapper\XMLReader The XMLReader object that will help read sheet's XML data */
    protected $xmlReader;

    /** @var Helper\CellValueFormatter Helper to format cell values */
    protected $cellValueFormatter;

    /** @var bool Whether the iterator has already been rewound once */
    protected $hasAlreadyBeenRewound = false;

    /** @var int Key for iterator */
    protected $rowIndex = 0;

    /** @var array Buffer used to store the row data, while checking if there are more rows to read */
    protected $rowDataBuffer = [];

    /** @var bool Indicates whether all rows have been read */
    protected $hasReachedEndOfFile = false;

    /** @var \Box\Spout\Reader\ReaderOptions */
    protected $readerOptions;

    /**
     * @param XMLReader $xmlReader XML Reader, positioned on the "<table:table>" element
     * @param \Box\Spout\Reader\ReaderOptions $readerOptions
     */
    public function __construct($xmlReader, ReaderOptions $readerOptions)
    {
        $this->xmlReader = $xmlReader;
        $this->readerOptions = $readerOptions;
        $this->cellValueFormatter = new CellValueFormatter($readerOptions->shouldFormatDates());
    }

    /**
     * Rewind the Iterator to the first element.
     * NOTE: It can only be done once, as it is not possible to read an XML file backwards.
     * @link http://php.net/manual/en/iterator.rewind.php
     *
     * @return void
     * @throws \Box\Spout\Reader\Exception\IteratorNotRewindableException If the iterator is rewound more than once
     */
    public function rewind()
    {
        // Because sheet and row data is located in the file, we can't rewind both the
        // sheet iterator and the row iterator, as XML file cannot be read backwards.
        // Therefore, rewinding the row iterator has been disabled.
        if ($this->hasAlreadyBeenRewound) {
            throw new IteratorNotRewindableException();
        }

        $this->hasAlreadyBeenRewound = true;
        $this->rowIndex = 0;
        $this->rowDataBuffer = [];
        $this->hasReachedEndOfFile = false;

        $this->next();
    }

    /**
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     *
     * @return boolean
     */
    public function valid()
    {
        return (!$this->hasReachedEndOfFile);
    }

    /**
     * Move forward to next element. Empty rows can be skipped.
     * @link http://php.net/manual/en/iterator.next.php
     *
     * @return void
     * @throws \Box\Spout\Reader\Exception\SharedStringNotFoundException If a shared string was not found
     * @throws \Box\Spout\Common\Exception\IOException If unable to read the sheet data XML
     */
    public function next()
    {
        $prevRow = null;

        if (count($this->rowDataBuffer) > 1) {
            array_shift($this->rowDataBuffer);
            $this->rowIndex++;

            return;
        } else {
            $prevRow = $this->current();
            $this->rowDataBuffer = [];
        }

        $rowData = [];
        $cellValue = null;
        $numRowsRepeated = 0;
        $numColumnsRepeated = 1;
        $numCellsRead = 0;
        $hasAlreadyReadOneCell = false;

        try {
            while ($this->xmlReader->read()) {
                if ($this->xmlReader->isPositionedOnStartingNode(self::XML_NODE_ROW)) {
                    // Start of a row description
                    $this->rowIndex++;

                    $numRowsRepeated = $this->getNumRowsRepeatedForCurrentNode();

                } elseif ($this->xmlReader->isPositionedOnStartingNode(self::XML_NODE_CELL)) {
                    // Start of a cell description
                    $currentNumColumnsRepeated = $this->getNumColumnsRepeatedForCurrentNode();

                    $node = $this->xmlReader->expand();
                    $currentCellValue = $this->getCellValue($node);

                    // process cell N only after having read cell N+1 (see below why)
                    if ($hasAlreadyReadOneCell) {
                        for ($i = 0; $i < $numColumnsRepeated; $i++) {
                            $rowData[] = $cellValue;
                        }
                    }

                    $cellValue = $currentCellValue;
                    $numColumnsRepeated = $currentNumColumnsRepeated;

                    $numCellsRead++;
                    $hasAlreadyReadOneCell = true;

                } elseif ($this->xmlReader->isPositionedOnEndingNode(self::XML_NODE_ROW)) {
                    // End of the row description
                    $isEmptyRow = ($numCellsRead <= 1 && $this->isEmptyCellValue($cellValue));

                    if (!$isEmptyRow) {
                        // Only add the value if the last read cell is not a trailing empty cell repeater in Excel.
                        // The current count of read columns is determined by counting the values in $rowData.
                        // This is to avoid creating a lot of empty cells, as Excel adds a last empty "<table:table-cell>"
                        // with a number-columns-repeated value equals to the number of (supported columns - used columns).
                        // In Excel, the number of supported columns is 16384, but we don't want to returns rows with
                        // always 16384 cells.
                        if ((count($rowData) + $numColumnsRepeated) !== self::MAX_COLUMNS_EXCEL) {
                            for ($i = 0; $i < $numColumnsRepeated; $i++) {
                                $rowData[] = $cellValue;
                            }
                        }
                    } elseif ($this->readerOptions->shouldPreserveEmptyRows()) {
                        // Take number of cells from the previously read line.
                        $rowData = empty($prevRow) ? [] : array_fill(0, count($prevRow), '');
                    } else {
                        return $this->next();
                    }

                    // see above, now check number of rows...
                    if ($this->rowIndex - 1 + $numRowsRepeated >= self::MAX_ROWS_EXCEL) {
                        $numRowsRepeated = 0;
                        $this->hasReachedEndOfFile = true;
                    }
                    break;

                } elseif ($this->xmlReader->isPositionedOnEndingNode(self::XML_NODE_TABLE)) {
                    // The closing "</table:table>" marks the end of the file
                    $this->hasReachedEndOfFile = true;
                    break;
                }
            }

        } catch (XMLProcessingException $exception) {
            throw new IOException("The sheet's data cannot be read. [{$exception->getMessage()}]");
        }

        for ($i = 0; $i < $numRowsRepeated; ++$i) {
            $this->rowDataBuffer[] = $rowData;
        }
    }

    /**
     * @return int The value of "table:number-columns-repeated" attribute of the current node, or 1 if attribute missing
     */
    protected function getNumColumnsRepeatedForCurrentNode()
    {
        $numColumnsRepeated = $this->xmlReader->getAttribute(self::XML_ATTRIBUTE_NUM_COLUMNS_REPEATED);
        return ($numColumnsRepeated !== null) ? intval($numColumnsRepeated) : 1;
    }

    /**
     * @return int The value of "table:number-rows-repeated" attribute of the current node, or 1 if attribute missing
     */
    protected function getNumRowsRepeatedForCurrentNode()
    {
        $numRowsRepeated = $this->xmlReader->getAttribute(self::XML_ATTRIBUTE_NUM_ROWS_REPEATED);
        return ($numRowsRepeated !== null) ? intval($numRowsRepeated) : 1;
    }

    /**
     * Returns the (unescaped) correctly marshalled, cell value associated to the given XML node.
     *
     * @param \DOMNode $node
     * @return string|int|float|bool|\DateTime|\DateInterval|null The value associated with the cell, empty string if cell's type is void/undefined, null on error
     */
    protected function getCellValue($node)
    {
        return $this->cellValueFormatter->extractAndFormatNodeValue($node);
    }

    /**
     * empty() replacement that honours 0 as a valid value
     *
     * @param string|int|float|bool|\DateTime|\DateInterval|null $value The cell value
     * @return bool
     */
    protected function isEmptyCellValue($value)
    {
        return (!isset($value) || trim($value) === '');
    }

    /**
     * Return the current element, from the buffer.
     * @link http://php.net/manual/en/iterator.current.php
     *
     * @return array|null
     */
    public function current()
    {
        return isset($this->rowDataBuffer[0]) ? $this->rowDataBuffer[0] : null;
    }

    /**
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     *
     * @return int
     */
    public function key()
    {
        return $this->rowIndex;
    }

    /**
     * Cleans up what was created to iterate over the object.
     *
     * @return void
     */
    public function end()
    {
        $this->xmlReader->close();
    }
}
