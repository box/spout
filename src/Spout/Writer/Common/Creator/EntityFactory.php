<?php

namespace Box\Spout\Writer\Common\Creator;

use Box\Spout\Writer\Common\Entity\Cell;
use Box\Spout\Writer\Common\Entity\Sheet;
use Box\Spout\Writer\Common\Entity\Workbook;
use Box\Spout\Writer\Common\Entity\Worksheet;
use Box\Spout\Writer\Common\Manager\SheetManager;

/**
 * Class EntityFactory
 * Factory to create entities
 *
 * @package Box\Spout\Writer\Common\Creator
 */
class EntityFactory
{
    /**
     * @return Workbook
     */
    public function createWorkbook()
    {
        return new Workbook();
    }

    /**
     * @param string $worksheetFilePath
     * @param Sheet $externalSheet
     * @return Worksheet
     */
    public function createWorksheet($worksheetFilePath, Sheet $externalSheet)
    {
        return new Worksheet($worksheetFilePath, $externalSheet);
    }

    /**
     * @param int $sheetIndex Index of the sheet, based on order in the workbook (zero-based)
     * @param string $associatedWorkbookId ID of the sheet's associated workbook
     * @param SheetManager $sheetManager To manage sheets
     * @return Sheet
     */
    public function createSheet($sheetIndex, $associatedWorkbookId, $sheetManager)
    {
        return new Sheet($sheetIndex, $associatedWorkbookId, $sheetManager);
    }

    /**
     * @param mixed $cellValue
     * @return Cell
     */
    public function createCell($cellValue)
    {
        return new Cell($cellValue);
    }

    /**
     * @return \ZipArchive
     */
    public function createZipArchive()
    {
        return new \ZipArchive();
    }
}
