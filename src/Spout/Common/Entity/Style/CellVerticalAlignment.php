<?php

namespace Box\Spout\Common\Entity\Style;

/**
 * Class CellVerticalAlignment
 * This class provides constants to work with text vertical alignment.
 */
abstract class CellVerticalAlignment
{
    public const AUTO = 'auto';
    public const BASELINE = 'baseline';
    public const BOTTOM = 'bottom';
    public const CENTER = 'center';
    public const DISTRIBUTED = 'distributed';
    public const JUSTIFY = 'justify';
    public const TOP = 'top';

    private static $VALID_ALIGNMENTS = [
        self::AUTO => 1,
        self::BASELINE => 1,
        self::BOTTOM => 1,
        self::CENTER => 1,
        self::DISTRIBUTED => 1,
        self::JUSTIFY => 1,
        self::TOP => 1,
    ];

    /**
     * @param string $cellVerticalAlignment
     *
     * @return bool Whether the given cell vertical alignment is valid
     */
    public static function isValid($cellVerticalAlignment)
    {
        return isset(self::$VALID_ALIGNMENTS[$cellVerticalAlignment]);
    }
}
