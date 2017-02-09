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
     * Comment of this cell
     * @var null | string
     */
    protected $comment = null;

    /**
     * Cell constructor.
     * @param $value mixed
     * @param $comment string
     */
    public function __construct($value, $comment = null)
    {
        $this->setValue($value);
        $this->setComment($comment);
    }

    /**
     * @param $value mixed
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return mixed|null
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param $comment string
     */
    public function setComment($comment)
    {
        $this->comment = $comment;
    }

    /**
     * @return null|string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Get the current value type
     * @return int
     */
    public function getType()
    {
        $value = $this->getValue();

        if(CellHelper::isBoolean($value)) {
            return self::CELL_TYPE_BOOLEAN;
        } elseif (CellHelper::isEmpty($value)) {
            return self::CELL_TYPE_BLANK;
        } elseif(CellHelper::isNumeric($this->getValue())) {
            return self::CELL_TYPE_NUMERIC;
        } elseif (CellHelper::isNonEmptyString($value)) {
            return self::CELL_TYPE_STRING;
        } else {
            return self::CELL_TYPE_ERROR;
        }
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->value;
    }
}