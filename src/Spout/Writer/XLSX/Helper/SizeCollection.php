<?php

namespace Box\Spout\Writer\XLSX\Helper;

/**
 * SizeCollection to build and hold widths & heights of individual characters.
 */
class SizeCollection
{
    /** Constant for default character size. */
    const BASE_SIZE = 12;

    /** @var array Contains character widths/heights for each font & size. */
    private $sizes = array();

    /**
     * SizeCollection constructor to read character sizes from csv.
     */
    public function __construct()
    {
        $fh = fopen(dirname(__FILE__) . '/size_collection.csv', 'r');
        $head = fgetcsv($fh);
        unset($head[0], $head[1]);

        while ($row = fgetcsv($fh)) {
            $this->addSizesToCollection($head, $row);
        }
    }

    /**
     * Return character sizes for given font name.
     *
     * @param string $fontName
     * @param int    $fontSize
     * @return array
     */
    public function get($fontName, $fontSize)
    {
        if (isset($this->sizes[$fontName][$fontSize])) {
            return $this->sizes[$fontName][$fontSize];
        }

        return $this->calculate($fontName, $fontSize);
    }

    /**
     * Calculate character widths based on font name and size.
     *
     * @param string $fontName
     * @param int    $fontSize
     * @return array
     */
    private function calculate($fontName, $fontSize)
    {
        foreach ($this->getBaseSizes($fontName) as $character => $size) {
            $size = round($size / self::BASE_SIZE * $fontSize, 3);
            $this->sizes[$fontName][$fontSize][$character] = $size;
        }
        return $this->sizes[$fontName][$fontSize];
    }

    /**
     * Get character base widths by font name or default.
     *
     * @param string $fontName
     * @return array
     */
    private function getBaseSizes($fontName)
    {
        if (isset($this->sizes[$fontName])) {
            return $this->sizes[$fontName][self::BASE_SIZE];
        }
        return $this->sizes['Calibri'][self::BASE_SIZE];
    }

    /**
     * Add character widths for a single font.
     *
     * @param array $keys
     * @param array $values
     */
    private function addSizesToCollection(array $keys, array $values)
    {
        $fontName = array_shift($values);
        $fontSize = array_shift($values);
        $this->sizes[$fontName][$fontSize] = array_combine($keys, $values);
    }
}