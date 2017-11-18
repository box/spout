<?php

namespace Box\Spout\Reader\Common\Entity;

class Row
{
    /**
     * The cells in this row
     * @var Cell[]
     */
    protected $cells = [];

    /**
     * Row constructor.
     * @param Cell[] $cells
     */
    public function __construct(array $cells)
    {
        $this->setCells($cells);
    }

    /**
     * @return Cell[] $cells
     */
    public function getCells()
    {
        return $this->cells;
    }

    /**
     * @param Cell[] $cells
     * @return Row
     */
    public function setCells(array $cells)
    {
        $this->cells = [];
        foreach ($cells as $cell) {
            $this->addCell($cell);
        }

        return $this;
    }

    /**
     * @param Cell $cell
     * @param mixed $cellIndex
     * @parma int $cellIndex
     * @return Row
     */
    public function setCellAtIndex(Cell $cell, $cellIndex)
    {
        $this->cells[$cellIndex] = $cell;

        return $this;
    }

    /**
     * @param Cell $cell
     * @return Row
     */
    public function addCell(Cell $cell)
    {
        $this->cells[] = $cell;

        return $this;
    }

    /**
     * @return array The row values, as array
     */
    public function toArray()
    {
        return array_map(function (Cell $cell) {
            return !$cell->isError() ? $cell->getValue() : null;
        }, $this->cells);
    }
}
