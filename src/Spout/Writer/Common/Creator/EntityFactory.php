<?php

namespace Box\Spout\Writer\Common\Creator;

use Box\Spout\Writer\Common\Sheet;
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
}