<?php

namespace Box\Spout\Reader\XLSX\Manager;

use Box\Spout\Reader\Common\Entity\Row;
use Box\Spout\Reader\XLSX\Creator\InternalEntityFactory;

/**
 * Class RowManager
 */
class RowManager
{
    /** @var InternalEntityFactory Factory to create entities */
    private $entityFactory;

    /**
     * @param InternalEntityFactory $entityFactory Factory to create entities
     */
    public function __construct(InternalEntityFactory $entityFactory)
    {
        $this->entityFactory = $entityFactory;
    }

    /**
     * Detect whether a row is considered empty.
     * An empty row has all of its cells empty.
     *
     * @param Row $row
     * @return bool
     */
    public function isEmpty(Row $row)
    {
        foreach ($row->getCells() as $cell) {
            if (!$cell->isEmpty()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Fills the missing indexes of a row with empty cells.
     *
     * @param Row $row
     * @return Row
     */
    public function fillMissingIndexesWithEmptyCells(Row $row)
    {
        $rowCells = $row->getCells();
        if (count($rowCells) === 0) {
            return $row;
        }

        $maxCellIndex = max(array_keys($rowCells));

        for ($cellIndex = 0; $cellIndex < $maxCellIndex; $cellIndex++) {
            if (!isset($rowCells[$cellIndex])) {
                $row->setCellAtIndex($this->entityFactory->createCell(''), $cellIndex);
            }
        }

        return $row;
    }
}
