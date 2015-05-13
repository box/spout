<?php

namespace Box\Spout\Reader\Helper\XLSX;

use Box\Spout\Reader\Internal\XLSX\Worksheet;
use Box\Spout\Reader\Sheet;

/**
 * Class WorksheetHelper
 * This class provides helper functions related to XLSX worksheets
 *
 * @package Box\Spout\Reader\Helper\XLSX
 */
class WorksheetHelper
{
    /** Extension for XML files */
    const XML_EXTENSION = '.xml';

    /** Paths of XML files relative to the XLSX file root */
    const CONTENT_TYPES_XML_FILE_PATH = '[Content_Types].xml';
    const WORKBOOK_XML_RELS_FILE_PATH = 'xl/_rels/workbook.xml.rels';
    const WORKBOOK_XML_FILE_PATH = 'xl/workbook.xml';

    /** Namespaces for the XML files */
    const MAIN_NAMESPACE_FOR_CONTENT_TYPES_XML = 'http://schemas.openxmlformats.org/package/2006/content-types';
    const MAIN_NAMESPACE_FOR_WORKBOOK_XML_RELS = 'http://schemas.openxmlformats.org/package/2006/relationships';
    const MAIN_NAMESPACE_FOR_WORKBOOK_XML = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

    /** Value of the Override attribute used in [Content_Types].xml to define worksheets */
    const OVERRIDE_CONTENT_TYPES_ATTRIBUTE = 'application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml';

    /** @var string Path of the XLSX file being read */
    protected $filePath;

    /** @var \Box\Spout\Common\Helper\GlobalFunctionsHelper Helper to work with global functions */
    protected $globalFunctionsHelper;

    /** @var \SimpleXMLElement XML element representing the workbook.xml.rels file */
    protected $workbookXMLRelsAsXMLElement;

    /** @var \SimpleXMLElement XML element representing the workbook.xml file */
    protected $workbookXMLAsXMLElement;

    /**
     * @param string $filePath Path of the XLSX file being read
     * @param \Box\Spout\Common\Helper\GlobalFunctionsHelper $globalFunctionsHelper
     */
    public function __construct($filePath, $globalFunctionsHelper)
    {
        $this->filePath = $filePath;
        $this->globalFunctionsHelper = $globalFunctionsHelper;
    }

    /**
     * Returns the file paths of the worksheet data XML files within the XLSX file.
     * The paths are read from the [Content_Types].xml file.
     *
     * @return Worksheet[] Worksheets within the XLSX file
     */
    public function getWorksheets()
    {
        $worksheets = [];

        $contentTypesAsXMLElement = $this->getFileAsXMLElementWithNamespace(
            self::CONTENT_TYPES_XML_FILE_PATH,
            self::MAIN_NAMESPACE_FOR_CONTENT_TYPES_XML
        );

        // find all nodes defining a worksheet
        $sheetNodes = $contentTypesAsXMLElement->xpath('//ns:Override[@ContentType="' . self::OVERRIDE_CONTENT_TYPES_ATTRIBUTE . '"]');

        for ($i = 0; $i < count($sheetNodes); $i++) {
            $sheetNode = $sheetNodes[$i];
            $sheetDataXMLFilePath = (string) $sheetNode->attributes()->PartName;

            $sheet = $this->getSheet($sheetDataXMLFilePath, $i);
            $worksheets[] = new Worksheet($sheet, $i, $sheetDataXMLFilePath);
        }

        return $worksheets;
    }

