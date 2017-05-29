<?php

namespace Box\Spout\Writer\Factory;

use Box\Spout\Writer\Common\Sheet;
use Box\Spout\Writer\Entity\Workbook;
use Box\Spout\Writer\Entity\Worksheet;

/**
 * Class EntityFactory
 * Entity factory
 *
 * @package Box\Spout\Writer\Factory
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