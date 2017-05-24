<?php

namespace Box\Spout\Writer\Common\Entity;

use Box\Spout\Writer\Common\Entity\Style\Style;

class Row
{
    /**
     * The cells in this row
     * @var array
     */
    protected $cells = [];

    /**
     * The row style
     * @var null|Style
     */
    protected $style = null;

    /**
     * Row constructor.
     * @param array $cells
     * @param Style|null $style
     */
    public function __construct(array $cells = [], Style $style = null)
    {
        $this
            ->setCells($cells)
            ->setStyle($style);
    }

    /**
     * @return array
     */
    public function getCells()
    {
        return $this->cells;
    }

    /**
     * @param array $cells
     * @return Row
     */
    public function setCells($cells)
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
     * @param Style $style
     * @return Row
     */
    public function setStyle($style)
    {
        $this->style = $style;
        return $this;
    }

    /**
     * @param Cell $cell
     */
    public function addCell(Cell $cell)
    {
        $this->cells[] = $cell;
        return $this;
    }
}
