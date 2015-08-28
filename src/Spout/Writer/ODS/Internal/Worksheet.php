<?php

namespace Box\Spout\Writer\ODS\Internal;

use Box\Spout\Common\Exception\InvalidArgumentException;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Helper\StringHelper;
use Box\Spout\Writer\Common\Helper\CellHelper;
use Box\Spout\Writer\Common\Internal\WorksheetInterface;
use Box\Spout\Writer\Common\Sheet;

/**
 * Class Worksheet
 * Represents a worksheet within a ODS file. The difference with the Sheet object is
 * that this class provides an interface to write data
 *
 * @package Box\Spout\Writer\ODS\Internal
 */
class Worksheet implements WorksheetInterface
{
    /**
     * @see https://wiki.openoffice.org/wiki/Documentation/FAQ/Calc/Miscellaneous/What's_the_maximum_number_of_rows_and_cells_for_a_spreadsheet_file%3f
     * @see https://bz.apache.org/ooo/show_bug.cgi?id=30215
     */
    const MAX_NUM_ROWS_REPEATED = 1048576;
    const MAX_NUM_COLUMNS_REPEATED = 1024;

    /** @var \Box\Spout\Writer\Common\Sheet The "external" sheet */
    protected $externalSheet;

    /** @var string Path to the XML file that will contain the sheet data */
    protected $worksheetFilePath;

    /** @var \Box\Spout\Common\Escaper\ODS Strings escaper */
    protected $stringsEscaper;

    /** @var \Box\Spout\Common\Helper\StringHelper To help with string manipulation */
    protected $stringHelper;

    /** @var Resource Pointer to the sheet data file (e.g. xl/worksheets/sheet1.xml) */
    protected $sheetFilePointer;

    /** @var int Index of the last written row */
    protected $lastWrittenRowIndex = 0;

    /**
     * @param \Box\Spout\Writer\Common\Sheet $externalSheet The associated "external" sheet
     * @param string $worksheetFilesFolder Temporary folder where the files to create the XLSX will be stored
     * @throws \Box\Spout\Common\Exception\IOException If the sheet data file cannot be opened for writing
     */
    public function __construct($externalSheet, $worksheetFilesFolder)
    {
        $this->externalSheet = $externalSheet;
        /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
        $this->stringsEscaper = new \Box\Spout\Common\Escaper\ODS();
        $this->worksheetFilePath = $worksheetFilesFolder . '/sheet' . $externalSheet->getIndex() . '.xml';

        $this->stringHelper = new StringHelper();

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

        // The XML file does not contain the "<table:table>" node as it contains the sheet's name
        // which may change during the execution of the program. It will be added at the end.
        $content = '    <table:table-column table:default-cell-style-name="ce1" table:number-columns-repeated="' . self::MAX_NUM_COLUMNS_REPEATED . '" table:style-name="co1"/>' . PHP_EOL;
        fwrite($this->sheetFilePointer, $content);
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
     * @return string Path to the temporary sheet content XML file
     */
    public function getWorksheetFilePath()
    {
        return $this->worksheetFilePath;
    }

    /**
     * Returns the table XML root node as string.
     *
     * @return string <table> node as string
     */
    public function getTableRootNodeAsString()
    {
        $escapedSheetName = $this->stringsEscaper->escape($this->externalSheet->getName());
        $tableStyleName = 'ta' . ($this->externalSheet->getIndex() + 1);

        return '<table:table table:style-name="' . $tableStyleName . '" table:name="' . $escapedSheetName . '">';
    }

    /**
     * @return \Box\Spout\Writer\Common\Sheet The "external" sheet
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
     * Adds data to the worksheet.
     *
     * @param array $dataRow Array containing data to be written.
     *          Example $dataRow = ['data1', 1234, null, '', 'data5'];
     * @param \Box\Spout\Writer\Style\Style $style Style to be applied to the row. NULL means use default style.
     * @return void
     * @throws \Box\Spout\Common\Exception\IOException If the data cannot be written
     * @throws \Box\Spout\Common\Exception\InvalidArgumentException If a cell value's type is not supported
     */
    public function addRow($dataRow, $style)
    {
        $numColumnsRepeated = self::MAX_NUM_COLUMNS_REPEATED;
        $styleIndex = ($style->getId() + 1); // 1-based

        $data = '    <table:table-row table:style-name="ro1">' . PHP_EOL;

        foreach($dataRow as $cellValue) {
            $data .= '        <table:table-cell table:style-name="ce' . $styleIndex . '"';

            if (CellHelper::isNonEmptyString($cellValue)) {
                $data .= ' office:value-type="string">' . PHP_EOL;

                $cellValueLines = explode("\n", $cellValue);
                foreach ($cellValueLines as $cellValueLine) {
                    $data .= '            <text:p>' . $this->stringsEscaper->escape($cellValueLine) . '</text:p>' . PHP_EOL;
                }

                $data .= '        </table:table-cell>' . PHP_EOL;
            } else if (CellHelper::isBoolean($cellValue)) {
                $data .= ' office:value-type="boolean" office:value="' . $cellValue . '">' . PHP_EOL;
                $data .= '            <text:p>' . $cellValue . '</text:p>' . PHP_EOL;
                $data .= '        </table:table-cell>' . PHP_EOL;
            } else if (CellHelper::isNumeric($cellValue)) {
                $data .= ' office:value-type="float" office:value="' . $cellValue . '">' . PHP_EOL;
                $data .= '            <text:p>' . $cellValue . '</text:p>' . PHP_EOL;
                $data .= '        </table:table-cell>' . PHP_EOL;
            } else if (empty($cellValue)) {
                $data .= '/>' . PHP_EOL;
            } else {
                throw new InvalidArgumentException('Trying to add a value with an unsupported type: ' . gettype($cellValue));
            }

            $numColumnsRepeated--;
        }

        if ($numColumnsRepeated > 0) {
            $data .= '        <table:table-cell table:number-columns-repeated="' . $numColumnsRepeated . '"/>' . PHP_EOL;
        }

        $data .= '    </table:table-row>' . PHP_EOL;

        $wasWriteSuccessful = fwrite($this->sheetFilePointer, $data);
        if ($wasWriteSuccessful === false) {
            throw new IOException("Unable to write data in {$this->worksheetFilePath}");
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
        $remainingRepeatedRows = self::MAX_NUM_ROWS_REPEATED - $this->lastWrittenRowIndex;

        if ($remainingRepeatedRows > 0) {
            $data = '    <table:table-row table:style-name="ro1" table:number-rows-repeated="' . $remainingRepeatedRows . '">' . PHP_EOL;
            $data .= '        <table:table-cell table:number-columns-repeated="' . self::MAX_NUM_COLUMNS_REPEATED . '"/>' . PHP_EOL;
            $data .= '    </table:table-row>' . PHP_EOL;

            fwrite($this->sheetFilePointer, $data);
        }

        fclose($this->sheetFilePointer);
    }
}
