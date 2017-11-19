<?php

namespace Box\Spout\Writer\Common\Entity;

use Box\Spout\Writer\Common\Entity\Style\Style;

class Row
{
    /**
     * The cells in this row
     * @var Cell[]
     */
    protected $cells = [];

    /**
     * The row style
     * @var Style
     */
    protected $style;

    /**
     * Row constructor.
     * @param Cell[] $cells
     * @param Style|null $style
     */
    public function __construct(array $cells, $style)
    {
        $this
            ->setCells($cells)
            ->setStyle($style);
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
     * @return $this
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
     * @return Style
     */
    public function getStyle()
    {
        return $this->style;
    }

    /**
     * @param Style|null $style
     * @return Row
     */
    public function setStyle($style)
    {
        $this->style = $style ?: new Style();

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
     * @return int
     */
    public function getNumCells()
    {
        return count($this->cells);
    }
}
