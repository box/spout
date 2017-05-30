<?php

namespace Box\Spout\Writer\Common\Creator;

use Box\Spout\Writer\Common\Entity\Sheet;
use Box\Spout\Writer\Common\Entity\Workbook;
use Box\Spout\Writer\Common\Entity\Worksheet;

/**
 * Class EntityFactory
 * Factory to create entities
 *
 * @package Box\Spout\Writer\Common\Creator
 */
class EntityFactory
{
    /** @var ManagerFactory */
    private $managerFactory;

    /**
     * EntityFactory constructor.
     *
     * @param ManagerFactory $managerFactory
     */
    public function __construct(ManagerFactory $managerFactory)
    {
        $this->managerFactory = $managerFactory;
    }

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
     * @return Sheet
     */
    public function createSheet($sheetIndex, $associatedWorkbookId)
    {
        $sheetManager = $this->managerFactory->createSheetManager();
        return new Sheet($sheetIndex, $associatedWorkbookId, $sheetManager);
    }
}