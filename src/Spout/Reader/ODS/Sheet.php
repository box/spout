<?php

namespace Box\Spout\Reader\ODS;

use Box\Spout\Reader\SheetInterface;
use Box\Spout\Reader\Wrapper\XMLReader;

/**
 * Class Sheet
 * Represents a sheet within a ODS file
 *
 * @package Box\Spout\Reader\ODS
 */
class Sheet implements SheetInterface
{
    /** @var \Box\Spout\Reader\ODS\RowIterator To iterate over sheet's rows */
    protected $rowIterator;

    /** @var int ID of the sheet */
    protected $id;

    /** @var int Index of the sheet, based on order in the workbook (zero-based) */
    protected $index;

    /** @var string Name of the sheet */
    protected $name;

    /**
     * @param XMLReader $xmlReader XML Reader, positioned on the "<table:table>" element
     * @param bool $shouldFormatDates Whether date/time values should be returned as PHP objects or be formatted as strings
     * @param bool $shouldPreserveEmptyRows Whether empty rows should be returned or skipped
     * @param int $sheetIndex Index of the sheet, based on order in the workbook (zero-based)
     * @param string $sheetName Name of the sheet
     */
    public function __construct($xmlReader, $shouldFormatDates, $shouldPreserveEmptyRows, $sheetIndex, $sheetName)
    {
        $this->rowIterator = new RowIterator($xmlReader, $shouldFormatDates, $shouldPreserveEmptyRows);
        $this->index = $sheetIndex;
        $this->name = $sheetName;
    }

    /**
     * @api
     * @return \Box\Spout\Reader\ODS\RowIterator
     */
    public function getRowIterator()
    {
        return $this->rowIterator;
    }

    /**
     * @api
     * @return int Index of the sheet, based on order in the workbook (zero-based)
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * @api
     * @return string Name of the sheet
     */
    public function getName()
    {
        return $this->name;
    }
}
