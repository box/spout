<?php

namespace Box\Spout\Writer\Common\Creator;

use Box\Spout\Writer\Common\Entity\Cell;
use Box\Spout\Writer\Common\Entity\Row;
use Box\Spout\Writer\Common\Entity\Style\Style;
use Box\Spout\Writer\Common\Manager\RowManager;
use Box\Spout\Writer\Common\Manager\Style\StyleMerger;

/**
 * Class EntityFactory
 * Factory to create external entities
 */
class EntityFactory
{
    /**
     * @param mixed $cellValue
     * @param Style|null $cellStyle
     * @return Cell
     */
    public static function createCell($cellValue, Style $cellStyle = null)
    {
        return new Cell($cellValue, $cellStyle);
    }

    /**
     * @param array $cells
     * @param Style|null $rowStyle
     * @return Row
     */
    public static function createRow(array $cells = [], Style $rowStyle = null)
    {
        $styleMerger = new StyleMerger();
        $rowManager = new RowManager($styleMerger);

        return new Row($cells, $rowStyle, $rowManager);
    }
}
