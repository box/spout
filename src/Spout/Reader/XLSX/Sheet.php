<?php

namespace Box\Spout\Reader\XLSX;

use Box\Spout\Reader\SheetInterface;

/**
 * Class Sheet
 * Represents a sheet within a XLSX file
 *
 * @package Box\Spout\Reader\XLSX
 */
class Sheet implements SheetInterface
{
    /** @var RowIterator To iterate over sheet's rows */
    protected $rowIterator;

    /** @var int ID of the sheet */
    protected $id;

    /** @var int Index of the sheet, based on order of creation (zero-based) */
    protected $index;

    /** @var string Name of the sheet */
    protected $name;

    /**
     * @param string $filePath Path of the XLSX file being read
     * @param string $sheetDataXMLFilePath Path of the sheet data XML file as in [Content_Types].xml
     * @param Helper\SharedStringsHelper Helper to work with shared strings
     * @param int $sheetId ID of the sheet
     * @param int $sheetIndex Index of the sheet, based on order of creation (zero-based)
     * @param string $sheetName Name of the sheet
     */
    public function __construct($filePath, $sheetDataXMLFilePath, $sharedStringsHelper, $sheetId, $sheetIndex, $sheetName)
    {
        $this->rowIterator = new RowIterator($filePath, $sheetDataXMLFilePath, $sharedStringsHelper);
        $this->id = $sheetId;
        $this->index = $sheetIndex;
        $this->name = $sheetName;
    }

    /**
     * @return RowIterator
     */
    public function getRowIterator()
    {
        return $this->rowIterator;
    }

    /**
     * @return int ID of the sheet
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int Index of the sheet, based on order of creation (zero-based)
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * @return string Name of the sheet
     */
    public function getName()
    {
        return $this->name;
    }
}
