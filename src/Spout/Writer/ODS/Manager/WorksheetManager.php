<?php

namespace Box\Spout\Writer\ODS\Manager;

use Box\Spout\Common\Exception\InvalidArgumentException;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Helper\StringHelper;
use Box\Spout\Writer\Common\Entity\Cell;
use Box\Spout\Writer\Common\Entity\Worksheet;
use Box\Spout\Writer\Common\Manager\WorksheetManagerInterface;
use Box\Spout\Writer\ODS\Helper\StyleHelper;
use Box\Spout\Writer\Common\Entity\Style\Style;

/**
 * Class WorksheetManager
 * ODS worksheet manager, providing the interfaces to work with ODS worksheets.
 *
 * @package Box\Spout\Writer\ODS\Manager
 */
class WorksheetManager implements WorksheetManagerInterface
{
    /** @var StyleHelper Helper to work with styles */
    private $styleHelper;

    /** @var \Box\Spout\Common\Escaper\ODS Strings escaper */
    private $stringsEscaper;

    /** @var StringHelper String helper */
    private $stringHelper;

    /**
     * WorksheetManager constructor.
     *
     * @param StyleHelper $styleHelper
     * @param \Box\Spout\Common\Escaper\ODS $stringsEscaper
     * @param StringHelper $stringHelper
     */
    public function __construct(
        StyleHelper $styleHelper,
        \Box\Spout\Common\Escaper\ODS $stringsEscaper,
        StringHelper $stringHelper)
    {
        $this->styleHelper = $styleHelper;
        $this->stringsEscaper = $stringsEscaper;
        $this->stringHelper = $stringHelper;
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
     * Returns the table XML root node as string.
     *
     * @param Worksheet $worksheet
     * @return string <table> node as string
     */
    public function getTableElementStartAsString(Worksheet $worksheet)
    {
        $externalSheet = $worksheet->getExternalSheet();
        $escapedSheetName = $this->stringsEscaper->escape($externalSheet->getName());
        $tableStyleName = 'ta' . ($externalSheet->getIndex() + 1);

        $tableElement  = '<table:table table:style-name="' . $tableStyleName . '" table:name="' . $escapedSheetName . '">';
        $tableElement .= '<table:table-column table:default-cell-style-name="ce1" table:style-name="co1" table:number-columns-repeated="' . $worksheet->getMaxNumColumns() . '"/>';

        return $tableElement;
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
        // $dataRow can be an associative array. We need to transform
        // it into a regular array, as we'll use the numeric indexes.
        $dataRowWithNumericIndexes = array_values($dataRow);

        $styleIndex = ($rowStyle->getId() + 1); // 1-based
        $cellsCount = count($dataRow);

        $data = '<table:table-row table:style-name="ro1">';

        $currentCellIndex = 0;
        $nextCellIndex = 1;

        for ($i = 0; $i < $cellsCount; $i++) {
            $currentCellValue = $dataRowWithNumericIndexes[$currentCellIndex];

            // Using isset here because it is way faster than array_key_exists...
            if (!isset($dataRowWithNumericIndexes[$nextCellIndex]) ||
                $currentCellValue !== $dataRowWithNumericIndexes[$nextCellIndex]) {

                $numTimesValueRepeated = ($nextCellIndex - $currentCellIndex);
                $data .= $this->getCellXML($currentCellValue, $styleIndex, $numTimesValueRepeated);

                $currentCellIndex = $nextCellIndex;
            }

            $nextCellIndex++;
        }

        $data .= '</table:table-row>';

        $wasWriteSuccessful = fwrite($worksheet->getFilePointer(), $data);
        if ($wasWriteSuccessful === false) {
            throw new IOException("Unable to write data in {$worksheet->getFilePath()}");
        }

        // only update the count if the write worked
        $lastWrittenRowIndex = $worksheet->getLastWrittenRowIndex();
        $worksheet->setLastWrittenRowIndex($lastWrittenRowIndex + 1);
    }

    /**
     * Returns the cell XML content, given its value.
     *
     * @param mixed $cellValue The value to be written
     * @param int $styleIndex Index of the used style
     * @param int $numTimesValueRepeated Number of times the value is consecutively repeated
     * @return string The cell XML content
     * @throws \Box\Spout\Common\Exception\InvalidArgumentException If a cell value's type is not supported
     */
    private function getCellXML($cellValue, $styleIndex, $numTimesValueRepeated)
    {
        $data = '<table:table-cell table:style-name="ce' . $styleIndex . '"';

        if ($numTimesValueRepeated !== 1) {
            $data .= ' table:number-columns-repeated="' . $numTimesValueRepeated . '"';
        }

        /** @TODO Remove code duplication with XLSX writer: https://github.com/box/spout/pull/383#discussion_r113292746 */
        if ($cellValue instanceof Cell) {
            $cell = $cellValue;
        } else {
            $cell = new Cell($cellValue);
        }

        if ($cell->isString()) {
            $data .= ' office:value-type="string" calcext:value-type="string">';

            $cellValueLines = explode("\n", $cell->getValue());
            foreach ($cellValueLines as $cellValueLine) {
                $data .= '<text:p>' . $this->stringsEscaper->escape($cellValueLine) . '</text:p>';
            }

            $data .= '</table:table-cell>';
        } else if ($cell->isBoolean()) {
            $data .= ' office:value-type="boolean" calcext:value-type="boolean" office:boolean-value="' . $cell->getValue() . '">';
            $data .= '<text:p>' . $cell->getValue() . '</text:p>';
            $data .= '</table:table-cell>';
        } else if ($cell->isNumeric()) {
            $data .= ' office:value-type="float" calcext:value-type="float" office:value="' . $cell->getValue() . '">';
            $data .= '<text:p>' . $cell->getValue() . '</text:p>';
            $data .= '</table:table-cell>';
        } else if ($cell->isEmpty()) {
            $data .= '/>';
        } else {
            throw new InvalidArgumentException('Trying to add a value with an unsupported type: ' . gettype($cell->getValue()));
        }

        return $data;
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

        fclose($worksheetFilePointer);
    }
}