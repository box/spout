<?php

namespace Box\Spout\Reader;

use Box\Spout\Common\Creator\HelperFactory;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Common\Type;
use Box\Spout\Reader\XLSX\Manager\SharedStringsCaching\CachingStrategyFactory;

/**
 * Class ReaderFactory
 * This factory is used to create readers, based on the type of the file to be read.
 * It supports CSV and XLSX formats.
 */
class ReaderFactory
{
    /**
     * This creates an instance of the appropriate reader, given the type of the file to be read
     *
     * @api
     * @param  string $readerType Type of the reader to instantiate
     * @throws \Box\Spout\Common\Exception\UnsupportedTypeException
     * @return ReaderInterface
     */
    public static function create($readerType)
    {
        switch ($readerType) {
            case Type::CSV: return self::getCSVReader();
            case Type::XLSX: return self::getXLSXReader();
            case Type::ODS: return self::getODSReader();
            default:
                throw new UnsupportedTypeException('No readers supporting the given type: ' . $readerType);
        }
    }

    /**
     * @return CSV\Reader
     */
    private static function getCSVReader()
    {
        $optionsManager = new CSV\Manager\OptionsManager();
        $helperFactory = new HelperFactory();
        $entityFactory = new CSV\Creator\EntityFactory($helperFactory);
        $globalFunctionsHelper = $helperFactory->createGlobalFunctionsHelper();

        return new CSV\Reader($optionsManager, $globalFunctionsHelper, $entityFactory);
    }

    /**
     * @return XLSX\Reader
     */
    private static function getXLSXReader()
    {
        $optionsManager = new XLSX\Manager\OptionsManager();
        $helperFactory = new XLSX\Creator\HelperFactory();
        $managerFactory = new XLSX\Creator\ManagerFactory($helperFactory, new CachingStrategyFactory());
        $entityFactory = new XLSX\Creator\EntityFactory($managerFactory, $helperFactory);
        $globalFunctionsHelper = $helperFactory->createGlobalFunctionsHelper();

        return new XLSX\Reader($optionsManager, $globalFunctionsHelper, $entityFactory, $managerFactory);
    }

    /**
     * @return ODS\Reader
     */
    private static function getODSReader()
    {
        $optionsManager = new ODS\Manager\OptionsManager();
        $helperFactory = new ODS\Creator\HelperFactory();
        $entityFactory = new ODS\Creator\EntityFactory($helperFactory);
        $globalFunctionsHelper = $helperFactory->createGlobalFunctionsHelper();

        return new ODS\Reader($optionsManager, $globalFunctionsHelper, $entityFactory);
    }
}