    /**
     * Returns an instance of a sheet, given the path of its data XML file.
     * We first look at "xl/_rels/workbook.xml.rels" to find the relationship ID of the sheet.
     * Then we look at "xl/worbook.xml" to find the sheet entry associated to the found ID.
     * The entry contains the ID and name of the sheet.
     *
     * If this piece of data can't be found by parsing the different XML files, the ID will default
     * to the sheet index, based on order in [Content_Types].xml. Similarly, the sheet's name will
     * default to the data sheet XML file name ("xl/worksheets/sheet2.xml" => "sheet2").
     *
     * @param string $sheetDataXMLFilePath Path of the sheet data XML file as in [Content_Types].xml
     * @param int $sheetIndexZeroBased Index of the sheet, based on order in [Content_Types].xml (zero-based)
     * @return \Box\Spout\Reader\Sheet Sheet instance
     */
    protected function getSheet($sheetDataXMLFilePath, $sheetIndexZeroBased)
    {
        $sheetId = $sheetIndexZeroBased + 1;
        $sheetName = $this->getDefaultSheetName($sheetDataXMLFilePath);

        /*
         * In [Content_Types].xml, the path is "/xl/worksheets/sheet1.xml"
         * In workbook.xml.rels, it is only "worksheets/sheet1.xml"
         */
        $sheetDataXMLFilePathInWorkbookXMLRels = ltrim($sheetDataXMLFilePath, '/xl/');

        // find the node associated to the given file path
        $workbookXMLResElement = $this->getWorkbookXMLRelsAsXMLElement();
        $relationshipNodes = $workbookXMLResElement->xpath('//ns:Relationship[@Target="' . $sheetDataXMLFilePathInWorkbookXMLRels . '"]');

        if (count($relationshipNodes) === 1) {
            $relationshipNode = $relationshipNodes[0];
            $sheetId = (string) $relationshipNode->attributes()->Id;

            $workbookXMLElement = $this->getWorkbookXMLAsXMLElement();
            $sheetNodes = $workbookXMLElement->xpath('//ns:sheet[@r:id="' . $sheetId . '"]');

            if (count($sheetNodes) === 1) {
                $sheetNode = $sheetNodes[0];
                $sheetId = (int) $sheetNode->attributes()->sheetId;
                $escapedSheetName = (string) $sheetNode->attributes()->name;

                $escaper = new \Box\Spout\Common\Escaper\XLSX();
                $sheetName = $escaper->unescape($escapedSheetName);
            }
        }

        return new Sheet($sheetId, $sheetIndexZeroBased, $sheetName);
    }

    /**
     * Returns the default name of the sheet whose data is located
     * at the given path.
     *
     * @param $sheetDataXMLFilePath
     * @return string The default sheet name
     */
    protected function getDefaultSheetName($sheetDataXMLFilePath)
    {
        return $this->globalFunctionsHelper->basename($sheetDataXMLFilePath, self::XML_EXTENSION);
    }

    /**
     * Returns a representation of the workbook.xml.rels file, ready to be parsed.
     * The returned value is cached.
     *
     * @return \SimpleXMLElement XML element representating the workbook.xml.rels file
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
     * @return \SimpleXMLElement XML element representating the workbook.xml.rels file
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
     * @return \SimpleXMLElement The XML element representing the file
     */
    protected function getFileAsXMLElementWithNamespace($xmlFilePath, $mainNamespace)
    {
        $normalizedXmlFilePath = str_replace('/', DIRECTORY_SEPARATOR, $xmlFilePath);
        $xmlContents = $this->globalFunctionsHelper->file_get_contents('zip://' . $this->filePath . '#' . $normalizedXmlFilePath);

        $xmlElement = new \SimpleXMLElement($xmlContents);
        $xmlElement->registerXPathNamespace('ns', $mainNamespace);

        return $xmlElement;
    }

    /**
     * Returns whether another worksheet exists after the current worksheet.
     * The order is determined by the order of appearance in the [Content_Types].xml file.
     *
     * @param Worksheet|null $currentWorksheet The worksheet being currently read or null if reading has not started yet
     * @param Worksheet[] $allWorksheets A list of all worksheets in the XLSX file. Must contain at least one worksheet
     * @return bool Whether another worksheet exists after the current sheet
     */
    public function hasNextWorksheet($currentWorksheet, $allWorksheets)
    {
        return ($currentWorksheet === null || ($currentWorksheet->getWorksheetIndex() + 1 < count($allWorksheets)));
    }
}
