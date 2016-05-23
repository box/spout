<?php

namespace Box\Spout\Reader\XLSX\Helper;

use Box\Spout\Reader\Wrapper\SimpleXMLElement;
use Box\Spout\Reader\XLSX\Sheet;

/**
 * Class SheetHelper
 * This class provides helper functions related to XLSX sheets
 *
 * @package Box\Spout\Reader\XLSX\Helper
 */
class SheetHelper
{
    /** Paths of XML files relative to the XLSX file root */
    const WORKBOOK_XML_RELS_FILE_PATH = 'xl/_rels/workbook.xml.rels';
    const WORKBOOK_XML_FILE_PATH = 'xl/workbook.xml';

    /** Namespaces for the XML files */
    const MAIN_NAMESPACE_FOR_WORKBOOK_XML_RELS = 'http://schemas.openxmlformats.org/package/2006/relationships';
    const MAIN_NAMESPACE_FOR_WORKBOOK_XML = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

    /** @var string Path of the XLSX file being read */
    protected $filePath;

    /** @var \Box\Spout\Reader\XLSX\Helper\SharedStringsHelper Helper to work with shared strings */
    protected $sharedStringsHelper;

    /** @var \Box\Spout\Common\Helper\GlobalFunctionsHelper Helper to work with global functions */
    protected $globalFunctionsHelper;

    /** @var bool Whether date/time values should be returned as PHP objects or be formatted as strings */
    protected $shouldFormatDates;

    /** @var \Box\Spout\Reader\Wrapper\SimpleXMLElement XML element representing the workbook.xml.rels file */
    protected $workbookXMLRelsAsXMLElement;

    /** @var \Box\Spout\Reader\Wrapper\SimpleXMLElement XML element representing the workbook.xml file */
    protected $workbookXMLAsXMLElement;

    /**
     * @param string $filePath Path of the XLSX file being read
     * @param \Box\Spout\Reader\XLSX\Helper\SharedStringsHelper Helper to work with shared strings
     * @param \Box\Spout\Common\Helper\GlobalFunctionsHelper $globalFunctionsHelper
     * @param bool $shouldFormatDates Whether date/time values should be returned as PHP objects or be formatted as strings
     */
    public function __construct($filePath, $sharedStringsHelper, $globalFunctionsHelper, $shouldFormatDates)
    {
        $this->filePath = $filePath;
        $this->sharedStringsHelper = $sharedStringsHelper;
        $this->globalFunctionsHelper = $globalFunctionsHelper;
        $this->shouldFormatDates = $shouldFormatDates;
    }

    /**
     * Returns the sheets metadata of the file located at the previously given file path.
     * The paths to the sheets' data are read from the [Content_Types].xml file.
     *
     * @return Sheet[] Sheets within the XLSX file
     */
    public function getSheets()
    {
        $sheets = [];

        // Starting from "workbook.xml" as this file is the source of truth for the sheets order
        $workbookXMLElement = $this->getWorkbookXMLAsXMLElement();
        $sheetNodes = $workbookXMLElement->xpath('//ns:sheet');

        foreach ($sheetNodes as $sheetIndex => $sheetNode) {
            $sheets[] = $this->getSheetFromSheetXMLNode($sheetNode, $sheetIndex);
        }

        return $sheets;
    }

    /**
     * Returns an instance of a sheet, given the XML node describing the sheet - from "workbook.xml".
     * We can find the XML file path describing the sheet inside "workbook.xml.res", by mapping with the sheet ID
     * ("r:id" in "workbook.xml", "Id" in "workbook.xml.res").
     *
     * @param \Box\Spout\Reader\Wrapper\SimpleXMLElement $sheetNode XML Node describing the sheet, as defined in "workbook.xml"
     * @param int $sheetIndexZeroBased Index of the sheet, based on order of appearance in the workbook (zero-based)
     * @return \Box\Spout\Reader\XLSX\Sheet Sheet instance
     */
    protected function getSheetFromSheetXMLNode($sheetNode, $sheetIndexZeroBased)
    {
        // To retrieve namespaced attributes, some versions of LibXML will accept prefixing the attribute
        // with the namespace directly (tested on LibXML 2.9.3). For older versions (tested on LibXML 2.7.8),
        // attributes need to be retrieved without the namespace hint.
        $sheetId = $sheetNode->getAttribute('r:id');
        if ($sheetId === null) {
            $sheetId = $sheetNode->getAttribute('id');
        }

        $escapedSheetName = $sheetNode->getAttribute('name');

        /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
        $escaper = new \Box\Spout\Common\Escaper\XLSX();
        $sheetName = $escaper->unescape($escapedSheetName);

        // find the file path of the sheet, by looking at the "workbook.xml.res" file
        $workbookXMLResElement = $this->getWorkbookXMLRelsAsXMLElement();
        $relationshipNodes = $workbookXMLResElement->xpath('//ns:Relationship[@Id="' . $sheetId . '"]');
        $relationshipNode = $relationshipNodes[0];

        // In workbook.xml.rels, it is only "worksheets/sheet1.xml"
        // In [Content_Types].xml, the path is "/xl/worksheets/sheet1.xml"
        $sheetDataXMLFilePath = '/xl/' . $relationshipNode->getAttribute('Target');

        return new Sheet($this->filePath, $sheetDataXMLFilePath, $this->sharedStringsHelper, $this->shouldFormatDates, $sheetIndexZeroBased, $sheetName);
    }

    /**
     * Returns a representation of the workbook.xml.rels file, ready to be parsed.
     * The returned value is cached.
     *
     * @return \Box\Spout\Reader\Wrapper\SimpleXMLElement XML element representating the workbook.xml.rels file
     */
    protected function getWorkbookXMLRelsAsXMLElement()
    {
        if (!$this->workbookXMLRelsAsXMLElement) {
            $this->workbookXMLRelsAsXMLElement = $this->getFileAsXMLElementWithNamespace(
                self::WORKBOOK_XML_RELS_FILE_PATH,
                self::MAIN_NAMESPACE_FOR_WORKBOOK_XML_RELS
            );
        }

        return $this->workbookXMLRelsAsXMLElement;
    }

    /**
     * Returns a representation of the workbook.xml file, ready to be parsed.
     * The returned value is cached.
     *
     * @return \Box\Spout\Reader\Wrapper\SimpleXMLElement XML element representating the workbook.xml.rels file
     */
    protected function getWorkbookXMLAsXMLElement()
    {
        if (!$this->workbookXMLAsXMLElement) {
            $this->workbookXMLAsXMLElement = $this->getFileAsXMLElementWithNamespace(
                self::WORKBOOK_XML_FILE_PATH,
                self::MAIN_NAMESPACE_FOR_WORKBOOK_XML
            );
        }

        return $this->workbookXMLAsXMLElement;
    }

    /**
     * Loads the contents of the given file in an XML parser and register the given XPath namespace.
     *
     * @param string $xmlFilePath The path of the XML file inside the XLSX file
     * @param string $mainNamespace The main XPath namespace to register
     * @return \Box\Spout\Reader\Wrapper\SimpleXMLElement The XML element representing the file
     */
    protected function getFileAsXMLElementWithNamespace($xmlFilePath, $mainNamespace)
    {
        $xmlContents = $this->globalFunctionsHelper->file_get_contents('zip://' . $this->filePath . '#' . $xmlFilePath);

        $xmlElement = new SimpleXMLElement($xmlContents);
        $xmlElement->registerXPathNamespace('ns', $mainNamespace);

        return $xmlElement;
    }
}
