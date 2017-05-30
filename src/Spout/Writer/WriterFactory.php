<?php

namespace Box\Spout\Writer;

use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Common\Helper\GlobalFunctionsHelper;
use Box\Spout\Common\Type;
use Box\Spout\Writer\Common\Creator\EntityFactory;
use Box\Spout\Writer\Common\Creator\ManagerFactory;
use Box\Spout\Writer\Common\Creator\Style\StyleBuilder;
use Box\Spout\Writer\Common\Manager\Style\StyleMerger;

/**
 * Class WriterFactory
 * This factory is used to create writers, based on the type of the file to be read.
 * It supports CSV, XLSX and ODS formats.
 *
 * @package Box\Spout\Writer
 */
class WriterFactory
{
    /**
     * This creates an instance of the appropriate writer, given the type of the file to be read
     *
     * @api
     * @param  string $writerType Type of the writer to instantiate
     * @return WriterInterface
     * @throws \Box\Spout\Common\Exception\UnsupportedTypeException
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

        return new CSV\Writer($optionsManager, $styleMerger, $globalFunctionsHelper);
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

        $entityFactory = new EntityFactory(new ManagerFactory());
        $internalFactory = new XLSX\Creator\InternalFactory($entityFactory);

        return new XLSX\Writer($optionsManager, $styleMerger, $globalFunctionsHelper, $internalFactory);
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

        $entityFactory = new EntityFactory(new ManagerFactory());
        $internalFactory = new ODS\Creator\InternalFactory($entityFactory);

        return new ODS\Writer($optionsManager, $styleMerger, $globalFunctionsHelper, $internalFactory);
    }
}
