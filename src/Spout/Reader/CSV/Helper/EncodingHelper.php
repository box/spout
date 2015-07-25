<?php

namespace Box\Spout\Reader\CSV\Helper;

/**
 * Class EncodingHelper
 * This class provides helper functions to work with encodings.
 *
 * @package Box\Spout\Reader\CSV\Helper
 */
class EncodingHelper
{
    /** Definition of the encodings that can have a BOM */
    const ENCODING_UTF8     = 'UTF-8';
    const ENCODING_UTF16_LE = 'UTF-16LE';
    const ENCODING_UTF16_BE = 'UTF-16BE';
    const ENCODING_UTF32_LE = 'UTF-32LE';
    const ENCODING_UTF32_BE = 'UTF-32BE';

    /** Definition of the BOMs for the different encodings */
    const BOM_UTF8     = "\xEF\xBB\xBF";
    const BOM_UTF16_LE = "\xFF\xFE";
    const BOM_UTF16_BE = "\xFE\xFF";
    const BOM_UTF32_LE = "\xFF\xFE\x00\x00";
    const BOM_UTF32_BE = "\x00\x00\xFE\xFF";

    /** @var \Box\Spout\Common\Helper\GlobalFunctionsHelper Helper to work with global functions */
    protected $globalFunctionsHelper;

    /** @var array Map representing the encodings supporting BOMs (key) and their associated BOM (value) */
    protected $supportedEncodingsWithBom;

    /**
     * @param \Box\Spout\Common\Helper\GlobalFunctionsHelper $globalFunctionsHelper
     */
    public function __construct($globalFunctionsHelper)
    {
        $this->globalFunctionsHelper = $globalFunctionsHelper;

        $this->supportedEncodingsWithBom = [
            self::ENCODING_UTF8     => self::BOM_UTF8,
            self::ENCODING_UTF16_LE => self::BOM_UTF16_LE,
            self::ENCODING_UTF16_BE => self::BOM_UTF16_BE,
            self::ENCODING_UTF32_LE => self::BOM_UTF32_LE,
            self::ENCODING_UTF32_BE => self::BOM_UTF32_BE,
        ];
    }

    /**
     * Returns the number of bytes to use as offset in order to skip the BOM.
     *
     * @param resource $filePointer Pointer to the file to check
     * @param string $encoding Encoding of the file to check
     * @return int Bytes offset to apply to skip the BOM (0 means no BOM)
     */
    public function getBytesOffsetToSkipBOM($filePointer, $encoding)
    {
        $byteOffsetToSkipBom = 0;

        if ($this->hasBom($filePointer, $encoding)) {
            $bomUsed = $this->supportedEncodingsWithBom[$encoding];

            // we skip the N first bytes
            $byteOffsetToSkipBom = strlen($bomUsed);
        }

        return $byteOffsetToSkipBom;
    }

    /**
     * Returns whether the file identified by the given pointer has a BOM.
     *
     * @param resource $filePointer Pointer to the file to check
     * @param string $encoding Encoding of the file to check
     * @return bool TRUE if the file has a BOM, FALSE otherwise
     */
    protected function hasBOM($filePointer, $encoding)
    {
        $hasBOM = false;

        $this->globalFunctionsHelper->rewind($filePointer);

        if (array_key_exists($encoding, $this->supportedEncodingsWithBom)) {
            $potentialBom = $this->supportedEncodingsWithBom[$encoding];
            $numBytesInBom = strlen($potentialBom);

            $hasBOM = ($this->globalFunctionsHelper->fgets($filePointer, $numBytesInBom + 1) === $potentialBom);
        }

        return $hasBOM;
    }
}
