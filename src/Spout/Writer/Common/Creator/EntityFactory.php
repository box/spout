<?php

namespace Box\Spout\Writer\Common\Creator;

use Box\Spout\Writer\Common\Entity\Row;
use Box\Spout\Writer\Common\Entity\Cell;
use Box\Spout\Writer\Common\Entity\Sheet;
use Box\Spout\Writer\Common\Entity\Style\Style;
use Box\Spout\Writer\Common\Entity\Workbook;
use Box\Spout\Writer\Common\Entity\Worksheet;
use Box\Spout\Writer\Common\Manager\RowManager;
use Box\Spout\Writer\Common\Manager\SheetManager;
use Box\Spout\Writer\Common\Manager\Style\StyleMerger;

/**
 * Class EntityFactory
 * Factory to create entities
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
    public static function createCell($cellValue)
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

    /**
     * @param array $cells
     * @param Style|null $style
     * @return Row
     */
    public static function createRow(array $cells, Style $style = null)
    {
        $styleMerger = new StyleMerger();
        $rowManager = new RowManager($styleMerger);
        return new Row($cells, $style, $rowManager);
    }
}
