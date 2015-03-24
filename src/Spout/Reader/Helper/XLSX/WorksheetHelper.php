<?php

namespace Box\Spout\Reader\Helper\XLSX;

use Box\Spout\Reader\Internal\XLSX\Worksheet;

/**
 * Class WorksheetHelper
 * This class provides helper functions related to XLSX worksheets
 *
 * @package Box\Spout\Reader\Helper\XLSX
 */
class WorksheetHelper
{
    /** Path of Content_Types XML file inside the XLSX file */
    const CONTENT_TYPES_XML_FILE_PATH = '[Content_Types].xml';

    /** Main namespace for the [Content_Types].xml file */
    const MAIN_NAMESPACE_FOR_CONTENT_TYPES_XML = 'http://schemas.openxmlformats.org/package/2006/content-types';

    /** Value of the Override attribute used in [Content_Types].xml to define worksheets */
    const OVERRIDE_CONTENT_TYPES_ATTRIBUTE = 'application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml';

    /** @var string Path of the XLSX file being read */
    protected $filePath;

    /**
     * @param string $filePath Path of the XLSX file being read
     */
    public function __construct($filePath)
    {
        $this->filePath = $filePath;
    }

    /**
     * Returns the file paths of the worksheet data XML files within the XLSX file.
     * The paths are read from the [Content_Types].xml file.
     *
     * @return Worksheet[] Worksheets within the XLSX file
     */
    public function getWorksheets()
    {
        $worksheets = array();

        $xmlContents = file_get_contents('zip://' . $this->filePath . '#' . self::CONTENT_TYPES_XML_FILE_PATH);

        $contentTypes = new \SimpleXMLElement($xmlContents);
        $contentTypes->registerXPathNamespace('ns', self::MAIN_NAMESPACE_FOR_CONTENT_TYPES_XML);

        // find all nodes defining a worksheet
        $sheetNodes = $contentTypes->xpath('//ns:Override[@ContentType="' . self::OVERRIDE_CONTENT_TYPES_ATTRIBUTE . '"]');

        for ($i = 0; $i < count($sheetNodes); $i++) {
            $sheetNode = $sheetNodes[$i];
            $sheetDataXMLFilePath = (string) $sheetNode->attributes()->PartName;
            $worksheets[] = new Worksheet($i, $sheetDataXMLFilePath);
        }

        return $worksheets;
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
        return ($currentWorksheet === null || ($currentWorksheet->getWorksheetNumber() + 1 < count($allWorksheets)));
    }
}
