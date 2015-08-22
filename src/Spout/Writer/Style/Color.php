<?php

namespace Box\Spout\Writer\Style;

use Box\Spout\Writer\Exception\InvalidColorException;

/**
 * Class Color
 * This class provides constants and functions to work with colors
 *
 * @package Box\Spout\Writer\Style
 */
class Color
{
    /** Standard colors - based on Office Online */
    const BLACK = 'FF000000';
    const WHITE = 'FFFFFFFF';
    const RED = 'FFFF0000';
    const DARK_RED = 'FFC00000';
    const ORANGE = 'FFFFC000';
    const YELLOW = 'FFFFFF00';
    const LIGHT_GREEN = 'FF92D040';
    const GREEN = 'FF00B050';
    const LIGHT_BLUE = 'FF00B0E0';
    const BLUE = 'FF0070C0';
    const DARK_BLUE = 'FF002060';
    const PURPLE = 'FF7030A0';

    /**
     * Returns an ARGB color from R, G and B values
     * Alpha is assumed to always be 1
     *
     * @param int $red Red component, 0 - 255
     * @param int $green Green component, 0 - 255
     * @param int $blue Blue component, 0 - 255
     * @return string ARGB color
     */
    public static function rgb($red, $green, $blue)
    {
        self::throwIfInvalidColorComponentValue($red);
        self::throwIfInvalidColorComponentValue($green);
        self::throwIfInvalidColorComponentValue($blue);

        return strtoupper(
            'FF' .
            self::convertColorComponentToHex($red) .
            self::convertColorComponentToHex($green) .
            self::convertColorComponentToHex($blue)
        );
    }

    /**
     * Throws an exception is the color component value is outside of bounds (0 - 255)
     *
     * @param int $colorComponent
     * @return void
     * @throws \Box\Spout\Writer\Exception\InvalidColorException
     */
    protected static function throwIfInvalidColorComponentValue($colorComponent)
    {
        if (!is_int($colorComponent) || $colorComponent < 0 || $colorComponent > 255) {
            throw new InvalidColorException("The RGB components must be between 0 and 255. Received: $colorComponent");
        }
    }

    /**
     * Converts the color component to its corresponding hexadecimal value
     *
     * @param int $colorComponent Color component, 0 - 255
     * @return string Corresponding hexadecimal value, with a leading 0 if needed. E.g "0f", "2d"
     */
    protected static function convertColorComponentToHex($colorComponent)
    {
        return str_pad(dechex($colorComponent), 2, '0', 0);
    }
}
