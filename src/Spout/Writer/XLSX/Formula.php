<?php

namespace Box\Spout\Writer\XLSX;

/**
 * Class Formula
 *
 * @package Box\Spout\Reader\XLSX
 */
class Formula
{


    /**
     * @var string
     */
    protected $formula;

    /**
     * @var string
     */
    protected $value = '0';

    /**
     * Formula constructor.
     * @param string $formula
     * @param string $value
     */
    public function __construct($formula, $value = '0')
    {
        $this->formula = $formula;
        $this->value = $value;
    }

    public function getXml()
    {
        return '><f>' . $this->formula . '</f><v>' . $this->value . '</v></c>';
    }

} 