<?php

namespace Box\Spout\Writer\XLSX;

use Box\Spout\Writer\Exception\InvalidSheetNameException;

/**
 * Class Sheet
 * External representation of a worksheet within a XLSX file
 *
 * @package Box\Spout\Writer\XLSX
 */
class Sheet
{
    const DEFAULT_SHEET_NAME_PREFIX = 'Sheet';

    /** Sheet name should not exceed 31 characters */
    const MAX_LENGTH_SHEET_NAME = 31;

    /** @var array Invalid characters that cannot be contained in the sheet name */
    private static $INVALID_CHARACTERS_IN_SHEET_NAME = ['\\', '/', '?', '*', '[', ']'];

    /** @var array Associative array [SHEET_INDEX] => [SHEET_NAME] keeping track of sheets' name to enforce uniqueness */
    protected static $SHEETS_NAME_USED = [];

    /** @var int Index of the sheet, based on order of creation (zero-based) */
    protected $index;

    /** @var string Name of the sheet */
    protected $name;

    /**
     * @param int $sheetIndex Index of the sheet, based on order of creation (zero-based)
     */
    public function __construct($sheetIndex)
    {
        $this->index = $sheetIndex;
        $this->setName(self::DEFAULT_SHEET_NAME_PREFIX . ($sheetIndex + 1));
    }

    /**
     * @return int Index of the sheet, based on order of creation (zero-based)
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * @return string Name of the sheet
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the name of the sheet. Note that Excel has some restrictions on the name:
     *  - it should not be blank
     *  - it should not exceed 31 characters
     *  - it should not contain these characters: \ / ? * [ or ]
     *  - it should be unique
     *
     * @param string $name Name of the sheet
     * @return Sheet
     * @throws \Box\Spout\Writer\Exception\InvalidSheetNameException If the sheet's name is invalid.
     */
    public function setName($name)
    {
        if (!$this->isNameValid($name)) {
            $errorMessage = "The sheet's name is invalid. It did not meet at least one of these requirements:\n";
            $errorMessage .= " - It should not be blank\n";
            $errorMessage .= " - It should not exceed 31 characters\n";
            $errorMessage .= " - It should not contain these characters: \\ / ? * [ or ]\n";
            $errorMessage .= " - It should be unique";
            throw new InvalidSheetNameException($errorMessage);
        }

        $this->name = $name;
        self::$SHEETS_NAME_USED[$this->index] = $name;

        return $this;
    }

    /**
     * Returns whether the given sheet's name is valid.
     * @see Sheet::setName for validity rules.
     *
     * @param string $name
     * @return bool TRUE if the name is valid, FALSE otherwise.
     */
    protected function isNameValid($name)
    {
        if (!is_string($name)) {
            return false;
        }

        $nameLength = $this->getStringLength($name);
        $hasValidLength = ($nameLength > 0 && $nameLength <= self::MAX_LENGTH_SHEET_NAME);
        $containsInvalidCharacters = $this->doesContainInvalidCharacters($name);
        $isNameUnique = $this->isNameUnique($name);

        return ($hasValidLength && !$containsInvalidCharacters && $isNameUnique);
    }

    /**
     * Returns the length of the given string.
     * It uses the multi-bytes function is available.
     * @see strlen
     * @see mb_strlen
     *
     * @param string $string
     * @return int
     */
    protected function getStringLength($string)
    {
        return extension_loaded('mbstring') ? mb_strlen($string) : strlen($string);
    }

    /**
     * Returns the position of the given character/substring in the given string.
     * It uses the multi-bytes function is available.
     * @see strpos
     * @see mb_strpos
     *
     * @param string $string Haystack
     * @param string $char Needle
     * @return int Index of the char in the string if found (started at 0) or -1 if not found
     */
    protected function getCharPosition($string, $char)
    {
        $position = extension_loaded('mbstring') ? mb_strpos($string, $char) : strpos($string, $char);
        return ($position !== false) ? $position : -1;
    }

    /**
     * Returns whether the given name contains at least one invalid character.
     * @see Sheet::$INVALID_CHARACTERS_IN_SHEET_NAME for the full list.
     *
     * @param string $name
     * @return bool TRUE if the name contains invalid characters, FALSE otherwise.
     */
    protected function doesContainInvalidCharacters($name)
    {
        foreach (self::$INVALID_CHARACTERS_IN_SHEET_NAME as $invalidCharacter) {
            if ($this->getCharPosition($name, $invalidCharacter) !== -1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns whether the given name is unique.
     *
     * @param string $name
     * @return bool TRUE if the name is unique, FALSE otherwise.
     */
    protected function isNameUnique($name)
    {
        foreach (self::$SHEETS_NAME_USED as $sheetIndex => $sheetName) {
            if ($sheetIndex !== $this->index && $sheetName === $name) {
                return false;
            }
        }

        return true;
    }
}
