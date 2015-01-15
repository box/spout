<?php

namespace Box\Spout\Writer\Internal\XLSX;

use Box\Spout\Common\Exception\IOException;
use Box\Spout\Writer\Helper\XLSX\CellHelper;

/**
 * Class Worksheet
 * Represents a worksheet within a XLSX file. The difference with the Sheet object is
 * that this class provides an interface to write data
 *
 * @package Box\Spout\Writer\Internal\XLSX
 */
class Worksheet
{
    const SHEET_XML_FILE_HEADER = <<<EOD
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
EOD;

    /** @var \Box\Spout\Writer\Sheet The "external" sheet */
    protected $externalSheet;

    /** @var string Path to the XML file that will contain the sheet data */
    protected $worksheetFilePath;

    /** @var \Box\Spout\Writer\Helper\XLSX\SharedStringsHelper Helper to write shared strings */
    protected $sharedStringsHelper;

    /** @var bool Whether inline or shared strings should be used */
    protected $shouldUseInlineStrings;

    /** @var \Box\Spout\Common\Escaper\XLSX Strings escaper */
    protected $stringsEscaper;

    /** @var Resource Pointer to the sheet data file (e.g. xl/worksheets/sheet1.xml) */
    protected $sheetFilePointer;

    /** @var int */
    protected $lastWrittenRowIndex = 0;

    /**
     * @param \Box\Spout\Writer\Sheet $externalSheet The associated "external" sheet
     * @param string $tempFolder Temporary folder where the files to create the XLSX will be stored
     * @param bool $shouldUseInlineStrings Whether inline or shared strings should be used
     * @throws \Box\Spout\Common\Exception\IOException If the sheet data file cannot be opened for writing
     */
    public function __construct($externalSheet, $worksheetFilesFolder, $sharedStringsHelper, $shouldUseInlineStrings)
    {
        $this->externalSheet = $externalSheet;
        $this->sharedStringsHelper = $sharedStringsHelper;
        $this->shouldUseInlineStrings = $shouldUseInlineStrings;

        $this->stringsEscaper = new \Box\Spout\Common\Escaper\XLSX();

        $this->worksheetFilePath = $worksheetFilesFolder . DIRECTORY_SEPARATOR . strtolower($this->externalSheet->getName()) . '.xml';
        $this->startSheet();
    }

    /**
     * Prepares the worksheet to accept data
     *
     * @return void
     * @throws \Box\Spout\Common\Exception\IOException If the sheet data file cannot be opened for writing
     */
    protected function startSheet()
    {
        $this->sheetFilePointer = fopen($this->worksheetFilePath, 'w');
        $this->throwIfSheetFilePointerIsNotAvailable();

        fwrite($this->sheetFilePointer, self::SHEET_XML_FILE_HEADER . PHP_EOL);
        fwrite($this->sheetFilePointer, '    <sheetData>' . PHP_EOL);
    }

    /**
     * @return \Box\Spout\Writer\Sheet The "external" sheet
     */
    public function getExternalSheet()
    {
        return $this->externalSheet;
    }

    /**
     * @return int The index of the last written row
     */
    public function getLastWrittenRowIndex()
    {
        return $this->lastWrittenRowIndex;
    }

    /**
     * @return int The ID of the worksheet
     */
    public function getId()
    {
        // sheet number is zero-based, while ID is 1-based
        return $this->externalSheet->getSheetNumber() + 1;
    }

    /**
     * Checks if the book has been created. Throws an exception if not created yet.
     *
     * @return void
     * @throws \Box\Spout\Common\Exception\IOException If the sheet data file cannot be opened for writing
     */
    protected function throwIfSheetFilePointerIsNotAvailable()
    {
        if (!$this->sheetFilePointer) {
            throw new IOException('Unable to open sheet for writing.');
        }
    }

    /**
     * Adds data to the worksheet.
     *
     * @param array $dataRow Array containing data to be written.
     *          Example $dataRow = ['data1', 1234, null, '', 'data5'];
     * @return void
     * @throws \Box\Spout\Common\Exception\IOException If the data cannot be written
     */
    public function addRow($dataRow)
    {
        $cellNumber = 0;
        $rowIndex = $this->lastWrittenRowIndex + 1;
        $numCells = count($dataRow);

        $data = '        <row r="' . $rowIndex . '" spans="1:' . $numCells . '">' . PHP_EOL;

        foreach($dataRow as $cellValue) {
            $columnIndex = CellHelper::getCellIndexFromColumnIndex($cellNumber);
            $data .= '            <c r="' . $columnIndex . $rowIndex . '"';

            if (empty($cellValue)) {
                $data .= '/>' . PHP_EOL;
            } else {
                if (is_numeric($cellValue)) {
                    $data .= '><v>' . $cellValue . '</v></c>' . PHP_EOL;
                } else {
                    if ($this->shouldUseInlineStrings) {
                        $data .= ' t="inlineStr"><is><t>' . $this->stringsEscaper->escape($cellValue) . '</t></is></c>' . PHP_EOL;
                    } else {
                        $sharedStringId = $this->sharedStringsHelper->writeString($cellValue);
                        $data .= ' t="s"><v>' . $sharedStringId . '</v></c>' . PHP_EOL;
                    }
                }
            }

            $cellNumber++;
        }

        $data .= '        </row>' . PHP_EOL;

        $wasWriteSuccessful = fwrite($this->sheetFilePointer, $data);
        if ($wasWriteSuccessful === false) {
            throw new IOException('Unable to write data in ' . $this->worksheetFilePath);
        }

        // only update the count if the write worked
        $this->lastWrittenRowIndex++;
    }

    /**
     * Closes the worksheet
     *
     * @return void
     */
    public function close()
    {
        fwrite($this->sheetFilePointer, '    </sheetData>' . PHP_EOL);
        fwrite($this->sheetFilePointer, '</worksheet>');
        fclose($this->sheetFilePointer);
    }
}
