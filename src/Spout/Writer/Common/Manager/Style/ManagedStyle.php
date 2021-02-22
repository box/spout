<?php

namespace Box\Spout\Writer\Common\Manager\Style;

use Box\Spout\Common\Entity\Style\Style;

class ManagedStyle
{
    private $style;
    private $isUpdated;

    public function __construct(Style $style, bool $isUpdated)
    {
        $this->style = $style;
        $this->isUpdated = $isUpdated;
    }

    public function getStyle() : Style
    {
        return $this->style;
    }

    public function isUpdated() : bool
    {
        return $this->isUpdated;
    }
}
