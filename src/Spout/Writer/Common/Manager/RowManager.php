<?php

namespace Box\Spout\Writer\Common\Manager;

use Box\Spout\Writer\Common\Entity\Row;
use Box\Spout\Writer\Common\Entity\Style\Style;
use Box\Spout\Writer\Common\Manager\Style\StyleMerger;

class RowManager
{
    /**
     * @var StyleMerger
     */
    protected $styleMerger;

    /**
     * RowManager constructor.
     * @param StyleMerger $styleMerger
     */
    public function __construct(StyleMerger $styleMerger)
    {
        $this->styleMerger = $styleMerger;
    }

    /**
     * @param Style $style
     * @return $this
     */
    public function applyStyle(Row $row, Style $style)
    {
        $mergedStyle = $this->styleMerger->merge($row->getStyle(), $style);
        $row->setStyle($mergedStyle);
    }

    /**
     * Detect whether a row is considered empty.
     * An empty row has either no cells at all - or only one empty cell
     *
     * @param Row $row
     * @return bool
     */
    public function isEmpty(Row $row)
    {
        $cells = $row->getCells();
        return count($cells) === 0 || (count($cells) === 1 && $cells[0]->isEmpty());
    }
}