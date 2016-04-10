<?php

namespace Box\Spout\Writer\ODS\Helper;

use Box\Spout\Writer\Style\BorderPart;
use Box\Spout\Writer\Style\Border;

/**
 * Class BorderHelper
 *
 * The fo:border, fo:border-top, fo:border-bottom, fo:border-left and fo:border-right attributes
 * specify border properties
 * http://docs.oasis-open.org/office/v1.2/os/OpenDocument-v1.2-os-part1.html#__RefHeading__1419780_253892949
 *
 * Example table-cell-properties
 *
 * <style:table-cell-properties
 * fo:border-bottom="0.74pt solid #ffc000" style:diagonal-bl-tr="none"
 * style:diagonal-tl-br="none" fo:border-left="none" fo:border-right="none"
 * style:rotation-align="none" fo:border-top="none"/>
 */
class BorderHelper
{

    /**
     * ODS border attributes
     *
     * @var array
     */
    public static $odsStyleMap = [
        Border::STYLE_SOLID.'%'.Border::WIDTH_THIN => '0.75pt solid',
        Border::STYLE_SOLID.'%'.Border::WIDTH_MEDIUM => '1.75pt solid',
        Border::STYLE_SOLID.'%'.Border::WIDTH_THICK => '2.5pt solid',
        Border::STYLE_DOTTED.'%'.Border::WIDTH_THIN => '0.75pt dotted',
        Border::STYLE_DOTTED.'%'.Border::WIDTH_MEDIUM => '1.75pt dotted',
        Border::STYLE_DOTTED.'%'.Border::WIDTH_THICK => '2.5pt dotted',
        Border::STYLE_DASHED.'%'.Border::WIDTH_THIN => '0.75pt dashed',
        Border::STYLE_DASHED.'%'.Border::WIDTH_MEDIUM => '1.75pt dashed',
        Border::STYLE_DASHED.'%'.Border::WIDTH_THICK => '2.5pt dashed',
        Border::STYLE_DOUBLE.'%'.Border::WIDTH_THIN => '0.75pt double',
        Border::STYLE_DOUBLE.'%'.Border::WIDTH_MEDIUM => '1.75pt double',
        Border::STYLE_DOUBLE.'%'.Border::WIDTH_THICK => '2.5pt double',
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
        $borderStyle = self::$odsStyleMap[$styleDef];
        $colorEl = ($borderPart->getColor() && $borderPart->getStyle() !== Border::STYLE_NONE)
            ? '#' . $borderPart->getColor() : '';
        $partEl = sprintf(
            'fo:border-%s="%s"',
            $borderPart->getName(),
            $borderStyle . ' ' .$colorEl
        );
        return $partEl;
    }
}
