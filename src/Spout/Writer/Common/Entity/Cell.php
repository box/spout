<?php

namespace Box\Spout\Writer\Common\Entity;

use Box\Spout\Writer\Common\Entity\Style\Style;
use Box\Spout\Writer\Common\Helper\CellHelper;
use Box\Spout\Writer\Common\Manager\Style\StyleMerger;

/**
 * Class Cell
 *
 * @package Box\Spout\Writer\Common\Entity
 */
class Cell
{
    /**
     * Numeric cell type (whole numbers, fractional numbers, dates)
     */
    const TYPE_NUMERIC = 0;

    /**
     * String (text) cell type
     */
    const TYPE_STRING = 1;

    /**
     * Formula cell type
     * Not used at the moment
     */
    const TYPE_FORMULA = 2;

    /**
     * Empty cell type
     */
    const TYPE_EMPTY = 3;

    /**
     * Boolean cell type
     */
    const TYPE_BOOLEAN = 4;

    /**
     * Error cell type
     */
    const TYPE_ERROR = 5;

    /**
     * The value of this cell
     * @var mixed|null
     */
    protected $value = null;

    /**
     * The cell type
     * @var int|null
     */
    protected $type = null;

    /**
     * The cell style
     * @var Style|null
     */
    protected $style = null;

    /**
     * @var StyleMerger
     */
    protected $styleMerger;

    /**
     * Cell constructor.
     * @param $value mixed
     * @param $style Style
     */
    public function __construct($value, Style $style = null)
    {
        $this->setValue($value);
        if ($style) {
            $this->setStyle($style);
        }

        $this->styleMerger = new StyleMerger();
    }

    /**
     * @param $value mixed|null
     */
    public function setValue($value)
    {
        $this->value = $value;
        $this->type = $this->detectType($value);
    }

    /**
     * @return mixed|null
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param Style $style
     */
    public function setStyle(Style $style)
    {
        $this->style = $style;
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
     * @return int|null
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Get the current value type
     * @param mixed|null $value
     * @return int
     */
    protected function detectType($value)
    {
        if (CellHelper::isBoolean($value)) {
            return self::TYPE_BOOLEAN;
        } elseif (CellHelper::isEmpty($value)) {
            return self::TYPE_EMPTY;
        } elseif (CellHelper::isNumeric($this->getValue())) {
            return self::TYPE_NUMERIC;
        } elseif (CellHelper::isNonEmptyString($value)) {
            return self::TYPE_STRING;
        } else {
            return self::TYPE_ERROR;
        }
    }

    /**
     * @return bool
     */
    public function isBoolean()
    {
        return $this->type === self::TYPE_BOOLEAN;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return $this->type === self::TYPE_EMPTY;
    }

    /**
     * Not used at the moment
     * @return bool
     */
    public function isFormula()
    {
        return $this->type === self::TYPE_FORMULA;
    }

    /**
     * @return bool
     */
    public function isNumeric()
    {
        return $this->type === self::TYPE_NUMERIC;
    }

    /**
     * @return bool
     */
    public function isString()
    {
        return $this->type === self::TYPE_STRING;
    }

    /**
     * @return bool
     */
    public function isError()
    {
        return $this->type === self::TYPE_ERROR;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->value;
    }

    /**
     * @param Style $style|null
     * @return $this
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
}
