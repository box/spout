<?php

namespace Box\Spout\Writer;

use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Common\Helper\GlobalFunctionsHelper;
use Box\Spout\Common\Type;
use Box\Spout\Writer\Common\Creator\EntityFactory;
use Box\Spout\Writer\Style\StyleBuilder;

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
        $writer = null;

        switch ($writerType) {
            case Type::CSV:
                $writer = self::getCSVWriter();
                break;
            case Type::XLSX:
                $writer = self::getXLSXWriter();
                break;
            case Type::ODS:
                $writer = self::getODSWriter();
                break;
            default:
                throw new UnsupportedTypeException('No writers supporting the given type: ' . $writerType);
        }

        $writer->setGlobalFunctionsHelper(new GlobalFunctionsHelper());

        return $writer;
    }

    /**
     * @return CSV\Writer
     */
    private static function getCSVWriter()
    {
        $optionsManager = new CSV\Manager\OptionsManager();

        return new CSV\Writer($optionsManager);
    }

    /**
     * @return XLSX\Writer
     */
    private static function getXLSXWriter()
    {
        $styleBuilder = new StyleBuilder();
        $optionsManager = new XLSX\Manager\OptionsManager($styleBuilder);
        $generalFactory = new XLSX\Creator\InternalFactory(new EntityFactory());

        return new XLSX\Writer($optionsManager, $generalFactory);
    }

    /**
     * @return ODS\Writer
     */
    private static function getODSWriter()
    {
        $styleBuilder = new StyleBuilder();
        $optionsManager = new ODS\Manager\OptionsManager($styleBuilder);
        $generalFactory = new ODS\Creator\InternalFactory(new EntityFactory());

        return new ODS\Writer($optionsManager, $generalFactory);
    }
}
