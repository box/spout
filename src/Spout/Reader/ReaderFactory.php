<?php

namespace Box\Spout\Reader;

use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Common\Helper\GlobalFunctionsHelper;
use Box\Spout\Common\Type;

/**
 * Class ReaderFactory
 * This factory is used to create readers, based on the type of the file to be read.
 * It supports CSV, ODS and XLSX formats.
 *
 * @package Box\Spout\Reader
 */
class ReaderFactory
{
    /**
     * This creates an instance of the appropriate reader, given the type of the file to be read
     *
     * @api
     * @param  string $readerType Type of the reader to instantiate
     * @return ReaderInterface
     * @throws \Box\Spout\Common\Exception\UnsupportedTypeException
     */
    public static function create($readerType)
    {
        $reader = null;

        switch ($readerType) {
            case Type::CSV:
                $reader = self::createCsvReader();
                break;
            case Type::XLSX:
                $reader = self::createXlsxReader();
                break;
            case Type::ODS:
                $reader = self::createOdsReader();
                break;
            default:
                throw new UnsupportedTypeException('No readers supporting the given type: ' . $readerType);
        }

        return $reader;
    }

    /**
     * @return CSV\Reader
     */
    public static function createCsvReader() {
        $reader = new CSV\Reader();
        self::setDefaultGlobalFunctionHelper($reader);

        return $reader;
    }

    /**
     * @return XLSX\Reader
     */
    public static function createXlsxReader() {
        $reader = new XLSX\Reader();
        self::setDefaultGlobalFunctionHelper($reader);

        return $reader;
    }

    /**
     * @return ODS\Reader
     */
    public static function createOdsReader() {
        $reader = new ODS\Reader();
        self::setDefaultGlobalFunctionHelper($reader);

        return $reader;
    }

    /**
     * @param AbstractReader $reader
     */
    private static function setDefaultGlobalFunctionHelper($reader)
    {
        $reader->setGlobalFunctionsHelper(new GlobalFunctionsHelper());
    }
}
