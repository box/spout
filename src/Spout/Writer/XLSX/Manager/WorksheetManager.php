<?php

namespace Box\Spout\Writer\XLSX\Manager;

use Box\Spout\Common\Exception\InvalidArgumentException;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Helper\StringHelper;
use Box\Spout\Writer\Common\Cell;
use Box\Spout\Writer\Common\Helper\CellHelper;
use Box\Spout\Writer\Common\Manager\OptionsManagerInterface;
use Box\Spout\Writer\Common\Options;
use Box\Spout\Writer\Entity\Worksheet;
use Box\Spout\Writer\Manager\WorksheetManagerInterface;
use Box\Spout\Writer\Style\Style;
use Box\Spout\Writer\XLSX\Helper\SharedStringsHelper;
use Box\Spout\Writer\XLSX\Helper\StyleHelper;

/**
 * Class WorksheetManager
 * XLSX worksheet manager, providing the interfaces to work with XLSX worksheets.
 *
 * @package Box\Spout\Writer\XLSX\Manager
 */
class WorksheetManager implements WorksheetManagerInterface
{
    /**
     * Maximum number of characters a cell can contain
     * @see https://support.office.com/en-us/article/Excel-specifications-and-limits-16c69c74-3d6a-4aaf-ba35-e6eb276e8eaa [Excel 2007]
     * @see https://support.office.com/en-us/article/Excel-specifications-and-limits-1672b34d-7043-467e-8e27-269d656771c3 [Excel 2010]
     * @see https://support.office.com/en-us/article/Excel-specifications-and-limits-ca36e2dc-1f09-4620-b726-67c00b05040f [Excel 2013/2016]
     */
    const MAX_CHARACTERS_PER_CELL = 32767;

    const SHEET_XML_FILE_HEADER = <<<EOD
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
EOD;

    /** @var bool Whether inline or shared strings should be used */
    protected $shouldUseInlineStrings;

    /** @var SharedStringsHelper Helper to write shared strings */
    private $sharedStringsHelper;

    /** @var StyleHelper Helper to work with styles */
    private $styleHelper;

    /** @var \Box\Spout\Common\Escaper\XLSX Strings escaper */
    private $stringsEscaper;

    /** @var StringHelper String helper */
    private $stringHelper;

    /**
     * WorksheetManager constructor.
     *
     * @param OptionsManagerInterface $optionsManager
     * @param SharedStringsHelper $sharedStringsHelper
     * @param StyleHelper $styleHelper
     * @param \Box\Spout\Common\Escaper\XLSX $stringsEscaper
     * @param StringHelper $stringHelper
     */
    public function __construct(
        OptionsManagerInterface $optionsManager,
        SharedStringsHelper $sharedStringsHelper,
        StyleHelper $styleHelper,
        \Box\Spout\Common\Escaper\XLSX $stringsEscaper,
        StringHelper $stringHelper)
    {
        $this->shouldUseInlineStrings = $optionsManager->getOption(Options::SHOULD_USE_INLINE_STRINGS);
        $this->sharedStringsHelper = $sharedStringsHelper;
        $this->styleHelper = $styleHelper;
        $this->stringsEscaper = $stringsEscaper;
        $this->stringHelper = $stringHelper;
    }

    /**
     * @return SharedStringsHelper
     */
    public function getSharedStringsHelper()
    {
        return $this->sharedStringsHelper;
    }


    /**
     * Prepares the worksheet to accept data
     *
     * @param Worksheet $worksheet The worksheet to start
     * @return void
     * @throws \Box\Spout\Common\Exception\IOException If the sheet data file cannot be opened for writing
     */
    public function startSheet(Worksheet $worksheet)
    {
        $sheetFilePointer = fopen($worksheet->getFilePath(), 'w');
        $this->throwIfSheetFilePointerIsNotAvailable($sheetFilePointer);

        $worksheet->setFilePointer($sheetFilePointer);

        fwrite($sheetFilePointer, self::SHEET_XML_FILE_HEADER);
        fwrite($sheetFilePointer, '<sheetData>');
    }

    /**
     * Checks if the sheet has been sucessfully created. Throws an exception if not.
     *
     * @param bool|resource $sheetFilePointer Pointer to the sheet data file or FALSE if unable to open the file
     * @return void
     * @throws IOException If the sheet data file cannot be opened for writing
     */
    private function throwIfSheetFilePointerIsNotAvailable($sheetFilePointer)
    {
        if (!$sheetFilePointer) {
            throw new IOException('Unable to open sheet for writing.');
        }
    }

    /**
     * Adds data to the given worksheet.
     *
     * @param Worksheet $worksheet The worksheet to add the row to
     * @param array $dataRow Array containing data to be written. Cannot be empty.
     *          Example $dataRow = ['data1', 1234, null, '', 'data5'];
     * @param Style $rowStyle Style to be applied to the row. NULL means use default style.
     * @return void
     * @throws IOException If the data cannot be written
     * @throws InvalidArgumentException If a cell value's type is not supported
     */
    public function addRow(Worksheet $worksheet, $dataRow, $rowStyle)
    {
        if (!$this->isEmptyRow($dataRow)) {
            $this->addNonEmptyRow($worksheet, $dataRow, $rowStyle);
        }

        $worksheet->setLastWrittenRowIndex($worksheet->getLastWrittenRowIndex() + 1);
    }

