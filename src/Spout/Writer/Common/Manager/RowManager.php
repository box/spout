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
}