<?php

namespace Box\Spout\Writer;

use Box\Spout\Writer\Common\Creator\EntityFactory;
use Box\Spout\Writer\Common\Entity\Row;
use Box\Spout\Writer\Common\Entity\Style\Style;

/**
 * Trait RowCreationHelper
 */
trait RowCreationHelper
{
    /**
     * @param array $cellValues
     * @param Style|null $rowStyle
     * @return Row
     */
    protected function createRowFromValues(array $cellValues, Style $rowStyle = null)
    {
        $row = EntityFactory::createRow([], $rowStyle);

        foreach ($cellValues as $cellValue) {
            $row->addCell(EntityFactory::createCell($cellValue));
        }

        return $row;
    }

    /**
     * @param array $cellValues
     * @param Style $rowStyle
     * @return Row
     */
    protected function createStyledRowFromValues(array $cellValues, Style $rowStyle)
    {
        return $this->createRowFromValues($cellValues, $rowStyle);
    }

    /**
     * @param array $rowValues
     * @param Style|null $rowsStyle
     * @return Row[]
     */
    protected function createRowsFromValues(array $rowValues, Style $rowsStyle = null)
    {
        $rows = [];

        foreach ($rowValues as $cellValues) {
            $rows[] = $this->createRowFromValues($cellValues, $rowsStyle);
        }

        return $rows;
    }

    /**
     * @param array $rowValues
     * @param Style $rowsStyle
     * @return Row[]
     */
    protected function createStyledRowsFromValues(array $rowValues, Style $rowsStyle)
    {
        return $this->createRowsFromValues($rowValues, $rowsStyle);
    }
}
