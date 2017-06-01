<?php

namespace Box\Spout\Writer\ODS\Manager;

use Box\Spout\Common\Exception\InvalidArgumentException;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Helper\StringHelper;
use Box\Spout\Writer\Common\Entity\Cell;
use Box\Spout\Writer\Common\Entity\Row;
use Box\Spout\Writer\Common\Entity\Worksheet;
use Box\Spout\Writer\Common\Manager\WorksheetManagerInterface;
use Box\Spout\Writer\ODS\Manager\Style\StyleManager;

/**
 * Class WorksheetManager
 * ODS worksheet manager, providing the interfaces to work with ODS worksheets.
 *
 * @package Box\Spout\Writer\ODS\Manager
 */
class WorksheetManager implements WorksheetManagerInterface
{
    /** @var \Box\Spout\Common\Escaper\ODS Strings escaper */
    private $stringsEscaper;

    /** @var StringHelper String helper */
    private $stringHelper;

    /** @var StyleManager Manages styles */
    private $styleManager;

    /**
     * WorksheetManager constructor.
     * @param StyleManager $styleManager
     * @param \Box\Spout\Common\Escaper\ODS $stringsEscaper
     * @param StringHelper $stringHelper
     */
    public function __construct(
        StyleManager $styleManager,
        \Box\Spout\Common\Escaper\ODS $stringsEscaper,
        StringHelper $stringHelper)
    {
        $this->stringsEscaper = $stringsEscaper;
        $this->stringHelper = $stringHelper;
        $this->styleManager = $styleManager;
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
     * Adds a row to the worksheet.
     *
     * @param Worksheet $worksheet The worksheet to add the row to
     * @param Row $row The row to be added
     * @return void
     *
     * @throws IOException If the data cannot be written
     * @throws InvalidArgumentException If a cell value's type is not supported
     */
    public function addRow(Worksheet $worksheet, Row $row)
    {

        $cells = $row->getCells();
        $cellsCount = count($cells);

        $data = '<table:table-row table:style-name="ro1">';

        $currentCellIndex = 0;
        $nextCellIndex = 1;

        for ($i = 0; $i < $cellsCount; $i++) {

            /** @var Cell $cell */
            $cell = $cells[$currentCellIndex];
            /** @var Cell|null $nextCell */
            $nextCell = isset($cells[$nextCellIndex]) ? $cells[$nextCellIndex] : null;

            if (null === $nextCell || $cell->getValue() !== $nextCell->getValue()) {

                // Apply styles - the row style is merged at this point
                $cell->applyStyle($row->getStyle());
                $this->styleManager->applyExtraStylesIfNeeded($cell);
                $registeredStyle = $this->styleManager->registerStyle($cell->getStyle());
                $styleIndex = $registeredStyle->getId() + 1; // 1-based

                $numTimesValueRepeated = ($nextCellIndex - $currentCellIndex);
                $data .= $this->getCellXML($cell, $styleIndex, $numTimesValueRepeated);
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
     * @param Cell $cell The cell to be written
     * @param int $styleIndex Index of the used style
     * @param int $numTimesValueRepeated Number of times the value is consecutively repeated
     * @return string The cell XML content
     * @throws \Box\Spout\Common\Exception\InvalidArgumentException If a cell value's type is not supported
     */
    protected function getCellXML(Cell $cell, $styleIndex, $numTimesValueRepeated)
    {
        $data = '<table:table-cell table:style-name="ce' . $styleIndex . '"';

        if ($numTimesValueRepeated !== 1) {
            $data .= ' table:number-columns-repeated="' . $numTimesValueRepeated . '"';
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