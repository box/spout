<?php

namespace Box\Spout\Writer\Style;

use Box\Spout\Writer\Exception\Border\InvalidNameException;
use Box\Spout\Writer\Exception\Border\InvalidStyleException;
use Box\Spout\Writer\Exception\Border\InvalidWidthException;

/**
 * Class BorderPart
 */
class BorderPart
{
    /**
     * @var string The style of this border part.
     */
    protected $style;

    /**
     * @var string The name of this border part.
     */
    protected $name;

    /**
     * @var string The color of this border part.
     */
    protected $color;

    /**
     * @var string The width of this border part.
     */
    protected $width;

    /**
     * @var array Allowed style constants for parts.
     */
    protected static $allowedStyles = [
        'none',
        'solid',
        'dashed',
        'dotted',
        'double'
    ];

    /**
     * @var array Allowed names constants for border parts.
     */
    protected static $allowedNames = [
        'left',
        'right',
        'top',
        'bottom',
    ];

    /**
     * @var array Allowed width constants for border parts.
     */
    protected static $allowedWidths = [
        'thin',
        'medium',
        'thick',
    ];

    /**
     * @param string $name @see  BorderPart::allowedNames
     * @param string $style @see BorderPart::allowedStyles
     * @param string $width @see BorderPart::allowedWidths
     * @param string $color A RGB color code
     * @throws InvalidNameException
     * @throws InvalidStyleException
     * @throws InvalidWidthException
     */
    public function __construct($name, $style = Border::STYLE_SOLID, $color = Color::BLACK, $width = Border::WIDTH_MEDIUM)
    {
        $this->setName($name);
        $this->setStyle($style);
        $this->setWidth($width);
        $this->setColor($color);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        if (!in_array($name, self::$allowedNames)) {
            throw new InvalidNameException($name);
        }
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getStyle()
    {
        return $this->style;
    }

    /**
     * @param string $style
     */
    public function setStyle($style)
    {
        if (!in_array($style, self::$allowedStyles)) {
            throw new InvalidStyleException($style);
        }
        $this->style = $style;
    }

    /**
     * @return string
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * @param string $color
     */
    public function setColor($color)
    {
        $this->color = $color;
    }

    /**
     * @return string
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param string $width
     */
    public function setWidth($width)
    {
        if (!in_array($width, self::$allowedWidths)) {
            throw new InvalidWidthException($width);
        }
        $this->width = $width;
    }

    /**
     * @return array
     */
    public static function getAllowedStyles()
    {
        return self::$allowedStyles;
    }

    /**
     * @return array
     */
    public static function getAllowedNames()
    {
        return self::$allowedNames;
    }

    /**
     * @return array
     */
    public static function getAllowedWidths()
    {
        return self::$allowedWidths;
    }
}
