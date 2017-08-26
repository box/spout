<?php

namespace Box\Spout\Reader\XLSX\Creator;

use Box\Spout\Reader\Common\Creator\EntityFactoryInterface;
use Box\Spout\Reader\Common\XMLProcessor;
use Box\Spout\Reader\XLSX\Helper\SharedStringsHelper;
use Box\Spout\Reader\XLSX\RowIterator;
use Box\Spout\Reader\XLSX\Sheet;
use Box\Spout\Reader\XLSX\SheetIterator;
use Box\Spout\Reader\Wrapper\XMLReader;

/**
 * Class EntityFactory
 * Factory to create entities
 *
 * @package Box\Spout\Reader\XLSX\Creator
 */
class EntityFactory implements EntityFactoryInterface
{
    /** @var HelperFactory */
    private $helperFactory;

    /**
     * @param HelperFactory $helperFactory
     */
    public function __construct(HelperFactory $helperFactory)
    {
        $this->helperFactory = $helperFactory;
    }

    /**
     * @param string $filePath Path of the file to be read
     * @param \Box\Spout\Common\Manager\OptionsManagerInterface $optionsManager Reader's options manager
     * @param SharedStringsHelper $sharedStringsHelper Helper to work with shared strings
     * @param \Box\Spout\Common\Helper\GlobalFunctionsHelper $globalFunctionsHelper
     * @return SheetIterator
     */
    public function createSheetIterator($filePath, $optionsManager, $sharedStringsHelper, $globalFunctionsHelper)
    {
        return new SheetIterator(
            $filePath,
            $optionsManager,
            $sharedStringsHelper,
            $globalFunctionsHelper,
            $this,
            $this->helperFactory
        );
    }

    /**
     * @param string $filePath Path of the XLSX file being read
     * @param string $sheetDataXMLFilePath Path of the sheet data XML file as in [Content_Types].xml
     * @param int $sheetIndex Index of the sheet, based on order in the workbook (zero-based)
     * @param string $sheetName Name of the sheet
     * @param bool $isSheetActive Whether the sheet was defined as active
     * @param \Box\Spout\Common\Manager\OptionsManagerInterface $optionsManager Reader's options manager
     * @param SharedStringsHelper $sharedStringsHelper Helper to work with shared strings
     * @return Sheet
     */
    public function createSheet(
        $filePath,
        $sheetDataXMLFilePath,
        $sheetIndex,
        $sheetName,
        $isSheetActive,
        $optionsManager,
        $sharedStringsHelper)
    {
        return new Sheet(
            $filePath,
            $sheetDataXMLFilePath,
            $sheetIndex,
            $sheetName,
            $isSheetActive,
            $optionsManager,
            $sharedStringsHelper,
            $this
        );
    }

    /**
     * @param string $filePath Path of the XLSX file being read
     * @param string $sheetDataXMLFilePath Path of the sheet data XML file as in [Content_Types].xml
     * @param \Box\Spout\Common\Manager\OptionsManagerInterface $optionsManager Reader's options manager
     * @param SharedStringsHelper $sharedStringsHelper Helper to work with shared strings
     * @return RowIterator
     */
    public function createRowIterator($filePath, $sheetDataXMLFilePath, $optionsManager, $sharedStringsHelper)
    {
        return new RowIterator($filePath, $sheetDataXMLFilePath, $optionsManager, $sharedStringsHelper, $this, $this->helperFactory);
    }

    /**
     * @return \ZipArchive
     */
    public function createZipArchive()
    {
        return new \ZipArchive();
    }

    /**
     * @return XMLReader
     */
    public function createXMLReader()
    {
        return new XMLReader();
    }

    /**
     * @param $xmlReader
     * @return XMLProcessor
     */
    public function createXMLProcessor($xmlReader)
    {
        return new XMLProcessor($xmlReader);
    }
}
