<?php

namespace Box\Spout\Common\Entity\Style;

/**
 * Class Style
 * Represents a style to be applied to a cell
 */
class NumberFormat
{
    const TYPE_CURRENCY = 1;
    const TYPE_PERCENTAGE = 2;
    const TYPE_NUMERIC = 3;

    const TYPES = [
        self::TYPE_CURRENCY,
        self::TYPE_PERCENTAGE,
        self::TYPE_NUMERIC,
    ];

    private $id;

    private $type;

    private $minDecimalPlaces;
    private $maxDecimalPlaces;

    private $currencySymbol;

    private $commas = true;

    public function __construct(int $type = null)
    {
        if (!empty($type)) {
            $this->setType($type);
        }
    }

    /**
     * @param int $id
     * @return NumberFormat
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @param bool $commas
     * @return NumberFormat
     */
    public function setCommas($commas)
    {
        $this->commas = $commas;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $type
     * @return NumberFormat
     */
    public function setType($type)
    {
        if (!in_array($type,self::TYPES)) {
            return $this;
            //todo throw some exception or something?
        }
        $this->type = $type;
        if ($type == self::TYPE_CURRENCY) {
            if (($this->minDecimalPlaces === null) && ($this->maxDecimalPlaces === null)) {
                $this->setDecimalPlaces(2,2);
            }
            if ($this->currencySymbol === null) {
                $this->setCurrencySymbol('$');
            }
        }
        return $this;
    }

    /**
     * @param int $minDecimalPlaces
     * @param int $maxDecimalPlaces
     * @return NumberFormat
     */
    public function setDecimalPlaces(int $minDecimalPlaces = null, int $maxDecimalPlaces = null)
    {
        $this->minDecimalPlaces = $minDecimalPlaces;
        $this->maxDecimalPlaces = $maxDecimalPlaces;
        return $this;
    }

    /**
     * @param int $currencySymbol
     * @return NumberFormat
     */
    public function setCurrencySymbol(string $currencySymbol)
    {
        $this->currencySymbol = $currencySymbol;
        return $this;
    }

    /**
     * @return string
     */
    public function getFormatCode()
    {
        //todo spit out xml tag
        $formatString = $this->type == self::TYPE_CURRENCY ? '$_' : '';
        $formatString .= ($this->commas ? '#,##0' : '#0');
        $formatString .= '.';
        for ($i = 0; $i < $this->minDecimalPlaces; $i++) {
            $formatString .= '0';
        }
        for ($i = $this->minDecimalPlaces; $i < $this->maxDecimalPlaces; $i++) {
            $formatString .= '#';
        }
        if ($this->type == self::TYPE_PERCENTAGE) {
            $formatString .= '%';
        }
        return $formatString;
    }
}
