<?php

namespace Box\Spout\Reader\Common\Entity;

use Box\Spout\Common\Helper\CellTypeHelper;

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
     * Date cell type
     */
    const TYPE_DATE = 5;

    /**
     * Error cell type
     */
    const TYPE_ERROR = 6;

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
     * @param $value mixed
     */
    public function __construct($value)
    {
        $this->setValue($value);
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
     * @return int|null
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Get the current value type
     *
     * @param mixed|null $value
     * @return int
     */
    protected function detectType($value)
    {
        if (CellTypeHelper::isBoolean($value)) {
            return self::TYPE_BOOLEAN;
        }
        if (CellTypeHelper::isEmpty($value)) {
            return self::TYPE_EMPTY;
        }
        if (CellTypeHelper::isNumeric($value)) {
            return self::TYPE_NUMERIC;
        }
        if (CellTypeHelper::isDateTimeOrDateInterval($value)) {
            return self::TYPE_DATE;
        }
        if (CellTypeHelper::isNonEmptyString($value)) {
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
}
