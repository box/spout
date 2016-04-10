<?php

namespace Box\Spout\Writer\XLSX\Helper;

use Box\Spout\Writer\Style\BorderPart;
use Box\Spout\Writer\Style\Border;

class BorderHelper
{
    public static $xlsxStyleMap = [
        Border::STYLE_SOLID.'%'.Border::WIDTH_THIN => 'thin',
        Border::STYLE_SOLID.'%'.Border::WIDTH_MEDIUM => 'medium',
        Border::STYLE_SOLID.'%'.Border::WIDTH_THICK => 'thick',
        Border::STYLE_DOTTED.'%'.Border::WIDTH_THIN => 'dotted',
        Border::STYLE_DOTTED.'%'.Border::WIDTH_MEDIUM => 'dotted',
        Border::STYLE_DOTTED.'%'.Border::WIDTH_THICK => 'dotted',
        Border::STYLE_DASHED.'%'.Border::WIDTH_THIN => 'dashed',
        Border::STYLE_DASHED.'%'.Border::WIDTH_MEDIUM => 'mediumDashed',
        Border::STYLE_DASHED.'%'.Border::WIDTH_THICK => 'mediumDashed',
        Border::STYLE_DOUBLE.'%'.Border::WIDTH_THIN => 'double',
        Border::STYLE_DOUBLE.'%'.Border::WIDTH_MEDIUM => 'double',
        Border::STYLE_DOUBLE.'%'.Border::WIDTH_THICK => 'double',
        Border::STYLE_NONE.'%'.Border::WIDTH_THIN => 'none',
        Border::STYLE_NONE.'%'.Border::WIDTH_MEDIUM => 'none',
        Border::STYLE_NONE.'%'.Border::WIDTH_THICK => 'none',
    ];

    /**
     * @param BorderPart $borderPart
     * @return string
     */
    public static function serializeBorderPart(BorderPart $borderPart)
    {
        $styleDef = $borderPart->getStyle() .'%' . $borderPart->getWidth();
        $borderStyle = self::$xlsxStyleMap[$styleDef];

        $colorEl = $borderPart->getColor() ? sprintf('<color rgb="%s"/>', $borderPart->getColor()) : '';
        $partEl = sprintf(
            '<%s style="%s">%s</%s>', $borderPart->getName(), $borderStyle, $colorEl, $borderPart->getName()
        );
        return $partEl.PHP_EOL;
    }
}