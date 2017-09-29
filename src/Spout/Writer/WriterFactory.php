<?php

namespace Box\Spout\Writer;

use Box\Spout\Common\Creator\HelperFactory;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Common\Helper\GlobalFunctionsHelper;
use Box\Spout\Common\Type;
use Box\Spout\Writer\Common\Creator\EntityFactory;
use Box\Spout\Writer\Common\Creator\Style\StyleBuilder;
use Box\Spout\Writer\Common\Manager\Style\StyleMerger;

/**
 * Class WriterFactory
 * This factory is used to create writers, based on the type of the file to be read.
 * It supports CSV, XLSX and ODS formats.
 */
class WriterFactory
{
    /**
     * This creates an instance of the appropriate writer, given the type of the file to be read
     *
     * @api
     * @param  string $writerType Type of the writer to instantiate
     * @throws \Box\Spout\Common\Exception\UnsupportedTypeException
     * @return WriterInterface
     */
    public static function create($writerType)
    {
        switch ($writerType) {
            case Type::CSV: return self::getCSVWriter();
            case Type::XLSX: return self::getXLSXWriter();
            case Type::ODS: return self::getODSWriter();
            default:
                throw new UnsupportedTypeException('No writers supporting the given type: ' . $writerType);
        }
    }

    /**
     * @return CSV\Writer
     */
    private static function getCSVWriter()
    {
        $optionsManager = new CSV\Manager\OptionsManager();
        $styleMerger = new StyleMerger();
        $globalFunctionsHelper = new GlobalFunctionsHelper();

        $helperFactory = new HelperFactory();

        return new CSV\Writer($optionsManager, $styleMerger, $globalFunctionsHelper, $helperFactory);
    }

    /**
     * @return XLSX\Writer
     */
    private static function getXLSXWriter()
    {
        $styleBuilder = new StyleBuilder();
        $optionsManager = new XLSX\Manager\OptionsManager($styleBuilder);
        $styleMerger = new StyleMerger();
        $globalFunctionsHelper = new GlobalFunctionsHelper();

        $helperFactory = new XLSX\Creator\HelperFactory();
        $managerFactory = new XLSX\Creator\ManagerFactory(new EntityFactory(), $helperFactory);

        return new XLSX\Writer($optionsManager, $styleMerger, $globalFunctionsHelper, $helperFactory, $managerFactory);
    }

    /**
     * @return ODS\Writer
     */
    private static function getODSWriter()
    {
        $styleBuilder = new StyleBuilder();
        $optionsManager = new ODS\Manager\OptionsManager($styleBuilder);
        $styleMerger = new StyleMerger();
        $globalFunctionsHelper = new GlobalFunctionsHelper();

        $helperFactory = new ODS\Creator\HelperFactory();
        $managerFactory = new ODS\Creator\ManagerFactory(new EntityFactory(), $helperFactory);

        return new ODS\Writer($optionsManager, $styleMerger, $globalFunctionsHelper, $helperFactory, $managerFactory);
    }
}
