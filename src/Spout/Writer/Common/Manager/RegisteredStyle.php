<?php

namespace Box\Spout\Writer\Common\Manager;

use Box\Spout\Common\Entity\Style\Style;

class RegisteredStyle
{
    /**
     * @var Style
     */
    private $style;

    /**
     * @var bool
     */
    private $isRowStyle;

    public function __construct(Style $style, bool $isRowStyle)
    {
        $this->style = $style;
        $this->isRowStyle = $isRowStyle;
    }

    public function getStyle() : Style
    {
        return $this->style;
    }

    public function isRowStyle() : bool
    {
        return $this->isRowStyle;
    }
}
