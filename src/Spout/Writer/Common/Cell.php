<?php

namespace Box\Spout\Writer\Common;

use Box\Spout\Writer\Common\Helper\CellHelper;

class Cell
{
    /**
     * Numeric cell type (whole numbers, fractional numbers, dates)
     */
    const CELL_TYPE_NUMERIC = 0;

    /**
     * String (text) cell type
     */
    const CELL_TYPE_STRING = 1;

    /**
     * Formula cell type
     */
    const CELL_TYPE_FORMULA = 2;

    /**
     * Blank cell type
     */
    const CELL_TYPE_BLANK = 3;

    /**
     * Boolean cell type
     */
    const CELL_TYPE_BOOLEAN = 4;

    /**
     * Error cell type
     */
    const CELL_TYPE_ERROR = 5;

    /**
     * The value of this cell
     * @var null | mixed
     */
    protected $value = null;

    /**
     * The cell type
     * @var null
     */
    protected $type = null;

    /**
     * Cell constructor.
     * @param $value mixed
     * @param $comment string
     */
    public function __construct($value)
    {
        $this->setValue($value);
    }

    /**
     * @param $value mixed
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
     * @return mixed|null
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Get the current value type
     * @return int
     */
    protected function detectType($value)
    {
        if (CellHelper::isBoolean($value)) {
            return self::CELL_TYPE_BOOLEAN;
        } elseif (CellHelper::isEmpty($value)) {
            return self::CELL_TYPE_BLANK;
        } elseif (CellHelper::isNumeric($this->getValue())) {
            return self::CELL_TYPE_NUMERIC;
        } elseif (CellHelper::isNonEmptyString($value)) {
            return self::CELL_TYPE_STRING;
        } else {
            return self::CELL_TYPE_ERROR;
        }
    }

    /**
     * @return bool
     */
    public function isBoolean()
    {
        return $this->type === self::CELL_TYPE_BOOLEAN;
    }

    /**
     * @return bool
     */
    public function isBlank()
    {
        return $this->type === self::CELL_TYPE_BLANK;
    }

    /**
     * @return bool
     */
    public function isNumeric()
    {
        return $this->type === self::CELL_TYPE_NUMERIC;
    }

    /**
     * @return bool
     */
    public function isString()
    {
        return $this->type === self::CELL_TYPE_STRING;
    }

    /**
     * @return bool
     */
    public function isError()
    {
        return $this->type === self::CELL_TYPE_ERROR;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->value;
    }
}
