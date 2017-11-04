<?php

namespace Box\Spout\Writer\Common\Entity;

use Box\Spout\Writer\Common\Entity\Style\Style;
use Box\Spout\Writer\Common\Manager\RowManager;

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
     * Thw row manager
     * @var RowManager
     */
    protected $rowManager;

    /**
     * Row constructor.
     * @param Cell[] $cells
     * @param Style|null $style
     * @param RowManager $rowManager
     */
    public function __construct(array $cells, $style, RowManager $rowManager)
    {
        $this
            ->setCells($cells)
            ->setStyle($style);

        $this->rowManager = $rowManager;
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
     * @param Style $style
     * @return Row
     */
    public function applyStyle($style)
    {
        $this->rowManager->applyStyle($this, $style);

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
     * Returns whether a row has cells
     *
     * @return bool
     */
    public function hasCells()
    {
        return $this->rowManager->hasCells($this);
    }

    /**
     * Detect whether this row is considered empty.
     * An empty row has either no cells at all - or only empty cells
     *
     * @return bool
     */
    public function isEmpty()
    {
        return $this->rowManager->isEmpty($this);
    }
}
