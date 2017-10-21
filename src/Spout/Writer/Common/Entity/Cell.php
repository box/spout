<?php

namespace Box\Spout\Writer\Common\Entity;

use Box\Spout\Writer\Common\Entity\Style\Style;
use Box\Spout\Writer\Common\Helper\CellHelper;
use Box\Spout\Writer\Common\Manager\Style\StyleMerger;

/**
 * Class Cell
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
    protected $value;

    /**
     * The cell type
     * @var int|null
     */
    protected $type;

    /**
     * The cell style
     * @var Style
     */
    protected $style;

    /**
     * @var StyleMerger
     */
    protected $styleMerger;

    /**
     * @param $value mixed
     * @param Style|null $style
     */
    public function __construct($value, Style $style = null)
    {
        $this->setValue($value);
        $this->setStyle($style);
        $this->styleMerger = new StyleMerger();
    }

    /**
     * @param mixed|null $value
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
     * @param Style|null $style
     */
    public function setStyle($style)
    {
        $this->style = $style ?: new Style();
    }

    /**
     * @return Style
     */
    public function getStyle()
    {
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
     *
     * @param mixed|null $value
     * @return int
     */
    protected function detectType($value)
    {
        if (CellHelper::isBoolean($value)) {
            return self::TYPE_BOOLEAN;
        }
        if (CellHelper::isEmpty($value)) {
            return self::TYPE_EMPTY;
        }
        if (CellHelper::isNumeric($this->getValue())) {
            return self::TYPE_NUMERIC;
        }
        if (CellHelper::isNonEmptyString($value)) {
            return self::TYPE_STRING;
        }

        return self::TYPE_ERROR;
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
     *
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
        return (string) $this->value;
    }

    /**
     * @param Style|null $style
     * @return Cell
     */
    public function applyStyle($style)
    {
        if ($style === null) {
            return $this;
        }

        $mergedStyle = $this->styleMerger->merge($this->style, $style);
        $this->setStyle($mergedStyle);

        return $this;
    }
}