    /**
     * Returns whether the given row is empty
     *
     * @param array $dataRow Array containing data to be written. Cannot be empty.
     *          Example $dataRow = ['data1', 1234, null, '', 'data5'];
     * @return bool Whether the given row is empty
     */
    private function isEmptyRow($dataRow)
    {
        $numCells = count($dataRow);
        // using "reset()" instead of "$dataRow[0]" because $dataRow can be an associative array
        return ($numCells === 1 && CellHelper::isEmpty(reset($dataRow)));
    }

    /**
     * Adds non empty row to the worksheet.
     *
     * @param Worksheet $worksheet The worksheet to add the row to
     * @param array $dataRow Array containing data to be written. Cannot be empty.
     *          Example $dataRow = ['data1', 1234, null, '', 'data5'];
     * @param \Box\Spout\Writer\Style\Style $style Style to be applied to the row. NULL means use default style.
     * @return void
     * @throws \Box\Spout\Common\Exception\IOException If the data cannot be written
     * @throws \Box\Spout\Common\Exception\InvalidArgumentException If a cell value's type is not supported
     */
    private function addNonEmptyRow(Worksheet $worksheet, $dataRow, $style)
    {
        $cellNumber = 0;
        $rowIndex = $worksheet->getLastWrittenRowIndex() + 1;
        $numCells = count($dataRow);

        $rowXML = '<row r="' . $rowIndex . '" spans="1:' . $numCells . '">';

        foreach($dataRow as $cellValue) {
            $rowXML .= $this->getCellXML($rowIndex, $cellNumber, $cellValue, $style->getId());
            $cellNumber++;
        }

        $rowXML .= '</row>';

        $wasWriteSuccessful = fwrite($worksheet->getFilePointer(), $rowXML);
        if ($wasWriteSuccessful === false) {
            throw new IOException("Unable to write data in {$worksheet->getFilePath()}");
        }
    }

    /**
     * Build and return xml for a single cell.
     *
     * @param int $rowIndex
     * @param int $cellNumber
     * @param mixed $cellValue
     * @param int $styleId
     * @return string
     * @throws InvalidArgumentException If the given value cannot be processed
     */
    private function getCellXML($rowIndex, $cellNumber, $cellValue, $styleId)
    {
        $columnIndex = CellHelper::getCellIndexFromColumnIndex($cellNumber);
        $cellXML = '<c r="' . $columnIndex . $rowIndex . '"';
        $cellXML .= ' s="' . $styleId . '"';

        /** @TODO Remove code duplication with ODS writer: https://github.com/box/spout/pull/383#discussion_r113292746 */
        if ($cellValue instanceof Cell) {
            $cell = $cellValue;
        } else {
            $cell = new Cell($cellValue);
        }

        if ($cell->isString()) {
            $cellXML .= $this->getCellXMLFragmentForNonEmptyString($cell->getValue());
        } else if ($cell->isBoolean()) {
            $cellXML .= ' t="b"><v>' . intval($cell->getValue()) . '</v></c>';
        } else if ($cell->isNumeric()) {
            $cellXML .= '><v>' . $cell->getValue() . '</v></c>';
        } else if ($cell->isEmpty()) {
            if ($this->styleHelper->shouldApplyStyleOnEmptyCell($styleId)) {
                $cellXML .= '/>';
            } else {
                // don't write empty cells that do no need styling
                // NOTE: not appending to $cellXML is the right behavior!!
                $cellXML = '';
            }
        } else {
            throw new InvalidArgumentException('Trying to add a value with an unsupported type: ' . gettype($cell->getValue()));
        }

        return $cellXML;
    }

    /**
     * Returns the XML fragment for a cell containing a non empty string
     *
     * @param string $cellValue The cell value
     * @return string The XML fragment representing the cell
     * @throws InvalidArgumentException If the string exceeds the maximum number of characters allowed per cell
     */
    private function getCellXMLFragmentForNonEmptyString($cellValue)
    {
        if ($this->stringHelper->getStringLength($cellValue) > self::MAX_CHARACTERS_PER_CELL) {
            throw new InvalidArgumentException('Trying to add a value that exceeds the maximum number of characters allowed in a cell (32,767)');
        }

        if ($this->shouldUseInlineStrings) {
            $cellXMLFragment = ' t="inlineStr"><is><t>' . $this->stringsEscaper->escape($cellValue) . '</t></is></c>';
        } else {
            $sharedStringId = $this->sharedStringsHelper->writeString($cellValue);
            $cellXMLFragment = ' t="s"><v>' . $sharedStringId . '</v></c>';
        }

        return $cellXMLFragment;
    }

    /**
     * Closes the worksheet
     *
     * @param Worksheet $worksheet
     * @return void
     */
    public function close(Worksheet $worksheet)
    {
        $worksheetFilePointer = $worksheet->getFilePointer();

        if (!is_resource($worksheetFilePointer)) {
            return;
        }

        fwrite($worksheetFilePointer, '</sheetData>');
        fwrite($worksheetFilePointer, '</worksheet>');
        fclose($worksheetFilePointer);
    }
}