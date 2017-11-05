<?php

namespace Box\Spout\Writer\Common\Creator;

use Box\Spout\Writer\Common\Entity\Cell;
use Box\Spout\Writer\Common\Entity\Row;
use Box\Spout\Writer\Common\Entity\Style\Style;
use Box\Spout\Writer\Common\Manager\RowManager;
use Box\Spout\Writer\Common\Manager\Style\StyleMerger;
use Box\Spout\Writer\WriterInterface;

/**
 * Class EntityFactory
 * Factory to create external entities
 */
class EntityFactory
{
    /**
     * This creates an instance of the appropriate writer, given the type of the file to be read
     *
     * @param  string $writerType Type of the writer to instantiate
     * @throws \Box\Spout\Common\Exception\UnsupportedTypeException
     * @return WriterInterface
     */
    public static function createWriter($writerType)
    {
        return (new WriterFactory())->create($writerType);
    }

    /**
     * @param Cell[] $cells
     * @param Style|null $rowStyle
     * @return Row
     */
    public static function createRow(array $cells = [], Style $rowStyle = null)
    {
        $styleMerger = new StyleMerger();
        $rowManager = new RowManager($styleMerger);

        return new Row($cells, $rowStyle, $rowManager);
    }

    /**
     * @param array $cellValues
     * @param Style|null $rowStyle
     * @return Row
     */
    public static function createRowFromArray(array $cellValues = [], Style $rowStyle = null)
    {
        $styleMerger = new StyleMerger();
        $rowManager = new RowManager($styleMerger);

        $cells = array_map(function ($cellValue) {
            return new Cell($cellValue);
        }, $cellValues);

        return new Row($cells, $rowStyle, $rowManager);
    }

    /**
     * @param mixed $cellValue
     * @param Style|null $cellStyle
     * @return Cell
     */
    public static function createCell($cellValue, Style $cellStyle = null)
    {
        return new Cell($cellValue, $cellStyle);
    }
}
