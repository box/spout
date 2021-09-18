<?php

namespace Box\Spout\Reader\Common\Creator;

use Box\Spout\Common\Type;
use Box\Spout\Reader\ReaderInterface;

/**
 * Class ReaderEntityFactory
 * Factory to create external entities
 */
class ReaderEntityFactory
{
    /**
     * Creates a reader by file extension
     *
     * @param string $path The path to the spreadsheet file. Supported extensions are .csv, .ods and .xlsx
     * @throws \Box\Spout\Common\Exception\UnsupportedTypeException
     * @return ReaderInterface
     */
    public static function createReaderFromFile(string $path)
    {
        return ReaderFactory::createFromFile($path);
    }

    /**
     * This creates an instance of a CSV reader
     *
     * @return \Box\Spout\Reader\CSV\Reader
     */
    public static function createCSVReader()
    {
        /** @var \Box\Spout\Reader\CSV\Reader $csvReader */
        $csvReader = ReaderFactory::createFromType(Type::CSV);

        return $csvReader;
    }

    /**
     * This creates an instance of a XLSX reader
     *
     * @return \Box\Spout\Reader\XLSX\Reader
     */
    public static function createXLSXReader()
    {
        /** @var \Box\Spout\Reader\XLSX\Reader $xlsxReader */
        $xlsxReader =  ReaderFactory::createFromType(Type::XLSX);

        return $xlsxReader;
    }

    /**
     * This creates an instance of a ODS reader
     *
     * @return \Box\Spout\Reader\ODS\Reader
     */
    public static function createODSReader()
    {
        /** @var \Box\Spout\Reader\ODS\Reader $odsReader */
        $odsReader = ReaderFactory::createFromType(Type::ODS);

        return $odsReader;
    }
}
