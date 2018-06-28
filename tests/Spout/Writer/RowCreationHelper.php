<?php

namespace Box\Spout\Writer;

use Box\Spout\Common\Entity\Row;
use Box\Spout\Common\Entity\Style\Style;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;

/**
 * Trait RowCreationHelper
 */
trait RowCreationHelper
{
    /**
     * @param array $cellValues
     * @return Row
     */
    protected function createRowFromValues(array $cellValues)
    {
        return $this->createStyledRowFromValues($cellValues, null);
    }

    /**
     * @param array $cellValues
     * @param Style|null $rowStyle
     * @return Row
     */
    protected function createStyledRowFromValues(array $cellValues, Style $rowStyle = null)
    {
        return WriterEntityFactory::createRowFromArray($cellValues, $rowStyle);
    }

    /**
     * @param array $rowValues
     * @return Row[]
     */
    protected function createRowsFromValues(array $rowValues)
    {
        return $this->createStyledRowsFromValues($rowValues, null);
    }

    /**
     * @param array $rowValues
     * @param Style|null $rowsStyle
     * @return Row[]
     */
    protected function createStyledRowsFromValues(array $rowValues, Style $rowsStyle = null)
    {
        $rows = [];

        foreach ($rowValues as $cellValues) {
            $rows[] = $this->createStyledRowFromValues($cellValues, $rowsStyle);
        }

        return $rows;
    }
}
