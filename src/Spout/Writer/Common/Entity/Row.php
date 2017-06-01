<?php

namespace Box\Spout\Writer\Common\Entity;

use Box\Spout\Writer\Common\Entity\Style\Style;
use Box\Spout\Writer\Common\Manager\Style\StyleMerger;

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
     * @var StyleMerger
     */
    protected $styleMerger;

    /**
     * Row constructor.
     * @param Cell[] $cells
     * @param Style|null $style
     */
    public function __construct(array $cells = [], Style $style = null)
    {
        $this
            ->setCells($cells)
            ->setStyle($style);

        $this->styleMerger = new StyleMerger();
    }

    /**
     * @return Cell[] $cells
     */
    public function getCells()
    {
        return $this->cells;
    }

    /**
     * @param array $cells
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
        if (!isset($this->style)) {
            $this->setStyle(new Style());
        }
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
     * @param Style $style|null
     * @return Row
     */
    public function applyStyle(Style $style = null)
    {
        if ($style === null) {
            return $this;
        }
        $merged = $this->styleMerger->merge($this->getStyle(), $style);
        $this->setStyle($merged);
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
     * Detect whether this row is considered empty.
     * An empty row has either no cells at all - or only empty cells
     *
     * @return bool
     */
    public function isEmpty()
    {
        return count($this->cells) === 0 || (count($this->cells) === 1 && $this->cells[0]->isEmpty());
    }
}
